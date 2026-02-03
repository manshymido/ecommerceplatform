<?php

namespace App\Modules\Shipping\Domain;

class Shipment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_RETURNED = 'returned';

    public function __construct(
        public readonly int $id,
        public readonly int $orderId,
        public readonly ?string $trackingNumber,
        public readonly ?string $carrierCode,
        public readonly string $status,
        public readonly ?\DateTimeInterface $shippedAt = null,
        public readonly ?\DateTimeInterface $deliveredAt = null,
    ) {
    }
}
