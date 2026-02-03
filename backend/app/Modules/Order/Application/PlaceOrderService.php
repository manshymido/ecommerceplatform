<?php

namespace App\Modules\Order\Application;

use App\Modules\Inventory\Application\Dto\ReservationItem;
use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\CartRepository;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Inventory\Application\InventoryService;
use App\Modules\Order\Domain\Order;
use App\Modules\Order\Domain\OrderRepository;

class PlaceOrderService
{
    public function __construct(
        private CartRepository $cartRepository,
        private OrderRepository $orderRepository,
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Place order from cart. Checks inventory, creates order, reserves stock, marks cart converted.
     *
     * @param  array{email?: string, billing_address?: array, shipping_address?: array, shipping_method_code?: string, shipping_method_name?: string}  $checkoutData
     * @return Order
     *
     * @throws \DomainException
     */
    public function placeOrder(Cart $cart, array $checkoutData = []): Order
    {
        if (! $cart->isActive() || count($cart->items) === 0) {
            throw new \DomainException('Cart is empty or already converted.');
        }

        $reservationItems = array_map(
            fn ($item) => new ReservationItem($item->productVariantId, $item->quantity),
            $cart->items
        );
        $variantQuantities = [];
        foreach ($cart->items as $item) {
            $variantQuantities[$item->productVariantId] = ($variantQuantities[$item->productVariantId] ?? 0) + $item->quantity;
        }
        $availability = $this->inventoryService->checkAvailability($variantQuantities, null);
        foreach ($availability as $result) {
            if (! $result->isAvailable) {
                throw new \DomainException("Insufficient stock for variant {$result->productVariantId}.");
            }
        }

        $variantIds = array_map(fn ($i) => $i->productVariantId, $cart->items);
        $variants = ProductVariant::with('product')->whereIn('id', $variantIds)->get()->keyBy('id');

        $linesData = [];
        foreach ($cart->items as $item) {
            $variant = $variants->get($item->productVariantId);
            $productName = $variant?->product?->name ?? 'Product';
            $sku = $variant?->sku ?? 'N/A';
            $lineTotal = $item->lineTotal();
            $linesData[] = [
                'product_variant_id' => $item->productVariantId,
                'product_name_snapshot' => $productName,
                'sku_snapshot' => $sku,
                'quantity' => $item->quantity,
                'unit_price_amount' => $item->unitPriceAmount,
                'unit_price_currency' => $item->unitPriceCurrency,
                'discount_amount' => $item->discountAmount,
                'discount_currency' => $item->discountCurrency,
                'tax_amount' => 0,
                'total_line_amount' => $lineTotal,
            ];
        }

        $subtotal = $cart->subtotalAmount ?? 0;
        $discount = $cart->discountAmount ?? 0;
        $shipping = (float) ($checkoutData['shipping_amount'] ?? 0);
        $tax = (float) ($checkoutData['tax_amount'] ?? 0);
        $total = max(0, $subtotal - $discount + $shipping + $tax);

        $orderData = [
            'user_id' => $cart->userId,
            'guest_email' => $cart->userId === null ? ($checkoutData['email'] ?? null) : null,
            'currency' => $cart->currency,
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'shipping_amount' => $shipping,
            'total_amount' => $total,
            'billing_address_json' => $checkoutData['billing_address'] ?? null,
            'shipping_address_json' => $checkoutData['shipping_address'] ?? null,
            'shipping_method_code' => $checkoutData['shipping_method_code'] ?? null,
            'shipping_method_name' => $checkoutData['shipping_method_name'] ?? null,
        ];

        $order = $this->orderRepository->create($orderData, $linesData);

        $reserved = $this->inventoryService->reserveStock(
            $reservationItems,
            \App\Modules\Inventory\Domain\StockReservation::SOURCE_ORDER,
            $order->id
        );
        if (! $reserved) {
            $reason = 'Stock reservation failed';
            $this->orderRepository->recordStatusChange(
                $order->id,
                Order::STATUS_PENDING_PAYMENT,
                Order::STATUS_CANCELLED,
                $order->userId,
                $reason
            );
            \App\Events\OrderCancelled::dispatch($order->id, $reason);
            throw new \DomainException('Could not reserve stock. Please try again.');
        }

        $this->cartRepository->markAsConverted($cart->id);

        $order = $this->orderRepository->findById($order->id);
        \App\Events\OrderPlaced::dispatch($order->id);

        return $order;
    }
}
