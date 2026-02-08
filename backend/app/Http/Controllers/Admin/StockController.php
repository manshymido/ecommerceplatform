<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Http\Requests\Admin\AssignStockRequest;
use App\Http\Resources\StockByVariantResource;
use App\Http\Resources\StockItemResource;
use App\Http\Resources\StockMovementResource;
use App\Modules\Inventory\Domain\StockItemRepository;
use App\Modules\Inventory\Infrastructure\Models\StockItem;
use App\Modules\Inventory\Infrastructure\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends ApiBaseController
{
    public function __construct(
        private StockItemRepository $stockItemRepository
    ) {
    }

    /**
     * List stock grouped by product variant (one row per variant, with total and per-warehouse breakdown).
     */
    public function byVariant(Request $request): JsonResponse
    {
        $query = StockItem::with(['productVariant.product', 'warehouse'])
            ->select('stock_items.*')
            ->orderBy('product_variant_id');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('search') && trim($request->search) !== '') {
            $searchTerm = trim($request->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('productVariant.product', fn ($pq) => $pq->where('name', 'LIKE', "%{$searchTerm}%"))
                    ->orWhereHas('productVariant', fn ($vq) => $vq->where('sku', 'LIKE', "%{$searchTerm}%")->orWhere('name', 'LIKE', "%{$searchTerm}%"));
            });
        }

        $items = $query->get();

        $grouped = $items->groupBy('product_variant_id')->map(function ($variantItems, $productVariantId) {
            $first = $variantItems->first();
            $productVariant = $first->productVariant;
            $totalQuantity = $variantItems->sum('quantity');
            $warehouses = $variantItems->map(fn ($i) => [
                'warehouse_id' => $i->warehouse_id,
                'warehouse_code' => $i->warehouse?->code,
                'warehouse_name' => $i->warehouse?->name,
                'quantity' => $i->quantity,
                'safety_stock' => $i->safety_stock ?? 0,
            ])->values()->all();

            return [
                'product_variant_id' => (int) $productVariantId,
                'product_variant' => $productVariant,
                'total_quantity' => $totalQuantity,
                'warehouses' => $warehouses,
            ];
        })->values();

        $page = (int) $request->get('page', 1);
        $perPage = $this->getPerPage($request);
        $total = $grouped->count();
        $paginated = $grouped->slice(($page - 1) * $perPage, $perPage)->values();

        return $this->paginated(
            new \Illuminate\Pagination\LengthAwarePaginator($paginated, $total, $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]),
            StockByVariantResource::collection($paginated)
        );
    }

    /**
     * List stock items (optionally filter by warehouse_id, search by product name).
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockItem::with(['productVariant.product', 'warehouse']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Search by product name (primary search method)
        if ($request->has('search') && !empty(trim($request->search))) {
            $searchTerm = trim($request->search);
            $query->where(function ($q) use ($searchTerm) {
                // Primary: Search by product name
                $q->whereHas('productVariant.product', function ($productQuery) use ($searchTerm) {
                    $productQuery->where('name', 'LIKE', "%{$searchTerm}%");
                })
                // Also search by product variant SKU
                ->orWhereHas('productVariant', function ($variantQuery) use ($searchTerm) {
                    $variantQuery->where('sku', 'LIKE', "%{$searchTerm}%");
                })
                // Also search by product variant name
                ->orWhereHas('productVariant', function ($variantQuery) use ($searchTerm) {
                    $variantQuery->where('name', 'LIKE', "%{$searchTerm}%");
                });
            });
        }

        $items = $query->orderBy('warehouse_id')->orderBy('product_variant_id')->paginate($this->getPerPage($request));

        return $this->paginated($items, StockItemResource::collection($items));
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

        return $this->data(new StockItemResource($item));
    }

    /**
     * Set (assign) absolute quantity for a variant in a warehouse. Creates stock_item if missing.
     */
    public function assign(AssignStockRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->stockItemRepository->setQuantity(
            $validated['product_variant_id'],
            $validated['warehouse_id'],
            $validated['quantity'],
            $validated['reason_code'] ?? 'assignment'
        );

        $item = StockItem::with(['productVariant.product', 'warehouse'])
            ->where('product_variant_id', $validated['product_variant_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->firstOrFail();

        return $this->data(new StockItemResource($item));
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

        // Enhanced search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                // Search by product name
                $q->whereHas('productVariant.product', function ($productQuery) use ($searchTerm) {
                    $productQuery->where('name', 'LIKE', "%{$searchTerm}%");
                })
                // Search by product variant SKU or name
                ->orWhereHas('productVariant', function ($variantQuery) use ($searchTerm) {
                    $variantQuery->where('sku', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('name', 'LIKE', "%{$searchTerm}%");
                })
                // Search by warehouse name or code
                ->orWhereHas('warehouse', function ($warehouseQuery) use ($searchTerm) {
                    $warehouseQuery->where('name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('code', 'LIKE', "%{$searchTerm}%");
                });
            });
        }

        $movements = $query->paginate($this->getPerPage($request));

        return $this->paginated($movements, StockMovementResource::collection($movements));
    }
}
