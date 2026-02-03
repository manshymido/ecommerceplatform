<?php

namespace App\Modules\Shipping\Infrastructure\Repositories;

use App\Modules\Shipping\Domain\ShippingMethod;
use App\Modules\Shipping\Domain\ShippingMethodRepository;
use App\Modules\Shipping\Domain\ShippingZone;
use App\Modules\Shipping\Infrastructure\Models\ShippingMethod as ShippingMethodModel;
use App\Modules\Shipping\Infrastructure\Models\ShippingMethodZone as ZoneModel;

class EloquentShippingMethodRepository implements ShippingMethodRepository
{
    /**
     * @return ShippingMethod[]
     */
    public function findAllActive(): array
    {
        return ShippingMethodModel::with('zones')
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function findByCode(string $code): ?ShippingMethod
    {
        $model = ShippingMethodModel::with('zones')->where('code', $code)->first();

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(ShippingMethodModel $model): ShippingMethod
    {
        $zones = $model->zones->map(fn (ZoneModel $z) => new ShippingZone(
            id: $z->id,
            shippingMethodId: $z->shipping_method_id,
            countryCode: $z->country_code,
            region: $z->region,
            postalCodePattern: $z->postal_code_pattern,
            minCartTotal: (float) $z->min_cart_total,
            maxCartTotal: $z->max_cart_total !== null ? (float) $z->max_cart_total : null,
            baseAmount: (float) $z->base_amount,
            perKgAmount: (float) $z->per_kg_amount,
            currency: $z->currency,
        ))->all();

        return new ShippingMethod(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            description: $model->description,
            isActive: (bool) $model->is_active,
            zones: $zones,
        );
    }
}
