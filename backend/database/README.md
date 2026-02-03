# Database Schema Reference

This directory contains database migrations and schema documentation.

## Current State (Phase 1)

**Tables Created:**
- `users` - User accounts
- `user_addresses` - Billing/shipping addresses
- `roles` - User roles (Spatie Permission)
- `permissions` - Permissions (Spatie Permission)
- `model_has_roles` - User-role assignments
- `role_has_permissions` - Role-permission assignments
- `personal_access_tokens` - Sanctum API tokens

## Relationships (Current)

```
users (1) ──< (N) user_addresses
users (N) >──< (N) roles [via model_has_roles]
roles (N) >──< (N) permissions [via role_has_permissions]
users (1) ──< (N) personal_access_tokens
```

## Full Schema Diagram

See `../../docs/ecommerce-database-diagrams.md` for:
- Complete ERD diagrams (Mermaid format)
- Current state diagram (Phase 1)
- Final state diagram (all phases)
- Relationship summaries
- Table statistics

## Migration Commands

```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Reset all migrations
php artisan migrate:reset

# Refresh (rollback + migrate)
php artisan migrate:refresh

# Fresh (drop all + migrate)
php artisan migrate:fresh
```

## Seeding

```bash
# Seed roles
php artisan db:seed --class=RoleSeeder

# Seed test users
php artisan db:seed --class=TestUserSeeder
```
