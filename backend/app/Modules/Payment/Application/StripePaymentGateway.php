<?php

namespace App\Modules\Payment\Application;

use App\Modules\Order\Domain\Order;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Refund as StripeRefund;
use Stripe\Stripe;

class StripePaymentGateway implements PaymentGateway
{
    public function __construct(string $secretKey = '')
    {
        if ($secretKey !== '') {
            Stripe::setApiKey($secretKey);
        }
    }

    /**
     * @return array{payment_intent_id: string, client_secret: string}
     */
    public function createPaymentIntent(Order $order, ?string $returnUrl = null): array
    {
        $params = [
            'amount' => $this->toCents($order->totalAmount),
            'currency' => strtolower($order->currency),
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->orderNumber,
            ],
            'automatic_payment_methods' => ['enabled' => true],
        ];
        if ($returnUrl !== null) {
            $params['return_url'] = $returnUrl;
        }

        $intent = PaymentIntent::create($params);

        return [
            'payment_intent_id' => $intent->id,
            'client_secret' => $intent->client_secret,
        ];
    }

    public function getPaymentIntentClientSecret(string $providerReference): ?string
    {
        try {
            $intent = PaymentIntent::retrieve($providerReference);
            return $intent->client_secret;
        } catch (ApiErrorException) {
            return null;
        }
    }

    public function getPaymentIntentStatus(string $providerReference): ?string
    {
        try {
            $intent = PaymentIntent::retrieve($providerReference);
            return $this->mapStripeStatus($intent->status);
        } catch (ApiErrorException) {
            return null;
        }
    }

    /**
     * @return array{refund_id: string|null, status: string, raw: array}
     */
    public function createRefund(string $providerReference, float $amount, string $currency, ?string $reason = null): array
    {
        try {
            $params = [
                'payment_intent' => $providerReference,
                'amount' => $this->toCents($amount),
                'reason' => $reason ? 'requested_by_customer' : null,
            ];
            $refund = StripeRefund::create($params);
            return [
                'refund_id' => $refund->id,
                'status' => $this->mapRefundStatus($refund->status),
                'raw' => $refund->toArray(),
            ];
        } catch (ApiErrorException $e) {
            return [
                'refund_id' => null,
                'status' => 'failed',
                'raw' => ['error' => $e->getMessage()],
            ];
        }
    }

    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => \App\Modules\Payment\Domain\Payment::STATUS_SUCCEEDED,
            'requires_payment_method', 'requires_confirmation', 'requires_action', 'processing' => \App\Modules\Payment\Domain\Payment::STATUS_PENDING,
            'canceled', 'cancelled' => \App\Modules\Payment\Domain\Payment::STATUS_VOIDED,
            default => \App\Modules\Payment\Domain\Payment::STATUS_FAILED,
        };
    }

    private function mapRefundStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => \App\Modules\Payment\Domain\Refund::STATUS_SUCCEEDED,
            'pending', 'requires_action' => \App\Modules\Payment\Domain\Refund::STATUS_PENDING,
            default => \App\Modules\Payment\Domain\Refund::STATUS_FAILED,
        };
    }
}
