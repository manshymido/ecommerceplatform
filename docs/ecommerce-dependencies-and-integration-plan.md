## Ecommerce System – Dependencies & Integration Plan

This document specifies **concrete, popular packages and services** the project will depend on, and how each integrates into the architecture. It is a specialization of the high‑level architecture and scaling plans.

The focus is on **stable, community‑accepted solutions** rather than custom implementations.

### 1. Core Platform & Runtime

- **Language & Runtime**
  - PHP 8.2+ (or latest stable supported by Laravel LTS).
  - Web server: Nginx or Apache behind a reverse proxy/load balancer.
- **Framework**
  - `laravel/laravel` (latest LTS, e.g. Laravel 11).
  - `laravel/octane` for high‑throughput HTTP handling (Swoole or RoadRunner).

### 2. Authentication, Authorization & Security

- **Session‑based & API Authentication**
  - `laravel/sanctum`
    - Simple token‑based auth for SPAs/mobile clients.
    - Can coexist with session auth for server‑rendered frontends.
- **Permissions & Roles**
  - `spatie/laravel-permission`
    - Role/permission management for admin area and feature gating.
    - Integrates with Laravel policies/guards.
- **Password Hashing & Security**
  - Laravel’s built‑in `bcrypt`/`argon2id`.
  - Use Laravel’s native password reset and email verification features.

### 3. Payments & Billing

For a senior‑level ecommerce, assume **multi‑gateway support** with Stripe as the primary choice and PayPal as an alternative.

- **Stripe**
  - `laravel/cashier-stripe`
    - Handles integration with Stripe for payments, subscriptions, and webhooks.
    - Use selectively: for one‑time payments, leverage Cashier’s payment intents support.
  - `stripe/stripe-php`
    - Low‑level Stripe SDK; used behind a domain‑oriented `PaymentGateway` interface.
- **PayPal**
  - `paypal/rest-api-sdk-php` (or current PayPal SDK).
    - Wrapped by a `PayPalGateway` adapter implementing the same `PaymentGateway` interface.
- **Abstraction Pattern**
  - Domain `PaymentGateway` interface with multiple adapters:
    - `StripePaymentGateway` (Cashier/Stripe SDK).
    - `PayPalPaymentGateway`.
  - This keeps Payment module logic independent of vendor details and allows future gateways.

### 4. Database, Migrations & Modeling

- **Database**
  - MySQL or PostgreSQL, using Laravel’s core database layer.
  - No extra ORM packages; rely on Eloquent plus custom repositories where needed.
- **Migrations & Seeding**
  - Native Laravel migrations, seeders, and factories (`laravel/framework`).

### 5. Caching, Sessions, Queues & Jobs

- **Redis Integration**
  - Use the PHP `phpredis` extension in production for best performance.
  - Fallback to `predis/predis` for environments without the extension.
  - Configure Laravel to use Redis for:
    - Cache.
    - Session.
    - Queue.
- **Queue Management**
  - `laravel/horizon`
    - Monitor and manage Redis queues.
    - Separate queues: high‑priority (payments, stock), normal (emails), low (reports).
- **Rate Limiting & Throttling**
  - Built‑in Laravel rate limiter (backed by Redis).
  - No additional packages unless more advanced throttling is required.

### 6. Search & Product Discovery

Avoid custom full‑text search; use battle‑tested engines with Laravel integration.

- **Search Abstraction**
  - `laravel/scout`
    - Provides unified indexing/search API.
- **Meilisearch (Recommended for ecommerce)**
  - `meilisearch/meilisearch-laravel-scout` or equivalent Scout driver.
    - Fast, easy to operate relevance engine.
    - Good fit for product search and filters.
- **Elasticsearch (Alternative)**
  - `elasticsearch/elasticsearch` SDK with a suitable Scout driver.
  - Prefer when teams already operate Elasticsearch at scale.

### 7. Files, Media & CDN Integration

- **Storage**
  - Laravel Filesystem with S3 driver:
    - `league/flysystem-aws-s3-v3`.
  - Use S3 or S3‑compatible services (MinIO, DigitalOcean Spaces, etc.) for:
    - Product images.
    - User uploads (invoices, attachments).
- **Image Handling (Optional)**
  - `intervention/image` or a similar library for server‑side image manipulation.
  - Consider moving heavy image processing to background jobs or dedicated services.

### 8. Notifications (Email, SMS, Push)

- **Email**
  - Use Laravel’s mail system with:
    - A transactional provider: SES, Mailgun, SendGrid, or Postmark.
    - The choice depends on infrastructure and pricing; all are widely used.
- **SMS / WhatsApp / Push**
  - `laravel-notification-channels/*` ecosystem
    - e.g. `laravel-notification-channels/twilio` for SMS.
  - Wrap providers via Notification module to avoid direct coupling.

### 9. Observability, Error Tracking & Debugging

- **Application Debugging (Non‑Production)**
  - `laravel/telescope`
    - For local/dev environment only.
    - Inspect requests, queries, jobs, logs.
- **Error Tracking**
  - `sentry/sentry-laravel` or `bugsnag/bugsnag-laravel`
    - Centralized error reporting with context.
- **Metrics & Tracing**
  - Use APM tooling compatible with PHP (e.g., New Relic, Datadog, or an OpenTelemetry exporter).
  - Integrate via infrastructure configuration and/or dedicated exporters, keeping code‑level coupling minimal.

### 10. API Documentation & Developer Experience

- **API Documentation**
  - `darkaonline/l5-swagger`
    - Generate OpenAPI/Swagger docs for REST APIs from annotations.
  - Alternative: `knuckleswtf/scribe` for human‑friendly API docs.
- **Testing**
  - Laravel’s built‑in PHPUnit test support.
  - Optionally `pestphp/pest` for expressive testing.

### 11. Coding Style Tooling

- **PSR‑12 Enforcement**
  - Use Laravel Pint or PHPCS configured with a **PSR‑12** ruleset.
  - Run style checks in:
    - Local development (pre‑commit hooks or manual commands).
    - CI pipeline as a required step before merge/deploy.

### 11. Admin Panel & Backoffice (Optional)

If you choose to speed up admin development with an ecosystem tool:

- **Laravel Nova** or **Filament**
  - `laravel/nova` (commercial) or `filament/filament` (open‑source).
  - Use only for backoffice UIs; keep domain logic in modules, not in the admin layer.

### 12. Package Usage Principles

- Prefer **Laravel first‑party packages** where they fit (Octane, Scout, Horizon, Sanctum, Cashier).
- For third‑party packages:
  - Choose **actively maintained** and **well‑documented** libraries.
  - Wrap them behind **interfaces in the Domain/Application layers** so that:
    - A package can be swapped with minimal impact.
    - Tests target your interfaces, not vendor APIs.
- Keep **business rules** out of package configuration; modules own business logic, packages provide infrastructure.

