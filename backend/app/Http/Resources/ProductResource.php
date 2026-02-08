<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /** @var array<int, int> variant_id => available_quantity (for single-product response only) */
    protected array $availability = [];

    /**
     * @param  mixed  $resource
     * @param  array<int, int>|mixed  $availability  For single-product response pass variant_id => available_quantity. When used in ResourceCollection, Laravel may pass the collection key; we only use availability when it is an array.
     */
    public function __construct($resource, mixed $availability = [])
    {
        parent::__construct($resource);
        $this->availability = is_array($availability) ? $availability : [];
    }

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
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(function ($variant) use ($request) {
                $arr = (new ProductVariantResource($variant))->toArray($request);
                if ($this->availability !== []) {
                    $arr['available_quantity'] = $this->availability[$variant->id] ?? 0;
                }

                return $arr;
            })->values()->all()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
