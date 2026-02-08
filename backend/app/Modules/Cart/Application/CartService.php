<?php

namespace App\Modules\Cart\Application;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ResourceNotFoundException;
use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\CartRepository;
use App\Modules\Catalog\Application\CatalogService;
use App\Modules\Inventory\Application\InventoryService;
use App\Modules\Promotion\Application\CouponService;

class CartService
{
    public function __construct(
        private CartRepository $cartRepository,
        private CouponService $couponService,
        private CatalogService $catalogService,
        private InventoryService $inventoryService
    ) {
    }

    public function getOrCreateCart(?int $userId, ?string $guestToken, string $currency = 'USD'): Cart
    {
        if ($userId !== null) {
            return $this->cartRepository->getOrCreateForUser($userId, $currency);
        }
        if ($guestToken !== null && $guestToken !== '') {
            return $this->cartRepository->getOrCreateForGuest($guestToken, $currency);
        }

        $guestToken = bin2hex(random_bytes(16));
        return $this->cartRepository->getOrCreateForGuest($guestToken, $currency);
    }

    public function getCart(?int $userId, ?string $guestToken): ?Cart
    {
        if ($userId !== null) {
            return $this->cartRepository->findActiveByUser($userId);
        }
        if ($guestToken !== null && $guestToken !== '') {
            return $this->cartRepository->findActiveByGuestToken($guestToken);
        }

        return null;
    }

    public function addItem(Cart $cart, int $productVariantId, int $quantity = 1): Cart
    {
        if (! $cart->canAddItem()) {
            throw BusinessRuleException::emptyCart();
        }

        if (! $this->catalogService->variantExists($productVariantId)) {
            throw new ResourceNotFoundException("Product variant not found or not available");
        }

        $quantity = min(max(1, $quantity), Cart::MAX_QUANTITY_PER_LINE);

        $availability = $this->inventoryService->checkAvailability([$productVariantId => $quantity], null);
        $result = $availability[0] ?? null;
        if ($result && ! $result->isAvailable) {
            throw BusinessRuleException::insufficientStock(
                $productVariantId,
                $quantity,
                $result->availableQty ?? 0
            );
        }

        try {
            $price = $this->catalogService->getVariantPrice($productVariantId, $cart->currency);
        } catch (ResourceNotFoundException $e) {
            throw new BusinessRuleException(
                "No price available for this product in {$cart->currency}",
                'PRICE_NOT_AVAILABLE'
            );
        }

        $this->cartRepository->addOrUpdateItem($cart->id, $productVariantId, $quantity, $price, $cart->currency);

        return $this->cartRepository->findById($cart->id);
    }

    public function updateItemQuantity(int $cartItemId, int $quantity): void
    {
        $quantity = min(max(0, $quantity), Cart::MAX_QUANTITY_PER_LINE);
        if ($quantity === 0) {
            $this->cartRepository->removeItem($cartItemId);
            return;
        }
        $cartItem = $this->cartRepository->findCartItem($cartItemId);
        if (! $cartItem) {
            throw new ResourceNotFoundException('Cart item not found');
        }
        $availability = $this->inventoryService->checkAvailability(
            [$cartItem['product_variant_id'] => $quantity],
            null
        );
        $result = $availability[0] ?? null;
        if ($result && ! $result->isAvailable) {
            throw BusinessRuleException::insufficientStock(
                $cartItem['product_variant_id'],
                $quantity,
                $result->availableQty ?? 0
            );
        }
        $this->cartRepository->updateItemQuantity($cartItemId, $quantity);
    }

    public function removeItem(int $cartItemId): void
    {
        $this->cartRepository->removeItem($cartItemId);
    }

    /**
     * Merge guest cart into the authenticated user's cart. Guest cart is marked converted after merge.
     */
    public function mergeGuestCartIntoUser(int $userId, string $guestToken, string $currency = 'USD'): Cart
    {
        $userCart = $this->cartRepository->getOrCreateForUser($userId, $currency);
        $guestCart = $this->cartRepository->findActiveByGuestToken($guestToken);

        if (! $guestCart || $guestCart->items === []) {
            return $this->cartRepository->findById($userCart->id) ?? $userCart;
        }

        foreach ($guestCart->items as $item) {
            $userCart = $this->addItem($userCart, $item->productVariantId, $item->quantity);
        }

        $this->cartRepository->markAsConverted($guestCart->id);

        return $this->cartRepository->findById($userCart->id);
    }

    public function applyCoupon(Cart $cart, string $code): array
    {
        $subtotal = $cart->subtotalAmount ?? 0;
        $discount = $this->couponService->validateAndCalculateDiscount($code, $subtotal, $cart->currency, $cart->userId);

        if ($discount === null) {
            return ['valid' => false, 'message' => 'Invalid or expired coupon.'];
        }

        $normalizedCode = strtoupper($code);
        $this->cartRepository->setCartCoupon($cart->id, $normalizedCode, $discount, $cart->currency);

        // Dispatch event for coupon application tracking
        \App\Events\CouponApplied::dispatch($cart->id, $normalizedCode, $discount, $cart->userId);

        return ['valid' => true, 'discount_amount' => $discount];
    }

    public function removeCoupon(Cart $cart): void
    {
        $this->cartRepository->removeCartCoupon($cart->id);
    }

}
