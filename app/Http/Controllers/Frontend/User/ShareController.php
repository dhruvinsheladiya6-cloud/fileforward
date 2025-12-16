<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\FileEntry;
use App\Models\FileEntryShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\FileSharedMail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ShareController extends Controller
{
 /**
     * List shares for a given file.
     */
    public function index($shared_id)
    {
        $file = FileEntry::with(['owner', 'shares.recipient'])
            ->where('shared_id', $shared_id)
            ->whereNull('deleted_at')   // ✅ exclude trashed
            ->where(function ($q) {
                $q->whereNull('expiry_at')->orWhere('expiry_at', '>', now()); // ✅ exclude expired
            })
            ->firstOrFail();

        $this->authorizeShareActor($file);

        $owner = [
            'name'  => $file->owner->name
                ?? trim(($file->owner->firstname ?? '') . ' ' . ($file->owner->lastname ?? '')),
            'email' => $file->owner->email ?? null,
            'role'  => 'owner',
        ];

        $shares = $file->shares()
            ->whereNull('revoked_at') // ✅ only active shares
            ->get()
            ->map(function ($s) {
                return [
                    'id'           => $s->id,
                    'name'         => $s->recipient?->name,
                    'email'        => $s->recipient?->email ?? $s->recipient_email,
                    'permission'   => $s->permission, // view/comment/edit
                    'can_download' => (bool) $s->can_download,
                ];
            })->values();

        return response()->json([
            'file' => [
                'name'           => $file->name,
                'shared_id'      => $file->shared_id,
                'general_access' => $file->general_access, // off/viewer/commenter/editor
                'public_link'    => route('file.preview', $file->shared_id),
            ],
            'owner'  => $owner,
            'shares' => $shares,
        ]);
    }

    public function generalAccess(Request $request, $shared_id)
    {
        $file = FileEntry::where('shared_id', $shared_id)->firstOrFail();
        $this->authorizeShareActor($file);

        $data = $request->validate([
            'general_access' => ['required', Rule::in(['off','viewer','commenter','editor'])],
        ]);

        $file->general_access = $data['general_access'];
        $file->save();

        return response()->json(['message' => 'General access updated.']);
    }

    public function store(Request $request, $shared_id)
    {
        $file = FileEntry::where('shared_id', $shared_id)->firstOrFail();
        $this->authorizeShareActor($file);

        // Merge email into recipients if provided
        if (!$request->filled('recipients') && $request->filled('email')) {
            $request->merge(['recipients' => trim((string) $request->input('email'))]);
        }

        // Validate request data
        $data = $request->validate([
            'recipients'    => ['nullable', 'string', 'max:500'],
            'permission'    => ['nullable', Rule::in(['view', 'comment', 'edit'])],
            'can_download'  => ['nullable', 'boolean'],
            'can_reshare'   => ['nullable', 'boolean'],
            'expires_at'    => ['nullable', 'date', 'after:now'],
            'message'       => ['nullable', 'string', 'max:500'],
            'access_status' => ['required', Rule::in(['0', '1'])],
        ]);

        // Extract validated data with defaults
        $permission   = $data['permission'] ?? 'view';
        $canDownload  = (bool) ($data['can_download'] ?? true);
        $canReshare   = (bool) ($data['can_reshare'] ?? false);
        $expiresAt    = $data['expires_at'] ?? null;
        $message      = $data['message'] ?? null;
        $accessStatus = $data['access_status'];

        $ownerEmail = strtolower(auth()->user()->email);

        // Parse recipient emails
        $raw = trim((string) ($data['recipients'] ?? ''));
        $parsed = collect(preg_split('/[,\s]+/', $raw))
            ->map(fn($e) => strtolower(trim($e)))
            ->filter(fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        // Handle private access with no recipients
        if ($accessStatus === '0' && $parsed->isEmpty()) {
            DB::beginTransaction();
            try {
                $file->update(['access_status' => '0']);
                // Optionally revoke existing shares if setting to private
                FileEntryShare::where('file_entry_id', $file->id)
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);
                DB::commit();
                return response()->json([
                    'message' => 'File set to private.',
                    'access_status' => '0',
                ], 200);
            } catch (\Throwable $e) {
                DB::rollBack();
                \Log::error('Failed to set file to private: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return response()->json(['message' => 'Failed to update access status. Please try again.'], 500);
            }
        }

        // Validate recipients for public sharing
        if ($accessStatus === '1' && $parsed->isEmpty() && $request->filled('recipients')) {
            return response()->json(['message' => 'No valid recipient emails provided.'], 422);
        }

        // Prevent sharing with self
        $emails = $parsed->reject(fn($e) => $e === $ownerEmail)->values();
        if ($parsed->isNotEmpty() && $emails->isEmpty()) {
            return response()->json(['message' => 'You cannot share with your own email address.'], 422);
        }

        // Determine target file IDs (include descendants for folders)
        $targetIds = [$file->id];
        if ($file->type === 'folder') {
            $targetIds = array_values(array_unique(array_merge(
                $targetIds,
                FileEntry::descendantIdsFor($file->id)
            )));
        }

        $created = [];
        $updated = [];
        $reopened = [];

        DB::beginTransaction();
        try {
            // Update FileEntry access_status
            $file->update(['access_status' => $accessStatus]);

            // Process shares if recipients are provided
            foreach ($emails as $email) {
                $recipientUser = User::where('email', $email)->first();

                foreach ($targetIds as $targetId) {
                    $recipientMatch = function ($q) use ($recipientUser, $email) {
                        if ($recipientUser) {
                            $q->where('recipient_user_id', $recipientUser->id);
                        } else {
                            $q->whereNull('recipient_user_id')->where('recipient_email', $email);
                        }
                    };

                    // Update existing active share
                    $existing = FileEntryShare::where('file_entry_id', $targetId)
                        ->where($recipientMatch)
                        ->whereNull('revoked_at')
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'permission'   => $permission,
                            'can_download' => $canDownload,
                            'can_reshare'  => $canReshare,
                            'expires_at'   => $expiresAt,
                            'message'      => $message,
                        ]);
                        $updated[] = $existing->id;
                        continue;
                    }

                    // Reopen revoked share
                    $revoked = FileEntryShare::where('file_entry_id', $targetId)
                        ->where($recipientMatch)
                        ->whereNotNull('revoked_at')
                        ->latest('id')
                        ->first();

                    if ($revoked) {
                        $revoked->update([
                            'revoked_at'   => null,
                            'permission'   => $permission,
                            'can_download' => $canDownload,
                            'can_reshare'  => $canReshare,
                            'expires_at'   => $expiresAt,
                            'message'      => $message,
                            'token'        => Str::random(64),
                        ]);
                        $reopened[] = $revoked->id;

                        try {
                            Mail::to($email)->send(new FileSharedMail($revoked));
                        } catch (\Throwable $e) {
                            \Log::error("Failed to send share email (reopened): {$e->getMessage()}");
                        }
                        continue;
                    }

                    // Create new share
                    $share = FileEntryShare::create([
                        'file_entry_id'     => $targetId,
                        'owner_id'          => $file->user_id,
                        'recipient_user_id' => $recipientUser?->id,
                        'recipient_email'   => $email,
                        'permission'        => $permission,
                        'can_download'      => $canDownload,
                        'can_reshare'       => $canReshare,
                        'token'             => Str::random(64),
                        'expires_at'        => $expiresAt,
                        'message'           => $message,
                    ]);

                    try {
                        Mail::to($email)->send(new FileSharedMail($share));
                    } catch (\Throwable $e) {
                        \Log::error("Failed to send share email: {$e->getMessage()}");
                    }

                    $created[] = $share->id;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Share operation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Sharing failed. Please try again.'], 500);
        }

        // Build response message
        $parts = [];
        if ($created)  $parts[] = count($created) . ' created';
        if ($updated)  $parts[] = count($updated) . ' updated';
        if ($reopened) $parts[] = count($reopened) . ' reopened';

        $message = $parts ? 'Shares: ' . implode(', ', $parts) . '.' : 'Access status updated to ' . ($accessStatus === '1' ? 'public.' : 'private.');

        return response()->json([
            'message'       => $message,
            'access_status' => $accessStatus,
            'created'       => $created,
            'updated'       => $updated,
            'reopened'      => $reopened,
        ], 200);
    }


    public function update(Request $request, $shared_id, $shareId)
    {
        $file = FileEntry::where('shared_id', $shared_id)->firstOrFail();
        $this->authorizeShareActor($file);

        $share = FileEntryShare::where('id', $shareId)->where('file_entry_id', $file->id)->firstOrFail();

        $data = $request->validate([
            'permission'   => ['nullable', Rule::in(['view','comment','edit'])],
            'can_download' => ['nullable','boolean'],
            'can_reshare'  => ['nullable','boolean'],
            'expires_at'   => ['nullable','date'],
            'revoke'       => ['nullable','boolean'],
        ]);

        if (!empty($data['revoke'])) {
            $share->revoked_at = now();
        } else {
            foreach (['permission','can_download','can_reshare','expires_at'] as $f) {
                if (array_key_exists($f, $data)) { $share->{$f} = $data[$f]; }
            }
        }
        $share->save();

        return response()->json(['message' => 'Share updated.']);
    }

    public function destroy($shared_id, $shareId)
    {
        $file = FileEntry::where('shared_id', $shared_id)->firstOrFail();
        $this->authorizeShareActor($file);

        $share = FileEntryShare::where('id', $shareId)->where('file_entry_id', $file->id)->firstOrFail();
        $share->revoked_at = now();
        $share->save();

        return response()->json(['message' => 'Share removed.']);
    }

protected function authorizeShareActor(FileEntry $file): void
{
    $user = auth()->user();

    // Real owner can always share
    if ((int) $file->user_id === (int) $user->id) {
        return;
    }

    // Otherwise, see if this user has an active share on this file
    $share = FileEntryShare::query()
        ->where('file_entry_id', $file->id)
        ->whereNull('revoked_at')
        ->where(function ($q) use ($user) {
            $q->where('recipient_user_id', $user->id)
              ->orWhere('recipient_email', $user->email);
        })
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })
        ->first();

    // No share → no rights
    if (!$share) {
        abort(403, 'You are not allowed to share this file.');
    }

    // Only allow if this share grants editing or reshare capability
    if (!$share->canReshare() && !$share->canEdit()) {
        abort(403, 'You are not allowed to share this file.');
    }
}


    public function recentRecipients(Request $request)
    {
        $ownerId = auth()->id();
        $q       = trim((string) $request->query('q', ''));
        $limit   = min((int) $request->query('limit', 20), 50);

        $query = FileEntryShare::query()
            ->leftJoin('users', 'users.id', '=', 'file_entry_shares.recipient_user_id')
            ->where('file_entry_shares.owner_id', $ownerId)
            ->when($q !== '', function ($qq) use ($q) {
                $like = '%'.$q.'%';
                $qq->where(function ($w) use ($like) {
                    $w->where('users.email', 'like', $like)
                    ->orWhere('file_entry_shares.recipient_email', 'like', $like)
                    // search full name (firstname + lastname)
                    ->orWhereRaw(
                        "CONCAT(COALESCE(users.firstname,''), ' ', COALESCE(users.lastname,'')) LIKE ?",
                        [$like]
                    );
                });
            })
            // group by the recipient identity (user_id/email pair)
            ->groupBy('file_entry_shares.recipient_user_id', 'file_entry_shares.recipient_email')
            ->selectRaw("
                -- unified email, lowercased
                LOWER(
                    CASE
                        WHEN file_entry_shares.recipient_user_id IS NOT NULL
                            THEN COALESCE(MAX(users.email), '')
                        ELSE COALESCE(MAX(file_entry_shares.recipient_email), '')
                    END
                ) AS email,

                -- display name from firstname + lastname (aggregated to satisfy ONLY_FULL_GROUP_BY)
                TRIM(
                    CONCAT(
                        COALESCE(NULLIF(MAX(users.firstname), ''), ''),
                        ' ',
                        COALESCE(NULLIF(MAX(users.lastname), ''), '')
                    )
                ) AS raw_name,

                MAX(file_entry_shares.created_at) AS last_shared_at
            ")
            ->orderByDesc('last_shared_at')
            ->limit($limit);

        $rows = $query->get()->map(function ($r) {
            $name = trim((string) $r->raw_name);
            return [
                'name'  => $name !== '' ? $name : null,
                'email' => strtolower((string) $r->email),
            ];
        })->values();

        return response()->json(['recipients' => $rows]);
    }


}
