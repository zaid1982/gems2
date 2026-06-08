# Database access: consolidation plan

## Where we started

Two parallel data-access layers, each opening their own PDO connection with
different attributes and building SQL by string concatenation:

- `Class_db` (`api/function/db.php`) - legacy procedural stack (most `api/*.php`).
- `DbMysql` (`api/class/DbMysql.php`) - OOP stack (`*_v2.php`, `*_v3.php`,
  `api/class/*.php`).

## Target (single layer)

- `Gfm\Database\Connection` - one factory that builds a correctly-configured
  PDO from `Gfm\Support\Config` (exceptions on, real prepared statements).
- `Gfm\Database\SafeQuery` - the only query API for new/migrated code; always
  parameterized.
- `Gfm\Database\Identifier` - validates identifiers that cannot be bound.

```php
use Gfm\Database\Connection;

$rows = Connection::query()->select(
    'SELECT * FROM wo_task WHERE site_id = :site AND wo_task_status = :status',
    [':site' => $siteId, ':status' => $status]
);
```

## What has been consolidated so far

- Both legacy layers now resolve connection credentials from the same place
  (`Config`) instead of separate hardcoded values.
- `Class_db::db_connect()` publishes its live PDO via
  `Connection::setShared($pdo)`, so `SafeQuery` used inside a legacy request runs
  on the same connection and transaction.
- The string-building builders are annotated `@deprecated`.

## Remaining migration (incremental)

1. Route new endpoints through the front controller and use
   `Connection::query()` / `SafeQuery`.
2. When touching a legacy endpoint, replace its `db_select`/`db_update`/
   `db_insert` calls with `SafeQuery`, verifying with the smoke tests.
3. Once `DbMysql` call sites are migrated, fold it into `Connection` and remove
   the second singleton. Do the same for `Class_db` last, since it is the most
   widely used.
