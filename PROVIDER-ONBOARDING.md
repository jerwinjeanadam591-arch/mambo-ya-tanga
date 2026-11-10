# Mambo ya Tanga payment and SMS onboarding

This project is ready to receive provider credentials, but it does not make
fake payment requests or store secrets in source code. Each provider must
first approve Mambo ya Tanga and provide a sandbox or production API contract.

## Business information to prepare

- Registered business name and certificate
- TIN and tax documents
- Owner/director identification
- Business phone and email
- Settlement bank account
- Physical business address in Mafinga, Iringa
- Website domain and HTTPS URL
- Customer support number
- Refund and cancellation policy
- Expected monthly transaction and SMS volume

## Payment providers

Request a merchant/API integration pack from each provider:

- Vodacom M-Pesa
- HaloPesa
- Mixx by Yas
- Airtel Money
- CRDB Bank
- NMB Bank

Ask each provider to confirm:

1. Tanzania and TZS support.
2. Merchant collection or checkout API availability.
3. Sandbox credentials and test wallet numbers.
4. Production credentials and activation steps.
5. Signed webhook or callback support.
6. Signature algorithm and webhook secret.
7. Idempotency or duplicate-transaction guidance.
8. Refund and reversal API support.
9. Settlement timing and reconciliation reports.
10. Transaction fees, setup fees, monthly minimums, VAT and withdrawal fees.
11. Allowed callback IPs, TLS requirements and uptime/support process.

Do not send a customer payment to a personal number. Use an approved merchant
number or bank collection account.

## Credentials to supply securely

Never put these values in HTML, JavaScript, Git, or a chat message:

```env
MAMBO_PAYMENT_PROVIDER=
MAMBO_PAYMENT_ENV=sandbox
MAMBO_PAYMENT_PUBLIC_KEY=
MAMBO_PAYMENT_SECRET_KEY=
MAMBO_PAYMENT_MERCHANT_ID=
MAMBO_PAYMENT_WEBHOOK_SECRET=
MAMBO_PAYMENT_CALLBACK_URL=https://mamboyatanga.co.tz/api/index.php?action=payment-webhook
```

For a multi-provider gateway, use one adapter configuration per provider and
enable only providers that have passed sandbox and reconciliation testing.

## SMS onboarding

Choose an SMS provider with Tanzania delivery and obtain:

- API base URL
- API key and secret
- Approved sender ID
- Delivery-report/webhook credentials
- Test destination numbers
- Per-message tariff and balance/credit terms

Recommended SMS events:

- Order received
- Order accepted
- Order ready
- Order delivered
- Payment confirmed
- Order cancelled

SMS must be queued after the order is saved. A provider outage must never
discard an order.

## Implementation sequence

1. Open merchant accounts and request sandbox access.
2. Confirm which provider will be the primary aggregator.
3. Configure the provider in server environment variables.
4. Implement the provider adapter and signed webhook verifier.
5. Test successful, failed, cancelled, expired and duplicate payments.
6. Reconcile gateway totals against the dashboard and settlement report.
7. Request production activation.
8. Run one low-value live test and verify settlement.
9. Enable SMS after payment/order events are confirmed.
10. Rotate all sandbox credentials and create a production backup.

The current application continues to support Cash and manual Mobile Money
confirmation while provider onboarding is in progress.

## Integration-ready storage and API

The provider registry in `config/providers.php` contains disabled-by-default
placeholders for `mpesa`, `halopesa`, `mixx_by_yas`, `airtelmoney`, `crdb`,
`nmb`, and `sms`. Enabling a flag only advertises configuration; it does not
make an HTTP request. Implement and test a dedicated adapter only after the
provider's signed API/webhook contract is approved.

`POST api/index.php?action=payment-instruction` accepts an order `reference`,
the normalized customer `phone`, and an optional registry provider key. It
creates a `pending` record and returns instructions for staff-confirmed
payment. `GET api/index.php?action=payment-transaction&id=...` is restricted
to an authenticated administrator. Never mark a transaction successful from a
client request; confirmation must come from staff reconciliation or a verified
provider webhook.

Notification work is represented by `notification_jobs` and
`notification_attempts`. Queue an SMS only after the business event is
committed, and use `queued`, `processing`, `sent`, `failed`, or `cancelled`
statuses. Do not place API keys in the database payload or logs.
