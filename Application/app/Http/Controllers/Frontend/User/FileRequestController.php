<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\FileEntry;
use App\Models\FileRequest;
use App\Http\Methods\SubscriptionManager;
use App\Mail\FileRequestInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Validator;

class FileRequestController extends Controller
{
    /** LIST page (Doc requests) */
    public function index(Request $request)
    {
        $user = Auth::user();

        $fileRequests = FileRequest::where('user_id', $user->id)
            ->with('folder')
            ->orderByDesc('created_at')
            ->paginate(20);

        $defaultFolderSharedId = $request->query('folder');
        $defaultFolderName  = null;
        $defaultFolderLabel = null;

        if ($defaultFolderSharedId) {
            $folder = FileEntry::with('parent')
                ->where('user_id', $user->id)
                ->where('shared_id', $defaultFolderSharedId)
                ->where('type', 'folder')
                ->whereNull('deleted_at')
                ->first();

            if ($folder) {
                $defaultFolderName = $folder->name;

                // Build breadcrumb using getBreadcrumbPathAttribute()
                $segments = [];

                // $folder->breadcrumb_path returns ancestors only
                foreach ($folder->breadcrumb_path as $crumb) {
                    $segments[] = $crumb->name;
                }

                // Add current folder itself
                $segments[] = $folder->name;

                // Final label like: "Root / Parent / Test"
                $defaultFolderLabel = 'Root';
                if (!empty($segments)) {
                    $defaultFolderLabel .= ' / ' . implode(' / ', $segments);
                }
            }
        }

        return view('frontend.user.file-requests.index', [
            'fileRequests'          => $fileRequests,
            'defaultFolderSharedId' => $defaultFolderSharedId,
            'defaultFolderName'     => $defaultFolderName,
            'defaultFolderLabel'    => $defaultFolderLabel,
        ]);
    }

    /** CREATE (used by both Files page popup & new Doc request modal) */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'folder_shared_id' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'max:255'],
            'expiration_date' => ['nullable', 'date'],
            'expiration_time' => ['nullable', 'date_format:H:i'],
            'is_doc_request' => ['nullable', 'boolean'],
            'storage_limit_value' => ['nullable', 'numeric', 'min:1'],
            'storage_limit_unit' => ['nullable', 'in:MB,GB'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = Auth::user();

        // CHECK: Duplicate title validation
        $titleInput = $request->input('title');
        if ($titleInput) {
            $existingRequest = FileRequest::where('user_id', $user->id)
                ->where('title', $titleInput)
                ->where('is_active', true)
                ->first();

            // if ($existingRequest) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'A file request with this title already exists. Please use a different title.',
            //     ], 422);
            // }
        }

        // --- Storage Limit Calculation ---
        // Default to 1GB if not provided to avoid SQL null error
        $storageLimitBytes = 1024 * 1024 * 1024;

        if ($request->filled('storage_limit_value')) {
            $value = (int) $request->storage_limit_value;
            $unit = $request->storage_limit_unit; // MB or GB

            if ($value > 0) {
                $bytes = $value * 1024 * 1024;
                if ($unit === 'GB') {
                    $bytes *= 1024;
                }
                $storageLimitBytes = $bytes;

                // Validate against user's available space
                $subscriptionDetails = SubscriptionManager::registredUserSubscriptionDetails($user);
                $availableSpace = $subscriptionDetails['storage']['remining']['number'] ?? null;

                if ($availableSpace !== null && $storageLimitBytes > $availableSpace) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Requested storage limit exceeds your available account storage (' . formatBytes($availableSpace) . ').',
                    ], 422);
                }
            }
        }

        $folder = null;
        $isDocRequest = (bool) $request->boolean('is_doc_request');
        $folderNameInput = trim((string) $request->input('folder_name'));

        // DOC REQUEST FLOW (from Doc requests page)
        if ($isDocRequest) {
            // 1) Determine parent folder (location)
            if ($request->filled('folder_shared_id')) {
                // User chose a location via "Change folder"
                $parentFolder = FileEntry::where('shared_id', $request->folder_shared_id)
                    ->where('user_id', $user->id)
                    ->where('type', 'folder')
                    ->notTrashed()
                    ->first();

                if (!$parentFolder) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Folder not found.',
                    ], 404);
                }
            } else {
                // Default location: "Doc requests" root folder under user's root
                // Try to reuse any existing root folder as a template
                $existingRoot = FileEntry::where('user_id', $user->id)
                    ->whereNull('parent_id')
                    ->where('type', 'folder')
                    ->whereNull('deleted_at')
                    ->first();

                $basePath = $existingRoot?->path ?? 'folders/' . $user->id;
                $baseStorageProvider = $existingRoot?->storage_provider_id ?? null;
                $baseLink = $existingRoot?->link ?? '';
                $baseFilename = $existingRoot?->filename ?? 'root';

                // Main "Doc requests" root folder (parent)
                $docRoot = FileEntry::where('user_id', $user->id)
                    ->whereNull('parent_id')
                    ->where('type', 'folder')
                    ->where('name', 'Doc requests')
                    ->whereNull('deleted_at')
                    ->first();

                if (!$docRoot) {
                    $docRoot = new FileEntry();
                    $docRoot->ip = $request->ip();
                    $docRoot->shared_id = Str::random(15);
                    $docRoot->user_id = $user->id;
                    $docRoot->parent_id = null;
                    $docRoot->storage_provider_id = $baseStorageProvider;
                    $docRoot->name = 'Doc requests';
                    $docRoot->filename = 'Doc requests';
                    $docRoot->mime = 'folder';
                    $docRoot->size = 0;
                    $docRoot->extension = '';
                    $docRoot->type = 'folder';
                    $docRoot->path = $basePath;
                    $docRoot->link = $baseLink;
                    $docRoot->access_status = 0;
                    $docRoot->password = null;
                    $docRoot->downloads = 0;
                    $docRoot->views = 0;
                    $docRoot->admin_has_viewed = 0;
                    $docRoot->expiry_at = null;
                    $docRoot->deleted_at = null;
                    $docRoot->purge_at = null;
                    $docRoot->uploaded_by = null;
                    $docRoot->uploaded_via_share_id = null;
                    $docRoot->save();
                }

                $parentFolder = $docRoot;
            }

            // 2) Child folder for this specific request (name from folder_name input)
            $folderName = $folderNameInput !== ''
                ? $folderNameInput
                : 'Request ' . now()->format('Y-m-d H:i:s.u');

            // Check if a folder with this exact name already exists under parent
            $existingFolder = FileEntry::where('user_id', $user->id)
                ->where('parent_id', $parentFolder->id)
                ->where('type', 'folder')
                ->where('name', $folderName)
                ->whereNull('deleted_at')
                ->first();

            if ($existingFolder) {
                // If user typed a folder_name and it already exists -> validation error
                if ($folderNameInput !== '') {
                    return response()->json([
                        'success' => false,
                        'message' => 'A folder with this name already exists in the selected location.',
                    ], 422);
                }
                $folder = $existingFolder;
            } else {
                // Create new child folder INSIDE the selected location
                $folder = new FileEntry();
                $folder->ip = $request->ip();
                $folder->shared_id = Str::random(15);
                $folder->user_id = $user->id;
                $folder->parent_id = $parentFolder->id;
                $folder->storage_provider_id = $parentFolder->storage_provider_id;
                $folder->name = $folderName;
                $folder->filename = $folderName;
                $folder->mime = 'folder';
                $folder->size = 0;
                $folder->extension = '';
                $folder->type = 'folder';
                $folder->path = $parentFolder->path;
                $folder->link = $parentFolder->link;
                $folder->access_status = 0;
                $folder->password = null;
                $folder->downloads = 0;
                $folder->views = 0;
                $folder->admin_has_viewed = 0;
                $folder->expiry_at = null;
                $folder->deleted_at = null;
                $folder->purge_at = null;
                $folder->uploaded_by = null;
                $folder->uploaded_via_share_id = null;
                $folder->save();
            }

        // NON-DOC-REQUEST FLOW (from Files page popup, etc.)
        } else {
            // Keep old behavior: use selected folder directly as upload location
            if ($request->filled('folder_shared_id')) {
                $folder = FileEntry::where('shared_id', $request->folder_shared_id)
                    ->where('user_id', $user->id)
                    ->where('type', 'folder')
                    ->notTrashed()
                    ->first();

                if (!$folder) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Folder not found.',
                    ], 404);
                }
            }
        }


        // --- Expiration handling ---
        $now = now();
        $expiresAt = null;

        if ($request->filled('expiration_date')) {
            $date = $request->expiration_date;
            $time = $request->expiration_time ?: '23:59';

            $expiresAt = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", config('app.timezone'))
                ->setTimezone(config('app.timezone'));

            if ($expiresAt->lessThanOrEqualTo($now)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expiration must be in the future.',
                ], 422);
            }

            // if ($expiresAt->greaterThan($now->copy()->addDay())) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Expiration cannot be more than 24 hours from now.',
            //     ], 422);
            // }
        } else {
            // default: 24 hours
            $expiresAt = $now->copy()->addDay();
        }

        // Password
        $passwordHash = $request->filled('password')
            ? Hash::make($request->password)
            : null;

        $title = $request->title ?: ($folder?->name ?? 'Upload files for ' . $user->email);

        $fileRequest = FileRequest::create([
            'user_id' => $user->id,
            'folder_id' => $folder?->id,
            'token' => Str::random(40),
            'title' => $title,
            'description' => $request->description,
            'password' => $passwordHash,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'storage_limit' => $storageLimitBytes,
        ]);

        $publicUrl = route('file-request.show', $fileRequest->token);

        return response()->json([
            'success' => true,
            'id' => $fileRequest->id,
            'url' => $publicUrl,
            'title' => $fileRequest->title,
            'expires_at' => optional($expiresAt)->toDateTimeString(),
            'protected' => (bool) $passwordHash,
        ]);
    }

    /** Single request details for "manage" modal (AJAX JSON) */
    public function show(FileRequest $fileRequest, Request $request)
    {
        $this->ensureOwner($fileRequest);

        // Convert storage_limit to value+unit for display
        $storageLimitValue = null;
        $storageLimitUnit = 'GB';
        if ($fileRequest->storage_limit) {
            $limitMB = $fileRequest->storage_limit / (1024 * 1024);
            if ($limitMB >= 1024) {
                $storageLimitValue = round($limitMB / 1024, 2);
                $storageLimitUnit = 'GB';
            } else {
                $storageLimitValue = round($limitMB, 2);
                $storageLimitUnit = 'MB';
            }
        }

        return response()->json([
            'id' => $fileRequest->id,
            'title' => $fileRequest->title,
            'description' => $fileRequest->description,
            'folder_id' => $fileRequest->folder?->id,
            'folder_shared_id' => $fileRequest->folder?->shared_id ?? null,
            'folder_path' => $fileRequest->folder_path,
            'expires_at' => optional($fileRequest->expires_at)?->toDateTimeString(),
            'is_active' => $fileRequest->is_active,
            'uploads_count' => $fileRequest->uploads_count,
            'views_count' => $fileRequest->views_count,
            'password_protected' => (bool) $fileRequest->password,
            'url' => $fileRequest->public_url,
            'storage_limit' => $fileRequest->storage_limit,
            'storage_limit_value' => $storageLimitValue,
            'storage_limit_unit' => $storageLimitUnit,
            // Note: password field intentionally omitted for security - never send password back to client
        ]);
    }

    /** UPDATE settings from manage modal */
    public function update(FileRequest $fileRequest, Request $request)
    {
        $this->ensureOwner($fileRequest);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'folder_shared_id' => ['nullable', 'string'],
            'folder_name' => ['nullable', 'string', 'max:255'], // 👈 NEW
            'password' => ['nullable', 'string', 'max:255'],
            'remove_password' => ['nullable', 'boolean'],
            'expiration_date' => ['nullable', 'date'],
            'expiration_time' => ['nullable', 'date_format:H:i'],
            'storage_limit_value' => ['nullable', 'numeric', 'min:1'],
            'storage_limit_unit' => ['nullable', 'in:MB,GB'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = Auth::user();

        // folder
        $folder = null;
        if ($request->filled('folder_shared_id')) {
            $folder = FileEntry::where('shared_id', $request->folder_shared_id)
                ->where('user_id', $user->id)
                ->where('type', 'folder')
                ->notTrashed()
                ->first();

            if (!$folder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder not found.',
                ], 404);
            }
        }

        // expiration (same rule as store)
        $now = now();
        $expiresAt = null;

        if ($request->filled('expiration_date')) {
            $date = $request->expiration_date;
            $time = $request->expiration_time ?: '23:59';

            $expiresAt = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", config('app.timezone'))
                ->setTimezone(config('app.timezone'));

            if ($expiresAt->lessThanOrEqualTo($now)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expiration must be in the future.',
                ], 422);
            }

            // if ($expiresAt->greaterThan($now->copy()->addDay())) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Expiration cannot be more than 24 hours from now.',
            //     ], 422);
            // }
        }

        // --- Storage Limit Calculation ---
        $storageLimitBytes = $fileRequest->storage_limit; // Keep existing if not modified
        if ($request->filled('storage_limit_value')) {
            $value = (int) $request->storage_limit_value;
            $unit = $request->storage_limit_unit; // MB or GB

            if ($value > 0) {
                $bytes = $value * 1024 * 1024;
                if ($unit === 'GB') {
                    $bytes *= 1024;
                }
                $storageLimitBytes = $bytes;

                // Validate against user's available space
                $subscriptionDetails = SubscriptionManager::registredUserSubscriptionDetails($user);
                $availableSpace = $subscriptionDetails['storage']['remining']['number'] ?? null;

                if ($availableSpace !== null && $storageLimitBytes > $availableSpace) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Requested storage limit exceeds your available account storage (' . formatBytes($availableSpace) . ').',
                    ], 422);
                }
            }
        } elseif ($request->has('storage_limit_value') && empty($request->storage_limit_value)) {
            // Empty value means remove limit -> Set to default 1GB
            $storageLimitBytes = 1024 * 1024 * 1024;
        }

        // password
        $passwordHash = $fileRequest->password;
        if ($request->boolean('remove_password')) {
            $passwordHash = null;
        } elseif ($request->filled('password')) {
            $passwordHash = Hash::make($request->password);
        }

        $fileRequest->update([
            'title' => $request->title,
            'description' => $request->description,
            'folder_id' => $folder?->id,
            'password' => $passwordHash,
            'expires_at' => $expiresAt,
            'storage_limit' => $storageLimitBytes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File request updated.',
        ]);
    }

    /** Close request (like Dropbox "Close request") */
    public function close(FileRequest $fileRequest)
    {
        $this->ensureOwner($fileRequest);

        $fileRequest->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'File request closed.',
        ]);
    }

    /** Optional: send invitation emails from "Share file request" modal */
    public function sendInvites(FileRequest $fileRequest, Request $request)
    {
        $this->ensureOwner($fileRequest);

        $data = $request->validate([
            'emails' => ['required', 'string'], // comma-separated
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $emails = collect(explode(',', $data['emails']))
            ->map('trim')
            ->filter()
            ->unique();

        $customMessage = $data['message'] ?? null;

        // Send email to each recipient
        foreach ($emails as $email) {
            Mail::to($email)->send(new FileRequestInvite($fileRequest, $customMessage));
        }

        return response()->json([
            'success' => true,
            'sent_to' => $emails->values(),
            'message' => 'Invitations sent successfully.',
        ]);
    }

    /** Get user's folders for folder picker */
    public function getFolders(Request $request)
    {
        $user = Auth::user();

        $folders = FileEntry::where('user_id', $user->id)
            ->where('type', 'folder')
            ->whereNull('deleted_at')
            ->select('id', 'shared_id', 'name', 'parent_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'folders' => $folders,
        ]);
    }

    protected function ensureOwner(FileRequest $fileRequest): void
    {
        if ($fileRequest->user_id !== Auth::id()) {
            abort(403);
        }
    }
}