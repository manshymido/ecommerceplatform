<?php

namespace App\Http\Controllers\Admin;

use App\Http\ApiMessages;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Modules\Order\Domain\OrderRepository;
use App\Modules\Order\Infrastructure\Models\Order as OrderModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
    }

    /**
     * GET /admin/orders - List all orders (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 15), 100);
        $orders = OrderModel::with('lines')->orderByDesc('created_at')->paginate($perPage);

        return ApiResponse::paginated($orders, OrderResource::collection($orders));
    }

    /**
     * GET /admin/orders/{id} - Show order by id.
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->findById($id);
        if (! $order) {
            return ApiResponse::notFound(ApiMessages::ORDER_NOT_FOUND);
        }

        return ApiResponse::data(new OrderResource($order));
    }
}
