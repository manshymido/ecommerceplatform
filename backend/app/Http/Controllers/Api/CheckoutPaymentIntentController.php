<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Modules\Cart\Application\CartService;
use App\Modules\Payment\Application\PaymentGateway;
use App\Modules\Order\Domain\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Creates a Stripe PaymentIntent for the current cart so Stripe Elements
 * can render on the checkout page before the order is placed.
 */
class CheckoutPaymentIntentController extends ApiBaseController
{
    use ResolvesCartContext;

    public function __construct(
        private CartService $cartService,
        private PaymentGateway $paymentGateway
    ) {
    }

    /**
     * POST /checkout/payment-intent
     *
     * Calculates the cart total (+ optional shipping) and creates a Stripe
     * PaymentIntent.  Returns client_secret for Stripe Elements.
     */
    public function store(Request $request): JsonResponse
    {
        $ctx = $this->cartContext($request);
        $cart = $this->cartService->getCart($ctx['user_id'], $ctx['guest_token']);

        if (! $cart || count($cart->items) === 0) {
            return $this->unprocessable('Cart is empty.');
        }

        $shippingAmount = (float) $request->input('shipping_amount', 0);
        $subtotal = $cart->subtotalAmount ?? 0;
        $discount = $cart->discountAmount ?? 0;
        $total = max(0, $subtotal - $discount + $shippingAmount);

        if ($total <= 0) {
            return $this->unprocessable('Order total must be greater than zero.');
        }

        // Build a lightweight Order-like object for the gateway
        $orderStub = new Order(
            id: 0,
            orderNumber: 'PENDING',
            userId: $ctx['user_id'],
            guestEmail: null,
            status: Order::STATUS_PENDING_PAYMENT,
            currency: $cart->currency,
            subtotalAmount: $subtotal,
            discountAmount: $discount,
            taxAmount: 0,
            shippingAmount: $shippingAmount,
            totalAmount: $total,
            lines: [],
        );

        try {
            $data = $this->paymentGateway->createPaymentIntent($orderStub);
        } catch (\Throwable $e) {
            return $this->unprocessable('Unable to initialize payment: ' . $e->getMessage());
        }

        return response()->json([
            'data' => [
                'client_secret' => $data['client_secret'],
                'payment_intent_id' => $data['payment_intent_id'],
                'stripe_publishable_key' => config('services.stripe.key'),
                'amount' => $total,
                'currency' => $cart->currency,
            ],
        ]);
    }
}
