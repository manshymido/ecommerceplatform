<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id ?? $this->orderId,
            'provider' => $this->provider,
            'provider_reference' => $this->provider_reference ?? $this->providerReference,
            'amount' => (float) ($this->amount ?? 0),
            'currency' => $this->currency,
            'status' => $this->status,
        ];
    }
}
