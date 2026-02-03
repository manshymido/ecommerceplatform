<?php

namespace App\Modules\Payment\Infrastructure\Repositories;

use App\Modules\Payment\Domain\Payment;
use App\Modules\Payment\Domain\PaymentRepository;
use App\Modules\Payment\Infrastructure\Models\Payment as PaymentModel;

class EloquentPaymentRepository implements PaymentRepository
{
    public function findById(int $id): ?Payment
    {
        $model = PaymentModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByProviderReference(string $provider, string $providerReference): ?Payment
    {
        $model = PaymentModel::where('provider', $provider)->where('provider_reference', $providerReference)->first();
        return $model ? $this->toDomain($model) : null;
    }

    /** @return Payment[] */
    public function findByOrder(int $orderId): array
    {
        return PaymentModel::where('order_id', $orderId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function create(array $data): Payment
    {
        $model = PaymentModel::create([
            'order_id' => $data['order_id'],
            'provider' => $data['provider'],
            'provider_reference' => $data['provider_reference'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => $data['status'],
            'raw_response_json' => $data['raw_response_json'] ?? null,
        ]);
        return $this->toDomain($model);
    }

    public function updateStatus(int $paymentId, string $status, ?array $rawResponse = null): void
    {
        $update = ['status' => $status];
        if ($rawResponse !== null) {
            $update['raw_response_json'] = $rawResponse;
        }
        PaymentModel::where('id', $paymentId)->update($update);
    }

    private function toDomain(PaymentModel $model): Payment
    {
        return new Payment(
            id: $model->id,
            orderId: $model->order_id,
            provider: $model->provider,
            providerReference: $model->provider_reference,
            amount: (float) $model->amount,
            currency: $model->currency,
            status: $model->status,
            rawResponse: $model->raw_response_json,
        );
    }
}
