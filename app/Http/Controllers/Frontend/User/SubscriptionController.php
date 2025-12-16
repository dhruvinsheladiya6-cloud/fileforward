<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\UploadSettings;

class SubscriptionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::where('user_id', userAuthInfo()->id)
            ->whereIn('status', [2, 3])
            ->with('plan')
            ->orderbyDesc('id')
            ->paginate(20);
        $plans = Plan::all()->count();
        $uploadMode = UploadSettings::getUploadMode();
        return view('frontend.user.subscription.index', ['transactions' => $transactions, 'plans' => $plans, 'uploadMode' => $uploadMode,]);
    }

    public function transaction($transaction_id)
    {
        $transaction = Transaction::where([['transaction_id', $transaction_id], ['user_id', userAuthInfo()->id]])
            ->whereIn('status', [2, 3])
            ->with(['plan', 'gateway', 'coupon'])
            ->firstOrFail();
        return view('frontend.user.subscription.transaction', ['transaction' => $transaction]);
    }
}
