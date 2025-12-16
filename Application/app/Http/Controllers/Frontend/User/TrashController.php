<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\FileEntry;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TrashController extends Controller
{
    public function index(Request $request)
    {
        // Base: only trashed + NOT scheduled for purge
        $query = FileEntry::with('parent')
            ->currentUser()
            ->trashed()
            ->whereNull('purge_at'); // HIDE scheduled items

        $currentFolder = null;

        if ($request->filled('folder')) {
            $currentFolder = FileEntry::where('shared_id', $request->folder)
                ->currentUser()
                ->trashed()
                ->where('type', 'folder')
                ->whereNull('purge_at') // cannot enter a folder already scheduled for purge
                ->first();

            if (!$currentFolder) {
                abort(404);
            }

            // Only direct children of the current trashed folder
            $query->where('parent_id', $currentFolder->id);
        } else {
            // Root Trash: show items at top level or whose parent isn't trashed
            $query->where(function ($q) {
                $q->whereNull('parent_id')
                  ->orWhereHas('parent', function ($p) {
                      $p->whereNull('deleted_at'); // parent not in trash
                  });
            });
        }

        // Search
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($searchQuery) use ($q) {
                $searchQuery->where('shared_id', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('filename', 'like', "%{$q}%")
                    ->orWhere('mime', 'like', "%{$q}%")
                    ->orWhere('extension', 'like', "%{$q}%");
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Sorting - folders first, then files
        $sortBy     = $request->get('sort', 'deleted_at');
        $sortOrder  = $request->get('order', 'desc');
        $allowedSorts  = ['deleted_at', 'name', 'size', 'type', 'expiry_at'];
        $allowedOrders = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSorts) && in_array($sortOrder, $allowedOrders)) {
            $query->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
                  ->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
                  ->orderByDesc('deleted_at');
        }

        // Paginate (append current query so filters/folder persist)
        $fileEntries = $query->paginate(20)->appends($request->query());

        // Breadcrumb (stay inside trashed chain only)
        $breadcrumb = [];
        if ($currentFolder) {
            $cur = $currentFolder;
            while ($cur && $cur->parent_id) {
                $p = $cur->parent;
                if (!$p || $p->deleted_at === null || $p->purge_at !== null) break; // stop at first non-trashed OR scheduled ancestor
                array_unshift($breadcrumb, $p);
                $cur = $p;
            }
        }

        return view('frontend.user.trash.index', [
            'fileEntries'   => $fileEntries,
            'filters'       => $request->only(['search', 'type', 'sort', 'order']),
            'hasFilters'    => $request->hasAny(['search', 'type']),
            'currentFolder' => $currentFolder,
            'breadcrumb'    => $breadcrumb,
        ]);
    }


    // Restore single file from trash
    public function restore($shared_id)
    {
        try {
            $fileEntry = FileEntry::where('shared_id', $shared_id)
                                 ->currentUser()
                                 ->trashed()
                                 ->first();
            
            if (!$fileEntry) {
                toastr()->error(lang('File not found in trash', 'files'));
                return back();
            }
            
            // Restore file by clearing deleted_at and expiry_at
            $fileEntry->update([
                'deleted_at' => null,
                'expiry_at' => null,
                'purge_at'   => null,
            ]);
            
            // If it's a folder, restore all children as well
            if ($fileEntry->type === 'folder') {
                $this->restoreChildrenFromTrash($fileEntry);
            }
            
            toastr()->success(lang('File restored successfully', 'files'));
            return back();
            
        } catch (\Exception $e) {
            \Log::error('Restore error: ' . $e->getMessage());
            toastr()->error(lang('Failed to restore file', 'files'));
            return back();
        }
    }

    // Delete file forever (permanent deletion)
    public function deleteForever($shared_id)
    {
        try {
            $fileEntry = FileEntry::where('shared_id', $shared_id)
                                ->currentUser()
                                ->trashed()
                                ->first();

            if (!$fileEntry) {
                toastr()->error(lang('File not found in trash', 'files'));
                return back();
            }

            // schedule purge in 7 days
            $when = Carbon::now()->addDays(7);
            $fileEntry->markForPurgeRecursive($when);

            toastr()->success(
                lang('File scheduled for permanent deletion on', 'files') . ' ' . $when->toDayDateTimeString()
            );
            return back();

        } catch (\Exception $e) {
            \Log::error('Schedule purge error: ' . $e->getMessage());
            toastr()->error(lang('Failed to schedule file for permanent deletion', 'files'));
            return back();
        }
    }


    // Restore multiple files
    public function restoreAll(Request $request)
    {
        try {
            $fileIds = $request->input('file_ids', []);
            
            if (empty($fileIds)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'type' => 'error',
                        'message' => lang('No files selected', 'files')
                    ], 400);
                }
                toastr()->error(lang('No files selected', 'files'));
                return back();
            }
            
            $restoredCount = 0;
            $errors = [];
            
            foreach ($fileIds as $fileId) {
                try {
                    $fileEntry = FileEntry::where('shared_id', trim($fileId))
                                         ->currentUser()
                                         ->trashed()
                                         ->first();
                    
                    if (!$fileEntry) {
                        $errors[] = "File with ID {$fileId} not found in trash";
                        continue;
                    }
                    
                    $fileEntry->update([
                        'deleted_at' => null,
                        'expiry_at' => null,
                        'purge_at'   => null,
                    ]);
                    
                    if ($fileEntry->type === 'folder') {
                        $this->restoreChildrenFromTrash($fileEntry);
                    }
                    
                    $restoredCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Failed to restore file {$fileId}: " . $e->getMessage();
                }
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'type' => 'success',
                    'message' => "{$restoredCount} file(s) restored successfully",
                    'restored_count' => $restoredCount,
                    'errors' => $errors
                ]);
            }
            
            if ($restoredCount > 0) {
                toastr()->success(lang("{$restoredCount} file(s) restored successfully", 'files'));
            }
            
            return back();
            
        } catch (\Exception $e) {
            \Log::error('Bulk restore error: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'An error occurred while restoring files'
                ], 500);
            }
            toastr()->error(lang('An error occurred while restoring files', 'files'));
            return back();
        }
    }

    // Delete multiple files forever
    public function deleteForeverAll(Request $request)
    {
        try {
            $fileIds = $request->input('file_ids', []);

            if (empty($fileIds)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'type' => 'error',
                        'message' => lang('No files selected', 'files')
                    ], 400);
                }
                toastr()->error(lang('No files selected', 'files'));
                return back();
            }

            $scheduledCount = 0;
            $errors = [];
            $when = Carbon::now()->addDays(7);

            foreach ($fileIds as $fileId) {
                try {
                    $fileEntry = FileEntry::where('shared_id', trim($fileId))
                                        ->currentUser()
                                        ->trashed()
                                        ->first();

                    if (!$fileEntry) {
                        $errors[] = "File with ID {$fileId} not found in trash";
                        continue;
                    }

                    $fileEntry->markForPurgeRecursive($when);
                    $scheduledCount++;

                } catch (\Exception $e) {
                    $errors[] = "Failed to schedule file {$fileId} for deletion: " . $e->getMessage();
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'type' => 'success',
                    'message' => "{$scheduledCount} file(s) scheduled for permanent deletion on {$when->toDayDateTimeString()}",
                    'scheduled_count' => $scheduledCount,
                    'purge_at' => $when->toIso8601String(),
                    'errors' => $errors
                ]);
            }

            if ($scheduledCount > 0) {
                toastr()->success(lang("{$scheduledCount} file(s) scheduled for permanent deletion on", 'files') . ' ' . $when->toDayDateTimeString());
            }

            return back();

        } catch (\Exception $e) {
            \Log::error('Bulk schedule purge error: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'An error occurred while scheduling files for permanent deletion'
                ], 500);
            }
            toastr()->error(lang('An error occurred while scheduling files for permanent deletion', 'files'));
            return back();
        }
    }


    // Helper method to restore children from trash
    private function restoreChildrenFromTrash($folder)
    {
        $children = FileEntry::where('parent_id', $folder->id)
                            ->where('user_id', $folder->user_id)
                            ->trashed()
                            ->get();
        
        foreach ($children as $child) {
            $child->update([
                'deleted_at' => null,
                'expiry_at' => null,
                'purge_at'   => null,
            ]);
            
            if ($child->type === 'folder') {
                $this->restoreChildrenFromTrash($child);
            }
        }
    }

    // Helper method to delete folder and its contents recursively
    private function deleteFolder($folder)
    {
        // Get all children (files and subfolders)
        $children = FileEntry::where('parent_id', $folder->id)->get();
        
        foreach ($children as $child) {
            if ($child->type === 'folder') {
                // Recursively delete subfolders
                $this->deleteFolder($child);
            } else {
                // Delete files from storage
                $this->deleteFileFromStorage($child);
            }
            
            // Delete database record permanently
            $child->forceDelete();
        }
    }

    // Helper method to delete file from storage
    private function deleteFileFromStorage($fileEntry)
    {
        // Only try to delete from storage if it's not a folder and has a path
        if ($fileEntry->type !== 'folder' && !empty($fileEntry->path)) {
            try {
                $handler = $fileEntry->storageProvider->handler;
                $handler::delete($fileEntry->path);
            } catch (\Exception $e) {
                \Log::error("Failed to delete file from storage: {$fileEntry->path}. Error: " . $e->getMessage());
                // Don't throw exception here, continue with database deletion
            }
        }
    }
}
