<?php

namespace App\Modules\Inventory\Domain;

class StockReservation
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CONSUMED = 'consumed';

    public const SOURCE_CART = 'cart';
    public const SOURCE_ORDER = 'order';

    public function __construct(
        public readonly int $id,
        public readonly int $productVariantId,
        public readonly int $warehouseId,
        public readonly int $quantity,
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly ?\DateTimeInterface $expiresAt,
        public readonly string $status,
    ) {
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
