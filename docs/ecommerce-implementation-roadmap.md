## Ecommerce System – Implementation Roadmap (Detailed)

This roadmap breaks the project into **phases and concrete Laravel tasks**, aligned with the architecture and module plans. It assumes a modular monolith with Laravel 11+.

---

### Phase 0 – Project Bootstrap & Infrastructure

- **P0.1 – New Laravel Project**
  - Create Laravel app with Octane support.
  - Configure `.env` for local DB, Redis, queue, mail.
- **P0.2 – Baseline Tooling**
  - Add and configure:
    - `laravel/octane`
    - `laravel/sanctum`
    - `spatie/laravel-permission`
    - `laravel/horizon`
    - `laravel/scout` + Meilisearch driver
    - Error tracking (e.g. Sentry/Bugsnag)
  - Set up PHPUnit/Pest, PHPStan/Psalm.
  - Configure Laravel Pint or PHPCS to enforce **PSR‑12** across the codebase.
  - Create initial **Postman collection** for API testing (in `postman/` directory).
- **P0.3 – Folder Structure**
  - Introduce `App/Modules/*/{Domain,Application,Infrastructure}` structure.
  - Create base interfaces (e.g. `OrderRepository`, `PaymentGateway`) and service providers to bind implementations.

---

### Phase 1 – User & Identity, Basic Admin Access

- **P1.1 – User Model & Auth**
  - Define `User` model, migrations (`users`, `user_addresses`).
  - Configure Laravel auth scaffolding (or Fortify/Jetstream if desired).
- **P1.2 – Roles & Permissions**
  - Install and configure Spatie Permission.
  - Seed roles: `customer`, `support`, `admin`, `super_admin`.
- **P1.3 – Basic Admin Guard**
  - Protect `/admin/*` routes with role/permission middleware.
  - Stub admin dashboard (can be Blade, Nova, or Filament later).

---

### Phase 2 – Catalog (Products, Variants, Pricing)

- **P2.1 – Migrations & Models**
  - Implement schema for:
    - `products`, `brands`, `categories`, `category_product`.
    - `product_variants`, `product_prices`.
  - Eloquent models in Catalog Infrastructure.
- **P2.2 – Domain Layer**
  - Define Catalog domain entities: `Product`, `ProductVariant`, `Price`, `Money`.
  - Map Eloquent models ↔ domain models in repositories.
- **P2.3 – Admin Product Management**
  - Admin CRUD for products, variants, prices, categories.
  - Validation, soft deletes, and basic SEO fields.
- **P2.4 – Storefront Product API**
  - `GET /api/v1/products`, `/products/{id}`, `/categories`, `/categories/{id}/products`.
  - Integrate Laravel Scout + Meilisearch for search and filtering.
  - Add Redis caching for hot queries.

---

### Phase 3 – Inventory (Stock, Reservations, Movements)

- **P3.1 – Inventory Schema**
  - Implement `warehouses`, `stock_items`, `stock_reservations`, `stock_movements`.
- **P3.2 – Domain & Services**
  - Domain entities: `StockItem`, `StockReservation`, `StockMovement`.
  - Inventory services:
    - `checkAvailability(variantId, qty, warehouse?)`
    - `reserveStock(cartItems/OrderDraft)`
    - `releaseReservations(...)`
    - `finalizeStockForOrder(orderId)`
- **P3.3 – Admin Stock Management**
  - Admin UI to adjust stock and view stock movements.
- **P3.4 – Integration Hooks**
  - Expose Inventory services to Cart and Order Application layers.

---

### Phase 4 – Cart & Promotions

- **P4.1 – Cart Schema & Models**
  - Implement `carts`, `cart_items`, `cart_coupons`.
- **P4.2 – Cart Domain**
  - `Cart`, `CartItem` entities, with invariants (max items, quantity limits).
  - Price snapshot fields in cart items.
- **P4.3 – Cart API**
  - `GET /cart`, `POST /cart/items`, `PATCH /cart/items/{id}`, `DELETE /cart/items/{id}`.
  - Guest carts with `guest_token`, auto‑merge on login.
- **P4.4 – Promotions Basics**
  - Implement `promotions`, `coupons`, `coupon_redemptions`.
  - A first promotion rule type (e.g. percentage off cart total).
  - Integrate promotion evaluation in Cart domain/application layer.

---

### Phase 5 – Checkout & Orders

- **P5.1 – Order Schema**
  - Implement `orders`, `order_lines`, `order_status_history`.
  - Include snapshot JSON fields for addresses/tax/shipping as designed.
- **P5.2 – Order Domain**
  - Entities: `Order`, `OrderLine`, `OrderStatus`, `Address`, `Money`, `TaxBreakdown`, `ShippingCost`.
  - Invariants: totals, status transitions.
- **P5.3 – Checkout Use Case**
  - `PlaceOrderHandler`:
    - Loads cart, recalculates totals (Catalog + Promotion).
    - Calls Inventory to reserve stock.
    - Creates and persists order + lines.
    - Marks cart as converted.
    - Emits `OrderPlaced` event.
- **P5.4 – Order API**
  - `POST /checkout`.
  - `GET /orders`, `GET /orders/{id}` (customer), `/admin/orders` (admin).
- **P5.5 – Guest Checkout**
  - Support checkout with guest carts and email + address snapshots.
  - Later linking of orders after account registration.

---

### Phase 6 – Payments (Stripe/PayPal Integration)

- **P6.1 – Payment Schema & Domain**
  - Implement `payments`, `refunds`.
  - Domain: `Payment`, `PaymentStatus`, `Refund`.
- **P6.2 – Payment Gateway Abstraction**
  - Define `PaymentGateway` interface in Payment module.
  - Implement `StripePaymentGateway` using Cashier/Stripe SDK.
  - Optionally implement `PayPalGateway`.
- **P6.3 – Initiate Payment Flow**
  - `InitiatePaymentHandler`:
    - Validates order state.
    - Creates `Payment` record (`PENDING`).
    - Calls gateway to create payment intent/session.
    - Returns redirect URL/secret.
  - API: `POST /orders/{id}/pay`.
- **P6.4 – Webhooks & Confirmation**
  - Webhook controllers for Stripe/PayPal:
    - Verify signature.
    - Update `Payment` status.
    - Trigger Order use case to mark order `PAID`.
    - Emit `PaymentSucceeded`/`PaymentFailed`.
- **P6.5 – Refunds**
  - Use case for admin/customer initiated cancellation + refund.
  - Integrate with Inventory to release or adjust stock.

---

### Phase 7 – Shipping & Logistics

- **P7.1 – Shipping Schema**
  - Implement `shipping_methods`, `shipping_method_zones`, `shipments`.
- **P7.2 – Shipping Domain**
  - Entities: `ShippingMethod`, `ShippingZone`, `Shipment`, `ShippingQuote`.
  - Rules for cost calculation based on zone, weight, cart/order value.
- **P7.3 – Checkout Integration**
  - At cart/checkout:
    - Expose API to retrieve available shipping methods + quotes.
    - Persist chosen method on order.
- **P7.4 – Fulfillment Flow**
  - `FulfillOrderHandler` and creation of `shipments`.
  - Optional integration with carrier APIs for label creation/tracking.

---

### Phase 8 – Notifications & CX Extras

- **P8.1 – Notification Listeners**
  - Implement listeners for `OrderPlaced`, `PaymentSucceeded`, `OrderFulfilled`, `OrderCancelled`.
  - Use Laravel Notifications with mail/SMS/push channels.
- **P8.2 – Wishlist & Reviews**
  - Implement wishlist flows (`wishlists`, `wishlist_items`).
  - Implement product reviews (`product_reviews`) with moderation.

---

### Phase 9 – Hardening, Scaling & Observability

- **P9.1 – Caching & Performance**
  - Apply Redis caching to catalog and other read‑heavy endpoints.
  - Tune Octane workers, DB pool sizes.
- **P9.2 – Observability**
  - Finalize logging structure, metrics dashboards, tracing configuration.
  - Set up alert rules (latency, errors, queue backlog, DB replica lag).
- **P9.3 – Load Testing**
  - Run k6/wrk load tests across:
    - Catalog browsing.
    - Checkout + payment.
    - Order history.
  - Adjust infra and app configuration to approach the 10k RPS goal.

---

### Phase 10 – Deployment & Operations Maturity

- **P10.1 – CI/CD Pipeline**
  - Build, test, and package app into artefacts/containers.
  - Automated deployment to staging + manual/controlled promotion to production.
- **P10.2 – Backups & DR**
  - Implement scheduled backups and restore procedures.
  - Document runbooks for incidents and rollbacks.
- **P10.3 – Regular Maintenance**
  - Dependency updates, security patches, key rotation, and periodic architecture reviews based on real usage.

