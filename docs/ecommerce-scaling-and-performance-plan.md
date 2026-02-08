## Ecommerce System – Scaling & Performance Plan

This document defines how the ecommerce system will scale to handle 10k+ requests per second while remaining stable and maintainable.

### 1. Scalability Objectives

- Sustain **≥ 10k RPS** for read‑heavy workloads (catalog, product pages).
- Maintain **P95 latency < 200–300 ms** for core endpoints under normal load.
- Support burst traffic (promotions, seasonal peaks) via horizontal scaling.
- Ensure resilience: graceful degradation under partial failures.

### 2. Application Tier Scaling

- **Stateless App Servers**
  - No local session storage; use Redis for sessions.
  - No user‑specific data on local disk; use object storage for media.
  - Configuration via environment variables.
- **Laravel Octane**
  - Use Swoole/RoadRunner to increase throughput per node.
  - Ensure services are request‑safe (no shared mutable state in singletons).
- **Horizontal Scaling**
  - Multiple app instances behind load balancer (Nginx/HAProxy/ELB).
  - Auto‑scaling policies based on CPU utilization, RPS, or latency.

### 3. Caching Strategy

- **Catalog & Content Caching**
  - Redis as primary cache for:
    - Product lists and detail pages.
    - Category pages and filters.
    - Home page sections (banners, featured products).
  - Use cache tags to invalidate on product/category updates.
  - Consider HTTP‑level caching (CDN) for public GET endpoints.
- **Configuration & Lookup Caching**
  - Cache tax rates, shipping methods, currencies, promotion rules.
  - Warm common caches at deploy/startup if needed.
- **Database Query Caching**
  - Use query‑level caching only when invalidation is well‑understood.
  - Prefer explicit per‑use‑case caching over global query cache.

### 4. Database Design & Scaling

- **Relational DB as System of Record**
  - Well‑normalized schema for core entities (users, products, orders, payments).
  - Carefully chosen denormalized fields for read performance (e.g. order totals, snapshots).
- **Indexing Guidelines**
  - Index all foreign keys (`user_id`, `product_id`, `order_id`, etc.).
  - Index common filter/sort fields (`status`, `created_at`, `slug`, `sku`).
  - Regularly review slow queries and adjust indexes.
- **Read Replicas**
  - Use DB replicas for read‑heavy traffic (catalog, order history).
  - Configure Laravel read/write connections (writes to primary, reads to replicas).
- **Data Lifecycle**
  - Archiving strategy for old orders/logs to separate DB or cold storage.
  - Partitioning or sharding for very large tables if needed (later phases).

### 5. Search & Filtering

- Offload complex search and filters to dedicated search engine (Meilisearch/Elasticsearch).
- Keep search index eventually consistent:
  - On product changes, enqueue jobs to update the index.
  - Failures retried via queues; fall back to DB queries in worst case.

### 6. Queue & Background Processing

- **Use Queues for Non‑Critical Work**
  - Emails, SMS, push notifications.
  - Invoice/PDF generation.
  - Search index updates.
  - ERP/WMS synchronization.
- **Queue Infrastructure**
  - Redis/SQS/RabbitMQ as queue backend.
  - Horizon for monitoring, with multiple worker pools:
    - High priority: payment, stock, order‑critical jobs.
    - Normal: emails, notifications.
    - Low priority: reports, heavy exports.
- **Backpressure & Failure Handling**
  - Max retries with dead‑letter queues for poison messages.
  - Alerting on queue backlog thresholds.

### 7. CDN & Edge Optimization

- Front static assets and public GET APIs with CDN.
- Strategies:
  - Cache product/listing pages with reasonable TTL and cache busting on updates.
  - Gzip/Brotli compression, HTTP/2 or HTTP/3.
  - Image optimization (responsive sizes, WebP/AVIF where possible).

### 8. Rate Limiting & Protection

- Use Laravel’s rate limiter for:
  - Login, registration, password reset.
  - Search endpoints and other expensive operations.
- Implement IP/user‑based throttling with sensible defaults.
- Use WAF (Web Application Firewall) at the edge for:
  - Basic DDoS mitigation.
  - Blocking known malicious patterns.

### 9. Observability & Operations

- **Logging**
  - Centralized, structured logs (JSON) with correlation IDs.
  - Separate channels for application, access, queue workers.
- **Metrics**
  - Collect RPS, latency, error rates per route.
  - Resource metrics (CPU, memory, DB connections, Redis stats, queue depth).
- **Tracing**
  - Distributed tracing across HTTP requests and background jobs.
  - Trace IDs propagated in logs and job metadata.
- **Alerting**
  - Alerts on error spikes, high latency, queue backlog, DB replica lag.

### 10. Performance Testing Strategy

- **Load Testing**
  - Use tools like k6/wrk to simulate realistic scenarios:
    - Catalog browsing.
    - Add to cart and checkout.
    - High write‑load scenarios (order placement).
  - Test both average and peak conditions.
- **Capacity Planning**
  - Determine how many RPS each app node supports.
  - Calculate number of nodes required for baseline and peak traffic.
- **Regression & Benchmarking**
  - Maintain a baseline performance suite and run after major changes.

### 11. Evolution Plan

- Start with a well‑tuned modular monolith and scale vertically + horizontally.
- Once specific modules hit organizational/technical limits (e.g. Payments, Inventory),
  consider extracting them into separate services using the existing module boundaries.

