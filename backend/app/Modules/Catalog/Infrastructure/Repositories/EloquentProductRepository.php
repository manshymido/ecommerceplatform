<?php

namespace App\Modules\Catalog\Infrastructure\Repositories;

use App\Modules\Catalog\Domain\Product;
use App\Modules\Catalog\Domain\ProductRepository as ProductRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Models\Product as ProductModel;
use Illuminate\Support\Facades\Cache;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findById(int $id): ?Product
    {
        $model = ProductModel::with(ProductModel::defaultEagerLoads())->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySlug(string $slug): ?Product
    {
        $cacheKey = "product:slug:{$slug}";

        $model = Cache::remember($cacheKey, 3600, function () use ($slug) {
            return ProductModel::with(ProductModel::defaultEagerLoads())
                ->where('slug', $slug)
                ->where('status', 'published')
                ->first();
        });

        return $model ? $this->toDomain($model) : null;
    }

    public function findPublished(array $filters = []): array
    {
        $cacheKey = 'products:published:'.md5(serialize($filters));

        $models = Cache::remember($cacheKey, 1800, function () use ($filters) {
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

            return $query->get();
        });

        return $models->map(fn ($model) => $this->toDomain($model))->toArray();
    }

    public function save(Product $product): Product
    {
        $model = $product->id
            ? ProductModel::findOrFail($product->id)
            : new ProductModel();

        $model->fill([
            'slug' => $product->slug,
            'name' => $product->name,
            'description' => $product->description,
            'brand_id' => $product->brandId,
            'status' => $product->status,
            'main_image_url' => $product->mainImageUrl,
            'seo_title' => $product->seoTitle,
            'seo_description' => $product->seoDescription,
        ]);

        $model->save();

        CatalogCache::forgetProduct($product->slug, $model->id);

        return $this->toDomain($model);
    }

    public function delete(int $id): bool
    {
        $model = ProductModel::findOrFail($id);
        $deleted = $model->delete();

        if ($deleted) {
            CatalogCache::forgetProduct($model->slug, $id);
        }

        return $deleted;
    }

    private function toDomain(ProductModel $model): Product
    {
        return new Product(
            id: $model->id,
            slug: $model->slug,
            name: $model->name,
            description: $model->description,
            brandId: $model->brand_id,
            status: $model->status,
            mainImageUrl: $model->main_image_url,
            seoTitle: $model->seo_title,
            seoDescription: $model->seo_description,
        );
    }
}
