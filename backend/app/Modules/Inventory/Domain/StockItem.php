<?php

namespace App\Modules\Inventory\Domain;

class StockItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $productVariantId,
        public readonly int $warehouseId,
        public readonly int $quantity,
        public readonly int $safetyStock,
    ) {
    }

    public function availableQuantity(): int
    {
        return max(0, $this->quantity - $this->safetyStock);
    }

    public function canFulfill(int $requestedQty): bool
    {
        return $this->availableQuantity() >= $requestedQty;
    }
}
