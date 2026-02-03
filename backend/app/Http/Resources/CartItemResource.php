<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'unit_price_amount' => (float) $this->unit_price_amount,
            'unit_price_currency' => $this->unit_price_currency,
            'discount_amount' => (float) $this->discount_amount,
            'discount_currency' => $this->discount_currency,
            'line_total' => (float) ($this->unit_price_amount * $this->quantity - $this->discount_amount),
            'product_variant' => $this->when(
                $this->resource instanceof \Illuminate\Database\Eloquent\Model && $this->relationLoaded('productVariant'),
                fn () => new ProductVariantResource($this->productVariant)
            ),
        ];
    }
}
