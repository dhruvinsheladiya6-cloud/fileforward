<?php

namespace App\Http\Controllers\Frontend\File;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\File\DownloadController;
use App\Models\FileEntry;
use Response;
use Storage;

// class SecureController extends Controller
// {
//     public function index($id)
//     {
//         $fileEntry = FileEntry::where('id', unhashid($id))->notExpired()->hasPreview()->with('storageProvider')->firstOrFail();
//         abort_if(!DownloadController::accessCheck($fileEntry), 404);
//         $handler = $fileEntry->storageProvider->handler;
//         return $handler::getFile($fileEntry);
//     }
// }
class SecureController extends Controller
{
    public function index($id)
    {
        try {
            // Remove hasPreview() filter as it's blocking non-preview files
            $fileEntry = FileEntry::where('id', unhashid($id))
                ->notExpired()
                ->with('storageProvider')
                ->firstOrFail();
            
            // Check access permissions
            abort_if(!DownloadController::accessCheck($fileEntry), 404);
            
            // Handle different storage types
            if ($fileEntry->storageProvider) {
                $handler = $fileEntry->storageProvider->handler;
                return $handler::getFile($fileEntry);
            } else {
                // Handle local storage
                return $this->getLocalFile($fileEntry);
            }
        } catch (\Exception $e) {
            \Log::error('Secure file access error: ' . $e->getMessage());
            abort(404);
        }
    }
    
    private function getLocalFile($fileEntry)
    {
        $filePath = storage_path('app/uploads/' . $fileEntry->filename);
        
        if (!file_exists($filePath)) {
            abort(404);
        }
        
        $mimeType = $fileEntry->mime ?: mime_content_type($filePath);
        
        return Response::file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileEntry->name . '"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}