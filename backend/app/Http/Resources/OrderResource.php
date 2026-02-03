<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lines = $this->lines ?? [];
        return [
            'id' => $this->id,
            'order_number' => $this->order_number ?? $this->orderNumber,
            'user_id' => $this->user_id ?? $this->userId,
            'guest_email' => $this->guest_email ?? $this->guestEmail,
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
        ];
    }
}
