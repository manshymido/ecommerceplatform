<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SeedsMinimalCatalog;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalCatalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createMinimalCatalog();
    }

    public function test_products_list_returns_published_products(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('data.0.name', 'Test Product')
            ->assertJsonPath('data.0.slug', 'test-product');
    }

    public function test_product_by_slug_returns_product(): void
    {
        $response = $this->getJson('/api/products/test-product');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'slug', 'name', 'variants']])
            ->assertJsonPath('data.slug', 'test-product');
    }

    public function test_product_by_slug_returns_404_when_not_found(): void
    {
        $response = $this->getJson('/api/products/non-existent-slug');

        $response->assertStatus(404);
    }

    public function test_categories_list_returns_categories(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.0.slug', 'electronics');
    }

    public function test_category_by_slug_returns_category(): void
    {
        $response = $this->getJson('/api/categories/electronics');

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'electronics');
    }

    public function test_brands_list_returns_brands(): void
    {
        $response = $this->getJson('/api/brands');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.0.slug', 'testbrand');
    }
}
