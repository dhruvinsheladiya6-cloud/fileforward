<?php

namespace App\Http\Controllers\Backend\Uploads;

use App\Http\Controllers\Controller;
use App\Models\FileEntry;
use App\Models\UploadSettings;
use Illuminate\Http\Request;

class UserUploadsController extends Controller
{
    public function index(Request $request)
    {
        $unviewedFiles = FileEntry::where('admin_has_viewed', 0)->userEntry()->notExpired()->get();
        if (count($unviewedFiles) > 0) {
            foreach ($unviewedFiles as $unviewedFile) {
                $unviewedFile->admin_has_viewed = 1;
                $unviewedFile->save();
            }
        }
        $totalImages = FileEntry::where('type', 'image')->userEntry()->notExpired()->count();
        $totalFileDocuments = FileEntry::whereIn('type', ['file', 'pdf'])->userEntry()->notExpired()->count();
        $usedSpace = FileEntry::userEntry()->notExpired()->sum('size');
        // Get current upload mode
        $uploadMode = UploadSettings::getUploadMode();

        if ($request->has('search')) {
            $q = $request->search;
            $fileEntries = FileEntry::where(function ($query) {
                $query->userEntry();
            })->where(function ($query) use ($q) {
                $query->where('shared_id', 'like', '%' . $q . '%')
                    ->OrWhere('name', 'like', '%' . $q . '%')
                    ->OrWhere('filename', 'like', '%' . $q . '%')
                    ->OrWhere('mime', 'like', '%' . $q . '%')
                    ->OrWhere('size', 'like', '%' . $q . '%')
                    ->OrWhere('extension', 'like', '%' . $q . '%');
            })->notExpired()->with('storageProvider')->orderbyDesc('id')->paginate(30);
            $fileEntries->appends(['search' => $q]);
        } elseif ($request->has('user')) {
            $fileEntries = FileEntry::where('user_id', unhashid($request->user))

                ->notExpired()
                ->with(['storageProvider'])
                ->orderbyDesc('id')
                ->paginate(30);
            $fileEntries->appends(['user' => $request->user]);
            $totalImages = FileEntry::where([['user_id', unhashid($request->user)], ['type', 'image']])->userEntry()->notExpired()->count();
            $totalFileDocuments = FileEntry::where('user_id', unhashid($request->user))->whereIn('type', ['file', 'pdf'])->userEntry()->notExpired()->count();
            $usedSpace = FileEntry::where('user_id', unhashid($request->user))->userEntry()->notExpired()->sum('size');
        } else {
            $fileEntries = FileEntry::userEntry()->notExpired()->with('storageProvider')->orderbyDesc('id')->paginate(30);
        }
        return view('backend.uploads.users.index', [
            'totalImages' => $totalImages,
            'totalFileDocuments' => $totalFileDocuments,
            'fileEntries' => $fileEntries,
            'usedSpace' => formatBytes($usedSpace),
            'uploadMode' => $uploadMode,
        ]);
    }

    /**
     * Toggle upload mode between custom and regular
     */
    public function toggleUploadMode(Request $request)
    {
        try {
            $setting = UploadSettings::toggleUploadMode();
            $mode = $setting->upload_mode;
            
            $message = $mode === 'custom' 
                ? __('Switched to Custom Upload Mode') 
                : __('Switched to Regular Upload Mode');
                
            toastr()->success($message);
        } catch (\Exception $e) {
            toastr()->error(__('Error updating upload mode'));
        }
        
        return back();
    }

    public function view($shared_id)
    {
        $fileEntry = FileEntry::where('shared_id', $shared_id)->userEntry()->notExpired()->with(['user', 'storageProvider'])->firstOrFail();
        return view('backend.uploads.users.view', ['fileEntry' => $fileEntry]);
    }

    public function downloadFile($shared_id)
    {
        $fileEntry = FileEntry::where('shared_id', $shared_id)->userEntry()->notExpired()->with('storageProvider')->firstOrFail();
        try {
            $handler = $fileEntry->storageProvider->handler;
            $download = $handler::download($fileEntry);
            if ($fileEntry->storageProvider->symbol == "local") {
                return $download;
            } else {
                return redirect($download);
            }
        } catch (\Exception$e) {
            toastr()->error(__('There was a problem while trying to download the file'));
            return back();
        }
    }

    public function destroy($shared_id)
    {
        $fileEntry = FileEntry::where('shared_id', $shared_id)->userEntry()->notExpired()->with('storageProvider')->first();
        if (is_null($fileEntry)) {
            toastr()->error(__('File not exists'));
            return back();
        }
        try {
            $handler = $fileEntry->storageProvider->handler;
            $delete = $handler::delete($fileEntry->path);
            if ($delete) {
                $fileEntry->delete();
                toastr()->success(__('Deleted successfully'));
                return redirect()->route('superadmin.uploads.users.index');
            }
        } catch (\Exception$e) {
            toastr()->error($e->getMessage());
            return back();
        }
    }

    public function destroySelected(Request $request)
    {
        if (empty($request->delete_ids)) {
            toastr()->error(__('You have not selected any file'));
            return back();
        }
        try {
            $fileEntriesIds = explode(',', $request->delete_ids);
            $totalDelete = 0;
            foreach ($fileEntriesIds as $fileEntryId) {
                $fileEntry = FileEntry::where('id', $fileEntryId)->userEntry()->notExpired()->with('storageProvider')->first();
                if (!is_null($fileEntry)) {
                    $handler = $fileEntry->storageProvider->handler;
                    $handler::delete($fileEntry->path);
                    $fileEntry->delete();
                    $totalDelete += 1;
                }
            }
            if ($totalDelete != 0) {
                $countFiles = ($totalDelete > 1) ? $totalDelete . ' ' . __('Files') : $totalDelete . ' ' . __('File');
                toastr()->success($countFiles . ' ' . __('deleted successfully'));
                return back();
            } else {
                toastr()->info(__('No files have been deleted'));
                return back();
            }
        } catch (\Exception$e) {
            toastr()->error($e->getMessage());
            return back();
        }
    }
}
