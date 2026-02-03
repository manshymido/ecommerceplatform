<?php

namespace App\Http;

/**
 * Centralized API response messages to avoid duplication across controllers.
 */
final class ApiMessages
{
    public const ORDER_NOT_FOUND = 'Order not found';

    public const CART_NOT_FOUND = 'Cart not found';

    public const SHIPMENT_NOT_FOUND = 'Shipment not found';

    public const PRODUCT_NOT_FOUND = 'Product not found';

    public const CATEGORY_NOT_FOUND = 'Category not found';
}
