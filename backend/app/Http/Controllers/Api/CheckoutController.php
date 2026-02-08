<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Modules\Cart\Application\CartService;
use App\Modules\Order\Application\PlaceOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends ApiBaseController
{
    use ResolvesCartContext;

    public function __construct(
        private CartService $cartService,
        private PlaceOrderService $placeOrderService,
        private \App\Modules\Order\Domain\OrderRepository $orderRepository,
        private \App\Modules\Payment\Domain\PaymentRepository $paymentRepository,
        private \App\Modules\Payment\Application\PaymentGateway $paymentGateway,
        private \App\Modules\Payment\Application\PaymentService $paymentService
    ) {
    }

    /**
     * POST /checkout - Place order from current cart (auth or guest via X-Guest-Token).
     *
     * If payment_intent_id is provided (modern checkout flow), the controller
     * creates a Payment record linked to the order and, when Stripe reports
     * the intent as succeeded, marks the order as paid immediately.
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $ctx = $this->cartContext($request);
        $cart = $this->cartService->getCart($ctx['user_id'], $ctx['guest_token']);

        if (! $cart) {
            return $this->unprocessable('Cart not found. Add items and try again.');
        }

        try {
            $order = $this->placeOrderService->placeOrder($cart, $request->validated());
        } catch (BusinessRuleException $e) {
            return $e->render();
        } catch (DomainException $e) {
            return $e->render();
        } catch (\DomainException $e) {
            return $this->fromDomainException($e);
        }

        // Link pre-created Stripe PaymentIntent to the new order
        $paymentIntentId = $request->input('payment_intent_id');
        if ($paymentIntentId) {
            try {
                $payment = $this->paymentRepository->create([
                    'order_id' => $order->id,
                    'provider' => \App\Modules\Payment\Domain\Payment::PROVIDER_STRIPE,
                    'provider_reference' => $paymentIntentId,
                    'amount' => $order->totalAmount,
                    'currency' => $order->currency,
                    'status' => \App\Modules\Payment\Domain\Payment::STATUS_PENDING,
                    'raw_response_json' => ['source' => 'checkout_inline'],
                ]);

                // Check if Stripe already confirmed the payment (confirmPayment was called before checkout)
                $stripeStatus = $this->paymentGateway->getPaymentIntentStatus($paymentIntentId);
                if ($stripeStatus === \App\Modules\Payment\Domain\Payment::STATUS_SUCCEEDED) {
                    $this->paymentService->markPaymentSucceeded($payment->id);
                    // Refresh order to get updated status
                    $order = $this->orderRepository->findById($order->id);
                }
            } catch (\Throwable $e) {
                // Non-fatal: payment can still be confirmed via webhook or manual retry
                \Illuminate\Support\Facades\Log::warning('Checkout payment link failed', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->data(new OrderResource($order), 201);
    }
}
