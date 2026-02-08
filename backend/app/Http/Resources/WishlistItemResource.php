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
            'product_variant' => [
                'id' => $this->product_variant_id ?? $this->productVariantId,
                'name' => $this->variantName ?? null,
                'sku' => $this->variantSku ?? null,
            ],
            'product' => $this->productId ? [
                'id' => $this->productId,
                'name' => $this->productName,
                'slug' => $this->productSlug,
                'main_image_url' => $this->productImageUrl,
            ] : null,
        ];
    }
}
