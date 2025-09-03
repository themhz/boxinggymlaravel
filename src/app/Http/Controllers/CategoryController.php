<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // GET /api/categories
    public function index(): JsonResponse
    {
        $items = Category::orderBy('name')->get();

        return response()->json([
            'result' => 'success',
            'data'   => $items,
        ]);
    }

    // GET /api/categories/{category}
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'result' => 'success',
            'data'   => $category,
        ]);
    }

    // POST /api/categories   (admin)
    public function store(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => 'nullable|string|max:120|unique:categories,slug',
            'description' => 'nullable|string|max:255',
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);
        if (Category::where('slug', $slug)->exists()) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Slug already exists.',
            ], 422);
        }

        $category = Category::create([
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] ?? null,
        ]);

        return response()->json([
            'result'  => 'success',
            'message' => 'Category created',
            'data'    => $category,
        ], 201);
    }

    // PUT/PATCH /api/categories/{category}   (admin)
    public function update(Request $request, Category $category): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:100',
            'slug'        => 'sometimes|nullable|string|max:120|unique:categories,slug,'.$category->id,
            'description' => 'sometimes|nullable|string|max:255',
        ]);

        if (array_key_exists('name', $data) && !array_key_exists('slug', $data)) {
            // keep existing slug unless user sends a new one
        }

        $category->fill($data)->save();

        return response()->json([
            'result'  => 'success',
            'message' => 'Category updated',
            'data'    => $category->fresh(),
        ]);
    }

    // DELETE /api/categories/{category}   (admin)
    public function destroy(Request $request, Category $category): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if ($category->articles()->exists()) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Cannot delete a category that has articles.',
            ], 409);
        }

        $category->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Category deleted',
        ]);
    }
}
