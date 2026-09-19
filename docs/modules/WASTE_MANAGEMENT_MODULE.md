# Waste Management V2

GEMS Scheduled Waste Register, dashboard and JKR reporting. Official totals use **Final** records only.

V2 adds the operational **Generation → Pending Collection → Disposal** lifecycle on top of the
same `wst_transaction` table, so the balance calculator and the JKR report keep working unchanged.

## SQL

1. `sql/2026-09-15_create_wst_waste_module.sql`
2. `sql/add_waste_submenu.sql`
3. `sql/2026-09-18_wst_generation_disposal.sql`
4. `sql/2026-09-18_nav_waste_kpa_enr.sql`

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
| `p_waste_generation` | Record waste as it is produced (V2) |
| `p_waste_pending` | Pending collection queue (V2) |
| `p_waste_dispose` | Execute a disposal (`?id=`) (V2) |

## Generation / Disposal lifecycle (V2)

```
Create generation  ->  FINAL Produced, entry_mode SIMPLE, collection_status PENDING
                       (registered weight, adds to the premise balance)
       |
       |-- edit weight / date / waste type while PENDING
       |-- delete while PENDING (row becomes CANCELLED, never removed)
       v
Execute disposal   ->  FINAL Disposed linked by parent_txn_id
                       (actual disposed weight, 2 mandatory images,
                        optional consignment note + receipt)
       v
Produced record    ->  collection_status DISPOSED, disposal_txn_id set
```

Rules:

- The registered weight stays on the Produced record. The **actual disposed weight** is the
  official quantity of the Disposed record, so a short collection leaves the remainder in the
  premise balance, which is what the JKR register needs.
- Both the *During disposal* and *After disposal* images are mandatory. Consignment note and
  receipt are optional, each with an optional reference number.
- A disposal is validated by `WasteBalance::validateChronological()`, so it can never take more
  than the premise holds for that waste code on that date.
- `entry_mode` is `REGISTER` for the Fifth Schedule form and `SIMPLE` for the generation and
  disposal screens. `validateForFinal()` skips the handling/location/packaging requirements and
  the premise evidence flags for `SIMPLE` rows, because the disposal screen enforces its own
  evidence rules.

New columns on `wst_transaction`: `entry_mode`, `collection_status`, `disposal_txn_id`,
`parent_txn_id`, `consignment_note_ref`, `consignment_receipt_ref`, `disposal_remarks`.
New columns on `wst_premise`: `default_handling_method_id`, `default_location_id` (auto-filled
onto SIMPLE rows so the JKR report still has values).

New `wst_ref_value` DOCUMENT_TYPE entries: `Disposal Image - During`, `Disposal Image - After`,
`Consignment Receipt`.

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
- `GET waste/generation?siteId&swCodeId&status=PENDING|DISPOSED&from&to&ref`
- `GET waste/generation/pending_summary?siteId` — pending kg grouped by waste type
- `GET waste/generation/{id}` — Produced record plus its linked disposal and documents
- `POST waste/generation` — `{siteId, swCodeId, qtyKg, eventDate, remarks}`
- `PUT waste/generation/{id}` — correct a PENDING record
- `DELETE waste/generation/{id}` — cancel a PENDING record (`reason` required)
- `POST waste/generation/{id}/dispose` — `{disposalDate, actualQtyKg, duringImage, afterImage,
  consignmentNote?, consignmentReceipt?, consignmentNoteRef?, consignmentReceiptRef?, remarks}`
- `GET waste/balance?siteId&swCodeId&asAt&type&qty&unit`
- `GET|POST waste/opening_balance`, `PUT waste/opening_balance/{id}`
- `GET waste/dashboard?...` — also accepts `year` and `month` as a period shortcut and returns
  `pendingBySw[]`, `disposedBySw[]`, `kpis.pendingTotalKg`, `kpis.disposedTotalKg`
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
- Evidence is optional unless the premise flags Produced/Disposed evidence as required. This
  applies to `REGISTER` rows only; `SIMPLE` disposals always require both images.
- Phase 1 of the generation/disposal lifecycle is web only. The Flutter app has no waste screens.
