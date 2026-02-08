<?php

namespace App\Modules\Order\Application;

use App\Exceptions\BusinessRuleException;
use App\Modules\Inventory\Application\Dto\ReservationItem;
use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\CartRepository;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Inventory\Application\InventoryService;
use App\Modules\Shipping\Infrastructure\Models\ShippingMethod;
use App\Modules\Order\Domain\Order;
use App\Modules\Order\Domain\OrderRepository;
use Illuminate\Support\Facades\DB;

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
     * Runs in a transaction with row locking to prevent overselling when two customers buy the last item.
     *
     * @param  array{email?: string, billing_address?: array, shipping_address?: array, shipping_method_code?: string, shipping_method_name?: string}  $checkoutData
     * @return Order
     *
     * @throws BusinessRuleException
     */
    public function placeOrder(Cart $cart, array $checkoutData = []): Order
    {
        if (! $cart->isActive() || count($cart->items) === 0) {
            throw BusinessRuleException::emptyCart();
        }

        $reservationItems = array_map(
            fn ($item) => new ReservationItem($item->productVariantId, $item->quantity),
            $cart->items
        );
        $variantQuantities = [];
        foreach ($cart->items as $item) {
            $variantQuantities[$item->productVariantId] = ($variantQuantities[$item->productVariantId] ?? 0) + $item->quantity;
        }

        return DB::transaction(function () use ($cart, $checkoutData, $reservationItems, $variantQuantities): Order {
            $availability = $this->inventoryService->checkAvailability($variantQuantities, null);
            foreach ($availability as $result) {
                if (! $result->isAvailable) {
                    throw BusinessRuleException::insufficientStock(
                        $result->productVariantId,
                        $variantQuantities[$result->productVariantId] ?? 0,
                        $result->availableQty ?? 0
                    );
                }
            }

            $variantIds = array_map(fn ($i) => $i->productVariantId, $cart->items);
            $variants = ProductVariant::with('product')->whereIn('id', $variantIds)->get()->keyBy('id');

            foreach ($cart->items as $item) {
                if (! $variants->has($item->productVariantId)) {
                    throw new BusinessRuleException(
                        'Some items in your cart are no longer available. Please remove them and try again.',
                        'ITEM_NO_LONGER_AVAILABLE'
                    );
                }
            }

            $linesData = [];
            foreach ($cart->items as $item) {
                $variant = $variants->get($item->productVariantId);
                $productName = $variant?->product?->name ?? 'Product';
                $sku = $variant?->sku ?? 'N/A';
                $lineTotal = $item->lineTotal();
                $linesData[] = [
                    'product_id' => $variant?->product_id,
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

            $shippingMethodCode = $checkoutData['shipping_method_code'] ?? null;
            $shippingMethod = $shippingMethodCode
                ? ShippingMethod::where('code', $shippingMethodCode)->first()
                : null;

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
                'shipping_method_id' => $shippingMethod?->id,
                'shipping_method_code' => $shippingMethodCode,
                'shipping_method_name' => $checkoutData['shipping_method_name'] ?? $shippingMethod?->name,
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
                throw new BusinessRuleException(
                    'Could not reserve stock. Please try again.',
                    'STOCK_RESERVATION_FAILED'
                );
            }

            $this->cartRepository->markAsConverted($cart->id);

            $order = $this->orderRepository->findById($order->id);
            \App\Events\OrderPlaced::dispatch($order->id);

            if ($cart->appliedCouponCode !== null && $discount > 0) {
                \App\Events\CouponRedeemed::dispatch(
                    $order->id,
                    $cart->appliedCouponCode,
                    $discount,
                    $cart->userId
                );
            }

            return $order;
        });
    }
}
