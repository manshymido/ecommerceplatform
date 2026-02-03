<?php

namespace App\Modules\Shipping\Domain;

interface ShipmentRepository
{
    public function findById(int $id): ?Shipment;

    /**
     * @return Shipment[]
     */
    public function findByOrder(int $orderId): array;

    /**
     * @param  array{order_id: int, tracking_number: string|null, carrier_code: string|null, status: string}  $data
     */
    public function create(array $data): Shipment;

    public function update(int $shipmentId, array $data): void;
}
