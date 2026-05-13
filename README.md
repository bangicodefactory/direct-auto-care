# Direct Auto Care

Garage & auto-service management platform. Laravel 11 + Inertia.js + React + MariaDB.

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 11 |
| Frontend | React 18 + Inertia.js |
| DB | MariaDB / MySQL |
| Styling | TailwindCSS + shadcn/ui |
| Auth | Laravel Breeze (Inertia/React preset) |
| Authz | Laravel Policies + Spatie Laravel-Permission |
| PDF | barryvdh/laravel-dompdf |
| Notifications | Laravel Notifications + Mail + Twilio (per ADR-001) |
| Queue | Laravel Queues + database/redis |
| Charts | Recharts (per ADR-003) |
| Calendar | react-big-calendar (per ADR-002) |

## Quickstart

```bash
git clone git@github.com:bangicodefactory/direct-auto-care.git
cd direct-auto-care
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run dev
php artisan serve
```

Default admin login: see `database/seeders/DatabaseSeeder.php`.

## Local development (XAMPP · Windows)

### Prerequisites

| Tool | Version | Where to get it |
|---|---|---|
| XAMPP | PHP 8.4 bundle | [apachefriends.org](https://www.apachefriends.org) |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org) |
| Node.js | 20 LTS | [nodejs.org](https://nodejs.org) |
| Git | any | [git-scm.com](https://git-scm.com) |

> PHP 8.4 is required (Symfony 8.x dependency). The XAMPP installer at `C:\xampp` ships with PHP 8.4 — verify with `php -v` in a new terminal.

---

### 1 — Start XAMPP services

Open **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`) and start:
- **Apache** (needed to serve phpMyAdmin if you want a GUI)
- **MySQL** (required — the app won't boot without it)

---

### 2 — Create the database

Open a terminal and run:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS direct_auto_care CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or open phpMyAdmin at `http://localhost/phpmyadmin` and create the `direct_auto_care` database manually.

---

### 3 — Clone and install dependencies

```powershell
git clone git@github.com:bangicodefactory/direct-auto-care.git
cd direct-auto-care
composer install
npm install
```

---

### 4 — Configure environment

```powershell
cp .env.example .env
php artisan key:generate
```

Open `.env` and confirm these values match your XAMPP setup (they should by default):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=direct_auto_care
DB_USERNAME=root
DB_PASSWORD=            # XAMPP MySQL has no root password by default

ADMIN_NAME="Administrateur"
ADMIN_EMAIL=admin@directautocare.ma
ADMIN_PASSWORD=changeme  # change this before going to production
```

---

### 5 — Run migrations and seed

```powershell
php artisan migrate:fresh --seed
```

This creates all tables and seeds:
- `admin` and `employee` roles
- One admin user (`ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env`)

---

### 6 — Start the dev servers

Open **two terminals** side by side:

**Terminal 1 — Laravel**
```powershell
php artisan serve
```
App available at `http://localhost:8000`

**Terminal 2 — Vite (hot reload)**
```powershell
npm run dev
```

Or run both in one terminal with the `dev` composer script:
```powershell
composer dev
```

---

### 7 — Log in

Navigate to `http://localhost:8000` and log in with the credentials you set in `.env`:

| Field | Default value |
|---|---|
| Email | `admin@directautocare.ma` |
| Password | `changeme` |

---

### Useful commands

```powershell
# Reset DB and re-seed
php artisan migrate:fresh --seed

# Fix PHP code style (PSR-12)
composer lint

# Check PHP code style without fixing
composer lint:check

# Fix JS/JSX formatting
npm run format

# Check JS/JSX formatting
npm run format:check

# Run ESLint
npm run lint

# Run all backend tests
php artisan test

# Production build (required before php artisan test in some setups)
npm run build
```

## Project management

- **Backlog & sprints**: [Linear → Direct Auto Care project](https://linear.app/ista3/project/direct-auto-care-eb1da6d6251f)
- **Spec**: `docs/SPEC.md` (or link to original spec doc)
- **ADRs**: `docs/adrs/`
- **Junior onboarding**: `docs/JUNIOR-ONBOARDING.md`

## Conventions

PSR-12 enforced by Laravel Pint. ESLint + Prettier for JS/JSX. PascalCase React components, snake_case migrations, kebab-case routes. French UI strings via `trans()` / i18n. Currency MAD (DH), dates dd/mm/yyyy.

Heavy logic in Service classes, validation in Form Requests, authorization in Policies. Soft deletes for customers/vehicles/invoices/services/products. DB transactions for multi-step writes.

## Git workflow

1. Branch per feature: `feature/customers`, `feature/invoice-numbering`, etc.
2. PR against `main`. CI must pass.
3. At least one approval required.
4. Squash on merge.

See `.github/pull_request_template.md` for the PR checklist.

## Definition of Done

A feature is shipped only when:
- Pint + ESLint pass
- `php artisan migrate:fresh --seed` runs cleanly
- French validation messages, shown inline
- Authorization enforced in Policy
- Works on mobile + tablet + desktop
- All Linear acceptance criteria met
- No console errors or Laravel deprecation warnings
- PR reviewed and merged

## License

Proprietary — © 2026 Bangi Code Factory. Not for redistribution.
