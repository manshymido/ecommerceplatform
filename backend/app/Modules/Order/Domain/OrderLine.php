<?php

namespace App\Modules\Order\Domain;

class OrderLine
{
    public function __construct(
        public readonly int $id,
        public readonly int $orderId,
        public readonly ?int $productVariantId,
        public readonly string $productNameSnapshot,
        public readonly string $skuSnapshot,
        public readonly int $quantity,
        public readonly float $unitPriceAmount,
        public readonly string $unitPriceCurrency,
        public readonly float $discountAmount,
        public readonly ?string $discountCurrency,
        public readonly float $taxAmount,
        public readonly float $totalLineAmount,
    ) {
    }
}
