# Storefront SPA

React SPA for the e-commerce platform. Consumes the Laravel API.

## Stack

- **Vite** + **React 19** + **TypeScript**
- **React Router** v7
- **TanStack Query** (server state)
- **Zustand** (auth, persisted)
- **Axios** (API client with interceptors)
- **Tailwind CSS**
- **React Hook Form** + **Zod** (forms)

## Setup

```bash
npm install
cp .env.example .env
```

Edit `.env` and set:

- `VITE_API_BASE_URL=http://localhost:8000/api` (Laravel API base URL)

## Run

```bash
npm run dev
```

Open `http://localhost:5173`. Ensure the backend is running at the URL set in `VITE_API_BASE_URL`.

## Build

```bash
npm run build
```

Output is in `dist/`. Serve with any static host or point Laravel to it for production.

## Features

- **Catalog:** Product list (filters, pagination), product detail, categories/brands
- **Cart:** Add/update/remove items, coupon; guest cart via `X-Guest-Token`
- **Checkout:** Address form, place order, redirect to order confirmation
- **Account:** Login (Sanctum token), orders list/detail, wishlist, submit review
- **Pay:** Order detail “Pay now” calls `POST /api/orders/{id}/pay` (e.g. Stripe redirect)
- **Admin:** Dashboard, products list, orders list (role: admin/super_admin)

## Auth

- Login: `POST /api/login` → store token in Zustand (persisted).
- Protected routes redirect to `/login` when unauthenticated.
- Admin routes require user role `admin` or `super_admin`.
