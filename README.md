# CBM Ondo — Membership Management

A web platform for the **City Boy Movement, Ondo State**, built to register members, organize membership records across local governments, wards and polling units, and track registration activity.

The project combines public registration forms with a responsive membership dashboard, downloadable ID cards, reporting tools and spreadsheet imports.

## Features

### Registration and member records

- Registration for members with or without voter cards.
- Consistent validation for personal details, Nigerian phone numbers and location selections.
- Phone normalization and email checks to prevent duplicate registrations, including equivalent phone formats.
- Member detail and editing screens accessible from each table row.
- Automatically generated CBM IDs and polling-unit membership codes for members with voter cards.

### Dashboard and reporting

- KPIs for total registrations, members with voter cards and members without voter cards.
- Filters by LGA, ward, polling unit, voter-card status and registration date, plus member search.
- Today, this week, this month and custom date ranges.
- Geographic breakdowns and daily/monthly registration trends with chart image downloads.
- Sortable columns, selectable page sizes, column visibility and removable filter chips.
- Excel, CSV, JSON and SQL exports covering all matching records across pagination.

KPIs, charts and exports follow the active filters. With no filters applied, exports include all members.

### Membership ID cards

- Consistent card design and dimensions across devices.
- A QR code containing the member's CBM ID.
- Individual PNG downloads from the member view page.
- Bulk ZIP downloads and print-ready A4 PDFs for selected members or all matching members.

### Excel and CSV imports

- Downloadable templates and a location reference file.
- Support for `.xlsx` and UTF-8 CSV files, up to **500 rows and 2 MB** per upload.
- Preview before saving, with row-specific errors for duplicates, invalid values and inconsistent locations.
- Contact normalization and validation shared with registration.
- Confirmation rechecks the records and saves the complete batch in a database transaction.

Uploading a file only creates a preview. Confirming a valid import immediately adds its members; there is no separate approval stage. CBM IDs and applicable polling-unit membership codes are generated on save. Imports create new records and are not a database restore mechanism.

## Technology

| Area | Technology |
| --- | --- |
| Backend | PHP and Laravel 13 |
| Interface | Blade, CSS, JavaScript and Chart.js |
| Asset build | Vite |
| Database | Eloquent migrations; MySQL deployment configuration |
| QR codes | BaconQrCode |
| Spreadsheet imports | PhpSpreadsheet |
| PDF card sheets | FPDF |
| Tests | Pest |

## Local development

### Requirements

- **PHP 8.5** for the development environment. Locked production dependencies require at least PHP 8.4.1.
- Composer 2.
- Node.js 22.12+ and npm.
- MySQL, or SQLite for local development and tests.
- PHP database driver and the extensions required by Composer, including GD with FreeType/PNG, Zip, Mbstring and XML extensions for cards and spreadsheets.

### Installation

From a fresh checkout:

```bash
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
npm ci
```

Edit `.env` with your local application URL and database settings. The example defaults to SQLite. For a fresh SQLite setup, create the database file if it does not exist:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

For a new installation, generate the key and initialize the database:

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed --class=ElectionLocationSeeder
npm run build
php artisan serve
```

**Run the location seeder only once on empty location tables.** When restoring an existing database, preserve its IDs and polling-unit counters, run pending migrations, and skip the seeder. Preserve the existing application key when moving encrypted data.

Open the URL printed by Artisan. The homepage contains registration forms; the membership dashboard is at `/membership` and the import page is at `/membership/import`.

For frontend development, run `npm run dev` in a second terminal instead of rebuilding assets after each change.

### Tests

```bash
php artisan test --compact
```

The test configuration uses an in-memory SQLite database. Coverage includes registration, contact duplicates, location validation, member editing, filters, reporting, exports, ID cards, bulk downloads and imports.

## Hosting

The application can be deployed on compatible DirectAdmin or cPanel hosting. It requires the correct PHP version, database configuration, writable Laravel storage and compiled frontend assets.

Serve only the contents of `public/` through the domain's document root. Keep application code, `.env`, private storage and database backups outside the public web directory. Use HTTPS and `APP_DEBUG=false` in production.

The repository includes:

- [cPanel deployment guide](CPANEL.md), with production setup, database transfer and verification steps. DirectAdmin uses different panel menus and directory paths.
- [Production environment template](.env.cpanel.example), whose environment settings also apply to DirectAdmin after replacing the domain and database placeholders.
- [Windows deployment builder](build-cpanel.ps1), which packages production dependencies and compiled assets while excluding local secrets, databases and development files.

The deployment archive contains application code, not existing member data. Transfer a complete database separately when moving an installation so member IDs and sequence counters are preserved.

## Project status and roadmap

Implemented improvements include consistent registration validation, duplicate prevention, date filters and trends, table controls, bulk ID-card downloads and spreadsheet imports.

Remaining work:

- Member change history.
- Scheduled backups and verified recovery.

See [TODO.md](TODO.md) for the detailed checklist and completion history.

## Staff access

The `main` branch requires staff sign-in for the dashboard. The pre-authentication version is preserved on `codex/no-authentication`.

Super admins have full access and can create staff accounts, assign roles and deactivate accounts through **Staff accounts**. Admins can view members, manage executive positions, and assign or remove appointments. Admins cannot update member information, export data, download ID cards, import members or manage staff accounts. Restrictions are enforced on server routes as well as in the interface. Public membership registration remains available; public staff-account registration is disabled.

When deploying this release, preserve the production `.env` and storage, install the locked dependencies and build assets as usual, then run in the application directory:

```bash
php artisan migrate --force
php artisan staff:create-super-admin
php artisan optimize
```

The account command securely prompts for a name, email and password (minimum 12 characters). Run it once for the first super admin, then sign in at `/login` and create other accounts through **Staff accounts**. Existing user accounts receive no dashboard access until a super admin assigns a role and activates them. Configure production mail for password-reset emails. The last active super admin cannot be deactivated, demoted or self-deleted.

## Search engine optimization

The homepage, support, About, Contact and Updates pages have page-specific descriptions, canonical URLs and social-sharing metadata. The homepage also publishes Organization and WebSite structured data. The sitemap at /sitemap.xml includes only these public pages.

Other application routes, including membership records, exports, ID cards, account pages and event registration, return a noindex header. This controls search indexing; it does not restrict access or replace authentication.

After deploying these changes to DirectAdmin:

1. Set APP_URL=https://master.cbmondo.org in the production .env (use your preferred domain if this changes). Canonicals, social image URLs and the sitemap use this value.
2. Remove the OLD static robots.txt from BOTH public_html/robots.txt and cbmondo/public/robots.txt, if present. Laravel now serves /robots.txt dynamically so its sitemap URL matches APP_URL. Leaving the static file would bypass that route.
3. Deploy the new config/seo.php, SEO controller, SearchIndexing middleware, SEO Blade component, modified views, routes/web.php and bootstrap/app.php. For ordinary releases, deploy the current source as a whole while preserving .env and persistent storage; older deployment ZIPs do not include these changes.
4. Run php artisan config:cache, php artisan route:cache and php artisan view:cache in the private application directory. No database migration or new dependency is required for this SEO update.
5. Check /robots.txt and /sitemap.xml on the live domain, and verify the canonical URL in the homepage source.
6. Verify ownership in Google Search Console and submit https://master.cbmondo.org/sitemap.xml. For HTML-tag verification, place only the supplied token in GOOGLE_SITE_VERIFICATION in .env and rebuild the configuration cache. DNS verification is also an option.
7. Request indexing of the homepage, support, About, Contact and Updates pages using Search Console URL Inspection. Monitor indexing and search performance there.

Noindex removal from search results takes recrawling; previously indexed membership URLs may also need Search Console removal requests. Keep those URLs crawlable so Google can read their noindex headers. Search placement and indexing are determined by Google, not guaranteed by these settings.

Public content pages are in resources/views/about.blade.php, contact.blade.php and updates.blade.php, using the shared public-page component and public/css/public-pages.css. Replace the clearly marked office-address and social-profile placeholders on the Contact page when confirmed. The Updates page currently contains registration guidance; add verified activity reports when available.
