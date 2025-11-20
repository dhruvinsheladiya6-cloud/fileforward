<?php

namespace App\Http\Controllers\Backend\Uploads;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FileEntry;
use App\Models\UploadSettings;
use Illuminate\Http\Request;

class UsersOverviewController extends Controller
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

        $query = User::query()
            ->withSum(['fileEntries as total_size'], 'size')
            ->withSum(['fileEntries as total_downloads'], 'downloads')
            ->withSum(['fileEntries as total_views'], 'views')
            ->withCount('fileEntries as files_count')
            ->withCount([
                'fileEntries as files_documents_count' => function ($q) {
                    $q->whereIn('type', ['file', 'pdf'])
                    ->userEntry()
                    ->notExpired();
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

        // Get the IDs for the current filter (BEFORE paginate)
        $filteredUserIds = (clone $query)->pluck('id');

        // Safe defaults if none
        if ($filteredUserIds->isEmpty()) {
            $summaryImages    = 0;
            $summaryDocs      = 0;
            $summaryUsedBytes = 0;
        } else {
            $summaryImages    = \App\Models\FileEntry::whereIn('user_id', $filteredUserIds)
                ->where('type', 'image')
                ->userEntry()
                ->notExpired()
                ->count();

            $summaryDocs      = \App\Models\FileEntry::whereIn('user_id', $filteredUserIds)
                ->whereIn('type', ['file', 'pdf'])
                ->userEntry()
                ->notExpired()
                ->count();

            $summaryUsedBytes = \App\Models\FileEntry::whereIn('user_id', $filteredUserIds)
                ->userEntry()
                ->notExpired()
                ->sum('size');
        }

        $summaryUsedSpace = formatBytes($summaryUsedBytes);

        // Now paginate the users list
        $users = $query->paginate(20)->withQueryString();

        return view('backend.uploads.users_overview.index', compact(
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
        $folderSid     = $request->get('folder'); // current folder shared_id
        $currentFolder = null;

        // Validate the current folder (belongs to user, is a folder, and NOT trashed)
        if ($folderSid) {
            $currentFolder = FileEntry::query()
                ->where('user_id', $user->id)
                ->where('type', 'folder')
                ->where('shared_id', $folderSid)
                ->firstOrFail();
        }

        // Base scope: entries under current folder (or root = parent_id null)
        $entriesQ = FileEntry::query()
            ->where('user_id', $user->id)
            ->where('parent_id', $currentFolder?->id) // null means root
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('mime', 'like', "%{$search}%")
                    ->orWhere('extension', 'like', "%{$search}%")
                    ->orWhere('shared_id', 'like', "%{$search}%");
                });
            });

        // One list: folders first, then files, alphabetical
        $entries = $entriesQ
            ->orderByRaw("CASE WHEN type='folder' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->with(['storageProvider','parent'])
            ->paginate(20)
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

        // Counters (overview = not trashed)
        $totalImages        = FileEntry::where('user_id', $user->id)->where('type','image')->count();
        $totalFileDocuments = FileEntry::where('user_id', $user->id)->where('type','!=','image')->count();
        $usedBytes          = FileEntry::where('user_id', $user->id)->sum('size');
        $usedSpace          = formatBytes($usedBytes);

        $uploadMode         = UploadSettings::getUploadMode();

        return view('backend.uploads.users_overview.files', compact(
            'user',
            'entries',
            'currentFolder',
            'breadcrumbs',
            'search',
            'totalImages',
            'totalFileDocuments',
            'usedSpace',
            'uploadMode'
        ));
    }

}
