<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'attributes' => $this->attributes,
            'is_default' => $this->is_default,
            'prices' => $this->whenLoaded('prices', fn () => ProductPriceResource::collection($this->prices)),
        ];
    }
}
