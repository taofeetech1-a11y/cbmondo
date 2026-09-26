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

- Authentication and access control for the membership dashboard and its actions. The current membership routes are not protected by authentication middleware.
- Member change history.
- Scheduled backups and verified recovery.

See [TODO.md](TODO.md) for the detailed checklist and completion history.
