<?php

namespace App\Modules\Cart\Domain;

class Cart
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_EXPIRED = 'expired';

    public const MAX_ITEMS = 100;
    public const MAX_QUANTITY_PER_LINE = 99;

    public function __construct(
        public readonly int $id,
        public readonly ?int $userId,
        public readonly ?string $guestToken,
        public readonly string $currency,
        public readonly string $status,
        /** @var CartItem[] */
        public readonly array $items,
        public readonly ?float $subtotalAmount = null,
        public readonly ?float $discountAmount = null,
        public readonly ?float $totalAmount = null,
        public readonly ?string $appliedCouponCode = null,
    ) {
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canAddItem(): bool
    {
        return $this->isActive() && count($this->items) < self::MAX_ITEMS;
    }
}
