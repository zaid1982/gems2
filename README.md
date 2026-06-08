# GFM GEMS

PHP backend and web UI for the GlobalFM facility-management system (work orders,
PPM, PTW, attendance, inventory, gamification, ...). It also serves the
`metadata-gfm` Flutter mobile app. Runs on PHP 8.1+ with MySQL (XAMPP locally).

> Modernization in progress. New code lives under the `Gfm\` namespace
> (`api/src/Gfm`, PSR-4 autoloaded). Legacy code (`api/*.php`, `api/function/f_*.php`,
> `api/class/*.php`) still runs unchanged and is migrated incrementally. See the
> docs linked below.

## Requirements

- PHP >= 8.1 (developed/tested on 8.3-8.5)
- MySQL / MariaDB
- Composer 2

## Setup

```bash
composer install                 # installs dependencies + dev tools
cp .env.example .env             # then edit .env with your secrets
```

Configuration is resolved by `Gfm\Support\Config` in this order:
**environment variable / `.env` -> `api/library/config.ini` -> default**, so you
can use either `.env` (preferred) or the legacy `config.ini`
(see `api/library/config.ini.example`). Never commit real secrets.

Point a vhost / Apache docroot at the project root (the `.htaccess` maps
pretty URLs to the page HTML files).

## Day-to-day commands

```bash
composer test          # PHPUnit unit tests
composer stan          # PHPStan static analysis
composer cs            # php-cs-fixer (dry-run); composer cs-fix to apply
composer audit         # dependency vulnerability check
```

CI (`.github/workflows/ci.yml`) runs cs-fixer, PHPStan and PHPUnit on PHP 8.3 & 8.4.

### Smoke tests against a running server

```bash
GFM_SMOKE_BASE_URL=https://staging.example/api/ \
GFM_SMOKE_USER=alice GFM_SMOKE_PASS=secret \
vendor/bin/phpunit --testsuite smoke
```

(They are skipped when `GFM_SMOKE_BASE_URL` is unset.)

## Architecture

- **Legacy procedural stack** - `api/*.php` endpoints -> `api/function/f_*.php`
  (`Class_*`) -> `Class_db` (`api/function/db.php`).
- **Legacy OOP stack** - `api/*_v2.php` / `*_v3.php` + `api/class/*.php` -> `DbMysql`.
- **New routed stack** (additive, opt-in) - `api/index.php` front controller +
  `Gfm\Http\Router` -> handlers -> `Gfm\Database\SafeQuery` (parameterized).

All responses use the `{success, result, error, errmsg}` envelope
(`Gfm\Http\JsonResponse`).

## Documentation

- [docs/SECURITY.md](docs/SECURITY.md) - credential rotation, JWT rollout, SQLi status.
- [docs/DATABASE.md](docs/DATABASE.md) - DB-layer consolidation plan.
- [docs/REFACTOR_GODFILES.md](docs/REFACTOR_GODFILES.md) - splitting `f_wo.php` / `f_ppm.php`.
- [docs/REFERENCE_IDS.md](docs/REFERENCE_IDS.md) - magic-ID -> typed-constant map.
- [docs/PERFORMANCE.md](docs/PERFORMANCE.md) - measurement-driven optimization.
- [docs/REPO_HYGIENE.md](docs/REPO_HYGIENE.md) - untracking vendored/scratch files.
- [CONTRIBUTING.md](CONTRIBUTING.md) - conventions for new/migrated code.
