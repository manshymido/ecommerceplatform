<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Inventory\Infrastructure\Models\StockItem;
use App\Modules\Inventory\Infrastructure\Models\StockMovement;
use App\Modules\Inventory\Infrastructure\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesAdminUser;
use Tests\TestCase;

class AdminInventoryApiTest extends TestCase
{
    use CreatesAdminUser;
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
    }

    public function test_admin_warehouses_index_requires_auth(): void
    {
        $response = $this->getJson('/api/admin/warehouses');

        $response->assertStatus(401);
    }

    public function test_admin_warehouses_index_returns_warehouses(): void
    {
        Warehouse::create(['name' => 'WH1', 'code' => 'WH1', 'city' => 'NYC']);

        $this->actingAsAdmin();
        $response = $this->getJson('/api/admin/warehouses');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.0.code', 'WH1');
    }

    public function test_admin_can_create_warehouse(): void
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/admin/warehouses', [
            'name' => 'New Warehouse',
            'code' => 'NW01',
            'country_code' => 'US',
            'city' => 'Chicago',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'NW01');
        $this->assertDatabaseHas('warehouses', ['code' => 'NW01']);
    }

    public function test_admin_can_update_warehouse(): void
    {
        $wh = Warehouse::create(['name' => 'Old', 'code' => 'OLD']);

        $this->actingAsAdmin();
        $response = $this->putJson('/api/admin/warehouses/' . $wh->id, [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_admin_can_delete_warehouse(): void
    {
        $wh = Warehouse::create(['name' => 'To Delete', 'code' => 'DEL']);

        $this->actingAsAdmin();
        $response = $this->deleteJson('/api/admin/warehouses/' . $wh->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('warehouses', ['id' => $wh->id]);
    }

    public function test_admin_stock_index_returns_stock_items(): void
    {
        $wh = Warehouse::create(['name' => 'W', 'code' => 'W']);
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'status' => 'published']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU1', 'name' => 'V1', 'is_default' => true]);
        StockItem::create(['product_variant_id' => $variant->id, 'warehouse_id' => $wh->id, 'quantity' => 50, 'safety_stock' => 0]);

        $this->actingAsAdmin();
        $response = $this->getJson('/api/admin/stock');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('data.0.quantity', 50);
    }

    public function test_admin_can_adjust_stock(): void
    {
        $wh = Warehouse::create(['name' => 'W', 'code' => 'W']);
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'status' => 'published']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU2', 'name' => 'V2', 'is_default' => true]);
        StockItem::create(['product_variant_id' => $variant->id, 'warehouse_id' => $wh->id, 'quantity' => 10, 'safety_stock' => 0]);

        $this->actingAsAdmin();
        $response = $this->postJson('/api/admin/stock/adjust', [
            'product_variant_id' => $variant->id,
            'warehouse_id' => $wh->id,
            'quantity_delta' => 5,
            'reason_code' => 'receipt',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.quantity', 15);
        $this->assertDatabaseHas('stock_items', ['product_variant_id' => $variant->id, 'warehouse_id' => $wh->id, 'quantity' => 15]);
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $variant->id, 'quantity' => 5, 'type' => 'in']);
    }

    public function test_admin_stock_movements_index_returns_movements(): void
    {
        $wh = Warehouse::create(['name' => 'W', 'code' => 'W']);
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'status' => 'published']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU3', 'name' => 'V3', 'is_default' => true]);
        StockMovement::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $wh->id,
            'type' => 'in',
            'quantity' => 20,
            'reason_code' => 'receipt',
            'reference_type' => 'adjustment',
            'created_at' => now(),
        ]);

        $this->actingAsAdmin();
        $response = $this->getJson('/api/admin/stock/movements');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('data.0.type', 'in')
            ->assertJsonPath('data.0.quantity', 20);
    }
}
