<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a coupon is redeemed (order placed with coupon).
 *
 * This event is used to track coupon usage and update redemption counts.
 */
class CouponRedeemed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly string $couponCode,
        public readonly float $discountAmount,
        public readonly ?int $userId = null
    ) {
    }
}
