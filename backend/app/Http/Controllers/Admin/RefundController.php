<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Admin\StoreRefundRequest;
use App\Http\Resources\RefundResource;
use App\Modules\Payment\Application\PaymentService;
use Illuminate\Http\JsonResponse;

class RefundController extends ApiBaseController
{
    public function __construct(
        private PaymentService $paymentService
    ) {
    }

    /**
     * POST /admin/payments/{id}/refund - Create refund for a succeeded payment (admin).
     */
    public function store(StoreRefundRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $refund = $this->paymentService->createRefund(
                $id,
                isset($validated['amount']) ? (float) $validated['amount'] : null,
                $validated['reason'] ?? null
            );
        } catch (\DomainException $e) {
            return $this->fromDomainException($e);
        }

        return $this->data(new RefundResource($refund), 201);
    }
}
