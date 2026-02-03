<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $shippedAt = $this->shipped_at ?? $this->shippedAt;
        $deliveredAt = $this->delivered_at ?? $this->deliveredAt;
        if ($shippedAt instanceof \DateTimeInterface) {
            $shippedAt = $shippedAt->format(\DateTimeInterface::ATOM);
        }
        if ($deliveredAt instanceof \DateTimeInterface) {
            $deliveredAt = $deliveredAt->format(\DateTimeInterface::ATOM);
        }
        return [
            'id' => $this->id,
            'order_id' => $this->order_id ?? $this->orderId,
            'tracking_number' => $this->tracking_number ?? $this->trackingNumber,
            'carrier_code' => $this->carrier_code ?? $this->carrierCode,
            'status' => $this->status,
            'shipped_at' => $shippedAt,
            'delivered_at' => $deliveredAt,
        ];
    }
}
