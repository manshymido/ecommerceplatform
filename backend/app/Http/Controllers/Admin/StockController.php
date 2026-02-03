<?php

namespace App\Http\Controllers\Admin;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Http\Resources\StockItemResource;
use App\Http\Resources\StockMovementResource;
use App\Modules\Inventory\Domain\StockItemRepository;
use App\Modules\Inventory\Infrastructure\Models\StockItem;
use App\Modules\Inventory\Infrastructure\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(
        private StockItemRepository $stockItemRepository
    ) {
    }

    /**
     * List stock items (optionally filter by warehouse_id, product_variant_id).
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockItem::with(['productVariant.product', 'warehouse']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->has('product_variant_id')) {
            $query->where('product_variant_id', $request->product_variant_id);
        }

        $items = $query->orderBy('warehouse_id')->orderBy('product_variant_id')->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($items, StockItemResource::collection($items));
    }

    /**
     * Adjust stock (add or deduct) and record movement.
     */
    public function adjust(AdjustStockRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->stockItemRepository->adjustQuantity(
            $validated['product_variant_id'],
            $validated['warehouse_id'],
            $validated['quantity_delta'],
            $validated['reason_code'],
            'adjustment',
            null
        );

        $item = StockItem::with(['productVariant', 'warehouse'])
            ->where('product_variant_id', $validated['product_variant_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->firstOrFail();

        return ApiResponse::data(new StockItemResource($item));
    }

    /**
     * List stock movements (paginated, optional filters).
     */
    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::with(['productVariant.product', 'warehouse'])
            ->orderByDesc('created_at');

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->has('product_variant_id')) {
            $query->where('product_variant_id', $request->product_variant_id);
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($movements, StockMovementResource::collection($movements));
    }
}
