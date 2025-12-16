<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Methods\FileDetailsDetector;
use App\Models\FileEntry;
use App\Models\FileRequest;
use App\Models\StorageProvider;
use App\Http\Methods\SubscriptionManager;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Validator;

class PublicFileRequestController extends Controller
{
    /**
     * Show public upload page for a given token.
     */
    public function showForm($token, Request $request)
    {
        $fileRequest = FileRequest::active()
            ->with(['owner', 'folder'])
            ->where('token', $token)
            ->firstOrFail();

        // If password protected and not unlocked in this session,
        // show password form instead of upload form.
        if ($fileRequest->password && !session()->get("file_request_{$token}_unlocked")) {
            return view('frontend.public.file-request-password', [
                'fileRequest' => $fileRequest,
                'token' => $token,
            ]);
        }

        // $uploadedFiles = FileEntry::where('parent_id', optional($fileRequest->folder)->id)
        //     ->whereNull('deleted_at')
        //     ->latest()
        //     ->get();

        $uploadedFiles = FileEntry::where('uploaded_via_share_id', $fileRequest->id)
        ->whereNull('deleted_at')
        ->where('type', '!=', 'folder')
        ->latest()
        ->get();

        $owner = $fileRequest->owner;
        $folder = $fileRequest->folder;

        $folderPath = '';
        if ($folder) {
            $segments = [];
            $current = $folder;
            while ($current) {
                $segments[] = $current->name;
                $current = $current->parent;
            }
            $folderPath = implode(' / ', array_reverse($segments));
        }

        $maxFileSizeMB = floor($fileRequest->maxFileSizeBytes() / (1024 * 1024));

        // Calculate effective storage limit:
        // 1. Start with request's storage_limit (or null if unlimited)
        // 2. Get owner's current available space
        // 3. Use minimum of both as effective limit
        $ownerAvailableSpace = null;
        if ($owner) {
            $subscriptionDetails = SubscriptionManager::registredUserSubscriptionDetails($owner);
            $ownerAvailableSpace = $subscriptionDetails['storage']['remining']['number'] ?? null;
        }

        // Effective limit logic
        $storageLimit = $fileRequest->storage_limit;

        // If owner has a limit, we must respect it
        if ($ownerAvailableSpace !== null) {
            if ($storageLimit === null) {
                // No request limit, so owner limit applies
                $storageLimit = $ownerAvailableSpace;
            } else {
                // Both exist, take the smaller one
                $storageLimit = min($storageLimit, $ownerAvailableSpace);
            }
        }

        $usedStorage = 0;
        if ($fileRequest->folder) {
            $usedStorage = FileEntry::where('parent_id', $fileRequest->folder->id)
                ->whereNull('deleted_at')
                ->sum('size');
        }

        return view('frontend.public.file-request-upload', compact(
            'fileRequest',
            'owner',
            'folder',
            'folderPath',
            'maxFileSizeMB',
            'storageLimit',
            'usedStorage',
            'uploadedFiles'
        ));

    }

    /**
     * Handle password unlock.
     */
    public function unlock($token, Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $fileRequest = FileRequest::active()
            ->where('token', $token)
            ->first();

        if (!$fileRequest) {
            return redirect()->route('file-request.show', $token)
                ->withErrors(['password' => 'This upload link is no longer available.']);
        }

        if (!$fileRequest->password) {
            // No password actually set; just go to upload page
            session()->put("file_request_{$token}_unlocked", true);
            return redirect()->route('file-request.show', $token);
        }

        if (!Hash::check($request->password, $fileRequest->password)) {
            return back()->withErrors(['password' => 'Incorrect password.'])->withInput();
        }

        session()->put("file_request_{$token}_unlocked", true);

        return redirect()->route('file-request.show', $token);
    }

    /**
     * Handle actual file upload for a public request.
     */
    public function upload($token, Request $request)
    {
        @set_time_limit(0);

        $fileRequest = FileRequest::active()
            ->with(['owner', 'folder'])
            ->where('token', $token)
            ->first();

        if (!$fileRequest) {
            return $this->error('This upload link is no longer available.', 410);
        }

        // Extra safety: prevent bypassing password by direct POST
        if ($fileRequest->password && !session()->get("file_request_{$token}_unlocked")) {
            return $this->error('You must enter the correct password before uploading.', 403);
        }

        $uploadedFile = $request->file('file');
        if (!$uploadedFile) {
            return $this->error('No file uploaded.');
        }

        $uploadedFileName = $uploadedFile->getClientOriginalName();

        // Validate client-side size vs request max
        $validator = Validator::make($request->all(), [
            'size' => ['required', 'integer', 'min:1', 'max:' . $fileRequest->maxFileSizeBytes()],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first() . ' (' . $uploadedFileName . ')');
        }

        try {
            $owner = $fileRequest->owner;
            $ownerId = $owner->id;
            $parentFolder = $fileRequest->folder; // may be null (root)

            // Storage provider
            $storageProvider = StorageProvider::where([
                ['symbol', env('FILESYSTEM_DRIVER')],
                ['status', 1],
            ])->first();

            if (is_null($storageProvider)) {
                return $this->error(lang('Unavailable storage provider', 'upload zone'));
            }

            // Chunk receiver (Pion)
            $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

            if ($receiver->isUploaded() === false) {
                return $this->error('Failed to upload (' . $uploadedFileName . ')');
            }

            $save = $receiver->receive();

            // If not finished yet, just return success; Pion will call again
            if (!$save->isFinished()) {
                return response()->json(['type' => 'success']);
            }

            // Final assembled file
            $file = $save->getFile();
            $fileName = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $fileMimeType = (!is_null(getFileMimeType($fileExtension)))
                ? getFileMimeType($fileExtension)
                : $file->getMimeType();

            if (empty($fileExtension)) {
                $fileExtension = FileDetailsDetector::lookupExtension($fileMimeType);
            }

            $fileSize = $file->getSize();
            if ($fileSize == 0) {
                return $this->error(lang('Empty files cannot be uploaded', 'upload zone'));
            }

            // Hard server-side cap check
            if ($fileSize > $fileRequest->maxFileSizeBytes()) {
                removeFile($file);
                return $this->error(
                    'File is too big. Max allowed is ' . floor($fileRequest->maxFileSizeBytes() / 1024 / 1024) . 'MB'
                );
            }

            // --- Storage Limit Enforcement ---

            // 1. Calculate current usage in the request folder
            $currentUsed = 0;
            if ($fileRequest->folder) {
                $currentUsed = FileEntry::where('parent_id', $fileRequest->folder->id)
                    ->whereNull('deleted_at')
                    ->sum('size');
            }

            // 2. Check against Request's specific storage limit (if set)
            if ($fileRequest->storage_limit) {
                if (($currentUsed + $fileSize) > $fileRequest->storage_limit) {
                    removeFile($file);
                    return $this->error(
                        'Upload rejected. Request storage limit exceeded. Remaining space: ' . formatBytes(max(0, $fileRequest->storage_limit - $currentUsed))
                    );
                }
            }

            // 3. Check against Owner's total available account space
            $ownerAvailableSpace = null;
            if ($owner) {
                $subscriptionDetails = SubscriptionManager::registredUserSubscriptionDetails($owner);
                $ownerAvailableSpace = $subscriptionDetails['storage']['remining']['number'] ?? null;
            }

            if ($ownerAvailableSpace !== null) {
                // Note: ownerAvailableSpace is the *remaining* space, not total capacity.
                // So we just check if fileSize > available
                if ($fileSize > $ownerAvailableSpace) {
                    removeFile($file);
                    return $this->error(
                        'Upload rejected. Owner\'s storage space exceeded. Remaining space: ' . formatBytes($ownerAvailableSpace)
                    );
                }
            }

            $ip = vIpInfo()->ip;
            $sharedId = Str::random(15);

            // Resolve storage path
            $parentDbId = $parentFolder?->id;
            $basePath = $parentFolder
                ? rtrim($parentFolder->getRawOriginal('path'), '/')
                : 'folders/' . $ownerId; // root for this user

            // Unique name inside that folder for that owner
            $finalDisplayName = $this->generateUniqueFilename($ownerId, $parentDbId, $fileName);

            $handler = $storageProvider->handler;
            $storagePath = $basePath;
            $storageName = $finalDisplayName;

            $uploadResponse = $handler::upload($file, $storagePath, $storageName);

            if ($uploadResponse->type == 'error') {
                Log::error('Public upload storage failed', [
                    'error' => $uploadResponse->msg ?? 'Unknown error',
                    'storage_path' => $storagePath,
                ]);
                return $this->error('Storage error: ' . ($uploadResponse->msg ?? 'Unknown'));
            }

            // Create DB entry
            $fileEntry = new FileEntry();
            $fileEntry->ip = $ip;
            $fileEntry->shared_id = $sharedId;
            $fileEntry->user_id = $ownerId;
            $fileEntry->parent_id = $parentDbId;
            $fileEntry->storage_provider_id = $storageProvider->id;
            $fileEntry->name = $finalDisplayName;
            $fileEntry->filename = $storageName;
            $fileEntry->mime = $fileMimeType;
            $fileEntry->size = $fileSize;
            $fileEntry->extension = $fileExtension;
            $fileEntry->type = getFileType($fileMimeType);
            $fileEntry->path = $storagePath;
            $fileEntry->link = $uploadResponse->link;
            $fileEntry->access_status = 0; // Private by default
            $fileEntry->uploaded_via_share_id = $fileRequest->id;
            $fileEntry->save();

            // Increment count
            $fileRequest->increment('uploads_count');

            return response()->json([
                'type' => 'success',
                'msg' => 'File uploaded successfully',
            ]);

        } catch (Exception $e) {
            Log::error('Public upload exception', ['error' => $e->getMessage()]);
            return $this->error('Server error during upload.');
        }
    }

    private function error($message, $code = 422)
    {
        return response()->json([
            'type' => 'error',
            'msg' => $message,
        ], $code);
    }

    private function generateUniqueFilename($userId, $parentId, $originalName)
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);

        $count = 0;
        $finalName = $originalName;

        while (
            FileEntry::where('user_id', $userId)
                ->where('parent_id', $parentId)
                ->where('name', $finalName)
                ->whereNull('deleted_at')
                ->exists()
        ) {
            $count++;
            $finalName = $name . ' (' . $count . ').' . $ext;
        }

        return $finalName;
    }
}
