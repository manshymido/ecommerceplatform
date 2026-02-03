<?php

namespace App\Modules\Cart\Domain;

class CartItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $cartId,
        public readonly int $productVariantId,
        public readonly int $quantity,
        public readonly float $unitPriceAmount,
        public readonly string $unitPriceCurrency,
        public readonly float $discountAmount,
        public readonly ?string $discountCurrency,
    ) {
    }

    public function lineTotal(): float
    {
        return ($this->unitPriceAmount * $this->quantity) - $this->discountAmount;
    }
}
