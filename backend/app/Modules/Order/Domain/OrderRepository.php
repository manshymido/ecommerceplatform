<?php

namespace App\Modules\Order\Domain;

interface OrderRepository
{
    public function findById(int $id): ?Order;

    public function findByOrderNumber(string $orderNumber): ?Order;

    /** @return Order[] */
    public function findByUser(int $userId, int $limit = 50): array;

    /** @return Order[] */
    public function findAll(int $limit = 50, int $offset = 0): array;

    /**
     * Create order with lines and initial status history.
     *
     * @param  array{user_id: int|null, guest_email: string|null, currency: string, subtotal_amount: float, discount_amount: float, tax_amount: float, shipping_amount: float, total_amount: float, billing_address_json: array|null, shipping_address_json: array|null, shipping_method_code: string|null, shipping_method_name: string|null}  $orderData
     * @param  array<int, array{product_variant_id: int|null, product_name_snapshot: string, sku_snapshot: string, quantity: int, unit_price_amount: float, unit_price_currency: string, discount_amount: float, discount_currency: string|null, tax_amount: float, total_line_amount: float}>  $linesData
     */
    public function create(array $orderData, array $linesData): Order;

    public function recordStatusChange(int $orderId, ?string $fromStatus, string $toStatus, ?int $userId = null, ?string $reason = null): void;
}
