<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Methods\FileDetailsDetector;
use App\Models\FileEntry;
use App\Models\StorageProvider;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Str;
use Validator;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        @set_time_limit(0);

        $uploadedFile = $request->file('file');
        $uploadedFileName = $uploadedFile->getClientOriginalName();
        
        $validator = Validator::make($request->all(), [
            'password' => ['nullable', 'max:255'],
            'upload_auto_delete' => ['required', 'integer', 'min:0', 'max:365'],
            'parent_folder_id' => ['nullable', 'string'], // Add folder support
            'size' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                return static::errorResponseHandler($error . ' (' . $uploadedFileName . ')');
            }
        }
    try {
        if (!subscription()->is_subscribed) {
            return static::errorResponseHandler(lang('Login or create account to start uploading files', 'alerts'));
        }

        if (subscription()->is_expired) {
            return static::errorResponseHandler(lang('Your subscription has been expired, renew it to start uploading files', 'alerts'));
        }

        if (subscription()->is_canceled) {
            return static::errorResponseHandler(lang('Your subscription has been canceled, please contact us for more information', 'alerts'));
        }

        // if (!array_key_exists($request->upload_auto_delete, autoDeletePeriods())) {
        //     return static::errorResponseHandler(lang('Invalid file auto delete time', 'upload zone'));
        // } else {
        //     if (autoDeletePeriods()[$request->upload_auto_delete]['days'] != 0) {
        //         $expiryAt = autoDeletePeriods()[$request->upload_auto_delete]['datetime'];
        //     } else {
        //         $expiryAt = null;
        //     }
        // }

        if ($request->has('password') && !is_null($request->password) && $request->password != "undefined") {
            if (subscription()->plan->password_protection) {
                $request->password = Hash::make($request->password);
            } else {
                $request->password = null;
            }
        }

        /*
        $unacceptableFileTypes = explode(',', settings('unacceptable_file_types'));
        $fileExt = $uploadedFile->getclientoriginalextension();
        if (in_array($fileExt, $unacceptableFileTypes)) {
            return static::errorResponseHandler(lang('You cannot upload files of this type.', 'upload zone'));
        }
        */
        // $maxFileSize = 100 * 1024 * 1024; // 100MB limit example
        // if ($uploadedFile->getSize() > $maxFileSize) {
        //     return static::errorResponseHandler(lang('File size too large. Maximum allowed size is 100MB.', 'upload zone'));
        // }

        if (!is_null(subscription()->plan->file_size)) {
            if ($request->size > subscription()->plan->file_size) {
                return static::errorResponseHandler(str_replace('{maxFileSize}', subscription()->formates->file_size, lang('File is too big, Max file size {maxFileSize}', 'upload zone')));
            }
        }

        if (!is_null(subscription()->storage->remining->number)) {
            if ($request->size > subscription()->storage->remining->number) {
                return static::errorResponseHandler(lang('insufficient storage space please ensure sufficient space', 'upload zone'));
            }
        }

        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        $storageProvider = StorageProvider::where([['symbol', env('FILESYSTEM_DRIVER')], ['status', 1]])->first();
        if (is_null($storageProvider)) {
            return static::errorResponseHandler(lang('Unavailable storage provider', 'upload zone'));
        }

        if ($receiver->isUploaded() === false) {
            return static::errorResponseHandler(str_replace('{filename}', $uploadedFileName, lang('Failed to upload ({filename})', 'upload zone')));
        }

        $save = $receiver->receive();
        if ($save->isFinished()) {
            $file = $save->getFile();
            $fileName = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $fileMimeType = (!is_null(getFileMimeType($fileExtension))) ? getFileMimeType($fileExtension) : $file->getMimeType();

            if (empty($fileExtension)) {
                $fileExtension = FileDetailsDetector::lookupExtension($fileMimeType);
            }

                $fileSize = $file->getSize();
                if ($fileSize == 0) {
                    return static::errorResponseHandler(lang('Empty files cannot be uploaded', 'upload zone'));
                }

                if (!is_null(subscription()->plan->file_size)) {
                    if ($fileSize > subscription()->plan->file_size) {
                        removeFile($file);
                        return static::errorResponseHandler(str_replace('{maxFileSize}', subscription()->formates->file_size, lang('File is too big, Max file size {maxFileSize}', 'upload zone')));
                    }
                }

                if (!is_null(subscription()->storage->remining->number)) {
                    if ($fileSize > subscription()->storage->remining->number) {
                        removeFile($file);
                        return static::errorResponseHandler(lang('insufficient storage space please ensure sufficient space', 'upload zone'));
                    }
                }

                $ip = vIpInfo()->ip;
                $sharedId = Str::random(15);
                $userId = Auth::user()->id;

                // CRITICAL FIX: Proper path handling for folder uploads
                $parentFolderId = $request->parent_folder_id;
                $parentDbId = null;
                $storageUploadPath = 'folders/' . $userId; // Default storage location
                $databaseFilePath = 'folders/' . $userId; // Default database path

                if ($parentFolderId) {
                    \Log::info('Processing folder upload', ['parent_folder_id' => $parentFolderId]);
                    
                    $parentFolder = FileEntry::where('shared_id', $parentFolderId)
                        ->where('type', 'folder')
                        ->where('user_id', $userId)
                        ->first();
                    
                    if ($parentFolder) {
                        $parentDbId = $parentFolder->id;
                        
                        // Get the folder's stored path from database
                        $folderPath = rtrim($parentFolder->getRawOriginal('path'), '/');
                        
                        // Use folder path for both storage and database
                        $storageUploadPath = $folderPath;
                        $databaseFilePath = $folderPath;
                        
                        \Log::info('Using folder path', [
                            'folder_name' => $parentFolder->name,
                            'folder_path' => $folderPath,
                            'storage_path' => $storageUploadPath,
                            'parent_db_id' => $parentDbId
                        ]);
                    } else {
                        \Log::warning('Parent folder not found', ['parent_folder_id' => $parentFolderId]);
                        // Fall back to root upload
                    }
                }

                \Log::info('Upload paths determined', [
                    'storage_upload_path' => $storageUploadPath,
                    'database_file_path' => $databaseFilePath
                ]);

                // === Handle duplicate filenames ===
                $finalDisplayName = $fileName; // original name

                $finalDisplayName = $this->generateUniqueFilename($userId, $parentDbId, $finalDisplayName);

                // Ensure storage also uses the resolved unique name
                $storageFileName = $finalDisplayName;
                $handler = $storageProvider->handler;
                $uploadResponse = $handler::upload($file, $storageUploadPath, $storageFileName);

                
                if ($uploadResponse->type == "error") {
                    \Log::error('Storage upload failed', [
                        'error' => $uploadResponse->msg ?? 'Unknown error',
                        'storage_path' => $storageUploadPath
                    ]);
                    return $uploadResponse;
                }

                // CRITICAL FIX: Build complete file path for database
                $completeFilePath = $databaseFilePath . '/' . $uploadResponse->filename;
                
                \Log::info('File upload completed', [
                    'storage_filename' => $uploadResponse->filename,
                    'complete_database_path' => $completeFilePath,
                    'parent_id' => $parentDbId
                ]);
                
                $createFileEntry = FileEntry::create([
                    'ip' => $ip,
                    'shared_id' => $sharedId,
                    'user_id' => $userId,
                    'parent_id' => $parentDbId, // CRITICAL: Links file to folder
                    'storage_provider_id' => $storageProvider->id,
                    'name' => $finalDisplayName,                              // display name with (n)
                    'filename' => $uploadResponse->filename ?? $storageFileName, 
                    'mime' => $fileMimeType,
                    'size' => $fileSize,
                    'extension' => $fileExtension,
                    'type' => getFileType($fileMimeType),
                    'path' => $completeFilePath, // ✅ Full DB path
                    'link' => $uploadResponse->link,
                    'password' => $request->password,
                    // 'expiry_at' => $expiryAt,
                    'access_status' => 0, // Add this line for initial access status
                ]);

                if ($createFileEntry) {
                \Log::info('File entry created successfully', [
                    'file_id' => $createFileEntry->id,
                    'shared_id' => $createFileEntry->shared_id,
                    'database_path' => $createFileEntry->path,
                    'parent_id' => $createFileEntry->parent_id
                ]);
                
                $previewId = null;
                $previewLink = null;
                
                if ($createFileEntry->type == "image" || $createFileEntry->type == "pdf") {
                    $previewId = "preview_" . $createFileEntry->shared_id;
                    $previewLink = route('file.preview', $createFileEntry->shared_id);
                }

                return response()->json([
                    'type' => 'success',
                    'download_id' => "download_" . $createFileEntry->shared_id,
                    'download_link' => route('file.download', $createFileEntry->shared_id),
                    'preview_id' => $previewId,
                    'preview_link' => $previewLink,
                ]);
            } else {
                \Log::error('Failed to create file entry in database');
                return static::errorResponseHandler('Failed to save file information');
            }
        }
        } catch (Exception $e) {
        \Log::error('Upload exception occurred', [
            'filename' => $uploadedFileName,
            'error_message' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString()
        ]);
        
        return static::errorResponseHandler('Upload failed: ' . $e->getMessage() . ' (' . $uploadedFileName . ')');
    }
    }


    private static function errorResponseHandler($response, $status = 422)
    {
        return response()->json(['type' => 'error', 'msg' => $response], $status);
    }

    // put this just under class UploadController extends Controller { ... }
    protected function generateUniqueFilename(int $userId, ?int $parentDbId, string $originalName): string
    {
        // Split base + extension (safe for multi-dot names)
        $dotPos = strrpos($originalName, '.');
        if ($dotPos !== false) {
            $base = substr($originalName, 0, $dotPos);
            $extWithDot = substr($originalName, $dotPos); // includes the dot
        } else {
            $base = $originalName;
            $extWithDot = '';
        }

        // If no duplicate, return as-is
        $exists = FileEntry::where('user_id', $userId)
            ->where('parent_id', $parentDbId)
            ->where('type', '!=', 'folder')
            ->whereNull('deleted_at')
            ->where('name', $originalName)
            ->exists();

        if (!$exists) {
            return $originalName;
        }

        // Find next available suffix: (1), (2), ...
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
