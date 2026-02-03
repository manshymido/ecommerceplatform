<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'city' => $this->city,
            'stock_items_count' => $this->when(isset($this->stock_items_count), $this->stock_items_count),
            'stock_items' => $this->whenLoaded('stockItems', fn () => StockItemResource::collection($this->stockItems)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
