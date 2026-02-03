<?php

namespace Database\Seeders;

use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductPrice;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Brands
        $brand1 = Brand::create(['name' => 'TechBrand', 'slug' => 'techbrand']);
        $brand2 = Brand::create(['name' => 'FashionCo', 'slug' => 'fashionco']);

        // Create Categories
        $electronics = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'position' => 1]);
        $clothing = Category::create(['name' => 'Clothing', 'slug' => 'clothing', 'position' => 2]);
        $phones = Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $electronics->id, 'position' => 1]);
        $shirts = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'parent_id' => $clothing->id, 'position' => 1]);

        // Create Products
        $product1 = Product::create([
            'name' => 'Smartphone Pro',
            'slug' => 'smartphone-pro',
            'description' => 'Latest smartphone with advanced features',
            'brand_id' => $brand1->id,
            'status' => 'published',
            'seo_title' => 'Smartphone Pro - Latest Tech',
            'seo_description' => 'Buy the latest Smartphone Pro with advanced features',
        ]);
        $product1->categories()->attach([$electronics->id, $phones->id]);

        $product2 = Product::create([
            'name' => 'Classic T-Shirt',
            'slug' => 'classic-t-shirt',
            'description' => 'Comfortable cotton t-shirt',
            'brand_id' => $brand2->id,
            'status' => 'published',
        ]);
        $product2->categories()->attach([$clothing->id, $shirts->id]);

        // Create Variants for Product 1
        $variant1 = ProductVariant::create([
            'product_id' => $product1->id,
            'sku' => 'SP-PRO-128-BLK',
            'name' => '128GB Black',
            'attributes' => ['storage' => '128GB', 'color' => 'Black'],
            'is_default' => true,
        ]);

        $variant2 = ProductVariant::create([
            'product_id' => $product1->id,
            'sku' => 'SP-PRO-256-BLK',
            'name' => '256GB Black',
            'attributes' => ['storage' => '256GB', 'color' => 'Black'],
            'is_default' => false,
        ]);

        // Create Variants for Product 2
        $variant3 = ProductVariant::create([
            'product_id' => $product2->id,
            'sku' => 'TS-CLS-S',
            'name' => 'Small',
            'attributes' => ['size' => 'S'],
            'is_default' => true,
        ]);

        $variant4 = ProductVariant::create([
            'product_id' => $product2->id,
            'sku' => 'TS-CLS-M',
            'name' => 'Medium',
            'attributes' => ['size' => 'M'],
            'is_default' => false,
        ]);

        // Create Prices
        ProductPrice::create([
            'product_variant_id' => $variant1->id,
            'currency' => 'USD',
            'amount' => 699.99,
            'compare_at_amount' => 799.99,
        ]);

        ProductPrice::create([
            'product_variant_id' => $variant2->id,
            'currency' => 'USD',
            'amount' => 799.99,
            'compare_at_amount' => 899.99,
        ]);

        ProductPrice::create([
            'product_variant_id' => $variant3->id,
            'currency' => 'USD',
            'amount' => 29.99,
        ]);

        ProductPrice::create([
            'product_variant_id' => $variant4->id,
            'currency' => 'USD',
            'amount' => 29.99,
        ]);

        $this->command->info('Catalog seeded successfully!');
        $this->command->info('Created: 2 brands, 4 categories, 2 products, 4 variants, 4 prices');
    }
}
