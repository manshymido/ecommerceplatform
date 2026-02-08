<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * @param  array<int, int>  $availability  variant_id => available_quantity
     */
    public function __construct($resource, protected array $availability = [])
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $items = $this->items ?? [];
        $itemsCollection = collect($items)->map(function ($i) {
            $arr = [
                'id' => $i->id,
                'product_variant_id' => $i->productVariantId,
                'quantity' => $i->quantity,
                'unit_price_amount' => $i->unitPriceAmount,
                'unit_price_currency' => $i->unitPriceCurrency,
                'discount_amount' => $i->discountAmount,
                'discount_currency' => $i->discountCurrency,
                'variant_name' => $i->variantName,
                'variant_sku' => $i->variantSku,
                'product_name' => $i->productName,
                'product_slug' => $i->productSlug,
                'product_image_url' => $i->productImageUrl,
            ];
            if ($this->availability !== []) {
                $arr['available_quantity'] = $this->availability[$i->productVariantId] ?? 0;
            }

            return (object) $arr;
        });

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
