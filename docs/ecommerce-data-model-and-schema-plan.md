## Ecommerce System – Data Model & Schema Plan

This document defines the **core data model**, focusing on aggregates, key tables, and relationships. It guides relational schema design (MySQL/Postgres) without locking into specific migrations.

> **See also**: `ecommerce-database-diagrams.md` for visual ERD diagrams showing current state (Phase 1) and final state (all phases) with all relationships.

### 1. Modeling Principles

- **Aggregate‑centric**: DB schema follows domain aggregates (`Order`, `Cart`, `Product`, `Payment`, `StockItem`).
- **Snapshots for auditability**: Orders store full commercial snapshots (prices, tax, addresses, shipping).
- **Multi‑currency ready**: Store `amount` + `currency` everywhere money appears.
- **Soft deletes where appropriate**: For user, product, order–related tables where historical referencing matters.

### 2. User & Identity

- **Users**
  - `users`: id, email (unique), password_hash, name, status, created_at, updated_at, email_verified_at, last_login_at.
- **Addresses**
  - `user_addresses`: id, user_id, type (billing/shipping), name, phone, line1, line2, city, region, postal_code, country_code, is_default.
- **Roles & Permissions**
  - As per `spatie/laravel-permission` tables (`roles`, `permissions`, `model_has_roles`, etc.).

### 3. Catalog

- **Products**
  - `products`: id, slug (unique), name, description, brand_id, status (draft/published/archived), main_image_url, seo_title, seo_description, created_at, updated_at.
  - `brands`: id, name, slug, created_at, updated_at.
  - `categories`: id, parent_id (nullable), name, slug, position, created_at, updated_at.
  - `category_product`: product_id, category_id (composite PK).
- **Variants & Attributes**
  - `product_variants`: id, product_id, sku (unique), name, attributes_json, is_default, created_at, updated_at.
  - `attributes` / `attribute_values` (optional normalization) or JSON‑based attributes on variants, depending on flexibility requirements.
- **Pricing (Multi‑currency / Channel aware)**
  - `product_prices`: id, product_variant_id, currency, amount, compare_at_amount (nullable), channel (web/mobile/wholesale, optional), valid_from, valid_to (nullable).

### 4. Inventory

- **Warehouses & Stock**
  - `warehouses`: id, name, code, country_code, region, city, created_at, updated_at.
  - `stock_items`: id, product_variant_id, warehouse_id, quantity, safety_stock, created_at, updated_at, UNIQUE(product_variant_id, warehouse_id).
- **Reservations & Movements**
  - `stock_reservations`: id, product_variant_id, warehouse_id, quantity, source_type (cart/order), source_id, expires_at, status (active/expired/consumed), created_at, updated_at.
  - `stock_movements`: id, product_variant_id, warehouse_id, type (in/out/adjustment), quantity, reason_code, reference_type (order/adjustment/etc.), reference_id, created_at.

### 5. Cart

- **Carts & Items**
  - `carts`: id, user_id (nullable), guest_token (nullable, unique), currency, status (active, converted, abandoned, expired), last_activity_at, created_at, updated_at.
  - `cart_items`: id, cart_id, product_variant_id, quantity, unit_price_amount, unit_price_currency, discount_amount, discount_currency, created_at, updated_at.
- **Cart‑level Promotions**
  - `cart_coupons`: id, cart_id, coupon_code, discount_amount, discount_currency, applied_at.

### 6. Orders

- **Orders**
  - `orders`: id, order_number (human‑friendly, unique), user_id (nullable for guest), status (pending_payment, paid, fulfilled, cancelled, refunded, etc.), currency, subtotal_amount, discount_amount, tax_amount, shipping_amount, total_amount, created_at, updated_at.
  - Snapshots:
    - `billing_address_json`, `shipping_address_json`.
    - `items_snapshot_json` (optional helper; detailed lines in separate table).
    - `shipping_method_code`, `shipping_method_name`.
    - `tax_breakdown_json` (per rate).
- **Order Lines**
  - `order_lines`: id, order_id, product_variant_id (nullable if deleted), product_name_snapshot, sku_snapshot, quantity, unit_price_amount, unit_price_currency, discount_amount, discount_currency, tax_amount, total_line_amount, created_at, updated_at.
- **Order Status History**
  - `order_status_history`: id, order_id, from_status, to_status, changed_by_user_id (nullable system), reason, created_at.

### 7. Payments & Refunds

- **Payments**
  - `payments`: id, order_id, provider (stripe/paypal/...), provider_reference, amount, currency, status (pending, succeeded, failed, refunded, partially_refunded, voided), raw_response_json, created_at, updated_at.
- **Refunds**
  - `refunds`: id, payment_id, amount, currency, status (pending, succeeded, failed), reason, raw_response_json, created_at, updated_at.

### 8. Promotions & Coupons

- **Promotions**
  - `promotions`: id, name, type (cart/product/global), rule_type (percentage/fixed/bxgy), value, starts_at, ends_at, priority, is_active, conditions_json (filter by category, brand, min amount, etc.), created_at, updated_at.
- **Coupons**
  - `coupons`: id, code (unique), promotion_id (nullable), usage_limit, usage_limit_per_user, starts_at, ends_at, is_active, created_at, updated_at.
  - `coupon_redemptions`: id, coupon_id, user_id (nullable), order_id, redeemed_at.

### 9. Shipping & Logistics

- **Shipping Methods**
  - `shipping_methods`: id, code, name, description, is_active, created_at, updated_at.
  - `shipping_method_zones`: id, shipping_method_id, country_code, region (nullable), postal_code_pattern (nullable), min_cart_total, max_cart_total, base_amount, per_kg_amount, currency.
- **Tracking**
  - `shipments`: id, order_id, tracking_number, carrier_code, status (pending, shipped, delivered, returned), shipped_at, delivered_at, created_at, updated_at.

### 10. Wishlist, Reviews & Customer Experience (Optional but Common)

- **Wishlist**
  - `wishlists`: id, user_id, created_at, updated_at.
  - `wishlist_items`: id, wishlist_id, product_variant_id, created_at.
- **Product Reviews**
  - `product_reviews`: id, user_id, product_id, rating (1–5), title, body, status (pending/approved/rejected), created_at, updated_at.

### 11. Configuration & Reference Data

- **Currencies**
  - `currencies`: code (PK), name, symbol, decimal_places, is_default.
  - `currency_rates`: id, from_currency, to_currency, rate, fetched_at.
- **Tax**
  - `tax_zones`: id, name, country_code, region (nullable), postal_code_pattern (nullable).
  - `tax_rates`: id, tax_zone_id, rate_percent, name, is_default, valid_from, valid_to (nullable).

### 12. Audit & Multi‑Tenancy (Optional)

- **Audit Trail (Beyond Orders)**
  - Generic `activity_log` table (or use a package like `spatie/laravel-activitylog`) for changes to key entities (product price changes, stock adjustments, admin actions).
- **Multi‑Tenancy (If Needed)**
  - Strategy:
    - Either a `tenant_id` column on all multi‑tenant tables, or
    - Full tenant separation using a package like `stancl/tenancy`.
  - Chosen approach depends on business requirement; for a single brand, treat tenancy as out of scope initially.

