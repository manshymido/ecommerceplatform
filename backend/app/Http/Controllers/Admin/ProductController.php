<?php

namespace App\Http\Controllers\Admin;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Modules\Catalog\Infrastructure\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(Product::defaultEagerLoadsForList());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($products, ProductResource::collection($products));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        if ($request->has('category_ids')) {
            $product->categories()->sync($request->category_ids);
        }

        return ApiResponse::data(new ProductResource($product->load(['brand', 'categories'])), 201);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with(Product::defaultEagerLoads())->findOrFail($id);

        return ApiResponse::data(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());

        if ($request->has('category_ids')) {
            $product->categories()->sync($request->category_ids);
        }

        return ApiResponse::data(new ProductResource($product->load(['brand', 'categories'])));
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return ApiResponse::deleted('Product');
    }
}
