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
