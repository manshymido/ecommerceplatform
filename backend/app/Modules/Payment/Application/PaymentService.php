<?php

namespace App\Modules\Payment\Application;

use App\Modules\Order\Domain\Order;
use App\Modules\Order\Domain\OrderRepository;
use App\Modules\Payment\Domain\Payment;
use App\Modules\Payment\Domain\PaymentRepository;
use App\Modules\Payment\Domain\Refund;
use App\Modules\Payment\Domain\RefundRepository;

class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private RefundRepository $refundRepository,
        private OrderRepository $orderRepository,
        private PaymentGateway $paymentGateway
    ) {
    }

    /**
     * Initiate payment for an order. Creates Payment (PENDING) and returns client data for frontend.
     *
     * @return array{payment: Payment, client_secret: string, payment_intent_id: string}
     *
     * @throws \DomainException
     */
    public function initiatePayment(Order $order, ?string $returnUrl = null): array
    {
        if (! $order->isPendingPayment()) {
            throw new \DomainException('Order is not in pending payment state.');
        }

        $existing = $this->paymentRepository->findByOrder($order->id);
        $pending = array_filter($existing, fn (Payment $p) => $p->isPending());
        if ($pending !== [] && ($payment = reset($pending)) && $payment->providerReference !== null) {
            $clientSecret = $this->paymentGateway->getPaymentIntentClientSecret($payment->providerReference);
            if ($clientSecret !== null) {
                return [
                    'payment' => $payment,
                    'client_secret' => $clientSecret,
                    'payment_intent_id' => $payment->providerReference,
                ];
            }
        }

        $data = $this->paymentGateway->createPaymentIntent($order, $returnUrl);
        $payment = $this->paymentRepository->create([
            'order_id' => $order->id,
            'provider' => Payment::PROVIDER_STRIPE,
            'provider_reference' => $data['payment_intent_id'],
            'amount' => $order->totalAmount,
            'currency' => $order->currency,
            'status' => Payment::STATUS_PENDING,
            'raw_response_json' => $data,
        ]);

        return [
            'payment' => $payment,
            'client_secret' => $data['client_secret'],
            'payment_intent_id' => $data['payment_intent_id'],
        ];
    }

    /**
     * Mark payment as succeeded (e.g. from webhook). Updates order status to PAID.
     */
    public function markPaymentSucceeded(int $paymentId, ?array $rawResponse = null): void
    {
        $payment = $this->paymentRepository->findById($paymentId);
        if (! $payment || $payment->isSucceeded()) {
            return;
        }
        $this->paymentRepository->updateStatus($paymentId, Payment::STATUS_SUCCEEDED, $rawResponse);
        $this->orderRepository->recordStatusChange(
            $payment->orderId,
            \App\Modules\Order\Domain\Order::STATUS_PENDING_PAYMENT,
            \App\Modules\Order\Domain\Order::STATUS_PAID,
            null,
            'Payment succeeded'
        );
        \App\Events\OrderPaymentSucceeded::dispatch($payment->orderId);
    }

    /**
     * Mark payment as failed (e.g. from webhook).
     */
    public function markPaymentFailed(int $paymentId, ?array $rawResponse = null): void
    {
        $payment = $this->paymentRepository->findById($paymentId);
        if (! $payment || ! $payment->isPending()) {
            return;
        }
        $this->paymentRepository->updateStatus($paymentId, Payment::STATUS_FAILED, $rawResponse);
    }

    /**
     * Create refund for a succeeded payment. Optionally partial amount; default full.
     *
     * @return Refund
     *
     * @throws \DomainException
     */
    public function createRefund(int $paymentId, ?float $amount = null, ?string $reason = null): Refund
    {
        $payment = $this->paymentRepository->findById($paymentId);
        if (! $payment || ! $payment->isSucceeded()) {
            throw new \DomainException('Payment not found or not succeeded.');
        }
        if ($payment->providerReference === null) {
            throw new \DomainException('Payment has no provider reference.');
        }

        $refundAmount = $amount ?? $payment->amount;
        if ($refundAmount <= 0 || $refundAmount > $payment->amount) {
            throw new \DomainException('Invalid refund amount.');
        }

        $refund = $this->refundRepository->create([
            'payment_id' => $paymentId,
            'order_id' => $payment->orderId,
            'amount' => $refundAmount,
            'currency' => $payment->currency,
            'status' => Refund::STATUS_PENDING,
            'reason' => $reason,
            'raw_response_json' => null,
        ]);

        $result = $this->paymentGateway->createRefund(
            $payment->providerReference,
            $refundAmount,
            $payment->currency,
            $reason
        );

        $this->refundRepository->updateStatus($refund->id, $result['status'], $result['raw'] ?? null);

        if ($result['status'] === Refund::STATUS_SUCCEEDED) {
            $succeededRefunds = array_filter(
                $this->refundRepository->findByPayment($paymentId),
                fn (Refund $r) => $r->status === Refund::STATUS_SUCCEEDED
            );
            $refundsTotal = array_sum(array_map(fn (Refund $r) => $r->amount, $succeededRefunds));
            $newPaymentStatus = $refundsTotal >= $payment->amount
                ? Payment::STATUS_REFUNDED
                : Payment::STATUS_PARTIALLY_REFUNDED;
            $this->paymentRepository->updateStatus($paymentId, $newPaymentStatus);

            if ($newPaymentStatus === Payment::STATUS_REFUNDED) {
                $this->orderRepository->recordStatusChange(
                    $payment->orderId,
                    Order::STATUS_PAID,
                    Order::STATUS_REFUNDED,
                    null,
                    'Payment refunded'
                );
            }
        }

        return $this->refundRepository->findById($refund->id);
    }
}
