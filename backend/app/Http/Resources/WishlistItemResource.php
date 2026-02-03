<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wishlist_id' => $this->wishlist_id ?? $this->wishlistId,
            'product_variant_id' => $this->product_variant_id ?? $this->productVariantId,
        ];
    }
}
