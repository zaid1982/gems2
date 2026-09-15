# Waste Management V1

GEMS Scheduled Waste Register, dashboard and JKR reporting. Official totals use **Final** records only.

## SQL

1. `sql/2026-09-15_create_wst_waste_module.sql`
2. `sql/add_waste_submenu.sql`

## Roles

The Waste Management menu is granted to **Administrator (`role_id = 1`) only**. Other roles do not see these pages until a later grant is added.

API capabilities remain role-based if a user opens a page URL directly:

| ID | Role | API capability |
|---|---|---|
| 1, 10 | Administrator / GFM Management | Full, including setup |
| 19 | Site Admin | Setup for own premise |
| 28 | Waste User | Create, edit, finalise, cancel Draft, attach documents |
| 29 | Waste Officer | Waste User plus amend Final, opening balance, JKR reports |

## Pages

| URL | Page |
|---|---|
| `p_waste_dashboard` | Dashboard |
| `p_waste_record_form` | New / edit waste record (`?id=`) |
| `p_waste_records` | Transaction list |
| `p_waste_opening_balance` | Opening balance |
| `p_waste_report` | JKR reports |
| `p_waste_setup` | Premises, profiles, locations, reference values |

## API (`waste` → `api/waste.php`)

Envelope `{ success, result, error, errmsg }`. JWT required.

- `GET waste/me` — capability flags
- `GET waste/sw_code?siteId=&activeOnly=`
- `GET|POST waste/premise`, `GET waste/premise/{siteId}`
- `GET|POST waste/profile`, `PUT|DELETE waste/profile/{id}`
- `GET|POST waste/location`, `PUT|DELETE waste/location/{id}`
- `GET|POST waste/ref_value`, `PUT|DELETE waste/ref_value/{id}`
- `GET waste/transaction`, `GET waste/transaction/{id}`
- `GET waste/transaction/duplicate_check?...`
- `POST waste/transaction`, `PUT waste/transaction/{id}`
- `POST waste/transaction/{id}/finalise|cancel|amend|document`
- `DELETE waste/transaction/{id}/document/{docId}`
- `GET waste/balance?siteId&swCodeId&asAt&type&qty&unit`
- `GET|POST waste/opening_balance`, `PUT waste/opening_balance/{id}`
- `GET waste/dashboard?...`
- `GET waste/report`, `GET waste/report/{id}`
- `POST waste/report/preview`, `POST waste/report`
- `POST waste/report/{id}/submission`

## Balance rules

`WasteBalance` is the single calculator for the form, dashboard and JKR report.

- Opening balance is not Produced.
- Final Produced adds kg; Final Disposed subtracts kg.
- Draft and Cancelled do not affect official totals.
- A disposal that would make any chronological running balance negative is blocked.
- 1 MT = 1,000 kg. Original qty/unit are kept; `qty_kg` is used for aggregation.

## Assumptions

- Premise = `cli_site` plus `wst_premise` settings.
- eSIS is treated as DOE/JAS eSWIS (manual reference only).
- Reporting period defaults to calendar month per premise.
- Evidence is optional unless the premise flags Produced/Disposed evidence as required.
