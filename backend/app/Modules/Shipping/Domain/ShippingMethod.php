<?php

namespace App\Modules\Shipping\Domain;

class ShippingMethod
{
    /**
     * @param  ShippingZone[]  $zones
     */
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $isActive,
        /** @var ShippingZone[] */
        public readonly array $zones = [],
    ) {
    }
}
