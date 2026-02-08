<?php

namespace App\Modules\Inventory\Infrastructure\Repositories;

use App\Modules\Inventory\Domain\StockItem as DomainStockItem;
use App\Modules\Inventory\Domain\StockItemRepository;
use App\Modules\Inventory\Domain\StockMovement;
use App\Modules\Inventory\Infrastructure\Models\StockItem as StockItemModel;
use App\Modules\Inventory\Infrastructure\Models\StockMovement as StockMovementModel;

class EloquentStockItemRepository implements StockItemRepository
{
    /**
     * @return DomainStockItem[]
     */
    public function getByVariantAndWarehouse(int $productVariantId, int $warehouseId): array
    {
        $models = StockItemModel::where('product_variant_id', $productVariantId)
            ->where('warehouse_id', $warehouseId)
            ->get();

        return $models->map(fn ($m) => $this->toDomain($m))->all();
    }

    /**
     * @return array<int, int> variant_id => available quantity (considering reservations)
     */
    public function getAvailableByVariants(array $productVariantIds, ?int $warehouseId = null): array
    {
        if (empty($productVariantIds)) {
            return [];
        }

        $items = StockItemModel::whereIn('product_variant_id', $productVariantIds);
        if ($warehouseId !== null) {
            $items->where('warehouse_id', $warehouseId);
        }
        $items = $items->get();

        $reservedRows = \App\Modules\Inventory\Infrastructure\Models\StockReservation::query()
            ->where('status', 'active')
            ->whereIn('product_variant_id', $productVariantIds)
            ->when($warehouseId !== null, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->selectRaw('product_variant_id, warehouse_id, SUM(quantity) as total')
            ->groupBy('product_variant_id', 'warehouse_id')
            ->get();
        $reserved = $reservedRows->keyBy(fn ($r) => $r->product_variant_id . '_' . $r->warehouse_id)->map(fn ($r) => (int) $r->total);

        $result = array_fill_keys($productVariantIds, 0);
        foreach ($items as $item) {
            $key = $item->product_variant_id . '_' . $item->warehouse_id;
            $reservedQty = (int) ($reserved[$key] ?? 0);
            $available = max(0, $item->quantity - $item->safety_stock - $reservedQty);
            $result[$item->product_variant_id] = ($result[$item->product_variant_id] ?? 0) + $available;
        }

        return $result;
    }

    public function findStockItem(int $productVariantId, int $warehouseId): ?DomainStockItem
    {
        $model = StockItemModel::where('product_variant_id', $productVariantId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function adjustQuantity(int $productVariantId, int $warehouseId, int $delta, string $reasonCode, ?string $referenceType = null, ?int $referenceId = null): void
    {
        $item = StockItemModel::where('product_variant_id', $productVariantId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $item) {
            $item = StockItemModel::create([
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'quantity' => 0,
                'safety_stock' => 0,
            ]);
        }

        $newQuantity = $item->quantity + $delta;
        if ($newQuantity < 0) {
            $newQuantity = 0;
        }

        $item->update(['quantity' => $newQuantity]);

        $type = $delta > 0 ? StockMovement::TYPE_IN : StockMovement::TYPE_OUT;
        StockMovementModel::create([
            'product_variant_id' => $productVariantId,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'quantity' => $delta,
            'reason_code' => $reasonCode,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_at' => now(),
        ]);
    }

    public function setQuantity(int $productVariantId, int $warehouseId, int $quantity, string $reasonCode = 'assignment'): void
    {
        $quantity = max(0, $quantity);
        $item = StockItemModel::where('product_variant_id', $productVariantId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        $oldQuantity = 0;
        if (! $item) {
            $item = StockItemModel::create([
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'safety_stock' => 0,
            ]);
        } else {
            $oldQuantity = $item->quantity;
            $item->update(['quantity' => $quantity]);
        }

        $delta = $quantity - $oldQuantity;
        if ($delta !== 0) {
            $type = $delta > 0 ? StockMovement::TYPE_IN : StockMovement::TYPE_OUT;
            StockMovementModel::create([
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'type' => $type,
                'quantity' => $delta,
                'reason_code' => $reasonCode,
                'reference_type' => 'adjustment',
                'reference_id' => null,
                'created_at' => now(),
            ]);
        }
    }

    public function lockForUpdate(array $productVariantIds): void
    {
        if (empty($productVariantIds)) {
            return;
        }
        StockItemModel::whereIn('product_variant_id', $productVariantIds)
            ->lockForUpdate()
            ->get();
    }

    /**
     * @return array<string, int> key "variantId_warehouseId" => available quantity
     */
    public function getAvailableByVariantPerWarehouse(array $productVariantIds): array
    {
        if (empty($productVariantIds)) {
            return [];
        }
        $items = StockItemModel::whereIn('product_variant_id', $productVariantIds)->get();
        $reservedRows = \App\Modules\Inventory\Infrastructure\Models\StockReservation::query()
            ->where('status', 'active')
            ->whereIn('product_variant_id', $productVariantIds)
            ->selectRaw('product_variant_id, warehouse_id, SUM(quantity) as total')
            ->groupBy('product_variant_id', 'warehouse_id')
            ->get();
        $reserved = $reservedRows->keyBy(fn ($r) => $r->product_variant_id . '_' . $r->warehouse_id)
            ->map(fn ($r) => (int) $r->total);

        $result = [];
        foreach ($items as $item) {
            $key = $item->product_variant_id . '_' . $item->warehouse_id;
            $reservedQty = (int) ($reserved[$key] ?? 0);
            $available = max(0, $item->quantity - $item->safety_stock - $reservedQty);
            if ($available > 0) {
                $result[$key] = ($result[$key] ?? 0) + $available;
            }
        }

        return $result;
    }

    private function toDomain(StockItemModel $model): DomainStockItem
    {
        return new DomainStockItem(
            id: $model->id,
            productVariantId: $model->product_variant_id,
            warehouseId: $model->warehouse_id,
            quantity: $model->quantity,
            safetyStock: $model->safety_stock,
        );
    }
}
