<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = Cart::with('product')
            ->where('user_id', Auth::id())
            ->get();

        // calculate totals
        $total = $cartItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        return response()->json([
            'status' => true,
            'message' => 'Cart fetched successfully',
            // 'total_items' => $cartItems->count(),
            'total_price' => $total,
            'data' => $cartItems
        ], 200);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();

        $product = Product::findOrFail($request->product_id);


        $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $product->id)
                        ->first();

        if ($cartItem) {
            // update quantity
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            // create new
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price
            ]);
        }

        return response()->json([
            'message' => 'Product added to cart',
            'cart' => $cartItem
        ], 200);
    }

    public function updateQuantity(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'quantity' => 'required|integer|min:0'
        ]);

        $cart = Cart::where('id', $request->cart_id)
                    ->where('user_id', Auth::id())
                    ->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        }

        if ($request->quantity == 0) {
            $cart->delete();

            return response()->json([
                'message' => 'Item removed from cart'
            ]);
        }

        // // Optional: stock check
        // if ($request->quantity > $cart->product->stock) {
        //     return response()->json([
        //         'message' => 'Requested quantity not available'
        //     ], 400);
        // }

        $cart->quantity = $request->quantity;
        $cart->save();

        return response()->json([
            'message' => 'Cart updated successfully',
            'cart' => $cart
        ]);
    }

    public function removeFromCart($id)
    {
        $cart = Cart::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'message' => 'Item removed from cart'
        ], 200);
    }
}
