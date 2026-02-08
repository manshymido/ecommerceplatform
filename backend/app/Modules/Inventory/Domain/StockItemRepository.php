<?php

namespace App\Modules\Inventory\Domain;

interface StockItemRepository
{
    /** @return StockItem[] */
    public function getByVariantAndWarehouse(int $productVariantId, int $warehouseId): array;

    /** @return array<int, int> product_variant_id => available quantity (aggregated across warehouses) */
    public function getAvailableByVariants(array $productVariantIds, ?int $warehouseId = null): array;

    public function findStockItem(int $productVariantId, int $warehouseId): ?StockItem;

    public function adjustQuantity(int $productVariantId, int $warehouseId, int $delta, string $reasonCode, ?string $referenceType = null, ?int $referenceId = null): void;

    /** Set absolute quantity for variant in warehouse (creates stock_item if missing). Records movement. */
    public function setQuantity(int $productVariantId, int $warehouseId, int $quantity, string $reasonCode = 'assignment'): void;

    /**
     * Lock stock_items rows for the given variant IDs (SELECT ... FOR UPDATE).
     * Call only inside an existing DB transaction.
     *
     * @param  array<int>  $productVariantIds
     */
    public function lockForUpdate(array $productVariantIds): void;

    /**
     * Get available quantity per (variant, warehouse). Key: "variantId_warehouseId", value: available qty.
     * Call after lockForUpdate in same transaction for consistent reads.
     *
     * @param  array<int>  $productVariantIds
     * @return array<string, int> key "variantId_warehouseId" => available
     */
    public function getAvailableByVariantPerWarehouse(array $productVariantIds): array;
}
