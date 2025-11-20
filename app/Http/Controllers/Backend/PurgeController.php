<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\FileEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PurgeController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'items');

        // ===== Shared stats for the top counters (from your current code)
        $stats = [
            'total'   => FileEntry::pendingPurge()->count(),
            'overdue' => FileEntry::dueForPurge()->count(),
            'today'   => FileEntry::pendingPurge()->whereDate('purge_at', Carbon::today())->count(),
            'next7'   => FileEntry::pendingPurge()->whereBetween('purge_at', [now(), now()->addDays(7)])->count(),
        ];

        if ($tab === 'users') {
            // --- USERS GRID (old /users)
            $users = FileEntry::pendingPurge()
                ->whereNotNull('deleted_at')
                ->select([
                    'user_id',
                    DB::raw('COUNT(*) as items_count'),
                    DB::raw('SUM(size) as total_bytes'),
                    DB::raw('MIN(purge_at) as next_purge_at'),
                    DB::raw('MAX(deleted_at) as last_deleted_at'),
                ])
                ->with('user')
                ->groupBy('user_id')
                ->orderBy(DB::raw('MIN(purge_at)'))
                ->paginate(50)
                ->appends($request->query());

            // If a user is requested, also load their tree (old userShow)
            $userId        = $request->integer('user_id');
            $user          = $userId ? User::find($userId) : null;
            $onlyScheduled = $request->boolean('scheduled', true);

            // NEW: secure folder navigation by shared_id instead of numeric id
            $folderSid     = $request->get('folder'); // e.g. "abCDe123"
            $currentFolder = null;
            $breadcrumbs   = collect();

            $entries   = collect();
            $userStats = ['scheduled'=>0,'folders'=>0,'files'=>0,'overdue'=>0];

            if ($user) {
                // Stats (unchanged)
                $qAll = FileEntry::with(['user','parent'])
                    ->forUser($user->id)
                    ->trashed();
                if ($onlyScheduled) $qAll->whereNotNull('purge_at');
                $allEntries = $qAll->get();

                $userStats = [
                    'scheduled' => $allEntries->whereNotNull('purge_at')->count(),
                    'folders'   => $allEntries->where('type','folder')->count(),
                    'files'     => $allEntries->where('type','!=','folder')->count(),
                    'overdue'   => $allEntries->whereNotNull('purge_at')->filter(fn($e)=> now()->greaterThan($e->purge_at))->count(),
                ];

                // Current folder by shared_id (secure)
                if ($folderSid) {
                    $currentFolder = FileEntry::with('parent')
                        ->forUser($user->id)
                        ->trashed()
                        ->where('type','folder')
                        ->where('shared_id', $folderSid)
                        ->firstOrFail();
                }

                // Children for current folder (folders first)
                $childrenQ = FileEntry::with('parent')
                    ->forUser($user->id)
                    ->trashed()
                    ->where('parent_id', $currentFolder?->id); // null for root
                if ($onlyScheduled) $childrenQ->whereNotNull('purge_at');

                $entries = $childrenQ
                    ->orderByRaw("CASE WHEN type='folder' THEN 0 ELSE 1 END")
                    ->orderBy('name')
                    ->paginate(50)
                    ->appends($request->query());

                // Breadcrumbs (Root -> ... -> current)
                if ($currentFolder) {
                    $cursor = $currentFolder;
                    while ($cursor && $cursor->parent_id) {
                        $cursor = $cursor->parent()->with('parent')->first(); // walk up
                        if ($cursor) $breadcrumbs->prepend($cursor);
                    }
                }
            }

            return view('backend.purge.index', compact(
                'tab','stats','users','user','entries','currentFolder','breadcrumbs','userStats','onlyScheduled'
            ));
        }

        // --- ITEMS TABLE (default – your original index)
        $query = FileEntry::with(['user','parent'])
            ->pendingPurge()
            ->orderBy('purge_at', 'asc');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shared_id', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('filename', 'like', "%{$search}%")
                ->orWhere('mime', 'like', "%{$search}%")
                ->orWhere('extension', 'like', "%{$search}%");
            });
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($owner = $request->get('owner')) {
            $query->whereHas('user', function ($u) use ($owner) {
                $u->where('id', $owner)
                ->orWhere('email', 'like', "%{$owner}%")
                ->orWhere('username', 'like', "%{$owner}%");
            });
        }
        if ($due = $request->get('due')) {
            if ($due === 'overdue')       $query->where('purge_at', '<=', now());
            elseif ($due === 'today')     $query->whereDate('purge_at', Carbon::today());
            elseif ($due === 'next7')     $query->whereBetween('purge_at', [now(), now()->addDays(7)]);
        }

        $entries = $query->paginate(50)->appends($request->query());

        return view('backend.purge.index', compact('tab','entries','stats'));
    }


    /** Cancel schedule: keep item in Trash (still deleted), but visible to user again */
    public function cancel($shared_id)
    {
        $entry = FileEntry::where('shared_id', $shared_id)->pendingPurge()->firstOrFail();
        $entry->update(['purge_at' => null]); // remains in trash via deleted_at
        toastr()->success(__('Purge schedule canceled. Item returned to Trash.'));
        return back();
    }

    /** Restore item (and descendants if folder) */
    public function restore($shared_id)
    {
        $entry = FileEntry::where('shared_id', $shared_id)->pendingPurge()->firstOrFail();

        $this->restoreEntryRecursive($entry);

        toastr()->success(__('Item restored successfully.'));
        return back();
    }

    /** Purge immediately (hard delete, recursive for folders) */
    public function purgeNow($shared_id)
    {
        $entry = FileEntry::where('shared_id', $shared_id)->pendingPurge()->firstOrFail();

        $this->hardDeleteRecursive($entry);

        toastr()->success(__('Item permanently deleted.'));
        return back();
    }

    /** Optional bulk operations */
    public function bulk(Request $request)
    {
        $action = $request->input('action'); // cancel | restore | purge
        $ids    = (array) $request->input('file_ids', []);

        if (empty($ids) || !in_array($action, ['cancel','restore','purge'])) {
            return back()->withErrors(__('Invalid bulk action or no items selected.'));
        }

        $affected = 0;
        foreach ($ids as $shared_id) {
            $entry = FileEntry::where('shared_id', $shared_id)->pendingPurge()->first();
            if (!$entry) continue;

            if ($action === 'cancel') {
                $entry->update(['purge_at' => null]);
                $affected++;
            } elseif ($action === 'restore') {
                $this->restoreEntryRecursive($entry);
                $affected++;
            } elseif ($action === 'purge') {
                $this->hardDeleteRecursive($entry);
                $affected++;
            }
        }

        toastr()->success(__(':n item(s) processed.', ['n' => $affected]));
        return back();
    }

    /* ===================== Helpers ===================== */

    /** Restore entry and descendants (clear deleted_at, expiry_at, purge_at) */
    private function restoreEntryRecursive(FileEntry $entry): void
    {
        $entry->update(['deleted_at' => null, 'expiry_at' => null, 'purge_at' => null]);

        if ($entry->type === 'folder') {
            $children = FileEntry::where('parent_id', $entry->id)->get();
            foreach ($children as $child) {
                // If children were also scheduled or trashed, restore them too
                $child->update(['deleted_at' => null, 'expiry_at' => null, 'purge_at' => null]);
                if ($child->type === 'folder') {
                    $this->restoreEntryRecursive($child);
                }
            }
        }
    }

    /** Hard delete from storage and DB (recursive for folders) */
    private function hardDeleteRecursive(FileEntry $entry): void
    {
        if ($entry->type === 'folder') {
            $children = FileEntry::where('parent_id', $entry->id)->get();
            foreach ($children as $child) {
                $this->hardDeleteRecursive($child);
            }
        } else {
            $this->deleteFromStorage($entry);
        }
        $entry->forceDelete();
    }

    private function deleteFromStorage(FileEntry $fileEntry): void
    {
        if ($fileEntry->type !== 'folder' && !empty($fileEntry->path)) {
            try {
                // If you have storage providers, use that; otherwise default disk
                if ($fileEntry->storageProvider && $fileEntry->storageProvider->handler) {
                    $handler = $fileEntry->storageProvider->handler;
                    $handler::delete($fileEntry->path);
                } else {
                    if (Storage::exists($fileEntry->path)) {
                        Storage::delete($fileEntry->path);
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Admin purge delete failed: {$fileEntry->path} - " . $e->getMessage());
            }
        }
    }





    public function users(Request $request)
    {
        // Aggregate users who have anything scheduled
        $users = FileEntry::pendingPurge()
            ->whereNotNull('deleted_at') // ensure it's actually trashed
            ->select([
                'user_id',
                DB::raw('COUNT(*) as items_count'),
                DB::raw('SUM(size) as total_bytes'),
                DB::raw('MIN(purge_at) as next_purge_at'),
                DB::raw('MAX(deleted_at) as last_deleted_at'),
            ])
            ->with('user')
            ->groupBy('user_id')
            ->orderBy(DB::raw('MIN(purge_at)')) // closest first
            ->paginate(50)
            ->appends($request->query());

        // Counters (reuse your existing logic)
        $stats = [
            'total'   => FileEntry::pendingPurge()->count(),
            'overdue' => FileEntry::dueForPurge()->count(),
            'today'   => FileEntry::pendingPurge()->whereDate('purge_at', Carbon::today())->count(),
            'next7'   => FileEntry::pendingPurge()->whereBetween('purge_at', [now(), now()->addDays(7)])->count(),
        ];

        return view('backend.purge.users', compact('users', 'stats'));
    }

    public function userShow(User $user, Request $request)
    {
        // Optionally filter by only scheduled (default) or all trashed
        $onlyScheduled = $request->boolean('scheduled', true);

        $q = FileEntry::with(['user','parent'])
            ->forUser($user->id)
            ->trashed(); // in trash

        if ($onlyScheduled) {
            $q->whereNotNull('purge_at');
        }

        // Pull EVERYTHING for nesting (don’t paginate this set)
        $entries = $q->orderBy('type','desc') // folders before files (optional)
                    ->orderBy('name')
                    ->get();

        $tree = FileEntry::buildTree($entries);

        // Counts for the header
        $stats = [
            'scheduled' => $entries->whereNotNull('purge_at')->count(),
            'folders'   => $entries->where('type','folder')->count(),
            'files'     => $entries->where('type','!=','folder')->count(),
            'overdue'   => $entries->whereNotNull('purge_at')->filter(fn($e)=> now()->greaterThan($e->purge_at))->count(),
        ];

        return view('backend.purge.user_show', compact('user','entries','tree','stats','onlyScheduled'));
    }

    /** USER-LEVEL BULK ACTIONS */

    public function restoreAllForUser(User $user)
    {
        $entries = FileEntry::forUser($user->id)->trashed()->get();
        $count = 0;
        foreach ($entries as $entry) {
            $this->restoreEntryRecursive($entry);
            $count++;
        }
        toastr()->success(__(':n item(s) restored for :u.', ['n'=>$count,'u'=>$user->email]));
        return back();
    }

    public function cancelAllForUser(User $user)
    {
        $count = FileEntry::forUser($user->id)
            ->trashed()
            ->pendingPurge()
            ->update(['purge_at' => null]);

        toastr()->success(__(':n item(s) unscheduled for purge for :u.', ['n'=>$count,'u'=>$user->email]));
        return back();
    }

    public function purgeAllForUser(User $user)
    {
        // Delete files first, folders last
        $files = FileEntry::forUser($user->id)->trashed()->pendingPurge()->where('type','!=','folder')->get();
        $folders = FileEntry::forUser($user->id)->trashed()->pendingPurge()->where('type','folder')->orderByDesc('id')->get();

        $count = 0;
        foreach ($files as $e) { $this->hardDeleteRecursive($e); $count++; }
        foreach ($folders as $e) { $this->hardDeleteRecursive($e); $count++; }

        toastr()->success(__(':n item(s) permanently deleted for :u.', ['n'=>$count,'u'=>$user->email]));
        return back();
    }
}
