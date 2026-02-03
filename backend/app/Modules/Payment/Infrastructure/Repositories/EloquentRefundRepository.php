<?php

namespace App\Modules\Payment\Infrastructure\Repositories;

use App\Modules\Payment\Domain\Refund;
use App\Modules\Payment\Domain\RefundRepository;
use App\Modules\Payment\Infrastructure\Models\Refund as RefundModel;

class EloquentRefundRepository implements RefundRepository
{
    public function findById(int $id): ?Refund
    {
        $model = RefundModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    /**
     * @return Refund[]
     */
    public function findByPayment(int $paymentId): array
    {
        return RefundModel::where('payment_id', $paymentId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function create(array $data): Refund
    {
        $model = RefundModel::create([
            'payment_id' => $data['payment_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => $data['status'],
            'reason' => $data['reason'] ?? null,
            'raw_response_json' => $data['raw_response_json'] ?? null,
        ]);

        return $this->toDomain($model);
    }

    public function updateStatus(int $refundId, string $status, ?array $rawResponse = null): void
    {
        $update = ['status' => $status];
        if ($rawResponse !== null) {
            $update['raw_response_json'] = $rawResponse;
        }
        RefundModel::where('id', $refundId)->update($update);
    }

    private function toDomain(RefundModel $model): Refund
    {
        return new Refund(
            id: $model->id,
            paymentId: $model->payment_id,
            amount: (float) $model->amount,
            currency: $model->currency,
            status: $model->status,
            reason: $model->reason,
            rawResponse: $model->raw_response_json,
        );
    }
}
