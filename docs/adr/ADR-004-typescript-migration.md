# ADR-004 — Migrate the frontend from JSX to TypeScript (TSX)

- **Status:** Proposed — awaiting Ahmed's sign-off (stack deviation per `CLAUDE.md`)
- **Date:** 2026-06-15
- **Deciders:** Ahmed (owner)
- **Supersedes / relates to:** IST-118 (shadcn migration — see `docs/shadcn-migration-audit.md`)

> This is a **stack deviation**. `CLAUDE.md` pins the frontend to "React 18 + Inertia.js" with
> no TypeScript, and forbids changing the stack without ADR sign-off. Nothing in this ADR is
> actioned until Ahmed approves. It also adds dev dependencies, which `CLAUDE.md` requires
> Ahmed to approve separately.

---

## Context

The question raised: *can the whole app be moved to TSX and shadcn blocks?*

Current frontend state (measured 2026-06-15):

| Fact | Value |
|---|---|
| `.jsx` files | 98 (46 Pages, 49 Components, 28 of which are `ui/` shadcn primitives) |
| Frontend LOC | ~11 970 |
| TypeScript configured | **No** — no `tsconfig.json`, no `typescript`/`@types/*` deps |
| `components.json` `"tsx"` | `false` (shadcn currently pulls primitives as `.jsx`) |
| Page resolution | `import.meta.glob('./Pages/**/*.jsx')` in `resources/js/app.jsx` (`.jsx` only) |
| Path alias | `@ → resources/js` (Vite + `jsconfig.json`) |

Two separate things are being conflated and should be decided independently:

1. **TSX migration** — convert `.jsx` → `.tsx` and add types. This ADR.
2. **shadcn "blocks"** — the prebuilt page-level blocks (`dashboard-01`, `sidebar-07`, etc.).
   These are **Next.js / App Router demos with mock data**, not Inertia drop-ins. We already
   verified `dashboard-01` was never applied — our dashboard is bespoke. Blocks can be used as
   *visual reference* but are not "moved into" an Inertia app wholesale. **Out of scope here**;
   the shadcn *primitive* migration is already tracked by IST-118.

So the real decision is: **do we adopt TypeScript for the frontend, and if so, how?**

## Decision

**Adopt TypeScript incrementally, leaf-first, with `.jsx` and `.tsx` coexisting** under
`allowJs`. No big-bang rewrite. Each PR converts a small, self-contained slice and stays green.

Rationale for *incremental* over *all-at-once*:

- 98 files / ~12k LOC is multi-day work. A single mega-PR is unreviewable and unmergeable
  against an active sprint, and would collide with the in-flight IST-118 shadcn migration.
- `allowJs: true` + `checkJs: false` lets typed `.tsx` and untyped `.jsx` import each other,
  so we can stop and ship at any point without a half-broken tree.
- Leaf-first (primitives → shared components → pages) means types flow *upward*: by the time we
  type a page, the components it consumes are already typed and give us inference for free.

### Tooling to add (all `--dev`, needs package approval)

- `typescript`
- `@types/react`, `@types/react-dom`, `@types/node`
- `typescript-eslint` (replaces hand-rolled JS-only ESLint parsing for `.ts`/`.tsx`)

No runtime deps change. Vite already transpiles TSX via `@vitejs/plugin-react` + esbuild — **no
type-checking happens at build time**, so a separate `tsc --noEmit` check is added as a script
and (later) a lefthook/CI gate.

### Config changes

- **`tsconfig.json`** (new): `allowJs: true`, `checkJs: false`, `strict: true`, `jsx: react-jsx`,
  `noEmit: true`, `moduleResolution: bundler`, and the `@/*` + `ziggy-js` paths migrated from the
  existing `jsconfig.json` (which is then removed). `strict` from day one — it's far cheaper to
  start strict on new `.tsx` than to retrofit it later.
- **`resources/js/app.jsx` → `app.tsx`**: widen the page glob to
  `import.meta.glob('./Pages/**/*.{jsx,tsx}')` so converted and un-converted pages both resolve
  during the migration. Update `vite.config.js` `input` to `resources/js/app.tsx`.
- **`components.json`**: flip `"tsx": true` so `npx shadcn add` emits `.tsx`. The 28 existing
  `ui/` primitives are re-pulled as `.tsx` in the first phase (they're generated, low-risk, and a
  clean way to validate the toolchain).
- **`package.json`** scripts: add `"types": "tsc --noEmit"`; extend `lint` to cover `.ts,.tsx`.
- **`.eslintrc` / `eslint.config`**: add the `typescript-eslint` parser + recommended config for
  `.ts`/`.tsx`, leaving `.jsx` rules intact.

### What we type (the parts that earn their keep)

- **Inertia page props** — each page gets a `Props` type; `usePage<SharedProps>()` typed once.
- **Shared/global props** — `auth.user`, `flash`, `locale`, permissions — declared on Inertia's
  `PageProps` via module augmentation, so every page inherits them.
- **Ziggy `route()`** — pull in Ziggy's generated types so route names/params are checked.
- **Domain models** — a small `resources/js/types/` with the shapes we pass from controllers
  (Customer, Vehicle, Invoice, ServiceOrder, …). These mirror, but are not auto-generated from,
  the Eloquent models. (A generator like `spatie/laravel-typescript-transformer` is a *possible
  later ADR* — explicitly **not** proposed now; another dep, another decision.)

## Migration phases (each = one or more PRs, each green)

Ordered so types flow upward and the highest-reuse code is typed first — this mirrors the
priority order already established in `docs/shadcn-migration-audit.md`.

1. **Toolchain bootstrap** — add deps, `tsconfig.json`, `app.tsx`, glob widen, ESLint, `types`
   script. Convert `lib/utils.js` + one trivial component to prove the pipeline. CI/`tsc` green.
2. **shadcn primitives** — flip `components.json` `tsx:true`, re-pull the 28 `ui/` primitives as
   `.tsx`. Generated code, low-risk, exercises the type setup broadly.
3. **Shared components** — `CustomerForm`, `VehicleForm`, layouts, `BottomNav`, `AppSidebar`,
   comboboxes. Highest reuse; typing these makes every consuming page partially inferred.
4. **Core types** — add `types/` domain models + Inertia `PageProps` augmentation + Ziggy types.
5. **Pages, by traffic/complexity** — Invoices and Workshop first (most logic, most to gain),
   then Appointments, Customers, Products, Service Records, then admin/auth/profile last.
6. **Cleanup** — once `.jsx` count hits zero: narrow the page glob back to `.tsx`, flip
   `checkJs`/remove `allowJs`, delete `jsconfig.json`, make `tsc --noEmit` a blocking lefthook +
   CI gate.

We can **pause permanently after any phase** and still ship — that's the point of `allowJs`.

## Consequences

**Positive**

- Compile-time safety on the fiscally sensitive surface (invoice totals, TVA, stock quantities,
  service-order state transitions) — exactly where a silent `undefined` is most expensive.
- Editor autocomplete on page props and domain models; safer refactors.
- Aligns the frontend with the typed backend (PHP 8.4 strict types) and with shadcn's own
  TS-first ecosystem, reducing friction in the IST-118 migration.

**Negative / costs**

- ~12k LOC over many PRs — real calendar time, competing with the active sprint.
- A new gate (`tsc --noEmit`) devs and CI must keep green.
- 4 new dev dependencies (supply-chain surface; offset by `ignore-scripts=true` already in
  `.npmrc` and our existing IOC discipline).
- Two-language frontend during the transition — manageable, but a state to communicate to the team.

**Neutral**

- No runtime/bundle change — TS is erased at build; users see nothing.

## Alternatives considered

1. **Status quo (plain JSX).** Zero cost, zero safety. Rejected given the fiscal-compliance
   surface, but it is the fallback if Ahmed declines.
2. **Big-bang conversion in one PR.** Rejected: unreviewable, unmergeable against an active
   sprint, collides with IST-118.
3. **JSDoc-typed `.jsx` (types via comments, `checkJs: true`, no `.tsx`).** Gets ~70% of the
   safety with no file renames and no build change. Tempting as a *zero-rename* middle ground,
   but verbose, weaker generics, and diverges from shadcn's TS-first templates (we'd hand-convert
   every block). Rejected in favour of real TSX, but it's the cheapest partial step if the full
   migration isn't approved.
4. **Auto-generate FE types from Eloquent** (`laravel-typescript-transformer`). Attractive but a
   separate dependency and decision; deferred to a future ADR rather than bundled in.

## Effort estimate

Rough order-of-magnitude, not a commitment:

| Phase | Scope | Est. |
|---|---|---|
| 1 — Bootstrap | config + 2 files | 0.5 day |
| 2 — Primitives | 28 `ui/` files (re-pulled) | 0.5 day |
| 3 — Shared components | ~21 files | 1–2 days |
| 4 — Core types | `types/` + augmentation | 0.5–1 day |
| 5 — Pages | 46 pages | 3–5 days |
| 6 — Cleanup | gates + glob narrow | 0.5 day |
| **Total** | **98 files** | **~6–9 days**, spread across the sprint |

## Open questions for sign-off

1. **Approve the deviation + the 4 dev deps?** (Both need your yes per `CLAUDE.md`.)
2. **`strict` from day one** (recommended) vs. loosen-then-tighten?
3. **Sequencing vs. IST-118** — interleave TSX with the shadcn migration (type each component as
   it's migrated to shadcn — one touch per file), or run TSX as a separate pass afterward?
4. If a full migration is a "not now": do we want **alternative 3 (JSDoc)** as a low-cost interim,
   or stay plain JSX?
