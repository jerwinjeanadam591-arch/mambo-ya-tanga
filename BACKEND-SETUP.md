# Mambo ya Tanga backend setup

The production prototype uses PHP 8.2+ and SQLite through PDO. The API is in
[`api/index.php`](./api/index.php), and the database is created automatically
at `data/mambo.sqlite` on the first request. This system manages food, juice,
pans, catering and other orders; it deliberately has no rooms or sleeping
accommodation module.

## Requirements

- PHP 8.2 or newer
- `pdo_sqlite` enabled
- HTTPS in production
- A web server configured to execute PHP

On Ubuntu:

```bash
sudo apt install php8.2-sqlite3
php -m | grep pdo_sqlite
```

## Start locally

```bash
MAMBO_ADMIN_EMAIL=admin@mamboyatanga.co.tz \
MAMBO_ADMIN_PASSWORD='replace-this-password' \
./start-mambo.sh
```

Open `http://127.0.0.1:8080/index.html` for the public site and
`http://127.0.0.1:8080/admin.html` for the staff dashboard.

The first database initialization seeds the admin account and menu catalog.
Set the environment variables before the first request; change the password
before deployment. Do not expose `data/` publicly. The included `.htaccess`
blocks database files on Apache.

Database initialization also creates the `admins.role` and `audit_logs` tables.
Existing administrators are migrated non-destructively and assigned the
`owner` role. Audit records are retained for staff logins and state-changing
administrative actions.

## API actions

- `GET api/index.php?action=health`
- `GET api/index.php?action=public`
- `GET api/index.php?action=delivery-zones` (active zones and server-controlled fees)
- `POST api/index.php?action=order`
- `GET api/index.php?action=order-lookup&phone=...&reference=...&token=...` (customer-authenticated status and detail lookup)
- `POST api/index.php?action=payment-instruction` (records a manual payment instruction; no provider call)
- `GET api/index.php?action=payment-transaction&id=...` (authenticated transaction inspection)
- `POST api/index.php?action=message`
- `POST api/index.php?action=subscribe`
- `POST api/index.php?action=login`
- `POST api/index.php?action=logout`
- `GET api/index.php?action=dashboard` (authenticated)
- `GET api/index.php?action=reports&days=30` (authenticated daily order and revenue summaries)
- `PATCH api/index.php?action=order-status` (authenticated)
- `GET api/index.php?action=order-detail&id=...` (authenticated staff order detail)
- `PATCH api/index.php?action=order-preparation` (authenticated; `Accepted` or `Ready`)
- `PATCH api/index.php?action=order-payment` (authenticated; `Pending`, `Paid`, `Failed`, or `Refunded`)
- `POST api/index.php?action=delivery-zone-save` (authenticated; creates or updates a zone)
- `DELETE api/index.php?action=delivery-zone-delete&id=...` (authenticated; only unused zones)
- `PATCH api/index.php?action=message-read&id=...` (authenticated)
- `POST api/index.php?action=menu-save` (authenticated; creates or updates an item)
- `PATCH api/index.php?action=menu-availability` (authenticated)
- `DELETE api/index.php?action=menu-delete&id=...` (authenticated; only unused items)

The authenticated session exposes the administrator `role` (`owner` for the
seeded account). Use `require_admin(['owner'])` or `admin_has_role('owner')`
for future owner-only actions.
Audit records are stored in SQLite and should be included in operational
retention and monitoring.

## Backups

Backups are intentionally CLI-only and are never served by the web API:

```bash
php scripts/backup.php
php scripts/backup.php /secure/backups/mambo-$(date +%Y%m%d).sqlite
```

The script uses SQLite's consistent `VACUUM INTO` backup operation, refuses to
overwrite an existing file, and creates the default backup directory with
restricted permissions. Store backups outside the document root and encrypt
or otherwise protect them according to your production policy.

The staff dashboard displays each order's individual items and quantities,
provides a WhatsApp link for contacting the customer, and supports menu
editing, availability changes, and safe deletion. Customers can call or open
WhatsApp directly from the ordering page and receive a WhatsApp handoff link
after placing an order.

Orders accept `payment_method` as `Cash`, `Mobile Money`, or `Card` (defaulting
to `Cash` for compatibility). Delivery orders must include an active
`delivery_zone_id` and an address. The API reads the zone fee from SQLite,
calculates `subtotal`, `delivery_fee`, and `total` server-side, and never
trusts a client-supplied total. Payment and preparation timestamps are stored
on orders. Existing databases are upgraded non-destructively on the next
request; no tables or rows are dropped.

Each new order receives a non-sequential `reference` and one-time-display
`token` in the create response. Store both securely and provide them with the
customer phone number to `order-lookup`; the token is stored only as a password
hash. Staff can retrieve a complete order with `order-detail` and update its
status through the existing authenticated endpoints.

State-changing staff requests validate the browser Origin/Referer and accept
the `csrf_token` returned by login as the `X-CSRF-Token` header. Existing
non-browser clients remain compatible when they do not send browser origin
headers. Public order, login, contact, subscription, and lookup requests have
SQLite-backed per-IP rate limits and return HTTP 429 when exceeded. Security
headers and a JSON exception handler are applied to every API response.

The current storage is suitable for a single hotel deployment or small
internal system. Configure HTTPS, backups, log monitoring, and a transactional
email/SMS provider before launch. Do not expose `data/` publicly.

## Payment and notification storage

Initialization creates `payment_transactions`, `notification_jobs`, and
`notification_attempts` with indexes and constrained, safe status values.
These are additive, non-destructive migrations: existing tables and rows are
not dropped. Manual payment instructions create a `pending` transaction linked
to an order. Provider adapters and SMS delivery are intentionally not invoked
until the onboarding checklist and credentials in `PROVIDER-ONBOARDING.md` are
complete.

Environment variable names and disabled-by-default provider placeholders are
listed in [`.env.example`](./.env.example). Load secrets through the web
server/process environment, never through HTML, JavaScript, or source control.

Public form validation is enforced both in the browser and on the server.
Phone numbers accept an optional leading `+` followed by 9 to 15 digits;
spaces, brackets, dots and hyphens are normalized. Email addresses are
trimmed, lowercased, and validated with PHP's email validator. Names,
subjects, addresses, notes, and messages have explicit length limits.
