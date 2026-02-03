<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'main_image_url' => $this->main_image_url,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'brand' => $this->whenLoaded('brand', fn () => new BrandResource($this->brand)),
            'categories' => $this->whenLoaded('categories', fn () => CategoryResource::collection($this->categories)),
            'variants' => $this->whenLoaded('variants', fn () => ProductVariantResource::collection($this->variants)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
