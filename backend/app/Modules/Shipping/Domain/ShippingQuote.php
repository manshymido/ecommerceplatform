<?php

namespace App\Modules\Shipping\Domain;

class ShippingQuote
{
    public function __construct(
        public readonly string $methodCode,
        public readonly string $methodName,
        public readonly float $amount,
        public readonly string $currency,
    ) {
    }
}
