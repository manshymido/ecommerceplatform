<?php

namespace Database\Factories;

use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Catalog\Infrastructure\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);
        $slug = Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 999999);
        return [
            'name' => $name,
            'slug' => $slug,
            'description' => fake()->paragraphs(2, true),
            'brand_id' => Brand::query()->inRandomOrder()->first()?->id ?? 1,
            'status' => 'published',
            'main_image_url' => null,
            'seo_title' => null,
            'seo_description' => null,
        ];
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => ['brand_id' => $brand->id]);
    }
}
