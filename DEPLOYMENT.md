# خَطّ — Deployment & Launch Checklist

## Requirements

- PHP 8.4+ (ext: pdo_mysql, mbstring, gd, fileinfo, intl, zip, exif)
- MySQL 8.0+
- Composer 2, Node 22+, Redis (Tier 1 — optional at pilot tier)

## Environment variables (key ones)

| Variable | Notes |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` in prod |
| `APP_URL` | Real domain; https is force-enabled in production |
| `DB_*` | MySQL 8 credentials |
| `COMMERCE_GATEWAY` | `fake` (default) or `stripe` |
| `STRIPE_SECRET` / `STRIPE_WEBHOOK_SECRET` | Required when gateway = stripe |
| `COMMERCE_SHIPPING_FLAT_MINOR` | Flat shipping per seller group (default 4000 = 40 SAR) |
| `COMMISSIONS_DEPOSIT_PERCENT` | Deposit share of quote (default 30) |
| `MAIL_*` | Real SMTP for outbound notifications |
| `BACKUP_…` | See Backups below |

## Build & deploy steps

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan storage:link
```

## Background work (Tier 0 pilot)

- Scheduler: one cron entry `* * * * * php artisan schedule:run` — runs the nightly DB backup.
- Queue: database driver + `php artisan queue:work` under a supervisor (notifications/emails). At pilot volume the synchronous paths still function without a worker.

## Gateway switch (fake → stripe)

1. Set `COMMERCE_GATEWAY=stripe`, add `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`.
2. Register webhook endpoint `https://<domain>/webhooks/stripe` for `checkout.session.completed`.
3. Each seller completes Stripe Express onboarding (`stripe_charges_enabled` flips true).
4. Smoke-test: small commission deposit → balance → transfer appears in Stripe dashboard.

## Backups

- `php artisan app:backup-db --keep=14` — gzipped mysqldump into `storage/backups`, scheduled nightly 03:00.
- Restore drill: `gunzip -c <file> | mysql -u USER DBNAME` — verify row counts.

## Pre-launch checklist

- [ ] `APP_DEBUG=false`, real mail creds, gateway=stripe, webhook registered
- [ ] Load test passes budget (see LOADTEST.md)
- [ ] Backup ran + restore drill done
- [ ] Admin user exists, first seller onboarded to Stripe
- [ ] Error pages + notifications verified in both locales
