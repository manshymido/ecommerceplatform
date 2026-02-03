<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Application\PaymentService;
use App\Modules\Payment\Domain\PaymentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private PaymentService $paymentService
    ) {
    }

    /**
     * POST /webhooks/stripe - Handle Stripe webhook events. Verify signature and update payment/order.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (empty($secret)) {
            Log::warning('Stripe webhook secret not configured');
            return response()->json(['error' => 'Webhook not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;
            default:
                // ignore other events
        }

        return response()->json(['received' => true]);
    }

    private function handlePaymentIntentSucceeded(object $intent): void
    {
        $payment = $this->paymentRepository->findByProviderReference('stripe', $intent->id);
        if ($payment !== null) {
            $this->paymentService->markPaymentSucceeded($payment->id, (array) $intent);
        }
    }

    private function handlePaymentIntentFailed(object $intent): void
    {
        $payment = $this->paymentRepository->findByProviderReference('stripe', $intent->id);
        if ($payment !== null) {
            $this->paymentService->markPaymentFailed($payment->id, (array) $intent);
        }
    }
}
