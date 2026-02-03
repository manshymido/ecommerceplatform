<?php

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\ProductRepository;
use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Product as ProductModel;
use Illuminate\Support\Facades\Cache;

class CatalogService
{
    public function __construct(
        private ProductRepository $productRepository
    ) {
    }

    public function getPublishedProducts(array $filters = [])
    {
        // For now, use Eloquent directly for pagination
        // Repository pattern can be enhanced later for pagination support
        $query = ProductModel::with(ProductModel::defaultEagerLoads())
            ->where('status', 'published');

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (isset($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }

        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        return $query;
    }

    public function getProductBySlug(string $slug)
    {
        return $this->productRepository->findBySlug($slug);
    }

    /** Returns the Eloquent model for API display (shared cache with repository). */
    public function getProductModelBySlug(string $slug): ?ProductModel
    {
        $cacheKey = "product:slug:{$slug}";

        return Cache::remember($cacheKey, 3600, function () use ($slug) {
            return ProductModel::with(ProductModel::defaultEagerLoads())
                ->where('slug', $slug)
                ->where('status', 'published')
                ->first();
        });
    }

    public function getAllCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('categories:all', 3600, function () {
            return Category::with('children')
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get();
        });
    }

    public function getCategoryBySlug(string $slug): ?Category
    {
        $cacheKey = "category:slug:{$slug}";

        return Cache::remember($cacheKey, 3600, function () use ($slug) {
            return Category::with(['children', 'products' => function ($query) {
                $query->where('status', 'published');
            }])
                ->where('slug', $slug)
                ->first();
        });
    }

    public function getAllBrands(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('brands:all', 3600, function () {
            return Brand::withCount('products')
                ->orderBy('name')
                ->get();
        });
    }
}
