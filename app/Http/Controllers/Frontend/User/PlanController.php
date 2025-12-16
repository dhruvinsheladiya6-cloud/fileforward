<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UploadSettings;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $uploadMode = UploadSettings::getUploadMode();

        if (!userAuthInfo()->subscription && $request->st != "subscribe") {
            return redirect(route('user.plans') . '?st=subscribe');
        }
        if (userAuthInfo()->subscription && $request->st) {
            return redirect()->route('user.plans');
        }
        return view('frontend.user.plans.index', ['uploadMode' => $uploadMode]);
    }
}
