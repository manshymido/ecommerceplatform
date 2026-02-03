<?php

namespace Database\Seeders;

use App\Modules\Shipping\Infrastructure\Models\ShippingMethod;
use App\Modules\Shipping\Infrastructure\Models\ShippingMethodZone;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $standard = ShippingMethod::firstOrCreate(
            ['code' => 'standard'],
            [
                'name' => 'Standard Shipping',
                'description' => '5-7 business days',
                'is_active' => true,
            ]
        );
        ShippingMethodZone::firstOrCreate(
            [
                'shipping_method_id' => $standard->id,
                'country_code' => 'US',
            ],
            [
                'region' => null,
                'postal_code_pattern' => null,
                'min_cart_total' => 0,
                'max_cart_total' => null,
                'base_amount' => 5.99,
                'per_kg_amount' => 0.50,
                'currency' => 'USD',
            ]
        );

        $express = ShippingMethod::firstOrCreate(
            ['code' => 'express'],
            [
                'name' => 'Express Shipping',
                'description' => '2-3 business days',
                'is_active' => true,
            ]
        );
        ShippingMethodZone::firstOrCreate(
            [
                'shipping_method_id' => $express->id,
                'country_code' => 'US',
            ],
            [
                'region' => null,
                'postal_code_pattern' => null,
                'min_cart_total' => 50,
                'max_cart_total' => null,
                'base_amount' => 12.99,
                'per_kg_amount' => 1.00,
                'currency' => 'USD',
            ]
        );
    }
}
