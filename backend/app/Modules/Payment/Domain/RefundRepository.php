<?php

namespace App\Modules\Payment\Domain;

interface RefundRepository
{
    public function findById(int $id): ?Refund;

    /** @return Refund[] */
    public function findByPayment(int $paymentId): array;

    /**
     * @param  array{payment_id: int, amount: float, currency: string, status: string, reason: string|null, raw_response_json: array|null}  $data
     */
    public function create(array $data): Refund;

    public function updateStatus(int $refundId, string $status, ?array $rawResponse = null): void;
}
