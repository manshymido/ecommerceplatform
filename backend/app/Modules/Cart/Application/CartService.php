<?php

namespace App\Modules\Cart\Application;

use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\CartRepository;
use App\Modules\Catalog\Infrastructure\Models\ProductPrice;
use App\Modules\Promotion\Application\CouponService;

class CartService
{
    public function __construct(
        private CartRepository $cartRepository,
        private CouponService $couponService
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
            throw new \DomainException('Cart cannot accept more items.');
        }

        $quantity = min(max(1, $quantity), Cart::MAX_QUANTITY_PER_LINE);
        $price = $this->getVariantPrice($productVariantId, $cart->currency);
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
        $this->cartRepository->updateItemQuantity($cartItemId, $quantity);
    }

    public function removeItem(int $cartItemId): void
    {
        $this->cartRepository->removeItem($cartItemId);
    }

    public function applyCoupon(Cart $cart, string $code): array
    {
        $subtotal = $cart->subtotalAmount ?? 0;
        $discount = $this->couponService->validateAndCalculateDiscount($code, $subtotal, $cart->currency, $cart->userId);
        if ($discount === null) {
            return ['valid' => false, 'message' => 'Invalid or expired coupon.'];
        }

        $this->cartRepository->setCartCoupon($cart->id, strtoupper($code), $discount, $cart->currency);

        return ['valid' => true, 'discount_amount' => $discount];
    }

    public function removeCoupon(Cart $cart): void
    {
        $this->cartRepository->removeCartCoupon($cart->id);
    }

    private function getVariantPrice(int $productVariantId, string $currency): float
    {
        $price = ProductPrice::where('product_variant_id', $productVariantId)
            ->where('currency', $currency)
            ->first();

        if ($price) {
            return (float) $price->amount;
        }

        $price = ProductPrice::where('product_variant_id', $productVariantId)->first();
        if ($price) {
            return (float) $price->amount;
        }

        throw new \DomainException('No price found for variant.');
    }
}
