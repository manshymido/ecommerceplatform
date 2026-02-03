<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiMessages;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Modules\Order\Domain\OrderRepository;
use App\Modules\Payment\Application\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{
    public function __construct(
        private OrderRepository $orderRepository,
        private PaymentService $paymentService
    ) {
    }

    /**
     * POST /orders/{id}/pay - Initiate payment for order. Returns client_secret for Stripe Elements.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findById($id);
        if (! $order || $order->userId !== $request->user()->id) {
            return ApiResponse::notFound(ApiMessages::ORDER_NOT_FOUND);
        }

        try {
            $result = $this->paymentService->initiatePayment(
                $order,
                $request->input('return_url')
            );
        } catch (\DomainException $e) {
            return ApiResponse::fromDomainException($e);
        }

        return ApiResponse::success([
            'payment' => new PaymentResource($result['payment']),
            'client_secret' => $result['client_secret'],
            'payment_intent_id' => $result['payment_intent_id'],
            'stripe_publishable_key' => config('services.stripe.key'),
        ], 201);
    }
}
