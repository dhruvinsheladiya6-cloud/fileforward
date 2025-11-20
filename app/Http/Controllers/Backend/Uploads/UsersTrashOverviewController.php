<?php

namespace App\Http\Controllers\Backend\Uploads;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FileEntry;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UsersTrashOverviewController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $sortable = [
            'size'      => 'total_size',
            'downloads' => 'total_downloads',
            'views'     => 'total_views',
            'files'     => 'files_documents_count',
            'name'      => 'firstname',
            'email'     => 'email',
        ];

        $sortKey   = $request->get('sort', 'size');
        $direction = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortCol   = $sortable[$sortKey] ?? 'total_size';

        // same as UsersOverview, but restricted to TRASHED
        $query = User::query()
            ->withSum(['fileEntries as total_size'      => function ($q) { $q->trashed(); }], 'size')
            ->withSum(['fileEntries as total_downloads' => function ($q) { $q->trashed(); }], 'downloads')
            ->withSum(['fileEntries as total_views'     => function ($q) { $q->trashed(); }], 'views')
            ->withCount(['fileEntries as files_count'   => function ($q) { $q->trashed(); }])
            ->withCount([
                'fileEntries as files_documents_count' => function ($q) {
                    $q->trashed()->whereIn('type', ['file', 'pdf'])->userEntry();
                }
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (in_array($sortKey, ['size', 'downloads', 'views', 'files'], true)) {
            $query->orderByRaw("COALESCE({$sortCol}, 0) {$direction}");
        } else {
            $query->orderBy($sortCol, $direction);
        }

        // IDs of users present in current (trash) filter
        $filteredUserIds = (clone $query)->pluck('id');

        if ($filteredUserIds->isEmpty()) {
            $summaryImages    = 0;
            $summaryDocs      = 0;
            $summaryUsedBytes = 0;
        } else {
            $summaryImages = FileEntry::whereIn('user_id', $filteredUserIds)
                ->trashed()->where('type', 'image')->userEntry()->count();

            $summaryDocs = FileEntry::whereIn('user_id', $filteredUserIds)
                ->trashed()->whereIn('type', ['file', 'pdf'])->userEntry()->count();

            $summaryUsedBytes = FileEntry::whereIn('user_id', $filteredUserIds)
                ->trashed()->userEntry()->sum('size');
        }

        $summaryUsedSpace = formatBytes($summaryUsedBytes);

        $users = $query->paginate(20)->withQueryString();

        return view('backend.uploads.users_trash.index', compact(
            'users',
            'search',
            'summaryImages',
            'summaryDocs',
            'summaryUsedSpace'
        ));
    }

    public function files(Request $request, User $user)
    {
        $search        = trim((string) $request->get('search', ''));
        $folderSid     = $request->get('folder'); // folder shared_id (like your demo)
        $currentFolder = null;

        // Validate current folder (belongs to this user, trashed, and a folder)
        if ($folderSid) {
            $currentFolder = FileEntry::query()
                ->forUser($user->id)
                ->trashed()
                ->where('type', 'folder')
                ->where('shared_id', $folderSid)
                ->firstOrFail();
        }

        // Base scope: entries under current folder (or root = parent_id null)
        $entriesQ = FileEntry::query()
            ->forUser($user->id)
            ->trashed()
            ->where('parent_id', $currentFolder?->id) // null means root
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('mime', 'like', "%{$search}%")
                    ->orWhere('extension', 'like', "%{$search}%")
                    ->orWhere('shared_id', 'like', "%{$search}%");
                });
            });

        // Single list: folders first, then files, alphabetical
        $entries = $entriesQ
            ->orderByRaw("CASE WHEN type='folder' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->with('storageProvider','parent')
            ->paginate(50)
            ->withQueryString();

        // Breadcrumbs (Root -> ... -> current)
        $breadcrumbs = collect();
        if ($currentFolder) {
            $cursor = $currentFolder;
            while ($cursor && $cursor->parent_id) {
                $cursor = FileEntry::with('parent')->find($cursor->parent_id);
                if ($cursor) $breadcrumbs->prepend($cursor);
            }
        }

        // Counters (whole trash for that user, as you have)
        $totalImages         = FileEntry::forUser($user->id)->trashed()->where('type','image')->count();
        $totalFileDocuments  = FileEntry::forUser($user->id)->trashed()->where('type','!=','image')->count();
        $usedBytes           = FileEntry::forUser($user->id)->trashed()->sum('size');
        $usedSpace           = formatBytes($usedBytes);

        return view('backend.uploads.users_trash.files', compact(
            'user','entries','currentFolder','breadcrumbs','search',
            'totalImages','totalFileDocuments','usedSpace'
        ));
    }

    /** ---------- OPTIONAL: actions ---------- */

    // Restore a single file from trash
    public function restore(string $shared_id)
    {
        $file = FileEntry::where('shared_id', $shared_id)->trashed()->firstOrFail();
        $file->update(['deleted_at' => null, 'purge_at' => null]);
        return back()->with('success', __('File restored.'));
    }

    // Restore multiple (expects "restore_ids" CSV)
    public function restoreSelected(Request $request)
    {
        $ids = collect(explode(',', (string) $request->input('restore_ids')))->filter()->all();
        if ($ids) {
            FileEntry::whereIn('id', $ids)->trashed()->update(['deleted_at' => null, 'purge_at' => null]);
        }
        return back()->with('success', __('Selected files restored.'));
    }

    // Mark a single file for purge now (uses your purge queue)
    public function schedulePurge(string $shared_id)
    {
        $file = FileEntry::where('shared_id', $shared_id)->trashed()->firstOrFail();
        $file->markForPurgeRecursive(Carbon::now());
        return back()->with('success', __('File scheduled for purge.'));
    }

    public function schedulePurgeSelected(Request $request)
    {
        $ids = collect(explode(',', (string) $request->input('delete_ids')))->filter()->all();
        if ($ids) {
            $when = Carbon::now();
            FileEntry::whereIn('id', $ids)->trashed()->get()->each->markForPurgeRecursive($when);
        }
        return back()->with('success', __('Selected files scheduled for purge.'));
    }
}
