<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Modules\Order\Domain\OrderRepository;
use App\Modules\Order\Infrastructure\Models\Order as OrderModel;
use App\Modules\Payment\Infrastructure\Models\Payment as PaymentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin controller for managing orders.
 *
 * Provides order listing, detail viewing, and payment information
 * for the admin dashboard.
 */
class OrderController extends ApiBaseController
{
    public function __construct(
        private readonly OrderRepository $orderRepository
    ) {
    }

    /**
     * List all orders with pagination and optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->buildOrderQuery($request);

        $perPage = $this->getPerPage($request);
        $orders = $query->paginate($perPage);

        return $this->paginated($orders, OrderResource::collection($orders));
    }

    /**
     * Build order query with filters applied.
     */
    private function buildOrderQuery(Request $request): Builder
    {
        $query = OrderModel::with(['lines', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Apply sorting
        $sortParams = $this->getSortParams($request, 'created_at', 'desc');
        $query->orderBy($sortParams['sort'], $sortParams['direction']);

        return $query;
    }

    /**
     * Show order details by ID (with payments, shipments, status history embedded).
     *
     * @throws ResourceNotFoundException
     */
    public function show(int $id): JsonResponse
    {
        $order = OrderModel::with([
            'lines.productVariant.product',
            'user',
            'payments.refunds',
            'shipments',
            'statusHistory.changedByUser',
        ])->find($id);

        if (! $order) {
            throw new ResourceNotFoundException(ApiMessages::ORDER_NOT_FOUND);
        }

        return $this->data(new OrderResource($order));
    }

    /**
     * List payments for an order (for refund UI).
     *
     * @throws ResourceNotFoundException
     */
    public function payments(int $id): JsonResponse
    {
        // Verify order exists
        $this->findOrderOrFail($id);

        $payments = PaymentModel::with('refunds')
            ->where('order_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return $this->collection(PaymentResource::collection($payments));
    }

    /**
     * Find order or throw not found exception.
     *
     * @throws ResourceNotFoundException
     */
    private function findOrderOrFail(int $id): mixed
    {
        $order = $this->orderRepository->findById($id);

        if (!$order) {
            throw new ResourceNotFoundException(ApiMessages::ORDER_NOT_FOUND);
        }

        return $order;
    }
}
