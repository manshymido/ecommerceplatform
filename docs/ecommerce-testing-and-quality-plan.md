## Ecommerce System – Testing & Quality Plan

This document defines how the ecommerce backend will be tested and kept reliable, covering levels of testing, tooling, and practices per module.

### 1. Testing Goals

- Catch regressions early, especially in **money, stock, and payment flows**.
- Keep **domain logic highly testable** (pure PHP, no framework coupling).
- Provide fast feedback in CI and meaningful safety nets before deployments.

### 2. Testing Levels

- **Unit Tests (Domain‑Focused)**
  - Target: entities, value objects, domain services (e.g. price calculation, discount rules, stock reservation).
  - No DB, no HTTP, no external APIs.
  - High coverage for:
    - `Order` total calculation, tax breakdown.
    - Promotion rules and coupon validation.
    - Inventory reservation rules and edge cases.
- **Application/Service Tests**
  - Target: use case handlers (e.g. `PlaceOrderHandler`, `AddItemToCartHandler`, `InitiatePaymentHandler`).
  - Use in‑memory or mocked repositories and gateways.
  - Verify orchestration, transaction boundaries, and emitted domain events.
- **Integration Tests**
  - Target: Laravel HTTP endpoints and Eloquent repositories.
  - Use test database (migrations per test suite run, `RefreshDatabase`).
  - Exercise:
    - Happy paths for checkout, payment, and order history.
    - Error/edge cases (insufficient stock, invalid coupon, payment failure).
- **End‑to‑End (E2E) / Contract Tests**
  - Target: interaction with external providers (payment gateways, search, mail).
  - Use sandbox environments or fakes.
  - Ensure webhook integration and critical flows remain compatible with external APIs.

### 3. Tooling & Frameworks

- **Test Runner**
  - Native PHPUnit as baseline.
  - Optionally `pestphp/pest` for more expressive tests.
- **API Testing**
  - **Postman** for manual and automated API testing:
    - Maintain Postman collections for all API endpoints (storefront, admin, webhooks).
    - Use environment variables for base URLs, tokens, and test data.
    - Create test scripts for automated validation of responses and status codes.
    - Version control Postman collections alongside code (in `postman/` directory).
- **Factories & Seeders**
  - Laravel model factories for generating realistic entities.
  - Seed base reference data (currencies, tax rates, shipping methods) in test DB.
- **Fakes & Mocks**
  - Laravel’s built‑in HTTP client fakes for external APIs (payment, search).
  - Hand‑rolled test doubles for domain interfaces (e.g. `PaymentGateway`, `StockRepository`).

### 4. Per‑Module Testing Focus

- **Catalog**
  - Unit: product/variant pricing, visibility rules.
  - Integration: search indexing jobs and queries.
- **Inventory**
  - Unit: stock reservation logic, over‑sell prevention, reservation expiry logic.
  - Integration: DB consistency of `StockItem`, `StockReservation`, `StockMovement`.
- **Cart**
  - Unit: item aggregation, quantity limits, price snapshots.
  - Application: applying coupons and shipping estimates via Promotion/Shipping services.
- **Order**
  - Unit: totals, status transitions, invariants.
  - Application: `PlaceOrderHandler` transaction; idempotency around duplicate submissions.
- **Payment**
  - Application: `InitiatePaymentHandler`, `HandlePaymentWebhookHandler` with fake gateways.
  - Integration: sandbox calls to Stripe/PayPal in non‑production environments.
- **Promotion/Pricing**
  - Unit: rule engine behaviour across realistic carts.
- **Notification**
  - Application: listeners triggered on `OrderPlaced`, `PaymentSucceeded`, etc., with mail/SMS fakes.

### 5. Error Handling & Resilience Testing

- Validate:
  - Graceful handling of DB/Redis unavailability (timeouts, retries where appropriate).
  - Idempotent processing of webhooks and job retries.
  - Proper rollback on failed transactions (e.g. payment succeeded but order update fails → compensation path).
- Simulate:
  - Partial failures (gateway 5xx, search index down) and defined fallbacks.

### 6. CI & Quality Gates

- **CI Pipeline Steps**
  - Code style/formatting (e.g. PHP CS Fixer or Laravel Pint).
  - Static analysis (e.g. Psalm or PHPStan with reasonable baseline).
  - Unit + application tests (fast suite) on every push.
  - Selected integration tests (core flows) on main branch.
- **Quality Gates**
  - Minimum code coverage threshold for domain layer.
  - No new high‑severity static analysis issues allowed on main branch.
  - All code style checks MUST enforce **PSR‑12** so that the entire codebase follows a consistent standard.

### 7. Release & Regression Strategy

- Maintain a **regression suite** focused on:
  - Checkout flow, payment success/failure, order creation.
  - Stock updates and reservation cleanup.
- Run regression suite before:
  - Major schema changes.
  - Payment or pricing‑related changes.
  - Promotions and peak season deployments.

