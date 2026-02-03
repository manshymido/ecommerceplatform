<?php

namespace App\Modules\Inventory\Domain;

class AvailabilityResult
{
    public function __construct(
        public readonly int $productVariantId,
        public readonly int $requestedQty,
        public readonly int $availableQty,
        public readonly bool $isAvailable,
        public readonly ?int $warehouseId = null,
    ) {
    }

    public static function available(int $variantId, int $requested, int $available, ?int $warehouseId = null): self
    {
        return new self($variantId, $requested, $available, true, $warehouseId);
    }

    public static function unavailable(int $variantId, int $requested, int $available): self
    {
        return new self($variantId, $requested, $available, false);
    }
}
