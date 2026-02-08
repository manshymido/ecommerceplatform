# Runbooks – Incidents and Rollbacks (Phase 10)

Operational procedures for handling incidents and rolling back deployments.

## Incident handling (high level)

1. **Triage** – Confirm scope and impact (which service, which users, errors vs latency).
2. **Mitigate** – Take actions to stabilise (scale up, disable non-critical features, rate limit, switch to read-only if safe).
3. **Communicate** – Notify stakeholders and set expectations (status page, ETA).
4. **Resolve** – Fix root cause or roll back (see below).
5. **Review** – Post-incident review and update runbooks/tests.

## Application rollback

Used when a new deployment causes errors or instability.

### If using Docker/containers

1. Identify the last known good image tag or artifact.
2. Redeploy that image to staging first; run smoke tests.
3. Redeploy the same image to production (or switch load balancer to the previous version).
4. If the bad release ran new migrations, assess:
   - If migrations are backwards-compatible, the old code can keep running.
   - If not, you may need to run a down-migration or data fix before rollback; plan this in advance.

### If deploying from Git

1. Check out the previous release tag or commit: `git checkout <previous-tag>`.
2. Redeploy that commit (same process you use for normal deploy: e.g. pull code, `composer install --no-dev`, `php artisan migrate` only if safe, restart PHP/Octane).
3. Clear caches: `php artisan config:clear`, `php artisan cache:clear` if needed.

### After rollback

- Verify health: hit `/up` and `/ready`, check logs.
- Monitor errors and latency; confirm the incident is resolved.
- Schedule a post-mortem and update this runbook with any new steps.

## Database migrations and rollback

- Prefer **backwards-compatible** migrations: add columns/tables first, deploy code that can work with old and new schema, then remove old columns in a later release.
- **Avoid** single-step destructive changes (dropping columns still in use).
- If you must roll back a migration: `php artisan migrate:rollback` (only if the migration is designed to be reversible). Test rollback in staging first.

## High-severity scenarios (short checklist)

| Scenario              | Immediate actions |
|-----------------------|-------------------|
| Site down / 5xx        | Check `/ready`; restart app/workers; check DB/Redis; consider rollback. |
| High error rate       | Check logs; roll back last deploy if correlated; scale or throttle. |
| High latency         | Check DB, cache, queue depth; scale app/DB; disable heavy features if needed. |
| Data concern          | Stop writes if necessary; investigate; restore from backup if required (see [backups-and-restore.md](backups-and-restore.md)). |
| Suspected breach      | Rotate secrets (APP_KEY, DB, Stripe, etc.); revoke tokens; see [maintenance.md](maintenance.md). |

## Maintenance mode

To put the app in maintenance mode during deploy or fixes:

```bash
cd backend
php artisan down
# … perform work …
php artisan up
```

Optionally: `php artisan down --refresh=15` to allow a short refresh window for long-running requests.
