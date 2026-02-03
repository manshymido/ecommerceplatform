<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'position' => $this->position,
            'parent' => $this->whenLoaded('parent', fn () => new CategoryResource($this->parent)),
            'children' => $this->whenLoaded('children', fn () => CategoryResource::collection($this->children)),
            'products' => $this->whenLoaded('products', fn () => ProductResource::collection($this->products)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
