# E-commerce API (Laravel Backend)

> Backend for [ecommerceplatform](https://github.com/manshymido/ecommerceplatform). This directory contains the Laravel API only.

Laravel-based API for a high-scale e-commerce platform. Modular monolith with hexagonal/DDD-style modules: catalog, inventory, cart, promotions, checkout, orders, payments (Stripe), shipping, wishlist, and product reviews.

## Requirements

- PHP 8.2+
- Composer
- SQLite (default) or MySQL / MariaDB
- Optional: Redis (cache), Node (frontend assets)

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` (database, cache, Stripe keys if using payments). Then:

```bash
php artisan migrate
php artisan db:seed
```

Create the SQLite file if using default DB:

```bash
touch database/database.sqlite
```

## Run locally

```bash
php artisan serve
```

API base: `http://localhost:8000`. Health: `http://localhost:8000/up`, readiness: `http://localhost:8000/ready`.

## Testing

```bash
php artisan test
```

See `TESTING.md` for more detail. Load tests (k6) are in `load-tests/`.

## API overview

| Area        | Examples                                      |
|------------|------------------------------------------------|
| **Catalog**| `GET /api/products`, `GET /api/products/{slug}`, categories, brands |
| **Cart**   | `GET/POST/PATCH/DELETE /api/cart`, items, coupon |
| **Checkout** | `POST /api/checkout` (guest or auth)         |
| **Orders** | `GET /api/orders`, `GET /api/orders/{id}` (auth) |
| **Payments** | `POST /api/orders/{id}/pay` (Stripe)         |
| **Shipping** | `GET /api/shipping/quotes`                    |
| **Wishlist** | `GET/POST/DELETE /api/wishlist` (auth)       |
| **Reviews**| `GET/POST /api/products/{slug}/reviews`       |
| **Admin**  | `GET /api/admin/*` (role: admin)               |

Postman collection: `postman/`.

## Project structure

- `app/` – HTTP layer, events, listeners, mail
- `app/Modules/` – Domain modules (Cart, Catalog, Inventory, Order, Payment, Promotion, Review, Shipping, Wishlist)
- `config/`, `routes/` – Laravel config and API routes
- `database/migrations/`, `database/seeders/` – Schema and seeds
- `docs/` (repo root) – Architecture, deployment, runbooks, backups

## Docker

```bash
docker build -t ecommerce-backend .
docker run -p 8000:8000 --env-file .env ecommerce-backend
```

## Docs and CI

- **Tests:** CI runs `php artisan test` on push/PR (see repo root [.github/workflows/tests.yml](https://github.com/manshymido/ecommerceplatform/blob/main/.github/workflows/tests.yml)).
- **Docs:** Architecture, deployment, runbooks, and backups are in the [repo root `docs/`](https://github.com/manshymido/ecommerceplatform/tree/main/docs).

## License

MIT.
