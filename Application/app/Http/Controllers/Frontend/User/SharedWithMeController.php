<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\FileEntry;
use App\Models\FileEntryShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UploadSettings;

class SharedWithMeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $email = strtolower($user->email);

        // Fetch all shares to this user
        $shares = FileEntryShare::with([
            'file.owner', // User associated with user_id (User 1)
            'file.uploader', // User associated with uploaded_by (User 2)
            'file.parent'
        ])
            ->whereNull('revoked_at')
            ->where(function ($q) use ($user, $email) {
                $q->where('recipient_user_id', $user->id)
                ->orWhere(function ($q2) use ($email) {
                    $q2->whereNull('recipient_user_id')->where('recipient_email', $email);
                });
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->get();

        // Build a set of all shared file IDs for quick lookup
        $sharedFileIds = $shares->pluck('file_entry_id')->filter()->unique()->all();

        // Keep only top-level shares: drop any share whose ancestor is also shared
        $shares = $shares->filter(function ($s) use ($sharedFileIds) {
            $f = $s->file;
            if (!$f) return false;
            $p = $f->parent;
            while ($p) {
                if (in_array($p->id, $sharedFileIds, true)) return false; // ancestor already shared
                $p = $p->parent;
            }
            return true;
        })->values();

        $uploadMode = UploadSettings::getUploadMode();

        return view('frontend.user.shared.index', compact('shares', 'uploadMode'));
    }

    
    public function summary(Request $request)
    {
        $user = Auth::user();

        // Base query: items shared with the current user (by account or email)
        $base = FileEntryShare::with(['file.owner'])    // requires relations file() on FileEntryShare and owner() on FileEntry
            ->whereNull('revoked_at')
            ->where(function ($q) use ($user) {
                $q->where('recipient_user_id', $user->id)
                  ->orWhere('recipient_email', $user->email);
            });

        $contextFile = null;

        // Optional filter by specific file shared_id
        if ($request->filled('file')) {
            $file = FileEntry::with('owner')->where('shared_id', $request->input('file'))->first();
            if ($file) {
                $contextFile = [
                    'name'      => $file->name,
                    'type'      => $file->type,
                    'shared_id' => $file->shared_id,
                ];
                $base->where('file_entry_id', $file->id);
            }
        }

        $shares = $base->orderByDesc('id')->limit(12)->get();

        // Fallback: if filtering to a file yielded nothing, return latest global list
        if ($request->filled('file') && $shares->isEmpty()) {
            $shares = FileEntryShare::with(['file.owner'])
                ->whereNull('revoked_at')
                ->where(function ($q) use ($user) {
                    $q->where('recipient_user_id', $user->id)
                      ->orWhere('recipient_email', $user->email);
                })
                ->orderByDesc('id')->limit(12)->get();
        }

        // Map safely (avoid null owner/file fatals)
        $out = $shares->map(function ($s) {
            $file  = $s->file;            // may be null if row is stale
            $owner = $file?->owner;       // may be null

            return [
                'id'         => $s->id,
                'token'      => $s->token,
                'permission' => $s->permission,   // view/comment/edit
                'file' => [
                    'name'      => $file?->name ?? 'Unknown file',
                    'type'      => $file?->type ?? 'file',
                    'shared_id' => $file?->shared_id,
                    'preview'   => $file ? route('file.preview', $file->shared_id) : null,
                ],
                'owner' => [
                    'name'  => $owner?->name
                        ?? trim(($owner->firstname ?? '').' '.($owner->lastname ?? ''))
                        ?: null,
                    'email' => $owner->email ?? null,
                ],
                'open' => $s->token ? route('user.shared.browse', $s->token) : null,
            ];
        })->values();

        return response()->json([
            'context_file' => $contextFile,
            'shares'       => $out,
        ]);
    }
}
