<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaymentSucceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $orderId
    ) {
    }
}
