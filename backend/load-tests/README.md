# Load Tests (k6)

Phase 9 load tests for catalog, checkout, and order history. Requires [k6](https://k6.io/docs/getting-started/installation/) installed.

## Environment

- `BASE_URL` – API base URL (default: `http://localhost:8000`)
- `AUTH_TOKEN` – (orders.js only) Bearer token for a customer user

## Run

```bash
# Catalog browsing (public)
k6 run -e BASE_URL=http://localhost:8000 load-tests/catalog.js

# With more load
k6 run --vus 20 --duration 1m -e BASE_URL=http://localhost:8000 load-tests/catalog.js

# Guest checkout flow (needs products/variants in DB)
k6 run -e BASE_URL=http://localhost:8000 load-tests/checkout-guest.js

# Order history (needs valid AUTH_TOKEN)
k6 run -e BASE_URL=http://localhost:8000 -e AUTH_TOKEN=your-token load-tests/orders.js
```

## Scenarios

| Script            | Endpoints                         | Auth        |
|------------------|-----------------------------------|-------------|
| `catalog.js`     | GET products, categories, brands | None        |
| `checkout-guest.js` | GET cart, POST cart/items, POST checkout | X-Guest-Token |
| `orders.js`      | GET /api/orders                   | Bearer token |

Adjust `options.stages` and `options.thresholds` in each script to approach your target RPS and latency (e.g. P95 < 200–300 ms).
