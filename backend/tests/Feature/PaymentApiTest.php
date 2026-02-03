<?php

namespace Tests\Feature;

use App\Modules\Order\Infrastructure\Models\Order as OrderModel;
use App\Modules\Payment\Application\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesAdminUser;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use CreatesAdminUser;
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
    }

    public function test_orders_pay_requires_auth(): void
    {
        $order = OrderModel::create([
            'order_number' => 'ORD-PAY',
            'user_id' => $this->admin->id,
            'status' => 'pending_payment',
            'currency' => 'USD',
            'subtotal_amount' => 50,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 50,
        ]);

        $response = $this->postJson('/api/orders/' . $order->id . '/pay', ['return_url' => 'https://example.com/thanks']);

        $response->assertStatus(401);
    }

    public function test_orders_pay_returns_404_when_order_not_owned(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $order = OrderModel::create([
            'order_number' => 'ORD-OTHER',
            'user_id' => $otherUser->id,
            'status' => 'pending_payment',
            'currency' => 'USD',
            'subtotal_amount' => 50,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 50,
        ]);
        $this->actingAsAdmin();

        $response = $this->postJson('/api/orders/' . $order->id . '/pay');

        $response->assertStatus(404);
    }

    public function test_orders_pay_returns_422_when_order_not_pending_payment(): void
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

        $response = $this->postJson('/api/orders/' . $order->id . '/pay');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Order is not in pending payment state.');
    }

    public function test_orders_pay_returns_201_with_client_secret_when_pending(): void
    {
        $order = OrderModel::create([
            'order_number' => 'ORD-PENDING',
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

        $this->mock(PaymentGateway::class, function ($mock) {
            $mock->shouldReceive('createPaymentIntent')
                ->once()
                ->andReturn([
                    'payment_intent_id' => 'pi_test_123',
                    'client_secret' => 'pi_test_123_secret_xxx',
                ]);
        });

        $response = $this->postJson('/api/orders/' . $order->id . '/pay', ['return_url' => 'https://example.com/thanks']);

        $response->assertStatus(201)
            ->assertJsonStructure(['payment', 'client_secret', 'payment_intent_id'])
            ->assertJsonPath('client_secret', 'pi_test_123_secret_xxx')
            ->assertJsonPath('payment_intent_id', 'pi_test_123')
            ->assertJsonPath('payment.order_id', $order->id)
            ->assertJsonPath('payment.status', 'pending');
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'provider_reference' => 'pi_test_123']);
    }
}
