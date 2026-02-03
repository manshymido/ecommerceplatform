<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Inventory\Infrastructure\Models\StockItem;
use App\Modules\Inventory\Infrastructure\Models\Warehouse;
use App\Modules\Order\Infrastructure\Models\Order as OrderModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesAdminUser;
use Tests\SeedsMinimalCatalog;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use CreatesAdminUser;
    use RefreshDatabase;
    use SeedsMinimalCatalog;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
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

    public function test_checkout_requires_cart(): void
    {
        $response = $this->postJson('/api/checkout', [
            'email' => 'guest@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cart not found. Add items and try again.');
    }

    public function test_checkout_guest_success(): void
    {
        $catalog = $this->createMinimalCatalog();
        $this->seedStockForVariant($catalog['variant']->id);

        $guestToken = $this->getJson('/api/cart')->json('data.guest_token');
        $this->postJson('/api/cart/items', [
            'product_variant_id' => $catalog['variant']->id,
            'quantity' => 2,
        ], ['X-Guest-Token' => $guestToken]);

        $response = $this->postJson('/api/checkout', [
            'email' => 'guest@example.com',
            'shipping_amount' => 10,
            'tax_amount' => 5,
        ], ['X-Guest-Token' => $guestToken]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'order_number', 'status', 'total_amount', 'lines', 'guest_email']])
            ->assertJsonPath('data.guest_email', 'guest@example.com')
            ->assertJsonPath('data.status', 'pending_payment')
            ->assertJsonPath('data.lines.0.quantity', 2);
        $this->assertNotEmpty($response->json('data.order_number'));
        $this->assertDatabaseHas('orders', ['guest_email' => 'guest@example.com']);
    }

    public function test_checkout_authenticated_user_success(): void
    {
        $catalog = $this->createMinimalCatalog();
        $this->seedStockForVariant($catalog['variant']->id);

        $this->actingAsAdmin();
        $this->getJson('/api/cart');
        $this->postJson('/api/cart/items', [
            'product_variant_id' => $catalog['variant']->id,
            'quantity' => 1,
        ]);

        $response = $this->postJson('/api/checkout', [
            'shipping_amount' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user_id', $this->admin->id)
            ->assertJsonPath('data.status', 'pending_payment');
        $this->assertDatabaseHas('orders', ['user_id' => $this->admin->id]);
    }

    public function test_checkout_fails_when_insufficient_stock(): void
    {
        $catalog = $this->createMinimalCatalog();
        $this->seedStockForVariant($catalog['variant']->id, 1);

        $guestToken = $this->getJson('/api/cart')->json('data.guest_token');
        $this->postJson('/api/cart/items', [
            'product_variant_id' => $catalog['variant']->id,
            'quantity' => 10,
        ], ['X-Guest-Token' => $guestToken]);

        $response = $this->postJson('/api/checkout', [
            'email' => 'guest@example.com',
        ], ['X-Guest-Token' => $guestToken]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Insufficient stock', $response->json('message'));
    }

    public function test_guest_checkout_stores_address_snapshots(): void
    {
        $catalog = $this->createMinimalCatalog();
        $this->seedStockForVariant($catalog['variant']->id);
        $guestToken = $this->getJson('/api/cart')->json('data.guest_token');
        $this->postJson('/api/cart/items', [
            'product_variant_id' => $catalog['variant']->id,
            'quantity' => 1,
        ], ['X-Guest-Token' => $guestToken]);

        $billing = ['name' => 'Jane Doe', 'line1' => '123 Main St', 'city' => 'NYC', 'country' => 'US'];
        $shipping = ['name' => 'Jane Doe', 'line1' => '456 Oak Ave', 'city' => 'LA', 'country' => 'US'];
        $response = $this->postJson('/api/checkout', [
            'email' => 'guest@example.com',
            'billing_address' => $billing,
            'shipping_address' => $shipping,
        ], ['X-Guest-Token' => $guestToken]);

        $response->assertStatus(201)
            ->assertJsonPath('data.billing_address.name', 'Jane Doe')
            ->assertJsonPath('data.billing_address.line1', '123 Main St')
            ->assertJsonPath('data.shipping_address.line1', '456 Oak Ave');
    }

    public function test_guest_checkout_requires_email(): void
    {
        $catalog = $this->createMinimalCatalog();
        $this->seedStockForVariant($catalog['variant']->id);
        $guestToken = $this->getJson('/api/cart')->json('data.guest_token');
        $this->postJson('/api/cart/items', [
            'product_variant_id' => $catalog['variant']->id,
            'quantity' => 1,
        ], ['X-Guest-Token' => $guestToken]);

        $response = $this->postJson('/api/checkout', [], ['X-Guest-Token' => $guestToken]);

        $response->assertStatus(422);
    }

    public function test_customer_orders_index_requires_auth(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }

    public function test_customer_orders_index_returns_own_orders(): void
    {
        $catalog = $this->createMinimalCatalog();
        $this->seedStockForVariant($catalog['variant']->id);
        $this->actingAsAdmin();
        $this->getJson('/api/cart');
        $this->postJson('/api/cart/items', ['product_variant_id' => $catalog['variant']->id, 'quantity' => 1]);
        $this->postJson('/api/checkout', ['shipping_amount' => 0]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
        $this->assertEquals($this->admin->id, $response->json('data.0.user_id'));
    }

    public function test_customer_orders_show_returns_own_order(): void
    {
        $catalog = $this->createMinimalCatalog();
        $this->seedStockForVariant($catalog['variant']->id);
        $this->actingAsAdmin();
        $this->getJson('/api/cart');
        $this->postJson('/api/cart/items', ['product_variant_id' => $catalog['variant']->id, 'quantity' => 1]);
        $orderRes = $this->postJson('/api/checkout', ['shipping_amount' => 0]);
        $orderId = $orderRes->json('data.id');

        $response = $this->getJson("/api/orders/{$orderId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $orderId)
            ->assertJsonPath('data.user_id', $this->admin->id);
    }

    public function test_customer_orders_show_returns_404_for_other_user_order(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $order = OrderModel::create([
            'order_number' => 'ORD-OTHER',
            'user_id' => $otherUser->id,
            'status' => 'pending_payment',
            'currency' => 'USD',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 100,
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/orders/' . $order->id);

        $response->assertStatus(404);
    }

    public function test_admin_orders_index_returns_orders(): void
    {
        OrderModel::create([
            'order_number' => 'ORD-001',
            'user_id' => $this->admin->id,
            'status' => 'pending_payment',
            'currency' => 'USD',
            'subtotal_amount' => 50,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 50,
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.order_number', 'ORD-001');
    }

    public function test_admin_orders_show_returns_order(): void
    {
        $order = OrderModel::create([
            'order_number' => 'ORD-SHOW',
            'user_id' => $this->admin->id,
            'status' => 'pending_payment',
            'currency' => 'USD',
            'subtotal_amount' => 99,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 99,
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/orders/' . $order->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_number', 'ORD-SHOW');
    }
}
