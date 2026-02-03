<?php

namespace App\Modules\Payment\Domain;

class Refund
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly int $id,
        public readonly int $paymentId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $reason = null,
        public readonly ?array $rawResponse = null,
    ) {
    }
}
