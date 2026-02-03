<?php

namespace App\Modules\Payment\Application;

use App\Modules\Order\Domain\Order;

/**
 * Abstraction for payment providers (Stripe, PayPal, etc.).
 */
interface PaymentGateway
{
    /**
     * Create a payment intent/session for the order. Returns data for the client (e.g. client_secret for Stripe).
     *
     * @return array{payment_intent_id: string, client_secret: string, ...}
     */
    public function createPaymentIntent(Order $order, ?string $returnUrl = null): array;

    /**
     * Get client secret for an existing payment intent (e.g. to resume payment).
     */
    public function getPaymentIntentClientSecret(string $providerReference): ?string;

    /**
     * Retrieve payment intent status from provider (e.g. after webhook or polling).
     */
    public function getPaymentIntentStatus(string $providerReference): ?string;

    /**
     * Create a refund with the provider.
     *
     * @return array{refund_id: string|null, status: string, raw: array}
     */
    public function createRefund(string $providerReference, float $amount, string $currency, ?string $reason = null): array;
}
