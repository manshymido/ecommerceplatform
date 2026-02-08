<?php

namespace Database\Seeders;

use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductPrice;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Inventory\Infrastructure\Models\StockItem;
use App\Modules\Inventory\Infrastructure\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LargeDatasetSeeder extends Seeder
{
    public const TARGET_PRODUCTS = 10_000;

    public const CHUNK_SIZE = 500;

    public function run(): void
    {
        $mainWarehouse = Warehouse::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Warehouse',
                'country_code' => 'US',
                'region' => 'CA',
                'city' => 'Los Angeles',
            ]
        );
        $brandIds = $this->ensureBrands(50);
        $categoryIds = $this->ensureCategories(100);

        $this->command->info('Seeding ' . self::TARGET_PRODUCTS . ' products in chunks of ' . self::CHUNK_SIZE . '...');

        Product::withoutSyncingToSearch(function () use ($brandIds, $categoryIds, $mainWarehouse) {
            $created = 0;
            $chunks = (int) ceil(self::TARGET_PRODUCTS / self::CHUNK_SIZE);

            for ($chunk = 0; $chunk < $chunks; $chunk++) {
                DB::transaction(function () use ($brandIds, $categoryIds, $mainWarehouse, &$created) {
                    $limit = min(self::CHUNK_SIZE, self::TARGET_PRODUCTS - $created);
                    for ($i = 0; $i < $limit; $i++) {
                        $product = $this->createProduct($brandIds, $created + $i + 1);
                        $variant = $this->createVariant($product);
                        $this->createPrice($variant);
                        $this->attachCategories($product, $categoryIds);
                        if ($mainWarehouse) {
                            $this->ensureStockItem($variant->id, $mainWarehouse->id);
                        }
                        $created++;
                    }
                });
                $this->command->info('  ' . $created . ' / ' . self::TARGET_PRODUCTS . ' products created.');
            }
        });

        $this->command->info('Large dataset seeded: ' . self::TARGET_PRODUCTS . ' products with variants, prices, and category links.');
    }

    /**
     * @return array<int>
     */
    private function ensureBrands(int $count): array
    {
        $existing = Brand::count();
        if ($existing >= $count) {
            return Brand::pluck('id')->all();
        }
        $toCreate = $count - $existing;
        $brands = [];
        for ($i = 0; $i < $toCreate; $i++) {
            $name = fake()->unique()->company();
            $brands[] = Brand::create([
                'name' => $name,
                'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 99999),
            ]);
        }
        return Brand::pluck('id')->all();
    }

    /**
     * @param array<int> $brandIds
     */
    private function createProduct(array $brandIds, int $sequence): Product
    {
        $name = fake()->words(3, true);
        $slug = 'product-' . $sequence . '-' . Str::slug($name);

        return Product::create([
            'name' => $name,
            'slug' => $slug,
            'description' => fake()->paragraphs(2, true),
            'brand_id' => fake()->randomElement($brandIds),
            'status' => 'published',
            'main_image_url' => null,
            'seo_title' => null,
            'seo_description' => null,
        ]);
    }

    /**
     * @return array<int>
     */
    private function ensureCategories(int $count): array
    {
        $existing = Category::count();
        if ($existing >= $count) {
            return Category::pluck('id')->all();
        }
        $toCreate = $count - $existing;
        for ($i = 0; $i < $toCreate; $i++) {
            $name = fake()->words(2, true);
            Category::create([
                'parent_id' => null,
                'name' => $name,
                'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(10000, 999999),
                'position' => fake()->numberBetween(0, 100),
            ]);
        }
        return Category::pluck('id')->all();
    }

    private function createVariant(Product $product): ProductVariant
    {
        $sku = 'SKU-' . $product->id . '-' . strtoupper(bin2hex(random_bytes(3)));

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $sku,
            'name' => fake()->words(2, true),
            'attributes' => ['variant' => fake()->word()],
            'is_default' => true,
        ]);
    }

    private function createPrice(ProductVariant $variant): void
    {
        $amount = fake()->randomFloat(2, 5, 999);
        ProductPrice::create([
            'product_variant_id' => $variant->id,
            'currency' => 'USD',
            'amount' => $amount,
            'compare_at_amount' => fake()->optional(0.3)->randomFloat(2, $amount, $amount * 1.2),
            'channel' => null,
            'valid_from' => null,
            'valid_to' => null,
        ]);
    }

    /**
     * @param array<int> $categoryIds
     */
    private function attachCategories(Product $product, array $categoryIds): void
    {
        $pick = (int) fake()->numberBetween(1, 2);
        $ids = fake()->randomElements($categoryIds, min($pick, count($categoryIds)));
        $product->categories()->attach(array_unique($ids));
    }

    private function ensureStockItem(int $productVariantId, int $warehouseId): void
    {
        StockItem::firstOrCreate(
            [
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
            ],
            [
                'quantity' => fake()->numberBetween(10, 500),
                'safety_stock' => fake()->numberBetween(2, 10),
            ]
        );
    }
}
