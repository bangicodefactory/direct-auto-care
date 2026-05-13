# Security hardening — Direct Auto Care

Implemented as part of IST-88 (Sprint 1). Last updated: 2026-05-13.

---

## CI / GitHub Actions hardening

### Actions pinned to commit SHAs

All `uses:` references in `.github/workflows/ci.yml` are pinned to full commit
SHAs instead of mutable tags. Tags can be moved after the fact; SHAs cannot.

| Action | Tag | Pinned SHA |
|---|---|---|
| `actions/checkout` | v4 | `34e114876b0b11c390a56381ad16ebd13914f8d5` |
| `actions/setup-node` | v4 | `49933ea5288caeca8642d1e84afbd3f7d6820020` |
| `actions/cache` | v4 | `0057852bfaa89a56745cba8c7296529d2fc39830` |
| `shivammathur/setup-php` | v2 | `accd6127cb78bee3e8082180cb391013d204ef9f` |

When a new upstream release is available, update the SHA and the comment
together. Dependabot will open PRs for this automatically.

### Minimal workflow permissions

`permissions: { contents: read }` is set at the workflow root. No job
escalates further. This limits the blast radius if a compromised action
attempts to write to the repo or call the GitHub API with elevated scope.

### No `pull_request_target`

Only `pull_request` is used, never `pull_request_target`. The latter executes
untrusted PR code with write-scope secrets in context, which is the entry point
for Pwn Request attacks. This is documented in the workflow file.

---

## npm / supply-chain hardening

### postinstall scripts disabled

`.npmrc` sets `ignore-scripts=true`. This blocks the primary execution vector
used by Mini Shai-Hulud and similar worms. If a package legitimately requires
postinstall scripts (e.g. native bindings), install it with
`--ignore-scripts=false` and document the exception here.

**Current exceptions:** none.

### Lockfile discipline

`package-lock.json` is always committed. CI uses `npm ci` (never
`npm install`) to ensure reproducible installs from the lockfile.

### Dependabot

`.github/dependabot.yml` enables weekly automated dependency PRs for both
npm and Composer. Auto-merge is disabled — every PR must be reviewed by Ahmed.

---

## IOC package scan (2026-05-13)

Mini Shai-Hulud compromised packages in the `@tanstack`, `@mistralai`, and
`@squawk` namespaces. Scan run against current dependency tree:

```
npm ls @tanstack   → (empty — not present)
npm ls @mistralai  → (empty — not present)
npm ls @squawk     → (empty — not present)
```

Recharts and react-big-calendar (future installs per ADR-003 / ADR-002) are
not on the IOC list as of this writing. Recheck weekly until the incident is
declared closed.

Live IOC tracker: https://socket.dev/supply-chain-attacks/mini-shai-hulud

---

## Token rotation procedure

When any of the following events occur (compromised runner, leaked secret,
token exposure), rotate in this order:

1. **GitHub PATs** — Settings → Developer settings → Personal access tokens → revoke and regenerate.
2. **npm tokens** — npmjs.com → Access Tokens → delete and regenerate.
3. **Cloud provider credentials** — rotate in the relevant provider console, then update any GitHub Actions secrets.
4. Verify no tokens are committed: run `gitleaks detect` (preferred) or `trufflehog filesystem .`. Avoid raw `grep -E 'eyJ'` — it matches any base64-encoded JSON and generates excessive false positives.

Rotation evidence for the 2026-05-11 incident window should be linked as a
comment on PR #19.

---

## Claude Code hygiene

- Do **not** run Claude Code inside CI runners. Dev machines only.
- MCP server allowlist: first-party (Anthropic) + verified maintainers only (Linear, GitHub). Reject any unknown MCP server.
- Periodically audit `~/.claude/` (Linux/macOS) and `%APPDATA%\claude-code\` (Windows) for unexpected files or hooks. Flag anything unfamiliar to Ahmed before proceeding.

---

## Sources

- [Mini Shai-Hulud Worm Compromises TanStack, Mistral AI — The Hacker News](https://thehackernews.com/2026/05/mini-shai-hulud-worm-compromises.html)
- [Mini Shai-Hulud is Back — StepSecurity](https://www.stepsecurity.io/blog/mini-shai-hulud-is-back-a-self-spreading-supply-chain-attack-hits-the-npm-ecosystem)
- [TanStack npm Packages Hit — Snyk](https://snyk.io/blog/tanstack-npm-packages-compromised/)
- [NHS Cyber Alert CC-4781](https://digital.nhs.uk/cyber-alerts/2026/cc-4781)
- [Socket.dev IOC tracker](https://socket.dev/supply-chain-attacks/mini-shai-hulud)
