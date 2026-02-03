<?php

namespace Tests;

use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductPrice;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;

trait SeedsMinimalCatalog
{
    /**
     * Create minimal catalog (brand, category, product with variant and price).
     * Keys: brand, category, product, variant (each optional array of attributes to merge).
     *
     * @param  array{brand?: array, category?: array, product?: array, variant?: array}  $overrides
     * @return array{brand: Brand, category: Category, product: Product, variant: ProductVariant, price: ProductPrice}
     */
    protected function createMinimalCatalog(array $overrides = []): array
    {
        $brand = Brand::create(array_merge(['name' => 'TestBrand', 'slug' => 'testbrand'], $overrides['brand'] ?? []));
        $category = Category::create(array_merge([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'position' => 1,
        ], $overrides['category'] ?? []));
        $product = Product::create(array_merge([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Description',
            'brand_id' => $brand->id,
            'status' => 'published',
        ], $overrides['product'] ?? []));
        $product->categories()->attach($category->id);
        $variant = ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU-1',
            'name' => 'Variant 1',
            'attributes' => ['size' => 'M'],
            'is_default' => true,
        ], $overrides['variant'] ?? []));
        $price = ProductPrice::create([
            'product_variant_id' => $variant->id,
            'currency' => 'USD',
            'amount' => 99.99,
        ]);

        return compact('brand', 'category', 'product', 'variant', 'price');
    }
}
