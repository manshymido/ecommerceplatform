<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $createdAt = $this->created_at ?? $this->createdAt;
        if ($createdAt instanceof \DateTimeInterface) {
            $createdAt = $createdAt->format(\DateTimeInterface::ATOM);
        }

        return [
            'id' => $this->id,
            'order_id' => $this->order_id ?? $this->orderId,
            'from_status' => $this->from_status ?? $this->fromStatus ?? null,
            'to_status' => $this->to_status ?? $this->toStatus,
            'changed_by_user_id' => $this->changed_by_user_id ?? $this->changedByUserId ?? null,
            'changed_by_user_name' => data_get($this->resource, 'changedByUser.name'),
            'reason' => $this->reason ?? null,
            'created_at' => $createdAt,
        ];
    }
}
