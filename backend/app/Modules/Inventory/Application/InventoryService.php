<?php

namespace App\Modules\Inventory\Application;

use App\Modules\Inventory\Application\Dto\ReservationItem;
use App\Modules\Inventory\Domain\AvailabilityResult;
use App\Modules\Inventory\Domain\StockReservation;
use App\Modules\Inventory\Domain\StockItemRepository;
use App\Modules\Inventory\Domain\StockReservationRepository;

class InventoryService
{
    public function __construct(
        private StockItemRepository $stockItemRepository,
        private StockReservationRepository $stockReservationRepository,
    ) {
    }

    /**
     * Check if requested quantities are available for given variant(s).
     * If warehouseId is null, availability is checked across all warehouses.
     *
     * @param  array<int, int>  $variantQuantities  [product_variant_id => quantity]
     * @return AvailabilityResult[]
     */
    public function checkAvailability(array $variantQuantities, ?int $warehouseId = null): array
    {
        $variantIds = array_keys($variantQuantities);
        $availableByVariant = $this->stockItemRepository->getAvailableByVariants($variantIds, $warehouseId);
        $results = [];

        foreach ($variantQuantities as $variantId => $requestedQty) {
            $available = $availableByVariant[$variantId] ?? 0;
            $results[] = $available >= $requestedQty
                ? AvailabilityResult::available($variantId, $requestedQty, $available, null)
                : AvailabilityResult::unavailable($variantId, $requestedQty, $available);
        }

        return $results;
    }

    /**
     * Reserve stock for a cart or order draft.
     * Uses default warehouse (id=1); returns true if all items could be reserved.
     *
     * @param  ReservationItem[]  $items
     */
    public function reserveStock(array $items, string $sourceType, int $sourceId, ?\DateTimeInterface $expiresAt = null): bool
    {
        $variantQuantities = [];
        foreach ($items as $item) {
            $variantQuantities[$item->productVariantId] = ($variantQuantities[$item->productVariantId] ?? 0) + $item->quantity;
        }

        $availability = $this->checkAvailability($variantQuantities, null);
        foreach ($availability as $result) {
            if (! $result->isAvailable) {
                return false;
            }
        }

        $expiresAt ??= now()->addMinutes(30);
        $defaultWarehouseId = 1;

        foreach ($items as $item) {
            $available = $this->stockItemRepository->getAvailableByVariants([$item->productVariantId], $defaultWarehouseId);
            if (($available[$item->productVariantId] ?? 0) < $item->quantity) {
                $this->releaseReservations($sourceType, $sourceId);

                return false;
            }
            $this->stockReservationRepository->reserve($item->productVariantId, $defaultWarehouseId, $item->quantity, $sourceType, $sourceId, $expiresAt);
        }

        return true;
    }

    public function releaseReservations(string $sourceType, int $sourceId): void
    {
        $this->stockReservationRepository->releaseBySource($sourceType, $sourceId);
    }

    /**
     * Consume reserved stock for an order (deduct from stock_items, record movements, mark reservations consumed).
     */
    public function finalizeStockForOrder(int $orderId): void
    {
        $this->stockReservationRepository->markConsumedBySource(StockReservation::SOURCE_ORDER, $orderId);
    }
}
