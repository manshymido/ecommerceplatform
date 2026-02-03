<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id ?? $this->orderId,
            'product_variant_id' => $this->product_variant_id ?? $this->productVariantId,
            'product_name_snapshot' => $this->product_name_snapshot ?? $this->productNameSnapshot,
            'sku_snapshot' => $this->sku_snapshot ?? $this->skuSnapshot,
            'quantity' => $this->quantity,
            'unit_price_amount' => (float) ($this->unit_price_amount ?? $this->unitPriceAmount),
            'unit_price_currency' => $this->unit_price_currency ?? $this->unitPriceCurrency,
            'discount_amount' => (float) ($this->discount_amount ?? $this->discountAmount),
            'discount_currency' => $this->discount_currency ?? $this->discountCurrency,
            'tax_amount' => (float) ($this->tax_amount ?? $this->taxAmount),
            'total_line_amount' => (float) ($this->total_line_amount ?? $this->totalLineAmount),
        ];
    }
}
