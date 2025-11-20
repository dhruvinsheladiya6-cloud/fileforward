<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\FileEntry;
use App\Models\FileEntryShare;
use App\Models\UploadSettings;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Hash;
use Illuminate\Http\Request;
use Validator;
use Str;
use Carbon\Carbon;

class FileController extends Controller
{
    public function index(Request $request)
    {
        // Get current folder context
        $currentFolderId = $request->get('folder');
        $currentFolder = null;
        $breadcrumbs = [];

        if ($currentFolderId) {
            $currentFolder = FileEntry::where('shared_id', $currentFolderId)
                ->where('type', 'folder')
                ->currentUser()
                ->notExpired()
                ->notTrashed()
                ->first();

            if ($currentFolder) {
                // Build breadcrumbs
                $breadcrumbs = $this->buildBreadcrumbs($currentFolder);
            } else {
                // Add redirect for invalid folder IDs
                return redirect()->route('user.files.index')
                    ->with('error', 'Folder not found or access denied.');
            }
        }

        // FIX THIS LINE - ADD notTrashed()
        $query = FileEntry::with(['uploader'])->currentUser()->notExpired()->notTrashed();

        // Filter by current folder or root level
        if ($currentFolder) {
            $query->where('parent_id', $currentFolder->id);
        } else {
            $query->hasNoParent();
        }

        // Search functionality
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($searchQuery) use ($q) {
                $searchQuery->where('shared_id', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%')
                    ->orWhere('filename', 'like', '%' . $q . '%')
                    ->orWhere('mime', 'like', '%' . $q . '%')
                    ->orWhere('extension', 'like', '%' . $q . '%');
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Sorting - folders first, then files
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        $allowedSorts = ['created_at', 'name', 'size', 'type'];
        $allowedOrders = ['asc', 'desc'];
        
        if (in_array($sortBy, $allowedSorts) && in_array($sortOrder, $allowedOrders)) {
            $query->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
                ->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
                ->orderByDesc('created_at');
        }

        $fileEntries = $query->paginate(20);
        
        // Append query parameters to pagination links
        $fileEntries->appends($request->query());

        $uploadMode = UploadSettings::getUploadMode();
        
        return view('frontend.user.files.index', [
            'fileEntries' => $fileEntries,
            'currentFolder' => $currentFolder,
            'breadcrumbs' => $breadcrumbs,
            'filters' => $request->only(['search', 'type', 'size', 'sort', 'order', 'folder']),
            'hasFilters' => $request->hasAny(['search', 'type', 'size']),
            'uploadMode' => $uploadMode,
        ]);
    }


    // AJAX endpoint for real-time filtering with folder context
    public function filter(Request $request)
    {
        $query = FileEntry::where(function ($query) {
            $query->currentUser();
        })->notExpired()->notTrashed();

        // Handle folder context
        $currentFolderId = $request->get('folder');
        if ($currentFolderId) {
            $currentFolder = FileEntry::where('shared_id', $currentFolderId)
                ->where('type', 'folder')
                ->currentUser()
                ->notExpired()
                ->notTrashed()
                ->first();
            
            if ($currentFolder) {
                $query->where('parent_id', $currentFolder->id);
            } else {
                $query->hasNoParent();
            }
        } else {
            $query->hasNoParent();
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $q = $request->search;
            $query->where(function ($searchQuery) use ($q) {
                $searchQuery->where('shared_id', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%')
                    ->orWhere('filename', 'like', '%' . $q . '%')
                    ->orWhere('mime', 'like', '%' . $q . '%')
                    ->orWhere('extension', 'like', '%' . $q . '%');
            });
        }

        // Type filter
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Size filter
        if ($request->has('size') && !empty($request->size)) {
            switch ($request->size) {
                case 'small':
                    $query->where('size', '<', 1048576);
                    break;
                case 'medium':
                    $query->whereBetween('size', [1048576, 10485760]);
                    break;
                case 'large':
                    $query->where('size', '>', 10485760);
                    break;
            }
        }

        // Sorting - folders first, then files
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        $allowedSorts = ['created_at', 'name', 'size', 'type'];
        $allowedOrders = ['asc', 'desc'];
        
        if (in_array($sortBy, $allowedSorts) && in_array($sortOrder, $allowedOrders)) {
            $query->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
                  ->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
                  ->orderByDesc('id');
        }

        $fileEntries = $query->get();

        return response()->json([
            'success' => true,
            'files' => $fileEntries->map(function ($file) {
                $data = [
                    'id' => $file->shared_id,
                    'name' => $file->name,
                    'type' => $file->type,
                    'size' => $file->size,
                    'extension' => $file->extension,
                    'created_at' => $file->created_at->format('Y-m-d H:i:s'),
                    'edit_url' => route('user.files.edit', $file->shared_id),
                    'formatted_size' => formatBytes($file->size),
                    'formatted_date' => vDate($file->created_at),
                ];

                // Add different URLs based on file type
                if ($file->type === 'folder') {
                    $data['folder_url'] = route('user.files.index', ['folder' => $file->shared_id]);
                } else {
                    $data['download_url'] = route('file.download', $file->shared_id);
                    $data['preview_url'] = isFileSupportPreview($file->type) ? route('file.preview', $file->shared_id) : null;
                }

                return $data;
            }),
            'count' => $fileEntries->count()
        ]);
    }
    
    public function edit($shared_id)
    {
        $uploadMode = UploadSettings::getUploadMode();
        // $fileEntry = FileEntry::where('shared_id', $shared_id)->currentUser()->notExpired()->firstOrFail();
        $fileEntry = FileEntry::where('shared_id', $shared_id)->currentUser()->notExpired()->notTrashed()->firstOrFail();
        return view('frontend.user.files.edit', ['fileEntry' => $fileEntry, 'uploadMode' => $uploadMode]);
    }

    public function update(Request $request, $shared_id)
    {
        // $fileEntry = FileEntry::where('shared_id', $shared_id)->currentUser()->notExpired()->first();
        $fileEntry = FileEntry::where('shared_id', $shared_id)->currentUser()->notExpired()->notTrashed()->first();
        if (is_null($fileEntry)) {
            toastr()->error(lang('File not found, missing or expired please refresh the page and try again', 'files'));
            return back();
        }

        $validator = Validator::make($request->all(), [
            'filename' => ['required', 'string', 'max:255'],
            'access_status' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back();
        }

        if ($request->has('password') && !is_null($request->password)) {
            if (subscription()->plan->password_protection) {
                $request->password = Hash::make($request->password);
            } else {
                $request->password = null;
            }
        }

        $update = $fileEntry->update([
            'name' => $request->filename,
            'access_status' => $request->access_status,
            'password' => $request->password,
        ]);

        if ($update) {
            toastr()->success(lang('Updated successfully', 'files'));
            return back();
        }
    }

public function updateStatus(Request $request, $shared_id)
{
    $fileEntry = FileEntry::where('shared_id', $shared_id)
        ->notExpired()
        ->notTrashed()
        ->first();

    if (is_null($fileEntry)) {
        return response()->json(['message' => 'File not found, missing, or expired.'], 404);
    }

    // ✅ Allow owner or share-actor (with can_reshare/canEdit)
    $this->authorizeShareActor($fileEntry);

    $validator = Validator::make($request->all(), [
        'access_status' => ['required', Rule::in(['0', '1'])],
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => $validator->errors()->first()], 422);
    }

    DB::beginTransaction();
    try {
        // Update access_status
        $fileEntry->update(['access_status' => $request->access_status]);

        // If you want: revoke all shares when set to private
        // if ($request->access_status === '0') {
        //     FileEntryShare::where('file_entry_id', $fileEntry->id)
        //         ->whereNull('revoked_at')
        //         ->update(['revoked_at' => now()]);
        // }

        // Generate download link
        $download_link = '';
        if ($request->access_status === '1') {
            try {
                // Use the correct route name (you already use file.download elsewhere)
                $download_link = route('file.download', $fileEntry->shared_id);
            } catch (\Exception $e) {
                $download_link = '';
            }
        }

        DB::commit();

        return response()->json([
            'message'       => 'Access status updated successfully.',
            'access_status' => $request->access_status,
            'download_link' => $download_link,
        ], 200);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to update access status. Please try again.'], 500);
    }
}


//people with access 14-11-25
// public function sharedPeople($shared_id)
// {
//     $fileEntry = FileEntry::where('shared_id', $shared_id)
//         ->notExpired()
//         ->notTrashed()
//         ->with(['user', 'uploader']) // load file owner + uploader
//         ->first();

//     if (!$fileEntry) {
//         return response()->json(['message' => 'File not found'], 404);
//     }

//     // ✅ Allow owner or share-actor (with can_reshare/canEdit)
//     $this->authorizeShareActor($fileEntry);

//     $people = FileEntryShare::where('file_entry_id', $fileEntry->id)
//         ->with(['recipient'])
//         ->get()
//         ->map(function ($share) {
//             return [
//                 'id'              => $share->id,
//                 'recipient_email' => $share->recipient_email,
//                 'recipient_name'  => $share->recipient ? $share->recipient->name : $share->recipient_email,
//                 'permission'      => $share->permission,
//                 'status'          => $share->isActive() ? 'active' : 'inactive',
//                 'revoked_at'      => $share->revoked_at,
//                 'expired'         => $share->isExpired(),
//                 'can_download'    => $share->can_download,
//                 'can_reshare'     => $share->can_reshare,
//             ];
//         });

//     // FILE OWNER (user_id on FileEntry)
//     $fileOwner = $fileEntry->user;
//     $ownerInfo = null;

//     if ($fileOwner) {
//         $ownerInfo = [
//             'id'    => $fileOwner->id,
//             'email' => $fileOwner->email,
//             'name'  => $fileOwner->name ?: $fileOwner->email,
//         ];
//     } else {
//         $ownerInfo = [
//             'id'    => auth()->id(),
//             'email' => auth()->user()->email,
//             'name'  => auth()->user()->name ?: auth()->user()->email,
//         ];
//     }

//     // UPLOADER (uploaded_by on FileEntry)
//     $uploaderInfo = null;
//     if ($fileEntry->uploader) {
//         $uploaderInfo = [
//             'id'    => $fileEntry->uploader->id,
//             'email' => $fileEntry->uploader->email,
//             'name'  => $fileEntry->uploader->name ?: $fileEntry->uploader->email,
//         ];
//     }

//     return response()->json([
//         'people'   => $people,
//         'owner'    => $ownerInfo,
//         'uploader' => $uploaderInfo,
//     ]);
// }

public function sharedPeople($shared_id)
{
    $fileEntry = FileEntry::where('shared_id', $shared_id)
        ->notExpired()
        ->notTrashed()
        ->with(['user', 'uploader', 'parent'])
        ->first();

    if (!$fileEntry) {
        return response()->json(['message' => 'File not found'], 404);
    }

    // ✅ owner OR share-actor (with can_reshare / canEdit), also via ancestor share
    $this->authorizeShareActor($fileEntry);

    // Collect this file + ancestor folder IDs
    $ids = [];
    $current = $fileEntry;
    while ($current) {
        $ids[] = $current->id;
        $current = $current->parent;
    }

    // Get all shares on this file OR any ancestor
    $shares = FileEntryShare::whereIn('file_entry_id', $ids)
        ->with('recipient')
        ->get();

    // Deduplicate by recipient identity (user_id/email pair)
    $people = $shares
        ->unique(function ($s) {
            return $s->recipient_user_id
                ? 'u:' . $s->recipient_user_id
                : 'e:' . strtolower((string) $s->recipient_email);
        })
        ->map(function ($share) {
            return [
                'id'              => $share->id,
                'recipient_email' => $share->recipient_email,
                'recipient_name'  => $share->recipient
                    ? ($share->recipient->name ?: $share->recipient->email)
                    : $share->recipient_email,
                'permission'      => $share->permission,
                'status'          => $share->isActive() ? 'active' : 'inactive',
                'revoked_at'      => $share->revoked_at,
                'expired'         => $share->isExpired(),
                'can_download'    => $share->can_download,
                'can_reshare'     => $share->can_reshare,
            ];
        })
        ->values();

    // FILE OWNER (user_id on FileEntry)
    $fileOwner = $fileEntry->user;
    if ($fileOwner) {
        $ownerInfo = [
            'id'    => $fileOwner->id,
            'email' => $fileOwner->email,
            'name'  => $fileOwner->name ?: $fileOwner->email,
        ];
    } else {
        $ownerInfo = [
            'id'    => auth()->id(),
            'email' => auth()->user()->email,
            'name'  => auth()->user()->name ?: auth()->user()->email,
        ];
    }

    // UPLOADER (uploaded_by on FileEntry) - might be recipient user
    $uploaderInfo = null;
    if ($fileEntry->uploader) {
        $uploaderInfo = [
            'id'    => $fileEntry->uploader->id,
            'email' => $fileEntry->uploader->email,
            'name'  => $fileEntry->uploader->name ?: $fileEntry->uploader->email,
        ];
    }

    return response()->json([
        'people'   => $people,
        'owner'    => $ownerInfo,
        'uploader' => $uploaderInfo,
    ]);
}






// Remove share - DELETE record completely
public function removeShare($share_id)
{
    $share = FileEntryShare::with('file')->where('id', $share_id)->first();

    if (!$share || !$share->file || $share->file->deleted_at) {
        return response()->json(['message' => 'Share not found'], 404);
    }

    // ✅ authorize owner or share-actor
    $this->authorizeShareActor($share->file);

    // Hard-delete record
    $share->delete();

    return response()->json([
        'message' => 'Access removed successfully'
    ]);
}


// Update share permission
public function updateSharePermission($share_id, Request $request)
{
    $validator = Validator::make($request->all(), [
        'permission' => ['required', Rule::in(['view', 'edit', 'comment'])],
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => $validator->errors()->first()], 422);
    }

    $share = FileEntryShare::with('file')->where('id', $share_id)->first();

    if (!$share || !$share->file || $share->file->deleted_at) {
        return response()->json(['message' => 'Share not found'], 404);
    }

    // ✅ allow owner or share-actor
    $this->authorizeShareActor($share->file);

    $share->update([
        'permission' => $request->permission
    ]);

    return response()->json([
        'message' => 'Permission updated successfully',
        'permission' => $share->permission
    ]);
}


// 14-11-25
// protected function authorizeShareActor(FileEntry $file): void
// {
//     /** @var User $user */
//     $user = Auth::user();

//     // Real owner can always manage file shares
//     if ((int) $file->user_id === (int) $user->id) {
//         return;
//     }

//     // Otherwise, check if this user has an active share on this file
//     $share = FileEntryShare::query()
//         ->where('file_entry_id', $file->id)
//         ->whereNull('revoked_at')
//         ->where(function ($q) use ($user) {
//             $q->where('recipient_user_id', $user->id)
//               ->orWhere('recipient_email', $user->email);
//         })
//         ->where(function ($q) {
//             $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
//         })
//         ->first();

//     // No share → no rights
//     if (!$share) {
//         abort(403, 'You are not allowed to manage this file.');
//     }

//     // Only allow if this share grants editing or reshare capability
//     if (!$share->canReshare() && !$share->canEdit()) {
//         abort(403, 'You are not allowed to share this file.');
//     }
// }
protected function authorizeShareActor(FileEntry $file): void
{
    $user = auth()->user();

    // Real owner can always share
    if ((int) $file->user_id === (int) $user->id) {
        return;
    }

    // Collect this file + all ancestor folder IDs
    $ids = [];
    $current = $file;
    while ($current) {
        $ids[] = $current->id;
        $current = $current->parent;
    }

    // Look for an active share on this file OR any ancestor
    $share = FileEntryShare::query()
        ->whereIn('file_entry_id', $ids)
        ->whereNull('revoked_at')
        ->where(function ($q) use ($user) {
            $q->where('recipient_user_id', $user->id)
              ->orWhere('recipient_email', $user->email);
        })
        ->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
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



    protected function authorizeOwner(FileEntry $file)
    {
        if ($file->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
    }
    
    public function moveToTrash($shared_id)
    {
        try {
            $user = auth()->user();
            $actorId = (int) ($user?->id ?? 0);

            $fileEntry = FileEntry::where('shared_id', $shared_id)
                ->currentUser()
                ->notExpired()
                ->notTrashed()
                ->first();

            if (!$fileEntry) {
                toastr()->error('File not found or already in trash');
                return back();
            }

            // Check if the file was uploaded by another user (recipient) via a share
            $isRecipientUploaded = $fileEntry->uploaded_via_share_id !== null 
                && $fileEntry->uploaded_by !== null 
                && $fileEntry->uploaded_by !== $actorId;

            if ($isRecipientUploaded) {
                // Update user_id to recipient, nullify uploaded_by and uploaded_via_share_id, and soft-delete
                $fileEntry->update([
                    'user_id' => $fileEntry->uploaded_by,
                    'uploaded_by' => null,
                    'uploaded_via_share_id' => null,
                    'deleted_at' => Carbon::now(),
                    'expiry_at' => Carbon::now()->addDays(30),
                ]);
            } else {
                // Standard soft-delete for owner’s files or non-recipient-uploaded files
                $fileEntry->update([
                    'deleted_at' => Carbon::now(),
                    'expiry_at' => Carbon::now()->addDays(30),
                ]);
            }

            // Handle folder children
            if ($fileEntry->type === 'folder') {
                $this->moveChildrenToTrash($fileEntry);
            }

            // Remove all related share records (for both sides)
            $this->deleteSharesForEntry($fileEntry);

            toastr()->success($isRecipientUploaded 
                ? __('File moved to recipient\'s trash and removed from shared items.') 
                : __('Moved to trash successfully. File will be permanently deleted after 30 days.'));

            if ($folderId = request('folder')) {
                return redirect()->route('user.files.index', ['folder' => $folderId]);
            }

            return redirect()->route('user.files.index');

        } catch (\Exception $e) {
            \Log::error('Move to trash error: ' . $e->getMessage());
            toastr()->error('Failed to move to trash: ' . $e->getMessage());
            return back();
        }
    }

    private function moveChildrenToTrash($folder)
    {
        $children = FileEntry::where('parent_id', $folder->id)
                            ->where('user_id', $folder->user_id)
                            ->notTrashed()
                            ->get();
        
        foreach ($children as $child) {
            $child->update([
                'deleted_at' => Carbon::now(),
                'expiry_at' => Carbon::now()->addDays(30)
            ]);
            
            if ($child->type === 'folder') {
                $this->moveChildrenToTrash($child);
            }
        }
    }
    
    /**
     * Delete all active share records for this file or folder (including children).
     */
    private function deleteSharesForEntry(FileEntry $entry)
    {
        // Collect all descendant IDs if it's a folder
        $ids = [$entry->id];
        if ($entry->type === 'folder') {
            $childIds = FileEntry::descendantIdsFor($entry->id);
            $ids = array_merge($ids, $childIds);
        }

        FileEntryShare::whereIn('file_entry_id', $ids)->delete();
    }

public function show($shared_id)
{
    // Allow any authorized actor (owner or user with share + can_reshare/edit)
    $file = FileEntry::where('shared_id', $shared_id)
        ->notExpired()
        ->notTrashed()
        ->firstOrFail();

    $this->authorizeShareActor($file); // ✅ owner or share-actor

    return response()->json([
        'access_status' => $file->access_status,
        'download_link' => route('file.download', $file->shared_id),
    ]);
}



    public function bulkMoveToTrash(Request $request)
    {
        try {
            // Accept both "ids" (comma-separated) and "file_ids" (array)
            $fileIds = [];
            if ($request->filled('ids')) {
                $fileIds = array_filter(array_map('trim', explode(',', $request->input('ids'))));
            } elseif ($request->has('file_ids')) {
                $fileIds = array_filter(array_map('trim', (array) $request->input('file_ids', [])));
            }

            if (empty($fileIds)) {
                $msg = lang('You have not selected any file', 'files');

                if ($request->expectsJson()) {
                    return response()->json(['type' => 'error', 'message' => $msg], 400);
                }

                toastr()->error($msg);
                return back();
            }

            $movedCount = 0;
            $errors = [];

            foreach ($fileIds as $sharedId) {
                try {
                    $entry = FileEntry::where('shared_id', $sharedId)
                        ->currentUser()
                        ->notExpired()
                        ->notTrashed()
                        ->first();

                    if (!$entry) {
                        $errors[] = "File with ID {$sharedId} not found or already trashed";
                        continue;
                    }

                    $entry->update([
                        'deleted_at' => Carbon::now(),
                        'expiry_at'  => Carbon::now()->addDays(30),
                    ]);

                    if ($entry->type === 'folder') {
                        $this->moveChildrenToTrash($entry);
                    }

                    // Remove related share records
                    $this->deleteSharesForEntry($entry);

                    $movedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to move {$sharedId} to trash: " . $e->getMessage();
                    \Log::error("Bulk move to trash error for {$sharedId}: " . $e->getMessage());
                }
            }

            if ($request->expectsJson()) {
                if ($movedCount > 0) {
                    $message = $movedCount . ' item(s) moved to trash';
                    if (!empty($errors)) {
                        $message .= ', but some items failed';
                    }
                    return response()->json([
                        'type' => 'success',
                        'message' => $message,
                        'moved_count' => $movedCount,
                        'errors' => $errors,
                    ]);
                }

                return response()->json([
                    'type' => 'error',
                    'message' => 'No items were moved to trash',
                    'errors' => $errors,
                ], 400);
            }

            if ($movedCount > 0) {
                toastr()->success('Moved to trash successfully. Items will be permanently deleted after 30 days.');
            } else {
                toastr()->error('No items were moved to trash');
            }

            // preserve folder context if present
            $redirectParams = [];
            if ($folderId = $request->input('folder')) {
                $redirectParams['folder'] = $folderId;
            }

            return redirect()->route('user.files.index', $redirectParams);

        } catch (\Exception $e) {
            \Log::error('Bulk move to trash error: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'An error occurred while moving items to trash: ' . $e->getMessage(),
                ], 500);
            }

            toastr()->error('An error occurred while moving items to trash');
            return back();
        }
    }


    // Fixed destroy method
    public function destroy($shared_id)
    {   
        try {
            $fileEntry = FileEntry::where('shared_id', $shared_id)
                ->currentUser()
                ->notExpired()
                ->first();

            if (!$fileEntry) {
                toastr()->error(lang('File not found, missing or expired please refresh the page and try again', 'files'));
                return back();
            }

            if ($fileEntry->type === 'folder') {
                $this->deleteFolder($fileEntry);
            } else {
                $this->deleteFileFromStorage($fileEntry);
            }

            $fileEntry->delete();

            toastr()->success(lang('Deleted successfully', 'files'));
            return redirect()->route('user.files.index');

        } catch (\Exception $e) {
            \Log::error('Delete error: ' . $e->getMessage());
            toastr()->error(lang('Failed to delete file: ' . $e->getMessage(), 'files'));
            return back();
        }
    }

    public function destroyAll(Request $request)
    {
        try {
            $fileIds = [];
            if ($request->has('ids') && !empty($request->ids)) {
                $fileIds = explode(',', $request->ids);
            } elseif ($request->has('file_ids')) {
                $fileIds = $request->input('file_ids', []);
            }

            if (empty($fileIds)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'type' => 'error',
                        'message' => lang('You have not selected any file', 'files')
                    ], 400);
                }
                toastr()->error(lang('You have not selected any file', 'files'));
                return back();
            }

            $deletedCount = 0;
            $errors = [];

            foreach ($fileIds as $fileId) {
                try {
                    $fileEntry = FileEntry::where('shared_id', trim($fileId))
                        ->currentUser()
                        ->notExpired()
                        ->first();

                    if (!$fileEntry) {
                        $errors[] = "File with ID {$fileId} not found";
                        continue;
                    }

                    if ($fileEntry->type === 'folder') {
                        $this->deleteFolder($fileEntry);
                    } else {
                        $this->deleteFileFromStorage($fileEntry);
                    }

                    $fileEntry->delete();
                    $deletedCount++;

                } catch (\Exception $e) {
                    $errors[] = "Failed to delete file {$fileId}: " . $e->getMessage();
                    \Log::error("Error deleting file {$fileId}: " . $e->getMessage());
                }
            }

            if ($request->expectsJson()) {
                if ($deletedCount > 0) {
                    $message = $deletedCount . ' file(s) deleted successfully';
                    if (!empty($errors)) {
                        $message .= ', but some files could not be deleted';
                    }
                    
                    return response()->json([
                        'type' => 'success',
                        'message' => $message,
                        'deleted_count' => $deletedCount,
                        'errors' => $errors
                    ]);
                } else {
                    return response()->json([
                        'type' => 'error',
                        'message' => 'No files were deleted',
                        'errors' => $errors
                    ], 400);
                }
            }

            if ($deletedCount > 0) {
                toastr()->success(lang('Deleted successfully', 'files'));
            } else {
                toastr()->error(lang('No files were deleted', 'files'));
            }
            return back();

        } catch (\Exception $e) {
            \Log::error('Bulk delete error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'An error occurred while deleting files: ' . $e->getMessage()
                ], 500);
            }
            
            toastr()->error(lang('An error occurred while deleting files', 'files'));
            return back();
        }
    }

    private function deleteFolder($folder)
    {
        $children = FileEntry::where('parent_id', $folder->id)->get();
        
        foreach ($children as $child) {
            if ($child->type === 'folder') {
                $this->deleteFolder($child);
            } else {
                $this->deleteFileFromStorage($child);
            }
            
            $child->delete();
        }
    }

    private function deleteFileFromStorage($fileEntry)
    {
        if ($fileEntry->type !== 'folder' && !empty($fileEntry->path)) {
            try {
                $handler = $fileEntry->storageProvider->handler;
                $handler::delete($fileEntry->path);
            } catch (\Exception $e) {
                \Log::error("Failed to delete file from storage: {$fileEntry->path}. Error: " . $e->getMessage());
            }
        }
    }

    public function createFolder(Request $request)
    {
        \Log::info('Create folder request:', $request->all());

        $validator = Validator::make($request->all(), [
            'folder_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^\/\\:*?"<>|]+$/',
                function ($attribute, $value, $fail) {
                    $reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
                    if (in_array(strtoupper($value), $reservedNames)) {
                        $fail('The folder name is reserved and cannot be used.');
                    }
                },
            ],
            'parent_folder_id' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'type' => 'error',
                'msg' => 'Validation failed: ' . $validator->errors()->first()
            ], 422);
        }

        try {
            $folderName = $request->folder_name;
            $parentFolderId = $request->parent_folder_id;
            $userId = auth()->id();
            
            // Get parent folder if specified
            $parentFolder = null;
            $parentDbId = null;
            $folderPath = 'folders/' . $userId . '/' . $folderName; // Default root path
            
            if ($parentFolderId) {
                $parentFolder = FileEntry::where('shared_id', $parentFolderId)
                    ->where('type', 'folder')
                    ->where('user_id', $userId)
                    ->first();
                
                if (!$parentFolder) {
                    return response()->json([
                        'type' => 'error',
                        'msg' => 'Parent folder not found.'
                    ], 404);
                }
                
                $parentDbId = $parentFolder->id;
                // Build path from parent folder
                $parentPath = rtrim($parentFolder->getRawOriginal('path'), '/');
                $folderPath = $parentPath . '/' . $folderName;
            }

            // Check if folder already exists in the same parent
            $existingFolder = FileEntry::where('name', $folderName)
                ->where('type', 'folder')
                ->where('user_id', $userId)
                ->where('parent_id', $parentDbId)
                ->where(function ($q) {
                    // Not deleted/trashed/purged/expired
                    $q->whereNull('deleted_at')
                    ->whereNull('purge_at')
                    ->where(function ($q2) {
                        $q2->whereNull('expiry_at')
                            ->orWhere('expiry_at', '>', now());
                    });
                })
                ->first();


            if ($existingFolder) {
                return response()->json([
                    'type' => 'error',
                    'msg' => 'A folder with this name already exists in this location.'
                ]);
            }

            // Get storage provider
            $storageProvider = \App\Models\StorageProvider::where([['symbol', env('FILESYSTEM_DRIVER')], ['status', 1]])->first();
            if (is_null($storageProvider)) {
                return response()->json([
                    'type' => 'error',
                    'msg' => 'Unavailable storage provider'
                ], 500);
            }

            // Generate unique shared_id
            $sharedId = Str::random(15);
            while (FileEntry::where('shared_id', $sharedId)->exists()) {
                $sharedId = Str::random(15);
            }

            $ip = vIpInfo()->ip;

            // Create folder entry in database
            $folder = FileEntry::create([
                'ip' => $ip,
                'shared_id' => $sharedId,
                'user_id' => $userId,
                'parent_id' => $parentDbId,
                'storage_provider_id' => $storageProvider->id,
                'name' => $folderName,
                'filename' => $folderName,
                'type' => 'folder',
                'mime' => 'folder',
                'extension' => null,
                'size' => 0,
                'path' => $folderPath, // This should be clean path like: folders/2/new fold
                'access_status' => 0,
                'link' => null,
                'password' => null,
                'expiry_at' => null,
                'deleted_at' => null,
            ]);

            \Log::info('Folder created with path: ' . $folderPath);

            return response()->json([
                'type' => 'success',
                'message' => 'Folder created successfully',
                'folder' => [
                    'id' => $folder->shared_id,
                    'name' => $folder->name,
                    'type' => $folder->type,
                    'size' => $folder->size,
                    'path' => $folder->path,
                    'created_at' => $folder->created_at->format('Y-m-d H:i:s'),
                    'folder_url' => route('user.files.index', ['folder' => $folder->shared_id]),
                    'edit_url' => route('user.files.edit', $folder->shared_id),
                    // 'delete_url' => route('user.files.delete', $folder->shared_id),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Folder creation failed: ' . $e->getMessage());
            return response()->json([
                'type' => 'error',
                'msg' => 'Failed to create folder: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildBreadcrumbs($folder)
    {
        $breadcrumbs = [];
        $current = $folder->parent; // Start from parent, not the current folder
        
        // Build path from parent folder to root
        while ($current) {
            array_unshift($breadcrumbs, $current);
            $current = $current->parent;
        }
        
        return $breadcrumbs;
    }


    public function getFoldersTree(Request $request)
    {
        $parentId = $request->get('parent_id');
        $query = FileEntry::where('user_id', auth()->id())
            ->where('type', 'folder')
            ->notTrashed();
        if ($parentId) {
            $parent = FileEntry::where('shared_id', $parentId)
                ->where('type', 'folder')
                ->where('user_id', auth()->id())
                ->notTrashed()
                ->firstOrFail();
            $query->where('parent_id', $parent->id);
        } else {
            $query->whereNull('parent_id');
        }
        $folders = $query->orderBy('name')->get(['shared_id', 'name']);

        return response()->json(['folders'=>$folders]);
    }

    public function move(Request $request, $shared_id)
    {
        $request->validate([
            'target_folder_id' => 'nullable|string|exists:file_entries,shared_id',
        ]);

        $file = FileEntry::where('shared_id', $shared_id)
            ->where('user_id', auth()->id())
            ->where('type', '!=', 'folder')
            ->notTrashed()
            ->firstOrFail();

        if ($request->target_folder_id) {
            $targetFolder = FileEntry::where('shared_id', $request->target_folder_id)
                ->where('type', 'folder')
                ->where('user_id', auth()->id())
                ->notTrashed()
                ->firstOrFail();

            if ($file->parent_id === $targetFolder->id) {
                return response()->json(['type'=>'error','msg'=>'Already inside this folder.'], 422);
            }
            $file->parent_id = $targetFolder->id;
        } else {
            $file->parent_id = null;
        }

        $file->save();

        return response()->json(['type'=>'success','msg'=>'File moved successfully']);
    }
    

    

    /**
     * Put items into clipboard as copy or cut (move).
     * body: action=copy|cut, ids[]=shared_id...
     */
    public function clipboardSet(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|string|in:copy,cut',
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'string'
        ]);

        $items = FileEntry::whereIn('shared_id', $data['ids'])
            ->where('user_id', auth()->id())
            ->notExpired()
            ->notTrashed()
            ->get(['id','shared_id','type','parent_id']);

        if ($items->isEmpty()) {
            return response()->json(['type'=>'error','msg'=>'No valid items selected.'], 422);
        }

        // Store a minimal clipboard in session
        session([
            'fm_clipboard' => [
                'action' => $data['action'],               // copy or cut
                'ids'    => $items->pluck('shared_id'),     // store shared ids
                'at'     => now()->timestamp,
            ],
        ]);

        return response()->json([
            'type' => 'success',
            'msg'  => ucfirst($data['action']).' ready. Go to a folder and Paste.',
            'clipboard' => session('fm_clipboard')
        ]);
    }

    public function clipboardClear(Request $request)
    {
        session()->forget('fm_clipboard');
        return response()->json(['type'=>'success','msg'=>'Clipboard cleared.']);
    }

    /**
     * Paste into target folder (null = root)
     * body: target_folder_id (nullable)
     */
public function clipboardPaste(Request $request)
{
    $request->validate([
        'target_folder_id' => 'nullable|string|exists:file_entries,shared_id',
    ]);

    $clip = session('fm_clipboard');
    if (!$clip || empty($clip['ids']) || empty($clip['action'])) {
        return response()->json(['type'=>'error','msg'=>'Clipboard is empty.'], 422);
    }

    // Resolve target parent DB id (or null for root)
    $targetParentId = null;
    $targetFolder = null;

    if ($request->filled('target_folder_id')) {
        $targetFolder = FileEntry::where('shared_id', $request->target_folder_id)
            ->where('type', 'folder')
            ->where('user_id', auth()->id())
            ->notTrashed()
            ->first();

        if (!$targetFolder) {
            return response()->json(['type'=>'error','msg'=>'Target folder not found.'], 404);
        }
        $targetParentId = $targetFolder->id;
    }

    // Fetch all entries by shared ids
    $entries = FileEntry::whereIn('shared_id', $clip['ids'])
        ->where('user_id', auth()->id())
        ->notExpired()
        ->notTrashed()
        ->get();

    if ($entries->isEmpty()) {
        return response()->json(['type'=>'error','msg'=>'Items not found.'], 404);
    }

    // Check storage limits for copy only
    if ($clip['action'] === 'copy') {
        $totalSize = $entries->sum('size');
        if (!is_null(subscription()->storage->remining->number) && $totalSize > subscription()->storage->remining->number) {
            return response()->json(['type'=>'error','msg'=>'Insufficient storage space to copy files.'], 422);
        }
    }

    // Block moving a folder into itself or descendants on CUT
    if ($clip['action'] === 'cut' && $targetParentId) {
        foreach ($entries as $entry) {
            if ($entry->type === 'folder') {
                if ($this->isTargetInsideFolder($targetParentId, $entry)) {
                    return response()->json([
                        'type'=>'error',
                        'msg'=>"You can’t move '{$entry->name}' into its own subfolder."
                    ], 422);
                }
            }
        }
    }

    $results = [
        'moved' => 0,
        'copied' => 0,
        'errors' => [],
    ];

    try {
        if ($clip['action'] === 'cut') {
            foreach ($entries as $entry) {
                try {
                    if ((int)$entry->parent_id === (int)$targetParentId) {
                        continue;
                    }
                    $this->moveEntry($entry, $targetParentId);
                    $results['moved']++;
                } catch (\Throwable $e) {
                    \Log::error('Move error: '.$e->getMessage());
                    $results['errors'][] = $entry->name.': '.$e->getMessage();
                }
            }
            session()->forget('fm_clipboard');
        } else {
            foreach ($entries as $entry) {
                try {
                    $this->duplicateEntryRecursive($entry, $targetParentId);
                    $results['copied']++;
                } catch (\Throwable $e) {
                    \Log::error('Copy error: '.$e->getMessage());
                    $results['errors'][] = $entry->name.': '.$e->getMessage();
                }
            }
        }
    } catch (\Throwable $e) {
        \Log::error('Paste error: '.$e->getMessage());
        return response()->json(['type'=>'error','msg'=>'Paste failed: '.$e->getMessage()], 500);
    }

    $summary = [];
    if ($results['moved'])  $summary[] = "{$results['moved']} moved";
    if ($results['copied']) $summary[] = "{$results['copied']} copied";
    $msg = count($summary) ? implode(', ', $summary).' successfully.' : 'Nothing changed.';

    return response()->json([
        'type' => 'success',
        'msg'  => $msg,
        'errors' => $results['errors']
    ]);
}

    /**
     * Duplicate single entry (optional direct “Duplicate” button)
     */
    public function duplicate(Request $request, $shared_id)
    {
        $entry = FileEntry::where('shared_id', $shared_id)
            ->where('user_id', auth()->id())
            ->notTrashed()
            ->notExpired()
            ->firstOrFail();

        try {
            $this->duplicateEntryRecursive($entry, $entry->parent_id);
            return response()->json(['type'=>'success','msg'=>'Duplicated successfully.']);
        } catch (\Throwable $e) {
            \Log::error('Duplicate error: '.$e->getMessage());
            return response()->json(['type'=>'error','msg'=>'Duplicate failed: '.$e->getMessage()], 500);
        }
    }

    // ====== HELPERS ======

    /** Move entry to new parent (null=root). For folder, just re-parent; children keep structure. */
    protected function moveEntry(FileEntry $entry, $targetParentId)
    {
        $userId = $entry->user_id; // Use entry's user_id (User 1)

        // Auto-rename if conflict
        $newName = $this->nextAvailableName($entry->name, $targetParentId, $entry->type, $userId);

        if ($newName !== $entry->name) {
            $entry->name = $newName;
            $entry->filename = $newName;
        }

        $entry->parent_id = $targetParentId;
        $newPath = $this->rebuildPath($entry);
        if ($newPath !== $entry->path) {
            $this->moveBinary($entry->path, $newPath);
            $entry->path = $newPath;
        }

        // If the file was uploaded via a share, create a new share record for the moved file
        if ($entry->uploaded_via_share_id) {
            $originalShare = \App\Models\FileEntryShare::where('id', $entry->uploaded_via_share_id)
                ->whereNull('revoked_at')
                ->first();

            if ($originalShare) {
                \DB::beginTransaction();
                try {
                    // Create new share record for the moved file
                    $newShare = \App\Models\FileEntryShare::create([
                        'file_entry_id'     => $entry->id,
                        'owner_id'          => $entry->user_id, // User 1
                        'recipient_user_id' => $originalShare->recipient_user_id, // User 2 (if registered)
                        'recipient_email'   => $originalShare->recipient_email, // User 2’s email
                        'permission'        => $originalShare->permission,
                        'can_download'      => $originalShare->can_download,
                        'can_reshare'       => $originalShare->can_reshare,
                        'token'             => Str::random(64),
                        'expires_at'        => $originalShare->expires_at,
                        'message'           => $originalShare->message,
                    ]);

                    // Update uploaded_via_share_id to point to the new share
                    $entry->uploaded_via_share_id = $newShare->id;

                    // Send email notification for the new share
                    try {
                        \Mail::to($newShare->recipient_email)->send(new \App\Mail\FileSharedMail($newShare));
                    } catch (\Throwable $e) {
                        \Log::error("Failed to send share email for moved file: {$e->getMessage()}");
                    }

                    \DB::commit();
                } catch (\Throwable $e) {
                    \DB::rollBack();
                    \Log::error('Failed to create share for moved file: '.$e->getMessage());
                    // Continue with move even if share creation fails to avoid blocking the operation
                }
            }
        }

        $entry->save();
    }

    protected function rebuildPath(FileEntry $entry)
    {
        $path = 'folders/' . $entry->user_id;
        $current = $entry->parent;
        $segments = [$entry->filename];

        while ($current) {
            $segments[] = $current->filename;
            $current = $current->parent;
        }

        $segments = array_reverse($segments);
        return implode('/', $segments);
    }

    protected function moveBinary($oldPath, $newPath)
    {
        $disk = env('FILESYSTEM_DRIVER');
        $dir = dirname($newPath);

        if (!\Storage::disk($disk)->exists($dir)) {
            \Storage::disk($disk)->makeDirectory($dir);
        }

        if (\Storage::disk($disk)->exists($oldPath)) {
            \Storage::disk($disk)->move($oldPath, $newPath);
        }
    }

    /** Duplicate entry (file or folder) recursively to target parent. */
    protected function duplicateEntryRecursive(FileEntry $src, $targetParentId)
    {
        $newName = $this->nextAvailableCopyName($src->name, $targetParentId, $src->type, auth()->id());

        // Start fresh - do NOT replicate uploaded_by or uploaded_via_share_id
        $new = new FileEntry();

        $new->shared_id = Str::random(15);
        while (FileEntry::where('shared_id', $new->shared_id)->exists()) {
            $new->shared_id = Str::random(15);
        }

        $new->user_id               = auth()->id();                    // ← OWNER = CURRENT USER
        $new->uploaded_by           = auth()->id();                    // ← STORAGE COUNTS FOR CURRENT USER
        $new->uploaded_via_share_id = null;                            // ← NOT FROM SHARE
        $new->parent_id             = $targetParentId;
        $new->storage_provider_id   = $src->storage_provider_id;
        $new->name                  = $newName;
        $new->filename              = $newName;
        $new->mime                  = $src->mime;
        $new->size                  = $src->size;
        $new->extension             = $src->extension;
        $new->type                  = $src->type;
        $new->ip                    = request()->ip();
        $new->downloads             = 0;
        $new->views                 = 0;
        $new->access_status         = 0;
        $new->password              = null;
        $new->expiry_at             = null;
        $new->deleted_at            = null;

        $basePath = $this->computeFolderPath($targetParentId, auth()->id());

        if ($src->type !== 'folder') {
            $ext = $src->extension ? '.'.$src->extension : '';
            $new->path = rtrim($basePath, '/').'/'.Str::uuid().$ext;

            // Save DB first
            $new->save();

            // Then copy binary
            $this->copyBinary($src, $new);
        } else {
            $new->path = rtrim($basePath, '/').'/'.$newName;
            $new->save();

            // Recurse children
            $children = $src->children()->notTrashed()->get();
            foreach ($children as $child) {
                $this->duplicateEntryRecursive($child, $new->id);
            }
        }

        return $new;
    }


    protected function copyBinary(FileEntry $src, FileEntry $dst)
    {
        if (empty($src->path)) {
            throw new \RuntimeException('Source path missing for '.$src->name);
        }
        if (empty($dst->path)) {
            throw new \RuntimeException('Destination path missing for '.$dst->name);
        }

        $disk = env('FILESYSTEM_DRIVER');
        $dir  = dirname($dst->path);

        // Ensure destination folder exists
        if (!\Storage::disk($disk)->exists($dir)) {
            \Storage::disk($disk)->makeDirectory($dir);
        }

        // Prefer provider handler if it supports copy
        $provider = $src->storageProvider;
        $handler  = $provider ? $provider->handler : null;

        if ($handler && method_exists($handler, 'copy')) {
            $handler::copy($src->path, $dst->path);
            return;
        }

        // Fallback: Storage copy, then streaming fallback
        if (!\Storage::disk($disk)->copy($src->path, $dst->path)) {
            $read = \Storage::disk($disk)->readStream($src->path);
            if (!$read) {
                throw new \RuntimeException('Failed to open source stream for copy.');
            }
            \Storage::disk($disk)->writeStream($dst->path, $read);
            if (is_resource($read)) fclose($read);
        }
    }


    /** True if $targetParentId is inside $folder's subtree (to prevent cyclic move). */
    protected function isTargetInsideFolder($targetParentId, FileEntry $folder)
    {
        if (!$targetParentId) return false;
        $current = FileEntry::find($targetParentId);
        while ($current) {
            if ($current->id === $folder->id) return true;
            $current = $current->parent;
        }
        return false;
    }

    /** Compute a normalized folder path for a user/parent (keeps your existing pattern). */
    protected function computeFolderPath($parentId, $userId)
    {
        if (!$parentId) {
            return 'folders/'.$userId;
        }
        $parent = FileEntry::find($parentId);
        return $parent && $parent->path ? $parent->path : 'folders/'.$userId;
    }

    /** Create “Name (copy)” then “Name (2)”, “Name (3)”, etc. */
    protected function nextAvailableCopyName($name, $parentId, $type, $userId)
    {
        // Try "(copy)" first
        $base = $this->stripCopyIndex($name);
        $candidate = $base.' (copy)';
        if (!$this->nameExists($candidate, $parentId, $type, $userId)) {
            return $candidate;
        }

        // Then (2), (3)...
        $i = 2;
        while ($this->nameExists($base." ($i)", $parentId, $type, $userId)) {
            $i++;
        }
        return $base." ($i)";
    }

    /** If moving into a folder with a conflict, auto-rename to next available plain name (… (2), … (3)). */
    protected function nextAvailableName($name, $parentId, $type, $userId)
    {
        if (!$this->nameExists($name, $parentId, $type, $userId)) return $name;

        $base = $this->stripCopyIndex($name);
        $i = 2;
        while ($this->nameExists($base." ($i)", $parentId, $type, $userId)) {
            $i++;
        }
        return $base." ($i)";
    }

    protected function nameExists($name, $parentId, $type, $userId)
    {
        return FileEntry::where('user_id', $userId)
            ->where('parent_id', $parentId)
            ->where('type', $type)
            ->where('name', $name)
            ->notTrashed()
            ->exists();
    }

    protected function stripCopyIndex($name)
    {
        // Remove trailing " (copy)" or " (n)" patterns
        return preg_replace('/\s\((copy|\d+)\)$/i', '', $name);
    }


    // Download a shared file
    public function download($shared_id)
    {
        $file = FileEntry::where('shared_id', $shared_id)->firstOrFail();

        // owner always OK
        if (auth()->check() && auth()->id() === (int)$file->user_id) {
            return $this->streamDownload($file);
        }

        // find the share row (so we can check can_download)
        $share = $file->shares()
            ->whereNull('revoked_at')
            ->where(function($q){
                if (auth()->check()) {
                    $q->orWhere('recipient_user_id', auth()->id())
                    ->orWhere('recipient_email', auth()->user()->email);
                }
            })
            ->where(function($q){
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$share) {
            abort(403, 'You do not have access to download this file.');
        }

        if (!$share->can_download) {
            abort(403, 'The owner disabled downloads for this item.');
        }

        return $this->streamDownload($file);
    }
}
