<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id ?? $this->userId,
            'product_id' => $this->product_id ?? $this->productId,
            'rating' => (int) ($this->rating ?? 0),
            'title' => $this->title ?? null,
            'body' => $this->body ?? null,
            'status' => $this->status ?? null,
        ];
    }
}
