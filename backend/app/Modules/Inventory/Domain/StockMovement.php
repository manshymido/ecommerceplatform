<?php

namespace App\Modules\Inventory\Domain;

class StockMovement
{
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public function __construct(
        public readonly int $id,
        public readonly int $productVariantId,
        public readonly int $warehouseId,
        public readonly string $type,
        public readonly int $quantity,
        public readonly ?string $reasonCode,
        public readonly ?string $referenceType,
        public readonly ?int $referenceId,
        public readonly \DateTimeInterface $createdAt,
    ) {
    }
}
