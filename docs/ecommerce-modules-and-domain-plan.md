## Ecommerce System – Modules & Domain Plan

This document defines the main modules (bounded contexts), their responsibilities, key domain concepts, and how they interact inside the modular monolith.

### 1. Bounded Contexts / Modules

The system is split into the following modules:

- **Catalog**
- **Inventory**
- **Cart**
- **Order**
- **Payment**
- **User & Identity**
- **Promotion/Pricing**
- **Shipping & Logistics**
- **Notification**

Each module is internally layered into **Domain**, **Application**, and **Infrastructure**.

### 2. Catalog Module

- **Responsibilities**
  - Manage products, categories, brands, attributes, and variants.
  - Provide product data for storefront, cart, and orders.
  - Integrate with search engine for full‑text and faceted search.
- **Core Domain Concepts**
  - `Product` (aggregate root) – core properties and relationships.
  - `ProductVariant` – SKU‑level variations (size, color, etc.).
  - `Category`, `Brand`.
  - `Price`, `Money` (value object).
- **Key Use Cases (Application Layer)**
  - Create/Update/Delete product and variants.
  - Publish/Unpublish product.
  - List/search products (for storefront and admin).
- **External Interactions**
  - Updates search index on product changes.
  - Notifies Promotion module when relevant fields (price, category) change.

### 3. Inventory Module

- **Responsibilities**
  - Track available stock per SKU and warehouse.
  - Manage stock reservations during checkout.
  - Expose stock availability to other modules.
- **Core Domain Concepts**
  - `StockItem` – current quantity per variant and warehouse.
  - `StockReservation` – temporary hold tied to cart/order.
  - `StockMovement` – in/out/adjustment with reason.
- **Key Use Cases**
  - Check stock availability for a list of SKUs.
  - Reserve stock for a pending order.
  - Release expired or cancelled reservations.
  - Apply final stock deduction on order fulfillment.
- **External Interactions**
  - Receives events from Order module (`OrderPlaced`, `OrderCancelled`, `OrderFulfilled`).
  - Optionally syncs with external WMS/ERP.

### 4. Cart Module

- **Responsibilities**
  - Manage shopping cart lifecycle for guests and authenticated users.
  - Maintain list of items, quantities, selected options, and preliminary totals.
  - Integrate with Promotions for discounts and with Inventory for availability checks.
- **Core Domain Concepts**
  - `Cart` (aggregate root).
  - `CartItem`.
  - `CartStatus` (active, converted, abandoned, expired).
- **Key Use Cases**
  - Create or retrieve cart.
  - Add/Update/Remove items.
  - Apply coupon/promo code.
  - View cart summary with estimated totals and shipping.
- **External Interactions**
  - Reads from Catalog for product and price data.
  - Calls Inventory to check availability (optionally to pre‑reserve).
  - Calls Promotion for discounts.

### 5. Order Module

- **Responsibilities**
  - Represent a confirmed purchase, with full snapshot of commercial data.
  - Manage order status transitions (placed, paid, shipped, completed, cancelled).
  - Provide order history and details to customers and admins.
- **Core Domain Concepts**
  - `Order` (aggregate root).
  - `OrderLine`.
  - `OrderStatus`.
  - `Address` (shipping/billing, value object).
  - `Money`, `TaxBreakdown`, `ShippingCost`.
- **Key Use Cases**
  - Place order from cart.
  - Recalculate totals before placing order.
  - Update order status (admin and system‑driven).
  - Retrieve order by ID or customer.
- **External Interactions**
  - Emits domain events: `OrderPlaced`, `OrderPaid`, `OrderCancelled`, `OrderFulfilled`.
  - Coordinates with Payment and Inventory via Application layer and events.

### 6. Payment Module

- **Responsibilities**
  - Integrate with external payment gateways.
  - Manage payment attempts, statuses, and refunds.
  - Link payments to orders while isolating gateway specifics.
- **Core Domain Concepts**
  - `Payment` – amount, currency, status, provider data.
  - `PaymentMethod` – gateway type and configuration.
  - `PaymentStatus` – pending, succeeded, failed, refunded, etc.
- **Key Use Cases**
  - Initiate payment for an order.
  - Handle payment provider webhooks/callbacks.
  - Trigger refunds or voids.
- **External Interactions**
  - Calls external payment providers via adapters.
  - Emits/handles events like `PaymentSucceeded`, `PaymentFailed`.
  - Updates Order module when payment state changes.

### 7. User & Identity Module

- **Responsibilities**
  - Authentication and authorization for customers and admins.
  - Manage user profiles and address books.
  - Provide identity info to other modules (e.g., Order, Cart).
- **Core Domain Concepts**
  - `User`, `Role`, `Permission`.
  - `Address`.
- **Key Use Cases**
  - Register, login, logout.
  - Manage profile and addresses.
  - Assign roles/permissions (admin side).

### 8. Promotion/Pricing Module

- **Responsibilities**
  - Centralize discount, coupon, and campaign rules.
  - Provide price calculation policies to Cart and Order.
- **Core Domain Concepts**
  - `Coupon`, `Campaign`, `Rule` (e.g., percentage off, fixed amount, buy‑X‑get‑Y).
  - `PriceRuleResult` (value object carrying discounted amounts and reasons).
- **Key Use Cases**
  - Validate coupon applicability for a given cart or order.
  - Calculate effective prices and discounts for a cart snapshot.
  - Track coupon usage limits.
- **External Interactions**
  - Called by Cart and Order use‑cases.
  - Optionally exposes reporting APIs for marketing.

### 9. Shipping & Logistics Module

- **Responsibilities**
  - Calculate shipping options and costs based on destination, weight, and cart/order totals.
  - Manage shipments and tracking information.
  - Integrate with external carriers (optional).
- **Core Domain Concepts**
  - `ShippingMethod`, `ShippingZone`, `Shipment`, `Carrier`.
  - `ShippingQuote` (value object with price and ETA).
- **Key Use Cases**
  - Estimate shipping costs during cart/checkout.
  - Persist chosen shipping method on order placement.
  - Update shipment status and tracking.
- **External Interactions**
  - Reads from Inventory (weight, availability per warehouse if relevant).
  - Optionally calls carrier APIs for label creation/tracking.

### 10. Notification Module

- **Responsibilities**
  - Send transactional and marketing notifications.
  - Centralize templates and delivery channels.
- **Core Domain Concepts**
  - `NotificationTemplate`.
  - `Channel` (email, SMS, push, webhook).
- **Key Use Cases**
  - Send order confirmation, shipment updates, password reset, etc.
- **External Interactions**
  - Subscribes to domain events (e.g. `OrderPlaced`, `PasswordResetRequested`).
  - Integrates with external mail/SMS/push providers.

### 11. Module Interaction Overview

High‑level interaction for a normal purchase:

1. **Catalog** provides product data to the storefront.
2. **Cart** uses Catalog (and optionally Inventory + Promotion) to maintain cart state.
3. **Order** creates a confirmed snapshot from the cart and coordinates stock reservation with Inventory.
4. **Payment** handles external payment flow and updates Order on success/failure.
5. **Inventory** finalizes stock deduction when orders are fulfilled or released on cancellation.
6. **Notification** listens to events to send relevant messages.

