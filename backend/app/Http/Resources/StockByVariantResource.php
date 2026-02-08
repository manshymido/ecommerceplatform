<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockByVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_variant_id' => $this->resource['product_variant_id'],
            'product_variant' => $this->when(isset($this->resource['product_variant']), fn () => new ProductVariantResource($this->resource['product_variant'])),
            'total_quantity' => $this->resource['total_quantity'],
            'warehouses' => $this->resource['warehouses'],
        ];
    }
}
