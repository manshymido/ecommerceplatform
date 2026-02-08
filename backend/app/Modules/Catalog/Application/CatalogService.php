<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Product as ProductModel;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Catalog service for managing products, categories, and brands.
 *
 * Uses caching for performance optimization on frequently accessed catalog data.
 */
class CatalogService extends BaseService
{
    protected string $cachePrefix = 'catalog';

    protected int $cacheTtl = 3600;

    private const SEARCH_SUGGESTION_LIMIT = 10;

    public function __construct()
    {
    }

    /**
     * Get query builder for published products with optional filters.
     *
     * @param array{search?: string, category_id?: int, brand_id?: int} $filters
     */
    public function getPublishedProducts(array $filters = []): Builder
    {
        $query = ProductModel::with(array_merge(ProductModel::defaultEagerLoadsForList(), ['variants.prices']))
            ->where('status', 'published');

        $query = $this->applySearchFilter($query, $filters);
        $query = $this->applyCategoryFilter($query, $filters);
        $query = $this->applyBrandFilter($query, $filters);

        return $query;
    }

    /**
     * Apply search filter to product query.
     */
    private function applySearchFilter(Builder $query, array $filters): Builder
    {
        if (!isset($filters['search'])) {
            return $query;
        }

        $term = trim($filters['search']);
        if ($term === '') {
            return $query;
        }

        return $query
            ->where('name', 'like', '%' . $term . '%')
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END ASC', [$term . '%'])
            ->orderBy('name');
    }

    /**
     * Apply category filter to product query.
     */
    private function applyCategoryFilter(Builder $query, array $filters): Builder
    {
        if (!isset($filters['category_id'])) {
            return $query;
        }

        return $query->whereHas('categories', function (Builder $q) use ($filters): void {
            $q->where('categories.id', $filters['category_id']);
        });
    }

    /**
     * Apply brand filter to product query.
     */
    private function applyBrandFilter(Builder $query, array $filters): Builder
    {
        if (!isset($filters['brand_id'])) {
            return $query;
        }

        return $query->where('brand_id', $filters['brand_id']);
    }

    /**
     * Get lightweight product name suggestions for search autocomplete.
     *
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    public function getSearchSuggestions(string $query, int $limit = self::SEARCH_SUGGESTION_LIMIT): array
    {
        $term = trim($query);

        if ($term === '') {
            return [];
        }

        return $this->remember(
            "suggestions:{$term}:{$limit}",
            fn () => $this->fetchSearchSuggestions($term, $limit),
            300 // 5 minute cache for suggestions
        );
    }

    /**
     * Fetch search suggestions from database.
     *
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    private function fetchSearchSuggestions(string $term, int $limit): array
    {
        return ProductModel::query()
            ->where('status', 'published')
            ->where('name', 'like', '%' . $term . '%')
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END ASC', [$term . '%'])
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'slug'])
            ->map(fn (ProductModel $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * Get product Eloquent model by slug for API display.
     *
     * Uses caching for performance optimization.
     */
    public function getProductModelBySlug(string $slug): ?ProductModel
    {
        return $this->remember(
            "product:slug:{$slug}",
            fn () => ProductModel::with(ProductModel::defaultEagerLoads())
                ->where('slug', $slug)
                ->where('status', 'published')
                ->first()
        );
    }

    /**
     * Get all top-level categories with their children.
     */
    public function getAllCategories(): Collection
    {
        return $this->remember(
            'categories:all',
            fn () => Category::with('children')
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get()
        );
    }

    /**
     * Get category by slug with children and published products.
     */
    public function getCategoryBySlug(string $slug): ?Category
    {
        return $this->remember(
            "category:slug:{$slug}",
            fn () => Category::with([
                'children',
                'products' => fn (Builder $query) => $query->where('status', 'published'),
            ])
                ->where('slug', $slug)
                ->first()
        );
    }

    /**
     * Get all brands with product counts.
     */
    public function getAllBrands(): Collection
    {
        return $this->remember(
            'brands:all',
            fn () => Brand::withCount('products')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Get the price for a product variant in the specified currency.
     *
     * Returns the price amount for the given currency, or falls back to default
     * currency if not found. Throws exception if no price exists.
     *
     * @param int $productVariantId
     * @param string $currency
     * @return float
     *
     * @throws \App\Exceptions\ResourceNotFoundException
     */
    public function getVariantPrice(int $productVariantId, string $currency = 'USD'): float
    {
        return $this->remember(
            "variant_price:{$productVariantId}:{$currency}",
            function () use ($productVariantId, $currency): float {
                return $this->fetchVariantPrice($productVariantId, $currency);
            },
            1800 // 30 minutes cache for prices
        );
    }

    /**
     * Fetch variant price from database.
     */
    private function fetchVariantPrice(int $productVariantId, string $currency): float
    {
        $price = \App\Modules\Catalog\Infrastructure\Models\ProductPrice::query()
            ->where('product_variant_id', $productVariantId)
            ->where('currency', $currency)
            ->first();

        if ($price) {
            return (float) $price->amount;
        }

        // Fallback to any available price for this variant
        $fallbackPrice = \App\Modules\Catalog\Infrastructure\Models\ProductPrice::query()
            ->where('product_variant_id', $productVariantId)
            ->first();

        if ($fallbackPrice) {
            return (float) $fallbackPrice->amount;
        }

        throw new \App\Exceptions\ResourceNotFoundException(
            "No price found for variant ID {$productVariantId}"
        );
    }

    /**
     * Check if a product variant exists and is available.
     */
    public function variantExists(int $productVariantId): bool
    {
        return $this->remember(
            "variant_exists:{$productVariantId}",
            fn () => \App\Modules\Catalog\Infrastructure\Models\ProductVariant::query()
                ->where('id', $productVariantId)
                ->whereHas('product', fn ($q) => $q->where('status', 'published'))
                ->exists(),
            600 // 10 minutes cache
        );
    }

    /**
     * Clear all catalog-related caches.
     */
    public function clearCache(): void
    {
        parent::clearCache();

        // Also clear specific keys that might have been set with dynamic values
        $this->forgetCache('categories:all');
        $this->forgetCache('brands:all');
    }
}
