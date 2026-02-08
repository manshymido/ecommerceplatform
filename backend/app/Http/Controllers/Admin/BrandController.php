<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;

/**
 * Admin controller for managing product brands.
 *
 * Handles CRUD operations with cache invalidation.
 */
class BrandController extends ApiBaseController
{
    /**
     * List all brands with product counts.
     */
    public function index(): JsonResponse
    {
        $brands = Brand::withCount('products')
            ->orderBy('name')
            ->get();

        return $this->collection(BrandResource::collection($brands));
    }

    /**
     * Create a new brand.
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        return $this->tryAction(
            function () use ($request): Brand {
                $brand = Brand::create($request->validated());
                CatalogCache::forgetBrands();

                return $brand;
            },
            fn (Brand $brand) => $this->created(new BrandResource($brand))
        );
    }

    /**
     * Display a single brand with products.
     *
     * @throws ResourceNotFoundException
     */
    public function show(string $id): JsonResponse
    {
        $brand = $this->findBrandOrFail($id, ['products']);

        return $this->data(new BrandResource($brand));
    }

    /**
     * Update an existing brand.
     */
    public function update(UpdateBrandRequest $request, string $id): JsonResponse
    {
        return $this->tryAction(
            function () use ($request, $id): Brand {
                $brand = Brand::findOrFail($id);
                $brand->update($request->validated());
                CatalogCache::forgetBrands();

                return $brand;
            },
            fn (Brand $brand) => $this->data(new BrandResource($brand))
        );
    }

    /**
     * Delete a brand.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->tryAction(
            function () use ($id): bool {
                $brand = Brand::findOrFail($id);
                $deleted = $brand->delete();
                CatalogCache::forgetBrands();

                return $deleted;
            },
            fn () => $this->deleted('Brand')
        );
    }

    /**
     * Find brand with eager loading or throw not found exception.
     *
     * @param array<string> $with
     * @throws ResourceNotFoundException
     */
    private function findBrandOrFail(string $id, array $with = []): Brand
    {
        $brand = Brand::with($with)->find($id);

        if (!$brand) {
            throw new ResourceNotFoundException(ApiMessages::BRAND_NOT_FOUND);
        }

        return $brand;
    }
}
