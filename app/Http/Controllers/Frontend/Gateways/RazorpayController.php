<?php

namespace App\Http\Controllers\Frontend\Gateways;

use App\Http\Controllers\Frontend\User\CheckoutController;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Vironeer\Addons\App\Http\Controllers\Controller;

class RazorpayController extends Controller
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
        $paymentName = "Payment for subscription " . $trx->plan->name . " Plan " . $planInterval;
        $gatewayFees = ($trx->total_price * paymentGateway('razorpay')->fees) / 100;
        $totalPrice = round(($trx->total_price + $gatewayFees), 2);
        $priceIncludeFees = str_replace('.', '', ($totalPrice * 100));
        try {
            $api = new Api(paymentGateway('razorpay')->credentials->key_id, paymentGateway('razorpay')->credentials->key_secret);
            $order = $api->order->create([
                'receipt' => $trx->transaction_id,
                'amount' => $priceIncludeFees,
                'currency' => currencyCode(),
                'payment_capture' => '0',
            ]);
            $details = [
                'key' => paymentGateway('razorpay')->credentials->key_id,
                'amount' => $priceIncludeFees,
                'currency' => currencyCode(),
                'order_id' => $order['id'],
                'buttontext' => lang('Pay Now', 'checkout'),
                'name' => settings('website_name'),
                'description' => $paymentName,
                'image' => asset(settings('website_dark_logo')),
                'prefill.name' => userAuthInfo()->name,
                'prefill.email' => userAuthInfo()->email,
                'prefill.contact' => userAuthInfo()->mobile,
                'theme.color' => settings('website_primary_color'),
            ];
            $data['error'] = false;
            $data['trx'] = $trx;
            $data['details'] = $details;
            $data['view'] = "frontend.user.gateways." . paymentGateway('razorpay')->symbol;
            $trx->update(['fees_price' => $gatewayFees, 'payment_id' => $order['id']]);
            return json_encode($data);
        } catch (\Exception$e) {
            $data['error'] = true;
            $data['msg'] = $e->getMessage();
            return json_encode($data);
        }
    }

    public function ipn(Request $request)
    {
        $checkoutId = $request->checkout_id;
        $paymentId = $request->razorpay_order_id;
        try {
            $trx = Transaction::where([['checkout_id', $checkoutId], ['payment_id', $paymentId], ['status', 1]])->first();
            if (is_null($trx)) {
                return redirect()->route('user.subscription');
            }
            $signature = hash_hmac('sha256', $request->razorpay_order_id . "|" . $request->razorpay_payment_id, paymentGateway('razorpay')->credentials->key_secret);
            if ($signature == $request->razorpay_signature) {
                $total = ($trx->total_price + $trx->fees_price);
                $payment_gateway_id = paymentGateway('razorpay')->id;
                $payment_id = $request->razorpay_payment_id;
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
