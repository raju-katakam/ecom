<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Orders fetched successfully',
            'data' => $orders
        ], 200);
    }
}
