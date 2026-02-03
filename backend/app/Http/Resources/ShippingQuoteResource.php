<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingQuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'method_code' => $this->methodCode ?? $this->method_code,
            'method_name' => $this->methodName ?? $this->method_name,
            'amount' => (float) ($this->amount ?? 0),
            'currency' => $this->currency,
        ];
    }
}
