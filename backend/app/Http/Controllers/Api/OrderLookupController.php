<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Resources\OrderResource;
use App\Modules\Order\Domain\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderLookupController extends ApiBaseController
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
    }

    /**
     * GET /orders/lookup?order_number=XXX&email=YYY - Public guest order lookup (throttled).
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email'],
        ]);

        $orderNumber = $validated['order_number'];
        $email = strtolower(trim($validated['email']));

        $order = $this->orderRepository->findByOrderNumber($orderNumber);
        if (! $order) {
            return $this->notFound(ApiMessages::ORDER_NOT_FOUND);
        }
        if ($order->userId !== null) {
            return $this->notFound(ApiMessages::ORDER_NOT_FOUND);
        }

        $guestEmail = $order->guestEmail !== null ? strtolower(trim($order->guestEmail)) : '';
        if ($guestEmail !== $email) {
            return $this->notFound(ApiMessages::ORDER_NOT_FOUND);
        }

        return $this->data(new OrderResource($order));
    }
}
