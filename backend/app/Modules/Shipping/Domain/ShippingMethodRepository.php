<?php

namespace App\Modules\Shipping\Domain;

interface ShippingMethodRepository
{
    /**
     * @return ShippingMethod[]
     */
    public function findAllActive(): array;

    public function findByCode(string $code): ?ShippingMethod;
}
