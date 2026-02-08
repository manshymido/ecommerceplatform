<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $arr = [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'unit_price_amount' => (float) $this->unit_price_amount,
            'unit_price_currency' => $this->unit_price_currency,
            'discount_amount' => (float) $this->discount_amount,
            'discount_currency' => $this->discount_currency,
            'line_total' => (float) ($this->unit_price_amount * $this->quantity - $this->discount_amount),
            'variant_name' => $this->variant_name ?? null,
            'variant_sku' => $this->variant_sku ?? null,
            'product_name' => $this->product_name ?? null,
            'product_slug' => $this->product_slug ?? null,
            'product_image_url' => $this->product_image_url ?? null,
        ];
        if (isset($this->resource->available_quantity)) {
            $arr['available_quantity'] = (int) $this->resource->available_quantity;
        }

        return $arr;
    }
}
