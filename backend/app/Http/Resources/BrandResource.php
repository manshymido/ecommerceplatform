<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'products' => $this->whenLoaded('products', fn () => ProductResource::collection($this->products)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
