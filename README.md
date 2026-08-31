# CityStake Bookings

Property, booking, and operations management platform for serviced apartments in Abuja.

## About

CityStake Bookings is a staff-facing management system for running serviced-apartment properties end to end: reservations and the guest stay lifecycle, housekeeping turnovers, quality-control inspections, maintenance, procurement, finances, and reporting. It's built as a Laravel 12 monolith with an Inertia.js + Vue 3 front end, and is designed to run on modest shared hosting.

Public visitors can browse property pages; the bulk of the application is the authenticated management area under `/manage`, gated by role-based permissions.

## Features

### Bookings & the stay lifecycle
- Create, edit, and manage reservations (including walk-in and group bookings)
- Full stay lifecycle: confirm → check-in → (pause/resume) → check-out → complete
- Staff-recorded payments — POS, bank transfer, cash — plus installment plans and balance tracking
- Caution/security deposit handling with itemised charges and refunds
- Late-checkout requests, approvals, and settlement
- Booking adjustments, unit reassignment, and cross-grade upgrades
- Automated guest emails (confirmation, reminders, checkout) and staff notifications

### Properties
- Buildings, unit types, and units (CRUD)
- Per-building settings: standard check-in / check-out times, booking policy, checkout settings
- Versioned property policies linked from confirmation emails
- Blocked dates for maintenance / out-of-service periods

### Housekeeping & inspections
- Housekeeping board driving the turnover pipeline: needs cleaning → cleaning → ready for QA → guest ready (plus blocked / occupied)
- Per-unit action menu (request cleaning, mark cleaned, block for maintenance, …), list and property-grouped grid views
- Structured quality-control inspections (Pass / Concern / N-A checklists) organised into daily rounds
- Turnover lifecycle shared between reception and QC, with every hand-off timestamped
- Branded PDF inspection reports (photos, score, digital attestation) and QC analytics

### Operations
- Maintenance reports with a multi-step approval workflow
- Procurement requests with officer → accountant → CEO approval and purchasing
- Complaints tracking and task management
- Stock / inventory usage logging and vendor management

### Finance & analytics
- Financial transactions, revenue and expense tracking
- Executive dashboard and operational analytics (ApexCharts)
- Role-scoped home dashboards (reception, manager, accountant, procurement, QC)
- Excel exports (bookings) and dompdf documents (invoices, reports)

### Platform & administration
- Role-based access control (Spatie Laravel Permission), editable from the Roles page
- Platform changelogs / update announcements with a configurable audience and rich-text (sanitised) bodies
- Super-admin usage analytics (page-visit tracking)
- Dark mode, responsive layouts, and audit logging

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.5)
- **Frontend:** Vue 3 (`<script setup>`) + Inertia.js 2, built with Vite
- **Styling:** Tailwind CSS 4
- **Database:** MySQL
- **Auth & access:** Laravel Breeze, Sanctum, Spatie Laravel Permission
- **Key packages:** `barryvdh/laravel-dompdf` (PDFs), `maatwebsite/excel` (spreadsheets), `ezyang/htmlpurifier` (HTML sanitisation), `laravel/socialite`, `tightenco/ziggy`, `vue3-apexcharts`

## Requirements

- PHP 8.2+ (production runs on **8.5**)
- Composer
- Node.js 18+ & NPM
- MySQL 8.0+

## Installation

### 1. Clone and install dependencies
```bash
git clone <repo-url> citystake-bookings
cd citystake-bookings
composer install
npm install
```

### 2. Environment
```bash
cp .env.example .env
php artisan key:generate
```

Configure `.env` — database, mail, and the initial admin account:
```env
APP_NAME="CityStake"
APP_URL=http://citystake-bookings.test

DB_CONNECTION=mysql
DB_DATABASE=citystake_bookings
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Seeds the first super-admin (see Seeded data below)
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=change-me

# Mail
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS="bookings@citystake.net"
MAIL_FROM_NAME="${APP_NAME}"
```

### 3. Database
```bash
php artisan migrate
php artisan db:seed
```

> ⚠️ **Production caution:** never run a bare `php artisan db:seed` against production. The roles/permissions seeder re-syncs permissions and will overwrite role customisations made from the Roles page. In production apply schema and permission changes with `php artisan migrate --force` only (permissions ship as additive migrations).

### 4. Run in development
```bash
composer run dev   # serves Laravel, the queue, logs, and Vite together
```
Or individually:
```bash
php artisan serve   # http://localhost:8000
npm run dev         # Vite with hot reload
```

## Seeded data

`php artisan db:seed` runs:
- **Roles & permissions** — `super-admin`, `ceo`, `manager`, `accountant`, `receptionist`, `quality-control`, `head-of-procurement`, `staff`
- **Checklist template** — the inspection checklist items and their categories
- **Initial super-admin** — created from `ADMIN_EMAIL` / `ADMIN_PASSWORD` if both are set (no credentials are hard-coded)

Buildings, unit types, and units are created through the app UI rather than seeded.

## Roles

Access is permission-based. Typical roles and focus:

| Role | Focus |
|------|-------|
| `super-admin` | Full access, incl. usage analytics and platform changelogs |
| `ceo` | Oversight, financial visibility, final approvals |
| `manager` | Property operations, complaints, tasks |
| `accountant` | Finances, payments, approvals |
| `receptionist` | Bookings, check-in/out, availability, housekeeping requests |
| `quality-control` | Inspections and unit turnover QA |
| `head-of-procurement` | Procurement review and purchasing |

Permissions can be adjusted per-role from **Manage → Roles** without redeploying.

## Scheduled tasks

A single system cron entry drives Laravel's scheduler:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs, among others:
- `bookings:send-reminders` — daily 09:00 (check-in reminders)
- `bookings:send-checkout-reminders` — daily 09:30
- `bookings:remind-checkins` — hourly, 14:00–20:00
- `bookings:remind-installments` — daily 09:15
- `tasks:send-overdue-reminders` — daily 08:00
- `inspections:close-stale-rounds` — daily 02:00
- `inspections:prune-photos` — weekly, Mon 03:30
- `notifications:prune` / `page-visits:prune` — daily 03:00 / 03:10

## Production deployment (shared hosting)

Production runs on Hostinger shared hosting: **sync queue** (`QUEUE_CONNECTION=sync`, no queue workers), **cron-only** scheduling, no websockets. Notes:

- **PHP CLI path:** the panel PHP version drives the website; the SSH CLI may differ. Use the explicit alt-PHP binary for artisan/composer, e.g. `/opt/alt/php85/usr/bin/php artisan …` (or add a shell alias in `~/.bash_profile`).
- **Deploying PHP changes:** `git pull`, then `php artisan migrate --force`.
- **When `composer.lock` changes:** run `composer install --no-dev --optimize-autoloader` on the server.
- **Front-end changes:** run `npm run build` locally and upload the generated `public/build` directory (it is git-ignored).
- **Cron:** the `schedule:run` entry must use the full alt-PHP path (aliases don't apply to cron).

Optimise caches after deploying:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Project structure
```
├── app/
│   ├── Console/Commands/    # Scheduled command classes
│   ├── Http/Controllers/
│   │   └── Admin/           # Management-area controllers (/manage)
│   ├── Mail/                # Mailables
│   ├── Models/              # Eloquent models
│   ├── Notifications/       # In-app / email notifications
│   └── Services/            # Business logic (e.g. UnitTurnoverService)
├── resources/
│   ├── js/
│   │   ├── Components/      # Reusable Vue components
│   │   ├── Composables/     # Shared composables (dark mode, toasts, …)
│   │   ├── Layouts/         # Layout components
│   │   └── Pages/           # Inertia pages
│   └── views/
│       ├── emails/          # Email blade templates
│       └── reports/         # PDF (dompdf) templates
├── routes/
│   ├── web.php              # Web + management routes
│   └── console.php          # Scheduler definitions
└── database/
    ├── migrations/
    └── seeders/
```

## License

Proprietary and confidential.

## Support

For support, email info@citystake.net or contact us through the website.

## Acknowledgments

- Built with [Laravel](https://laravel.com) and [Inertia.js](https://inertiajs.com)
- UI powered by [Tailwind CSS](https://tailwindcss.com)
- Icons by [Lucide](https://lucide.dev)
- Charts by [ApexCharts](https://apexcharts.com)

---

**CityStake** — Premium Living in Abuja's Finest Locations
