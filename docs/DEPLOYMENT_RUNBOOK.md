# Deployment and recovery runbook

This project uses SQLite for its built-in backup and restore-test commands. Do not use those commands as the sole backup plan if the production database is MySQL or PostgreSQL. Never commit `.env`, signing keys, or backup archives.

## Before publishing

1. Set production values in the server's `.env`: `APP_ENV=production`, `APP_DEBUG=false`, an HTTPS `APP_URL`, unique `APP_KEY` and `DOCUMENT_SIGNING_KEY`, real Turnstile keys, SMTP credentials, secure session cookies, and `QUEUE_CONNECTION=database` (or Redis).
2. Install dependencies, then run `php artisan migrate --force` and `npm run build` on the deployed release. Keep the web root at `public/` only. Do not expose `storage/` or the project root.
3. Start a supervised queue worker with `php artisan queue:work --tries=3 --timeout=60`. Restart it on each deployment with `php artisan queue:restart`. Email notifications for ready-to-release, completed, and rejected requests depend on this worker.
4. Run `php artisan production:check`. Resolve every FAIL before public access. Verify HTTPS, login, student notifications, document issuance, and receipt authorization manually.

## Backup and restore drill

1. On SQLite deployments, run `php artisan backup:create --name=before-deploy`. The ZIP contains SQLite snapshots and private uploads. Move an encrypted copy to restricted off-server storage; never to `public/` or GitHub.
2. Run `php artisan backup:restore-test BACKUP_FILENAME.zip`. This extracts and verifies an isolated copy; it does not replace the live database.
3. Schedule regular backups and isolated restore tests. Check the age and result of the latest backup after every deployment. For non-SQLite databases, use a database-specific consistent dump and a separately tested private-file backup.
4. If recovery is needed, stop writers, preserve the damaged data, restore only after choosing the correct backup and obtaining operator approval, then rerun `production:check` and smoke tests. The built-in restore-test is **not** a live restore command.

For local UAT, follow [UAT_CHECKLIST.md](UAT_CHECKLIST.md). Local HTTP will intentionally fail the production checks; do not weaken them to make a development machine appear deployment-ready.
