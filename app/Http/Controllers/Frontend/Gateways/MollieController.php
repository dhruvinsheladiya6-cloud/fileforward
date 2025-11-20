<?php

namespace App\Http\Controllers\Frontend\Gateways;

use App\Http\Controllers\Frontend\User\CheckoutController;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Mollie\Laravel\Facades\Mollie;
use Vironeer\Addons\App\Http\Controllers\Controller;

class MollieController extends Controller
{

    public static function process($trx)
    {
        if ($trx->status != 0) {
            $data['error'] = true;
            $data['msg'] = lang('Invalid or expired transaction', 'checkout');
            return json_encode($data);
        }
        if ($trx->plan->interval == 1) {
            $planInterval = '(Monthly)';
        } elseif ($trx->plan->interval == 2) {
            $planInterval = '(Yearly)';
        } elseif ($trx->plan->interval == 3) {
            $planInterval = '(Lifetime)';
        }
        config(['mollie.key' => trim(paymentGateway('mollie')->credentials->api_key)]);
        $paymentName = "Payment for subscription " . $trx->plan->name . " Plan " . $planInterval;
        $gatewayFees = ($trx->total_price * paymentGateway('mollie')->fees) / 100;
        $totalPrice = priceFormt(($trx->total_price + $gatewayFees));
        $payment = Mollie::api()->payments->create([
            "amount" => [
                "currency" => currencyCode(),
                "value" => $totalPrice,
            ],
            "description" => $paymentName,
            "redirectUrl" => route('ipn.mollie') . '?checkoutId=' . $trx->checkout_id,
            "metadata" => [
                "order_id" => $trx->transaction_id,
            ],
        ]);
        try {
            $payment = Mollie::api()->payments()->get($payment->id);
            $trx->update(['fees_price' => $gatewayFees, 'payment_id' => $payment->id]);
            $data['error'] = false;
            $data['redirectUrl'] = $payment->getCheckoutUrl();
            return json_encode($data);
        } catch (\Exception$e) {
            $data['error'] = true;
            $data['msg'] = $e->getMessage();
            return json_encode($data);
        }
    }

    public function ipn(Request $request)
    {
        $checkoutId = $request->checkoutId;
        try {
            $trx = Transaction::where([['checkout_id', $checkoutId], ['payment_id', '!=', null], ['status', 1]])->first();
            if (is_null($trx)) {
                return redirect()->route('user.subscription');
            }
            config(['mollie.key' => trim(paymentGateway('mollie')->credentials->api_key)]);
            $payment = Mollie::api()->payments()->get($trx->payment_id);
            if ($payment->metadata->order_id != $trx->transaction_id) {
                throw new Exception(lang('Invalid or expired transaction', 'checkout'));
            }
            if ($payment->status == "paid") {
                $total = ($trx->total_price + $trx->fees_price);
                $payment_gateway_id = paymentGateway('mollie')->id;
                $payment_id = $payment->id;
                $updateTrx = $trx->update([
                    'total_price' => $total,
                    'payment_gateway_id' => $payment_gateway_id,
                    'payment_id' => $payment_id,
                    'status' => 2,
                ]);
                if ($updateTrx) {
                    CheckoutController::updateSubscription($trx);
                    toastr()->success(lang('Payment made successfully', 'checkout'));
                    return redirect()->route('user.subscription');
                }
            } else {
                throw new Exception(lang('Payment failed', 'checkout'));
            }
        } catch (\Exception$e) {
            toastr()->error($e->getMessage());
            return redirect()->route('home');
        }
    }
}
