# E-commerce Platform

Backend API and documentation for a high-scale e-commerce system. Built with Laravel (modular monolith, hexagonal/DDD-style).

## Repository structure

| Path | Description |
|------|-------------|
| **[backend/](backend/)** | Laravel API – catalog, cart, checkout, orders, payments (Stripe), shipping, wishlist, reviews |
| **[frontend/](frontend/)** | React SPA (Vite, TypeScript, TanStack Query, Zustand) – storefront and admin |
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

### Frontend

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

App: `http://localhost:5173`. Set `VITE_API_BASE_URL=http://localhost:8000/api` in `.env` if the API runs elsewhere.

## Tech stack

- **Backend:** PHP 8.2 · Laravel 12 · SQLite / MySQL · optional Redis, Stripe, k6 load tests
- **Frontend:** React 19 · Vite · TypeScript · TanStack Query · Zustand · Tailwind CSS

## License

MIT
