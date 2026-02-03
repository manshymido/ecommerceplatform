<?php

namespace App\Modules\Payment\Domain;

class Payment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    public const STATUS_VOIDED = 'voided';

    public const PROVIDER_STRIPE = 'stripe';
    public const PROVIDER_PAYPAL = 'paypal';

    public function __construct(
        public readonly int $id,
        public readonly int $orderId,
        public readonly string $provider,
        public readonly ?string $providerReference,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?array $rawResponse = null,
    ) {
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSucceeded(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }
}
