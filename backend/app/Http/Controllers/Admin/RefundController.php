<?php

namespace App\Http\Controllers\Admin;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\RefundResource;
use App\Modules\Payment\Application\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {
    }

    /**
     * POST /admin/payments/{id}/refund - Create refund for a succeeded payment (admin).
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $refund = $this->paymentService->createRefund(
                $id,
                $request->input('amount') ? (float) $request->input('amount') : null,
                $request->input('reason')
            );
        } catch (\DomainException $e) {
            return ApiResponse::fromDomainException($e);
        }

        return ApiResponse::data(new RefundResource($refund), 201);
    }
}
