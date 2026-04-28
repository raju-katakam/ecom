<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category')->get();
        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric',

            'pimage' => 'nullable|image|mimes:jpg,jpeg,png'

        ]);

        $imageName = null;

        if ($request->hasFile('pimage')) {
          $imagePath = $request->file('pimage')->store('products', 'public');
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'pimage' => $imagePath
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric',
            
        ]);

        if ($request->hasFile('pimage')) {
            // delete old image
            if ($product->pimage) {
                Storage::disk('public')->delete($product->pimage);
            }
             $request->validate([
                'pimage' => 'image|mimes:jpg,jpeg,png|max:2048'
            ]);


            $imagePath = $request->file('pimage')->store('products', 'public');
            $product->pimage = $imagePath;
        }

        $product->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
        ]);

        return response()->json(['message' =>'product updated successfully',
        'data' => $product]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        if ($product->pimage && file_exists(public_path('products/' . $product->pimage))) {
            unlink(public_path('products/' . $product->pimage));
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
