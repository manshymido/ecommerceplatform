<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesAdminUser;
use Tests\TestCase;

class AdminCatalogApiTest extends TestCase
{
    use CreatesAdminUser;
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
    }

    public function test_admin_products_index_requires_auth(): void
    {
        $response = $this->getJson('/api/admin/products');

        $response->assertStatus(401);
    }

    public function test_admin_products_index_returns_products(): void
    {
        Product::create([
            'name' => 'Admin Product',
            'slug' => 'admin-product',
            'status' => 'draft',
        ]);

        $this->actingAsAdmin();
        $response = $this->getJson('/api/admin/products');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('data.0.name', 'Admin Product');
    }

    public function test_admin_can_create_product(): void
    {
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand']);
        $cat = Category::create(['name' => 'Cat', 'slug' => 'cat', 'position' => 1]);

        $this->actingAsAdmin();
        $response = $this->postJson('/api/admin/products', [
                'name' => 'New Product',
                'slug' => 'new-product',
                'description' => 'Desc',
                'brand_id' => $brand->id,
                'status' => 'published',
                'category_ids' => [$cat->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Product')
            ->assertJsonPath('data.slug', 'new-product');

        $this->assertDatabaseHas('products', ['slug' => 'new-product']);
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
            'status' => 'draft',
        ]);

        $this->actingAsAdmin();
        $response = $this->putJson('/api/admin/products/' . $product->id, [
                'name' => 'Updated Name',
                'status' => 'published',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $product->refresh();
        $this->assertSame('Updated Name', $product->name);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::create([
            'name' => 'To Delete',
            'slug' => 'to-delete',
            'status' => 'draft',
        ]);

        $this->actingAsAdmin();
        $response = $this->deleteJson('/api/admin/products/' . $product->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_brands_index_returns_brands(): void
    {
        Brand::create(['name' => 'B', 'slug' => 'b']);

        $this->actingAsAdmin();
        $response = $this->getJson('/api/admin/brands');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_admin_can_create_brand(): void
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/admin/brands', [
                'name' => 'New Brand',
                'slug' => 'new-brand',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'new-brand');
        $this->assertDatabaseHas('brands', ['slug' => 'new-brand']);
    }

    public function test_admin_categories_index_returns_categories(): void
    {
        Category::create(['name' => 'C', 'slug' => 'c', 'position' => 1]);

        $this->actingAsAdmin();
        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }
}
