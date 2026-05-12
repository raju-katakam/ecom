<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;

class PaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        $api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );

        $amount = $request->amount * 100;

        $order = $api->order->create([
            'receipt' => 'receipt_' . rand(1000, 9999),
            'amount' => $amount,
            'currency' => 'INR'
        ]);

        Payment::create([
            'user_id' => auth()->id(),
            'order_id' => $order['id'],
            'amount' => $request->amount,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $order['id'],
            'amount' => $amount,
            'key' => config('services.razorpay.key')
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );

        try {

            // Verify Signature
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            $api->utility->verifyPaymentSignature($attributes);

            // Update Payment Table
            Payment::where('order_id', $request->razorpay_order_id)
                ->update([
                    'payment_id' => $request->razorpay_payment_id,
                    'signature' => $request->razorpay_signature,
                    'status' => 'success'
                ]);

            // Logged User
            $userId = auth()->id();

            // Get Cart Products
            $carts = Cart::with('product')
                ->where('user_id', $userId)
                ->get();

            // Calculate Total
            $total = 0;

            foreach ($carts as $cart) {

                $total += $cart->product->price * $cart->quantity;
            }

            // Create Order
            $order = Order::create([

                'user_id' => $userId,

                'order_number' => 'ORD-' . time(),

                'total_amount' => $total,

                'payment_status' => 'paid',

                'order_status' => 'processing',

                'payment_method' => 'razorpay',

                'shipping_name' => $request->shipping_name,

                'shipping_email' => $request->shipping_email,

                'shipping_phone' => $request->shipping_phone,

                'shipping_address' => $request->shipping_address,
            ]);

            // Create Order Items
            foreach ($carts as $cart) {

                OrderItem::create([

                    'order_id' => $order->id,

                    'product_id' => $cart->product_id,

                    'quantity' => $cart->quantity,

                    'price' => $cart->product->price,

                    'subtotal' => $cart->product->price * $cart->quantity,
                ]);
            }

            // Clear Cart
            Cart::where('user_id', $userId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment Successful',
                'order' => $order
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

}
