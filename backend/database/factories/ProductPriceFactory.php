<?php

namespace Database\Factories;

use App\Modules\Catalog\Infrastructure\Models\ProductPrice;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Catalog\Infrastructure\Models\ProductPrice>
 */
class ProductPriceFactory extends Factory
{
    protected $model = ProductPrice::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 5, 999);
        return [
            'product_variant_id' => ProductVariant::factory(),
            'currency' => 'USD',
            'amount' => $amount,
            'compare_at_amount' => fake()->optional(0.3)->randomFloat(2, $amount, $amount * 1.2),
            'channel' => null,
            'valid_from' => null,
            'valid_to' => null,
        ];
    }
}
