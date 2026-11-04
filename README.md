# Azurite Rental System

A PHP and MariaDB rental application for coastal stays in Kenya. Tenants explore houses and request bookings; hosts manage their own listings, availability and guests; administrators manage the whole application. Prices are in Kenyan shillings and dates use Africa/Nairobi time.

## Requirements

- PHP 8.1 or later with PDO MySQL, Fileinfo and Calendar extensions.
- MariaDB 10.4 or later, or a compatible MySQL server using InnoDB.
- Apache 2.4 with `.htaccess` overrides enabled. XAMPP supplies these components.
- Python 3 for the optional regression suite.

No Composer or Node installation is needed.

## Fresh local installation

1. Clone this repository into `C:/xampp/htdocs/Azurite-Rental-System`.
2. Start Apache and MySQL in XAMPP.
3. Copy `includes/config.example.php` to `includes/config.local.php` and set your database connection. Alternatively set `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASS` in the server environment.
4. Create a new demo database from the project folder:

   ```powershell
   C:/xampp/php/php.exe tools/setup.php azurite_rental
   ```

   The setup command refuses to overwrite an existing database. To use another name, pass that name and update `config.local.php` to match. `database/database.sql` is also available for a manual **demo reset**; it drops application tables and must never be imported over records you want to preserve.

5. Open [the local application](http://localhost/Azurite-Rental-System/public/). The project root also redirects there. Deployment folder names are detected automatically.

Demo sign-ins:

| Role | Username | Password |
| --- | --- | --- |
| Administrator | `admin` | `admin123` |
| Host | `host` | `123` |
| Tenant | `user` | `123` |

These accounts are for local demonstrations. Change their passwords before using the application outside a demo environment. New passwords require at least eight characters. Existing MD5 passwords are accepted once and upgraded to PHP password hashes after successful sign-in. The legacy database column name `password_md5` is retained for compatibility.

## Upgrade an existing, readable database

Back up the database and uploaded files first. Import **only** `database/upgrade.sql` into the database selected by your configuration. It widens the password column without deleting accounts, houses or bookings. Do not import the demo reset for an upgrade.

Local repair note: the original `azurite_rental` database reported that all seven tables “don't exist in engine.” Its records could not be read and it was left intact. This workstation uses a separate `azurite_rental_repaired` demo database through ignored local configuration. That configuration is not committed. Recovering old records requires a usable database backup or separate MariaDB tablespace recovery; the source-code repair does not recover those records.

## Booking and account behavior

- Both booking submission URLs use the same service and validation. Only tenants may book. Dates must be real calendar dates, check-in cannot be in the past, and check-out must follow check-in. The booking form supports one to four guests.
- Check-out is exclusive, so one guest may arrive on the previous guest's departure date.
- Pending, approved, confirmed and checked-in bookings reserve nights. Host-blocked nights cannot be booked. Room-row locks serialize bookings, availability changes and status updates to prevent conflicting reservations.
- Prices are calculated on the server from nightly rates and the number of nights. New requests are pending until approved.
- Hosts can manage only their own houses and bookings. Booking details and identity documents require authorization.
- All POST forms have session-bound CSRF tokens. Legacy delete, status, availability and sign-out links display a confirmation form before changing data.
- Inactive accounts cannot sign in. Sign-in regenerates the session identifier. Sessions are stored under ignored `storage/sessions`.
- Uploads are limited to 5 MB and checked by content type. Images accept JPG/PNG; identity documents also accept PDF. New identity documents are stored outside `public`, under `storage/id_cards`. Legacy identity files are served through `public/document.php` and direct Apache access is denied.
- The house search filters location/name and availability dates together. The public calendar uses the same reserving statuses as the booking service.

## Deployment and private files

Use Apache with overrides enabled so the included `.htaccess` rules apply. Prefer `public/` as the document root; when serving the repository under XAMPP, the root rules block internal folders, SQL files and archives. The PHP development server does not enforce `.htaccess` and is intended only for isolated local testing.

Give PHP write access to `storage/` and `public/uploads/`. Keep `includes/config.local.php`, environment files, session files and identity uploads out of Git. Uploaded avatars and identity documents previously tracked in this repository have been removed from the current source tree while local files are retained. Earlier Git commits can still contain them; removing historical copies requires a separate coordinated history cleanup.

## Verification

Run from the project folder:

```powershell
python tests/run.py
```

Set `PHP_BIN` if PHP is installed elsewhere. The test database account needs permission to create and drop databases. The suite checks PHP syntax, creates an isolated `azurite_test_*` database, exercises booking validation and audit behavior, and starts a loopback-only PHP server for tenant, host and admin HTTP checks. It removes its database when finished and never imports demo data into your configured application database.

## Project structure

```text
database/     Demo schema and non-destructive upgrade
includes/     Shared authentication, booking service, layouts and configuration
public/       Tenant pages, host/admin panels and public assets
storage/      Private runtime files (ignored)
tests/        Syntax, database and HTTP regression checks
tools/        CLI-only fresh database setup
```
