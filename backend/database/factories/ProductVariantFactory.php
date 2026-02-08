<?php

namespace Database\Factories;

use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Catalog\Infrastructure\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $sku = 'SKU-' . strtoupper(fake()->unique()->regexify('[A-Z0-9]{10}'));
        return [
            'product_id' => Product::factory(),
            'sku' => $sku,
            'name' => fake()->words(2, true),
            'attributes' => ['variant' => fake()->word()],
            'is_default' => true,
        ];
    }
}
