<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiMessages;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Modules\Order\Domain\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
    }

    /**
     * GET /orders - List orders for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min((int) $request->get('per_page', 15), 50);
        $orders = $this->orderRepository->findByUser($user->id, $limit);

        return ApiResponse::collection(OrderResource::collection(collect($orders)));
    }

    /**
     * GET /orders/{id} - Show order (only if it belongs to the user).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findById($id);
        if (! $order || $order->userId !== $request->user()->id) {
            return ApiResponse::notFound(ApiMessages::ORDER_NOT_FOUND);
        }

        return ApiResponse::data(new OrderResource($order));
    }
}
