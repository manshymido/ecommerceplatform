<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $arr = [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'attributes' => $this->attributes,
            'is_default' => $this->is_default,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'prices' => $this->whenLoaded('prices', fn () => ProductPriceResource::collection($this->prices)),
        ];
        if (isset($this->resource->available_quantity)) {
            $arr['available_quantity'] = (int) $this->resource->available_quantity;
        }

        return $arr;
    }
}
