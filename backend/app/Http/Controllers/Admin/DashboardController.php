<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiBaseController;
use App\Http\Resources\OrderResource;
use App\Modules\Catalog\Infrastructure\Models\Product as ProductModel;
use App\Modules\Order\Domain\Order as OrderDomain;
use App\Modules\Order\Infrastructure\Models\Order as OrderModel;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiBaseController
{
    /**
     * GET /admin/dashboard - Stats and recent orders for admin dashboard.
     */
    public function __invoke(): JsonResponse
    {
        $totalProducts = ProductModel::count();
        $totalOrders = OrderModel::count();
        $revenue = (float) OrderModel::whereIn('status', [
            OrderDomain::STATUS_PAID,
            OrderDomain::STATUS_FULFILLED,
        ])->sum('total_amount');
        $recentOrders = OrderModel::with('lines')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return $this->success([
            'message' => 'Admin Dashboard',
            'stats' => [
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'revenue' => round($revenue, 2),
            ],
            'recent_orders' => OrderResource::collection($recentOrders)->resolve(),
        ]);
    }
}
