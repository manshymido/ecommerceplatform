<?php

namespace Tests\Feature;

use App\Modules\Order\Infrastructure\Models\Order as OrderModel;
use App\Modules\Shipping\Infrastructure\Models\ShippingMethod;
use App\Modules\Shipping\Infrastructure\Models\ShippingMethodZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesAdminUser;
use Tests\TestCase;

class ShippingApiTest extends TestCase
{
    use CreatesAdminUser;
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
        $this->seedShipping();
    }

    protected function seedShipping(): void
    {
        $standard = ShippingMethod::create([
            'code' => 'standard',
            'name' => 'Standard',
            'description' => '5-7 days',
            'is_active' => true,
        ]);
        ShippingMethodZone::create([
            'shipping_method_id' => $standard->id,
            'country_code' => 'US',
            'min_cart_total' => 0,
            'base_amount' => 5.99,
            'per_kg_amount' => 0.5,
            'currency' => 'USD',
        ]);
    }

    public function test_shipping_quotes_returns_quotes_for_country_and_cart_total(): void
    {
        $response = $this->getJson('/api/shipping/quotes?country_code=US&cart_total=100');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
        $this->assertEquals('standard', $response->json('data.0.method_code'));
        $this->assertEqualsWithDelta(5.99, $response->json('data.0.amount'), 0.01);
    }

    public function test_shipping_quotes_requires_country_and_cart_total(): void
    {
        $response = $this->getJson('/api/shipping/quotes');

        $response->assertStatus(422);
    }

    public function test_admin_can_list_shipments_for_order(): void
    {
        $order = OrderModel::create([
            'order_number' => 'ORD-SHIP',
            'user_id' => $this->admin->id,
            'status' => 'paid',
            'currency' => 'USD',
            'subtotal_amount' => 50,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 50,
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/orders/' . $order->id . '/shipments');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function test_admin_can_create_shipment_for_paid_order(): void
    {
        $order = OrderModel::create([
            'order_number' => 'ORD-PAID',
            'user_id' => $this->admin->id,
            'status' => 'paid',
            'currency' => 'USD',
            'subtotal_amount' => 50,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 50,
        ]);
        $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/orders/' . $order->id . '/shipments', [
            'tracking_number' => '1Z999AA10123456784',
            'carrier_code' => 'ups',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.tracking_number', '1Z999AA10123456784')
            ->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('shipments', ['order_id' => $order->id]);
    }

    public function test_admin_cannot_create_shipment_for_pending_order(): void
    {
        $order = OrderModel::create([
            'order_number' => 'ORD-PEND',
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

        $response = $this->postJson('/api/admin/orders/' . $order->id . '/shipments');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Order must be paid before creating a shipment.');
    }
}
