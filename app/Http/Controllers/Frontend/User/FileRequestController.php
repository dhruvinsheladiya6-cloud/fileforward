<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\FileEntry;
use App\Models\FileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Validator;

class FileRequestController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_shared_id' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = Auth::user();

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

        $fileRequest = FileRequest::create([
            'user_id'   => $user->id,
            'folder_id' => $folder?->id,
            'token'     => Str::random(40),
            'title'     => $folder?->name ?? 'Upload files for '.$user->email,
        ]);

        $publicUrl = route('file-request.show', $fileRequest->token);

        return response()->json([
            'success' => true,
            'url'     => $publicUrl,
        ]);
    }
}
