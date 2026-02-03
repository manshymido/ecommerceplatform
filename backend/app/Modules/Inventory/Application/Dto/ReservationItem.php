<?php

namespace App\Modules\Inventory\Application\Dto;

class ReservationItem
{
    public function __construct(
        public readonly int $productVariantId,
        public readonly int $quantity,
    ) {
    }
}
