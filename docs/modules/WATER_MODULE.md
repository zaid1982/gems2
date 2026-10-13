# Water Monitoring

Daily cumulative water meter readings, the monthly summary derived from them, an optional IoT
comparison, and the monthly water bill. The sidebar parent is **Utilities - Water**. Electricity
is the separate parent **Utilities - Electrical**. Both use the same morning-reading rule.

This module is separate from the older `utl_utility` shift readings (morning / evening / night) and
from the Flutter `WaterBill` screen. Those stay as they are.

## SQL

1. `sql/2026-10-13_create_wtr_module.sql` — tables, meter seed, tariff seed, audit actions 262–266
2. `sql/2026-10-13_nav_water.sql` — water pages
3. `sql/2026-10-13_nav_utilities_split.sql` — **Utilities - Electrical** (bolt) and **Utilities - Water** (droplet)

Audit actions 256–261 are unchanged. Module 20 is renamed to Utility Monitoring.

## Roles

Same capabilities as electricity, via `EnergyBase`:

| ID | Role | Capability |
|---|---|---|
| 1, 10 | Administrator / GFM Management | Everything |
| 18 | Utility Reader | Enter readings, IoT, remarks and the optional invoice |
| 19 | Site Admin | Meter setup and tariff |
| 2 | Site Manager | Enter readings |
| 30 | KPI Admin | Everything, across sites |
| 31, 32 | PI Entry / KPI Viewer | Read only |

The shipped menu grants match electricity: Administrator (1), Utility Reader (18) and KPI Admin (30).
Site Admin can maintain meters and the tariff when that menu is granted.

## Pages

| URL | Page |
|---|---|
| `p_water_daily` | Workbook-style daily grid, with meter setup in a modal |
| `p_water_monthly` | Twelve months of consumption, the calculated bill and the optional invoice |
| `p_water_tariff` | Effective-dated tiers for the selected site |

## Tables

| Table | Purpose |
|---|---|
| `wtr_meter` | Water meters per site. Seeded with `Main Water Meter` |
| `wtr_reading` | Cumulative m³, optional IoT m³ and a remark. Unique on (meter, date) |
| `wtr_tariff` | Tiers for one site and effective date. The last tier has no upper bound |
| `wtr_bill_actual` | Optional utility invoice for one site and month. The calculated bill is not stored |

Column names avoid digits (`volume`, not `m3`) because `DbMysql::convertToDbString()` rewrites a
digit as `_` plus that digit.

`cumulative_volume` may be null when the row only holds an IoT figure or a remark. IoT is that
day's consumption from the IoT column, not a second cumulative register. There is no device feed.

## Gap distribution

Same rule as electricity. Readings are taken each morning, so today's figure closes yesterday.
When a morning is missed, the difference is spread evenly across the days the later reading closes,
stopping the day before that reading. Those days are marked estimated.

```
Friday    496,487
Saturday  no reading
Sunday    497,343

gap    = 2 days
per day = 856 / 2 = 428 m³

Friday    428 m³   estimated
Saturday  428 m³   estimated
Sunday    blank until Monday
```

A day is blank only when no interval covers it. Blank is not zero. A reading lower than the one
before it is not spread; those days stay blank and the grid names the meter and date.

`averageDaily = total / days that have data`. `perDay` is kept at full precision and rounded only
where a value is shown, so the month adds back to the meter difference. The bill uses the rounded
monthly total, so the RM figure matches the m³ on screen.

Implemented by `EnergyCalculator::distribute()`. Water reads the extra `estimated` map. Electricity
ignores it, and its kWh results are unchanged.

## IoT variance

Shown only when that day's consumption exists and IoT is present and not zero:

```
(manual consumption − IoT) / IoT × 100
```

Example: manual 675 m³, IoT 480.23 m³ → 40.56%.

IoT is not split across a gap. Each day has its own optional IoT figure, including an estimated day
when the user filled one in. No Normal / Warning / Investigation bands are applied. Those thresholds
are not in the source workbook.

## Tariff and bill

A month uses the latest tariff whose effective date is on or before the first day of that month.
A mid-month change applies from the next month. Tiers are a monthly block on the whole month's
consumption, not a daily allowance.

Seeded for every active site, effective 1 Jan 2026:

```
first 35 m³ × RM2.62
+ remaining × RM2.86
```

May at 14,693 m³:

```
35 × 2.62 + (14,693 − 35) × 2.86 = RM42,013.58
```

The first tier must start at 0. Tiers must meet without a gap or an overlap. The last tier is
open-ended. If no tariff covers the month, calculated RM stays blank.

An open month is billed from the consumption captured so far. Bill days are the calendar days in
the month. Days with data and estimated days are counted separately.

The optional invoice is stored in `wtr_bill_actual`. Difference is actual minus calculated.
Volume change and cost change are the percentage against the previous month. January uses December
of the previous year. A missing or zero previous value has no percentage.

## API (`water` → `api/water.php`)

Envelope `{ success, result, error, errmsg }`. JWT required.

- `GET water/me` — `{isAdmin, canRecord, canSetup, canView}`
- `GET water/site`
- `GET water/meter?siteId&activeOnly`, `POST water/meter`, `PUT water/meter/{id}`, `DELETE water/meter/{id}`
- `GET water/daily?siteId&year&month` — the grid, totals, average, estimated days and any issues
- `POST water/reading` — `{meterId, readingDate, cumulativeVolume, iotVolume, remark}` (upsert; send all three; clearing all three deletes the row)
- `DELETE water/reading/{id}`
- `GET water/monthly?siteId&year`
- `GET water/tariff?siteId`, `POST water/tariff`, `DELETE water/tariff?siteId&effectiveFrom`
- `POST water/bill` — `{siteId, year, month, actualRm}` (empty `actualRm` clears the invoice)

A reading write returns the refreshed daily grid. A bill write returns the refreshed year.

## Notes

- The daily grid writes on blur.
- Phase 1 is web only. The Flutter app still uses the older `utility_meter` endpoints.
