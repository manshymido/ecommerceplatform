<?php

namespace App\Modules\Payment\Domain;

interface PaymentRepository
{
    public function findById(int $id): ?Payment;

    public function findByProviderReference(string $provider, string $providerReference): ?Payment;

    /** @return Payment[] */
    public function findByOrder(int $orderId): array;

    /**
     * @param  array{order_id: int, provider: string, provider_reference: string|null, amount: float, currency: string, status: string, raw_response_json: array|null}  $data
     */
    public function create(array $data): Payment;

    public function updateStatus(int $paymentId, string $status, ?array $rawResponse = null): void;
}
