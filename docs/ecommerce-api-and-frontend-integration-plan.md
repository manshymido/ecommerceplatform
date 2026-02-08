## Ecommerce System – API & Frontend Integration Plan

This document defines how the Laravel backend exposes APIs, how they are structured and versioned, and how frontend clients (web SPA, mobile) integrate.

### 1. API Styles & Consumers

- **Internal Web UI**
  - Laravel Blade or Inertia.js for server‑rendered or hybrid pages.
- **Public/Private APIs**
  - JSON REST API for SPAs and mobile clients.
  - Separate concerns:
    - **Storefront API** (customer‑facing).
    - **Admin API** (backoffice).

### 2. API Design Principles

- **Resource‑oriented**
  - Standard RESTful resources: `/products`, `/categories`, `/cart`, `/orders`, `/payments`, `/users`.
- **Consistency**
  - Use consistent naming, HTTP verbs, and status codes:
    - `GET /products`, `GET /products/{id}`, `POST /cart/items`, `POST /checkout`, `POST /orders/{id}/pay`.
  - Standard error envelope (e.g. `{ "error": { "code": "...", "message": "...", "details": [...] } }`).
- **Validation**
  - Laravel Form Requests for strong, centralized validation.
  - Validation errors return machine‑friendly structures for frontend handling.

### 3. Versioning Strategy

- **URL‑based or Header‑based Versioning**
  - E.g. `/api/v1/...` for public APIs.
  - Future changes introduce `/api/v2/...` while keeping older versions for a deprecation period.
- **Change Management**
  - Avoid breaking changes inside a version.
  - Document new fields/endpoints and keep them backward‑compatible where possible.

### 4. Authentication & Authorization at API Level

- **Storefront APIs**
  - `laravel/sanctum` for:
    - SPA token auth.
    - Mobile API tokens.
  - Guest operations (browse, view product) allowed without token.
- **Admin APIs**
  - Sanctum or session‑based auth plus:
    - `spatie/laravel-permission` roles/permissions.
    - Strict guards and middleware (`can:*` policies).

### 5. API Boundaries per Module

- **Catalog API**
  - `GET /products`, `GET /products/{id}`, `GET /categories`, `GET /categories/{id}/products`.
  - Filter/search parameters forwarded to search engine through application layer.
- **Cart API**
  - `GET /cart`, `POST /cart/items`, `PATCH /cart/items/{id}`, `DELETE /cart/items/{id}`.
  - `POST /cart/coupon`, `DELETE /cart/coupon`.
- **Checkout & Order API**
  - `POST /checkout` → creates order from current cart.
  - `GET /orders`, `GET /orders/{id}` for authenticated customers.
- **Payment API**
  - `POST /orders/{id}/pay` → returns payment initiation details (redirect URL, client secret).
  - Webhook endpoints (protected by signatures) for payment providers:
    - e.g. `/webhooks/payments/stripe`, `/webhooks/payments/paypal`.
- **Admin API**
  - Namespaced routes (e.g. `/admin/products`, `/admin/orders`, `/admin/users`).
  - Extra capabilities: bulk updates, exports, refunds, manual stock adjustments.

### 6. API Documentation & Contracts

- **OpenAPI/Swagger**
  - Generate machine‑readable API specs using `darkaonline/l5-swagger` or `knuckleswtf/scribe`.
  - Keep docs versioned alongside code; update on every breaking or significant change.
- **Postman Collections**
  - Maintain Postman collections for all API endpoints:
    - Separate collections for Storefront API, Admin API, and Webhooks.
    - Include example requests with proper authentication headers.
    - Use environment variables for base URL, tokens, and test data.
    - Version control collections in the repository (e.g., `postman/` directory).
    - Update collections when new endpoints are added or existing ones change.
- **Client SDKs (Optional)**
  - Generate TypeScript/other language clients from OpenAPI for SPAs or mobile apps.

### 7. Frontend Integration Patterns

- **SSR / Blade**
  - Simple, SEO‑critical pages (home, category, product detail) can use server rendering with Blade.
  - Use standard Laravel controllers, passing view models.
- **SPA (Vue/React/etc.)**
  - SPA consumes `/api/v1/...` endpoints.
  - Use Sanctum for authentication (SPA + API on same domain) or token‑based auth for cross‑domain.
  - Coordinate loading states, error handling, and retries using the standard API error format.
- **Mobile Apps**
  - Rely on the same `/api/v1/...` endpoints used by SPA.
  - Use personal access tokens or mobile‑specific token issuance via Sanctum.

### 8. Performance Considerations at API Edge

- Pagination for list endpoints (e.g. `page`, `per_page`, `cursor`).
- ETag/Last‑Modified headers for cacheable resources.
- Gzip/Brotli compression enabled at the HTTP edge.
- Avoid overfetching:
  - Expandable fields via query parameters when necessary (e.g. `?include=items,shipping_address`).

