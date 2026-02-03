<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->items ?? [];
        return [
            'id' => $this->id,
            'user_id' => $this->user_id ?? $this->userId,
            'items' => WishlistItemResource::collection($items),
        ];
    }
}
