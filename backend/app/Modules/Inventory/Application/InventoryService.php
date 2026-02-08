<?php

namespace App\Modules\Inventory\Application;

use App\Modules\Inventory\Application\Dto\ReservationItem;
use App\Modules\Inventory\Domain\AvailabilityResult;
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
     * Allocates from any warehouse(s) that have available stock. Must be called inside a DB transaction;
     * call lockForUpdate on relevant variants before calling this to avoid race conditions.
     *
     * @param  ReservationItem[]  $items
     */
    public function reserveStock(array $items, string $sourceType, int $sourceId, ?\DateTimeInterface $expiresAt = null): bool
    {
        $variantQuantities = [];
        foreach ($items as $item) {
            $variantQuantities[$item->productVariantId] = ($variantQuantities[$item->productVariantId] ?? 0) + $item->quantity;
        }
        $variantIds = array_keys($variantQuantities);

        $this->stockItemRepository->lockForUpdate($variantIds);
        $availablePerWarehouse = $this->stockItemRepository->getAvailableByVariantPerWarehouse($variantIds);

        $expiresAt ??= now()->addMinutes(30);
        $toReserve = []; // [(variantId, warehouseId, qty), ...]

        foreach ($items as $item) {
            $need = $item->quantity;
            $variantId = $item->productVariantId;
            $warehouseKeys = [];
            foreach (array_keys($availablePerWarehouse) as $key) {
                $parts = explode('_', $key);
                if (isset($parts[0], $parts[1]) && (int) $parts[0] === $variantId) {
                    $warehouseKeys[] = $key;
                }
            }
            usort($warehouseKeys, fn (string $a, string $b): int => (int) explode('_', $a)[1] <=> (int) explode('_', $b)[1]);
            foreach ($warehouseKeys as $key) {
                if ($need <= 0) {
                    break;
                }
                $available = $availablePerWarehouse[$key] ?? 0;
                $take = min($need, $available);
                if ($take > 0) {
                    $parts = explode('_', $key);
                    $warehouseId = (int) ($parts[1] ?? 0);
                    $toReserve[] = [(int) $variantId, $warehouseId, $take];
                    $availablePerWarehouse[$key] = $available - $take;
                    $need -= $take;
                }
            }
            if ($need > 0) {
                $this->releaseReservations($sourceType, $sourceId);

                return false;
            }
        }

        foreach ($toReserve as [$variantId, $warehouseId, $qty]) {
            $this->stockReservationRepository->reserve($variantId, $warehouseId, $qty, $sourceType, $sourceId, $expiresAt);
        }

        return true;
    }

    /**
     * Commit reserved stock: marks reservations as consumed and decrements
     * actual stock_items.quantity.  Called when payment succeeds.
     */
    public function commitStock(string $sourceType, int $sourceId): void
    {
        $this->stockReservationRepository->markConsumedBySource($sourceType, $sourceId);
    }

    public function releaseReservations(string $sourceType, int $sourceId): void
    {
        $this->stockReservationRepository->releaseBySource($sourceType, $sourceId);
    }
}
