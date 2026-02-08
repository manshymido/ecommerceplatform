<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Centralized API response messages to avoid duplication across controllers.
 *
 * Following PSR-12 and using final class to prevent extension.
 */
final class ApiMessages
{
    // =========================================================================
    // Resource Not Found Messages
    // =========================================================================

    public const ORDER_NOT_FOUND = 'Order not found';

    public const CART_NOT_FOUND = 'Cart not found';

    public const SHIPMENT_NOT_FOUND = 'Shipment not found';

    public const PRODUCT_NOT_FOUND = 'Product not found';

    public const CATEGORY_NOT_FOUND = 'Category not found';

    public const REVIEW_NOT_FOUND = 'Review not found';

    public const WISHLIST_ITEM_NOT_FOUND = 'Wishlist item not found';

    public const BRAND_NOT_FOUND = 'Brand not found';

    public const WAREHOUSE_NOT_FOUND = 'Warehouse not found';

    public const REFUND_NOT_FOUND = 'Refund not found';

    public const USER_NOT_FOUND = 'User not found';

    public const COUPON_NOT_FOUND = 'Coupon not found';

    public const PAYMENT_NOT_FOUND = 'Payment not found';

    public const STOCK_NOT_FOUND = 'Stock record not found';

    public const VARIANT_NOT_FOUND = 'Product variant not found';

    // =========================================================================
    // Validation Messages
    // =========================================================================

    public const INVALID_CREDENTIALS = 'Invalid credentials';

    public const INVALID_QUANTITY = 'Invalid quantity provided';

    public const INVALID_COUPON = 'Invalid or expired coupon code';

    public const INVALID_PAYMENT_METHOD = 'Invalid payment method';

    public const INVALID_SHIPPING_ADDRESS = 'Invalid shipping address';

    // =========================================================================
    // Business Rule Messages
    // =========================================================================

    public const INSUFFICIENT_STOCK = 'Insufficient stock available';

    public const CART_EMPTY = 'Cart is empty';

    public const ORDER_NOT_MODIFIABLE = 'Order cannot be modified in its current status';

    public const ORDER_ALREADY_PAID = 'Order has already been paid';

    public const ORDER_CANCELLED = 'Order has been cancelled';

    public const REFUND_NOT_ALLOWED = 'Refund is not allowed for this order';

    public const COUPON_ALREADY_USED = 'This coupon has already been used';

    public const COUPON_EXPIRED = 'This coupon has expired';

    public const COUPON_NOT_APPLICABLE = 'This coupon is not applicable to your order';

    public const PAYMENT_FAILED = 'Payment processing failed';

    public const PAYMENT_ALREADY_PROCESSED = 'Payment has already been processed';

    // =========================================================================
    // Authentication Messages
    // =========================================================================

    public const UNAUTHENTICATED = 'Unauthenticated';

    public const UNAUTHORIZED = 'You are not authorized to perform this action';

    public const EMAIL_NOT_VERIFIED = 'Please verify your email address';

    public const ACCOUNT_DISABLED = 'Your account has been disabled';

    // =========================================================================
    // Success Messages
    // =========================================================================

    public const CREATED = 'Resource created successfully';

    public const UPDATED = 'Resource updated successfully';

    public const DELETED = 'Resource deleted successfully';

    public const ORDER_PLACED = 'Order placed successfully';

    public const PAYMENT_SUCCESSFUL = 'Payment processed successfully';

    public const REFUND_PROCESSED = 'Refund processed successfully';

    public const COUPON_APPLIED = 'Coupon applied successfully';

    public const ITEM_ADDED_TO_CART = 'Item added to cart';

    public const ITEM_REMOVED_FROM_CART = 'Item removed from cart';

    public const ITEM_ADDED_TO_WISHLIST = 'Item added to wishlist';

    public const ITEM_REMOVED_FROM_WISHLIST = 'Item removed from wishlist';

    // =========================================================================
    // Private constructor to prevent instantiation
    // =========================================================================

    private function __construct()
    {
        // Static class - prevent instantiation
    }
}
