# Direct Auto Care — Claude context

Claude Code reads this file at the start of every session in this repo. Keep it up-to-date.

## What we're building

Laravel 11 + Inertia.js + React monolith for a Moroccan auto-service garage. French UI. Mobile + desktop. Moroccan fiscal compliance on invoicing.

Full spec: `~/Documents/Claude/Projects/SiyanaPlus/SiyanaPlus.md` on Ahmed's machine. Linear project: https://linear.app/ista3/project/direct-auto-care-eb1da6d6251f

## Stack — do not deviate without ADR sign-off

| Layer | Tech |
|---|---|
| Backend | Laravel 11, PHP 8.4 |
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

## Backend conventions

- **PSR-12** via Laravel Pint. Always run `composer lint` before suggesting a commit.
- **Form Requests** for all validation. Never validate in controllers.
- **Service classes** for non-trivial logic. Controllers stay thin.
- **Eloquent relationships**. Raw SQL only if necessary.
- **Soft deletes** for: customers, vehicles, invoices, services, products.
- **DB transactions** for multi-step writes (invoice + items + stock decrement = one transaction).
- **Policies** on every model. Use `authorizeResource` in controllers.

## Frontend conventions

- **ESLint + Prettier**. Always run `npm run lint` before suggesting a commit.
- One Inertia page per main route in `resources/js/Pages/`.
- Reusable UI in `resources/js/Components/`.
- Use **Inertia's `useForm()`** — never raw fetch/axios.
- Inline validation errors under each field.
- Toasts via **sonner** — never `alert()`.

## Naming

- React components: PascalCase
- Migrations: snake_case
- Routes: kebab-case
- DB columns: snake_case

## Localization

- French is the default UI language.
- All user-facing strings through `trans()` (PHP) or i18n (JS).
- Date format: dd/mm/yyyy. Currency: MAD (DH).

## Definition of Done

Before claiming any task complete:

- [ ] Pint + ESLint + Prettier pass
- [ ] `php artisan migrate:fresh --seed` runs cleanly
- [ ] French validation messages, inline
- [ ] Authorization at Policy level, tested as employee role
- [ ] Mobile (iPhone 14 + Galaxy S21) + tablet + desktop verified
- [ ] All Linear acceptance criteria met
- [ ] No console errors, no Laravel deprecation warnings
- [ ] PR template checklist filled, screenshots attached for UI

## Git workflow

- Branch per feature, use the Linear-generated branch name (each issue has a "Copy git branch name" button). Example: `ahmedchioua/ist-7-scaffold-laravel-11-project-breeze-inertiareact`.
- PR title starts with the Linear ID: `IST-7: scaffold Laravel 11 + Breeze`.
- Commits: present tense, lowercase. Optionally reference `IST-XX`.
- Squash on merge.

## Critical — do NOT

- Mock the database in tests for invoice numbering or stock decrements. Use real DB row locks.
- Hard-delete invoices. Soft delete only, admin only.
- Allow `quantity_on_hand` to go negative. Block at StockService with a French error.
- Validate in controllers. Always Form Request.
- Put authz in frontend. Always Policy server-side.
- Add a new Composer or NPM package without asking Ahmed first.

## npm / supply-chain hygiene (IST-88)

- **Always use `npm ci`**, never `npm install`, when working from main or a clean checkout. `npm ci` is reproducible from the lockfile; `npm install` may silently resolve newer transitive versions.
- **Commit `package-lock.json`** always. Never gitignore it.
- **postinstall scripts are disabled** via `.npmrc` (`ignore-scripts=true`). If a new package requires scripts (e.g. native bindings), raise it explicitly with Ahmed before enabling.
- Before approving any Dependabot PR that touches React, Inertia, Vite, or Tailwind core, cross-check against the IOC list: https://socket.dev/supply-chain-attacks/mini-shai-hulud
- Recheck `npm ls @tanstack @mistralai @squawk` weekly until the Mini Shai-Hulud incident is declared closed.

## Claude Code + MCP allowlist (IST-88)

- **Do not run Claude Code inside CI runners.** Dev machines only.
- MCP server allowlist: only first-party (Anthropic) + verified maintainers (Linear, GitHub). Do not add unknown MCP servers.
- If anything unfamiliar appears under `~/.claude/` or `%APPDATA%\claude-code\`, flag it to Ahmed before proceeding.

## Open ADRs awaiting decision

- ADR-001 — WhatsApp provider (Twilio recommended) — needed by Sprint 4
- ADR-002 — Calendar library (react-big-calendar recommended) — needed by Sprint 3
- ADR-003 — Charts library (Recharts recommended) — needed by Sprint 4

Drafts on Ahmed's machine: `~/Documents/Claude/Projects/SiyanaPlus/adrs/`.

## How I (Claude) should work in this repo

- Run linters automatically after non-trivial changes.
- When implementing a Linear issue, **read the issue body** before writing code — it contains the spec excerpt + acceptance criteria + scope.
- If the issue is ambiguous, surface the ambiguity instead of guessing.
- Suggest a single commit at a time with a clean message; let Ahmed review before pushing.
- For PRs, fill the PR template completely. Include screenshots when UI changes are involved.
- French strings in code: write them correctly with accents. Don't approximate.
