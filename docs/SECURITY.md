# Security hardening & credential rotation

This document tracks the security work from the incremental modernization and,
most importantly, the **operational actions a human must perform** that code
changes alone cannot cover.

## 1. Rotate leaked credentials (REQUIRED, do this now)

The following secrets were committed to source control (in `api/library/config.ini`
history and in `api/class/Constant.php` comments) and must be considered
compromised. Rotating them is an operational task that cannot be done from code:

| Secret | Where it was exposed | Action |
| --- | --- | --- |
| MySQL password | `config.ini` history | Change the DB user's password, update `DB_PASS` |
| SMTP password (`GFMphase@3`) | `config.ini` history / `[smtp] m_password` | Reset the mailbox/app password, update `SMTP_PASS` |
| JWT signing key (`gems2`) | hardcoded in `f_login.php` | Generate a strong `JWT_SECRET` (see below) |

Generate a strong JWT secret:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

After rotating, set the new values in `.env` (preferred) or `api/library/config.ini`.
Both are git-ignored.

### Optional: scrub git history

Rotation is the mandatory mitigation. If you also want to remove the secrets from
history (invasive; rewrites commits and requires a coordinated force-push), use
[`git filter-repo`](https://github.com/newren/git-filter-repo) or the BFG. Only do
this with the whole team aware, since everyone must re-clone.

## 2. Configuration is now environment-driven

`Gfm\Support\Config` resolves every setting in this order: environment variable
(`.env`) -> `api/library/config.ini` -> safe default. Existing deployments keep
working unchanged; new deployments should use `.env` (copy from `.env.example`).

Connection code routed through `Config`:

- `Class_db::db_connect()` and `db_connect_constant()` (`api/function/db.php`)
- `DbMysql::connect()` (`api/class/DbMysql.php`)
- SMTP send path (`Class_email::send_email_365()` in `api/function/f_email.php`)
- `Constant::boot()` syncs DB host/name/credentials, log dirs and base URL.

## 3. Maintenance tools (`maintenance/`)

Operator tools under `maintenance/` (database query editor, schema compare, log
explorer, git tool, etc.) are gated by a shared **X-Api-Key** secret.

1. Set the key in `.env` (preferred) or `api/library/config.ini`:

   ```ini
   [maintenance]
   api_key = <generate with: php -r "echo bin2hex(random_bytes(32)), PHP_EOL;">
   ```

   Or: `MAINTENANCE_API_KEY=...` in `.env`.

2. Open `maintenance/dashboard.html` and enter the key when prompted. It is kept
   in `sessionStorage` for the browser tab and attached to all `fetch()` calls.

3. All `maintenance/*.php` backends enforce the key server-side via
   `Gfm\Security\MaintenanceGuard`. CLI usage of those scripts is exempt.

Direct links to `.php` endpoints append `?api_key=` automatically once unlocked.
Sensitive artifacts (`.sql`, `.log`, `logs/`, etc.) are blocked by
`maintenance/.htaccess`.

## 4. JWT hardening rollout

`Class_login` now reads the signing secret, token TTL and validation leeway from
`Config` (`JWT_SECRET`, `JWT_TTL`, `JWT_LEEWAY`).

Defaults are deliberately backward compatible so the live Flutter app and web UI
are not logged out on deploy:

1. Deploy with a real `JWT_SECRET` set. Keep `JWT_LEEWAY=86400` initially so tokens
   signed with the previous behavior keep validating during the transition.

   > Note: changing `JWT_SECRET` invalidates all existing tokens immediately, so
   > coordinate this with a mobile app release or expect users to re-login once.

2. After the client fleet has re-authenticated (>= max old token age), tighten:
   `JWT_TTL=28800` (8h) and `JWT_LEEWAY=60`.

3. Coordinate any TTL change with the mobile team (`metadata-gfm`) so the app's
   refresh/expiry handling matches.

## 5. Known follow-ups (tracked, not yet changed)

- **Password hashing uses `md5()`** (`Class_login::check_login`, `check_login_web`,
  `reset_password`, and `f_user.php`). MD5 is unsuitable for passwords. Migrate to
  `password_hash()`/`password_verify()` with transparent upgrade-on-login. This
  touches every comparison site plus a backfill, so it is staged separately to
  avoid locking users out.
- **Dependency advisories**: run `composer audit` regularly (CI does). Address the
  reported advisories by upgrading the affected package(s).

## 6. SQL injection hardening

Done:

- The `Class_db` clause builders (`get_whereAnd_str`, `get_set_str`,
  `get_commaVal_str` in `api/function/db.php`) now quote string literals with the
  charset-aware `PDO::quote()` instead of `addslashes()`, and the previously
  **unescaped** comparison operators (`'$r1'` / `'$r2'`) are quoted too.
- The user-controllable, quoted placeholders in `get_sql()` substitution
  (`search_text`, `wo_type`, date ranges) are now escaped to prevent string
  literal breakout. This closes the datatable search-box injection.
- New/migrated code should use `Gfm\Database\SafeQuery` (always parameterized)
  and `Gfm\Database\Identifier` (validated identifiers).

Remaining (tracked):

- Numeric/structural `get_sql()` placeholders (`[user_id]`, `[site_id]`,
  `[roles]`, `[where_str]`, ...) are populated by server code and interpolated
  unquoted. Fully parameterizing them is part of decomposing `sql.php` into
  repositories (Phase 3); until then, ensure callers pass only server-derived
  numeric IDs/lists into these.

## 7. Authorization

- `Gfm\Http\RoleGuard` provides server-side role enforcement
  (`RoleGuard::requireAny($roles, [$allowedRoleId, ...])`).
- Audit item: many `api/*.php` endpoints authenticate the JWT but do not enforce
  role-based authorization server-side (the UI hides actions instead). Work
  through the endpoints and gate privileged actions with `RoleGuard`.
