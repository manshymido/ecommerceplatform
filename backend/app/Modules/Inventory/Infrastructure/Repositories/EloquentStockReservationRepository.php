<?php

namespace App\Modules\Inventory\Infrastructure\Repositories;

use App\Modules\Inventory\Domain\StockReservation as DomainStockReservation;
use App\Modules\Inventory\Domain\StockReservationRepository;
use App\Modules\Inventory\Infrastructure\Models\StockMovement as StockMovementModel;
use App\Modules\Inventory\Infrastructure\Models\StockReservation as StockReservationModel;
use Illuminate\Support\Facades\DB;

class EloquentStockReservationRepository implements StockReservationRepository
{
    /**
     * @return DomainStockReservation[]
     */
    public function getActiveBySource(string $sourceType, int $sourceId): array
    {
        return StockReservationModel::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', DomainStockReservation::STATUS_ACTIVE)
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function reserve(int $productVariantId, int $warehouseId, int $quantity, string $sourceType, int $sourceId, ?\DateTimeInterface $expiresAt = null): DomainStockReservation
    {
        $model = StockReservationModel::create([
            'product_variant_id' => $productVariantId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'expires_at' => $expiresAt,
            'status' => DomainStockReservation::STATUS_ACTIVE,
        ]);

        return $this->toDomain($model);
    }

    public function releaseBySource(string $sourceType, int $sourceId): void
    {
        StockReservationModel::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', DomainStockReservation::STATUS_ACTIVE)
            ->update(['status' => DomainStockReservation::STATUS_EXPIRED]);
    }

    public function markConsumedBySource(string $sourceType, int $sourceId): void
    {
        $reservations = StockReservationModel::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', DomainStockReservation::STATUS_ACTIVE)
            ->get();

        DB::transaction(function () use ($reservations, $sourceType, $sourceId) {
            foreach ($reservations as $res) {
                $res->update(['status' => DomainStockReservation::STATUS_CONSUMED]);
                \App\Modules\Inventory\Infrastructure\Models\StockItem::where('product_variant_id', $res->product_variant_id)
                    ->where('warehouse_id', $res->warehouse_id)
                    ->decrement('quantity', $res->quantity);
                StockMovementModel::create([
                    'product_variant_id' => $res->product_variant_id,
                    'warehouse_id' => $res->warehouse_id,
                    'type' => 'out',
                    'quantity' => -$res->quantity,
                    'reason_code' => 'sale',
                    'reference_type' => $sourceType,
                    'reference_id' => $sourceId,
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function expireStale(): void
    {
        StockReservationModel::where('status', DomainStockReservation::STATUS_ACTIVE)
            ->where('expires_at', '<', now())
            ->update(['status' => DomainStockReservation::STATUS_EXPIRED]);
    }

    private function toDomain(StockReservationModel $model): DomainStockReservation
    {
        return new DomainStockReservation(
            id: $model->id,
            productVariantId: $model->product_variant_id,
            warehouseId: $model->warehouse_id,
            quantity: $model->quantity,
            sourceType: $model->source_type,
            sourceId: (int) $model->source_id,
            expiresAt: $model->expires_at,
            status: $model->status,
        );
    }
}
