# Deploy CBM Ondo to cPanel

This package prepares the application for hosting. It does not publish it, transfer the member database, add authentication, or configure scheduled backups.

## Requirements

Use PHP **8.4.1 or newer** (8.5 recommended), for both website and CLI. Laravel supports 8.3 but this project's locked Symfony dependencies require 8.4.1. Do not bypass Composer platform checks.

Enable ctype, curl, dom, fileinfo, filter, gd (with FreeType/PNG), hash, iconv, mbstring, openssl, pdo_mysql, session, simplexml, tokenizer, xml, xmlreader, xmlwriter, zip and zlib. PHP must be 64-bit. Use MySQL 8+ or a Laravel-supported MariaDB version.

Recommended PHP settings: memory_limit=256M, upload_max_filesize=2M, post_max_size=8M, max_execution_time=120, max_input_vars=1000. PHP's temporary directory and application storage must be writable. Host request timeouts can still limit large card ZIP/PDF downloads; split batches if needed.

Select PHP in MultiPHP Manager (some hosts use Select PHP Version). Confirm the CLI binary with your host; it may be /opt/cpanel/ea-php85/root/usr/bin/php. Below, php means that verified binary. Composer 2 and Terminal/SSH are recommended. Without Terminal, ask your host to run the commands below; do not create a public web installer.

## Build on Windows

Run in this project's PowerShell terminal:

    powershell -NoProfile -ExecutionPolicy Bypass -File .\build-cpanel.ps1

The script builds Vite assets and creates a timestamped ZIP in storage/app containing cbmondo and locked production dependencies. It excludes the live .env, databases, node_modules, development dependencies, cached configuration, sessions, logs, private uploads, old archives and duplicate checkouts. Local vendor is untouched. Node is not needed on cPanel.

This is an application package, not a database backup. Transfer required uploads and the database separately. Keep ZIPs and staging directories outside web-accessible folders.

## Layout A: configurable document root

Extract cbmondo into /home/CPANEL_USER/cbmondo. Set the domain document root to /home/CPANEL_USER/cbmondo/public, or ask your host to do it. Keep public/.htaccess.

Only public/ should be web-accessible. Do not upload the whole project into public_html with the development root rewrite file.

## Layout B: fixed main-domain public_html

cPanel normally does not allow changing the main-domain document root. Keep the application in /home/CPANEL_USER/cbmondo. Copy the CONTENTS of cbmondo/public (including .htaccess) into /home/CPANEL_USER/public_html. Also retain cbmondo/public: CLI rendering, fonts and build manifests use that copy.

Replace public_html/index.php with this code (assuming those sibling directory names):

    <?php
    use Illuminate\Http\Request;
    define('LARAVEL_START', microtime(true));
    $base = dirname(__DIR__).'/cbmondo';
    if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
        require $maintenance;
    }
    require $base.'/vendor/autoload.php';
    $app = require_once $base.'/bootstrap/app.php';
    $app->usePublicPath(__DIR__);
    $app->handleRequest(Request::capture());

Synchronize BOTH public asset copies on updates. Do not copy the project's root .htaccess or .env into public_html. Preserve PHP-handler directives added by cPanel to public_html/.htaccess.

## Environment and HTTPS

1. Create a database and database user in cPanel. Grant that user privileges on this database.
2. Inside the PRIVATE cbmondo directory, copy .env.cpanel.example to .env. Set the HTTPS domain in APP_URL and complete cPanel-prefixed database/user names and password.
3. Install SSL/AutoSSL and enable cPanel Force HTTPS Redirect after the certificate works. APP_URL alone does not redirect HTTP.
4. On a NEW installation generate the application key once:

    cd /home/CPANEL_USER/cbmondo
    php artisan key:generate --force

When moving existing encrypted data, preserve the existing APP_KEY instead. Never regenerate it during updates. Keep the key and environment file in a private backup. Leave APP_DEBUG=false.

The template uses secure encrypted database sessions, database cache, daily logs and synchronous queues; current features do not need a persistent worker. MAIL_MAILER=log does not send email: configure SMTP before relying on email delivery. Keep UTC timezone for current date-filter semantics.

Give storage and bootstrap/cache owner-write permissions (usually 755 for account-owned PHP-FPM; 775 only if group writing is required). Use 600 for .env if supported. Never use 777.

## Database: choose one path

### Fresh installation without existing members

    php artisan migrate --force
    php artisan db:seed --class=ElectionLocationSeeder --force

Seed election locations ONCE, only into empty location tables. The seeder inserts records and is not safe to rerun against populated tables.

### Move existing members while preserving IDs

Export the COMPLETE active database using native database tooling, then import it into the new empty database via phpMyAdmin or your host. Include all tables: especially memberships, lgas, wards, polling_units (including next_member_number), migrations, users and supporting tables. Preserve primary keys and auto-increment values.

Dashboard exports and the Excel/CSV importer are not database-migration tools: imports assign new IDs. MySQL source data needs a compatible MySQL target; SQLite requires a separate conversion plan. Do not assume database/database.sqlite is the active development database.

After restoration run pending migrations:

    php artisan migrate --force

Do NOT run the location seeder or migrate:fresh. Transfer required persistent uploads separately. Do not copy development sessions, import previews, logs or compiled caches. Verify member totals and polling-unit sequence counters before new registrations.

## Finish installation

From the private application directory with the correct PHP binary:

    composer check-platform-reqs --no-dev
    php artisan package:discover --ansi
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

The archive includes production vendor. For source-only deployment first run:

    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

If public uploads are needed, use php artisan storage:link for layout A. For layout B link public_html/storage to /home/CPANEL_USER/cbmondo/storage/app/public; ask your host if symlinks are restricted. Never expose storage/app/private.

No scheduled application tasks currently require cron. Add scheduler/worker configuration when later backup or background-job features require it.

## Verify on the real host

- Visit /up, homepage and /membership over HTTPS.
- Confirm CSS, JS, logos and fonts load without localhost URLs.
- Check restored member totals and location dropdowns.
- Test a card PNG, small ZIP/PDF batch, Excel export and import PREVIEW without saving fake records.
- Confirm /.env, /composer.json, /storage/logs/laravel.log and /vendor/autoload.php are not served.
- Inspect storage/logs privately on failure; do not enable public debug output.
- Authentication remains deferred at your request. Membership viewing, editing, exports and imports retain their current public access.

## Updates and recovery

Back up the full database and required files and retain the prior release. Use php artisan down while replacing code and migrating. Preserve production .env, APP_KEY and persistent storage. Install locked dependencies, run pending migrations, rebuild caches above, verify and run php artisan up. If setup fails, keep maintenance mode enabled while fixing it. Do not blindly roll back migrations: restore a matching tested database/files backup when schema changes require recovery. Scheduled backups and restoration verification remain Goal 8.

## Sources

- https://laravel.com/docs/13.x/deployment
- https://docs.cpanel.net/cpanel/domains/domains/manage-the-domain/
- https://docs.cpanel.net/cpanel/software/multiphp-manager-for-cpanel/

