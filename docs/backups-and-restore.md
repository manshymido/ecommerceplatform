# Backups and Restore (Phase 10)

Scheduled backups and restore procedures for the e-commerce backend.

## What to back up

- **Database** – all application data (users, orders, products, etc.).
- **Environment** – `.env` (or use a secrets manager; keep a secure copy for DR).
- **Storage** – `storage/app` (uploaded files, private assets) if used.
- **Config** – any non-repo config (e.g. server configs) if applicable.

## Database backups

### SQLite (default local/dev)

```bash
cd backend
cp storage/database/database.sqlite storage/database/database.sqlite.backup-$(date +%Y%m%d-%H%M)
# Or to a backup directory:
cp storage/database/database.sqlite /backups/razergold-db-$(date +%Y%m%d-%H%M).sqlite
```

### MySQL/MariaDB

```bash
# Full dump (run from a host with DB access)
mysqldump -u USER -p DATABASE_NAME > backup-$(date +%Y%m%d-%H%M).sql

# With gzip
mysqldump -u USER -p DATABASE_NAME | gzip > backup-$(date +%Y%m%d-%H%M).sql.gz
```

### Scheduling (cron)

Example: daily at 02:00 (SQLite):

```cron
0 2 * * * cd /path/to/backend && cp storage/database/database.sqlite /backups/razergold-db-$(date +\%Y\%m\%d).sqlite
```

For MySQL, call `mysqldump` in a similar cron job. Retain backups per your RPO (e.g. 7 daily, 4 weekly).

## Restore procedures

### SQLite

1. Stop the application (or put in maintenance mode).
2. Replace the database file:
   ```bash
   cp /backups/razergold-db-YYYYMMDD.sqlite backend/storage/database/database.sqlite
   ```
3. Restore correct ownership/permissions.
4. Run migrations if the backup is from an older version: `php artisan migrate`.
5. Clear config/cache: `php artisan config:clear`.
6. Bring the application back up.

### MySQL

1. Stop the application or enable maintenance mode.
2. Restore the dump:
   ```bash
   mysql -u USER -p DATABASE_NAME < backup-YYYYMMDD-HHMM.sql
   ```
   If compressed: `gunzip -c backup-YYYYMMDD-HHMM.sql.gz | mysql -u USER -p DATABASE_NAME`
3. Run migrations if needed: `php artisan migrate`.
4. Restart the application.

## Storage (files)

If you store uploads under `storage/app`, back them up with your preferred tool (rsync, object storage sync, VM snapshots). Restore by copying files back to `storage/app` with correct permissions (e.g. `storage:link` if using public disk).

## Recovery testing

- Periodically restore a backup into a non-production environment and run smoke tests.
- Document any environment-specific steps (e.g. DB name, paths) in this doc or in [runbooks.md](runbooks.md).
