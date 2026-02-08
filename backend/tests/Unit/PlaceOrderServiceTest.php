<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\CouponRedeemed;
use App\Events\OrderCancelled;
use App\Events\OrderPlaced;
use App\Exceptions\BusinessRuleException;
use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\CartItem;
use App\Modules\Cart\Domain\CartRepository;
use App\Modules\Inventory\Application\InventoryService;
use App\Modules\Inventory\Domain\AvailabilityResult;
use App\Modules\Order\Application\PlaceOrderService;
use App\Modules\Order\Domain\Order;
use App\Modules\Order\Domain\OrderRepository;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlaceOrderServiceTest extends TestCase
{
    private CartRepository|MockInterface $cartRepository;
    private OrderRepository|MockInterface $orderRepository;
    private InventoryService|MockInterface $inventoryService;
    private PlaceOrderService $placeOrderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartRepository = Mockery::mock(CartRepository::class);
        $this->orderRepository = Mockery::mock(OrderRepository::class);
        $this->inventoryService = Mockery::mock(InventoryService::class);

        $this->placeOrderService = new PlaceOrderService(
            $this->cartRepository,
            $this->orderRepository,
            $this->inventoryService
        );

        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_throws_exception_for_empty_cart(): void
    {
        $cart = $this->createTestCart(items: []);

        $this->expectException(BusinessRuleException::class);

        $this->placeOrderService->placeOrder($cart);
    }

    #[Test]
    public function it_throws_exception_for_converted_cart(): void
    {
        $cart = $this->createTestCart(status: Cart::STATUS_CONVERTED);

        $this->expectException(BusinessRuleException::class);

        $this->placeOrderService->placeOrder($cart);
    }

    #[Test]
    public function it_throws_exception_for_insufficient_stock(): void
    {
        $cartItem = new CartItem(
            id: 1,
            cartId: 1,
            productVariantId: 10,
            quantity: 5,
            unitPriceAmount: 100.00,
            unitPriceCurrency: 'USD',
            discountAmount: 0,
            discountCurrency: 'USD'
        );
        $cart = $this->createTestCart(items: [$cartItem]);

        $availabilityResult = AvailabilityResult::unavailable(
            variantId: 10,
            requested: 5,
            available: 2
        );

        $this->inventoryService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn([$availabilityResult]);

        $this->expectException(BusinessRuleException::class);

        $this->placeOrderService->placeOrder($cart);
    }

    #[Test]
    public function it_places_order_successfully(): void
    {
        $cartItem = new CartItem(
            id: 1,
            cartId: 1,
            productVariantId: 10,
            quantity: 2,
            unitPriceAmount: 50.00,
            unitPriceCurrency: 'USD',
            discountAmount: 0,
            discountCurrency: 'USD'
        );
        $cart = $this->createTestCart(items: [$cartItem], subtotal: 100.00);

        $availabilityResult = AvailabilityResult::available(
            variantId: 10,
            requested: 2,
            available: 10
        );

        $this->inventoryService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn([$availabilityResult]);

        $order = $this->createTestOrder();

        $this->orderRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($order);

        $this->inventoryService
            ->shouldReceive('reserveStock')
            ->once()
            ->andReturn(true);

        $this->cartRepository
            ->shouldReceive('markAsConverted')
            ->with($cart->id)
            ->once();

        $this->orderRepository
            ->shouldReceive('findById')
            ->with($order->id)
            ->once()
            ->andReturn($order);

        $result = $this->placeOrderService->placeOrder($cart);

        $this->assertSame($order, $result);
        Event::assertDispatched(OrderPlaced::class);
    }

    #[Test]
    public function it_cancels_order_when_stock_reservation_fails(): void
    {
        $cartItem = new CartItem(
            id: 1,
            cartId: 1,
            productVariantId: 10,
            quantity: 2,
            unitPriceAmount: 50.00,
            unitPriceCurrency: 'USD',
            discountAmount: 0,
            discountCurrency: 'USD'
        );
        $cart = $this->createTestCart(items: [$cartItem]);

        $availabilityResult = AvailabilityResult::available(
            variantId: 10,
            requested: 2,
            available: 10
        );

        $this->inventoryService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn([$availabilityResult]);

        $order = $this->createTestOrder();

        $this->orderRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($order);

        $this->inventoryService
            ->shouldReceive('reserveStock')
            ->once()
            ->andReturn(false);

        $this->orderRepository
            ->shouldReceive('recordStatusChange')
            ->once();

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Could not reserve stock');

        try {
            $this->placeOrderService->placeOrder($cart);
        } finally {
            Event::assertDispatched(OrderCancelled::class);
        }
    }

    #[Test]
    public function it_dispatches_coupon_redeemed_event_when_coupon_applied(): void
    {
        $cartItem = new CartItem(
            id: 1,
            cartId: 1,
            productVariantId: 10,
            quantity: 1,
            unitPriceAmount: 100.00,
            unitPriceCurrency: 'USD',
            discountAmount: 0,
            discountCurrency: 'USD'
        );
        $cart = $this->createTestCart(
            items: [$cartItem],
            subtotal: 100.00,
            discount: 15.00,
            couponCode: 'SAVE15'
        );

        $availabilityResult = AvailabilityResult::available(
            variantId: 10,
            requested: 1,
            available: 10
        );

        $this->inventoryService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn([$availabilityResult]);

        $order = $this->createTestOrder();

        $this->orderRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($order);

        $this->inventoryService
            ->shouldReceive('reserveStock')
            ->once()
            ->andReturn(true);

        $this->cartRepository
            ->shouldReceive('markAsConverted')
            ->once();

        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($order);

        $this->placeOrderService->placeOrder($cart);

        Event::assertDispatched(CouponRedeemed::class, function ($event) {
            return $event->couponCode === 'SAVE15' && $event->discountAmount === 15.00;
        });
    }

    /**
     * Create a test Cart instance.
     */
    private function createTestCart(
        ?int $userId = 1,
        string $status = Cart::STATUS_ACTIVE,
        array $items = [],
        float $subtotal = 0.00,
        float $discount = 0.00,
        ?string $couponCode = null
    ): Cart {
        // If no items provided and we need a non-empty cart, create a default item
        if (empty($items) && $status === Cart::STATUS_ACTIVE) {
            // Leave items empty - caller should provide them if needed
        }

        return new Cart(
            id: 1,
            userId: $userId,
            guestToken: null,
            currency: 'USD',
            status: $status,
            items: $items,
            subtotalAmount: $subtotal,
            discountAmount: $discount,
            totalAmount: $subtotal - $discount,
            appliedCouponCode: $couponCode
        );
    }

    /**
     * Create a test Order instance.
     */
    private function createTestOrder(): Order
    {
        return new Order(
            id: 1,
            orderNumber: 'ORD-001',
            userId: 1,
            guestEmail: null,
            status: Order::STATUS_PENDING_PAYMENT,
            currency: 'USD',
            subtotalAmount: 100.00,
            discountAmount: 0.00,
            taxAmount: 0.00,
            shippingAmount: 0.00,
            totalAmount: 100.00,
            lines: []
        );
    }
}
