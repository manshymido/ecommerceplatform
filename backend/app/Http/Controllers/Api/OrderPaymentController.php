<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Resources\PaymentResource;
use App\Modules\Order\Domain\OrderRepository;
use App\Modules\Payment\Application\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderPaymentController extends ApiBaseController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private PaymentService $paymentService,
        private \App\Modules\Payment\Application\PaymentGateway $paymentGateway,
        private \App\Modules\Payment\Domain\PaymentRepository $paymentRepository
    ) {
    }

    /**
     * POST /orders/{id}/pay - Initiate payment for order. Returns client_secret for Stripe Elements.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findById($id);
        if (! $order || $order->userId !== $request->user()->id) {
            return $this->notFound(ApiMessages::ORDER_NOT_FOUND);
        }

        try {
            $result = $this->paymentService->initiatePayment(
                $order,
                $request->input('return_url')
            );
        } catch (\DomainException $e) {
            return $this->fromDomainException($e);
        }

        return response()->json([
            'data' => [
                'payment' => (new PaymentResource($result['payment']))->resolve(),
                'client_secret' => $result['client_secret'],
                'payment_intent_id' => $result['payment_intent_id'],
                'stripe_publishable_key' => config('services.stripe.key'),
            ],
        ], 201);
    }

    /**
     * POST /orders/{id}/pay/confirm - Check payment status with Stripe and update locally.
     * Called by frontend after stripe.confirmPayment() succeeds client-side.
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findById($id);
        if (! $order || $order->userId !== $request->user()->id) {
            return $this->notFound(ApiMessages::ORDER_NOT_FOUND);
        }

        $paymentIntentId = $request->input('payment_intent_id');
        if (! $paymentIntentId) {
            return $this->unprocessable('payment_intent_id is required.');
        }

        $payments = $this->paymentRepository->findByOrder($order->id);
        $payment = collect($payments)->first(
            fn ($p) => $p->providerReference === $paymentIntentId
        );

        if (! $payment) {
            return $this->notFound(ApiMessages::PAYMENT_NOT_FOUND);
        }

        if ($payment->isSucceeded()) {
            return response()->json(['data' => ['status' => 'succeeded', 'already_confirmed' => true]]);
        }

        $providerStatus = $this->paymentGateway->getPaymentIntentStatus($paymentIntentId);

        if ($providerStatus === \App\Modules\Payment\Domain\Payment::STATUS_SUCCEEDED) {
            $this->paymentService->markPaymentSucceeded($payment->id);
            return response()->json(['data' => ['status' => 'succeeded']]);
        }

        return response()->json(['data' => ['status' => $providerStatus ?? 'unknown']]);
    }
}
