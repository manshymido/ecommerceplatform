<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CouponApplied;
use App\Events\CouponRedeemed;
use App\Modules\Promotion\Infrastructure\Models\Coupon;
use App\Modules\Promotion\Infrastructure\Models\CouponRedemption;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener to track coupon usage for analytics and limit enforcement.
 */
class TrackCouponUsage implements ShouldQueue
{
    /**
     * Handle coupon applied to cart event.
     *
     * Logs the application for analytics purposes.
     */
    public function handleCouponApplied(CouponApplied $event): void
    {
        Log::info('Coupon applied to cart', [
            'cart_id' => $event->cartId,
            'coupon_code' => $event->couponCode,
            'discount_amount' => $event->discountAmount,
            'user_id' => $event->userId,
        ]);
    }

    /**
     * Handle coupon redeemed (order placed) event.
     *
     * Records the redemption to enforce usage limits.
     */
    public function handleCouponRedeemed(CouponRedeemed $event): void
    {
        $coupon = Coupon::where('code', $event->couponCode)->first();

        if (! $coupon) {
            Log::warning('Coupon not found during redemption tracking', [
                'coupon_code' => $event->couponCode,
                'order_id' => $event->orderId,
            ]);
            return;
        }

        // Record the redemption
        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'order_id' => $event->orderId,
            'user_id' => $event->userId,
            'redeemed_at' => now(),
        ]);

        // Increment usage count on coupon
        $coupon->increment('times_used');

        Log::info('Coupon redeemed successfully', [
            'coupon_id' => $coupon->id,
            'coupon_code' => $event->couponCode,
            'order_id' => $event->orderId,
            'user_id' => $event->userId,
            'total_uses' => $coupon->times_used + 1,
        ]);
    }
}
