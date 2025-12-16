<?php
// app/Http/Controllers/Frontend/User/ShareOpenController.php
namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\FileEntryShare;

class ShareOpenController extends Controller
{
    public function open($token)
    {
        $share = FileEntryShare::with('file')->where('token', $token)->firstOrFail();

        if ($share->revoked_at) abort(403, 'This share has been revoked.');
        if ($share->expires_at && now()->greaterThan($share->expires_at)) abort(403, 'This share has expired.');

        if (is_null($share->accepted_at)) { $share->accepted_at = now(); $share->save(); }

        $file = $share->file;
        if (!$file) abort(404);

        if ($file->type === 'folder') {
            // NEW: browse shared folder (keeps structure)
            return redirect()->route('user.shared.browse', $share->token);
        }
        return redirect()->route('file.preview', $file->shared_id);
    }
}
