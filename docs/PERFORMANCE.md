# Performance: measure before optimizing

Indexes and query rewrites should be driven by evidence, not guesses.

## 1. Surface slow queries

- New code path (`SafeQuery`): set `APP_DEBUG=true` and queries slower than
  200ms are written to the error log as `[gfm] slow query ...`.
- Legacy stacks: enable the MySQL slow query log on the server, e.g.

  ```sql
  SET GLOBAL slow_query_log = 'ON';
  SET GLOBAL long_query_time = 0.2;          -- seconds
  SET GLOBAL slow_query_log_file = '/var/log/mysql/gems-slow.log';
  ```

  Then summarize with `mysqldumpslow` or `pt-query-digest`.

## 2. Diagnose

For each slow statement, run `EXPLAIN` (or `EXPLAIN ANALYZE`) and look for
`type=ALL` (full scans), large `rows`, `Using filesort`, `Using temporary`.

N+1 patterns: the dashboard/report methods in `f_wo.php` / `f_ppm.php` loop over
rows issuing per-row queries. Prefer a single joined/aggregated query (the
`WorkOrderRepository::countByStatusForSite` pattern) over per-row lookups.

## 3. Likely candidates (VERIFY with EXPLAIN first)

The recurring WHERE clauses in `api/library/sql.php` suggest these may benefit
from indexes, but confirm against real data and query plans before adding:

- `wo_task(site_id, wo_task_time_created)` - dashboards filter by site + created date range.
- `wo_task(wo_task_status)` - status aggregations.
- `ppm_task(ppm_task_start_date)`, `ppm_task(ppm_task_assigned_to)` - PPM reports.

## 4. Apply and re-measure

Add one index, re-run the representative query with `EXPLAIN`, and compare timing
from the slow log. Keep indexes minimal: every index slows writes and uses space.
Record what was changed and the before/after numbers in the PR.
