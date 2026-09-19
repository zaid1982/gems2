# Energy & Utility Monitoring V1

Daily incoming electricity meter readings, the monthly summary derived from them, and the monthly
Building Energy Index (BEI).

This module is separate from the older `utl_utility` / `p_utility` screens, which track the monthly
TNB **bill** (tariff, maximum demand charge, KWTBB). This one tracks **consumption** from cumulative
incoming meter readings, in the shape of the Daily Consumption Electricity workbook.

## SQL

1. `sql/2026-09-18_create_enr_module.sql` — tables, audit module 20, two seeded meters per active site
2. `sql/2026-09-18_nav_waste_kpa_enr.sql` — menu and grants

## Roles

| ID | Role | Capability |
|---|---|---|
| 1, 10 | Administrator / GFM Management | Everything |
| 18 | Utility Reader | Enter readings, daily notes and monthly BEI |
| 19 | Site Admin | Meter setup and site BEI configuration |
| 30 | KPI Admin | Everything, across sites |
| 31, 32 | PI Entry / KPI Viewer | Read only |

## Pages

| URL | Page |
|---|---|
| `p_energy_daily` | Workbook-style daily grid, with meter setup in a modal |
| `p_energy_monthly` | Twelve months of totals per meter, plus a stacked chart |
| `p_energy_bei` | Site configuration and the monthly BEI results |

## Tables

| Table | Purpose |
|---|---|
| `enr_meter` | Incoming meters per site, configurable. Seeded with `Incoming No.1` and `Incoming No.2` |
| `enr_reading` | Cumulative kWh and maximum demand per meter per date. Unique on (meter, date) |
| `enr_daily_note` | Chiller running hours and a remark per site per date |
| `enr_site_config` | Gross floor area, target BEI and the annualisation factor |
| `enr_monthly_bei` | Monthly BEI result with a DRAFT / FINAL status |

Column names deliberately avoid digits (`floor_area_sqm`, not `floor_area_m2`), because
`DbMysql::convertToDbString()` rewrites `m2` as `m_2` when it converts key names.

## Gap distribution

Readings are not taken every day. When a day is missed, the next reading covers the whole gap, so
the difference is spread evenly across the days it covers:

```
last reading day 16 = 1,100 kWh
next reading day 18 = 1,400 kWh

gapDays = 2
perDay  = (1400 - 1100) / 2 = 150

day 17 -> 150 kWh
day 18 -> 150 kWh
```

- A day is blank only when **no interval covers it**: before the meter's first reading, or after its
  last. Blank is deliberately not zero, so an incomplete month is visibly incomplete.
- The readings immediately **before and after** the requested range are loaded too, because a
  reading outside the range still describes days inside it. The leading one lets day 1 be derived;
  the trailing one covers a gap that swallows the range. Without the trailing reading, a month that
  sits entirely inside one gap (say readings on 31 Jan and 28 Apr, viewing March) would look empty
  on the daily grid while the yearly summary reported consumption for it.
- A reading lower than the one before it (meter replacement or a typo) cannot be spread. Those days
  stay blank and the grid shows a warning naming the meter and date.
- `averageDaily = totalKwh / days that have data`, so a part-captured month is not understated.
- `perDay` is held at full precision internally and rounded only where a value is emitted, so the
  monthly and yearly totals add back up to the meter differences exactly. Rounding each day first
  would drift by a few kWh over a year.

Implemented in `EnergyCalculator::distribute()`, `prepare()` and `buildMonth()`.

### Query cost

`prepare()` distributes every meter once, so a caller needing several months loads the whole year in
one query and assembles each month in memory. Measured with 6 meters and 365 readings each:

| Endpoint | Queries |
|---|---|
| `energy/daily` (one month) | 7 |
| `energy/monthly` (twelve months) | 6 |
| `energy/bei` (twelve months) | 12 |

`energy/monthly` costs fewer queries than `energy/daily` because the role lookups are cached after
the first call. Before this was reworked, `monthly` and `bei` each ran roughly
`12 x (1 per meter + 1)` queries — about 84 apiece at six meters.

## BEI

```
totalKwh  = electricity + chilled water
actualBei = (totalKwh / floorAreaSqm) x annualiseFactor
pass      = actualBei <= targetBei        (lower is better)
resultPct = pass ? 100 : 0
```

BEI is conventionally kWh/m²/**year**. Set `annualise_factor` to **12** when the consumption
figures are monthly, or **1** to keep the raw ratio. With the May workbook figures
(627,727.60 + 587,648.40 kWh over 90,082 m²) the raw ratio is 13.49 and the annualised value is
161.90, against the workbook's 158.98 — the residual difference comes from how the workbook handles
month length, which is why the factor is configurable rather than hard-coded.

Electricity is prefilled from the monthly total derived from the daily readings. Typing over it
marks the month as an override (`electricity_is_override`), which is needed while the daily meters
and the figure used for PI 3B do not cover the same scope (gap G08). Chilled water is entered
manually. Finalising a month locks it; Site Admin or KPI Admin can reopen it.

## API (`energy` → `api/energy.php`)

Envelope `{ success, result, error, errmsg }`. JWT required.

- `GET energy/me` — `{isAdmin, canRecord, canSetup, canView}`
- `GET energy/site`
- `GET energy/meter?siteId&activeOnly`, `POST energy/meter`, `PUT energy/meter/{id}`, `DELETE energy/meter/{id}`
- `GET energy/daily?siteId&year&month` — the grid, meter totals, total kWh, average daily and any issues
- `POST energy/reading` — `{meterId, readingDate, cumulativeKwh, maxDemandKw, remark}` (upsert)
- `DELETE energy/reading/{id}`
- `POST energy/daily_note` — `{siteId, date, chillerRunningHours, remark}` (upsert)
- `GET energy/monthly?siteId&year`
- `GET|POST|PUT energy/config`
- `GET energy/bei?siteId&year`, `POST|PUT energy/bei`
- `POST energy/bei/{id}/finalise`, `POST energy/bei/{id}/reopen`

Every write that changes the grid returns the refreshed grid, so the screen updates from one call.

## Notes

- The daily grid writes on blur: changing a cumulative value saves the reading, clearing it deletes
  the reading, and the chiller hours and remark save as a daily note.
- `EnergyReading::loadReadings()` gives each UNION branch its own placeholder names. PDO runs with
  `ATTR_EMULATE_PREPARES = false`, which rejects a named placeholder used more than once.
- Phase 1 is web only; the Flutter app still uses the older `utility_meter` endpoints.
