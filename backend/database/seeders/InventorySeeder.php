<?php

namespace Database\Seeders;

use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Inventory\Infrastructure\Models\StockItem;
use App\Modules\Inventory\Infrastructure\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $main = Warehouse::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Warehouse',
                'country_code' => 'US',
                'region' => 'CA',
                'city' => 'Los Angeles',
            ]
        );

        // Seed stock for existing variants (optional: run after CatalogSeeder)
        $variants = ProductVariant::all();
        foreach ($variants as $variant) {
            StockItem::firstOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $main->id,
                ],
                [
                    'quantity' => 100,
                    'safety_stock' => 5,
                ]
            );
        }
    }
}
