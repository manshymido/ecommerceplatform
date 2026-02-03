<?php

namespace App\Modules\Cart\Domain;

interface CartRepository
{
    public function findById(int $id): ?Cart;

    public function findActiveByUser(int $userId): ?Cart;

    public function findActiveByGuestToken(string $guestToken): ?Cart;

    public function getOrCreateForUser(int $userId, string $currency = 'USD'): Cart;

    public function getOrCreateForGuest(string $guestToken, string $currency = 'USD'): Cart;

    public function addOrUpdateItem(int $cartId, int $productVariantId, int $quantity, float $unitPriceAmount, string $currency, float $discountAmount = 0, ?string $discountCurrency = null): void;

    public function updateItemQuantity(int $cartItemId, int $quantity): void;

    public function removeItem(int $cartItemId): void;

    public function setCartCoupon(int $cartId, string $couponCode, float $discountAmount, string $currency): void;

    public function removeCartCoupon(int $cartId): void;

    public function markAsConverted(int $cartId): void;

    public function touchLastActivity(int $cartId): void;
}
