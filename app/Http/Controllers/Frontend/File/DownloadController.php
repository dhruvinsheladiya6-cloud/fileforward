<?php

namespace App\Http\Controllers\Frontend\File;

use App\Http\Controllers\Controller;
use App\Http\Methods\ReCaptchaValidation;
use App\Models\BlogArticle;
use App\Models\DownloadLink;
use App\Models\FileEntry;
use App\Models\FileEntryShare;
use App\Models\FileReport;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Session;
use Validator;

class DownloadController extends Controller
{
    public function index($shared_id)
    {
        $fileEntry = FileEntry::where('shared_id', $shared_id)
            ->where('access_status', 1)
            ->notExpired()
            ->firstOrFail();

        if (!is_null($fileEntry->password)) {
            if (!Session::has(filePasswordSession($fileEntry->shared_id))) {
                return redirect(route('file.password', $fileEntry->shared_id) . '?source=download');
            } else {
                $password = decrypt(Session::get(filePasswordSession($fileEntry->shared_id)));
                if ($password != $fileEntry->password) {
                    return redirect(route('file.password', $fileEntry->shared_id) . '?source=download');
                }
            }
        }

        $downloadLink = static::generateDownloadLink($fileEntry);
        $blogArticles = BlogArticle::limit(6)->orderbyDesc('id')->get();
        $fileEntry->increment('views');
        return view('frontend.file.download', [
            'fileEntry' => $fileEntry,
            'downloadLink' => $downloadLink,
            'blogArticles' => $blogArticles
        ]);
    }

    public function createDownloadLink(Request $request, $shared_id)
    {
        $fileEntry = FileEntry::where('shared_id', $shared_id)->notExpired()->first();
        if (is_null($fileEntry) || !static::accessCheck($fileEntry)) {
            return jsonError(lang('File not found, missing or expired', 'download page'));
        }
        if (!is_null($fileEntry->password)) {
            if (!Session::has(filePasswordSession($fileEntry->shared_id))) {
                return jsonError(lang('Unauthorized access', 'alerts'));
            } else {
                $password = decrypt(Session::get(filePasswordSession($fileEntry->shared_id)));
                if ($password != $fileEntry->password) {
                    return jsonError(lang('Unauthorized access', 'alerts'));
                }
            }
        }
        $downloadLink = static::generateDownloadLink($fileEntry);
        return response()->json([
            'type' => 'success',
            'download_link' => route('file.download.approval', [$fileEntry->shared_id, hashid($downloadLink->id), $fileEntry->name]),
        ]);
    }

    public function download($shared_id, $id, $filename)
    {
        $fileEntry = FileEntry::where('shared_id', $shared_id)->notExpired()->firstOrFail();
        abort_if(!static::accessCheck($fileEntry), 404);
        $downloadLink = DownloadLink::where([['id', unhashid($id)], ['file_entry_id', $fileEntry->id]])->notExpired()->first();
        abort_if(is_null($downloadLink), 404);
        try {
            $handler = $fileEntry->storageProvider->handler;
            $fileEntry->increment('downloads');
            $download = $handler::download($fileEntry);
            if ($fileEntry->storageProvider->symbol == "local") {
                return $download;
            } else {
                return redirect($download);
            }
        } catch (Exception $e) {
            toastr()->error(lang('There was a problem while trying to download the file', 'download page'));
            return redirect()->route('file.download', $fileEntry->shared_id);
        }
    }

    public function reportFile(Request $request, $shared_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'reason' => ['required', 'integer', 'min:0', 'max:4'],
            'details' => ['required', 'string', 'max:600'],
        ] + ReCaptchaValidation::validate());
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back();
        }
        $fileEntry = FileEntry::where('shared_id', $shared_id)->notExpired()->first();
        if (is_null($fileEntry) || !static::accessCheck($fileEntry)) {
            toastr()->error(lang('File not found, missing or expired', 'download page'));
            return back();
        }
        if (Auth::user()) {
            if ($fileEntry->user_id == Auth::user()->id) {
                toastr()->error(lang('File not found, missing or expired', 'download page'));
                return back();
            }
        }
        if (!array_key_exists($request->reason, reportReasons())) {
            toastr()->error(lang('Invalid report reason', 'download page'));
            return back();
        }
        $alreadyReported = FileReport::where([['file_entry_id', $fileEntry->id], ['ip', vIpInfo()->ip]])
            ->OrWhere([['file_entry_id', $fileEntry->id], ['email', $request->email]])
            ->first();
        if (!is_null($alreadyReported)) {
            toastr()->error(lang('You have already reported this file', 'download page'));
            return back();
        }
        $createFileReport = FileReport::create([
            'file_entry_id' => $fileEntry->id,
            'ip' => vIpInfo()->ip,
            'name' => $request->name,
            'email' => $request->email,
            'reason' => $request->reason,
            'details' => $request->details,
        ]);
        if ($createFileReport) {
            $title = __('New report #') . $fileEntry->shared_id;
            $image = asset('images/icons/report.png');
            $link = route('superadmin.reports.view', $createFileReport->id);
            adminNotify($title, $image, $link);
            toastr()->success(lang('Your report has been sent successfully, we will review and take the necessary action', 'download page'));
            return back();
        }
    }

    public static function generateDownloadLink($fileEntry)
    {
        $downloadLink = DownloadLink::where('file_entry_id', $fileEntry->id)->notExpired()->first();
        if (is_null($downloadLink)) {
            $downloadLinks = DownloadLink::where('file_entry_id', $fileEntry->id)->hasExpired()->get();
            foreach ($downloadLinks as $downloadLink) {
                $downloadLink->delete();
            }
            $downloadLink = DownloadLink::create(['file_entry_id' => $fileEntry->id, 'expiry_at' => Carbon::now()->addMinutes(settings('download_link_validity_time'))]);
        }
        return $downloadLink;
    }

public static function accessCheck($fileEntry): bool
{
    $user = Auth::user();
    if (!$user) {
        return false;
    }

    // 1️⃣ Owner can always access
    if ((int) $fileEntry->user_id === (int) $user->id) {
        return true;
    }

    // 2️⃣ Direct file share to user
    $directShare = FileEntryShare::where('file_entry_id', $fileEntry->id)
        ->where('recipient_user_id', $user->id)
        ->whereNull('revoked_at')
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })
        ->exists();

    if ($directShare) {
        return true;
    }

    // 3️⃣ Indirect share — inside a shared folder the user can access
    // find all active folder shares owned by this file’s owner
    $folderShares = FileEntryShare::where('recipient_user_id', $user->id)
        ->whereNull('revoked_at')
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })
        ->get();

    foreach ($folderShares as $share) {
        $root = $share->file;
        if ($root && $root->type === 'folder') {
            // File must belong to same owner and be inside that folder
            if (
                (int) $root->user_id === (int) $fileEntry->user_id &&
                (
                    $fileEntry->id === $root->id ||
                    $fileEntry->isDescendantOf($root)
                )
            ) {
                return true;
            }
        }
    }

    // ❌ No match found
    return false;
}

}
