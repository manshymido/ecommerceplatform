<?php

namespace App\Modules\Order\Domain;

class Order
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * @param  OrderLine[]  $lines
     * @param  array<string, mixed>|null  $billingAddress
     * @param  array<string, mixed>|null  $shippingAddress
     */
    public function __construct(
        public readonly int $id,
        public readonly string $orderNumber,
        public readonly ?int $userId,
        public readonly ?string $guestEmail,
        public readonly string $status,
        public readonly string $currency,
        public readonly float $subtotalAmount,
        public readonly float $discountAmount,
        public readonly float $taxAmount,
        public readonly float $shippingAmount,
        public readonly float $totalAmount,
        /** @var OrderLine[] */
        public readonly array $lines,
        public readonly ?array $billingAddress = null,
        public readonly ?array $shippingAddress = null,
        public readonly ?string $shippingMethodCode = null,
        public readonly ?string $shippingMethodName = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $userEmail = null,
        public readonly ?string $userName = null,
    ) {
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }
}
