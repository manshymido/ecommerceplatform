<?php

namespace App\Modules\Promotion\Application;

use App\Modules\Promotion\Infrastructure\Models\Coupon;
use App\Modules\Promotion\Infrastructure\Models\Promotion;

class CouponService
{
    /**
     * Validate coupon code and return discount amount for given subtotal (cart currency).
     * Returns null if invalid or not applicable.
     */
    public function validateAndCalculateDiscount(string $code, float $subtotalAmount, string $currency, ?int $userId = null): ?float
    {
        $coupon = Coupon::where('code', $this->normalizeCode($code))->first();
        if (! $coupon || ! $coupon->isCurrentlyValid()) {
            return null;
        }

        if ($userId !== null && $coupon->usage_limit_per_user !== null) {
            $userRedemptions = $coupon->redemptions()->where('user_id', $userId)->count();
            if ($userRedemptions >= $coupon->usage_limit_per_user) {
                return null;
            }
        }

        $promotion = $coupon->promotion;
        if ($promotion && ! $promotion->isCurrentlyValid()) {
            return null;
        }

        $ruleType = $promotion ? $promotion->rule_type : 'fixed';
        $value = $promotion ? (float) $promotion->value : 0;

        $conditions = $promotion?->conditions_json ?? [];
        $minCartAmount = $conditions['min_cart_amount'] ?? null;
        if ($minCartAmount !== null && $subtotalAmount < (float) $minCartAmount) {
            return null;
        }

        if ($ruleType === 'percentage') {
            $discount = round($subtotalAmount * ($value / 100), 2);
        } else {
            $discount = min($value, $subtotalAmount);
        }

        return $discount > 0 ? (float) $discount : null;
    }

    /**
     * Get coupon by code (for storing in cart_coupons with discount already calculated).
     */
    public function findValidCouponByCode(string $code): ?Coupon
    {
        $coupon = Coupon::where('code', $this->normalizeCode($code))->first();

        return $coupon && $coupon->isCurrentlyValid() ? $coupon : null;
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper($code);
    }
}
