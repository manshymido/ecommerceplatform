## Ecommerce System – Core Flows & Sequence Diagrams

This document describes the main business flows and their high‑level sequence diagrams (text/mermaid style) for the ecommerce system.

### 1. Browse Products & View Product Details

**Goal**: Customer views product lists and details with fast, cached responses.

**High‑Level Sequence**

1. Customer → `GET /products` with filters.
2. `CatalogController` validates and forwards to `ProductCatalogQueryService`.
3. `ProductCatalogQueryService`:
   - Checks Redis for cached result.
   - On miss, queries DB or Search Engine, builds DTOs, stores in cache.
4. Result returned to customer.

```mermaid
sequenceDiagram
  participant C as Customer
  participant API as CatalogController
  participant QS as ProductCatalogQueryService
  participant R as Redis
  participant DB as Database/Search

  C->>API: GET /products?filters
  API->>QS: listProducts(filters)
  QS->>R: get(cacheKey)
  alt Cache hit
    R-->>QS: products
  else Cache miss
    QS->>DB: queryProducts(filters)
    DB-->>QS: products
    QS->>R: set(cacheKey, products)
  end
  QS-->>API: products DTO
  API-->>C: JSON/HTML response
```

### 2. Add Item to Cart

**Goal**: Safely add a product variant to cart, checking basic availability.

**High‑Level Sequence**

1. Customer → `POST /cart/items`.
2. `CartController` validates and calls `AddItemToCartHandler`.
3. `AddItemToCartHandler`:
   - Loads or creates `Cart` aggregate via `CartRepository`.
   - Optionally calls Inventory (`InventoryService`) for availability.
   - Adds/updates `CartItem` in the Cart domain object.
   - Persists updated cart.
4. Returns updated cart summary.

```mermaid
sequenceDiagram
  participant C as Customer
  participant API as CartController
  participant UC as AddItemToCartHandler
  participant CR as CartRepository
  participant IS as InventoryService

  C->>API: POST /cart/items
  API->>UC: handle(addItemCommand)
  UC->>CR: findOrCreateCart(user/guest)
  CR-->>UC: Cart
  UC->>IS: checkAvailability(variantId, qty)
  IS-->>UC: availabilityResult
  UC->>UC: addOrUpdateItem()
  UC->>CR: save(cart)
  CR-->>UC: persistedCart
  UC-->>API: cartSummary
  API-->>C: cart JSON
```

### 3. Checkout & Place Order

**Goal**: Turn a cart into a confirmed order with a consistent snapshot of prices, taxes, and addresses.

**High‑Level Sequence**

1. Customer → `POST /checkout`.
2. `CheckoutController` validates data and calls `PlaceOrderHandler`.
3. `PlaceOrderHandler`:
   - Loads cart via `CartRepository`.
   - Calls Catalog/Promotion services to recalculate prices and discounts.
   - Calls `InventoryService` to reserve stock atomically for each item.
   - Creates `Order` aggregate with all snapshots (items, totals, addresses).
   - Persists via `OrderRepository`.
   - Marks cart as converted/closed.
   - Emits `OrderPlaced` domain event.
4. Response includes order summary and payment initiation details.

```mermaid
sequenceDiagram
  participant C as Customer
  participant API as CheckoutController
  participant UC as PlaceOrderHandler
  participant CR as CartRepository
  participant OR as OrderRepository
  participant PR as PromotionService
  participant IS as InventoryService
  participant EB as EventBus

  C->>API: POST /checkout
  API->>UC: handle(placeOrderCommand)
  UC->>CR: getCart(cartId/user)
  CR-->>UC: Cart
  UC->>PR: calculateDiscounts(cartSnapshot)
  PR-->>UC: discountResult
  UC->>IS: reserveStock(cartItems)
  IS-->>UC: reservationResult
  UC->>UC: buildOrderAggregate()
  UC->>OR: save(order)
  OR-->>UC: persistedOrder
  UC->>CR: markCartConverted(cart)
  UC->>EB: publish(OrderPlaced)
  UC-->>API: orderSummary
  API-->>C: order JSON
```

### 4. Online Payment Flow

**Goal**: Process online payment via external provider, ensuring order/payment consistency.

**High‑Level Sequence**

1. Customer → `POST /orders/{id}/pay`.
2. `PaymentController` calls `InitiatePaymentHandler`.
3. `InitiatePaymentHandler`:
   - Verifies order status allows payment.
   - Creates `Payment` record (status `PENDING`).
   - Calls `PaymentGateway` adapter to create charge/session.
   - Stores provider reference/secret.
4. Customer is redirected to payment provider or uses client secret.
5. Payment provider sends webhook → `PaymentWebhookController`.
6. `PaymentWebhookController` calls `HandlePaymentWebhookHandler`.
7. `HandlePaymentWebhookHandler`:
   - Finds `Payment` by provider reference.
   - Updates `Payment` status.
   - If succeeded:
     - Updates `Order` status to `PAID`.
     - Emits `PaymentSucceeded` event.

```mermaid
sequenceDiagram
  participant C as Customer
  participant API as PaymentController
  participant IH as InitiatePaymentHandler
  participant PH as HandlePaymentWebhookHandler
  participant PG as PaymentGateway
  participant ORD as OrderRepository
  participant PAY as PaymentRepository
  participant EB as EventBus
  participant PP as PaymentProvider

  C->>API: POST /orders/{id}/pay
  API->>IH: handle(initiatePaymentCommand)
  IH->>ORD: getOrder(orderId)
  ORD-->>IH: Order
  IH->>PAY: createPayment(order, amount)
  PAY-->>IH: Payment (PENDING)
  IH->>PG: createCharge(Payment)
  PG->>PP: API request
  PP-->>PG: charge/session info
  PG-->>IH: providerRef
  IH->>PAY: updateProviderRef(Payment, providerRef)
  IH-->>API: paymentRedirectInfo
  API-->>C: redirect/secret

  PP-->>PH: webhook callback
  PH->>PAY: findByProviderRef(providerRef)
  PAY-->>PH: Payment
  PH->>PAY: updateStatus(SUCCEEDED/FAILED)
  alt Payment succeeded
    PH->>ORD: getOrder(orderId)
    ORD-->>PH: Order
    PH->>ORD: markOrderPaid(Order)
    PH->>EB: publish(PaymentSucceeded)
  end
```

### 5. Inventory Update & Fulfillment

**Goal**: Ensure stock levels reflect final fulfillment state and reservations are cleaned up.

**High‑Level Sequence**

1. Warehouse/admin marks order as shipped/fulfilled (`OrderFulfilled` use case).
2. `FulfillOrderHandler`:
   - Validates current order status.
   - Updates order status to `FULFILLED`.
   - Emits `OrderFulfilled` event.
3. Inventory listener (`OnOrderFulfilled`) reacts:
   - Converts reservations into final stock deductions (if not already done).
   - Records `StockMovement` entries.
4. Notifications listener sends shipment confirmation.

```mermaid
sequenceDiagram
  participant A as Admin/WMS
  participant API as OrderAdminController
  participant FH as FulfillOrderHandler
  participant ORD as OrderRepository
  participant EB as EventBus
  participant INVL as InventoryListener
  participant INV as InventoryService

  A->>API: POST /admin/orders/{id}/fulfill
  API->>FH: handle(fulfillOrderCommand)
  FH->>ORD: getOrder(orderId)
  ORD-->>FH: Order
  FH->>ORD: markFulfilled(Order)
  FH->>ORD: save(Order)
  FH->>EB: publish(OrderFulfilled)

  EB-->>INVL: OrderFulfilled event
  INVL->>INV: finalizeStockForOrder(orderId)
  INV-->>INVL: stockUpdated
```

### 6. Abandoned Cart & Expired Reservations

**Goal**: Clean up stale carts and free reserved stock.

**High‑Level Sequence**

1. Scheduler triggers periodic job (e.g. every 5–15 minutes).
2. Job queries for carts/reservations past their expiration time.
3. For each expired reservation:
   - Releases stock back to available quantity.
   - Marks reservation as expired.
4. For abandoned carts (no activity for threshold):
   - Mark as `abandoned`.
   - Optionally emit event for marketing automation.

These flows ensure that stock is not permanently locked by stale sessions and that the inventory view remains accurate.

### 7. Order Cancellation & Refunds

**Goal**: Allow customers or admins to cancel orders under defined conditions and process refunds through payment providers.

**High‑Level Sequence (Customer‑initiated cancellation before shipment)**

1. Customer → `POST /orders/{id}/cancel`.
2. `OrderController@cancel` calls `CancelOrderHandler`.
3. `CancelOrderHandler`:
   - Loads order and validates state (e.g. not yet fulfilled; rules per payment status).
   - Updates order status to `CANCELLED`.
   - Emits `OrderCancelled` event.
4. Listener in Payment module checks if payment was captured:
   - If captured:
     - Creates refund request via `PaymentGateway`.
     - Updates `Refund` record and emits `RefundProcessed` on success.
5. Inventory listener reacts to `OrderCancelled`:
   - Releases reserved stock if not yet released/finalized.

```mermaid
sequenceDiagram
  participant C as Customer
  participant API as OrderController
  participant CH as CancelOrderHandler
  participant ORD as OrderRepository
  participant EB as EventBus
  participant PL as PaymentListener
  participant PG as PaymentGateway
  participant INVL as InventoryListener

  C->>API: POST /orders/{id}/cancel
  API->>CH: handle(cancelOrderCommand)
  CH->>ORD: getOrder(orderId)
  ORD-->>CH: Order
  CH->>CH: validateCancelable()
  CH->>ORD: markCancelled(Order)
  CH->>ORD: save(Order)
  CH->>EB: publish(OrderCancelled)

  EB-->>PL: OrderCancelled
  PL->>PG: requestRefund(order/payment)
  PG-->>PL: refundResult

  EB-->>INVL: OrderCancelled
  INVL->>INVL: releaseReservationsIfAny(order)
```

### 8. Guest Checkout & Account Creation

**Goal**: Support checkout without prior registration, while still allowing users to create an account later and attach orders.

**High‑Level Sequence**

1. Guest browses and adds items to cart using guest token.
2. At checkout, guest fills in email and address details.
3. `CheckoutController` calls `PlaceOrderHandler` with guest customer data.
4. `PlaceOrderHandler`:
   - Creates order with `user_id = null`, stores email and addresses in snapshots.
5. Optional flow: guest later creates an account with the same email.
6. A background job or explicit use case links historical guest orders to the new user account.

This flow avoids forcing registration while keeping data linkable when desired.

