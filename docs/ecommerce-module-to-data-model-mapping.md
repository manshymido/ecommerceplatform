## Ecommerce System – Module → Data Model Mapping

This document maps each module/bounded context to the **tables and core entities** defined in the data model, plus notes about ownership and cross‑module access.

> This follows the schema described in `ecommerce-data-model-and-schema-plan.md`.

---

### 1. User & Identity Module

**Owns**

- `users`
- `user_addresses`
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (from Spatie)

**Reads**

- `orders` (for “My Orders” and identity linkage).
- `coupon_redemptions` (per‑user coupon usage).
- `wishlists`, `wishlist_items`, `product_reviews` (user‑authored data).

**Notes**

- `users.id` is the primary identity key referenced across other modules (foreign key).
- Some flows (guest checkout) use `user_id = null` + email snapshot; later reconciliation can attach orders to users.

---

### 2. Catalog Module

**Owns**

- `products`
- `brands`
- `categories`
- `category_product`
- `product_variants`
- `product_prices`
- (Optional) `attributes`, `attribute_values`

**Reads**

- `wishlists`, `wishlist_items`, `product_reviews` (to enrich product detail views).
- `stock_items` (read‑only, to expose availability information).

**Notes**

- Catalog is the primary source of truth for **descriptive product data** (names, descriptions, media, SEO).
- Pricing is modeled via `product_prices` but may be influenced by the Promotion module at runtime.

---

### 3. Inventory Module

**Owns**

- `warehouses`
- `stock_items`
- `stock_reservations`
- `stock_movements`

**Reads**

- `product_variants` (for SKU relationships and sometimes weight/attributes).
- `orders`, `order_lines` (for reconciling stock after fulfillment or cancellations).

**Notes**

- `stock_items` and `stock_reservations` are the **authoritative source** for availability.
- Other modules (Cart, Order) should not mutate stock tables directly; they interact via Inventory services/use cases.

---

### 4. Cart Module

**Owns**

- `carts`
- `cart_items`
- `cart_coupons`

**Reads**

- `product_variants`, `product_prices` (to build price snapshots).
- `promotions`, `coupons` (to validate and apply discounts).
- `stock_items` / Inventory services (for availability checks).

**Notes**

- Cart stores **price and discount snapshots** (`unit_price_amount`, `discount_amount`) to avoid surprise changes from catalog pricing updates during a session.
- Status in `carts.status` differentiates active, converted, abandoned, and expired carts.

---

### 5. Order Module

**Owns**

- `orders`
- `order_lines`
- `order_status_history`

**Reads**

- `users`, `user_addresses` (to link orders to customers and addresses when not using pure JSON snapshots).
- `payments`, `refunds` (for payment state and financial reconciliation).
- `shipments` (for fulfillment state).
- `stock_reservations`, `stock_movements` (for consistency checks, typically via Inventory services).

**Notes**

- `orders` and `order_lines` capture **immutable commercial snapshots** (prices, taxes, discounts, addresses, shipping method).
- Status transitions and history are primarily modeled in `orders.status` + `order_status_history`.

---

### 6. Payment Module

**Owns**

- `payments`
- `refunds`

**Reads**

- `orders` (amount, currency, customer info, order status).
- `users` (for payer identity in some flows).

**Notes**

- Payment module is responsible for **external provider interactions** and recording their results.
- It never mutates `orders` directly; instead, it raises domain events or calls Order use cases that update orders.

---

### 7. Promotion/Pricing Module

**Owns**

- `promotions`
- `coupons`
- `coupon_redemptions`

**Reads**

- `carts`, `cart_items` (to evaluate cart‑level rules).
- `orders`, `order_lines` (for applied discounts and reporting).
- `products`, `product_variants`, `categories`, `brands` (for targeting by catalog attributes).

**Notes**

- Promotion rules are stored in `promotions.conditions_json` and evaluated by the module’s rule engine.
- `coupon_redemptions` links coupons to orders/users and enforces usage limits.

---

### 8. Shipping & Logistics Module

**Owns**

- `shipping_methods`
- `shipping_method_zones`
- `shipments`

**Reads**

- `orders`, `order_lines` (for weight, dimensions, value, and address when quoting or creating shipments).
- `warehouses` and `stock_items` (to choose origin warehouse in multi‑warehouse setups).

**Notes**

- Shipping quotes are computed using rules in `shipping_method_zones` plus order/cart data.
- `shipments` track fulfillment and carrier information; they are linked back to orders for status display.

---

### 9. Notification Module

**Owns**

- Potentially `notification_templates` (if not using only code‑driven templates).
- Optionally a `notifications_outbox` table to persist outgoing notifications for reliability/audit.

**Reads**

- `orders`, `payments`, `shipments`, `users` to populate notification content.

**Notes**

- In many Laravel apps, notifications are not fully normalized in DB; this module can be as light or as heavy as needed.

---

### 10. Catalog Experience Extras (Wishlist & Reviews)

While mostly part of **Catalog/User experience**, it’s useful to call them out:

- **Wishlist**
  - Owned by Catalog/User:
    - `wishlists`
    - `wishlist_items`
- **Reviews**
  - Owned by Catalog/User:
    - `product_reviews`

Both read from `users` and `products`, and are exposed via Catalog and User modules.

---

### 11. Cross‑Module Summary Table

Simple ERD‑like ownership overview:

- **User & Identity** → `users`, `user_addresses`, `roles`, `permissions`, …
- **Catalog** → `products`, `brands`, `categories`, `category_product`, `product_variants`, `product_prices`, (attributes tables).
- **Inventory** → `warehouses`, `stock_items`, `stock_reservations`, `stock_movements`.
- **Cart** → `carts`, `cart_items`, `cart_coupons`.
- **Order** → `orders`, `order_lines`, `order_status_history`.
- **Payment** → `payments`, `refunds`.
- **Promotion/Pricing** → `promotions`, `coupons`, `coupon_redemptions`.
- **Shipping & Logistics** → `shipping_methods`, `shipping_method_zones`, `shipments`.
- **Notification** → `notification_templates` (if DB‑backed) and/or `notifications_outbox`.
- **Extras (CX)** → `wishlists`, `wishlist_items`, `product_reviews`.

