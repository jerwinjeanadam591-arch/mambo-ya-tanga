# Mambo ya Tanga final launch mode

The current production mode accepts online orders without charging customers
inside the website. Customers choose Cash, Mobile Money, or Card, and staff
confirm the payment manually. This keeps the ordering system usable while
payment-provider onboarding is pending.

## Launch checklist

1. Set a strong production admin email and password using environment variables.
2. Deploy behind HTTPS on a domain owned by Mambo ya Tanga.
3. Confirm the business phone and WhatsApp number in the public pages.
4. Review every menu item, category, price, image, and availability flag.
5. Review delivery zones and fees in the staff dashboard.
6. Confirm opening hours, preparation times, allergy information, and policies.
7. Create and test a database backup:

   ```bash
   php scripts/backup.php /secure/backups/mambo-$(date +%Y%m%d).sqlite
   ```

8. Place one pickup, delivery, and catering test order from a mobile phone.
9. Confirm the order appears in the staff dashboard and kitchen queue.
10. Confirm staff can update preparation, payment, and delivery statuses.

## Customer flow

- The customer selects items and service type.
- The server recalculates prices and delivery fees.
- The order is stored with a unique reference and private tracking token.
- The customer receives the tracking details and WhatsApp handoff.
- Mobile Money customers receive a manual payment instruction record.
- Staff confirm payment after checking the approved merchant account.

## Payment limitation

No payment provider is called by the current website. Do not advertise
automatic online payment until a licensed provider account, sandbox tests,
signed webhook verification, refund process, and production credentials are
configured.
