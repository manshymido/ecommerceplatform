<?php

namespace App\Http\Controllers\Admin;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Modules\Catalog\Infrastructure\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::with('parent', 'children')
            ->orderBy('position')
            ->get();

        return ApiResponse::collection(CategoryResource::collection($categories));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());
        CatalogCache::forgetCategory(null);

        return ApiResponse::data(new CategoryResource($category->load('parent')), 201);
    }

    public function show(string $id): JsonResponse
    {
        $category = Category::with(['parent', 'children', 'products'])
            ->findOrFail($id);

        return ApiResponse::data(new CategoryResource($category));
    }

    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->update($request->validated());

        return ApiResponse::data(new CategoryResource($category->load('parent')));
    }

    public function destroy(string $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->delete();
        CatalogCache::forgetCategory($category->slug ?? null);

        return ApiResponse::deleted('Category');
    }
}
