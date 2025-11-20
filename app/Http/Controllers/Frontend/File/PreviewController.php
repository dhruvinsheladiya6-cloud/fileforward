<?php

namespace App\Http\Controllers\Frontend\File;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\File\DownloadController;
use App\Models\FileEntry;
use Session;

class PreviewController extends Controller
{
 public function index($shared_id)
    {
        try {
            $fileEntry = FileEntry::where('shared_id', $shared_id)
                ->notExpired()
                ->with('user')
                ->firstOrFail();

            // ---- keep the token handy
            $token = request('s');
            $tokenQS = $token ? ('&s=' . urlencode($token)) : '';

            // Access check (will read token from current request)
            abort_if(!DownloadController::accessCheck($fileEntry), 404);

            // Password protection (preserve token in redirects!)
            if (!is_null($fileEntry->password)) {
                $sessionKey = filePasswordSession($fileEntry->shared_id);

                if (!Session::has($sessionKey)) {
                    return redirect(route('file.password', $fileEntry->shared_id) . '?source=preview' . $tokenQS);
                } else {
                    $password = decrypt(Session::get($sessionKey));
                    if ($password != $fileEntry->password) {
                        return redirect(route('file.password', $fileEntry->shared_id) . '?source=preview' . $tokenQS);
                    }
                }
            }

            $fileEntry->increment('views');

            $fileCategory = $this->getFileCategory($fileEntry);
            $isPreviewSupported = $this->isPreviewSupported($fileCategory);

            // IMPORTANT: preserve ?s=... on the URLs used by the view/player
            $previewUrl  = route('secure.file', hashid($fileEntry->id)) . ($token ? ('?s=' . urlencode($token)) : '');
            $downloadUrl = route('file.download', $fileEntry->shared_id) . ($token ? ('?s=' . urlencode($token)) : '');

            return view('frontend.file.preview.unified', [
                'fileEntry'         => $fileEntry,
                'fileCategory'      => $fileCategory,
                'isPreviewSupported'=> $isPreviewSupported,
                'previewUrl'        => $previewUrl,
                'downloadUrl'       => $downloadUrl,
            ]);

        } catch (\Exception $e) {
            \Log::error('Preview error: ' . $e->getMessage());
            abort(404, 'File not found or cannot be previewed');
        }
    }

    
    private function getFileCategory($fileEntry)
    {
        $extension = strtolower($fileEntry->extension ?? '');
        $mime = strtolower($fileEntry->mime ?? '');
        
        // Image files
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']) || 
            strpos($mime, 'image/') === 0) {
            return 'image';
        }
        
        // PDF files
        if ($extension === 'pdf' || $mime === 'application/pdf') {
            return 'pdf';
        }
        
        // Video files
        if (in_array($extension, ['mp4', 'webm', 'ogg', 'avi', 'mov', 'mkv']) || 
            strpos($mime, 'video/') === 0) {
            return 'video';
        }
        
        // Audio files
        if (in_array($extension, ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac']) || 
            strpos($mime, 'audio/') === 0) {
            return 'audio';
        }
        
        // Text/Code files
        if (in_array($extension, ['txt', 'html', 'css', 'js', 'php', 'py', 'java', 'cpp', 'c', 'json', 'xml', 'md', 'log', 'csv']) || 
            strpos($mime, 'text/') === 0) {
            return 'text';
        }
        
        return 'unsupported';
    }
    
    private function isPreviewSupported($category)
    {
        return in_array($category, ['image', 'pdf', 'video', 'audio', 'text']);
    }
}
