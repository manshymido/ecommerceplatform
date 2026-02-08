<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Whether the underlying resource is an Eloquent model with the given relation loaded.
     * Domain Order (DTO) does not have relationLoaded(); only Eloquent models do.
     */
    private function hasRelation(string $relation): bool
    {
        return method_exists($this->resource, 'relationLoaded')
            && $this->resource->relationLoaded($relation);
    }

    public function toArray(Request $request): array
    {
        $lines = $this->lines ?? [];
        return [
            'id' => $this->id,
            'order_number' => $this->order_number ?? $this->orderNumber,
            'user_id' => $this->user_id ?? $this->userId,
            'guest_email' => $this->guest_email ?? $this->guestEmail,
            'user_email' => $this->userEmail ?? (isset($this->user) ? $this->user->email : null),
            'user_name' => $this->userName ?? (isset($this->user) ? $this->user->name : null),
            'status' => $this->status,
            'currency' => $this->currency,
            'subtotal_amount' => (float) ($this->subtotal_amount ?? $this->subtotalAmount),
            'discount_amount' => (float) ($this->discount_amount ?? $this->discountAmount),
            'tax_amount' => (float) ($this->tax_amount ?? $this->taxAmount),
            'shipping_amount' => (float) ($this->shipping_amount ?? $this->shippingAmount),
            'total_amount' => (float) ($this->total_amount ?? $this->totalAmount),
            'lines' => OrderLineResource::collection($lines),
            'billing_address' => $this->billing_address_json ?? $this->billingAddress,
            'shipping_address' => $this->shipping_address_json ?? $this->shippingAddress,
            'shipping_method_code' => $this->shipping_method_code ?? $this->shippingMethodCode,
            'shipping_method_name' => $this->shipping_method_name ?? $this->shippingMethodName,
            'created_at' => (isset($this->created_at) && $this->created_at !== null && method_exists($this->created_at, 'toIso8601String'))
            ? $this->created_at->toIso8601String()
            : ($this->createdAt ?? null),
            'payments' => $this->when($this->hasRelation('payments'), fn () => PaymentResource::collection($this->payments)),
            'shipments' => $this->when($this->hasRelation('shipments'), fn () => ShipmentResource::collection($this->shipments)),
            'status_history' => $this->when($this->hasRelation('statusHistory'), fn () => OrderStatusHistoryResource::collection($this->statusHistory)),
        ];
    }
}
