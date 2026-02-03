<?php

namespace App\Modules\Order\Infrastructure\Repositories;

use App\Modules\Order\Domain\Order;
use App\Modules\Order\Domain\OrderLine;
use App\Modules\Order\Domain\OrderRepository;
use App\Modules\Order\Infrastructure\Models\Order as OrderModel;
use App\Modules\Order\Infrastructure\Models\OrderLine as OrderLineModel;
use App\Modules\Order\Infrastructure\Models\OrderStatusHistory;
use Illuminate\Support\Str;

class EloquentOrderRepository implements OrderRepository
{
    public function findById(int $id): ?Order
    {
        $model = OrderModel::with('lines')->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        $model = OrderModel::with('lines')->where('order_number', $orderNumber)->first();

        return $model ? $this->toDomain($model) : null;
    }

    /**
     * @return Order[]
     */
    public function findByUser(int $userId, int $limit = 50): array
    {
        return OrderModel::with('lines')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    /**
     * @return Order[]
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        return OrderModel::with('lines')
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function create(array $orderData, array $linesData): Order
    {
        $orderData['order_number'] = $this->generateOrderNumber();

        $model = OrderModel::create([
            'order_number' => $orderData['order_number'],
            'user_id' => $orderData['user_id'] ?? null,
            'guest_email' => $orderData['guest_email'] ?? null,
            'status' => \App\Modules\Order\Domain\Order::STATUS_PENDING_PAYMENT,
            'currency' => $orderData['currency'],
            'subtotal_amount' => $orderData['subtotal_amount'],
            'discount_amount' => $orderData['discount_amount'] ?? 0,
            'tax_amount' => $orderData['tax_amount'] ?? 0,
            'shipping_amount' => $orderData['shipping_amount'] ?? 0,
            'total_amount' => $orderData['total_amount'],
            'billing_address_json' => $orderData['billing_address_json'] ?? null,
            'shipping_address_json' => $orderData['shipping_address_json'] ?? null,
            'shipping_method_code' => $orderData['shipping_method_code'] ?? null,
            'shipping_method_name' => $orderData['shipping_method_name'] ?? null,
            'tax_breakdown_json' => $orderData['tax_breakdown_json'] ?? null,
        ]);

        foreach ($linesData as $line) {
            OrderLineModel::create([
                'order_id' => $model->id,
                'product_variant_id' => $line['product_variant_id'] ?? null,
                'product_name_snapshot' => $line['product_name_snapshot'],
                'sku_snapshot' => $line['sku_snapshot'],
                'quantity' => $line['quantity'],
                'unit_price_amount' => $line['unit_price_amount'],
                'unit_price_currency' => $line['unit_price_currency'],
                'discount_amount' => $line['discount_amount'] ?? 0,
                'discount_currency' => $line['discount_currency'] ?? null,
                'tax_amount' => $line['tax_amount'] ?? 0,
                'total_line_amount' => $line['total_line_amount'],
            ]);
        }

        OrderStatusHistory::create([
            'order_id' => $model->id,
            'from_status' => null,
            'to_status' => \App\Modules\Order\Domain\Order::STATUS_PENDING_PAYMENT,
            'changed_by_user_id' => $orderData['user_id'] ?? null,
            'reason' => 'Order placed',
            'created_at' => now(),
        ]);

        return $this->toDomain($model->load('lines'));
    }

    public function recordStatusChange(int $orderId, ?string $fromStatus, string $toStatus, ?int $userId = null, ?string $reason = null): void
    {
        OrderStatusHistory::create([
            'order_id' => $orderId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $userId,
            'reason' => $reason,
            'created_at' => now(),
        ]);
        OrderModel::where('id', $orderId)->update(['status' => $toStatus]);
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function toDomain(OrderModel $model): Order
    {
        $lines = $model->lines->map(fn (OrderLineModel $l) => new OrderLine(
            id: $l->id,
            orderId: $l->order_id,
            productVariantId: $l->product_variant_id,
            productNameSnapshot: $l->product_name_snapshot,
            skuSnapshot: $l->sku_snapshot,
            quantity: $l->quantity,
            unitPriceAmount: (float) $l->unit_price_amount,
            unitPriceCurrency: $l->unit_price_currency,
            discountAmount: (float) $l->discount_amount,
            discountCurrency: $l->discount_currency,
            taxAmount: (float) $l->tax_amount,
            totalLineAmount: (float) $l->total_line_amount,
        ))->all();

        return new Order(
            id: $model->id,
            orderNumber: $model->order_number,
            userId: $model->user_id,
            guestEmail: $model->guest_email,
            status: $model->status,
            currency: $model->currency,
            subtotalAmount: (float) $model->subtotal_amount,
            discountAmount: (float) $model->discount_amount,
            taxAmount: (float) $model->tax_amount,
            shippingAmount: (float) $model->shipping_amount,
            totalAmount: (float) $model->total_amount,
            lines: $lines,
            billingAddress: $model->billing_address_json,
            shippingAddress: $model->shipping_address_json,
            shippingMethodCode: $model->shipping_method_code,
            shippingMethodName: $model->shipping_method_name,
        );
    }
}
