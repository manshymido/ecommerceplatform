<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ResourceNotFoundException;
use App\Modules\Cart\Application\CartService;
use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\CartRepository;
use App\Modules\Catalog\Application\CatalogService;
use App\Modules\Inventory\Application\InventoryService;
use App\Modules\Inventory\Domain\AvailabilityResult;
use App\Modules\Promotion\Application\CouponService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    private CartRepository|MockInterface $cartRepository;
    private CouponService|MockInterface $couponService;
    private CatalogService|MockInterface $catalogService;
    private InventoryService|MockInterface $inventoryService;
    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartRepository = Mockery::mock(CartRepository::class);
        $this->couponService = Mockery::mock(CouponService::class);
        $this->catalogService = Mockery::mock(CatalogService::class);
        $this->inventoryService = Mockery::mock(InventoryService::class);

        $this->cartService = new CartService(
            $this->cartRepository,
            $this->couponService,
            $this->catalogService,
            $this->inventoryService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_creates_cart_for_authenticated_user(): void
    {
        $userId = 1;
        $expectedCart = $this->createTestCart($userId);

        $this->cartRepository
            ->shouldReceive('getOrCreateForUser')
            ->with($userId, 'USD')
            ->once()
            ->andReturn($expectedCart);

        $cart = $this->cartService->getOrCreateCart($userId, null);

        $this->assertSame($expectedCart, $cart);
    }

    #[Test]
    public function it_creates_cart_for_guest_with_token(): void
    {
        $guestToken = 'test-guest-token';
        $expectedCart = $this->createTestCart(null, $guestToken);

        $this->cartRepository
            ->shouldReceive('getOrCreateForGuest')
            ->with($guestToken, 'USD')
            ->once()
            ->andReturn($expectedCart);

        $cart = $this->cartService->getOrCreateCart(null, $guestToken);

        $this->assertSame($expectedCart, $cart);
    }

    #[Test]
    public function it_generates_guest_token_when_none_provided(): void
    {
        $this->cartRepository
            ->shouldReceive('getOrCreateForGuest')
            ->withArgs(function ($token, $currency) {
                return is_string($token) && strlen($token) === 32 && $currency === 'USD';
            })
            ->once()
            ->andReturn($this->createTestCart(null, 'generated-token'));

        $cart = $this->cartService->getOrCreateCart(null, null);

        $this->assertNotNull($cart);
    }

    #[Test]
    public function it_adds_item_to_cart_successfully(): void
    {
        $cart = $this->createTestCart(1);
        $variantId = 10;
        $quantity = 2;
        $price = 29.99;

        $this->catalogService
            ->shouldReceive('variantExists')
            ->with($variantId)
            ->once()
            ->andReturn(true);

        $this->inventoryService
            ->shouldReceive('checkAvailability')
            ->with([$variantId => $quantity], null)
            ->once()
            ->andReturn([AvailabilityResult::available($variantId, $quantity, $quantity, null)]);

        $this->catalogService
            ->shouldReceive('getVariantPrice')
            ->with($variantId, 'USD')
            ->once()
            ->andReturn($price);

        $this->cartRepository
            ->shouldReceive('addOrUpdateItem')
            ->with($cart->id, $variantId, $quantity, $price, 'USD')
            ->once();

        $updatedCart = $this->createTestCart(1);
        $this->cartRepository
            ->shouldReceive('findById')
            ->with($cart->id)
            ->once()
            ->andReturn($updatedCart);

        $result = $this->cartService->addItem($cart, $variantId, $quantity);

        $this->assertSame($updatedCart, $result);
    }

    #[Test]
    public function it_throws_exception_when_variant_not_found(): void
    {
        $cart = $this->createTestCart(1);
        $variantId = 999;

        $this->catalogService
            ->shouldReceive('variantExists')
            ->with($variantId)
            ->once()
            ->andReturn(false);

        $this->expectException(ResourceNotFoundException::class);

        $this->cartService->addItem($cart, $variantId, 1);
    }

    #[Test]
    public function it_throws_exception_when_insufficient_stock(): void
    {
        $cart = $this->createTestCart(1);
        $variantId = 10;
        $requested = 5;
        $available = 2;

        $this->catalogService
            ->shouldReceive('variantExists')
            ->with($variantId)
            ->once()
            ->andReturn(true);

        $this->inventoryService
            ->shouldReceive('checkAvailability')
            ->with([$variantId => $requested], null)
            ->once()
            ->andReturn([AvailabilityResult::unavailable($variantId, $requested, $available)]);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionCode(0);

        $this->cartService->addItem($cart, $variantId, $requested);
    }

    #[Test]
    public function it_throws_exception_when_price_not_available(): void
    {
        $cart = $this->createTestCart(1);
        $variantId = 10;

        $this->catalogService
            ->shouldReceive('variantExists')
            ->with($variantId)
            ->once()
            ->andReturn(true);

        $this->inventoryService
            ->shouldReceive('checkAvailability')
            ->with([$variantId => 1], null)
            ->once()
            ->andReturn([AvailabilityResult::available($variantId, 1, 10, null)]);

        $this->catalogService
            ->shouldReceive('getVariantPrice')
            ->with($variantId, 'USD')
            ->once()
            ->andThrow(new ResourceNotFoundException('No price found'));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('No price available for this product in USD');

        $this->cartService->addItem($cart, $variantId, 1);
    }

    #[Test]
    public function it_enforces_max_quantity_per_line(): void
    {
        $cart = $this->createTestCart(1);
        $variantId = 10;
        $requestedQuantity = 150; // Exceeds MAX_QUANTITY_PER_LINE (99)
        $cappedQty = Cart::MAX_QUANTITY_PER_LINE;
        $price = 10.00;

        $this->catalogService
            ->shouldReceive('variantExists')
            ->with($variantId)
            ->once()
            ->andReturn(true);

        $this->inventoryService
            ->shouldReceive('checkAvailability')
            ->with([$variantId => $cappedQty], null)
            ->once()
            ->andReturn([AvailabilityResult::available($variantId, $cappedQty, $cappedQty, null)]);

        $this->catalogService
            ->shouldReceive('getVariantPrice')
            ->with($variantId, 'USD')
            ->once()
            ->andReturn($price);

        $this->cartRepository
            ->shouldReceive('addOrUpdateItem')
            ->withArgs(function ($cartId, $vId, $qty, $p, $curr) {
                return $qty === Cart::MAX_QUANTITY_PER_LINE; // Should be capped at 99
            })
            ->once();

        $this->cartRepository
            ->shouldReceive('findById')
            ->andReturn($this->createTestCart(1));

        $this->cartService->addItem($cart, $variantId, $requestedQuantity);
    }

    #[Test]
    public function it_applies_valid_coupon(): void
    {
        $cart = $this->createTestCart(1, null, 100.00);
        $code = 'SAVE10';
        $discount = 10.00;

        $this->couponService
            ->shouldReceive('validateAndCalculateDiscount')
            ->with($code, 100.00, 'USD', 1)
            ->once()
            ->andReturn($discount);

        $this->cartRepository
            ->shouldReceive('setCartCoupon')
            ->with($cart->id, 'SAVE10', $discount, 'USD')
            ->once();

        $result = $this->cartService->applyCoupon($cart, $code);

        $this->assertTrue($result['valid']);
        $this->assertEquals($discount, $result['discount_amount']);
    }

    #[Test]
    public function it_returns_invalid_for_bad_coupon(): void
    {
        $cart = $this->createTestCart(1, null, 100.00);
        $code = 'INVALID';

        $this->couponService
            ->shouldReceive('validateAndCalculateDiscount')
            ->with($code, 100.00, 'USD', 1)
            ->once()
            ->andReturn(null);

        $result = $this->cartService->applyCoupon($cart, $code);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function it_removes_item_by_setting_quantity_to_zero(): void
    {
        $cartItemId = 5;

        $this->cartRepository
            ->shouldReceive('removeItem')
            ->with($cartItemId)
            ->once();

        $this->cartService->updateItemQuantity($cartItemId, 0);
    }

    #[Test]
    public function it_updates_item_quantity_when_stock_available(): void
    {
        $cartItemId = 5;
        $newQuantity = 3;
        $variantId = 10;

        $this->cartRepository
            ->shouldReceive('findCartItem')
            ->with($cartItemId)
            ->once()
            ->andReturn(['cart_id' => 1, 'product_variant_id' => $variantId, 'quantity' => 1]);

        $this->inventoryService
            ->shouldReceive('checkAvailability')
            ->with([$variantId => $newQuantity], null)
            ->once()
            ->andReturn([AvailabilityResult::available($variantId, $newQuantity, $newQuantity, null)]);

        $this->cartRepository
            ->shouldReceive('updateItemQuantity')
            ->with($cartItemId, $newQuantity)
            ->once();

        $this->cartService->updateItemQuantity($cartItemId, $newQuantity);
    }

    #[Test]
    public function it_throws_when_updating_quantity_exceeds_stock(): void
    {
        $cartItemId = 5;
        $newQuantity = 10;
        $variantId = 10;
        $available = 2;

        $this->cartRepository
            ->shouldReceive('findCartItem')
            ->with($cartItemId)
            ->once()
            ->andReturn(['cart_id' => 1, 'product_variant_id' => $variantId, 'quantity' => 1]);

        $this->inventoryService
            ->shouldReceive('checkAvailability')
            ->with([$variantId => $newQuantity], null)
            ->once()
            ->andReturn([AvailabilityResult::unavailable($variantId, $newQuantity, $available)]);

        $this->expectException(BusinessRuleException::class);

        $this->cartService->updateItemQuantity($cartItemId, $newQuantity);
    }

    /**
     * Create a test Cart instance.
     */
    private function createTestCart(
        ?int $userId = null,
        ?string $guestToken = null,
        float $subtotal = 0.00
    ): Cart {
        return new Cart(
            id: 1,
            userId: $userId,
            guestToken: $guestToken,
            currency: 'USD',
            status: Cart::STATUS_ACTIVE,
            items: [],
            subtotalAmount: $subtotal,
            discountAmount: 0.00,
            totalAmount: $subtotal,
            appliedCouponCode: null
        );
    }
}
