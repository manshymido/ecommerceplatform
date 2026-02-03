<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->items ?? [];
        $itemsCollection = collect($items)->map(fn ($i) => (object) [
            'id' => $i->id,
            'product_variant_id' => $i->productVariantId,
            'quantity' => $i->quantity,
            'unit_price_amount' => $i->unitPriceAmount,
            'unit_price_currency' => $i->unitPriceCurrency,
            'discount_amount' => $i->discountAmount,
            'discount_currency' => $i->discountCurrency,
        ]);

        return [
            'id' => $this->id,
            'guest_token' => $this->when($this->guestToken !== null, $this->guestToken),
            'currency' => $this->currency,
            'status' => $this->status,
            'items' => CartItemResource::collection($itemsCollection),
            'subtotal_amount' => (float) ($this->subtotalAmount ?? 0),
            'discount_amount' => (float) ($this->discountAmount ?? 0),
            'total_amount' => (float) ($this->totalAmount ?? 0),
            'applied_coupon' => $this->when($this->appliedCouponCode !== null, [
                'code' => $this->appliedCouponCode,
                'discount_amount' => (float) ($this->discountAmount ?? 0),
            ]),
        ];
    }
}
