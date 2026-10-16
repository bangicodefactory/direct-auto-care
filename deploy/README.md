# Deploy — white-label, one instance per client

The same codebase serves every client. A client gets its **own** cPanel domain,
MySQL database, and server `.env` (company identity + theme colours). The compiled
front-end bundle is client-agnostic — the theme and logo are injected at runtime
via `GET /branding.css` and Inertia shared props from `config('company')` — so no
per-client rebuild is needed.

CI builds once per target and pushes over SSH. Each client is a **GitHub
Environment** holding that client's secrets. See `.github/workflows/deploy.yml`.

## Add a new client

1. **cPanel**
   - Point the client's domain nameservers at Namecheap (see the DNS notes) and
     add the domain in cPanel so the DNS zone is created and it resolves.
   - Set the domain's **document root to `.../<app>/public`** (Laravel serves from
     `public/`), matching the existing garage setup.
   - Create a dedicated **MySQL database + user**; grant all privileges.
   - Enable **AutoSSL** for the domain.
   - Authorise the deploy **SSH key** on the account.

2. **Server `.env`** — start from `.env.example`. Set at minimum:
   - `APP_NAME`, `APP_URL` (the client's https domain), `APP_KEY` (leave blank —
     `post-deploy.sh` generates one on first deploy), `APP_ENV=production`,
     `APP_DEBUG=false`.
   - `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` for the client's DB.
   - `COMPANY_*` — the client's legal identity (name, address, ICE, IF, RC) shown
     in the app header and on invoices.
   - `COMPANY_LOGO` — absolute URL or a public-relative path (e.g.
     `branding/acme-logo.svg`) shipped with the deploy. Empty = themed default mark.
   - `COMPANY_SHOW_PRODUCT_UNITS` — `true` (default) shows stock units next to
     product quantities in the app, on invoices/PDF and in the workshop; `false`
     hides them everywhere and makes the product unit field optional (category
     default). A blank or unrecognised value keeps the default. An Environment
     **variable** of the same name (step 3) overrides this line, so the toggle
     can be flipped without rewriting the secret:
     `gh variable set COMPANY_SHOW_PRODUCT_UNITS --env <client> --body false`,
     then redeploy.
   - `THEME_*` — hex brand colours (leave unset for the Direct Auto Care palette).
   - `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` for the seeded admin.

3. **GitHub Environment** (Settings → Environments → **New**, name = the client
   slug, e.g. `directautocare`)
   - Secrets: `CPANEL_SSH_HOST`, `CPANEL_SSH_PORT`, `CPANEL_SSH_USER`,
     `CPANEL_SSH_KEY`, `CPANEL_DEPLOY_PATH` (the app root), `CPANEL_ENV` (the full
     server `.env` from step 2, pasted).
   - Vars (optional): `CPANEL_PHP_BIN` (PHP 8.4 CLI path),
     `SENTRY_ORG` / `SENTRY_PROJECT` (to use a per-client Sentry project),
     `COMPANY_SHOW_PRODUCT_UNITS` (`true` / `false`, or `1`/`0`, `yes`/`no`,
     `on`/`off` in any case; overrides the line in `CPANEL_ENV`). Anything else
     fails the deploy before it touches the server. Set it on the Environment
     only: a repo-level variable of that name fails every deploy.

4. **Register the client** in the repo variable `DEPLOY_CLIENTS` (a JSON array of
   environment names), e.g. `["garage-demo","directautocare"]`. Auto-deploys on
   `main` fan out to every entry.

5. **First deploy** — run the **Deploy (per-client)** workflow manually with
   `client = <slug>` and the `seed` input:
   - **`seed = prod`** for a real client's first deploy → migrate + roles +
     service catalog + admin (from `ADMIN_*`), **no demo data**.
   - **`seed = demo`** only to (re)load the garage showcase's fake data
     (destructive: `migrate:fresh --seed` DROPS all tables).
   - **`seed = none`** (default) on later deploys → additive `migrate`.
   It builds, rsyncs, writes the `.env`, generates `APP_KEY`, then seeds per above.

   Sentry: for a client's front/back events to group, set `SENTRY_ENVIRONMENT`
   in its `CPANEL_ENV` to the client slug (same as the GitHub Environment name).

## Master switch

Nothing deploys until the repo variable `DEPLOY_ENABLED` is `"true"`.
