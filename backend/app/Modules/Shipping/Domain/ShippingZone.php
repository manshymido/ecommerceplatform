<?php

namespace App\Modules\Shipping\Domain;

class ShippingZone
{
    public function __construct(
        public readonly int $id,
        public readonly int $shippingMethodId,
        public readonly string $countryCode,
        public readonly ?string $region,
        public readonly ?string $postalCodePattern,
        public readonly float $minCartTotal,
        public readonly ?float $maxCartTotal,
        public readonly float $baseAmount,
        public readonly float $perKgAmount,
        public readonly string $currency,
    ) {
    }

    public function matches(string $countryCode, float $cartTotal, float $weightKg = 0): bool
    {
        if (strtoupper($this->countryCode) !== strtoupper($countryCode)) {
            return false;
        }
        if ($cartTotal < $this->minCartTotal) {
            return false;
        }
        if ($this->maxCartTotal !== null && $cartTotal > $this->maxCartTotal) {
            return false;
        }
        return true;
    }

    public function calculateAmount(float $weightKg = 0): float
    {
        return $this->baseAmount + ($this->perKgAmount * max(0, $weightKg));
    }
}
