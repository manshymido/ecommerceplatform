<?php

namespace App\Modules\Inventory\Domain;

interface StockItemRepository
{
    /** @return StockItem[] */
    public function getByVariantAndWarehouse(int $productVariantId, int $warehouseId): array;

    /** @return StockItem[] keyed by product_variant_id (aggregated across warehouses) */
    public function getAvailableByVariants(array $productVariantIds, ?int $warehouseId = null): array;

    public function findStockItem(int $productVariantId, int $warehouseId): ?StockItem;

    public function adjustQuantity(int $productVariantId, int $warehouseId, int $delta, string $reasonCode, ?string $referenceType = null, ?int $referenceId = null): void;
}
