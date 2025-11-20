<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\FileEntry;
use App\Models\FileEntryShare;
use Illuminate\Http\Request;
use App\Models\UploadSettings;

use App\Http\Methods\FileDetailsDetector;
use App\Models\StorageProvider;
use Illuminate\Support\Facades\Hash;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Validator;
use Exception;

class ShareBrowseController extends Controller
{
    public function index(Request $request, string $token)
    {
        $share = FileEntryShare::with(['file.owner'])->where('token', $token)
            ->whereNull('revoked_at')
            ->firstOrFail();

        if ($share->isExpired()) {
            abort(403, 'This share link has expired.');
        }

        $root = $share->file;
        if (!$root) abort(404);

        // Single file → just preview it
        if ($root->type !== 'folder') {
            return redirect()->route('file.preview', $root->shared_id);
        }

        $folderSharedId = $request->query('folder');
        $currentFolder  = $root;

        if ($folderSharedId && $folderSharedId !== $root->shared_id) {
            $candidate = FileEntry::where('shared_id', $folderSharedId)->first();
            if (!$candidate || $candidate->type !== 'folder') abort(404);
            if (!$candidate->isDescendantOf($root)) abort(403);
            $currentFolder = $candidate;
        }

        $user = auth()->user();
        $recipientId = $user?->id;

        $curShareId = (int) $share->id;
        $ownerId    = (int) $root->user_id;

        /*
        |--------------------------------------------------------------------------
        | SHOW ONLY FILES THAT ARE ACTUALLY SHARED WITH THIS RECIPIENT
        |--------------------------------------------------------------------------
        */
        $children = FileEntry::with('uploader')
            ->where('parent_id', $currentFolder->id)
            ->where('user_id', $ownerId)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($share, $recipientId) {
                // Case 1: part of this folder share (root shared folder)
                $q->where('uploaded_via_share_id', $share->id);

                // Case 2: or explicitly shared file to same recipient
                $q->orWhereIn('id', function ($sub) use ($recipientId) {
                    $sub->select('file_entry_id')
                        ->from('file_entry_shares')
                        ->where('recipient_user_id', $recipientId)
                        ->whereNull('revoked_at');
                });
            })
            ->select('file_entries.*')
            ->selectRaw(
                'CASE WHEN file_entries.uploaded_via_share_id = ? 
                        AND file_entries.uploaded_by IS NOT NULL
                        AND file_entries.uploaded_by <> ?
                    THEN 1 ELSE 0 END AS show_uploader',
                [$curShareId, $ownerId]
            )
            ->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Breadcrumbs
        |--------------------------------------------------------------------------
        */
        $breadcrumbs = [];
        $walker = $currentFolder;
        while ($walker && $walker->id !== $root->id) {
            array_unshift($breadcrumbs, $walker);
            $walker = $walker->parent;
        }
        array_unshift($breadcrumbs, $root);

        $uploadMode = UploadSettings::getUploadMode();

        return view('frontend.user.shared.browse', [
            'token'        => $token,
            'root'         => $root,
            'current'      => $currentFolder,
            'children'     => $children,
            'breadcrumbs'  => $breadcrumbs,
            'owner'        => $root->owner,
            'share'        => $share,
            'uploadMode'   => $uploadMode,
        ]);
    }



    public function bulkMoveToTrash(Request $request, string $token)
    {
        $share = FileEntryShare::with('file.owner')->where('token', $token)->firstOrFail();
        if ($share->revoked_at) abort(403);
        if ($share->expires_at && now()->greaterThan($share->expires_at)) abort(403);

        $root = $share->file;
        if (!$root) abort(404);
        if ($root->type !== 'folder') abort(400); // bulk is folder context only

        // Parse selected items (ids: comma-separated OR file_ids: [])
        $fileIds = [];
        if ($request->filled('ids')) {
            $fileIds = array_filter(array_map('trim', explode(',', $request->input('ids'))));
        } elseif ($request->has('file_ids')) {
            $fileIds = array_filter(array_map('trim', (array) $request->input('file_ids', [])));
        }

        if (empty($fileIds)) {
            $msg = __('You have not selected any file');
            if ($request->expectsJson()) {
                return response()->json(['type' => 'error', 'message' => $msg], 400);
            }
            toastr()->error($msg);
            return back();
        }

        $ownerId = (int) $root->user_id;
        $actorId = (int) (Auth::id() ?? 0);

        // bulkMoveToTrash()
        
        $deleted = 0; $trashed = 0; $errors = [];

        foreach ($fileIds as $sid) {
            try {
                $entry = FileEntry::where('shared_id', $sid)
                    ->where('user_id', $ownerId)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$entry) { $errors[] = "Item {$sid} not found or already processed"; continue; }

                if ($entry->id !== $root->id && !$entry->isDescendantOf($root)) {
                    $errors[] = "Item {$sid} is outside the shared folder"; continue;
                }

                $action = $this->applySharedDeletePolicy($entry, $share, $ownerId, $actorId);

                if ($action === 'hard') {
                    $this->hardDeleteRecursive($entry);
                    $deleted++;
                } else { // trash
                    $this->softDeleteToTrash($entry, $ownerId);
                    $trashed++;
                }
            } catch (\Throwable $t) {
                \Log::error("Share bulk delete policy failed for {$sid}: ".$t->getMessage());
                $errors[] = "Failed to process {$sid}: ".$t->getMessage();
            }
        }

        if ($request->expectsJson()) {
            $ok = ($deleted + $trashed) > 0;
            return response()->json([
                'type'     => $ok ? 'success' : 'error',
                'message'  => $ok
                    ? "Processed: {$deleted} permanently deleted, {$trashed} moved to trash".(!empty($errors) ? ' (some failed)' : '')
                    : 'No items were processed',
                'deleted'  => $deleted,
                'trashed'  => $trashed,
                'errors'   => $errors,
            ], $ok ? 200 : 400);
        }

        if ($trashed) {
            toastr()->success(__('Moved to trash. Items will be permanently deleted after 30 days.'));
        } elseif ($deleted) {
            toastr()->success(__('Deleted permanently.'));
        } else {
            toastr()->error(__('No items were processed'));
        }

        $params = [];
        if ($folder = $request->input('folder')) $params['folder'] = $folder;
        return redirect()->route('user.shared.browse', array_merge(['token' => $token], $params));

    }

    // Optional single-item variant used by per-row action
    public function moveToTrash(Request $request, string $token, string $shared_id)
    {
        // Just reuse destroy() so both buttons behave with the same policy
        return $this->destroy($request, $token, $shared_id);
    }


    /** Recursively soft-delete children under a folder, constrained to the same owner. */
    private function moveShareChildrenToTrash(FileEntry $folder, int $ownerId): void
    {
        $children = FileEntry::where('parent_id', $folder->id)
            ->where('user_id', $ownerId)
            ->whereNull('deleted_at')
            ->get();

        foreach ($children as $child) {
            $child->update([
                'deleted_at' => Carbon::now(),
                'expiry_at'  => Carbon::now()->addDays(30),
            ]);

            if ($child->type === 'folder') {
                $this->moveShareChildrenToTrash($child, $ownerId);
            }
        }
    }


    
    public function upload(Request $request, string $token)
    {
        // 1) Validate & load share
        $share = FileEntryShare::with('file.owner')->where('token', $token)->firstOrFail();
        if ($share->revoked_at) abort(403, 'This share has been revoked.');
        if ($share->expires_at && now()->greaterThan($share->expires_at)) abort(403, 'This share has expired.');

        // Require edit permission to upload
        if (!in_array($share->permission, ['edit'])) {
            abort(403, 'You do not have permission to upload to this shared folder.');
        }

        $root = $share->file;
        if (!$root || $root->type !== 'folder') {
            abort(403, 'Uploads are only supported for shared folders.');
        }

        // 2) Validate request (mirror your UploadController’s rules)
        $uploadedFile = $request->file('file');
        $uploadedFileName = $uploadedFile?->getClientOriginalName();

        $validator = Validator::make($request->all(), [
            'password'          => ['nullable', 'max:255'],
            'upload_auto_delete'=> ['required', 'integer', 'min:0', 'max:365'],
            'parent_folder_id'  => ['nullable', 'string'], // shared_id of target folder inside the shared tree
            'size'              => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            $msg = implode(' ', $validator->errors()->all());
            return response()->json(['type' => 'error', 'msg' => $msg . ($uploadedFileName ? " ($uploadedFileName)" : '')], 422);
        }

        // 3) Subscription & limits (use the recipient’s subscription as your current UploadController does)
        if (!subscription()->is_subscribed) {
            return response()->json(['type' => 'error', 'msg' => lang('Login or create account to start uploading files', 'alerts')]);
        }
        if (subscription()->is_expired) {
            return response()->json(['type' => 'error', 'msg' => lang('Your subscription has been expired, renew it to start uploading files', 'alerts')]);
        }
        if (subscription()->is_canceled) {
            return response()->json(['type' => 'error', 'msg' => lang('Your subscription has been canceled, please contact us for more information', 'alerts')]);
        }

        // Auto-delete -> expiryAt (same logic as UploadController)
        if (!array_key_exists($request->upload_auto_delete, autoDeletePeriods())) {
            return response()->json(['type' => 'error', 'msg' => lang('Invalid file auto delete time', 'upload zone')]);
        }
        $expiryAt = autoDeletePeriods()[$request->upload_auto_delete]['days'] != 0
            ? autoDeletePeriods()[$request->upload_auto_delete]['datetime']
            : null;

        // Password protection (use plan feature)
        if ($request->has('password') && !is_null($request->password) && $request->password !== "undefined") {
            $request->password = subscription()->plan->password_protection ? Hash::make($request->password) : null;
        }

        // File size limits
        $maxFileSize = 100 * 1024 * 1024; // 100MB example
        if ($uploadedFile->getSize() > $maxFileSize) {
            return response()->json(['type' => 'error', 'msg' => lang('File size too large. Maximum allowed size is 100MB.', 'upload zone')]);
        }
        if (!is_null(subscription()->plan->file_size) && $request->size > subscription()->plan->file_size) {
            return response()->json(['type' => 'error', 'msg' => str_replace('{maxFileSize}', subscription()->formates->file_size, lang('File is too big, Max file size {maxFileSize}', 'upload zone'))]);
        }
        if (!is_null(subscription()->storage->remining->number) && $request->size > subscription()->storage->remining->number) {
            return response()->json(['type' => 'error', 'msg' => lang('insufficient storage space please ensure sufficient space', 'upload zone')]);
        }

        try {
            // 4) Chunk receiver
            $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));
            $storageProvider = StorageProvider::where([['symbol', env('FILESYSTEM_DRIVER')], ['status', 1]])->first();
            if (!$storageProvider) {
                return response()->json(['type' => 'error', 'msg' => lang('Unavailable storage provider', 'upload zone')]);
            }
            if ($receiver->isUploaded() === false) {
                return response()->json(['type' => 'error', 'msg' => str_replace('{filename}', $uploadedFileName, lang('Failed to upload ({filename})', 'upload zone'))]);
            }

            $save = $receiver->receive();
            if (!$save->isFinished()) {
                // Receiver will keep returning partial responses until finished — mimic your main uploader’s behavior
                return $save->handler()->getResponse();
            }

            // 5) Finished: build metadata
            $file    = $save->getFile();
            $fName   = $file->getClientOriginalName();
            $ext     = $file->getClientOriginalExtension();
            $mime    = (!is_null(getFileMimeType($ext))) ? getFileMimeType($ext) : $file->getMimeType();
            if (empty($ext)) { $ext = FileDetailsDetector::lookupExtension($mime); }

            $size = $file->getSize();
            if ($size == 0) {
                return response()->json(['type' => 'error', 'msg' => lang('Empty files cannot be uploaded', 'upload zone')]);
            }
            if (!is_null(subscription()->plan->file_size) && $size > subscription()->plan->file_size) {
                removeFile($file);
                return response()->json(['type' => 'error', 'msg' => str_replace('{maxFileSize}', subscription()->formates->file_size, lang('File is too big, Max file size {maxFileSize}', 'upload zone'))]);
            }
            if (!is_null(subscription()->storage->remining->number) && $size > subscription()->storage->remining->number) {
                removeFile($file);
                return response()->json(['type' => 'error', 'msg' => lang('insufficient storage space please ensure sufficient space', 'upload zone')]);
            }

            // 6) Resolve target folder inside the shared tree
            $targetSharedId = $request->input('parent_folder_id'); // shared_id of target
            $targetFolder   = $root; // default: root
            if ($targetSharedId && $targetSharedId !== $root->shared_id) {
                $candidate = FileEntry::where('shared_id', $targetSharedId)->first();
                if (!$candidate || $candidate->type !== 'folder') {
                    abort(404, 'Target folder not found.');
                }
                if (!$candidate->isDescendantOf($root)) {
                    abort(403, 'Target is not inside the shared folder.');
                }
                $targetFolder = $candidate;
            }

            // 7) IMPORTANT: save as the OWNER so it appears for both parties
            $ownerId     = $root->user_id;
            $parentDbId  = $targetFolder->id;
            $folderPath  = rtrim($targetFolder->getRawOriginal('path') ?: ('folders/'.$ownerId), '/');

            // === Auto-rename inside the target folder (owner's scope) ===
            $finalDisplayName = $this->resolveUniqueFilename($ownerId, $parentDbId, $fName);
            $storageFileName  = $finalDisplayName;

            // 8) Upload to storage (support handlers with/without a target filename parameter)
            $handler        = $storageProvider->handler;
            $uploadResponse = null;

            try {
                // Prefer handler::upload($file, $dir, $filename)
                $uploadResponse = $handler::upload($file, $folderPath, $storageFileName);
            } catch (\ArgumentCountError $e) {
                // Fallback: handler only accepts ($file, $dir). Enforce name via Storage::putFileAs.
                try {
                    // If the handler still needs to move the chunk temp file into place, let it run:
                    $tmpResp = $handler::upload($file, $folderPath);

                    // Then enforce our desired name using Laravel Storage if it didn't use our name:
                    if (!isset($tmpResp->filename) || $tmpResp->filename !== $storageFileName) {
                        $disk = env('FILESYSTEM_DRIVER');
                        $stored = \Illuminate\Support\Facades\Storage::disk($disk)
                            ->putFileAs($folderPath, $file, $storageFileName);

                        if (!$stored) {
                            return response()->json(['type' => 'error', 'msg' => 'Failed to upload file to storage.']);
                        }

                        // Normalize a response object
                        $uploadResponse = (object)[
                            'type'     => 'success',
                            'filename' => $storageFileName,
                            'link'     => $tmpResp->link ?? null,
                        ];
                    } else {
                        $uploadResponse = $tmpResp;
                    }
                } catch (\Throwable $t) {
                    return response()->json(['type' => 'error', 'msg' => 'Failed to upload file to storage.']);
                }
            }

            if ($uploadResponse->type === "error") {
                return $uploadResponse;
            }

            $storedFilename   = $uploadResponse->filename ?? $storageFileName;
            $completeFilePath = $folderPath . '/' . $storedFilename;

            // 9) Create DB record with resolved names
            $create = FileEntry::create([
                'ip'                    => vIpInfo()->ip,
                'shared_id'             => Str::random(15),
                'user_id'               => $ownerId,            // owner, so both see the file
                'parent_id'             => $parentDbId,
                'storage_provider_id'   => $storageProvider->id,

                'name'                  => $finalDisplayName,   // display name with possible (n)
                'filename'              => $storedFilename,     // actual object name in storage
                'mime'                  => $mime,
                'size'                  => $size,
                'extension'             => $ext,
                'type'                  => getFileType($mime),
                'path'                  => $completeFilePath,   // folders/{owner}/.../{filename}
                'link'                  => $uploadResponse->link ?? null,
                'password'              => $request->password,
                'expiry_at'             => $expiryAt,
                'access_status'         => 0,

                'uploaded_by'           => auth()->id(),
                'uploaded_via_share_id' => $share->id,
            ]);

            $previewId   = null;
            $previewLink = null;
            if (in_array($create->type, ['image','pdf'])) {
                $previewId   = 'preview_' . $create->shared_id;
                $previewLink = route('file.preview', $create->shared_id);
            }

            return response()->json([
                'type'          => 'success',
                'download_id'   => 'download_'.$create->shared_id,
                'download_link' => route('file.download', $create->shared_id),
                'preview_id'    => $previewId,
                'preview_link'  => $previewLink,
            ]);
        } catch (Exception $e) {
            Log::error('Shared upload failed', ['error' => $e->getMessage()]);
            return response()->json(['type' => 'error', 'msg' => 'Upload failed: '.$e->getMessage()], 500);
        }
    }


    /**
     * Return a unique filename for (user_id, parent_id).
     * Produces: "name.ext", "name (1).ext", "name (2).ext", ...
     */
    protected function resolveUniqueFilename(int $userId, ?int $parentId, string $originalName): string
    {
        $dotPos = strrpos($originalName, '.');
        if ($dotPos !== false) {
            $base = substr($originalName, 0, $dotPos);
            $extWithDot = substr($originalName, $dotPos); // includes ".ext"
        } else {
            $base = $originalName;
            $extWithDot = '';
        }

        // If no duplicate, return as-is
        if (!$this->nameExists($userId, $parentId, $originalName)) {
            return $originalName;
        }

        // Try "name (1).ext", "name (2).ext", ...
        $n = 1;
        do {
            $candidate = "{$base} ({$n}){$extWithDot}";
            $n++;
        } while ($this->nameExists($userId, $parentId, $candidate));

        return $candidate;
    }

    /** Check if (user_id, parent_id, name) exists for a non-folder, not soft-deleted. */
    protected function nameExists(int $userId, ?int $parentId, string $name): bool
    {
        return FileEntry::where('user_id', $userId)
            ->where('parent_id', $parentId)
            ->where('type', '!=', 'folder')
            ->whereNull('deleted_at')
            ->where('name', $name)
            ->exists();
    }


    /**
     * Edit (shared-side) — owner OR recipient with edit perm
     */
    public function edit(Request $request, string $token, string $shared_id)
    {
        $share = FileEntryShare::where('token', $token)
            ->whereNull('revoked_at')
            ->firstOrFail();

        if ($share->isExpired()) {
            abort(403, 'This share link has expired.');
        }

        $user        = auth()->user();
        $isOwner     = (int)$share->owner_id === (int)($user?->id ?? 0);
        $isRecipient = $share->recipient_user_id && (int)$share->recipient_user_id === (int)($user?->id ?? 0);

        // Must be owner or recipient with edit rights
        if (!($isOwner || $isRecipient)) {
            abort(403);
        }
        if (!$isOwner && !$share->canEdit()) {
            abort(403, 'You do not have edit permission.');
        }

        // Fix: lookup depends on whether share is folder or file
        $query = FileEntry::where('shared_id', $shared_id);
            // ->notExpired()
            // ->notTrashed();

        if ($share->file && $share->file->type === 'folder') {
            // folder share → must belong to owner
            $query->where('user_id', $share->owner_id);
        }

        $fileEntry = $query->first();

        if (!$fileEntry) {
            abort(404, 'File not found.');
        }

        // Ensure file is inside the shared folder if folder-share
        if ($share->file && $share->file->type === 'folder'
            && $fileEntry->id !== $share->file->id
            && !$fileEntry->isDescendantOf($share->file)) {
            abort(403, 'File is not within the shared folder.');
        }

        $uploadMode = UploadSettings::getUploadMode();

        $canEditAll   = $isOwner || ($isRecipient && $share->canEdit());
        $canEditMeta  = $isOwner || $share->canEdit();
        $canDelete    = $isOwner || ($isRecipient && $share->canEdit());

        return view('frontend.user.shared.edit', [
            'fileEntry'   => $fileEntry,
            'uploadMode'  => $uploadMode,
            'sharedEdit'  => true,
            'canEditAll'  => $canEditAll,
            'canEditMeta' => $canEditMeta,
            'canDelete'   => $canDelete,
            'token'       => $token,
        ]);
    }


    /**
     * Update (shared-side) — rename for editors; public/private/password for editors too (owner or recipient)
     */
public function update(Request $request, string $token, string $shared_id)
{
    // 1. Get the share
    $share = FileEntryShare::where('token', $token)
        ->whereNull('revoked_at')
        ->firstOrFail();

    if ($share->isExpired()) {
        abort(403, 'This share link has expired.');
    }

    $user        = auth()->user();
    $isOwner     = $user && (int)$share->owner_id === (int)$user->id;
    $isRecipient = $user && $share->recipient_user_id && (int)$share->recipient_user_id === (int)$user->id;

    // 2. Permission: only owner OR recipient with edit rights
    if (!$isOwner && !($isRecipient && $share->canEdit())) {
        abort(403, 'You do not have edit permission.');
    }

    // 3. Resolve the file entry
    $fileEntry = null;

    if ($share->file) {
        if ($share->file->type === 'folder') {
            // Folder share → must match folder or a descendant
            $fileEntry = FileEntry::where('shared_id', $shared_id)
                ->notExpired()
                ->notTrashed()
                ->first();

            if (!$fileEntry || ($fileEntry->id !== $share->file->id && !$fileEntry->isDescendantOf($share->file))) {
                abort(403, 'File is not within the shared folder.');
            }
        } else {
            // ✅ Single file share → always use the shared file itself
            $fileEntry = $share->file;
        }
    }

    if (!$fileEntry) {
        abort(403, 'File not found or not accessible.');
    }

    // 4. Validate request
    $data = $request->validate([
        'filename'      => ['required','string','max:255'],
        'access_status' => ['required','boolean'],
        'password'      => ['nullable','string','max:255'],
    ]);

    // 5. Update file
    $fileEntry->name          = $data['filename'];
    $fileEntry->access_status = (int)$data['access_status'];

    if ($request->filled('password')) {
        $fileEntry->password = (optional(subscription()->plan)->password_protection ?? false)
            ? Hash::make($request->password)
            : null;
    } elseif ($request->has('password')) {
        $fileEntry->password = null;
    }

    $fileEntry->save();

    toastr()->success(lang('Updated successfully', 'files'));
    return back();
}


public function destroy(Request $request, string $token, string $shared_id)
{
    $share = FileEntryShare::with('file')
        ->where('token', $token)
        ->whereNull('revoked_at')
        ->first();

    if (!$share || $share->isExpired()) {
        return redirect()->route('user.shared.index')->with('error', __('This share is no longer available or has expired.'));
    }

    $user = auth()->user();
    $actorId = (int) ($user?->id ?? 0);
    $ownerId = (int) $share->owner_id;
    $isOwner = $actorId === $ownerId;
    $isRecipient = $share->recipient_user_id && (int)$share->recipient_user_id === $actorId;
    $isFolderShare = $share->file && $share->file->type === 'folder';

    // Get file entry from owner's scope
    $entry = FileEntry::where('shared_id', $shared_id)
        ->where('user_id', $ownerId)
        ->notTrashed()
        ->first();

    if (!$entry) {
        return redirect()->route('user.shared.index')->with('error', __('The file is no longer available.'));
    }

    // Validate: ensure the file belongs to this share
    if ($share->file) {
        if ($isFolderShare) {
            if ($entry->id !== $share->file->id && !$entry->isDescendantOf($share->file)) {
                abort(403, 'File is not within the shared folder.');
            }
        } else {
            if ($entry->id !== $share->file->id) {
                abort(403, 'File not part of this share.');
            }
        }
    }

    /*
     |--------------------------------------------------------------------------
     | OWNER DELETES → normal soft delete
     |--------------------------------------------------------------------------
     */
    if ($isOwner) {
        $this->softDeleteToTrash($entry, $ownerId);
        toastr()->success(__('Moved to trash. Items will be permanently deleted after 30 days.'));
        return $this->redirectAfterDelete($request, $token, $ownerId);
    }

    /*
     |--------------------------------------------------------------------------
     | RECIPIENT DELETES
     |--------------------------------------------------------------------------
     */
    if (!$isRecipient) {
        abort(403, 'Unauthorized action.');
    }

    // --- 1. Recipient uploaded file themselves (via this share) ---
    if (
        $share->canEdit() &&
        $entry->uploaded_via_share_id === $share->id &&
        $entry->uploaded_by === $actorId
    ) {
        // Update user_id, nullify uploaded_by and uploaded_via_share_id, and soft-delete
        $entry->update([
            'user_id' => $actorId,
            'uploaded_by' => null,
            'uploaded_via_share_id' => null,
            'deleted_at' => now(),
            'expiry_at' => now()->addDays(30),
        ]);
        // Remove all share records for this file (for both sides)
        FileEntryShare::where('file_entry_id', $entry->id)->delete();
        toastr()->success(__('File moved to your trash and removed from shared items.'));
        return $this->redirectAfterDelete($request, $token, $ownerId);
    }

    // --- 2. Recipient deletes file inside a folder share (owner's file) ---
    if ($isFolderShare && $entry->id !== $share->file->id) {
        // Move file to owner's trash
        $this->softDeleteToTrash($entry, $ownerId);
        // Clean up share records for this file
        FileEntryShare::where('file_entry_id', $entry->id)
            ->where('recipient_user_id', $actorId)
            ->delete();
        toastr()->success(__('Removed from shared and moved to owner\'s trash.'));
        return $this->redirectAfterDelete($request, $token, $ownerId);
    }

    // --- 3. Recipient deletes single-file share (outside folder) ---
    if (!$isFolderShare) {
        // Move the file to owner’s trash
        $this->softDeleteToTrash($entry, $ownerId);
        // Remove the share record
        $share->delete();
        toastr()->success(__('Removed from your shared items and moved to owner\'s trash.'));
        return redirect()->route('user.shared.index');
    }

    // --- 4. Recipient deletes the shared root folder ---
    if ($isFolderShare && $entry->id === $share->file->id) {
        // Move the folder (and all contents) to owner’s trash
        $this->softDeleteToTrash($entry, $ownerId);
        // Remove all share records for this folder and its children
        $childIds = FileEntry::descendantIdsFor($entry->id);
        FileEntryShare::whereIn('file_entry_id', array_merge([$entry->id], $childIds))
            ->where('recipient_user_id', $actorId)
            ->delete();
        toastr()->success(__('Shared folder and its contents have been removed and moved to owner\'s trash.'));
        return redirect()->route('user.shared.index');
    }

    abort(403, 'Unauthorized action.');
}



// public function destroy(Request $request, string $token, string $shared_id)
// {
//     $share = FileEntryShare::with('file')->where('token', $token)->firstOrFail();

//     if ($share->isExpired()) {
//         abort(403, 'This share link has expired.');
//     }

//     $user        = auth()->user();
//     $isOwner     = $user && (int)$share->owner_id === (int)$user->id;
//     $isRecipient = $user && $share->recipient_user_id && (int)$share->recipient_user_id === (int)$user->id;

//     if (!$isOwner && !$isRecipient) {
//         abort(403, 'You do not have delete permission.');
//     }

//     // The specific file/folder being deleted
//     $entry = FileEntry::where('shared_id', $shared_id)
//         ->whereNull('deleted_at')
//         ->firstOrFail();

//     // Ensure the file is within the share scope
//     if ($share->file->type === 'folder') {
//         if ($entry->id !== $share->file->id && !$entry->isDescendantOf($share->file)) {
//             abort(403, 'File is not within this shared folder.');
//         }
//     } else {
//         if ($entry->id !== $share->file->id) {
//             abort(403, 'File not part of this share.');
//         }
//     }

//     $ownerId = (int)$share->owner_id;
//     $actorId = (int)($user?->id ?? 0);

//     $action = $this->applySharedDeletePolicy($entry, $share, $ownerId, $actorId);

//     // ---- Perform action ----
//     if ($action === 'hard') {
//         $this->hardDeleteRecursive($entry);
//         toastr()->success(__('Deleted permanently.'));
//     } elseif ($action === 'trash') {
//         $this->softDeleteToTrash($entry, $ownerId);
//         toastr()->success(__('Moved to trash. Items will be permanently deleted after 30 days.'));
//     } elseif ($action === 'unshare') {
//         if ($entry->id === $share->file->id) {
//             // deleting the main share
//             $share->delete();
//         } else {
//             // file inside shared folder → create/delete per-file share row
//             FileEntryShare::updateOrCreate(
//                 [
//                     'file_entry_id'     => $entry->id,
//                     'owner_id'          => $share->owner_id,
//                     'recipient_user_id' => $share->recipient_user_id,
//                 ],
//                 []
//             )->delete();
//         }

//     toastr()->success(__('Removed from your shared items.'));
//     return redirect()->route('user.shared.index');
// }


//     // Owner deletes → stay in browse
//     $params = ['token' => $token];
//     if ($folder = $request->input('folder')) {
//         $params['folder'] = $folder;
//     }
//     return redirect()->route('user.shared.browse', $params);
// }






//--04-10
// public function destroy(Request $request, string $token, string $shared_id)
// {
//     $share = FileEntryShare::where('token', $token)->firstOrFail();

//     if ($share->isExpired()) {
//         abort(403, 'This share link has expired.');
//     }

//     $user        = auth()->user();
//     $isOwner     = $user && (int)$share->owner_id === (int)$user->id;
//     $isRecipient = $user && $share->recipient_user_id && (int)$share->recipient_user_id === (int)$user->id;

//     if (!$isOwner && !$isRecipient) {
//         abort(403, 'You do not have delete permission.');
//     }

//     // Load file entry
//     $entry = FileEntry::where('shared_id', $shared_id)->firstOrFail();

//     // ✅ Case 1: Recipient deletes → just remove the share record
//     if ($isRecipient) {
//         // if folder share → recipient wants to unshare that file only
//         if ($entry->id === $share->file->id) {
//             // deleting the root share itself
//             $share->delete();
//         } else {
//             // child file inside a shared folder → remove recipient’s access
//             FileEntryShare::where('file_entry_id', $entry->id)
//                 ->where('recipient_user_id', $user->id)
//                 ->delete();
//         }

//         toastr()->success(__('Removed from your shared items.'));
//         return redirect()->route('user.shared.index'); // back to "Shared with me"
//     }

//     // ✅ Case 2: Owner deletes → apply trash policy on the real file
//     if ($isOwner) {
//         if ($entry->type === 'folder') {
//             $this->softDeleteToTrash($entry, $share->owner_id);
//         } else {
//             $this->softDeleteToTrash($entry, $share->owner_id);
//         }

//         toastr()->success(__('Moved to trash. Items will be permanently deleted after 30 days.'));

//         $params = ['token' => $token];
//         if ($folder = $request->input('folder')) {
//             $params['folder'] = $folder;
//         }
//         return redirect()->route('user.shared.browse', $params);
//     }

//     abort(403, 'Unauthorized action.');
// }



// <form action="{{ route('shared.delete', ['token' => $token, 'shared_id' => $fileEntry->shared_id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this file?');">
//     @csrf
//     @method('DELETE')
//     <button type="submit" class="btn btn-danger">Delete</button>
// </form>



    /**
     * Decide action for shared-side delete:
     * - If uploaded by recipient via this share  => HARD DELETE
     * - Else if actor is owner (and item is owner's) => SOFT DELETE to trash
     * - Else (actor is recipient, owner-uploaded) => MOVE to owner's root (unshare)
     *
     * Returns: 'hard', 'trash', or 'move'
     */
private function applySharedDeletePolicy(
    FileEntry $entry,
    FileEntryShare $share,
    int $ownerId,
    ?int $actorId
): string {
    $recipientId = $share->recipient_user_id ? (int)$share->recipient_user_id : null;

    // If recipient uploaded this file via this share → allow hard delete
    $uploadedByRecipientViaThisShare =
        $entry->uploaded_via_share_id === (int)$share->id
        && $entry->uploaded_by
        && $recipientId
        && (int)$entry->uploaded_by === $recipientId;

    if ($uploadedByRecipientViaThisShare) {
        return 'hard';   // recipient can delete their own uploads permanently
    }

    if ($actorId === $ownerId) {
        return 'trash';  // owner moves their file to trash
    }

    return 'unshare';    // recipient removes from "Shared with me"
}





    /**
     * Hard delete an entry (and subtree for folders) from storage + DB.
     */
    private function hardDeleteRecursive(FileEntry $entry): void
    {
        if ($entry->type === 'folder') {
            $children = FileEntry::where('parent_id', $entry->id)->get();
            foreach ($children as $child) {
                $this->hardDeleteRecursive($child);
            }
        } else {
            try {
                if ($entry->path && $entry->storageProvider) {
                    $handler = $entry->storageProvider->handler;
                    $handler::delete($entry->path);
                }
            } catch (\Throwable $t) {
                \Log::warning("Failed to delete storage object {$entry->path}: ".$t->getMessage());
            }
        }

        // finally, delete DB row
        $entry->delete();
    }

    /**
     * Move an entry (and subtree) out of the shared folder to the owner's root.
     * Note: we only change DB placement; we don't physically move storage objects.
     * If you must also move storage paths, you can extend this.
     */
    private function moveEntryToOwnerRoot(FileEntry $entry, int $ownerId): void
    {
        // Owner's root is parent_id = null
        $this->reparentSubtree($entry, null, $ownerId);
    }

    /** Re-parent subtree under a new parent_id (null = root). */
    private function reparentSubtree(FileEntry $entry, ?int $newParentId, int $ownerId): void
    {
        $entry->update([
            'parent_id' => $newParentId,
            // keep 'path' unchanged to avoid heavy storage moves; adjust if your UI shows path
        ]);

        if ($entry->type === 'folder') {
            $children = FileEntry::where('parent_id', $entry->id)
                ->where('user_id', $ownerId)
                ->get();

            foreach ($children as $child) {
                // children keep the same relative parent chain (we didn't change folder id)
                // so nothing to do here unless you want to physically move paths
                // If you do want to maintain a logical path string, recompute here.
            }
        }
    }

    /**
     * Soft delete (+expiry) and cascade for folders.
     */
    private function softDeleteToTrash(FileEntry $entry, int $ownerId): void
    {
        $entry->update([
            'deleted_at' => now(),
            'expiry_at' => now()->addDays(30),
        ]);
        if ($entry->type === 'folder') {
            $this->moveShareChildrenToTrash($entry, $ownerId);
        }
    }

    /**
 * Check if the share is still accessible.
 */
private function isShareAccessible(string $token): bool
{
    return FileEntryShare::where('token', $token)
        ->whereNull('revoked_at')
        ->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        })
        ->exists();
}

/**
 * Check if the folder is still accessible and not trashed.
 */
private function isFolderAccessible(string $shared_id, int $ownerId): bool
{
    return FileEntry::where('shared_id', $shared_id)
        ->where('user_id', $ownerId)
        ->notTrashed()
        ->exists();
}

/**
 * Handle redirect after deletion, checking share and folder accessibility.
 */
private function redirectAfterDelete(Request $request, string $token, int $ownerId)
{
    $params = ['token' => $token];
    if ($folder = $request->input('folder')) {
        $params['folder'] = $folder;
        if (!$this->isFolderAccessible($folder, $ownerId)) {
            return redirect()->route('user.shared.index')->with('success', toastr()->getMessage());
        }
    }
    if (!$this->isShareAccessible($token)) {
        return redirect()->route('user.shared.index')->with('success', toastr()->getMessage());
    }
    return redirect()->route('user.shared.browse', $params);
}


}
