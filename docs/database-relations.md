# Database Table Relations

Overview of table relationships in the RazerGold system (from migrations).  
**FK** = foreign key (DB-enforced). **Ref** = logical reference (no FK).

---

## 1. Users & Auth

| Table | Relation | To Table | Type | On Delete |
|-------|----------|----------|------|-----------|
| **user_addresses** | user_id | users | FK | cascade |
| **sessions** | user_id | users | FK (nullable) | null on delete |
| **carts** | user_id | users | FK (nullable) | cascade |
| **wishlists** | user_id | users | FK | cascade |
| **orders** | user_id | users | FK (nullable) | set null |
| **coupon_redemptions** | user_id | users | FK (nullable) | set null |
| **order_status_history** | changed_by_user_id | users | FK (nullable) | set null |
| **product_reviews** | user_id | users | FK | cascade |

**Constraints (SQLite/PostgreSQL):** One default address per (user_id, type); one default variant per product (partial unique indexes).

---

## 2. Catalog (Brands, Categories, Products)

| Table | Relation | To Table | Type | On Delete |
|-------|----------|----------|------|-----------|
| **categories** | parent_id | categories | FK (nullable) | cascade |
| **products** | brand_id | brands | FK (nullable) | set null |
| **product_variants** | product_id | products | FK | cascade |
| **product_prices** | product_variant_id | product_variants | FK | cascade |
| **category_product** | product_id | products | FK | cascade |
| **category_product** | category_id | categories | FK | cascade |

**Pivot:** `category_product` = many-to-many between **products** and **categories**.

---

## 3. Inventory (Warehouses & Stock)

| Table | Relation | To Table | Type | On Delete |
|-------|----------|----------|------|-----------|
| **stock_items** | product_variant_id | product_variants | FK | cascade |
| **stock_items** | warehouse_id | warehouses | FK | cascade |
| **stock_reservations** | product_variant_id | product_variants | FK | cascade |
| **stock_reservations** | warehouse_id | warehouses | FK | cascade |
| **stock_movements** | product_variant_id | product_variants | FK | cascade |
| **stock_movements** | warehouse_id | warehouses | FK | cascade |

**Note:** `stock_reservations` has polymorphic-style `source_type` + `source_id` (e.g. cart, order).  
`stock_movements` has `reference_type` + `reference_id` for order/adjustment etc.

---

## 4. Promotions & Coupons

| Table | Relation | To Table | Type | On Delete |
|-------|----------|----------|------|-----------|
| **coupons** | promotion_id | promotions | FK (nullable) | set null |
| **cart_coupons** | cart_id | carts | FK | cascade |
| **coupon_redemptions** | coupon_id | coupons | FK | cascade |
| **coupon_redemptions** | user_id | users | FK (nullable) | set null |
| **coupon_redemptions** | order_id | orders | FK (nullable) | null on delete |
| **cart_coupons** | coupon_id | coupons | FK (nullable) | null on delete |

**Constraints:** One redemption per coupon per order (unique on coupon_id, order_id when order_id not null).  
**Note:** `cart_coupons` also stores `coupon_code` for display; `coupon_id` is the FK.

---

## 5. Cart

| Table | Relation | To Table | Type | On Delete |
|-------|----------|----------|------|-----------|
| **carts** | user_id | users | FK (nullable) | cascade |
| **cart_items** | cart_id | carts | FK | cascade |
| **cart_items** | product_variant_id | product_variants | FK | cascade |
| **cart_coupons** | cart_id | carts | FK | cascade |

---

## 6. Orders & Fulfillment

| Table | Relation | To Table | Type | On Delete |
|-------|----------|----------|------|-----------|
| **orders** | user_id | users | FK (nullable) | set null |
| **order_lines** | order_id | orders | FK | cascade |
| **order_lines** | product_id | products | FK (nullable) | null on delete |
| **order_lines** | product_variant_id | product_variants | FK (nullable) | set null |
| **order_status_history** | order_id | orders | FK | cascade |
| **order_status_history** | changed_by_user_id | users | FK (nullable) | set null |
| **payments** | order_id | orders | FK | cascade |
| **refunds** | payment_id | payments | FK | cascade |
| **refunds** | order_id | orders | FK (nullable) | null on delete |
| **shipments** | order_id | orders | FK | cascade |
| **shipment_items** | shipment_id | shipments | FK | cascade |
| **shipment_items** | order_line_id | order_lines | FK | cascade |
| **returns** | order_id | orders | FK | cascade |
| **returns** | refund_id | refunds | FK (nullable) | null on delete |
| **return_lines** | return_id | returns | FK | cascade |
| **return_lines** | order_line_id | order_lines | FK | cascade |

**Note:** Orders have optional `shipping_method_id` (FK) plus snapshot `shipping_method_code` / `shipping_method_name`.

---

## 7. Shipping Configuration

| Table | Relation | To Table | Type | On Delete |
|-------|----------|----------|------|-----------|
| **shipping_method_zones** | shipping_method_id | shipping_methods | FK | cascade |

---

## 8. Wishlist & Reviews

| Table | Relation | To Table | Type | On Delete |
|-------|----------|----------|------|-----------|
| **wishlists** | user_id | users | FK | cascade |
| **wishlist_items** | wishlist_id | wishlists | FK | cascade |
| **wishlist_items** | product_variant_id | product_variants | FK | cascade |
| **product_reviews** | user_id | users | FK | cascade |
| **product_reviews** | product_id | products | FK (nullable) | null on delete |

---

## Entity Relationship Summary (by entity)

- **users** → user_addresses, sessions, carts, wishlists, orders, coupon_redemptions, order_status_history (changed_by), product_reviews  
- **brands** → products  
- **categories** → categories (self), category_product (with products)  
- **products** → product_variants, category_product, product_reviews  
- **product_variants** → product_prices, stock_items, stock_reservations, stock_movements, cart_items, order_lines, wishlist_items  
- **warehouses** → stock_items, stock_reservations, stock_movements  
- **promotions** → coupons  
- **coupons** → cart_coupons (by code), coupon_redemptions  
- **carts** → cart_items, cart_coupons  
- **orders** → order_lines, order_status_history, payments, shipments, returns; optional shipping_method_id; referenced by coupon_redemptions, refunds  
- **payments** → refunds  
- **refunds** → optionally referenced by returns (refund_id)  
- **shipments** → shipment_items  
- **shipment_items** → link shipments to order_lines (quantity per line)  
- **returns** → return_lines; optional refund_id  
- **return_lines** → link returns to order_lines (quantity returned)  
- **shipping_methods** → shipping_method_zones; referenced by orders (shipping_method_id)  

---

## Standalone / No Foreign Keys

- **brands** – no incoming FKs from other core tables (products point to brands).  
- **warehouses** – no FKs to other tables; stock tables point to warehouses.  
- **promotions** – only coupons reference them.  
- **shipping_methods** – only shipping_method_zones reference them.  
- **cache**, **jobs**, **personal_access_tokens**, **permission_tables**, **password_reset_tokens** – Laravel/system tables not listed above.

---

## Purchase and return flow

- **Fulfillment:** `shipments` record each package sent; `shipment_items` link a shipment to specific `order_lines` and quantities. Use this for partial fulfillment and to know which items are in which package.
- **Returns:** `returns` represent a return request (order_id, status, optional refund_id). `return_lines` link a return to `order_lines` and quantities. Refunds are linked via `refunds.order_id` and optionally `returns.refund_id`. Restock can be recorded in `stock_movements` with `reference_type = 'return'`, `reference_id = return.id`.
