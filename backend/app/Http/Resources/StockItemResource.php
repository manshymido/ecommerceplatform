<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'warehouse_id' => $this->warehouse_id,
            'quantity' => $this->quantity,
            'safety_stock' => $this->safety_stock,
            'available' => $this->when(isset($this->quantity) && isset($this->safety_stock), max(0, $this->quantity - $this->safety_stock)),
            'product_variant' => $this->whenLoaded('productVariant', fn () => new ProductVariantResource($this->productVariant)),
            'warehouse' => $this->whenLoaded('warehouse', fn () => new WarehouseResource($this->warehouse)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
