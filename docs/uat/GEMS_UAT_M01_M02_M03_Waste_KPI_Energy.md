# GEMS User Acceptance Testing Script

## Waste Management · KPI & APD · Energy & Utility

| Field | Value |
| --- | --- |
| Document title | GEMS UAT Script — M01 Waste Management, M02 KPI & APD, M03 Energy & Utility |
| System | Government Facility Management System (GEMS) |
| Scope batch | Three new modules implemented before / independently of the Tabler UI rebrand (functional scope, not a UI-theme test) |
| Version | 1.0 |
| Date | 21 September 2026 |
| Prepared as | Senior Business Analyst / QA Lead / UAT Test Designer |
| Audience | Customer representatives, facility officers, KPI administrators, utility readers, GFM UAT facilitator |
| Execution fields | Actual Result = `________________` · Status = `Not Tested` |
| Companion workbook | `GEMS_UAT_M01_M02_M03_Test_Cases.csv` (same rows; import into the official UAT Excel workbook) |

---

## 0. How to use this script

1. Read **Section 2 (Business rules and formulas)** before any calculation case.
2. Create / confirm the **Section 3 test data and accounts** on a non-production UAT environment. Do not use live operational premises, months or meters.
3. Execute **Common** cases once, then M01, M02 and M03. The three **End-to-end** cases (WST-UAT-024, KPA-UAT-032, ENR-UAT-023) are the customer sign-off paths.
4. Write the **Actual Result** in objective figures (quantities, scores, messages). Tick Status **Pass** / **Fail** / **Blocked**. Use Severity only when logging a defect.
5. Where this script says **TBC — Business confirmation required**, do not invent a rule. Raise it in the UAT kick-off.
6. Where implementation differs from the intended business process, the case still tests the **intended business outcome** and the difference is listed in **Section 10**.

There is **no M04 module**. KPI Structure, PI Assignment, KPI Admin, PI Entry and KPI Viewer belong to **M02**. License, Space, Visitor and PTW are out of scope. Legacy PPNS KPI (`kpi_ppns` / `api/kpi.php`) and legacy Utility bills (`p_utility` / `utl_utility`) are out of scope.

**There is no approval workflow** on M01, M02 or M03.

---

## 1. Scope

| ID | Module | Implementation | In-scope functions | Out of scope |
| --- | --- | --- | --- | --- |
| M01 | Waste Management | `wst_*`, `api/waste.php` | Generation → Pending Disposal → Execute Disposal → Waste Records → Balance → Dashboard → JKR report (plus Opening Balance and Setup as supporting functions) | Flutter waste screens; eSWIS/eSIS integration; general GEMS admin |
| M02 | KPI & APD | `kpa_*`, `api/kpa.php` | Structure, PI Assignment, Config, Monthly Evaluation, PI Entry, Summary, History, M02 roles | Legacy PPNS KPI; contract-linked MPV; automatic GEMS+ parameter fetch; general user administration |
| M03 | Energy & Utility | `enr_*`, `api/energy.php` | Daily cumulative electricity, monthly summary, BEI, meter setup, site energy config | Legacy TNB bill Utility module; wiring daily totals into KPI PI 3B |

### Pages a tester will use

**Waste Management (sidebar):** Waste Dashboard (`p_waste_dashboard`), Waste Generation (`p_waste_generation`), Pending Disposal (`p_waste_pending`), Waste Record (`p_waste_records`).

**Waste Management (not in sidebar — type the page name in the GEMS URL):** Execute Disposal (`p_waste_dispose?id=`), Waste Record form (`p_waste_record_form?id=`), Opening Balance (`p_waste_opening_balance`), JKR Waste Reports (`p_waste_report`), Waste Setup (`p_waste_setup`).

**KPI & APD (sidebar by role):** KPI / APD Summary (`p_kpi_in`), Monthly Evaluation (`p_kpa_evaluation`), KPI History (`p_kpa_history`), KPI Structure (`p_kpa_structure`), PI Assignment (`p_kpa_assignment`). PI Entry (`p_kpa_pi_entry?id=`) is opened from the evaluation grid.

**Energy Monitoring (sidebar):** Daily Electricity (`p_energy_daily`), Monthly Summary (`p_energy_monthly`), Building Energy Index (`p_energy_bei`).

---

## 2. Business rules and formulas (read before testing)

### 2.1 Waste balance (implemented)

Official totals use **Final** records only. Draft and Cancelled are excluded. Opening balance is a starting stock figure; it is **not** Produced.

```
qty_kg = qty × 1,000 when unit is MT, otherwise qty (rounded to 3 decimal places)

Current / closing kg
  = Opening kg
  + Final Produced kg (event date after opening as-at, up to as-at)
  − Final Disposed kg (same window)

Check: Opening + Produced − Disposed = Closing
```

A disposal is rejected if it would make the chronological running balance negative:

*Message:* `Disposal quantity is not supported by the available balance for this event date. Review the quantity and earlier waste records.`

**Generation → collection rule**

| Step | What the system stores |
| --- | --- |
| Save generation | Final Produced, collection status **Pending Collection**, weight in **kg**, adds to premise balance |
| Edit / delete | Allowed only while Pending. Delete sets Cancelled (row kept) and requires a reason |
| Execute disposal | New Final Disposed row with **actual** kg; both During and After images mandatory; original produced weight stays on the generation row; collection status becomes **Disposed** |
| Short collection | Remainder stays in **premise balance**. No second pending row. The same generation cannot be disposed again |

**No approval step.**

### 2.2 KPI / APD (implemented)

There is **no approval step**. PI Entry captures and **Submit** locks the indicator. Only KPI Admin / Administrator can **Reopen**.

Creating a month **snapshots** the active structure. Later structure edits do not rewrite that month.

**Achievement (rounded to 4 decimal places)**

| calc_type | Rule | Seeded use |
| --- | --- | --- |
| EXPRESSION | Evaluate `formula_expr` using p1, p2, … and + − × ÷, min(), max() | Most PIs, e.g. 1A `(p1/p2)*100` |
| BACKLOG_AVG | For pairs (p1,p2), (p3,p4), (p5,p6) = (total, completed). Empty pair skipped. Pair with total ≤ 0 scores 100%. Achievement = mean of bucket scores | PI 1E |
| BEI | `(p1 + p2) / p3`, then × p4 if p4 is supplied. p3 must be > 0 | PI 3B |
| AVG_PARAMS | Mean of every supplied parameter | PI 4C |
| DIRECT | First supplied parameter (available, not seeded) | — |

**Pass rules** (epsilon 0.00005): `GTE_TARGET` actual ≥ target; `LTE_TARGET` actual ≤ target (PI 3B); `EQ_TARGET` absolute difference ≤ epsilon.

BEI achievement is the BEI number; the summary **result %** for BEI is **100 if pass, 0 if fail**.

**APD (rounded to 2 decimal places)**

```
apdMax       = round(MPV × maxApdPct / 100, 2)
apdExposure  = round(apdMax × PI weightagePct / 100, 2)

If PI meets target:  demeritImposed = 0,  apdDeducted = 0
If PI misses target: demeritImposed = demeritPoint, apdDeducted = full apdExposure
```

Workbook check used in UAT:

```
MPV        = 4,147,120.556
maxApdPct  = 5.00
apdMax     = 207,356.03
PI at 5%   = 10,367.80
PI at 3%   = 6,220.68
PI at 8%   = 16,588.48
```

Month status: **Open** until every snapshotted PI is Submitted, then **Completed**.

### 2.3 Energy consumption and BEI (implemented)

Users enter **cumulative kWh** on the days a reading is taken. The system **derives** daily consumption.

```
For each pair of readings on the same meter (earlier → later):
  gapDays = calendar days between the two dates
  delta   = later cumulative − earlier cumulative
  If delta < 0: do not spread; warn; those days stay blank
  Else: perDay = delta / gapDays
        assign perDay to each day after the earlier date through to the later date

The first reading on a meter is a baseline (no consumption that day).
Days that no interval covers stay blank — not zero.
averageDaily = totalKwh / days that have data
```

**Controlled 375 kWh example (adapted)**

| Date | Incoming No.1 cumulative (kWh) | Derived consumption (kWh) |
| --- | ---: | ---: |
| 31/08/2026 | 10,000.00 | baseline |
| 01/09/2026 | 10,100.00 | 100.00 |
| 02/09/2026 | 10,225.00 | 125.00 |
| 03/09/2026 | 10,375.00 | 150.00 |
| **September total** | | **375.00** |

**BEI**

```
totalKwh  = electricity kWh + chilled-water kWh
actualBei = round((totalKwh / floorAreaSqm) × annualiseFactor, 4)
pass      = actualBei ≤ targetBei   (lower is better)
resultPct = 100 if pass else 0
```

Electricity is prefilled from the monthly total of daily readings. Typing over it is an **override**. Chilled water is entered manually. Finalising locks the month.

If floor area ≤ 0 the BEI is not scored: `Enter a gross floor area greater than zero in the site configuration.`

---

## 3. Reusable test data

Replace the **TBC** names with the accounts and site/premise the UAT facilitator issues. Do not invent live customer names.

### 3.1 Common / environment

| Item | Value |
| --- | --- |
| Environment | UAT / staging — **not production** |
| Application | GEMS web |
| UAT window | September 2026 official month (today is 21/09/2026; generation/disposal/reading dates cannot be in the future) |
| Date rule | If a scripted date is still in the future on the test day, use **today** and write the actual date in Actual Result |

### 3.2 Waste (M01)

| Item | Value |
| --- | --- |
| Premise | **UAT Premise A** — dedicated premise/site issued by the facilitator. Do not use a live operational premise. Demo seed data (if present on an old demo site) already contains many Final rows and will break the 100/50/30 arithmetic |
| Scheduled waste type | Prefer **SW410** — *Rags, plastics, papers or filters contaminated with scheduled wastes*. Alternative: **SW305** — *Spent lubricating oil*. Use whatever active type appears in the Waste type list for UAT Premise A |
| Unit on generation / disposal | **kg** (0.001 precision) |
| Opening (controlled) | 100.000 kg as at 31/08/2026 |
| Generated (E2E) | 50.000 kg on 15/09/2026 or today — capture system reference as **WST-REF-01** |
| Disposed (E2E) | 30.000 kg actual; two photographs |
| Expected current balance | **120.000 kg** |
| Evidence files | Two images (During / After), each under 10 MB. Optional consignment PDF |
| Spare SW code | SW305 — for zero/negative opening tests so SW410 100 kg is not overwritten |

### 3.3 KPI & APD (M02)

| Item | Value |
| --- | --- |
| Site | **UAT Site A** |
| Official E2E month | **September 2026** |
| Boundary month | **August 2026** (Dataset B) |
| Fail month | **October 2026** (Dataset C) |
| MPV | 4,147,120.556 |
| Maximum APD % | 5.00 |
| APD Maximum | 207,356.03 |
| Official E2E PI | **1A** Customer Satisfaction Survey rating |
| Calculation PIs | 1A, 1E, 3B, 3C, 4C (and 1B for the exposure check) |
| KPI Admin | Role **30** — account **UAT-KPI-ADMIN** (TBC) |
| PI Entry | Role **31** — account **UAT-PI-ENTRY** (TBC), assigned 1A, 1E, 3B, 4C |
| KPI Viewer | Role **32** — account **UAT-KPI-VIEWER** (TBC) |

Seeded structure (do not deactivate on a shared template):

| Group | Weight | Sample PIs |
| --- | ---: | --- |
| 1 FMM Service Delivery related to Core Business | 61% | 1A target 80% `(p1/p2)*100`; 1E backlog average; 1I / 1J availability |
| 2 Asset Performance | 20% | 2A–2E |
| 3 Building Energy Efficiency | 10% | 3A; **3B BEI target 170.65 LTE**; 3C `100-p1` |
| 4 Safety & Statutory Compliance | 9% | 4A; 4B; **4C** mean of timeliness % and content % |
| **Total** | **100%** | 21 PIs |

### 3.4 Energy (M03)

| Item | Value |
| --- | --- |
| Site | **UAT Site A** |
| Meters | Seeded **Incoming No.1** (official chain), **Incoming No.2** (gap / year-end / rollback only) |
| Official cumulatives | 31/08/2026 **10,000.00** → 01/09 **10,100.00** → 02/09 **10,225.00** → 03/09 **10,375.00** |
| Official September kWh | **375.00** on Incoming No.1 |
| GFA | 10,000 m² |
| Target BEI | 170.65 |
| Annualisation factor | 12 |
| Chilled water (Sep) | 125.00 kWh |
| Expected Sep BEI | **0.6000** (pass) |
| Utility Reader | Role **18** — **UAT-UTILITY-READER** (TBC) |

### 3.5 Calculation datasets (KPI)

**Dataset A — Normal (September 2026, official)**

| PI | Inputs | Manual achievement | Pass? | APD deducted |
| --- | --- | ---: | --- | ---: |
| 1A | p1=88, p2=100 | 88.0000% | Yes (≥80) | 0.00 |
| 1E | 10/10, 5/5, 0/0 | 100.0000% | Yes (≥100) | 0.00 |
| 4C | 100, 100 | 100.0000% | Yes (≥100) | 0.00 |

**Dataset B — Boundary (August 2026)**

| PI | Inputs | Manual achievement | Pass? |
| --- | --- | ---: | --- |
| 1A | p1=80, p2=100 | 80.0000% | Yes (on target) |
| 3B | p1=142208.3333, p2=0, p3=10000, p4=12 | BEI 170.6500 | Yes (LTE target) |
| 3C | p1=0 | 100.0000% | Yes (on target) |

**Dataset C — Fail (October 2026)**

| PI | Inputs | Manual achievement | Pass? | APD deducted |
| --- | --- | ---: | --- | ---: |
| 1A | p1=60, p2=100 | 60.0000% | No | 10,367.80 |
| 1E | 10/8, 5/5, 0/0 | 93.3333% | No | 10,367.80 |
| 3B | p1=200000, p2=50000, p3=10000, p4=12 | BEI 300.0000 (result 0%) | No | 6,220.68 |
| 4C | 90, 80 | 85.0000% | No | 6,220.68 |
| **Four-PI subtotal** | | | | **33,176.96** |

---

## 4. Entry criteria

- [ ] UAT environment has the waste, KPA and energy SQL applied (`2026-09-15_create_wst_waste_module.sql`, `2026-09-18_wst_generation_disposal.sql`, `2026-09-18_create_kpa_module.sql`, `2026-09-18_create_enr_module.sql`, `2026-09-18_nav_waste_kpa_enr.sql`)
- [ ] Dedicated UAT premise/site created; not a live operational site
- [ ] UAT Premise A has at least one active waste type (profile) so the generation dropdown is not empty
- [ ] Opening 100 kg posted only when the facilitator is ready for the waste arithmetic
- [ ] KPI template present (4 groups / 21 PIs / 100% weightage)
- [ ] Incoming No.1 and Incoming No.2 exist on UAT Site A
- [ ] Named UAT accounts issued for Admin, Waste User, Waste Officer, KPI Admin, PI Entry, KPI Viewer, Utility Reader
- [ ] Testers have two sample photos and (optional) a PDF
- [ ] Testers have been briefed: waste generation is kg; energy is **cumulative** readings; KPI has **no approval**

---

## 5. Common UAT

<table>
<thead><tr>
<th>UAT ID</th>
<th>Module</th>
<th>Function / Submodule</th>
<th>Test Scenario</th>
<th>Preconditions</th>
<th>Test Steps</th>
<th>Test Data</th>
<th>Expected Result</th>
<th>Actual Result</th>
<th>Status</th>
<th>Severity</th>
<th>Remarks</th>
</tr></thead><tbody>
<tr>
<td>COM-UAT-001</td>
<td>Common</td>
<td>Navigation</td>
<td>Verify a business user can open Waste Management, KPI &amp; APD and Energy Monitoring from the GEMS sidebar and reach the intended working pages.</td>
<td>User holds Administrator, or a role granted the relevant module menus.</td>
<td>1. Login to GEMS.<br>2. In the sidebar, expand Waste Management and open each visible child (Waste Dashboard, Waste Generation, Pending Disposal, Waste Record).<br>3. Expand KPI &amp; APD and open each visible child allowed for the role.<br>4. Expand Energy Monitoring and open Daily Electricity, Monthly Summary and Building Energy Index.</td>
<td>UAT Admin or the role under test.</td>
<td>Each listed page opens without an error page. Sidebar parent labels are Waste Management, KPI &amp; APD and Energy Monitoring. Waste children include Waste Dashboard, Waste Generation, Pending Disposal and Waste Record. KPI children include KPI / APD Summary, Monthly Evaluation, KPI History, and (Admin only) KPI Structure and PI Assignment. Energy children are Daily Electricity, Monthly Summary and Building Energy Index.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Opening Balance, JKR Waste Reports, Waste Setup, Execute Disposal and PI Entry are implemented but hidden from the sidebar (URL / in-page links only). See GAP-WST-01 and GAP-KPA-01.</td>
</tr>
<tr>
<td>COM-UAT-002</td>
<td>Common</td>
<td>Page identity</td>
<td>Verify each M01–M03 working page shows the correct browser title and on-screen page heading so users know where they are.</td>
<td>COM-UAT-001 passed for the role under test.</td>
<td>1. Open each page listed in Test Data.<br>2. Record the browser tab title and the page heading (H1).<br>3. Confirm no breadcrumb trail is shown.</td>
<td>Waste Generation → title 'GEMS 2.0 - Waste Generation', H1 'Waste Generation'. Pending Disposal → 'GEMS 2.0 - Pending Disposal' / 'Pending Disposal'. Waste Records → 'GEMS 2.0 - Waste Records' / 'Waste Record'. Waste Dashboard → 'GEMS 2.0 - Waste Dashboard' / 'Waste Dashboard'. KPI / APD Summary → 'GEMS 2.0 - KPI / APD Dashboard' / 'KPI / APD Dashboard'. Monthly Evaluation → 'GEMS 2.0 - Monthly KPI Evaluation' / 'Monthly KPI Evaluation'. KPI History → 'GEMS 2.0 - KPI History' / 'KPI History'. Daily Electricity → 'GEMS 2.0 - Daily Electricity' / 'Daily Electricity'. Monthly Summary → 'GEMS 2.0 - Monthly Electricity Summary' / matching H1. BEI → 'GEMS 2.0 - Building Energy Index' / matching H1.</td>
<td>Every listed title and heading matches. Breadcrumb is not present (implementation gap — page title is the identifier).</td>
<td>________________</td>
<td>Not Tested</td>
<td>Low</td>
<td>GAP-COM-01: breadcrumbs are not implemented on these pages.</td>
</tr>
<tr>
<td>COM-UAT-003</td>
<td>Common</td>
<td>Save / Cancel / Back</td>
<td>Verify a user can abandon an incomplete record without saving, and can return to the previous list from a detail page.</td>
<td>User can open Waste Generation and Pending Disposal.</td>
<td>1. Open Waste Generation.<br>2. Enter a weight but do not save. Click Pending Disposal (or navigate away).<br>3. Return to Waste Generation and confirm the unsaved weight is cleared.<br>4. From Pending Disposal, open Execute Disposal on any pending row (or open Waste Generation then Pending Disposal).<br>5. Click Back on Execute Disposal.</td>
<td>Any unsaved draft values; no save.</td>
<td>Unsaved generation is not written to Pending Disposal or Waste Records. Back from Execute Disposal returns to Pending Disposal.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Cancel on Waste Generation is navigation, not an API cancel. Do not confuse with deleting a saved pending record (WST-UAT-009).</td>
</tr>
<tr>
<td>COM-UAT-004</td>
<td>Common</td>
<td>Empty state</td>
<td>Verify list and dashboard pages show a clear empty message when the selected premise/site and filters have no records.</td>
<td>A clean UAT premise/site with no M01–M03 transactions for the selected period, or filters that match nothing.</td>
<td>1. Open Waste Generation for a premise with no recent generation — check the recent list.<br>2. Open Pending Disposal with Status = Pending and a date range that has none — check list and summary.<br>3. Open Waste Records with filters that match nothing.<br>4. Open Monthly Evaluation for a year with no months (or a new site).<br>5. Open KPI History for a year range with no evaluations.<br>6. Open Daily Electricity for a month with no readings.</td>
<td>UAT empty site/premise; filters that return zero rows.</td>
<td>Pages do not crash. User sees the implemented empty text, including: 'No generation records yet.'; 'No pending disposal records.' / 'Nothing is pending disposal'; 'No waste records yet.' or 'No records match the current filters.'; 'No evaluation months yet.'; 'No evaluation months in the selected range.'; daily grid still renders calendar days with blank consumption (not invented zeros) when no readings exist.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Empty daily energy days show em-dash, not 0.00. Monthly energy table may show 0.00 for a meter with no data — see GAP-ENR-01.</td>
</tr>
<tr>
<td>COM-UAT-005</td>
<td>Common</td>
<td>Validation and success messages</td>
<td>Verify mandatory-field and success messages are shown in business language (not a blank failure) when a user saves incorrectly and when a save succeeds.</td>
<td>User can create waste generation and PI entry (or Admin can).</td>
<td>1. On Waste Generation, click Save with waste type and weight blank.<br>2. Fill valid data and Save.<br>3. On a PI Entry page (Admin), click Submit with a required parameter blank.<br>4. Enter a valid value, Save Draft, then observe the success notification.</td>
<td>Blank mandatory fields; then valid WST-GEN-01 data / a valid PI parameter.</td>
<td>Incomplete save is blocked and a readable warning is shown (Waste: 'Select a waste type.' / 'Enter a waste weight greater than zero.'). Successful generation shows 'Waste generation recorded and is now pending collection.' PI submit without a required value shows 'Enter a value for "{parameter label}" before submitting.' Successful PI save/submit shows the corresponding success notification.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Record the exact on-screen wording. Generic 'Something went wrong' is a fail.</td>
</tr>
<tr>
<td>COM-UAT-006</td>
<td>Common</td>
<td>Data persistence</td>
<td>Verify a saved business record is still present after browser refresh and after leaving the module and returning.</td>
<td>WST-UAT-001 or equivalent saved generation exists.</td>
<td>1. Save a waste generation (or use WST-GEN-01).<br>2. Refresh the browser on Waste Generation.<br>3. Open Pending Disposal and find the record.<br>4. Logout, login again, and search the same record.</td>
<td>WST-GEN-01 (reference captured during WST-UAT-001).</td>
<td>The same waste reference, premise, waste type, date and weight are still shown. The record was not duplicated by refresh.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>If refresh creates a second record, fail as duplicate-submit defect.</td>
</tr>
<tr>
<td>COM-UAT-007</td>
<td>Common</td>
<td>Access denied</td>
<td>Verify a user with no Waste, KPI or Energy role cannot open those modules from the sidebar, and a direct URL does not allow them to change data.</td>
<td>A GEMS user exists who is not Administrator, Waste User/Officer, KPI Admin/PI Entry/KPI Viewer, Utility Reader or KPI Admin.</td>
<td>1. Login as the unauthorised user.<br>2. Confirm Waste Management, KPI &amp; APD and Energy Monitoring are not in the sidebar.<br>3. Paste a direct URL for p_waste_generation, p_kpa_structure and p_energy_daily.<br>4. Attempt to save if the page renders.</td>
<td>UAT-NOACCESS account (TBC — Business confirmation required).</td>
<td>Sidebar does not show the three modules. Direct URL either redirects, shows an access message, or the save is rejected with 'You are not allowed to create or change waste records.' / 'You are not allowed to change the KPI structure.' / 'You are not allowed to record meter readings.' No new official record is created.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Account list is TBC. Do not use this case to test general GEMS user administration screens.</td>
</tr>
<tr>
<td>COM-UAT-008</td>
<td>Common</td>
<td>Date and numeric format</td>
<td>Verify dates and quantities display in a consistent business format so dashboard, lists and forms can be reconciled.</td>
<td>At least one waste generation in kg with 3 decimal places and one energy reading with 2 decimal places exist.</td>
<td>1. Open Waste Generation / Pending Disposal / Dashboard for the test record.<br>2. Confirm weight is shown in kg (3 decimal places where entered).<br>3. Open Daily Electricity and confirm cumulative/consumption use 2 decimal places.<br>4. Open KPI summary and confirm APD amounts are in RM with 2 decimal places.</td>
<td>WST-GEN-02 (12.500 kg); ENR daily reading 10000.00; any KPI month with APD figures.</td>
<td>Waste quantities are kg on generation/pending (not silently converted to MT on those screens). Energy kWh shows 2 decimals. APD shows 2 decimals. Dates use the date picker / ISO calendar date (YYYY-MM-DD) on forms and a readable date on lists. 1 MT = 1,000 kg is stated on the waste dashboard unit note.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Low</td>
<td>Fifth Schedule Waste Record form still uses MT or kg — do not treat that as a generation-screen defect.</td>
</tr>
</tbody></table>


---

## 6. M01 — Waste Management

<table>
<thead><tr>
<th>UAT ID</th>
<th>Module</th>
<th>Function / Submodule</th>
<th>Test Scenario</th>
<th>Preconditions</th>
<th>Test Steps</th>
<th>Test Data</th>
<th>Expected Result</th>
<th>Actual Result</th>
<th>Status</th>
<th>Severity</th>
<th>Remarks</th>
</tr></thead><tbody>
<tr>
<td>WST-UAT-001</td>
<td>M01 Waste Management</td>
<td>Waste Generation</td>
<td>Verify a facility officer can record scheduled waste generated at a premise and that the record is accepted into the official register.</td>
<td>Tester is logged in with a Waste User, Waste Officer or Administrator account that can see Waste Management. Dedicated UAT premise (UAT Premise A) is selected. At least one active scheduled waste type is available for that premise (prefer SW410 if listed). Do not use a live operational premise.</td>
<td>1. Login to GEMS.<br>2. Open Waste Management &gt; Waste Generation.<br>3. Select UAT Premise A.<br>4. Select waste type SW410 (or the agreed UAT waste type).<br>5. Enter waste weight 50.000 kg.<br>6. Set Date to 15/09/2026 (or today if 15/09/2026 is in the future on the test day).<br>7. Enter remarks 'UAT WST-GEN-01'.<br>8. Save.<br>9. Record the waste reference shown in the recent list.</td>
<td>Premise: UAT Premise A. Waste type: SW410. Weight: 50.000 kg. Date: 15/09/2026 (or today). Remarks: UAT WST-GEN-01. Capture system reference as WST-REF-01.</td>
<td>Save succeeds. Notification: 'Waste generation recorded and is now pending collection.' Recent list shows the new row with matching waste type, date, registered weight 50.000 kg and status Pending Collection. A waste reference is allocated. Record is FINAL in the official register (it counts toward balance).</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Generation saves immediately as Final + Pending Collection. There is no draft and no approval step. Date cannot be future — if UAT runs before 15/09/2026 use today and record the actual date used.</td>
</tr>
<tr>
<td>WST-UAT-002</td>
<td>M01 Waste Management</td>
<td>Waste Generation</td>
<td>Verify waste generation cannot be saved when mandatory business information is missing.</td>
<td>Tester is logged in with a Waste User, Waste Officer or Administrator account that can see Waste Management. Dedicated UAT premise (UAT Premise A) is selected. At least one active scheduled waste type is available for that premise (prefer SW410 if listed). Do not use a live operational premise.</td>
<td>1. Open Waste Generation.<br>2. Clear or leave Waste type unselected, leave Waste weight blank, and clear Date if possible.<br>3. Attempt Save.<br>4. Select a waste type only, leave weight blank, attempt Save.<br>5. Enter weight 50.000 and leave waste type blank, attempt Save.</td>
<td>Blank waste type; blank weight; blank date.</td>
<td>No new pending record is created. User is shown the implemented messages: 'Select a waste type.'; 'Enter a waste weight greater than zero.'; 'Enter the waste generation date.' as applicable.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Mandatory fields on this screen: Premise, Waste type, Waste weight (kg), Date. Remarks are optional.</td>
</tr>
<tr>
<td>WST-UAT-003</td>
<td>M01 Waste Management</td>
<td>Waste Generation</td>
<td>Verify a facility officer cannot record a zero or negative generated weight.</td>
<td>Tester is logged in with a Waste User, Waste Officer or Administrator account that can see Waste Management. Dedicated UAT premise (UAT Premise A) is selected. At least one active scheduled waste type is available for that premise (prefer SW410 if listed). Do not use a live operational premise.</td>
<td>1. Open Waste Generation and select UAT Premise A and SW410.<br>2. Enter weight 0 and a valid date. Save.<br>3. Enter weight -10. Save.<br>4. Enter weight 50.000 and Save to confirm the form still works afterwards.</td>
<td>Weight 0; weight -10; then valid 50.000 kg (discard or cancel this extra row if created — or use it as WST-GEN-extra).</td>
<td>0 and -10 are rejected. Message includes 'Enter a waste weight greater than zero.' No official pending row is created for the invalid attempts.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Minimum accepted weight is 0.001 kg.</td>
</tr>
<tr>
<td>WST-UAT-004</td>
<td>M01 Waste Management</td>
<td>Waste Generation</td>
<td>Verify generated waste cannot be backdated into the future.</td>
<td>Tester is logged in with a Waste User, Waste Officer or Administrator account that can see Waste Management. Dedicated UAT premise (UAT Premise A) is selected. At least one active scheduled waste type is available for that premise (prefer SW410 if listed). Do not use a live operational premise.</td>
<td>1. Open Waste Generation.<br>2. Select premise and waste type.<br>3. Enter weight 10.000 kg.<br>4. Set Date to tomorrow. Save.</td>
<td>Date = tomorrow. Weight 10.000 kg.</td>
<td>Save is rejected. Message: 'The waste generation date cannot be in the future.' No pending record is created.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Date control also has max = today.</td>
</tr>
<tr>
<td>WST-UAT-005</td>
<td>M01 Waste Management</td>
<td>Waste Generation</td>
<td>Verify a decimal scheduled-waste quantity can be recorded and displayed at gram precision.</td>
<td>Tester is logged in with a Waste User, Waste Officer or Administrator account that can see Waste Management. Dedicated UAT premise (UAT Premise A) is selected. At least one active scheduled waste type is available for that premise (prefer SW410 if listed). Do not use a live operational premise.</td>
<td>1. Open Waste Generation.<br>2. Select UAT Premise A and SW410.<br>3. Enter 12.500 kg, date today, remarks 'UAT WST-GEN-02'.<br>4. Save.<br>5. Confirm the recent list and Pending Disposal show 12.500 kg.</td>
<td>WST-GEN-02: 12.500 kg, today, SW410, remarks UAT WST-GEN-02. Capture reference WST-REF-02.</td>
<td>Record saves. Registered weight displays as 12.500 kg on Generation recent list and Pending Disposal. Balance increases by 12.500 kg (not rounded to a whole kilogram).</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Use this row later only if isolation is still clean; otherwise cancel it after the check.</td>
</tr>
<tr>
<td>WST-UAT-006</td>
<td>M01 Waste Management</td>
<td>Pending Disposal</td>
<td>Verify newly generated scheduled waste appears in Pending Disposal with the correct premise, waste code and outstanding weight.</td>
<td>WST-UAT-001 completed. WST-REF-01 known.</td>
<td>1. Open Waste Management &gt; Pending Disposal.<br>2. Select UAT Premise A.<br>3. Set Status to Pending.<br>4. Search using the waste reference from WST-UAT-001.<br>5. Read the row and the Pending by waste-type summary.</td>
<td>WST-REF-01; 50.000 kg; SW410; UAT Premise A.</td>
<td>The row is listed. Waste type / SW code matches SW410. Generated date matches. Registered (kg) = 50.000. Disposed (kg) is blank or 0. Status badge = Pending Collection. Pending Weight KPI includes this 50.000 kg. Summary table shows SW410 with pending kg including 50.000.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Screen title is Pending Disposal; status text is Pending Collection. Both are correct implemented labels.</td>
</tr>
<tr>
<td>WST-UAT-007</td>
<td>M01 Waste Management</td>
<td>Pending Disposal</td>
<td>Verify several generation records for the same premise remain separate and the pending weight is the sum of outstanding rows.</td>
<td>WST-REF-01 (50.000 kg) and WST-REF-02 (12.500 kg) both still Pending for the same premise and waste type. No other pending SW410 on that premise, or tester records the before/after totals.</td>
<td>1. Note Pending Weight and SW410 summary kg before this check (or immediately after creating the two rows).<br>2. Confirm both references appear as separate rows.<br>3. Add the registered kg of all pending SW410 rows and compare with the SW410 summary Pending (kg) and the Pending Weight card.</td>
<td>WST-REF-01 50.000 + WST-REF-02 12.500 = 62.500 kg (if only these two pending SW410 rows).</td>
<td>Each generation remains its own row with its own reference. Pending Weight and SW410 Pending (kg) equal the sum of pending registered weights for that filter (62.500 kg if only these two).</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>If other pending rows exist, reconcile against the full filtered list — do not force 62.500.</td>
</tr>
<tr>
<td>WST-UAT-008</td>
<td>M01 Waste Management</td>
<td>Pending Disposal</td>
<td>Verify a pending generation can be corrected (waste type, weight, date, remarks) before collection.</td>
<td>A dedicated pending row exists (create WST-GEN-03: 20.000 kg, remarks UAT-EDIT-BEFORE) that will not be used in the 100/50/30 balance case.</td>
<td>1. Open Pending Disposal.<br>2. On the dedicated pending row, click Edit.<br>3. Change weight to 25.000 kg, date if needed, remarks to 'UAT-EDIT-AFTER'.<br>4. Save.<br>5. Re-open the row / refresh the list.</td>
<td>Before: 20.000 kg. After: 25.000 kg, remarks UAT-EDIT-AFTER. Optional correction reason.</td>
<td>Save succeeds. List shows 25.000 kg and updated remarks. Pending summary kg increases by 5.000 versus the pre-edit value. Status remains Pending Collection. Official balance uses 25.000 kg, not 20.000 kg.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Edit is allowed only while Pending. After disposal the generation weight is locked.</td>
</tr>
<tr>
<td>WST-UAT-009</td>
<td>M01 Waste Management</td>
<td>Pending Disposal</td>
<td>Verify a pending generation can be cancelled so it leaves the collection queue and no longer affects the premise balance, while remaining on the audit trail.</td>
<td>A dedicated pending row exists that is not part of the official E2E transaction (create WST-GEN-04: 8.000 kg if needed).</td>
<td>1. Note Current Balance / pending kg for the waste type.<br>2. On the dedicated row, click Delete.<br>3. Leave reason blank and confirm — expect block.<br>4. Enter reason 'UAT cancel pending — not collected'.<br>5. Confirm delete.<br>6. Refresh Pending Disposal (Status Pending and Status All).<br>7. Open Waste Records and search the reference.</td>
<td>Reason required: 'UAT cancel pending — not collected'. Weight 8.000 kg.</td>
<td>Blank reason is rejected: 'Enter the reason for deleting this pending waste record.' After confirm, row disappears from Pending. Modal text advised the row is cancelled and kept for audit. Waste Records / Status All can still find it as cancelled / not pending. Premise balance no longer includes the 8.000 kg. Notification confirms the cancel.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>The row is not physically removed. Cancelled and Draft records are excluded from official totals.</td>
</tr>
<tr>
<td>WST-UAT-010</td>
<td>M01 Waste Management</td>
<td>Disposal</td>
<td>Verify a facility officer can record a full collection/disposal equal to the registered weight, with both mandatory disposal photographs.</td>
<td>A pending generation exists for full disposal (create WST-GEN-05: 40.000 kg, date today, remarks UAT-FULL-DISP). Two image files are available.</td>
<td>1. Open Pending Disposal and find WST-GEN-05.<br>2. Click Execute disposal.<br>3. Confirm Section A shows the same reference, waste type, generated date, 40.000 kg and premise.<br>4. Attach During disposal image and After disposal image.<br>5. Set Disposal date = today (on or after generated date).<br>6. Enter Actual disposed weight 40.000 kg.<br>7. Save.<br>8. Return to Pending Disposal, Status = Pending, then Status = Disposed.</td>
<td>WST-GEN-05: registered 40.000 kg; actual 40.000 kg; two JPG/PNG images; disposal date = today.</td>
<td>Save succeeds. Message: 'Disposal recorded. The waste record is now marked as Disposed.' User is returned to Pending Disposal. Row is no longer Pending Collection. Status = Disposed. Disposed (kg) = 40.000. A linked disposal transaction exists. Official balance decreases by 40.000 kg.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>There is no approval step. One disposal per generation record.</td>
</tr>
<tr>
<td>WST-UAT-011</td>
<td>M01 Waste Management</td>
<td>Disposal</td>
<td>Verify disposal cannot be completed without both during and after photographs.</td>
<td>A pending generation exists (create WST-GEN-06: 5.000 kg if needed).</td>
<td>1. Open Execute Disposal for the pending row.<br>2. Enter disposal date and actual weight 5.000 kg but attach no images. Attempt Save.<br>3. Attach only the During image. Attempt Save.<br>4. Attach only the After image (remove During if needed). Attempt Save.</td>
<td>Images missing; weight 5.000 kg.</td>
<td>Save remains blocked or is rejected. Messages: 'Attach the during disposal image before saving.' and/or 'Attach the after disposal image before saving.' Record stays Pending Collection. No disposal transaction is created.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Save control is disabled until both images are attached.</td>
</tr>
<tr>
<td>WST-UAT-012</td>
<td>M01 Waste Management</td>
<td>Disposal</td>
<td>Verify disposal date cannot be in the future and cannot be earlier than the generation date.</td>
<td>Pending generation dated today or a known past date (use WST-GEN-06 if still pending).</td>
<td>1. Open Execute Disposal.<br>2. Attach both images.<br>3. Set disposal date to tomorrow, actual weight equal to registered. Save.<br>4. Set disposal date to one day before the generated date. Save.<br>5. Set disposal date = generated date (or today). Do not save if this would consume the E2E row — cancel out after the messages are confirmed, or use a disposable pending row.</td>
<td>Future date; date before generation; valid date = generated date.</td>
<td>Future date rejected: 'The disposal date cannot be in the future.' Earlier-than-generation rejected: 'The disposal date cannot be earlier than the generation date.' Record remains pending until a valid save.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Use a disposable pending row so the E2E 50 kg row is not accidentally disposed here.</td>
</tr>
<tr>
<td>WST-UAT-013</td>
<td>M01 Waste Management</td>
<td>Disposal</td>
<td>Verify a disposal that would take more scheduled waste than the premise holds is rejected.</td>
<td>Known current balance for UAT Premise A + SW410 (from Dashboard Current Balance or Opening + net). A pending generation exists.</td>
<td>1. Open Waste Dashboard, filter premise + SW410, note Current Balance (kg).<br>2. Open Execute Disposal on a pending SW410 row.<br>3. Attach both images.<br>4. Enter Actual disposed weight = Current Balance + 100 kg.<br>5. Save.</td>
<td>Actual qty = current SW410 balance + 100 kg.</td>
<td>Save is rejected. Message: 'Disposal quantity is not supported by the available balance for this event date. Review the quantity and earlier waste records.' Generation remains Pending Collection. Balance unchanged.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Actual disposed may exceed the registered weight of this one row if the premise holds enough of that SW code (opening + earlier production). This test uses an amount larger than the whole premise balance.</td>
</tr>
<tr>
<td>WST-UAT-014</td>
<td>M01 Waste Management</td>
<td>Disposal</td>
<td>Verify a short collection (actual disposed less than registered) marks the generation as Disposed and leaves the remainder in the premise balance — not as a new pending row.</td>
<td>Use the official E2E generation WST-REF-01 (50.000 kg) only if the controlled balance case is being executed now; otherwise create WST-GEN-07: 50.000 kg pending.</td>
<td>1. Note Current Balance for the premise + SW code.<br>2. Execute disposal on the 50.000 kg pending row.<br>3. Attach both images.<br>4. Disposal date today.<br>5. Actual disposed weight 30.000 kg.<br>6. Save.<br>7. Open Pending Disposal Status = Pending and search the reference.<br>8. Switch Status to Disposed and find the reference.<br>9. Recalculate Current Balance.</td>
<td>Registered 50.000 kg. Actual disposed 30.000 kg. Remainder 20.000 kg.</td>
<td>Disposal saves. Generation status becomes Disposed (not left Pending). No second pending row is created for the 20.000 kg shortfall. Registered weight on the produced record remains 50.000 kg. Disposed (kg) on the list = 30.000. Premise current balance decreases by 30.000 kg only (remainder 20.000 kg stays in stock). Screen may note that the remainder stays in the premise balance.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>This is the implemented partial-disposal rule. Do not expect a split pending record. One disposal per generation — the 20 kg cannot be disposed against the same generation again.</td>
</tr>
<tr>
<td>WST-UAT-015</td>
<td>M01 Waste Management</td>
<td>Disposal</td>
<td>Verify consignment note and receipt are optional and can be stored with reference numbers when provided.</td>
<td>A disposable pending row exists (create WST-GEN-08: 6.000 kg). Optional PDF/JPG files available.</td>
<td>1. Execute disposal.<br>2. Attach both mandatory images.<br>3. Attach a consignment note and enter reference 'CN-UAT-001'.<br>4. Attach a consignment receipt and enter reference 'CR-UAT-001'.<br>5. Enter actual = registered. Save.<br>6. Open the record from Waste Records / View and confirm the documents and references are visible.</td>
<td>CN-UAT-001; CR-UAT-001; two evidence images; optional note/receipt files.</td>
<td>Disposal succeeds with or without consignment files. When provided, the files and reference numbers are stored on the disposal record and can be seen when the record is opened.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Consignment documents are optional. Evidence images are not.</td>
</tr>
<tr>
<td>WST-UAT-016</td>
<td>M01 Waste Management</td>
<td>Disposal</td>
<td>Verify a generation that has already been disposed cannot be disposed a second time.</td>
<td>WST-UAT-010 or WST-UAT-014 already disposed a row. Tester has that reference.</td>
<td>1. Open Pending Disposal, Status = Disposed, find the disposed reference.<br>2. Confirm Execute disposal is not offered (or open p_waste_dispose?id= of that record if the action is hidden).<br>3. If the page opens, attempt to save another disposal.</td>
<td>Already-disposed reference from WST-GEN-05 or WST-REF-01.</td>
<td>Execute disposal is not available for Disposed rows. If the URL is opened, the system states 'This waste record is already disposed and can no longer be changed.' No second disposal transaction is created.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Remainder after a short collection is not disposed through the same generation row.</td>
</tr>
<tr>
<td>WST-UAT-017</td>
<td>M01 Waste Management</td>
<td>Waste Records</td>
<td>Verify a user can find historical waste transactions by reference, date, waste type, premise and status.</td>
<td>WST-REF-01 and at least one disposed record exist.</td>
<td>1. Open Waste Management &gt; Waste Record.<br>2. Search the waste reference from the E2E generation.<br>3. Filter premise = UAT Premise A, waste type = SW410, date from/to covering 15/09/2026.<br>4. Filter type Produced, then Disposed.<br>5. Filter status Final.<br>6. Open (View) the produced row and the disposed row.</td>
<td>WST-REF-01; UAT Premise A; SW410; period covering the test dates.</td>
<td>Search returns the matching row(s). Filters reduce the list to matching premise, SW code, dates, type and status. View shows the same reference, dates, quantities and, for the lifecycle pair, the linked generation/disposal panel. Final produced and final disposed rows are both listed. Cancelled rows do not appear as Final.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Waste Record is the list. The Fifth Schedule form is the view/edit page and is not in the sidebar.</td>
</tr>
<tr>
<td>WST-UAT-018</td>
<td>M01 Waste Management</td>
<td>Opening Balance</td>
<td>Verify a Waste Officer / Administrator can record the starting quantity held at a premise so later generation and disposal can be reconciled.</td>
<td>Logged in as Waste Officer or Administrator. Page is reached via URL p_waste_opening_balance (not in the sidebar). Prefer a waste type reserved for the controlled balance case, or perform this BEFORE other SW410 movements on a clean UAT premise.</td>
<td>1. Open p_waste_opening_balance.<br>2. Add / save opening balance.<br>3. Select UAT Premise A, SW410, as-at date 31/08/2026, quantity 100, unit kg.<br>4. Save.<br>5. Refresh the opening-balance list.<br>6. Open Waste Dashboard for September 2026, same premise and SW410, and read Opening Balance.</td>
<td>UAT-OB-01: Premise UAT Premise A; SW410; as-at 31/08/2026; 100.000 kg.</td>
<td>Save succeeds. List shows 100.000 kg as at 31/08/2026. Dashboard Opening Balance for September 2026 = 100.000 kg (if no Final movements after 31/08/2026 and before 01/09/2026). Opening is not listed as Produced.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>GAP-WST-01: Opening Balance is hidden from the sidebar. Zero is allowed; negative is not. One opening row per premise + SW code (save updates the same row).</td>
</tr>
<tr>
<td>WST-UAT-019</td>
<td>M01 Waste Management</td>
<td>Waste Balance</td>
<td>Verify current scheduled-waste balance equals Opening + Generated − Disposed using a controlled dataset.</td>
<td>Clean UAT Premise A + SW410 for the period, or tester records any extra Final SW410 movements and includes them. Recommended controlled set: Opening 100.000 kg (WST-UAT-018); Generated 50.000 kg (WST-REF-01); Disposed actual 30.000 kg (WST-UAT-014). WST-REF-02 and other extras must be cancelled or excluded from this SW code.</td>
<td>1. Confirm opening 100.000 kg as at 31/08/2026.<br>2. Confirm one Final produced 50.000 kg in September.<br>3. Confirm one Final disposed 30.000 kg in September.<br>4. Manually calculate 100 + 50 − 30 = 120.<br>5. Open Waste Dashboard, month September 2026, premise UAT Premise A, SW410.<br>6. Read Opening, Produced, Disposed, Current Balance.<br>7. Open the balance table row for that premise + SW410.</td>
<td>Opening 100.000 kg. Produced 50.000 kg. Disposed 30.000 kg. Expected closing / current = 120.000 kg. Net movement = 20.000 kg.</td>
<td>Dashboard Opening = 100.000 kg. Produced = 50.000 kg. Disposed = 30.000 kg. Net Movement = 20.000 kg. Current Balance / Closing = 120.000 kg. Balance table Opening + Produced − Disposed = Closing. Cancelled rows (if any) are excluded. Units kg; 120.000 kg = 0.120 MT if MT is also shown.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Formula (implemented): Current = Opening kg + sum(Final Produced kg) − sum(Final Disposed kg), with opening applied as the starting point and only Final rows after the opening as-at date. Draft and Cancelled are excluded. If extra rows exist, replace 120 with the testers' own controlled arithmetic and attach the working.</td>
</tr>
<tr>
<td>WST-UAT-020</td>
<td>M01 Waste Management</td>
<td>Waste Dashboard</td>
<td>Verify every dashboard figure for the controlled period can be traced to the underlying waste transactions.</td>
<td>Same controlled September 2026 / UAT Premise A / SW410 dataset as WST-UAT-019. Other premises may exist — filter to the UAT premise.</td>
<td>1. Open Waste Dashboard.<br>2. Set Month = September, Year = 2026, Premise = UAT Premise A, Waste type = SW410.<br>3. Copy Opening, Produced, Disposed, Net Movement, Current Balance, Final Transactions, Pending Disposal kg.<br>4. Open Waste Records with the same premise, SW code and September dates, Status Final — sum Produced and Disposed kg.<br>5. Open Pending Disposal Status = Pending for that premise/SW — sum registered kg.<br>6. Click the Pending Disposal KPI and confirm it opens the pending page.<br>7. Review Produced/Disposed/Pending/Closing by SW and by premise tables.</td>
<td>Expected (isolated SW410): Opening 100.000; Produced 50.000; Disposed 30.000; Net 20.000; Current 120.000; Final Transactions = 2 (one Produced + one Disposed) if no other Final SW410 rows; Pending Disposal kg = 0 after WST-UAT-014.</td>
<td>Each KPI equals the independently summed source records for the same filter. Pending Disposal kg is the current pending collection total (not limited to the selected month — if this differs from 0 because of other pending rows, record the actual pending list). Click-through opens Waste Records or Pending Disposal with the filter applied. Presence of a number without reconciliation is a fail.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Pending Disposal kg and pending-by-SW are current outstanding, not period-bound. Draft count on the dashboard is all drafts, not period-bound. Record as observation if that surprises the business (GAP-WST-03).</td>
</tr>
<tr>
<td>WST-UAT-021</td>
<td>M01 Waste Management</td>
<td>JKR Reporting</td>
<td>Verify a Waste Officer / Administrator can generate a JKR scheduled-waste report version for a premise and period and open the PDF and Excel files.</td>
<td>Logged in as Waste Officer or Administrator. Page via URL p_waste_report (not in the sidebar). Controlled September data exists.</td>
<td>1. Open p_waste_report.<br>2. Click Generate.<br>3. Select UAT Premise A, Period from 01/09/2026, Period to/As-at 30/09/2026, include transaction appendix.<br>4. Preview.<br>5. Generate version.<br>6. Open PDF and Open Excel from the version list.</td>
<td>Premise UAT Premise A; 01/09/2026–30/09/2026; appendix on.</td>
<td>Preview shows a summary table. Generate creates a new version number. Success: 'JKR report version generated. Historical versions are unchanged.' PDF title reflects JKR / GEMS Scheduled Waste Register. Excel and PDF open. Earlier versions remain listed.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>GAP-WST-01: JKR Reports is hidden from the sidebar.</td>
</tr>
<tr>
<td>WST-UAT-022</td>
<td>M01 Waste Management</td>
<td>JKR Reporting</td>
<td>Verify JKR report opening, produced, disposed and closing quantities match the official Final transactions for the same premise and period.</td>
<td>WST-UAT-021 version generated. WST-UAT-019 expected figures known.</td>
<td>1. Open the generated PDF/Excel.<br>2. Locate the SW410 (or UAT waste type) summary row.<br>3. Compare Opening, Produced, Disposed, Closing with the dashboard and with the testers' arithmetic.<br>4. If appendix is included, confirm the 50.000 kg produced and 30.000 kg disposed lines appear.<br>5. Confirm Excel note Opening + Produced − Disposed = Closing holds on the SW410 row.</td>
<td>Expected SW410: Opening 100.000 kg; Produced 50.000 kg; Disposed 30.000 kg; Closing 120.000 kg.</td>
<td>Report SW410 totals equal the dashboard and the manual 100 + 50 − 30 = 120 working. Appendix lines match the Final transactions. Cancelled rows are absent. Closing = Opening + Produced − Disposed.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Official totals use Final records only.</td>
</tr>
<tr>
<td>WST-UAT-023</td>
<td>M01 Waste Management</td>
<td>JKR Reporting</td>
<td>Verify JKR submission details can be recorded against a generated report version for customer/JKR acknowledgement.</td>
<td>A JKR report version exists (WST-UAT-021). User is Waste Officer or Administrator.</td>
<td>1. On p_waste_report, select the UAT version.<br>2. Record JKR Submission.<br>3. Enter submission date today, Submitted To 'JKR UAT', channel if listed, reference 'JKR-UAT-SUB-01', notes 'UAT submission'.<br>4. Save (acknowledgement file optional).<br>5. Re-open the version.</td>
<td>Submitted To: JKR UAT. Reference: JKR-UAT-SUB-01. Date: today.</td>
<td>Save succeeds. Message: 'JKR submission details recorded.' The version shows the submission date, submitted-to, channel and reference. The report files themselves are unchanged.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Submission is a register of the filing, not a workflow approval.</td>
</tr>
<tr>
<td>WST-UAT-024</td>
<td>M01 Waste Management</td>
<td>End-to-end</td>
<td>Verify one scheduled-waste transaction can be followed from generation through pending collection, disposal, records, balance, dashboard and JKR report.</td>
<td>UAT Premise A + SW410 reserved. Opening 100.000 kg as at 31/08/2026 already saved. No extra Final SW410 movements in September (cancel extras first). Two disposal images ready.</td>
<td>1. Generate 50.000 kg on 15/09/2026 (or today), remarks 'UAT E2E WST-REF-01'. Capture reference.<br>2. Open Pending Disposal — confirm Pending Collection, 50.000 kg, same premise and SW410.<br>3. Execute disposal: both images, date on/after generation, actual 30.000 kg.<br>4. Confirm row is Disposed and not pending.<br>5. Open Waste Records — find produced 50.000 and disposed 30.000, linked.<br>6. Dashboard September 2026: Opening 100, Produced 50, Disposed 30, Current 120.<br>7. Generate JKR report 01/09/2026–30/09/2026 and confirm the same four figures for SW410.</td>
<td>Single chain: Opening 100 kg; WST-REF-01 generated 50 kg; disposed 30 kg; expected current 120 kg.</td>
<td>Every stage shows the same reference/premise/SW code. Status moves Pending Collection → Disposed. Balance and JKR closing both equal 120.000 kg. No approval step is required. Customer can sign this chain as the official M01 acceptance path.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Use the same physical transaction throughout. If dates were shifted because 15/09/2026 was in the future, keep that same date in the JKR period.</td>
</tr>
<tr>
<td>WST-UAT-025</td>
<td>M01 Waste Management</td>
<td>Pending Disposal</td>
<td>Verify Pending Disposal explains when nothing is waiting for collection.</td>
<td>Filter Pending Disposal to a premise with no pending rows (or Status Pending + a future date range).</td>
<td>1. Open Pending Disposal.<br>2. Select a premise/date/status combination with no pending rows.<br>3. Read the list and the waste-type summary.</td>
<td>UAT premise with zero pending, or dates that exclude all rows.</td>
<td>List shows 'No pending disposal records.' Summary shows 'Nothing is pending disposal'. Pending Records = 0. Pending Weight = 0.000 kg. Page does not retain a previous premise's totals.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Low</td>
<td></td>
</tr>
<tr>
<td>WST-UAT-026</td>
<td>M01 Waste Management</td>
<td>Waste Balance</td>
<td>Verify a cancelled pending generation is excluded from official dashboard and JKR totals.</td>
<td>WST-UAT-009 produced a cancelled row of 8.000 kg. Controlled SW410 figures otherwise known.</td>
<td>1. Confirm the cancelled reference is not Status Final on Waste Records.<br>2. Open Dashboard for the period and confirm Produced does not include the 8.000 kg.<br>3. Confirm JKR produced total also excludes it.</td>
<td>Cancelled 8.000 kg reference from WST-GEN-04.</td>
<td>Produced, Disposed, Current Balance and JKR produced do not include the cancelled 8.000 kg.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Official totals = Final only.</td>
</tr>
<tr>
<td>WST-UAT-027</td>
<td>M01 Waste Management</td>
<td>Access / Role</td>
<td>Verify Waste User can record generation and disposal but cannot maintain opening balance or generate JKR reports, while Waste Officer can.</td>
<td>Two accounts: Waste User (role 28) and Waste Officer (role 29), both assigned to UAT Premise A. Do not test the general user-admin screen — only the waste pages.</td>
<td>1. Login as Waste User.<br>2. Confirm sidebar Waste Management is visible.<br>3. Create a small generation (1.000 kg) and confirm it is allowed.<br>4. Open p_waste_opening_balance and p_waste_report and attempt to save / generate.<br>5. Logout. Login as Waste Officer.<br>6. Confirm opening balance and JKR generate are allowed.</td>
<td>UAT-WASTE-USER; UAT-WASTE-OFFICER (TBC — actual usernames).</td>
<td>Waste User: can generate and dispose; opening/JKR save is denied (capability message). Waste Officer: can generate/dispose and can save opening balance and generate JKR. Neither role sees a separate approval inbox.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Menu SQL grants Waste User and Waste Officer the operational waste pages. Accounts TBC. If a role still has no menu, record GAP-WST-02.</td>
</tr>
<tr>
<td>WST-UAT-028</td>
<td>M01 Waste Management</td>
<td>Access / Role</td>
<td>Verify a user without a waste role does not see Waste Management and cannot create official waste records.</td>
<td>A user with no roles 1, 10, 28, 29 (for example PI Entry only).</td>
<td>1. Login as that user.<br>2. Confirm Waste Management is absent from the sidebar.<br>3. Open p_waste_generation directly and attempt Save.</td>
<td>UAT-NOACCESS or UAT-PI-ENTRY.</td>
<td>No waste menu. Save rejected or page blocked. No new Final waste row.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Overlaps COM-UAT-007; execute once and cross-reference if preferred.</td>
</tr>
<tr>
<td>WST-UAT-029</td>
<td>M01 Waste Management</td>
<td>Waste Records</td>
<td>Verify opening a lifecycle record from Waste Records shows the generation details and the linked disposal (weight, date, evidence) together.</td>
<td>WST-REF-01 already disposed (30.000 kg).</td>
<td>1. Open Waste Record.<br>2. Search WST-REF-01.<br>3. View the produced row.<br>4. Confirm the linked disposal panel shows 30.000 kg and disposal date.<br>5. View the disposed row and confirm it points back to the same generation.</td>
<td>WST-REF-01 / linked disposal.</td>
<td>View page (Fifth Schedule layout) opens. Linked panel shows the pair. Registered 50.000 kg and actual disposed 30.000 kg are both visible. Documents/images can be opened.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>The Fifth Schedule form is also used to key REGISTER records; this case only verifies viewing the V2 lifecycle pair.</td>
</tr>
<tr>
<td>WST-UAT-030</td>
<td>M01 Waste Management</td>
<td>Opening Balance</td>
<td>Verify a negative opening quantity is rejected and a zero opening quantity is accepted.</td>
<td>Waste Officer or Administrator on p_waste_opening_balance. Use a spare SW code (for example SW305) so the 100 kg SW410 opening is not overwritten.</td>
<td>1. Attempt opening quantity -1 kg for UAT Premise A + SW305. Save.<br>2. Save opening 0 kg for the same premise + SW305, as-at 31/08/2026.</td>
<td>SW305; -1 kg then 0 kg.</td>
<td>Negative rejected: 'Opening quantity must be zero or greater.' Zero saves successfully.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Opening upserts per premise + SW code — do not overwrite SW410 100 kg.</td>
</tr>
<tr>
<td>WST-UAT-031</td>
<td>M01 Waste Management</td>
<td>Pending Disposal</td>
<td>Verify a user can narrow Pending Disposal by premise, waste type, status and date and can search by waste reference.</td>
<td>Several pending/disposed rows exist across dates.</td>
<td>1. Open Pending Disposal.<br>2. Filter premise = UAT Premise A.<br>3. Filter waste type = SW410.<br>4. Set Status Pending, then Disposed, then All.<br>5. Set From/To around the E2E date.<br>6. Search WST-REF-01.</td>
<td>WST-REF-01; UAT Premise A; SW410.</td>
<td>Each filter reduces the list correctly. Search finds WST-REF-01. Status Pending hides disposed rows. Status Disposed hides pending rows. Date range excludes rows outside it.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Default status filter is Pending.</td>
</tr>
<tr>
<td>WST-UAT-032</td>
<td>M01 Waste Management</td>
<td>Waste Dashboard</td>
<td>Verify changing the dashboard month/year (including a month with no movements) changes the official period figures and does not reuse the previous month's totals.</td>
<td>September 2026 controlled figures known. August 2026 should have no UAT SW410 movements after opening as-at 31/08/2026.</td>
<td>1. Open Dashboard, September 2026, UAT Premise A, SW410 — note Produced/Disposed/Current.<br>2. Change to August 2026.<br>3. Change to October 2026.<br>4. Return to September 2026 and confirm the original figures reappear.</td>
<td>August / September / October 2026.</td>
<td>August 2026 Produced and Disposed for SW410 = 0.000 if no August Final rows; Opening/Current reflect opening 100.000 kg only. October Produced/Disposed = 0.000; Current remains 120.000 kg if no later movements. September figures return to 100 / 50 / 30 / 120.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Year-end: if testing December→January, Current must carry forward; Produced resets for the new month.</td>
</tr>
</tbody></table>


---

## 7. M02 — KPI & APD

<table>
<thead><tr>
<th>UAT ID</th>
<th>Module</th>
<th>Function / Submodule</th>
<th>Test Scenario</th>
<th>Preconditions</th>
<th>Test Steps</th>
<th>Test Data</th>
<th>Expected Result</th>
<th>Actual Result</th>
<th>Status</th>
<th>Severity</th>
<th>Remarks</th>
</tr></thead><tbody>
<tr>
<td>KPA-UAT-001</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Structure</td>
<td>Verify a KPI Admin can open the shared KPI structure and see the four seeded groups, 21 performance indicators and a 100% weightage total.</td>
<td>Logged in as KPI Admin (role 30) or Administrator. Dedicated UAT site selected. Shared KPI template (4 groups / 21 PIs) is active.</td>
<td>1. Login as KPI Admin.<br>2. Open KPI &amp; APD &gt; KPI Structure.<br>3. Select the shared template / UAT site that uses the global template.<br>4. Count KPI groups and indicators.<br>5. Read the active weightage message.</td>
<td>Expected groups: 1 FMM Service Delivery (61%); 2 Asset Performance (20%); 3 Building Energy Efficiency (10%); 4 Safety &amp; Statutory Compliance (9%). 21 PIs. Weightage 100%.</td>
<td>Page title KPI Structure. Four active groups and 21 active PIs are listed with PI number, name, target, weightage and formula/calc type. Weightage message: 'Active weightage totals 100%.' Maximum APD (%) default is 5.00 unless previously changed.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Do not deactivate seeded PIs on a shared template used by other sites. Prefer a site-specific copy if the project has created one for UAT.</td>
</tr>
<tr>
<td>KPA-UAT-002</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Structure</td>
<td>Verify a KPI Admin can add and later edit a KPI group on a UAT site-specific structure without damaging the shared template.</td>
<td>KPI Admin. Prefer creating site-specific groups on UAT Site A only (first group on a site switches that site off the shared template). If UAT must stay on the shared template, skip create and only edit a UAT-named group previously prepared — or create and then deactivate at the end of UAT.</td>
<td>1. Open KPI Structure for UAT Site A.<br>2. Add group number UAT, name 'UAT Group', sort order 99, Active.<br>3. Save.<br>4. Edit the name to 'UAT Group Revised'. Save.<br>5. Confirm the shared template (other site) still has the original four groups only.</td>
<td>Group no UAT; name UAT Group then UAT Group Revised.</td>
<td>First save: 'KPI group saved.' Group appears. Duplicate group number is rejected: 'KPI group UAT already exists.' Edit updates the name. Other sites still using the shared template are unchanged.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Creating the first site-specific group makes that site stop using the shared 21-PI template. Do this only on a dedicated UAT site. TBC with business whether UAT uses shared template or a site copy.</td>
</tr>
<tr>
<td>KPA-UAT-003</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Structure</td>
<td>Verify a KPI Admin can open a seeded Performance Indicator, view its parameters and dry-run the formula with sample values.</td>
<td>Logged in as KPI Admin (role 30) or Administrator. Dedicated UAT site selected. Shared KPI template (4 groups / 21 PIs) is active. Use the shared template in read/test mode (do not change seeded formulas).</td>
<td>1. Open KPI Structure.<br>2. Open PI 1A Customer Satisfaction Survey rating.<br>3. Confirm target 80%, weightage 5%, formula (p1/p2)*100, parameters p1 and p2.<br>4. Use Test formula with p1=80, p2=100.<br>5. Close without saving changes.</td>
<td>PI 1A; test p1=80, p2=100; expected 80.</td>
<td>PI fields match the seeded definition. Test formula returns 80 (or 80.0000). No change is written if the tester cancels/closes without save.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>EXPRESSION parser supports pN, + − * /, min(), max() only.</td>
</tr>
<tr>
<td>KPA-UAT-004</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Structure</td>
<td>Verify KPI group and PI definition cannot be saved without the required business identifiers.</td>
<td>Logged in as KPI Admin (role 30) or Administrator. Dedicated UAT site selected. Shared KPI template (4 groups / 21 PIs) is active.</td>
<td>1. Open Add Group with number and name blank. Save.<br>2. Open Add / Edit PI with PI number and name blank. Save.<br>3. Set weightage 150 on a PI (or a UAT PI). Save.</td>
<td>Blank group; blank PI; weightage 150.</td>
<td>Group blocked: 'Enter the KPI group number and name.' PI blocked: 'Enter the PI number and name.' Weightage blocked: 'The weightage must be between 0 and 100.'</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Do not save invalid changes to seeded PIs.</td>
</tr>
<tr>
<td>KPA-UAT-005</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Structure</td>
<td>Verify the structure screen warns when active PI weightage does not total 100%, because APD exposures would no longer add up to the APD maximum.</td>
<td>KPI Admin on a dedicated UAT site structure, or temporarily change a UAT-only PI weightage and restore it afterwards. Do not leave the shared template unbalanced.</td>
<td>1. Note the current weightage message (should be 100%).<br>2. On a UAT-only PI (or a temporary edit that will be reversed), change weightage so the active total is not 100% (for example 5 → 6).<br>3. Save and read the banner.<br>4. Restore the original weightage.</td>
<td>Unbalanced total e.g. 101%.</td>
<td>Warning: 'Active weightage totals {X}%. APD exposure will not add up to the maximum until this is 100%.' System still allows the save (warning, not hard stop). After restore, message returns to 'Active weightage totals 100%.'</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>GAP-KPA-02: unbalanced weightage is a warning only.</td>
</tr>
<tr>
<td>KPA-UAT-006</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Structure</td>
<td>Verify a KPI Admin can deactivate a UAT-only Performance Indicator so it is no longer copied into a newly created month.</td>
<td>A UAT-only PI exists on UAT Site A (created in KPA-UAT-002/003). Do not deactivate seeded 1A–4C on the shared template.</td>
<td>1. On KPI Structure, deactivate the UAT-only PI.<br>2. Confirm prompt: 'Deactivate this Performance Indicator?'<br>3. Create (or inspect) a new month after deactivation and confirm that PI is absent from the snapshot.<br>4. Confirm an already-created earlier month still shows the PI if it was snapshotted while active.</td>
<td>UAT-only PI.</td>
<td>PI status becomes Inactive. Success: 'Performance Indicator deactivated.' New months omit it. Existing snapshotted months keep the copy they were created with.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Deactivate is a status change, not a hard delete.</td>
</tr>
<tr>
<td>KPA-UAT-007</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Configuration</td>
<td>Verify a KPI Admin can set the site Maximum APD percentage used when a new evaluation month is created.</td>
<td>Logged in as KPI Admin (role 30) or Administrator. Dedicated UAT site selected. Shared KPI template (4 groups / 21 PIs) is active. Dedicated UAT site.</td>
<td>1. Open KPI Structure for UAT Site A.<br>2. Set Maximum APD (%) to 5.00 if not already.<br>3. Save.<br>4. Attempt 101 and -1 to confirm the range, then restore 5.00.</td>
<td>Valid 5.00%. Invalid 101 and -1.</td>
<td>Valid save: 'KPI configuration saved.' Invalid: 'The maximum APD percentage must be between 0 and 100.' New months default to 5.00% (shown on Create Month as Max APD % and used in APD Maximum = MPV × 5%).</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Per-month Max APD % can later be edited via Edit MPV (KPA-UAT-035).</td>
</tr>
<tr>
<td>KPA-UAT-008</td>
<td>M02 KPI &amp; APD</td>
<td>PI Assignment</td>
<td>Verify a KPI Admin can assign selected Performance Indicators to a PI Entry user for the UAT site.</td>
<td>KPI Admin. At least one active user holds the PI Entry role (31) for UAT Site A. Do not open general User Management except to confirm the user already exists.</td>
<td>1. Open KPI &amp; APD &gt; PI Assignment.<br>2. Select UAT Site A.<br>3. Select PI 1A, 1E, 3B and 4C (the calculation set).<br>4. Select UAT-PI-ENTRY user.<br>5. Save.</td>
<td>Site UAT Site A. PIs 1A, 1E, 3B, 4C. User UAT-PI-ENTRY (TBC).</td>
<td>Save succeeds: 'PI assignment saved.' Assignment list shows each PI + user + site as active. Assignable-user list contains only PI Entry role users.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>If the list says 'No user holds the PI Entry role yet', stop and request the role on the existing UAT account. Do not treat User Management as an M02 test object.</td>
</tr>
<tr>
<td>KPA-UAT-009</td>
<td>M02 KPI &amp; APD</td>
<td>PI Assignment</td>
<td>Verify assigning the same PI to the same user again does not create a duplicate active assignment row.</td>
<td>KPA-UAT-008 completed.</td>
<td>1. Assign PI 1A to UAT-PI-ENTRY again on UAT Site A.<br>2. Save.<br>3. Count 1A assignment rows for that user.</td>
<td>Duplicate of PI 1A / UAT-PI-ENTRY / UAT Site A.</td>
<td>Still one active assignment for that site + PI + user. Implementation reactivates the same assignment rather than inserting a second active row.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td></td>
</tr>
<tr>
<td>KPA-UAT-010</td>
<td>M02 KPI &amp; APD</td>
<td>PI Assignment</td>
<td>Verify a KPI Admin can remove a PI assignment so that PI Entry user can no longer submit that indicator.</td>
<td>An extra assignment exists (assign PI 2A temporarily), or use a spare PI.</td>
<td>1. On PI Assignment, remove the spare PI assignment.<br>2. Login as PI Entry.<br>3. Open that PI for the UAT month and confirm it is read-only / not assigned.</td>
<td>Spare PI (e.g. 2A) assigned then removed.</td>
<td>Remove success: 'PI assignment removed.' PI Entry sees 'This indicator is not assigned to you.' and cannot Save/Submit that PI. Other assigned PIs remain editable.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Remove is a deactivation (status 2), not a hard delete.</td>
</tr>
<tr>
<td>KPA-UAT-011</td>
<td>M02 KPI &amp; APD</td>
<td>Monthly Evaluation</td>
<td>Verify a KPI Admin can create the September 2026 monthly evaluation, snapshotting the KPI structure and calculating APD maximum from MPV.</td>
<td>Logged in as KPI Admin (role 30) or Administrator. Dedicated UAT site selected. Shared KPI template (4 groups / 21 PIs) is active. September 2026 must not already exist for UAT Site A (use October 2026 if September was created in a dry run — then keep that month as the official UAT month).</td>
<td>1. Open Monthly Evaluation.<br>2. Click Create Month.<br>3. Select UAT Site A, Year 2026, Month September.<br>4. Enter MPV 4147120.556.<br>5. Confirm Max APD % preview is 5.00 and APD Maximum preview is 207,356.03.<br>6. Remarks 'UAT September 2026'.<br>7. Save.<br>8. Open the new month.</td>
<td>MPV 4,147,120.556. Max APD 5.00%. Expected APD Maximum RM 207,356.03. Period September 2026.</td>
<td>Success: 'Monthly KPI evaluation created from the KPI template.' Month status = Open. List shows MPV 4,147,120.556, Max APD 5.00%, APD Maximum 207,356.03, Demerit 0, APD Deducted 0, progress 0 / 21 (or 0 / number of active PIs snapshotted). Indicator grid lists the snapshotted PIs, each Draft.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>apdMax = round(MPV × maxApdPct / 100, 2) = round(207356.0278, 2) = 207,356.03. There is no approval step.</td>
</tr>
<tr>
<td>KPA-UAT-012</td>
<td>M02 KPI &amp; APD</td>
<td>Monthly Evaluation</td>
<td>Verify a second evaluation cannot be created for the same site and month.</td>
<td>September 2026 already exists for UAT Site A.</td>
<td>1. Click Create Month again.<br>2. Select the same site, year and month.<br>3. Enter any MPV. Save.</td>
<td>Duplicate September 2026 / UAT Site A.</td>
<td>Save rejected: 'A KPI evaluation for September 2026 already exists.' Only one month row remains.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td></td>
</tr>
<tr>
<td>KPA-UAT-013</td>
<td>M02 KPI &amp; APD</td>
<td>Monthly Evaluation</td>
<td>Verify Monthly Payment Value cannot be negative when creating or editing a month.</td>
<td>KPI Admin. Use Create Month for a spare unused month (e.g. January 2025) or Edit MPV on a disposable month — do not corrupt September 2026.</td>
<td>1. Open Create Month for a spare period.<br>2. Enter MPV -1. Save.<br>3. Cancel without creating a valid spare month, or delete/ignore if created.</td>
<td>MPV = -1.</td>
<td>Rejected: 'The Monthly Payment Value cannot be negative.' September 2026 MPV remains 4,147,120.556.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td></td>
</tr>
<tr>
<td>KPA-UAT-014</td>
<td>M02 KPI &amp; APD</td>
<td>PI Entry</td>
<td>Verify an authorised PI Entry user can open the UAT month and enter values only for the Performance Indicators assigned to them.</td>
<td>Logged in as PI Entry (role 31) assigned to UAT PI set for UAT Site A. September 2026 evaluation already exists for that site. Assignments from KPA-UAT-008 in place.</td>
<td>1. Login as UAT-PI-ENTRY.<br>2. Confirm KPI Structure and PI Assignment are not in the sidebar.<br>3. Open Monthly Evaluation and open September 2026.<br>4. Open PI 1A (assigned) and confirm fields are editable.<br>5. Open an unassigned PI (e.g. 2A) and confirm it is read-only.</td>
<td>UAT-PI-ENTRY; assigned 1A, 1E, 3B, 4C.</td>
<td>PI Entry can open Summary, Monthly Evaluation and History. Structure/Assignment are not in the menu. Assigned PI shows editable parameter fields. Unassigned PI shows 'This indicator is not assigned to you.' and Save/Submit are not available. Grid may mark unassigned Draft PIs with the not-assigned icon.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>PI Entry can see the other PIs in the month grid; they cannot edit them.</td>
</tr>
<tr>
<td>KPA-UAT-015</td>
<td>M02 KPI &amp; APD</td>
<td>PI Entry</td>
<td>Verify a PI Entry user is prevented from submitting an indicator that is not assigned to them even if they open the URL.</td>
<td>Unassigned eval_pi id known from the September grid (or copied from the browser URL of an unassigned PI opened as Admin).</td>
<td>1. As PI Entry, open p_kpa_pi_entry?id= of an unassigned PI.<br>2. Attempt to type a value and Save or Submit.</td>
<td>Unassigned eval_pi URL.</td>
<td>Save/Submit blocked. Message: 'This Performance Indicator is not assigned to you.' Values are not changed.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td></td>
</tr>
<tr>
<td>KPA-UAT-016</td>
<td>M02 KPI &amp; APD</td>
<td>Access / Role</td>
<td>Verify a PI Entry user cannot change KPI structure, formulas, parameters or PI assignments.</td>
<td>Logged in as UAT-PI-ENTRY.</td>
<td>1. Confirm KPI Structure and PI Assignment are absent from the sidebar.<br>2. Paste p_kpa_structure and p_kpa_assignment.<br>3. Attempt to save a group, a PI, or an assignment.</td>
<td>UAT-PI-ENTRY.</td>
<td>Pages are hidden. Direct URL shows a not-allowed state or save returns 'You are not allowed to change the KPI structure.' No structure/assignment change is stored.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td></td>
</tr>
<tr>
<td>KPA-UAT-017</td>
<td>M02 KPI &amp; APD</td>
<td>PI Entry</td>
<td>Verify a PI Entry user can capture parameter values for an assigned PI and save them as draft without locking the indicator.</td>
<td>Logged in as PI Entry (role 31) assigned to UAT PI set for UAT Site A. September 2026 evaluation already exists for that site.</td>
<td>1. Open September 2026 &gt; PI 1A.<br>2. Enter p1 Total Rating Marks Obtained = 88.<br>3. Enter p2 Total Possible Marks = 100.<br>4. Click Save Draft (or allow autosave and click Save Draft).<br>5. Leave the page and re-open PI 1A.</td>
<td>Dataset A / PI 1A: p1=88, p2=100.</td>
<td>Values remain 88 and 100. Achievement shows 88.0000%. Target 80%. Result = Target met. APD Deducted = 0.00. PI status remains Draft. Month remains Open.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Autosave may already store the numbers before Save Draft. Submit is a separate action (KPA-UAT-020).</td>
</tr>
<tr>
<td>KPA-UAT-018</td>
<td>M02 KPI &amp; APD</td>
<td>PI Entry</td>
<td>Verify a PI cannot be submitted while a required parameter is blank.</td>
<td>PI 1A still Draft. Tester can temporarily clear p2.</td>
<td>1. Open PI 1A.<br>2. Clear p2.<br>3. Click Submit and confirm the prompt if shown.</td>
<td>p1=88; p2 blank.</td>
<td>Submit is rejected. Message: 'Enter a value for "Total Possible Marks" before submitting.' (label as shown). Status remains Draft.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Restore p2=100 after the test.</td>
</tr>
<tr>
<td>KPA-UAT-019</td>
<td>M02 KPI &amp; APD</td>
<td>PI Entry</td>
<td>Verify division by zero does not produce a fake score and blocks submit.</td>
<td>PI 1A Draft.</td>
<td>1. Set p1=88, p2=0.<br>2. Observe achievement.<br>3. Attempt Submit.<br>4. Restore p2=100.</td>
<td>p1=88; p2=0.</td>
<td>Achievement is not a number. Message: 'The achievement cannot be calculated yet. Check that every parameter is filled in and that no divisor is zero.' Submit blocked with that message or 'The achievement cannot be calculated. Review the parameter values.' No Target met / Not met score is stored as 0% unless the calculator truly produced 0.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>A 0% pass/fail result is not acceptable here — the result must be Not calculated.</td>
</tr>
<tr>
<td>KPA-UAT-020</td>
<td>M02 KPI &amp; APD</td>
<td>PI Entry</td>
<td>Verify submitting a valid PI locks the values so the PI Entry user cannot change them.</td>
<td>PI 1A Draft with p1=88, p2=100 (Dataset A).</td>
<td>1. Open PI 1A.<br>2. Click Submit.<br>3. Confirm: 'Submit PI 1A? The values are locked once submitted.'<br>4. Attempt to change p1.<br>5. Attempt Save Draft.</td>
<td>PI 1A Dataset A.</td>
<td>Success: 'PI submitted. The values are now locked.' Status = Submitted. Fields disabled. Further save returns 'This PI has been submitted. Ask a KPI Admin to reopen it before changing the values.'</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>No approval step. Submit is the lock.</td>
</tr>
<tr>
<td>KPA-UAT-021</td>
<td>M02 KPI &amp; APD</td>
<td>Monthly Evaluation</td>
<td>Verify only a KPI Admin (or Administrator) can reopen a submitted PI, and that PI Entry cannot.</td>
<td>PI 1A Submitted. Have both UAT-PI-ENTRY and KPI Admin sessions.</td>
<td>1. As PI Entry, open submitted PI 1A and confirm Reopen is not available / not allowed.<br>2. Login as KPI Admin.<br>3. Open PI 1A and Reopen. Confirm the prompt.<br>4. Confirm status returns to Draft and the month is Open.<br>5. Re-enter 88 / 100 and Submit again so Dataset A remains locked for later reconciliation.</td>
<td>PI 1A.</td>
<td>PI Entry cannot reopen. KPI Admin reopen succeeds: 'PI reopened for editing.' Status Draft; month Open. After re-submit, values 88 / 100 and achievement 88.0000% are locked again.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Reopen of a non-submitted PI: 'Only a submitted PI can be reopened.'</td>
</tr>
<tr>
<td>KPA-UAT-022</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Calculation</td>
<td>Verify Dataset A (normal / meeting target) — PI 1A, 1E and 4C calculate achievement, pass and APD exactly as the engine defines.</td>
<td>September 2026, MPV 4,147,120.556, max APD 5%, APD Maximum 207,356.03. Tester is KPI Admin or the assigned PI Entry.</td>
<td>1. Open PI 1A. Enter p1=88, p2=100. Save. Record Target, Achievement, APD Exposure, APD Deducted, pass text.<br>2. Open PI 1E. Enter p1=10, p2=10, p3=5, p4=5, p5=0, p6=0. Save. Record results.<br>3. Open PI 4C. Enter p1=100, p2=100. Save. Record results.<br>4. Compare with the manual working in Test Data.</td>
<td>APD Maximum RM 207,356.03.<br><br>PI 1A EXPRESSION (p1/p2)*100: 88/100*100 = 88.0000%. Target 80, GTE → PASS. Weight 5%. APD exposure = round(207356.03*5/100,2) = 10,367.80. Deducted 0.00. Demerit 0.<br><br>PI 1E BACKLOG_AVG: buckets (10/10)=100, (5/5)=100, (0/0 treated as no backlog)=100. Mean 100.0000%. Target 100 → PASS. Exposure 10,367.80. Deducted 0.00.<br><br>PI 4C AVG_PARAMS: mean(100,100)=100.0000%. Target 100 → PASS. Weight 3%. Exposure = round(207356.03*3/100,2) = 6,220.68. Deducted 0.00.</td>
<td>System achievement, pass text (Target met), APD exposure and APD deducted match the manual working to 4 decimal places on achievement and 2 decimal places on RM. Month totals do not include APD for these three PIs.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Achievement is rounded to 4 decimal places. APD is rounded to 2 decimal places. A bucket with total 0 on PI 1E scores 100%.</td>
</tr>
<tr>
<td>KPA-UAT-023</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Calculation</td>
<td>Verify Dataset B (boundary / exactly on target) — PI 1A at 80%, PI 3B BEI exactly 170.6500, and PI 3C with zero wastage findings.</td>
<td>Same September 2026 month. Prefer a second month (e.g. August 2026, same MPV) if Dataset A values must stay frozen — otherwise temporarily edit then restore Dataset A. Recommended: create August 2026 as the boundary month.</td>
<td>1. Create or open August 2026 with the same MPV / 5% if September is reserved for Dataset A/C.<br>2. PI 1A: p1=80, p2=100. Save.<br>3. PI 3B: p1=142208.3333, p2=0, p3=10000, p4=12. Save.<br>4. PI 3C: p1=0. Save.<br>5. Compare with the manual working.</td>
<td>Boundary month (recommended August 2026), same APD Maximum 207,356.03.<br><br>PI 1A: 80/100*100 = 80.0000% = target 80 → PASS (GTE).<br><br>PI 3B BEI: (142208.3333+0)/10000 × 12 = 170.6500. Target 170.6500, LTE → PASS. Displayed result % = 100. Weight 3%. Exposure 6,220.68. Deducted 0.00.<br><br>PI 3C EXPRESSION 100-p1: 100-0 = 100.0000% = target → PASS.</td>
<td>All three show Target met. PI 3B actual BEI 170.6500 (unit BEI, not %). PI 3B result column on the summary is 100 (pass/fail display), not 170.65. APD deducted 0.00 for each.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>If p1 is entered as 142208.3333 and the field rounds to 4 dp, BEI must still be 170.6500. Lower-is-better for 3B.</td>
</tr>
<tr>
<td>KPA-UAT-024</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Calculation</td>
<td>Verify Dataset C (below target / high BEI) — failing PIs impose their full APD exposure and demerit points.</td>
<td>Use October 2026 with MPV 4,147,120.556 and 5% so September Dataset A remains intact.</td>
<td>1. Create October 2026 with the same MPV.<br>2. PI 1A: p1=60, p2=100. Save.<br>3. PI 1E: p1=10, p2=8, p3=5, p4=5, p5=0, p6=0. Save.<br>4. PI 3B: p1=200000, p2=50000, p3=10000, p4=12. Save.<br>5. PI 4C: p1=90, p2=80. Save.<br>6. Compare with the manual working.</td>
<td>October 2026. APD Maximum 207,356.03.<br><br>PI 1A: 60.0000% &lt; 80 → FAIL. Demerit 1. APD deducted 10,367.80.<br><br>PI 1E: buckets 80%, 100%, 100%. Mean = 93.3333%. Target 100 → FAIL. Demerit 1. APD deducted 10,367.80.<br><br>PI 3B: (250000/10000)×12 = 300.0000 &gt; 170.6500 → FAIL. Result % = 0. Demerit 1. APD deducted 6,220.68.<br><br>PI 4C: mean(90,80)=85.0000% &lt; 100 → FAIL. Demerit 1. APD deducted 6,220.68.<br><br>Sum of these four deductions = 33,176.96. Sum of demerit = 4.</td>
<td>Each PI shows Target not met, the achievement/BEI above, APD Deducted equal to full exposure, and the seeded demerit. October month cards increase by these amounts once the PIs are saved (even before submit). Summary Actual column is red for these PIs.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Failing a PI deducts the whole APD exposure, not a partial percentage of the miss.</td>
</tr>
<tr>
<td>KPA-UAT-025</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Calculation</td>
<td>Verify the workbook APD example: MPV 4,147,120.556 at 5% gives APD maximum RM 207,356.03, and a 5% weightage PI (1B) has exposure RM 10,367.80.</td>
<td>September 2026 month exists with that MPV. KPI Admin.</td>
<td>1. Open September 2026 month header and read APD Maximum.<br>2. Open PI 1B (Customer Rating in Work Order sheet) and read APD Exposure (value is shown even before parameters if the month snapshot calculated it).<br>3. Manually compute 4,147,120.556 × 5 / 100 = 207,356.0278 → 207,356.03 and 207,356.03 × 5 / 100 = 10,367.8015 → 10,367.80.</td>
<td>MPV 4,147,120.556; 5%; PI 1B weight 5%.</td>
<td>APD Maximum = 207,356.03. PI 1B APD Value / Exposure = 10,367.80. If 1B is later failed, APD Deducted becomes 10,367.80; if it passes, deducted stays 0.00.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>This is the implementation-verified workbook example. Do not invent a different APD split.</td>
</tr>
<tr>
<td>KPA-UAT-026</td>
<td>M02 KPI &amp; APD</td>
<td>Monthly Evaluation</td>
<td>Verify the monthly evaluation grid shows the evaluation month, each snapshotted PI, entered values / achievement and running demerit and APD totals.</td>
<td>September 2026 with Dataset A PIs entered (1A, 1E, 4C).</td>
<td>1. Open Monthly Evaluation, year 2026.<br>2. Confirm the September row: period, MPV, max APD %, APD maximum, demerit, APD deducted, progress, status Open.<br>3. Open the month.<br>4. Confirm 1A / 1E / 4C show the Dataset A achievements and Target met.<br>5. Confirm other PIs still Draft / not calculated.</td>
<td>September 2026 Dataset A.</td>
<td>Grid period is September 2026. Entered PIs show achievement and pass. Progress = submitted / total (e.g. 1/21 if only 1A was submitted). Status remains Open until every PI is submitted. Totals equal the sum of imposed demerit and deducted APD of PIs that have results.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td></td>
</tr>
<tr>
<td>KPA-UAT-027</td>
<td>M02 KPI &amp; APD</td>
<td>Monthly Evaluation</td>
<td>Verify the month status becomes Completed only after every snapshotted PI has been submitted.</td>
<td>KPI Admin. Either submit every remaining September PI with any valid passing values (long path) OR use a dedicated UAT site whose structure has only the four calculation PIs. TBC which path the business will execute.</td>
<td>1. Note current progress (e.g. 1 / 21).<br>2. Submit remaining PIs (Admin may enter and submit unassigned PIs).<br>3. Refresh the month list.<br>4. Reopen one PI and confirm the month returns to Open, then re-submit.</td>
<td>All PIs in the snapshot.</td>
<td>When progress is n / n submitted, status = Completed. Reopen of any PI returns status to Open. There is no approve button and no extra workflow.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>TBC — Business confirmation required: whether UAT will submit all 21 seeded PIs or use a reduced UAT structure. If 21 must be submitted, schedule a dedicated data-entry session.</td>
</tr>
<tr>
<td>KPA-UAT-028</td>
<td>M02 KPI &amp; APD</td>
<td>KPI / APD Summary</td>
<td>Verify the KPI / APD Dashboard figures for September 2026 equal the monthly evaluation results (not an independent invented score).</td>
<td>September 2026 Dataset A entered. Prefer after the calculation PIs are saved.</td>
<td>1. Open KPI &amp; APD &gt; KPI / APD Summary.<br>2. Select UAT Site A, Year 2026, Month September.<br>3. Read Total Indicators, Total Weight, Demerit Points, APD Deducted.<br>4. Find rows 1A, 1E, 4C in the Pavement Deduction table.<br>5. Compare Target, Actual, Points Imposed, Weightage, APD Value, APD Deducted with the PI Entry screens.<br>6. Use category chips (Service Delivery, Energy Efficiency, Safety &amp; Compliance) and the keyword search.</td>
<td>September 2026. Expected 1A Actual 88.0000 Target 80 Points Imposed 0 APD Deducted 0.00. 1E Actual 100.0000. 4C Actual 100.0000. Demerit / APD Deducted cards = sum of all PIs in that month (0.00 extra if only these three have results and they passed).</td>
<td>Summary cards and table rows match the evaluation. BEI rows show target without a % suffix. Pass actuals are green; fail red; not calculated show an em-dash. Search/chips filter the table. If a month has not been created, the page shows the structure with empty results and 'No evaluation exists for this month yet. Showing the configured structure.' — that banner must not appear for September once created.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Table title in the product is 'Pavement Deduction for Performance Based KPI'.</td>
</tr>
<tr>
<td>KPA-UAT-029</td>
<td>M02 KPI &amp; APD</td>
<td>KPI History</td>
<td>Verify completed or in-progress months remain in History with the same MPV, APD and demerit, and that an earlier month is not changed by later months.</td>
<td>At least two months exist (e.g. August Dataset B, September Dataset A, October Dataset C).</td>
<td>1. Open KPI History.<br>2. Select UAT Site A, From year 2026, To year 2026.<br>3. Read the monthly totals table for Aug/Sep/Oct.<br>4. Confirm September still shows MPV 4,147,120.556 and APD Maximum 207,356.03 and the Dataset A deducted/demerit.<br>5. Select PI 1A in the indicator trend and confirm Aug 80, Sep 88, Oct 60 (if those months were entered).<br>6. Search/change the year range to a year with no data.</td>
<td>2026 months as created in KPA-UAT-022/023/024.</td>
<td>Each month's MPV, APD Maximum, APD Deducted, APD Retained (Maximum − Deducted), demerit and status match that month's evaluation. September numbers do not pick up October failures. Empty range shows 'No evaluation months in the selected range.' Charts follow the same figures.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>History is a report of stored months, not a live recalculation against a changed template (see KPA-UAT-033).</td>
</tr>
<tr>
<td>KPA-UAT-030</td>
<td>M02 KPI &amp; APD</td>
<td>Access / Role</td>
<td>Verify a KPI Viewer can read the summary and history but cannot enter PI values or change structure.</td>
<td>UAT-KPI-VIEWER account with role 32 only (TBC).</td>
<td>1. Login as KPI Viewer.<br>2. Confirm sidebar shows KPI &amp; APD, KPI / APD Summary and KPI History only (no Monthly Evaluation, Structure, Assignment).<br>3. Open Summary and History for UAT Site A / 2026.<br>4. Confirm no Create Month, no Save and no Submit controls.<br>5. Paste p_kpa_evaluation, p_kpa_structure, p_kpa_assignment and a PI Entry URL. Attempt any save.</td>
<td>UAT-KPI-VIEWER (TBC).</td>
<td>Viewer sees Summary and History values for the UAT site. No edit/submit/create controls on those pages. Hidden pages are blocked or save is rejected. Stored September/October values are not changed.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Role 32 nav grant is Summary + History only.</td>
</tr>
<tr>
<td>KPA-UAT-031</td>
<td>M02 KPI &amp; APD</td>
<td>Access / Role</td>
<td>Verify a KPI Viewer cannot open Monthly Evaluation from the menu and cannot create a month.</td>
<td>UAT-KPI-VIEWER.</td>
<td>1. Login as KPI Viewer.<br>2. Confirm Monthly Evaluation is not listed.<br>3. Open p_kpa_evaluation directly.<br>4. If the page renders, click Create Month and attempt to save.</td>
<td>UAT-KPI-VIEWER.</td>
<td>Monthly Evaluation is not in the sidebar. Direct access does not create a new month. Existing months remain unchanged.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td></td>
</tr>
<tr>
<td>KPA-UAT-032</td>
<td>M02 KPI &amp; APD</td>
<td>End-to-end</td>
<td>Verify one KPI can be taken from structure confirmation through assignment, PI entry, monthly evaluation, summary and history using the same site, PI and month.</td>
<td>KPI Admin, PI Entry and (optional) KPI Viewer accounts. UAT Site A. Shared or site template ready. September 2026 is the official E2E month (create it if KPA-UAT-011 was skipped).</td>
<td>1. As KPI Admin, open KPI Structure — confirm PI 1A is active, target 80%, formula (p1/p2)*100, weight 5%.<br>2. Confirm Maximum APD % = 5.00.<br>3. Assign PI 1A to UAT-PI-ENTRY for UAT Site A.<br>4. Create September 2026 with MPV 4,147,120.556 if it does not exist.<br>5. Logout. Login as PI Entry.<br>6. Open Monthly Evaluation &gt; September 2026 &gt; PI 1A.<br>7. Enter p1=88, p2=100. Save Draft. Confirm achievement 88.0000% Target met APD deducted 0.00.<br>8. Submit PI 1A.<br>9. Open KPI / APD Summary for September 2026 — row 1A Actual 88, Points Imposed 0, APD Deducted 0.00.<br>10. Open KPI History 2026 — September row still shows the same MPV and APD Maximum 207,356.03.</td>
<td>Same KPI: PI 1A. Same site: UAT Site A. Same month: September 2026. Same values: 88 / 100 → 88%.</td>
<td>Every stage shows PI 1A, September 2026 and achievement 88.0000%. Submit locks the PI. Summary and History reconcile to the evaluation. No approval step appears. PI Entry never needed Structure access. APD exposure for 1A remains 10,367.80 and deducted remains 0.00.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Keep September reserved for this passing Dataset A chain. Use August/October for boundary/fail datasets.</td>
</tr>
<tr>
<td>KPA-UAT-033</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Structure</td>
<td>Verify changing a PI target or formula after a month has been created does not rewrite that month's snapshotted target or result.</td>
<td>September 2026 already contains PI 1A result 88.0000% against target 80. Dedicated UAT site preferred. If only the shared template is available, change a UAT-only PI instead of seeded 1A.</td>
<td>1. Note September PI 1A target 80 and actual 88.<br>2. As KPI Admin, on KPI Structure temporarily change PI 1A target to 90 (UAT site structure only).<br>3. Re-open September 2026 PI 1A.<br>4. Confirm the month still shows target 80 and actual 88 and still Target met.<br>5. Restore structure target to 80.<br>6. Create (or inspect) a brand-new future month and confirm it picks up target 90 only if the change was left in place — then restore 80 before that new month is kept.</td>
<td>Structure target temporarily 90. September snapshot must stay 80.</td>
<td>September PI 1A target remains 80.0000 and result remains Target met. Structure change does not recalculate historical months. Only months created after the change would receive the new target.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>This is the snapshot rule. Do not leave the shared template target at 90.</td>
</tr>
<tr>
<td>KPA-UAT-034</td>
<td>M02 KPI &amp; APD</td>
<td>KPI / APD Summary</td>
<td>Verify the summary page for a month that has not yet been created shows the configured indicators with empty results rather than a blank page.</td>
<td>A month with no evaluation, e.g. March 2025 on UAT Site A.</td>
<td>1. Open KPI / APD Summary.<br>2. Select UAT Site A, March 2025 (or another unused month).<br>3. Read the banner and the table.</td>
<td>Unused month.</td>
<td>Banner: 'No evaluation exists for this month yet. Showing the configured structure.' Table lists configured PIs. Actual, demerit imposed and APD deducted are empty / zero. Status Not started. exists = false. Page is not blank.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td></td>
</tr>
<tr>
<td>KPA-UAT-035</td>
<td>M02 KPI &amp; APD</td>
<td>Monthly Evaluation</td>
<td>Verify editing MPV or Max APD % on an existing month recalculates APD maximum and every indicator's APD exposure.</td>
<td>KPI Admin. Use October 2026 (Dataset C) or a spare month — do not change September if it is the signed E2E month, or change and restore.</td>
<td>1. Open the spare month.<br>2. Edit MPV.<br>3. Change MPV from 4,147,120.556 to 2,000,000.000.<br>4. Keep Max APD % = 5.<br>5. Save.<br>6. Compute APD Maximum = 100,000.00 and PI 1A exposure = 5,000.00.<br>7. Restore MPV 4,147,120.556 so later history checks still match, unless this month is disposable.</td>
<td>New MPV 2,000,000.000. Max APD 5%. Expected APD Maximum 100,000.00. 5% PI exposure 5,000.00. Warning on the modal: 'Saving recalculates the APD value of every indicator in this month.'</td>
<td>Save: 'Monthly KPI evaluation updated and recalculated.' Header APD Maximum = 100,000.00. Each PI exposure is recomputed from the new maximum. Achievement values do not change. Restore leaves the official figures back at 207,356.03 if required.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td></td>
</tr>
<tr>
<td>KPA-UAT-036</td>
<td>M02 KPI &amp; APD</td>
<td>KPI Configuration</td>
<td>Verify Maximum APD percentage rejects values outside 0–100 when saved from KPI Structure.</td>
<td>Logged in as KPI Admin (role 30) or Administrator. Dedicated UAT site selected. Shared KPI template (4 groups / 21 PIs) is active.</td>
<td>1. Open KPI Structure.<br>2. Enter Maximum APD (%) = 101. Save.<br>3. Enter -1. Save.<br>4. Restore 5.00 and save.</td>
<td>101; -1; then 5.00.</td>
<td>Out-of-range values are rejected: 'The maximum APD percentage must be between 0 and 100.' 5.00 remains the stored value after restore.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Duplicates the invalid branch of KPA-UAT-007; execute once if preferred and mark the other as cross-referenced.</td>
</tr>
</tbody></table>


---

## 8. M03 — Energy & Utility

<table>
<thead><tr>
<th>UAT ID</th>
<th>Module</th>
<th>Function / Submodule</th>
<th>Test Scenario</th>
<th>Preconditions</th>
<th>Test Steps</th>
<th>Test Data</th>
<th>Expected Result</th>
<th>Actual Result</th>
<th>Status</th>
<th>Severity</th>
<th>Remarks</th>
</tr></thead><tbody>
<tr>
<td>ENR-UAT-001</td>
<td>M03 Energy &amp; Utility</td>
<td>Daily Electricity</td>
<td>Verify a utility officer can record a cumulative incoming-meter reading for a building and date, and that the system stores it as a reading (not as a typed daily kWh consumption).</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist.</td>
<td>1. Login to GEMS.<br>2. Open Energy Monitoring &gt; Daily Electricity.<br>3. Select UAT Site A, Month September, Year 2026.<br>4. On Incoming No.1, date 31/08/2026, open August 2026 first if the September grid does not show 31/08.<br>5. Enter Cumulative (kWh) 10,000.00 on 31/08/2026 (baseline).<br>6. Switch to September 2026.<br>7. Enter Incoming No.1 cumulative 10,100.00 on 01/09/2026.<br>8. Tab or leave the cell and wait for Saved.<br>9. Refresh the page.</td>
<td>ENR-RDG-01: Incoming No.1. 31/08/2026 = 10,000.00. 01/09/2026 = 10,100.00. Expected 01/09 consumption = 100.00 kWh.</td>
<td>Autosave shows Saved and 'Meter reading saved.' After refresh both cumulative values remain. 01/09/2026 Consumption (kWh) = 100.00. 31/08/2026 is the baseline (no consumption on the first reading day of a meter unless a previous reading exists). Total kWh card includes 100.00 for September once only 01/09 is in range (plus any later September days).</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Users enter CUMULATIVE meter readings. Consumption is calculated. This is different from typing 100 kWh as a daily usage figure.</td>
</tr>
<tr>
<td>ENR-UAT-002</td>
<td>M03 Energy &amp; Utility</td>
<td>Daily Electricity</td>
<td>Verify a cumulative reading cannot be negative, cannot be in the future, and cannot be saved without a value.</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist.</td>
<td>1. On Daily Electricity for today, enter cumulative -10 on Incoming No.1. Leave the cell.<br>2. Attempt a reading dated tomorrow (if the date column exists for a future day in the current month, or change month to a future month if offered).<br>3. Clear a cell that had no previous reading and confirm nothing is posted as a negative.</td>
<td>Cumulative -10; date = tomorrow.</td>
<td>Negative rejected: 'The cumulative meter reading cannot be negative.' Future date rejected: 'The reading date cannot be in the future.' No official reading is stored for the invalid attempts. Existing valid readings are unchanged.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>The grid typically only lists days of the selected month; tomorrow appears only when testing near month-end or using the last day +1 via API-less UI (if tomorrow is not on the grid, record that the date control / month list prevents future months beyond current+1 and still blocks future days).</td>
</tr>
<tr>
<td>ENR-UAT-003</td>
<td>M03 Energy &amp; Utility</td>
<td>Daily Electricity</td>
<td>Verify a zero cumulative reading is accepted (meters may be reset) and is not treated as a missing reading.</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist. Use Incoming No.2 so Incoming No.1 E2E data is not disturbed.</td>
<td>1. Select Incoming No.2 on a spare date (e.g. 01/07/2026 in July).<br>2. Enter cumulative 0.00. Wait for Saved.<br>3. Refresh.</td>
<td>Incoming No.2; 01/07/2026; 0.00 kWh.</td>
<td>Save succeeds. Value 0.00 remains after refresh. It is a stored reading, not a blank cell.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Zero is allowed. Negative is not. Clear the cell later if this spare meter should stay empty (ENR-UAT-008).</td>
</tr>
<tr>
<td>ENR-UAT-004</td>
<td>M03 Energy &amp; Utility</td>
<td>Daily Electricity</td>
<td>Verify decimal cumulative readings are stored to 2 decimal places and used in consumption.</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist.</td>
<td>1. On Incoming No.1, enter 10,225.50 on 02/09/2026 (after 10,100.00 on 01/09).<br>2. Confirm consumption on 02/09/2026.</td>
<td>02/09/2026 cumulative 10,225.50. Previous 10,100.00. Expected consumption 125.50 kWh.</td>
<td>Saved value shows 10225.50 / 10,225.50. Consumption 02/09 = 125.50 kWh (2 decimal places).</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>If the official E2E set must stay at 10,225.00, use 10,225.00 here and run the decimal check on Incoming No.2 instead (e.g. 1000.00 then 1000.25).</td>
</tr>
<tr>
<td>ENR-UAT-005</td>
<td>M03 Energy &amp; Utility</td>
<td>Daily Electricity</td>
<td>Verify entering a second cumulative value on the same meter and date overwrites the reading instead of creating a duplicate day.</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist.</td>
<td>1. On Incoming No.1, 03/09/2026, enter 10,375.00. Save.<br>2. Change the same cell to 10,380.00. Save.<br>3. Refresh.<br>4. Set it back to 10,375.00 if this day is part of the 375 kWh official set.</td>
<td>Same meter + date; 10,375.00 then 10,380.00.</td>
<td>Only one reading exists for Incoming No.1 on 03/09/2026. Latest value is stored. Consumption for 03/09 uses the latest cumulative minus the previous reading.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Unique key is meter + date (upsert).</td>
</tr>
<tr>
<td>ENR-UAT-006</td>
<td>M03 Energy &amp; Utility</td>
<td>Daily Electricity</td>
<td>Verify missed days between two readings receive an even share of the consumption (gap distribution).</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist. Use Incoming No.2 so the official Incoming No.1 September totals stay clean.</td>
<td>1. Open September 2026, Incoming No.2.<br>2. Enter cumulative 20,000.00 on 16/09/2026.<br>3. Enter cumulative 20,300.00 on 18/09/2026.<br>4. Read consumption for 16, 17 and 18 September.</td>
<td>Incoming No.2: 16/09 = 20,000.00 (first reading / baseline). 18/09 = 20,300.00. gapDays = 2. delta = 300. perDay = 150.00.</td>
<td>16/09 consumption is blank (first reading day has no prior interval). 17/09 consumption = 150.00 kWh. 18/09 consumption = 150.00 kWh. Warning box is empty (readings increased). Monthly total for Incoming No.2 includes 300.00 kWh from this gap.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Formula: perDay = (later cumulative − earlier cumulative) / calendar days between the two reading dates; that perDay is written onto each day after the earlier reading through to the later reading date.</td>
</tr>
<tr>
<td>ENR-UAT-007</td>
<td>M03 Energy &amp; Utility</td>
<td>Daily Electricity</td>
<td>Verify a cumulative reading lower than the previous reading is stored but not spread, and the user is warned to check for a meter replacement or typing error.</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist. Use Incoming No.2 on spare days after the gap test, or a third meter.</td>
<td>1. On Incoming No.2 enter 21,000.00 on 20/09/2026.<br>2. Enter 20,500.00 on 21/09/2026.<br>3. Read the amber warning and the consumption cells for 20–21/09.</td>
<td>Rollback: 20/09 21,000.00 → 21/09 20,500.00.</td>
<td>Both readings save. Consumption for the days in that interval stays blank (not a negative kWh). Warning: 'Incoming No.2: The reading on 2026-09-21 is lower than the reading on 2026-09-20. Check for a meter replacement or a typing error.' (date format as shown).</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>The save is not blocked. Days stay blank so an incomplete/invalid interval is visible.</td>
</tr>
<tr>
<td>ENR-UAT-008</td>
<td>M03 Energy &amp; Utility</td>
<td>Daily Electricity</td>
<td>Verify clearing a cumulative cell removes that reading so the day returns to unused.</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist. Use the Incoming No.2 rollback cell or the 0.00 July reading.</td>
<td>1. Clear the cumulative cell for the spare reading.<br>2. Wait for Saved / 'Meter reading removed.'<br>3. Refresh.</td>
<td>Cell cleared (empty).</td>
<td>Reading is deleted. Cell is blank. Any warning caused only by that pair disappears. Official Incoming No.1 September E2E readings are untouched.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Clearing is the implemented delete. There is no separate Delete button on the grid.</td>
</tr>
<tr>
<td>ENR-UAT-009</td>
<td>M03 Energy &amp; Utility</td>
<td>Daily Electricity</td>
<td>Verify chiller running hours and the daily remark can be saved for the site (one note per date, not per meter).</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist.</td>
<td>1. On 01/09/2026 enter Chiller hrs 12.5 and Remark 'UAT chiller note'.<br>2. Wait for Saved.<br>3. Refresh.<br>4. Confirm the same note is not captured separately per meter.</td>
<td>01/09/2026; 12.5 hours; remark UAT chiller note.</td>
<td>Values persist after refresh. Message 'Daily note saved.' One remark/chiller value applies to the site-date row.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Low</td>
<td>No server-side negative check on chiller hours (GAP-ENR-02). Do not treat a negative chiller value as a required fail unless the business later mandates it.</td>
</tr>
<tr>
<td>ENR-UAT-010</td>
<td>M03 Energy &amp; Utility</td>
<td>Monthly Summary</td>
<td>Verify September monthly consumption for Incoming No.1 equals the sum of derived daily consumption from the controlled cumulative readings.</td>
<td>Incoming No.1 readings: 31/08/2026 = 10,000.00; 01/09 = 10,100.00; 02/09 = 10,225.00; 03/09 = 10,375.00. No further Incoming No.1 readings in September (remove extras).</td>
<td>1. Open Daily Electricity September 2026 and confirm consumption 100.00 + 125.00 + 150.00 on 1–3 Sep.<br>2. Read the Incoming No.1 monthly total on the daily footer.<br>3. Open Energy Monitoring &gt; Monthly Summary, UAT Site A, Year 2026.<br>4. Read September Incoming No.1 and Total kWh.</td>
<td>Expected daily: 01/09=100.00; 02/09=125.00; 03/09=150.00. Expected September Incoming No.1 = 375.00 kWh. Manual: 100+125+150=375.</td>
<td>Daily footer monthly total for Incoming No.1 = 375.00. Monthly Summary September Incoming No.1 = 375.00. September Total kWh includes 375.00 plus any other meters' September consumption (Incoming No.2 must be 0.00 / unused for the isolated figure). Chart September bar for Incoming No.1 = 375.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>This is the adapted form of the 100+125+150=375 business example. Because the product stores cumulative readings, testers enter 10000 / 10100 / 10225 / 10375, not 100, 125, 150 as typed usage.</td>
</tr>
<tr>
<td>ENR-UAT-011</td>
<td>M03 Energy &amp; Utility</td>
<td>Monthly Summary</td>
<td>Verify two incoming meters are totalled separately and then combined on the monthly summary.</td>
<td>Incoming No.1 September = 375.00. Incoming No.2 has the 300.00 gap-distribution consumption from 16–18/09 (if still present) or is unused (0.00).</td>
<td>1. Open Monthly Summary 2026.<br>2. Read September columns Incoming No.1, Incoming No.2, Total kWh.<br>3. Add the two meter columns and compare with Total kWh.</td>
<td>If No.2 gap test remains: 375.00 + 300.00 = 675.00. If No.2 cleared: 375.00 + 0.00 = 375.00.</td>
<td>Per-meter September figures match Daily Electricity monthly totals. Total kWh = sum of meters. Year footer equals the sum of the twelve month totals for each meter.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Clear Incoming No.2 before the official BEI E2E if BEI should use 375 kWh only.</td>
</tr>
<tr>
<td>ENR-UAT-012</td>
<td>M03 Energy &amp; Utility</td>
<td>Period selection</td>
<td>Verify changing site, month and year reloads the correct period and does not leave the previous period's readings on screen.</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist.</td>
<td>1. Open Daily Electricity, UAT Site A, September 2026 — confirm the 10,100.00 reading is visible.<br>2. Change to August 2026 — confirm 31/08 10,000.00 and no September rows.<br>3. Change to October 2026 — September readings are gone.<br>4. Open Monthly Summary, change year to 2025, then back to 2026.<br>5. If a second site exists, switch site and confirm Incoming No.1 UAT readings disappear.</td>
<td>August / September / October 2026; year 2025 vs 2026.</td>
<td>Each change shows only that period's data. September 2026 figures return when that period is selected again. No mixing of sites.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td></td>
</tr>
<tr>
<td>ENR-UAT-013</td>
<td>M03 Energy &amp; Utility</td>
<td>Daily Electricity</td>
<td>Verify days that no interval covers stay blank (not zero) so an incomplete month is obviously incomplete.</td>
<td>Incoming No.1 has readings only on 31/08 and 1–3/09. Rest of September has no later reading.</td>
<td>1. Open Daily Electricity September 2026.<br>2. Inspect 04/09/2026 through 30/09/2026 consumption cells.<br>3. Read Days Covered and Average Daily.</td>
<td>No Incoming No.1 reading after 03/09/2026.</td>
<td>04–30 September consumption shows em-dash / blank, not 0.00. Days Covered is 3 / 30 (plus any other meter days). Average Daily = total kWh / days that have data (375 / 3 = 125.00 if only those three days and only Incoming No.1).</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Blank means 'not covered', not 'zero consumption'. Monthly Summary may still display 0.00 in a meter cell when the whole month has no data for that meter (GAP-ENR-01).</td>
</tr>
<tr>
<td>ENR-UAT-014</td>
<td>M03 Energy &amp; Utility</td>
<td>Building Energy Index</td>
<td>Verify BEI is calculated as (electricity + chilled water) / gross floor area × annualisation factor, and that a value at or below target is a pass.</td>
<td>UAT Site A. Incoming No.1 September total 375.00 kWh. Incoming No.2 unused (0). Tester can save site configuration.</td>
<td>1. Open Energy Monitoring &gt; Building Energy Index.<br>2. Select UAT Site A, Year 2026.<br>3. Set Gross floor area 10,000 m², Target BEI 170.65, Annualisation factor 12. Save configuration.<br>4. On September, leave Electricity as the derived 375.00 (or type it if not prefilled).<br>5. Enter Chilled water 125.00.<br>6. Save the month if required and read Actual BEI, result and pass/fail.</td>
<td>Electricity 375.00. Chilled 125.00. Total energy 500.00. GFA 10,000. Factor 12. Manual BEI = (500 / 10000) × 12 = 0.6000. Target 170.65. 0.6000 ≤ 170.65 → PASS. Result % = 100.</td>
<td>Electricity defaults from daily readings (375.00) with hint 'From daily readings'. Total energy 500.00. Actual BEI 0.6000. Month passes. Result 100%. Configuration save: 'Energy configuration saved.' Month save: 'Monthly BEI saved.'</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Implemented formula: actualBei = round((electricityKwh + chilledWaterKwh) / floorAreaSqm × factor, 4); pass when actualBei &lt;= targetBei + 0.00005. Factor 12 is required when the consumption is a monthly figure and the target is an annual BEI.</td>
</tr>
<tr>
<td>ENR-UAT-015</td>
<td>M03 Energy &amp; Utility</td>
<td>Building Energy Index</td>
<td>Verify a month whose BEI is above the target fails (result 0%) using a controlled high-consumption override.</td>
<td>Site config still GFA 10,000; target 170.65; factor 12. Use October 2026 so September pass case remains.</td>
<td>1. Open BEI, year 2026, October.<br>2. Enter Electricity 1,200,000.00 (override) and Chilled water 300,000.00.<br>3. Save.<br>4. Compute (1,500,000 / 10,000) × 12 = 1,800.0000.</td>
<td>October electricity 1,200,000.00; chilled 300,000.00; GFA 10,000; factor 12; target 170.65. Expected BEI 1800.0000. FAIL. Result 0%.</td>
<td>Actual BEI 1800.0000. Target not met / 0%. Status Draft until finalised. September row is still 0.6000 / 100%. Year cards count October as failing and September as passing.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Lower BEI is better. resultPct is 100 or 0, not a partial score.</td>
</tr>
<tr>
<td>ENR-UAT-016</td>
<td>M03 Energy &amp; Utility</td>
<td>Building Energy Index</td>
<td>Verify BEI cannot be scored when gross floor area is missing / not set, and the user is told to enter it.</td>
<td>Use a spare site if available. Otherwise temporarily set UAT Site A GFA to 0, run the check, then restore 10,000. Do not finalise.</td>
<td>1. Set Gross floor area to blank/0 and Save configuration.<br>2. Open a month with electricity 375.<br>3. Read BEI and message.<br>4. Restore GFA 10,000.</td>
<td>GFA 0 or blank.</td>
<td>Actual BEI is blank / not scored. Message: 'Enter a gross floor area greater than zero in the site configuration.' Finalise is rejected: 'The BEI cannot be finalised until it produces a value. Check the floor area and consumption.'</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Restore GFA immediately.</td>
</tr>
<tr>
<td>ENR-UAT-017</td>
<td>M03 Energy &amp; Utility</td>
<td>Building Energy Index</td>
<td>Verify a zero or negative floor area is rejected or produces no BEI, and a zero/negative target prevents scoring.</td>
<td>KPI Admin or Site Admin/Administrator who can save energy configuration.</td>
<td>1. Attempt GFA -10. Save.<br>2. Attempt Target BEI -1. Save.<br>3. Set Target BEI 0 with GFA 10,000 and open September.<br>4. Restore Target 170.65 and GFA 10,000.</td>
<td>GFA -10; target -1; target 0.</td>
<td>Negative GFA: 'The gross floor area cannot be negative.' Negative target: 'The target BEI cannot be negative.' Target 0: BEI number may still calculate but is not scored — message 'Set a target BEI in the site configuration to score this month.' Result Not scored.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Annualisation factor ≤ 0 is reset to 1.0 (not rejected with a message).</td>
</tr>
<tr>
<td>ENR-UAT-018</td>
<td>M03 Energy &amp; Utility</td>
<td>Building Energy Index</td>
<td>Verify typing over the electricity figure marks the month as an override, and clearing it returns the value derived from daily readings.</td>
<td>September 2026 derived electricity 375.00.</td>
<td>1. Open BEI September.<br>2. Change Electricity to 400.00. Save.<br>3. Confirm hint shows Override and the derived 375.00.<br>4. Clear Electricity and Save.<br>5. Confirm it returns to 375.00 From daily readings.</td>
<td>Override 400.00 then clear.</td>
<td>After override, electricity 400.00, BEI recalculates with 400 + chilled. Hint includes Override and the derived 375.00. After clear, electricity 375.00 and override flag is removed.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Override exists because Energy daily meters and KPI PI 3B do not currently share the same meter scope (GAP-KPA-03 / G08).</td>
</tr>
<tr>
<td>ENR-UAT-019</td>
<td>M03 Energy &amp; Utility</td>
<td>Building Energy Index</td>
<td>Verify a utility officer can finalise a month to lock it, and only a setup-capable user (Site Admin / KPI Admin / Administrator) can reopen it.</td>
<td>September BEI saved with the official 375 / 125 / 0.6000 pass figures. Utility Reader and KPI Admin (or Administrator) accounts available.</td>
<td>1. As Utility Reader (or Admin), Finalise September. Confirm: 'Finalise this month? The values are locked once finalised.'<br>2. Attempt to change chilled water. Save.<br>3. As Utility Reader, confirm Reopen is not available.<br>4. As KPI Admin / Administrator / Site Admin, Reopen.<br>5. Change is allowed again. Re-finalise if the customer wants the month locked after UAT.</td>
<td>September 2026 official BEI row.</td>
<td>Finalise: 'Monthly BEI finalised.' Status Final. Edit rejected: 'This month has been finalised. Reopen it before changing the values.' Reopen available only to setup-capable roles. After reopen, status Draft and values can change.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Site Admin has API reopen/setup but no Energy menu in the shipped nav SQL (GAP-ENR-03). Administrator or KPI Admin can demonstrate reopen if Site Admin cannot see the page.</td>
</tr>
<tr>
<td>ENR-UAT-020</td>
<td>M03 Energy &amp; Utility</td>
<td>Meter setup</td>
<td>Verify a setup-capable user can add an incoming meter and can deactivate it without deleting its historical readings.</td>
<td>Logged in as Administrator or KPI Admin (Meter setup button visible). Dedicated UAT site.</td>
<td>1. On Daily Electricity click Meter setup.<br>2. Add meter name 'UAT Incoming No.3', description 'UAT only', order 3. Save.<br>3. Confirm it appears on the daily grid.<br>4. Enter a one-day cumulative on that meter, then deactivate the meter.<br>5. Confirm prompt: 'Deactivate this meter? Existing readings are kept.'<br>6. Confirm the meter is Inactive and no longer used for new days, while the earlier reading is retained.</td>
<td>UAT Incoming No.3.</td>
<td>Create: 'Incoming meter saved.' Duplicate name rejected: 'A meter named "UAT Incoming No.3" already exists at this site.' Deactivate: 'Incoming meter deactivated.' Historical reading remains available for totals of the days it covered.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Utility Reader cannot see Meter setup (ENR-UAT-021).</td>
</tr>
<tr>
<td>ENR-UAT-021</td>
<td>M03 Energy &amp; Utility</td>
<td>Access / Role</td>
<td>Verify a Utility Reader can enter readings and BEI values but cannot change meter setup or site BEI configuration.</td>
<td>UAT-UTILITY-READER role 18 only (TBC).</td>
<td>1. Login as Utility Reader.<br>2. Confirm Energy Monitoring menu is visible.<br>3. Enter or edit a spare cumulative reading — allowed.<br>4. Confirm Meter setup is hidden.<br>5. Open BEI — electricity/chilled editable; configuration Save disabled.<br>6. Confirm Reopen on a Final month is not available.</td>
<td>UAT-UTILITY-READER.</td>
<td>Reader can record readings, daily notes and BEI month values / finalise. Meter setup button is hidden. Site GFA/target/factor cannot be saved. Reopen is hidden. Other sites are not selectable if the account is site-scoped.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td></td>
</tr>
<tr>
<td>ENR-UAT-022</td>
<td>M03 Energy &amp; Utility</td>
<td>Access / Role</td>
<td>Verify PI Entry / KPI Viewer have no Energy Monitoring menu, and Site Admin has no menu even though setup APIs exist.</td>
<td>UAT-PI-ENTRY (31) and/or UAT-KPI-VIEWER (32). Optional Site Admin (19).</td>
<td>1. Login as PI Entry — confirm Energy Monitoring is not in the sidebar.<br>2. Paste p_energy_daily — page is view-only or denied; save of a reading is rejected: 'You are not allowed to record meter readings.'<br>3. Repeat as KPI Viewer.<br>4. As Site Admin, confirm Energy Monitoring is not in the sidebar (shipped grants). If the URL opens, meter setup / config may be allowed — record actual behaviour.</td>
<td>Roles 31, 32, 19.</td>
<td>Roles 31 and 32: no menu; cannot save readings. Role 19: no menu in shipped navigation (GAP-ENR-03). Record whether direct URL allows setup. No official reading is created by a view-only user.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>TBC — Business confirmation required on whether Site Admin should receive the Energy menu.</td>
</tr>
<tr>
<td>ENR-UAT-023</td>
<td>M03 Energy &amp; Utility</td>
<td>End-to-end</td>
<td>Verify one building and one reporting month can be followed from daily cumulative readings through monthly total to a manually checked BEI.</td>
<td>UAT Site A. Incoming No.1 only (Incoming No.2 unused). Config GFA 10,000; target 170.65; factor 12.</td>
<td>1. Enter Incoming No.1 cumulatives: 31/08/2026 = 10,000.00; 01/09 = 10,100.00; 02/09 = 10,225.00; 03/09 = 10,375.00.<br>2. Confirm daily consumption 100, 125, 150 and September meter total 375.00 kWh.<br>3. Open Monthly Summary 2026 — September Incoming No.1 = 375.00.<br>4. Open BEI 2026. Confirm Electricity prefilled 375.00.<br>5. Enter Chilled water 125.00.<br>6. Manually calculate (375+125)/10000×12 = 0.6000.<br>7. Compare with system Actual BEI.<br>8. Confirm pass versus 170.65.</td>
<td>Same site UAT Site A. Same period September 2026. Same meter Incoming No.1. Same 375 kWh + 125 kWh chilled.</td>
<td>Daily total, monthly total and BEI electricity input are all 375.00 kWh. System BEI = 0.6000. Manual BEI = 0.6000. Month passes (100%). Figures still match after refresh.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Critical</td>
<td>Do not mix Incoming No.2 gap-test kWh into this official chain.</td>
</tr>
<tr>
<td>ENR-UAT-024</td>
<td>M03 Energy &amp; Utility</td>
<td>Period selection</td>
<td>Verify a reading pair that crosses 31 December / 1 January still produces consumption on 1 January and appears in the new year monthly total.</td>
<td>Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist. Use Incoming No.2 to avoid touching September E2E.</td>
<td>1. Open Daily Electricity December 2025 (or 2026 if executing after that year exists — use 31/12/2025 and 01/01/2026 if those dates are not in the future).<br>2. Enter Incoming No.2 cumulative 50,000.00 on 31/12/2025.<br>3. Switch to January 2026 and enter 50,200.00 on 01/01/2026.<br>4. Confirm 01/01 consumption = 200.00.<br>5. Open Monthly Summary year 2026 — January Incoming No.2 includes 200.00. Year 2025 December includes 0.00 from this pair (the first reading is baseline).</td>
<td>31/12/2025 = 50,000.00; 01/01/2026 = 50,200.00. Expected 01/01/2026 consumption 200.00 kWh.</td>
<td>1 January shows 200.00 kWh. January 2026 monthly total for Incoming No.2 includes 200.00. December 2025 does not invent consumption on 31/12 from this first reading. If 31/12/2025 is in the future relative to the UAT day, use the most recent 31 Dec / 1 Jan that is not in the future and record the dates used.</td>
<td>________________</td>
<td>Not Tested</td>
<td>High</td>
<td>Future dates are blocked — pick a year already elapsed, or execute after 1 Jan.</td>
</tr>
<tr>
<td>ENR-UAT-025</td>
<td>M03 Energy &amp; Utility</td>
<td>Export</td>
<td>Verify Daily, Monthly and BEI screens can export a CSV that contains the same kWh / BEI figures shown on screen.</td>
<td>September 2026 official figures exist.</td>
<td>1. On Daily Electricity September 2026 click Export. Open the CSV.<br>2. On Monthly Summary 2026 click Export. Open the CSV.<br>3. On BEI 2026 click Export. Open the CSV.<br>4. Compare Incoming No.1 September 375.00 and BEI 0.6000 with the screen.</td>
<td>Filenames like 'GEMS Daily Electricity …', 'GEMS Monthly Electricity 2026', 'GEMS Building Energy Index 2026'.</td>
<td>Files download. September 375.00 and BEI 0.6000 (and chilled 125.00) appear in the corresponding export. Export is not a substitute for on-screen reconciliation if figures disagree.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Medium</td>
<td>Export is client-side CSV, not the JKR PDF engine.</td>
</tr>
<tr>
<td>ENR-UAT-026</td>
<td>M03 Energy &amp; Utility</td>
<td>Empty state</td>
<td>Verify Daily Electricity explains when a site has no incoming meters instead of showing a broken grid.</td>
<td>A spare UAT site with no meters, or deactivate all meters on a disposable site. Do not deactivate Incoming No.1 on the official UAT Site A.</td>
<td>1. Open Daily Electricity for the empty site.<br>2. Open Monthly Summary for that site.<br>3. Open Meter setup (Admin) and confirm 'No meters yet'.</td>
<td>Site with zero active meters.</td>
<td>Daily: 'No incoming meters are configured for this site. Use Meter setup to add one.' Monthly chart: 'No incoming meters are configured for this site.' Meter modal: 'No meters yet'. No JavaScript error.</td>
<td>________________</td>
<td>Not Tested</td>
<td>Low</td>
<td></td>
</tr>
</tbody></table>


---

## 9. Traceability matrix

| Requirement / Function | Module | UAT ID(s) | Coverage |
| --- | --- | --- | --- |
| Sidebar / page identity / save-cancel / empty / messages / persistence / format | Common | COM-UAT-001 to COM-UAT-008 | Covered |
| Access denied (no module role) | Common | COM-UAT-007, WST-UAT-028 | Covered |
| Waste Generation (record, mandatory, invalid qty/date, decimal) | M01 | WST-UAT-001 to WST-UAT-005 | Covered |
| Pending Collection / Pending Disposal | M01 | WST-UAT-006, WST-UAT-007, WST-UAT-008, WST-UAT-009, WST-UAT-025, WST-UAT-031 | Covered |
| Disposal (full, images, dates, over-balance, partial, consignment, no second dispose) | M01 | WST-UAT-010 to WST-UAT-016 | Covered |
| Waste Records (search, filter, linked view) | M01 | WST-UAT-017, WST-UAT-029 | Covered |
| Opening Balance | M01 | WST-UAT-018, WST-UAT-030 | Covered |
| Waste Balance reconciliation | M01 | WST-UAT-019, WST-UAT-026 | Covered |
| Waste Dashboard reconciliation / period | M01 | WST-UAT-020, WST-UAT-032 | Covered |
| JKR reporting, export, submission, totals | M01 | WST-UAT-021, WST-UAT-022, WST-UAT-023 | Covered |
| M01 end-to-end | M01 | WST-UAT-024 | Covered |
| M01 roles (Waste User / Officer / none) | M01 | WST-UAT-027, WST-UAT-028 | Covered |
| Fifth Schedule REGISTER data entry (handling, packaging, draft/finalise/amend) | M01 | — | Partial — view path covered (WST-UAT-029). Full REGISTER keying is implemented but is not the V2 operational path. See GAP-WST-04 |
| Waste Setup (premise flags, profiles, locations) | M01 | Entry criteria + GAP-WST-01 | Partial — treated as a precondition, not a customer process UAT |
| KPI Structure (view, group/PI, mandatory, weightage, deactivate, snapshot) | M02 | KPA-UAT-001 to KPA-UAT-006, KPA-UAT-033 | Covered |
| KPI configuration (max APD %) | M02 | KPA-UAT-007, KPA-UAT-036 | Covered |
| PI Assignment | M02 | KPA-UAT-008, KPA-UAT-009, KPA-UAT-010 | Covered |
| PI Entry (save, mandatory, ÷0, submit lock) | M02 | KPA-UAT-017 to KPA-UAT-020 | Covered |
| Monthly Evaluation (create, duplicate, MPV, grid, complete, edit MPV) | M02 | KPA-UAT-011 to KPA-UAT-013, KPA-UAT-026, KPA-UAT-027, KPA-UAT-035 | Covered |
| KPI / APD calculation (Datasets A/B/C + workbook APD) | M02 | KPA-UAT-022 to KPA-UAT-025 | Covered |
| KPI Summary | M02 | KPA-UAT-028, KPA-UAT-034 | Covered |
| KPI History | M02 | KPA-UAT-029 | Covered |
| M02 roles (Admin / PI Entry / Viewer) | M02 | KPA-UAT-014 to KPA-UAT-016, KPA-UAT-021, KPA-UAT-030, KPA-UAT-031 | Covered |
| M02 end-to-end | M02 | KPA-UAT-032 | Covered |
| GEMS+ automatic parameter fetch / contract MPV | M02 | — | Not Covered — not implemented (GAP-KPA-04, GAP-KPA-05) |
| Daily Electricity (enter, validate, decimal, upsert, gap, rollback, delete, notes) | M03 | ENR-UAT-001 to ENR-UAT-009, ENR-UAT-013 | Covered |
| Monthly Summary | M03 | ENR-UAT-010, ENR-UAT-011 | Covered |
| Period / year transition / export / empty meters | M03 | ENR-UAT-012, ENR-UAT-024, ENR-UAT-025, ENR-UAT-026 | Covered |
| BEI (pass, fail, GFA, target, override, finalise) | M03 | ENR-UAT-014 to ENR-UAT-019 | Covered |
| Meter setup | M03 | ENR-UAT-020 | Covered |
| M03 roles | M03 | ENR-UAT-021, ENR-UAT-022 | Covered |
| M03 end-to-end | M03 | ENR-UAT-023 | Covered |
| Daily energy totals feeding KPI PI 3B | M03 / M02 | — | Not Covered — not wired (GAP-KPA-03) |
| License / Space / Visitor / PTW / general user admin | — | — | Not Covered — outside this batch |

---

## 10. Observation / Requirement Gap

Only differences confirmed against the implementation are listed.

| ID | Module | Observation | Expected Business Behaviour | Current Behaviour | Recommendation | UAT Impact |
| --- | --- | --- | --- | --- | --- | --- |
| GAP-WST-01 | M01 | Opening Balance, JKR Waste Reports and Waste Setup are implemented but hidden from the sidebar. Execute Disposal and the Fifth Schedule form are link-only. | A Waste Officer should reach opening stock, JKR reporting and setup from the Waste Management menu. | Testers must type `p_waste_opening_balance`, `p_waste_report` or `p_waste_setup` in the GEMS URL. | Confirm with the customer whether these are Phase-1 hidden-on-purpose or a navigation defect. If they are in-scope for acceptance, add menu items before UAT sign-off. | UAT still covers the functions (WST-UAT-018/021/023). Facilitator must issue the URLs. |
| GAP-WST-02 | M01 | Module documentation originally said the waste menu was Administrator-only. Navigation SQL grants Waste User (28) and Waste Officer (29). | Operational officers use the module. | Menu is granted to roles 1, 28 and 29 if `2026-09-18_nav_waste_kpa_enr.sql` was applied. An earlier script granted Administrator only. | Confirm which script is on the UAT database before role testing. | WST-UAT-027 may be Blocked if only Administrator has the menu. |
| GAP-WST-03 | M01 | Dashboard **Pending Disposal kg** and Draft count are not limited to the selected month. | A monthly dashboard usually shows that month only. | Pending and Draft tiles are current / global counts. Produced/Disposed/Opening/Closing follow the period. | Brief testers. Decide whether the customer accepts current-outstanding on a monthly dashboard. | WST-UAT-020 must reconcile pending against the **current** pending list, not only September rows. |
| GAP-WST-04 | M01 | A full Fifth Schedule REGISTER form still exists (handling, packaging, MT/kg, Submit-to-Final). The V2 operational path is SIMPLE generation/disposal in kg. Save Draft is hidden on the REGISTER form. | TBC — whether JKR officers must still key Fifth Schedule fields operationally. | V2 lifecycle is Generation → Pending Disposal → Execute Disposal. REGISTER remains as view/edit and an alternate keying path. | Agree with the customer that V2 is the accepted operational path. If Fifth Schedule keying is still a contractual deliverable, add a supplementary UAT pack. | This pack treats REGISTER as view/linked-record (Partial). |
| GAP-WST-05 | M01 | Partial collection does not leave a pending remainder. | Some businesses expect the shortfall to stay “awaiting collection”. | Generation becomes Disposed; remainder stays in **balance only**. | Demonstrate WST-UAT-014 to the customer before sign-off so the rule is accepted. | Covered. Do not fail the test for “missing pending remainder” unless the customer rejects the rule. |
| GAP-WST-06 | M01 | Premise default handling method / default location exist on the database for SIMPLE rows but are not exposed on Waste Setup. | SIMPLE rows still need handling/location so the JKR register is complete. | Values are back-filled from columns that Setup does not let the officer maintain. | Confirm defaults were set in SQL for UAT Premise A. Add Setup fields if officers must maintain them. | JKR appendix may show blank handling/location if defaults are empty — record Actual Result. |
| GAP-COM-01 | Common | Breadcrumbs are not implemented on these pages. | Common GEMS pages often show a breadcrumb. | Page title / H1 only. | Accept page title as the identifier for this batch, or add breadcrumbs later. | COM-UAT-002 checks title/H1, not breadcrumb. |
| GAP-KPA-01 | M02 | PI Entry is not a sidebar item (opened from the month grid). GFM Management (role 10) has API administrator rights but is not in the KPA nav grant SQL. | TBC whether role 10 should see the KPI menu. | Grants: Admin+KPI Admin = all pages; PI Entry = Summary, Evaluation, History; Viewer = Summary, History. | Confirm role 10 menu on UAT. | Role 10 menu visibility is TBC. |
| GAP-KPA-02 | M02 | Active weightage not equal to 100% is a warning, not a hard stop. | APD exposures should sum to APD maximum. | Save is allowed while unbalanced. | Keep the UAT template at 100%. Treat a live unbalanced template as a data-setup fail. | KPA-UAT-005. |
| GAP-KPA-03 | M02 / M03 | Energy daily totals are **not** copied into KPI PI 3B. | One building energy figure feeding both BEI and PI 3B. | PI 3B parameters are keyed manually. Energy BEI is a separate screen. | Accept as Phase 1, or raise as a Phase 2 integration. | No UAT case expects automatic PI 3B fill. |
| GAP-KPA-04 | M02 | MPV is keyed each month (defaults to previous month). It is not read from the contract module. | TBC — contract-linked MPV (assumption G01). | Manual MPV. | Confirm with the customer. | Covered as manual entry. |
| GAP-KPA-05 | M02 | Parameters marked GEMS / `gems_hook` are **not** auto-fetched. | TBC — GEMS+ automation (assumption G09). | All parameters are manual. Screen text: “Marked for GEMS+ automation. Enter the value manually for now.” | Accept Phase 1 manual capture. | Do not fail UAT for missing automation. |
| GAP-KPA-06 | M02 | Completing a month requires **every** snapshotted PI to be submitted (21 on the seeded template). | TBC whether UAT will complete all 21 or accept Open months plus sample PIs. | Status becomes Completed only at n / n. | Decide in kick-off. A reduced UAT site structure is the practical option. | KPA-UAT-027 is TBC on volume. |
| GAP-ENR-01 | M03 | Daily grid uses blank for uncovered days; Monthly Summary paints **0.00** in a meter cell with no data. | Incomplete months should not look like zero consumption. | Daily is correct (blank). Monthly table uses 0.00. | Brief testers. Consider blank on the monthly table later. | ENR-UAT-013 records both behaviours. |
| GAP-ENR-02 | M03 | Chiller hours and maximum demand are not validated for negative values. Daily notes are not blocked for future dates. | TBC whether the business requires the same validation as cumulative kWh. | Cumulative kWh is validated; chiller/MD are weaker. | Confirm with the customer. | Not failed unless the customer states the rule. |
| GAP-ENR-03 | M03 | Site Admin (19), PI Entry (31) and KPI Viewer (32) have Energy API capabilities in code/docs, but shipped navigation grants only Administrator (1), Utility Reader (18) and KPI Admin (30). | TBC who should see Energy Monitoring. | Menu = 1, 18, 30. | Confirm intended roles before UAT. | ENR-UAT-022 records actual menu vs API. |
| GAP-ENR-04 | M03 | Per-meter remark and photo columns exist on the reading table but have no daily-grid fields. | TBC whether a photo of the meter is required. | Not offered on the screen. | Out of this UAT unless the customer requires it. | Not Covered. |

---

## 11. UAT statistics

| Module | Positive | Validation / Negative | Calculation | Role / Access | E2E | Total |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| M01 Waste Management | 15 | 9 | 5 | 2 | 1 | 32 |
| M02 KPI & APD | 15 | 9 | 5 | 6 | 1 | 36 |
| M03 Energy & Utility | 13 | 6 | 4 | 2 | 1 | 26 |
| Common | 5 | 2 | 0 | 1 | 0 | 8 |
| TOTAL | 48 | 26 | 14 | 11 | 3 | 102 |

Case-type mapping used above: **Validation / Negative** includes mandatory fields, invalid data, empty states and blocked actions. **Calculation** includes balance, dashboard/JKR reconciliation, KPI datasets and energy/BEI arithmetic. Each case is counted once.

---

## 12. UAT readiness checklist

- [ ] M01 core business flow covered
- [ ] M02 core business flow covered
- [ ] M03 core business flow covered
- [ ] Mandatory validations covered
- [ ] Negative scenarios covered
- [ ] Waste balance reconciled
- [ ] Waste dashboard reconciled
- [ ] JKR report reconciled
- [ ] KPI calculations manually verified
- [ ] KPI role/access scenarios covered
- [ ] Energy monthly totals reconciled
- [ ] BEI manually verified
- [ ] End-to-end scenarios completed
- [ ] Requirement gaps documented
- [ ] Test data prepared

Facilitator extras before the session:

- [ ] UAT accounts named and passwords issued
- [ ] UAT Premise A / UAT Site A created and granted to those accounts
- [ ] Waste type SW410 (or agreed code) active on UAT Premise A
- [ ] Hidden waste URLs communicated
- [ ] Decision on GAP-KPA-06 (21 PI submit vs reduced structure)
- [ ] Decision on whether Opening Balance and JKR must appear in the menu before sign-off
- [ ] Testers briefed that energy is cumulative readings and that waste short-collection leaves remainder in stock, not in Pending

---

## 13. Severity guide (for defects)

| Severity | Use when the failed case means… |
| --- | --- |
| Critical | The core business chain cannot be completed (cannot record waste, cannot dispose, cannot create a KPI month, cannot save a meter reading, or an official total is wrong). |
| High | A major function or official calculation/report is wrong, or a named role cannot do its job. |
| Medium | The function works only partially or causes significant operational inconvenience. |
| Low | Display / wording / empty-state issue with no material effect on official figures. |

---

## 14. Sign-off

| Role | Name | Date | Result (Accepted / Accepted with reservations / Rejected) | Signature |
| --- | --- | --- | --- | --- |
| Customer — Waste process owner | | | | |
| Customer — KPI / APD process owner | | | | |
| Customer — Energy process owner | | | | |
| GFM UAT facilitator | | | | |
| GEMS project lead | | | | |

Reservations must quote UAT IDs and gap IDs.

---

*End of GEMS UAT Script v1.0 — M01 / M02 / M03. Import `GEMS_UAT_M01_M02_M03_Test_Cases.csv` into the official workbook (UTF-8 with BOM). Do not invent functions that are not listed here.*
