<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception for business rule violations.
 *
 * Use this when a business rule is violated but the data is technically valid.
 */
class BusinessRuleException extends DomainException
{
    public function __construct(
        string $message,
        ?string $errorCode = 'BUSINESS_RULE_VIOLATION',
        array $context = []
    ) {
        parent::__construct(
            message: $message,
            errorCode: $errorCode,
            context: $context,
            statusCode: 422
        );
    }

    /**
     * Insufficient stock error.
     */
    public static function insufficientStock(int $variantId, int $requested, int $available): self
    {
        return new self(
            message: "Insufficient stock for variant {$variantId}. Requested: {$requested}, Available: {$available}",
            errorCode: 'INSUFFICIENT_STOCK',
            context: [
                'variant_id' => $variantId,
                'requested' => $requested,
                'available' => $available,
            ]
        );
    }

    /**
     * Invalid coupon error.
     */
    public static function invalidCoupon(string $code, string $reason): self
    {
        return new self(
            message: "Coupon '{$code}' is invalid: {$reason}",
            errorCode: 'INVALID_COUPON',
            context: [
                'code' => $code,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Order cannot be modified.
     */
    public static function orderNotModifiable(string $orderNumber, string $status): self
    {
        return new self(
            message: "Order {$orderNumber} cannot be modified in status '{$status}'",
            errorCode: 'ORDER_NOT_MODIFIABLE',
            context: [
                'order_number' => $orderNumber,
                'status' => $status,
            ]
        );
    }

    /**
     * Cart is empty error.
     */
    public static function emptyCart(): self
    {
        return new self(
            message: 'Cannot proceed with an empty cart',
            errorCode: 'EMPTY_CART'
        );
    }

    /**
     * Payment failed error.
     */
    public static function paymentFailed(string $reason): self
    {
        return new self(
            message: "Payment failed: {$reason}",
            errorCode: 'PAYMENT_FAILED',
            context: ['reason' => $reason]
        );
    }
}
