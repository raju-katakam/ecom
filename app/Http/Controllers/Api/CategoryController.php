<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Category::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'catname' => 'required',
            'catimage' => 'required|image|mimes:jpg,jpeg,png'
        ]);
        $imagePath = null;
        if ($request->hasFile('catimage')) {
            $imagePath = $request->file('catimage')->store('categories', 'public');
        }
        $category = Category::create([
            'catname' => $request->catname,
            'catimage' => $imagePath
        ]);
        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'catname' => 'required',
            'catimage' => 'nullable|image|mimes:jpg,jpeg,png'
        ]);

        if ($request->hasFile('catimage')) {
            // delete old image
            if ($category->catimage) {
                Storage::disk('public')->delete($category->catimage);
            }

            $imagePath = $request->file('catimage')->store('categories', 'public');
            $category->catimage = $imagePath;
        }
        
        $category->catname = $request->catname;
        $category->save();

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
