# KPI & APD V1

Configurable KPI/APD engine: define the KPI structure once, then create one **monthly evaluation**
per site that snapshots the structure and captures the parameter values. The engine calculates each
indicator's achievement, whether it met its target, the demerit points imposed and the APD deducted.

There is **no approval step**. PI Entry captures and submits; submitting locks the indicator and
only a KPI Admin can reopen it.

## SQL

1. `sql/2026-09-18_create_kpa_module.sql` — tables, roles 30/31/32, audit module 19, and the
   global template (4 groups, 21 PI, 47 parameters)
2. `sql/2026-09-18_nav_waste_kpa_enr.sql` — menu and grants

## Roles

| ID | Role | Capability |
|---|---|---|
| 1, 10 | Administrator / GFM Management | Everything |
| 30 | KPI Admin | KPI structure, PI definition, parameters, formulas, PI assignment, create months, edit MPV, reopen a submitted PI |
| 31 | PI Entry | Capture and submit the parameters of the PI assigned to them |
| 32 | KPI Viewer | Read the summary and history |

PI Entry is scoped by `kpa_pi_assignment` (site + PI + user). An unassigned indicator is read-only
for a PI Entry user. Only KPI Admin may edit formulas and parameter definitions.

## Pages

| URL | Page | Roles |
|---|---|---|
| `p_kpi_in` | KPI / APD Summary | 1, 30, 31, 32 |
| `p_kpa_evaluation` | Monthly evaluation list and indicator grid | 1, 30, 31 |
| `p_kpa_pi_entry` | PI parameter entry (`?id=` eval_pi_id) | 1, 30, 31 |
| `p_kpa_history` | Multi-month trends | 1, 30, 31, 32 |
| `p_kpa_structure` | Config, KPI groups, PI definition, parameters | 1, 30 |
| `p_kpa_assignment` | PI to PI-Entry-user assignment | 1, 30 |

## Tables

| Table | Purpose |
|---|---|
| `kpa_config` | Maximum APD percentage per site (default 5.00) |
| `kpa_group` | KPI groups. `site_id = 0` is the global template |
| `kpa_pi` | Performance Indicators: target, unit, demerit, weightage, pass rule, calc type, formula, source |
| `kpa_pi_param` | Parameter definitions (`p1`, `p2`, …) with data type, source and an optional `gems_hook` |
| `kpa_evaluation` | One month per site: MPV, max APD %, APD maximum, status, totals |
| `kpa_evaluation_pi` | Snapshot of each PI in that month plus its results |
| `kpa_evaluation_param` | Captured parameter values for that month |
| `kpa_pi_assignment` | Which PI Entry users may work on which PI |
| `kpa_history` | Audit trail |

A site uses its own `kpa_group` rows when it has at least one active group; otherwise it falls
back to the shared template (`site_id = 0`). See `KpaBase::resolveTemplateSite()`.

## Snapshotting

Creating a month copies the template into `kpa_evaluation_pi` and `kpa_evaluation_param`. Later
changes to a target, weightage or formula therefore never rewrite a month that has already been
captured. The MPV defaults to the most recent earlier month for the site.

## Calculation

`KpaCalculator` dispatches on `calc_type`:

| calc_type | Behaviour |
|---|---|
| `EXPRESSION` | Evaluates `formula_expr` with a recursive-descent parser. Supports numbers, `pN`, `+ - * / ( )`, `min()`, `max()`. `eval()` is never used and anything else is rejected. |
| `BACKLOG_AVG` | PI 1E. Parameter pairs `(p1,p2) (p3,p4) (p5,p6)` are (total, completed) per ageing bucket. A bucket with no backlog counts as 100%. The achievement is the mean of the buckets. |
| `BEI` | PI 3B. `(p1 electricity + p2 chilled water) / p3 floor area`, multiplied by the optional `p4` annualisation factor. |
| `AVG_PARAMS` | PI 4C. Mean of every supplied percentage. |
| `DIRECT` | The first supplied parameter is the achievement. |

Pass rules: `GTE_TARGET` (default), `LTE_TARGET` (used by BEI, where lower is better), `EQ_TARGET`.

A missing required parameter or a division by zero produces a null achievement plus a message
shown on the PI entry screen; the PI cannot be submitted in that state.

### APD

```
apdMax   = MPV x maxApdPct / 100          (per month)
apdValue = apdMax x PI weightagePct / 100 (per indicator, its exposure)

PI meets target  -> demeritImposed = 0,             apdDeducted = 0
PI fails target  -> demeritImposed = demeritPoint,  apdDeducted = apdValue
```

Verified against the workbook: MPV 4,147,120.556 at 5% gives an APD maximum of RM 207,356.03, and
PI 1B at 5% weightage gives an exposure of RM 10,367.80, deducted in full when the target is missed.

The month totals `total_demerit` and `total_apd_deducted` are recalculated after every save, submit
or reopen, and the month flips to `COMPLETED` once every indicator is submitted.

## Seeded template

4 groups and 21 indicators with weightage totalling 100% (61 + 20 + 10 + 9). The KPI Structure
screen shows a warning while the active weightage does not total 100%, because the per-indicator
APD exposures would not add up to the APD maximum.

## API (`kpa` → `api/kpa.php`)

Envelope `{ success, result, error, errmsg }`. JWT required.

- `GET kpa/me` — `{isAdmin, canAdmin, canEntry, canView, assignedPiIds}`
- `GET kpa/site`
- `GET|POST|PUT kpa/config`
- `GET|POST kpa/group`, `PUT|DELETE kpa/group/{id}`
- `GET|POST kpa/pi`, `GET|PUT|DELETE kpa/pi/{id}`
- `GET|POST kpa/pi/{id}/param`, `PUT|DELETE kpa/pi/{id}/param/{paramId}`
- `POST kpa/pi/{id}/test` — dry-run a formula with sample values
- `GET kpa/structure/report`
- `GET kpa/assignment`, `GET kpa/assignment/users`, `POST kpa/assignment`, `DELETE kpa/assignment/{id}`
- `GET kpa/evaluation?siteId&year&status`, `GET kpa/evaluation/defaults?siteId&year&month`
- `GET kpa/evaluation/{id}`, `POST kpa/evaluation`, `PUT kpa/evaluation/{id}`
- `GET kpa/evaluation_pi/{id}`, `PUT kpa/evaluation_pi/{id}/params`
- `POST kpa/evaluation_pi/{id}/submit`, `POST kpa/evaluation_pi/{id}/reopen`
- `GET kpa/report/summary?evalId` or `?siteId&year&month`
- `GET kpa/report/history?siteId&fromYear&toYear`

`report/summary` for a month that has not been created returns the configured indicators with empty
results and `exists: false`, so the summary grid is never blank.

## Assumptions to confirm with the customer

- **MPV source (G01)** — entered manually each month, defaulting to the previous month. Not yet
  linked to the contract module.
- **PI 1G / 1H** — seeded as `(marks obtained / maximum marks) * 100`. The source note reads
  "audit marks / audit count", which only yields a percentage when each audit is scored out of 100.
- **PI 1E (G05)** — the mean of three bucket achievements. Bucket thresholds live in the parameter
  labels and can be re-worded without a code change.
- **PI 3C (G06)** — `100 - p1`, so each wastage finding costs one percentage point.
- **PI 4C (G07)** — the mean of the timeliness and content percentages.
- **PI 3B / BEI (G08)** — BEI is conventionally kWh/m²/**year**. One month of consumption divided
  by the floor area gives roughly 13.5 for the May figures, while the workbook shows 158.98, which
  implies annualisation. Parameter `p4` supplies the factor (12 for monthly data, blank for none)
  so this can be matched without a code change. The daily electricity totals are **not** wired into
  PI 3B, because the two do not currently cover the same meters.
- **GEMS+ automation (G09)** — every parameter carries a `source_type` and a `gems_hook`, but
  nothing is fetched automatically yet. All parameters are entered manually in phase 1.
