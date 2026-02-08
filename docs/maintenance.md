# Regular Maintenance (Phase 10)

Dependency updates, security patches, key rotation, and periodic checks.

## Dependency updates

### PHP (Composer)

- **Check for updates:** `cd backend && composer outdated`
- **Update all (dev):** `composer update` (then run tests: `php artisan test`)
- **Update one package:** `composer update vendor/package`
- **Security only:** `composer audit` (PHP 8.2+ / Composer 2.4+)

After updates, run the test suite and fix any breaking changes. Commit `composer.lock`.

### Node (if used for frontend assets)

- **Check:** `npm outdated`
- **Update:** `npm update` or update specific packages; run build and tests.

### Schedule

- At least monthly: run `composer outdated` and `composer audit`; apply security-related updates promptly.
- Major upgrades (e.g. Laravel major version): plan in a separate task; test in staging first.

## Security patches

- Subscribe to [Laravel Security Releases](https://laravel.com/docs/releases) and apply security patches as soon as possible.
- Run `composer audit` regularly; address reported vulnerabilities.
- Keep PHP version supported (e.g. PHP 8.2+); upgrade before EOL.

## Key and secret rotation

Rotate these periodically and after any suspected compromise.

| Secret | Where | Rotation steps |
|--------|--------|----------------|
| **APP_KEY** | `.env` | Generate new: `php artisan key:generate --show`. Update `.env`; deploy. All encrypted data (e.g. cookies) will need to be re-created (users may need to log in again). |
| **DB password** | `.env` | Change in DB; update `DB_PASSWORD`; restart app. |
| **Stripe keys** | `.env` | Rotate in Stripe Dashboard; update `STRIPE_KEY` / `STRIPE_SECRET` / webhook signing secret; deploy. |
| **Sanctum / session** | Laravel config | If you use custom keys, rotate and redeploy; users may be logged out. |

After rotation, clear config cache: `php artisan config:clear` (or redeploy).

## Periodic checks

- **Logs** – Review `storage/logs` (or centralised logs) for errors and anomalies.
- **Queue** – If using queues/Horizon, monitor depth and failed jobs; retry or fix and release.
- **Backups** – Confirm backup jobs run and test restore at least quarterly (see [backups-and-restore.md](backups-and-restore.md)).
- **Performance** – Run load tests (e.g. k6) after major changes; review latency and error rates.
- **Architecture** – Periodically review scaling, caching, and observability against real usage and the [ecommerce-scaling-and-performance-plan.md](ecommerce-scaling-and-performance-plan.md).

## Checklist (e.g. monthly)

- [ ] `composer outdated` and `composer audit`; update as needed.
- [ ] Run full test suite; fix any failures.
- [ ] Confirm backups ran and test restore in non-prod.
- [ ] Review error logs and queue health.
- [ ] Plan key rotation if due (e.g. quarterly for APP_KEY in high-security setups).
