# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Event site for the **NephroCare Annual Walkathon 2026** (with Vivacity Multispeciality Hospital): a Laravel 11 backend serving a Vue 3 SPA via Inertia.js (no separate REST API — controllers return Inertia responses directly). Public side is a single landing page (hero, about, event details, registration form, FAQ, contact + support-ticket popup) plus a privacy policy page. A small self-rolled, mobile-first admin panel (custom controllers/pages under `Admin/`) lists registrations and support tickets, manages volunteer sub-users, and checks walkers in on event day by scanning their QR code.

## Common commands

```bash
# PHP dependencies / app setup
composer install
php artisan key:generate
php artisan migrate

# Frontend dependencies
npm install

# Local development (run both concurrently)
php artisan serve
npm run dev            # Vite dev server with HMR

# Production frontend build
npm run build

# Tests (Pest, configured via phpunit.xml)
php artisan test                                  # full suite
php artisan test --filter=AdminPanelTest           # single test file/class
php artisan test tests/Feature/AdminPanelTest.php   # (tests use the MySQL db `walkathon_testing`)
vendor/bin/pest                                    # equivalent, direct Pest runner

# Code style (Laravel Pint)
vendor/bin/pint                # fix
vendor/bin/pint --test         # check only

# Artisan helpers already wired up in this repo
php artisan route:list                             # inspect registered routes
```

There is no JS linter/test runner configured (no ESLint/Vitest in `package.json`) — don't assume one exists.

## Architecture

### Request flow: Laravel controller -> Inertia -> Vue page

Every page is a Vue SFC under `resources/js/Pages/`, returned via `Inertia::render('Some/Path')` and resolved by file path relative to `Pages/`.

Layout selection is automatic, by page-name prefix, in `resources/js/resolvePage.js` (shared by `app.js` and `ssr.js`):
- `Admin/*` -> `Layouts/AuthenticatedLayout.vue` (Tailwind)
- `Auth/*` -> no layout; the page wraps itself in `Layouts/GuestLayout.vue` (Tailwind)
- anything else -> `Layouts/Frontend/Layout.vue` (walkathon `Header.vue` + `Footer.vue` + `SeoHead`)

### Styling

- Frontend styling is plain CSS in `resources/css/walkathon.css`, imported by `Layouts/Frontend/Layout.vue` — not Tailwind. Images live in `public/images/` and are referenced with absolute `/images/...` paths.
- Tailwind (`resources/css/app.css`, includes the preflight reset) is imported only by the admin/auth layouts so it doesn't clash with the walkathon stylesheet.

### Routing

All pulled in from `routes/web.php`:
- `routes/frontend.php` — `/`, `/privacy-policy`, `POST /register`, `POST /support-ticket` (controllers in `App\Http\Controllers\Frontend`)
- `routes/admin.php` — under `admin/` prefix / `admin.` name prefix; everything except `login`/`login-post` sits behind `auth` + `active`, then grouped by role (see Auth & roles)
- `routes/artisan.php` — SSR control routes and misc artisan-triggerable routes (the latter only when `APP_ARTISAN=1`)

### Forms (registration + support ticket)

`Pages/Frontend/Index.vue` validates client-side (same rules as the old static `script.js`) then posts with Inertia `useForm`. `Frontend\RegistrationController` / `Frontend\SupportTicketController` validate again server-side (registration rules live in `App\Support\RegistrationForm`, shared with the admin "Add registration" form), persist `WalkathonRegistration` / `SupportTicket`, and `back()->with('success', ...)`. Server validation errors are keyed by the same field names as the form (`firstName`, `whatsapp`, `bloodGroup`, ...), so they map straight onto the inputs. Follow this pattern for new forms.

After a successful registration the controller redirects to `GET /thank-you/{token}` (`Frontend/ThankYou.vue`), not back. `token` is a random UUID so walkers can't open each other's pages. The bib number is `4` + the row id padded to 3 digits (4001, 4002 … 4999, 41000 …), set in `WalkathonRegistration::booted()`. QR codes are made server-side with `endroid/qr-code` (`WalkathonRegistration::qrPng()` / `qrDataUri()`). The bib card PNG download is drawn client-side on a canvas in `ThankYou.vue`. The confirmation email (`App\Mail\BibConfirmationMail`, view `mail/bib-confirmation.blade.php`) is sent by `App\Jobs\SendBibConfirmationMail` via `dispatchAfterResponse()`: no queue worker needed, failures are logged, and `mail_sent_at` stays null.

### Auth & roles

Single `users` table (`App\Models\User`) with `spatie/laravel-permission` roles. Role names and groups live in `App\Support\Roles` — use its constants, not string literals:
- `SUPER-ADMIN` — everything, incl. sub-user management (`admin/sub-users`, `Admin\SubUserController`)
- `EDITOR` — everything except sub-user management
- `SUB-USER` — volunteers: dashboard, registration list/detail with **limited fields**, QR scan, mark attended

`routes/admin.php` groups routes by `Roles::PANEL` / `Roles::ADMINS` / `SUPER_ADMIN`. Field-level visibility is decided server-side in `WalkathonRegistration::toPanelArray(bool $full)` — volunteers never receive email, WhatsApp, DOB, address or medical history. The `active` middleware (`EnsureUserIsActive`) signs out deactivated users. `HandleInertiaRequests::share()` exposes `auth.is_admin` / `auth.can_manage_sub_users` for hiding UI; the routes enforce the same rules. `DatabaseSeeder` creates the first super admin with credentials hard-coded in the seeder; `RoleSeeder` is idempotent.

### Event-day check-in

`Admin/Scan.vue` reads the walker's QR with `qr-scanner` (camera needs HTTPS or localhost) and calls `GET admin/scan?code=<QR text or bib>`; `Admin\ScanController` extracts the token (UUID) or bib number. `POST admin/registrations/{id}/attend` sets `attended_at` / `attended_by` (once); admins can undo via `.../unattend`. `Components/Admin/RegistrationDetails.vue` is the shared result card.

### Admin registrations

Admins (not sub-users) get two extra actions on the Registrations page: **Download Excel** (`GET admin/registrations/export`, streamed `.xlsx` via `openspout/openspout`, honours the list's `q`/`status` filters, times in Asia/Kolkata) and **Add registration** (`admin/registrations/create` → `store`, same `RegistrationForm` rules, sets `created_by`, optional bib email). `{registration}` routes are constrained with `whereNumber` so `export`/`create` don't collide with them.

### Admin UI

Tailwind, mobile-first: off-canvas sidebar under `lg`, card lists on phones (`md:hidden`) and tables from `md` up. Server flash `success` / `error` is shown by `AuthenticatedLayout.vue`.

### SEO

`config/seo.php` (keyed by route name) -> `App\Support\Seo` -> shared `seo` prop -> rendered by `resources/views/partials/seo.blade.php` and `Components/SeoHead.vue`. Pages shouldn't set their own `<title>`.

### Frontend conventions

- Import alias `@/*` -> `resources/js/*`.
- `Head`, `Link` and `Icon` (`@iconify/vue`) are registered globally; `boxicons` web component is used in the admin nav.
- Routes are referenced from Vue via Ziggy's `route()` helper.
