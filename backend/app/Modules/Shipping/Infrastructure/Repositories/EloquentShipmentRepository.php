<?php

namespace App\Modules\Shipping\Infrastructure\Repositories;

use App\Modules\Shipping\Domain\Shipment;
use App\Modules\Shipping\Domain\ShipmentRepository;
use App\Modules\Shipping\Infrastructure\Models\Shipment as ShipmentModel;

class EloquentShipmentRepository implements ShipmentRepository
{
    public function findById(int $id): ?Shipment
    {
        $model = ShipmentModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    /**
     * @return Shipment[]
     */
    public function findByOrder(int $orderId): array
    {
        return ShipmentModel::where('order_id', $orderId)
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function create(array $data): Shipment
    {
        $model = ShipmentModel::create([
            'order_id' => $data['order_id'],
            'tracking_number' => $data['tracking_number'] ?? null,
            'carrier_code' => $data['carrier_code'] ?? null,
            'status' => $data['status'] ?? Shipment::STATUS_PENDING,
        ]);

        return $this->toDomain($model);
    }

    public function update(int $shipmentId, array $data): void
    {
        $allowed = ['tracking_number', 'carrier_code', 'status', 'shipped_at', 'delivered_at'];
        $filtered = array_intersect_key($data, array_flip($allowed));
        if ($filtered !== []) {
            ShipmentModel::where('id', $shipmentId)->update($filtered);
        }
    }

    private function toDomain(ShipmentModel $model): Shipment
    {
        return new Shipment(
            id: $model->id,
            orderId: $model->order_id,
            trackingNumber: $model->tracking_number,
            carrierCode: $model->carrier_code,
            status: $model->status,
            shippedAt: $model->shipped_at?->toDateTimeImmutable(),
            deliveredAt: $model->delivered_at?->toDateTimeImmutable(),
        );
    }
}
