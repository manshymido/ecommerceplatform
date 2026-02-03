# E-commerce Platform

Backend API and documentation for a high-scale e-commerce system. Built with Laravel (modular monolith, hexagonal/DDD-style).

## Repository structure

| Path | Description |
|------|-------------|
| **[backend/](backend/)** | Laravel API – catalog, cart, checkout, orders, payments (Stripe), shipping, wishlist, reviews |
| **[docs/](docs/)** | Architecture, deployment, runbooks, backups, maintenance |
| **[.github/workflows/](.github/workflows/)** | CI – runs tests on push/PR |

## Quick start

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

API: `http://localhost:8000`. See **[backend/README.md](backend/README.md)** for full setup, API overview, and testing.

## Tech stack

- **PHP 8.2** · **Laravel 12** · SQLite / MySQL · optional Redis, Stripe, k6 load tests

## License

MIT
