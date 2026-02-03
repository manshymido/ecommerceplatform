<?php

namespace App\Http\Controllers\Admin;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::withCount('products')
            ->orderBy('name')
            ->get();

        return ApiResponse::collection(BrandResource::collection($brands));
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = Brand::create($request->validated());
        CatalogCache::forgetBrands();

        return ApiResponse::data(new BrandResource($brand), 201);
    }

    public function show(string $id): JsonResponse
    {
        $brand = Brand::with('products')
            ->findOrFail($id);

        return ApiResponse::data(new BrandResource($brand));
    }

    public function update(UpdateBrandRequest $request, string $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $brand->update($request->validated());
        CatalogCache::forgetBrands();

        return ApiResponse::data(new BrandResource($brand));
    }

    public function destroy(string $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();
        CatalogCache::forgetBrands();

        return ApiResponse::deleted('Brand');
    }
}
