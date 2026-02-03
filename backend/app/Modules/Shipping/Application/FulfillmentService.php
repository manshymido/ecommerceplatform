<?php

namespace App\Modules\Shipping\Application;

use App\Modules\Order\Domain\Order;
use App\Modules\Order\Domain\OrderRepository;
use App\Modules\Shipping\Domain\Shipment;
use App\Modules\Shipping\Domain\ShipmentRepository;

class FulfillmentService
{
    public function __construct(
        private ShipmentRepository $shipmentRepository,
        private OrderRepository $orderRepository
    ) {
    }

    public function createShipment(int $orderId, ?string $trackingNumber = null, ?string $carrierCode = null): Shipment
    {
        $order = $this->orderRepository->findById($orderId);
        if (! $order) {
            throw new \DomainException('Order not found.');
        }
        if ($order->status !== Order::STATUS_PAID) {
            throw new \DomainException('Order must be paid before creating a shipment.');
        }

        return $this->shipmentRepository->create([
            'order_id' => $orderId,
            'tracking_number' => $trackingNumber,
            'carrier_code' => $carrierCode,
            'status' => Shipment::STATUS_PENDING,
        ]);
    }

    public function markShipped(int $shipmentId, ?string $trackingNumber = null, ?string $carrierCode = null): void
    {
        $data = [
            'status' => Shipment::STATUS_SHIPPED,
            'shipped_at' => now(),
        ];
        if ($trackingNumber !== null) {
            $data['tracking_number'] = $trackingNumber;
        }
        if ($carrierCode !== null) {
            $data['carrier_code'] = $carrierCode;
        }
        $this->shipmentRepository->update($shipmentId, $data);
    }

    public function markDelivered(int $shipmentId): void
    {
        $this->shipmentRepository->update($shipmentId, [
            'status' => Shipment::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
        $shipment = $this->shipmentRepository->findById($shipmentId);
        if ($shipment) {
            $this->orderRepository->recordStatusChange(
                $shipment->orderId,
                Order::STATUS_PAID,
                Order::STATUS_FULFILLED,
                null,
                'Shipment delivered'
            );
            \App\Events\OrderFulfilled::dispatch($shipment->orderId);
        }
    }
}
