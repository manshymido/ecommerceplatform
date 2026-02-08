<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Resources\OrderResource;
use App\Modules\Order\Domain\OrderRepository;
use App\Modules\Order\Infrastructure\Models\Order as OrderModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends ApiBaseController
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
    }

    /**
     * GET /orders - List orders for the authenticated user (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $this->getPerPage($request);
        $orders = OrderModel::where('user_id', $user->id)
            ->with('lines')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->paginated($orders, OrderResource::collection($orders));
    }

    /**
     * GET /orders/{id} - Show order (only if it belongs to the user).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findById($id);
        if (! $order || $order->userId !== $request->user()->id) {
            return $this->notFound(ApiMessages::ORDER_NOT_FOUND);
        }

        return $this->data(new OrderResource($order));
    }
}
