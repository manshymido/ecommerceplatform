<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Inventory\Infrastructure\Models\StockItem;
use App\Modules\Inventory\Infrastructure\Models\Warehouse;
use App\Modules\Promotion\Infrastructure\Models\Coupon;
use App\Modules\Promotion\Infrastructure\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SeedsMinimalCatalog;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalCatalog;

    protected function setUp(): void
    {
        parent::setUp();
        $catalog = $this->createMinimalCatalog([
            'product' => ['name' => 'P', 'slug' => 'p'],
            'brand' => ['name' => 'B', 'slug' => 'b'],
            'category' => ['name' => 'C', 'slug' => 'c'],
            'variant' => ['sku' => 'SKU1', 'name' => 'V1'],
        ]);
        $this->seedStockForVariant($catalog['variant']->id);
        $this->seedPromotion();
    }

    protected function seedStockForVariant(int $variantId, int $qty = 100): void
    {
        $wh = Warehouse::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main', 'country_code' => 'US', 'city' => 'NYC']
        );
        StockItem::firstOrCreate(
            ['product_variant_id' => $variantId, 'warehouse_id' => $wh->id],
            ['quantity' => $qty, 'safety_stock' => 0]
        );
    }

    protected function seedPromotion(): void
    {
        $promo = Promotion::create([
            'name' => '10% Off',
            'type' => 'cart',
            'rule_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'conditions_json' => ['min_cart_amount' => 0],
        ]);
        Coupon::create([
            'code' => 'SAVE10',
            'promotion_id' => $promo->id,
            'is_active' => true,
        ]);
    }

    public function test_get_cart_creates_guest_cart_when_no_auth(): void
    {
        $response = $this->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'guest_token', 'currency', 'items', 'subtotal_amount', 'total_amount']])
            ->assertJsonPath('data.items', []);
        $this->assertNotEmpty($response->json('data.guest_token'));
    }

    public function test_add_item_to_cart(): void
    {
        $cartRes = $this->getJson('/api/cart');
        $guestToken = $cartRes->json('data.guest_token');
        $variantId = ProductVariant::first()->id;

        $response = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variantId,
            'quantity' => 2,
        ], ['X-Guest-Token' => $guestToken]);

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.product_variant_id', $variantId)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.subtotal_amount', 199.98);
    }

    public function test_update_cart_item_quantity(): void
    {
        $cartRes = $this->getJson('/api/cart');
        $guestToken = $cartRes->json('data.guest_token');
        $variantId = ProductVariant::first()->id;
        $this->postJson('/api/cart/items', ['product_variant_id' => $variantId, 'quantity' => 2], ['X-Guest-Token' => $guestToken]);
        $cart = $this->getJson('/api/cart', ['X-Guest-Token' => $guestToken])->json('data');
        $itemId = $cart['items'][0]['id'];

        $response = $this->patchJson("/api/cart/items/{$itemId}", ['quantity' => 1], ['X-Guest-Token' => $guestToken]);

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.quantity', 1)
            ->assertJsonPath('data.subtotal_amount', 99.99);
    }

    public function test_remove_cart_item(): void
    {
        $cartRes = $this->getJson('/api/cart');
        $guestToken = $cartRes->json('data.guest_token');
        $variantId = ProductVariant::first()->id;
        $this->postJson('/api/cart/items', ['product_variant_id' => $variantId, 'quantity' => 1], ['X-Guest-Token' => $guestToken]);
        $cart = $this->getJson('/api/cart', ['X-Guest-Token' => $guestToken])->json('data');
        $itemId = $cart['items'][0]['id'];

        $response = $this->deleteJson("/api/cart/items/{$itemId}", [], ['X-Guest-Token' => $guestToken]);

        $response->assertStatus(200)
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.subtotal_amount', 0);
    }

    public function test_apply_coupon(): void
    {
        $cartRes = $this->getJson('/api/cart');
        $guestToken = $cartRes->json('data.guest_token');
        $variantId = ProductVariant::first()->id;
        $this->postJson('/api/cart/items', ['product_variant_id' => $variantId, 'quantity' => 1], ['X-Guest-Token' => $guestToken]);

        $response = $this->postJson('/api/cart/coupon', ['code' => 'SAVE10'], ['X-Guest-Token' => $guestToken]);

        $response->assertStatus(200)
            ->assertJsonPath('data.applied_coupon.code', 'SAVE10');
        $this->assertEqualsWithDelta(10.0, $response->json('data.discount_amount'), 0.01);
        $this->assertEqualsWithDelta(89.99, $response->json('data.total_amount'), 0.01);
    }

    public function test_remove_coupon(): void
    {
        $cartRes = $this->getJson('/api/cart');
        $guestToken = $cartRes->json('data.guest_token');
        $variantId = ProductVariant::first()->id;
        $this->postJson('/api/cart/items', ['product_variant_id' => $variantId, 'quantity' => 1], ['X-Guest-Token' => $guestToken]);
        $this->postJson('/api/cart/coupon', ['code' => 'SAVE10'], ['X-Guest-Token' => $guestToken]);

        $response = $this->deleteJson('/api/cart/coupon', [], ['X-Guest-Token' => $guestToken]);

        $response->assertStatus(200);
        $this->assertNull($response->json('data.applied_coupon'));
        $this->assertEqualsWithDelta(0, $response->json('data.discount_amount'), 0.01);
    }
}
