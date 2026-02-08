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
            'user_name' => $this->userName ?? $this->user_name ?? null,
            'created_at' => isset($this->created_at) && $this->created_at !== null
            ? (method_exists($this->created_at, 'toIso8601String')
                ? $this->created_at->toIso8601String()
                : (string) $this->created_at)
            : ($this->createdAt ?? null),
        ];
    }
}
