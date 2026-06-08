# Contributing / conventions

The codebase is being modernized incrementally. Follow these conventions for any
new or migrated code; leave untouched legacy code alone unless your change
requires it.

## Golden rules

- Keep the app running. Changes must preserve the public API contract
  (`{success, result, error, errmsg}` envelope, existing endpoint URLs/params)
  so the Flutter app and web UI keep working.
- Prefer small, behavior-preserving steps over big rewrites.
- No secrets in source. Use `.env` / `Gfm\Support\Config`.

## New code

- Namespace `Gfm\` under `api/src/Gfm/` (PSR-4), `declare(strict_types=1)`.
- Database access only through `Gfm\Database\SafeQuery` (parameterized) and
  `Gfm\Database\Connection`. Never concatenate user input into SQL; validate
  identifiers with `Gfm\Database\Identifier`.
- Replace magic numbers/strings with typed constants/enums under `Gfm\Domain`
  (see [docs/REFERENCE_IDS.md](docs/REFERENCE_IDS.md)).
- Use `Gfm\Http\JsonResponse` for the envelope and `Gfm\Http\ApiException` for
  user-facing vs internal errors. Enforce permissions with `Gfm\Http\RoleGuard`.
- Add PHPUnit tests under `tests/Unit` (and `tests/Smoke` for endpoint contracts).

## Migrating legacy code

Follow the per-slice loop in
[docs/REFACTOR_GODFILES.md](docs/REFACTOR_GODFILES.md): create a tested
repository/service, delegate the legacy method to it (keep the old signature),
verify with tests + smoke, then remove the legacy method once all callers move.

## Quality gates (must pass before merge)

```bash
composer cs        # code style (php-cs-fixer)
composer stan      # static analysis (PHPStan, level 6 on api/src + tests)
composer test      # PHPUnit
```

CI runs the same on PHP 8.3 and 8.4. PHPStan/cs-fixer are scoped to `api/src`
and `tests` today; as legacy folders are migrated, add them to the scope in
`phpstan.neon` / `.php-cs-fixer.dist.php` and capture any pre-existing issues in
`phpstan-baseline.neon` (`vendor/bin/phpstan analyse --generate-baseline`).

## Commits

Conventional, imperative summaries (e.g. `fix: ...`, `feat: ...`, `refactor: ...`).
Do not commit `.env`, `api/library/config.ini`, or anything under `developer/`.
