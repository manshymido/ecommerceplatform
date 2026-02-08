<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Events\OrderPaymentSucceeded;
use App\Modules\Inventory\Application\InventoryService;
use App\Modules\Inventory\Domain\StockReservation;

/**
 * Connects order lifecycle events to inventory operations.
 *
 * Runs synchronously (no ShouldQueue) because stock deduction is critical
 * and must complete immediately when payment succeeds.
 *
 * - Payment succeeded → commit reservations (deduct stock, record movement)
 * - Order cancelled   → release reservations (return stock to available pool)
 */
class HandleOrderInventory
{
    public function __construct(
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Payment succeeded: consume the reservations and deduct actual stock.
     */
    public function handlePaymentSucceeded(OrderPaymentSucceeded $event): void
    {
        $this->inventoryService->commitStock(
            StockReservation::SOURCE_ORDER,
            $event->orderId
        );
    }

    /**
     * Order cancelled: release active reservations so stock becomes available again.
     */
    public function handleOrderCancelled(OrderCancelled $event): void
    {
        $this->inventoryService->releaseReservations(
            StockReservation::SOURCE_ORDER,
            $event->orderId
        );
    }
}
