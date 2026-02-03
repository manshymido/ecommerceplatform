<?php

namespace Database\Seeders;

use App\Modules\Promotion\Infrastructure\Models\Coupon;
use App\Modules\Promotion\Infrastructure\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promo = Promotion::firstOrCreate(
            ['name' => '10% Off Cart'],
            [
                'type' => 'cart',
                'rule_type' => 'percentage',
                'value' => 10,
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'priority' => 0,
                'is_active' => true,
                'conditions_json' => ['min_cart_amount' => 50],
            ]
        );

        Coupon::firstOrCreate(
            ['code' => 'SAVE10'],
            [
                'promotion_id' => $promo->id,
                'usage_limit' => 1000,
                'usage_limit_per_user' => 1,
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'is_active' => true,
            ]
        );
    }
}
