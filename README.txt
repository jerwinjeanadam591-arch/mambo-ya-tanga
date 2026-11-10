MAMBO YA TANGA
Offline food ordering and management system
Mafinga - Iringa, Tanzania
Developed by Digicoretech

============================================================
START THE WEBSITE WITH ONE CLICK
============================================================

Linux:

1. Open this project folder.
2. Double-click "Mambo ya Tanga.desktop" to start the website.
   If Linux asks whether to trust the launcher, choose "Trust and Launch" or "Allow Launching".
   The .sh files are terminal scripts and may open in a text editor depending on your file-manager settings.
3. If the file opens in a text editor, right-click it, choose
   Properties > Permissions, enable "Allow executing file as program",
   then double-click it again.
4. Open:

   http://127.0.0.1:8000/index.html

The launcher checks PHP and SQLite support, starts the local server, and
opens the public website when xdg-open is available.

Terminal alternative:

   ./start-mambo.sh

Stop the website by returning to the launcher terminal and pressing:

   Ctrl+C

Windows:

Install PHP 8.2 or newer with the pdo_sqlite extension, then run:

   php -S 127.0.0.1:8000 -t .

Open http://127.0.0.1:8000/index.html in a browser.

============================================================
LOCAL DATA STORAGE
============================================================

Orders, menu items, staff data, payment records, audit logs, delivery zones
and subscribers are stored locally in:

   data/mambo.sqlite

The database is created automatically on the first API request.

Do not delete data/mambo.sqlite unless you intentionally want to reset the
local database. Always create a backup before resetting or upgrading:

   php scripts/backup.php data/backups/mambo-backup.sqlite

The data/ directory is protected from browser access by .htaccess.

============================================================
STAFF DASHBOARD
============================================================

Dashboard:

   http://127.0.0.1:8000/admin.html

Set the owner account credentials before the first API request initializes
the database:

   MAMBO_ADMIN_EMAIL=owner@mamboyatanga.co.tz \
   MAMBO_ADMIN_PASSWORD='replace-with-a-strong-unique-password' \
   ./start-mambo.sh

The password is required when the database has no staff account. Never use a
real password in a committed file. This is the only seeded staff account.
There are currently no customer usernames or customer passwords. Customers
place orders as guests and use their order reference, phone number and
private tracking token to check their order status.

Important: changing environment variables does not change an already-seeded
admin password in an existing SQLite database. For production, change the
password through a password-management feature or recreate/securly migrate
the database before launch.

============================================================
SYSTEM PAGES
============================================================

Public website:
   http://127.0.0.1:8000/index.html

Customer ordering:
   http://127.0.0.1:8000/order.html

Staff dashboard:
   http://127.0.0.1:8000/admin.html

Kitchen queue:
   http://127.0.0.1:8000/kitchen.html

Ordering policies:
   http://127.0.0.1:8000/policies.html

Health check:
   http://127.0.0.1:8000/api/index.php?action=health

============================================================
CURRENT PAYMENT MODE
============================================================

The local system accepts:

   Cash
   Mobile Money
   Card

Payments are currently confirmed manually by staff. No live payment gateway
is called until approved provider credentials and webhook documentation are
configured. Never advertise automatic online payment while the gateway is
disabled.

============================================================
REQUIREMENTS
============================================================

   PHP 8.2 or newer
   pdo_sqlite PHP extension
   sqlite3 PHP extension
   A modern web browser

Check PHP support:

   php -m | grep -E 'pdo_sqlite|sqlite3'

============================================================
TROUBLESHOOTING
============================================================

"pdo_sqlite is not enabled":
   Install the SQLite PHP extension for your PHP version, then run the
   launcher again.

"Address already in use":
   Another local server is already running on port 8000. Use the existing
   http://127.0.0.1:8000 address or stop the old process first.

Database errors:
   Confirm that the data/ directory is writable by PHP and that the SQLite
   extension is enabled.

============================================================
OFFLINE SECURITY
============================================================

This local site is intended for the business computer or local network.
Do not expose port 8000 directly to the public internet. For public launch,
use HTTPS hosting, replace the development password, configure backups, and
protect all provider secrets with server environment variables.
