<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Methods\FileDetailsDetector;
use App\Models\FileEntry;
use App\Models\FileRequest;
use App\Models\StorageProvider;
use Exception;
use Illuminate\Http\Request;
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
    public function showForm($token)
    {
        $fileRequest = FileRequest::active()
            ->with(['owner', 'folder'])
            ->where('token', $token)
            ->firstOrFail();

        $owner  = $fileRequest->owner;
        $folder = $fileRequest->folder;

        $folderPath = '';
        if ($folder) {
            $segments = [];
            $current  = $folder;
            while ($current) {
                $segments[] = $current->name;
                $current    = $current->parent;
            }
            $folderPath = implode(' / ', array_reverse($segments));
        }

        $maxFileSizeMB = floor($fileRequest->maxFileSizeBytes() / (1024 * 1024)); // e.g. 50

        return view('frontend.public.file-request-upload', compact(
            'fileRequest',
            'owner',
            'folder',
            'folderPath',
            'maxFileSizeMB'
        ));
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

        $uploadedFile = $request->file('file');
        if (!$uploadedFile) {
            return $this->error('No file uploaded.');
        }

        $uploadedFileName = $uploadedFile->getClientOriginalName();

        // Validate client-side size vs request max (e.g., 50MB)
        $validator = Validator::make($request->all(), [
            'size' => ['required', 'integer', 'min:1', 'max:'.$fileRequest->maxFileSizeBytes()],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first().' ('.$uploadedFileName.')');
        }

        try {
            $owner       = $fileRequest->owner;
            $ownerId     = $owner->id;
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
                return $this->error('Failed to upload ('.$uploadedFileName.')');
            }

            $save = $receiver->receive();

            // If not finished yet, just return success; Pion will call again
            if (!$save->isFinished()) {
                return response()->json(['type' => 'success']);
            }

            // Final assembled file
            $file          = $save->getFile();
            $fileName      = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $fileMimeType  = (!is_null(getFileMimeType($fileExtension)))
                ? getFileMimeType($fileExtension)
                : $file->getMimeType();

            if (empty($fileExtension)) {
                $fileExtension = FileDetailsDetector::lookupExtension($fileMimeType);
            }

            $fileSize = $file->getSize();
            if ($fileSize == 0) {
                return $this->error(lang('Empty files cannot be uploaded', 'upload zone'));
            }

            // Hard server-side 50MB cap (or whatever is set on the request)
            if ($fileSize > $fileRequest->maxFileSizeBytes()) {
                removeFile($file);
                return $this->error(
                    'File is too big. Max allowed is '.floor($fileRequest->maxFileSizeBytes() / 1024 / 1024).'MB'
                );
            }

            // (Optional) If you want to enforce owner storage limit, plug your subscription() logic here.

            $ip       = vIpInfo()->ip;
            $sharedId = Str::random(15);

            // Resolve storage path
            $parentDbId = $parentFolder?->id;
            $basePath   = $parentFolder
                ? rtrim($parentFolder->getRawOriginal('path'), '/')
                : 'folders/'.$ownerId; // root for this user

            // Unique name inside that folder for that owner
            $finalDisplayName = $this->generateUniqueFilename($ownerId, $parentDbId, $fileName);

            $handler        = $storageProvider->handler;
            $storagePath    = $basePath;
            $storageName    = $finalDisplayName;

            $uploadResponse = $handler::upload($file, $storagePath, $storageName);

            if ($uploadResponse->type == 'error') {
                Log::error('Public upload storage failed', [
                    'error'        => $uploadResponse->msg ?? 'Unknown error',
                    'storage_path' => $storagePath,
                ]);
                return $this->error($uploadResponse->msg ?? 'Storage error');
            }

            $completeFilePath = $basePath.'/'.$uploadResponse->filename;

            $entry = FileEntry::create([
                'ip'                    => $ip,
                'shared_id'             => $sharedId,
                'user_id'               => $ownerId,
                'parent_id'             => $parentDbId,
                'storage_provider_id'   => $storageProvider->id,
                'name'                  => $finalDisplayName,
                'filename'              => $uploadResponse->filename ?? $storageName,
                'mime'                  => $fileMimeType,
                'size'                  => $fileSize,
                'extension'             => $fileExtension,
                'type'                  => getFileType($fileMimeType),
                'path'                  => $completeFilePath,
                'link'                  => $uploadResponse->link,
                'password'              => null,
                'access_status'         => 0, // private by default
                'uploaded_by'           => null, // guest
                'uploaded_via_share_id' => null,
            ]);

            $fileRequest->increment('uploads_count');

            $previewId   = null;
            $previewLink = null;

            if ($entry->type === 'image' || $entry->type === 'pdf') {
                $previewId   = 'preview_'.$entry->shared_id;
                $previewLink = route('file.preview', $entry->shared_id);
            }

            return response()->json([
                'type'          => 'success',
                'download_id'   => 'download_'.$entry->shared_id,
                'download_link' => route('file.download', $entry->shared_id),
                'preview_id'    => $previewId,
                'preview_link'  => $previewLink,
            ]);
        } catch (Exception $e) {
            Log::error('Public upload exception', [
                'token'        => $token,
                'file'         => $uploadedFileName ?? null,
                'error_message'=> $e->getMessage(),
                'stack_trace'  => $e->getTraceAsString(),
            ]);

            return $this->error('Upload failed: '.$e->getMessage().' ('.$uploadedFileName.')');
        }
    }

    private function error($msg, $status = 422)
    {
        return response()->json(['type' => 'error', 'msg' => $msg], $status);
    }

    /**
     * Same logic style as UploadController::generateUniqueFilename,
     * but scoped to owner + folder.
     */
    protected function generateUniqueFilename(int $userId, ?int $parentDbId, string $originalName): string
    {
        $dotPos = strrpos($originalName, '.');
        if ($dotPos !== false) {
            $base       = substr($originalName, 0, $dotPos);
            $extWithDot = substr($originalName, $dotPos); // includes dot
        } else {
            $base       = $originalName;
            $extWithDot = '';
        }

        $exists = FileEntry::where('user_id', $userId)
            ->where('parent_id', $parentDbId)
            ->where('type', '!=', 'folder')
            ->whereNull('deleted_at')
            ->where('name', $originalName)
            ->exists();

        if (!$exists) {
            return $originalName;
        }

        $n = 1;
        do {
            $candidate = "{$base} ({$n}){$extWithDot}";
            $exists = FileEntry::where('user_id', $userId)
                ->where('parent_id', $parentDbId)
                ->where('type', '!=', 'folder')
                ->whereNull('deleted_at')
                ->where('name', $candidate)
                ->exists();
            $n++;
        } while ($exists);

        return $candidate;
    }
}
