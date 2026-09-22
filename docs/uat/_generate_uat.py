#!/usr/bin/env python3
"""Generate GEMS M01-M03 UAT markdown + Excel-ready CSV from structured cases."""
from __future__ import annotations

import csv
from pathlib import Path

OUT = Path(__file__).resolve().parent
COLS = [
    "UAT ID", "Module", "Function / Submodule", "Test Scenario",
    "Preconditions", "Test Steps", "Test Data", "Expected Result",
    "Actual Result", "Status", "Severity", "Remarks", "Case Type",
]

# case type: Positive | Validation | Calculation | Role | E2E


def C(uid, module, fn, scenario, pre, steps, data, expected, severity, remarks, ctype):
    return {
        "UAT ID": uid,
        "Module": module,
        "Function / Submodule": fn,
        "Test Scenario": scenario,
        "Preconditions": pre,
        "Test Steps": steps,
        "Test Data": data,
        "Expected Result": expected,
        "Actual Result": "________________",
        "Status": "Not Tested",
        "Severity": severity,
        "Remarks": remarks,
        "Case Type": ctype,
    }


PRE_W = (
    "Tester is logged in with a Waste User, Waste Officer or Administrator account "
    "that can see Waste Management. Dedicated UAT premise (UAT Premise A) is selected. "
    "At least one active scheduled waste type is available for that premise "
    "(prefer SW410 if listed). Do not use a live operational premise."
)
PRE_K_ADMIN = (
    "Logged in as KPI Admin (role 30) or Administrator. Dedicated UAT site selected. "
    "Shared KPI template (4 groups / 21 PIs) is active."
)
PRE_K_ENTRY = (
    "Logged in as PI Entry (role 31) assigned to UAT PI set for UAT Site A. "
    "September 2026 evaluation already exists for that site."
)
PRE_E = (
    "Logged in as Utility Reader (role 18), KPI Admin (30) or Administrator. "
    "UAT Site A selected. Incoming No.1 (and Incoming No.2 if seeded) exist."
)

cases: list[dict] = []

# =============================================================================
# COMMON
# =============================================================================
cases += [
    C("COM-UAT-001", "Common", "Navigation",
      "Verify a business user can open Waste Management, KPI & APD and Energy Monitoring from the GEMS sidebar and reach the intended working pages.",
      "User holds Administrator, or a role granted the relevant module menus.",
      "1. Login to GEMS.\n2. In the sidebar, expand Waste Management and open each visible child (Waste Dashboard, Waste Generation, Pending Disposal, Waste Record).\n3. Expand KPI & APD and open each visible child allowed for the role.\n4. Expand Energy Monitoring and open Daily Electricity, Monthly Summary and Building Energy Index.",
      "UAT Admin or the role under test.",
      "Each listed page opens without an error page. Sidebar parent labels are Waste Management, KPI & APD and Energy Monitoring. Waste children include Waste Dashboard, Waste Generation, Pending Disposal and Waste Record. KPI children include KPI / APD Summary, Monthly Evaluation, KPI History, and (Admin only) KPI Structure and PI Assignment. Energy children are Daily Electricity, Monthly Summary and Building Energy Index.",
      "Critical",
      "Opening Balance, JKR Waste Reports, Waste Setup, Execute Disposal and PI Entry are implemented but hidden from the sidebar (URL / in-page links only). See GAP-WST-01 and GAP-KPA-01.",
      "Positive"),
    C("COM-UAT-002", "Common", "Page identity",
      "Verify each M01–M03 working page shows the correct browser title and on-screen page heading so users know where they are.",
      "COM-UAT-001 passed for the role under test.",
      "1. Open each page listed in Test Data.\n2. Record the browser tab title and the page heading (H1).\n3. Confirm no breadcrumb trail is shown.",
      "Waste Generation → title 'GEMS 2.0 - Waste Generation', H1 'Waste Generation'. Pending Disposal → 'GEMS 2.0 - Pending Disposal' / 'Pending Disposal'. Waste Records → 'GEMS 2.0 - Waste Records' / 'Waste Record'. Waste Dashboard → 'GEMS 2.0 - Waste Dashboard' / 'Waste Dashboard'. KPI / APD Summary → 'GEMS 2.0 - KPI / APD Dashboard' / 'KPI / APD Dashboard'. Monthly Evaluation → 'GEMS 2.0 - Monthly KPI Evaluation' / 'Monthly KPI Evaluation'. KPI History → 'GEMS 2.0 - KPI History' / 'KPI History'. Daily Electricity → 'GEMS 2.0 - Daily Electricity' / 'Daily Electricity'. Monthly Summary → 'GEMS 2.0 - Monthly Electricity Summary' / matching H1. BEI → 'GEMS 2.0 - Building Energy Index' / matching H1.",
      "Every listed title and heading matches. Breadcrumb is not present (implementation gap — page title is the identifier).",
      "Low",
      "GAP-COM-01: breadcrumbs are not implemented on these pages.",
      "Positive"),
    C("COM-UAT-003", "Common", "Save / Cancel / Back",
      "Verify a user can abandon an incomplete record without saving, and can return to the previous list from a detail page.",
      "User can open Waste Generation and Pending Disposal.",
      "1. Open Waste Generation.\n2. Enter a weight but do not save. Click Pending Disposal (or navigate away).\n3. Return to Waste Generation and confirm the unsaved weight is cleared.\n4. From Pending Disposal, open Execute Disposal on any pending row (or open Waste Generation then Pending Disposal).\n5. Click Back on Execute Disposal.",
      "Any unsaved draft values; no save.",
      "Unsaved generation is not written to Pending Disposal or Waste Records. Back from Execute Disposal returns to Pending Disposal.",
      "Medium",
      "Cancel on Waste Generation is navigation, not an API cancel. Do not confuse with deleting a saved pending record (WST-UAT-009).",
      "Positive"),
    C("COM-UAT-004", "Common", "Empty state",
      "Verify list and dashboard pages show a clear empty message when the selected premise/site and filters have no records.",
      "A clean UAT premise/site with no M01–M03 transactions for the selected period, or filters that match nothing.",
      "1. Open Waste Generation for a premise with no recent generation — check the recent list.\n2. Open Pending Disposal with Status = Pending and a date range that has none — check list and summary.\n3. Open Waste Records with filters that match nothing.\n4. Open Monthly Evaluation for a year with no months (or a new site).\n5. Open KPI History for a year range with no evaluations.\n6. Open Daily Electricity for a month with no readings.",
      "UAT empty site/premise; filters that return zero rows.",
      "Pages do not crash. User sees the implemented empty text, including: 'No generation records yet.'; 'No pending disposal records.' / 'Nothing is pending disposal'; 'No waste records yet.' or 'No records match the current filters.'; 'No evaluation months yet.'; 'No evaluation months in the selected range.'; daily grid still renders calendar days with blank consumption (not invented zeros) when no readings exist.",
      "Medium",
      "Empty daily energy days show em-dash, not 0.00. Monthly energy table may show 0.00 for a meter with no data — see GAP-ENR-01.",
      "Validation"),
    C("COM-UAT-005", "Common", "Validation and success messages",
      "Verify mandatory-field and success messages are shown in business language (not a blank failure) when a user saves incorrectly and when a save succeeds.",
      "User can create waste generation and PI entry (or Admin can).",
      "1. On Waste Generation, click Save with waste type and weight blank.\n2. Fill valid data and Save.\n3. On a PI Entry page (Admin), click Submit with a required parameter blank.\n4. Enter a valid value, Save Draft, then observe the success notification.",
      "Blank mandatory fields; then valid WST-GEN-01 data / a valid PI parameter.",
      "Incomplete save is blocked and a readable warning is shown (Waste: 'Select a waste type.' / 'Enter a waste weight greater than zero.'). Successful generation shows 'Waste generation recorded and is now pending collection.' PI submit without a required value shows 'Enter a value for \"{parameter label}\" before submitting.' Successful PI save/submit shows the corresponding success notification.",
      "High",
      "Record the exact on-screen wording. Generic 'Something went wrong' is a fail.",
      "Validation"),
    C("COM-UAT-006", "Common", "Data persistence",
      "Verify a saved business record is still present after browser refresh and after leaving the module and returning.",
      "WST-UAT-001 or equivalent saved generation exists.",
      "1. Save a waste generation (or use WST-GEN-01).\n2. Refresh the browser on Waste Generation.\n3. Open Pending Disposal and find the record.\n4. Logout, login again, and search the same record.",
      "WST-GEN-01 (reference captured during WST-UAT-001).",
      "The same waste reference, premise, waste type, date and weight are still shown. The record was not duplicated by refresh.",
      "High",
      "If refresh creates a second record, fail as duplicate-submit defect.",
      "Positive"),
    C("COM-UAT-007", "Common", "Access denied",
      "Verify a user with no Waste, KPI or Energy role cannot open those modules from the sidebar, and a direct URL does not allow them to change data.",
      "A GEMS user exists who is not Administrator, Waste User/Officer, KPI Admin/PI Entry/KPI Viewer, Utility Reader or KPI Admin.",
      "1. Login as the unauthorised user.\n2. Confirm Waste Management, KPI & APD and Energy Monitoring are not in the sidebar.\n3. Paste a direct URL for p_waste_generation, p_kpa_structure and p_energy_daily.\n4. Attempt to save if the page renders.",
      "UAT-NOACCESS account (TBC — Business confirmation required).",
      "Sidebar does not show the three modules. Direct URL either redirects, shows an access message, or the save is rejected with 'You are not allowed to create or change waste records.' / 'You are not allowed to change the KPI structure.' / 'You are not allowed to record meter readings.' No new official record is created.",
      "High",
      "Account list is TBC. Do not use this case to test general GEMS user administration screens.",
      "Role"),
    C("COM-UAT-008", "Common", "Date and numeric format",
      "Verify dates and quantities display in a consistent business format so dashboard, lists and forms can be reconciled.",
      "At least one waste generation in kg with 3 decimal places and one energy reading with 2 decimal places exist.",
      "1. Open Waste Generation / Pending Disposal / Dashboard for the test record.\n2. Confirm weight is shown in kg (3 decimal places where entered).\n3. Open Daily Electricity and confirm cumulative/consumption use 2 decimal places.\n4. Open KPI summary and confirm APD amounts are in RM with 2 decimal places.",
      "WST-GEN-02 (12.500 kg); ENR daily reading 10000.00; any KPI month with APD figures.",
      "Waste quantities are kg on generation/pending (not silently converted to MT on those screens). Energy kWh shows 2 decimals. APD shows 2 decimals. Dates use the date picker / ISO calendar date (YYYY-MM-DD) on forms and a readable date on lists. 1 MT = 1,000 kg is stated on the waste dashboard unit note.",
      "Low",
      "Fifth Schedule Waste Record form still uses MT or kg — do not treat that as a generation-screen defect.",
      "Positive"),
]

# =============================================================================
# M01 WASTE
# =============================================================================
cases += [
    C("WST-UAT-001", "M01 Waste Management", "Waste Generation",
      "Verify a facility officer can record scheduled waste generated at a premise and that the record is accepted into the official register.",
      PRE_W,
      "1. Login to GEMS.\n2. Open Waste Management > Waste Generation.\n3. Select UAT Premise A.\n4. Select waste type SW410 (or the agreed UAT waste type).\n5. Enter waste weight 50.000 kg.\n6. Set Date to 15/09/2026 (or today if 15/09/2026 is in the future on the test day).\n7. Enter remarks 'UAT WST-GEN-01'.\n8. Save.\n9. Record the waste reference shown in the recent list.",
      "Premise: UAT Premise A. Waste type: SW410. Weight: 50.000 kg. Date: 15/09/2026 (or today). Remarks: UAT WST-GEN-01. Capture system reference as WST-REF-01.",
      "Save succeeds. Notification: 'Waste generation recorded and is now pending collection.' Recent list shows the new row with matching waste type, date, registered weight 50.000 kg and status Pending Collection. A waste reference is allocated. Record is FINAL in the official register (it counts toward balance).",
      "Critical",
      "Generation saves immediately as Final + Pending Collection. There is no draft and no approval step. Date cannot be future — if UAT runs before 15/09/2026 use today and record the actual date used.",
      "Positive"),
    C("WST-UAT-002", "M01 Waste Management", "Waste Generation",
      "Verify waste generation cannot be saved when mandatory business information is missing.",
      PRE_W,
      "1. Open Waste Generation.\n2. Clear or leave Waste type unselected, leave Waste weight blank, and clear Date if possible.\n3. Attempt Save.\n4. Select a waste type only, leave weight blank, attempt Save.\n5. Enter weight 50.000 and leave waste type blank, attempt Save.",
      "Blank waste type; blank weight; blank date.",
      "No new pending record is created. User is shown the implemented messages: 'Select a waste type.'; 'Enter a waste weight greater than zero.'; 'Enter the waste generation date.' as applicable.",
      "High",
      "Mandatory fields on this screen: Premise, Waste type, Waste weight (kg), Date. Remarks are optional.",
      "Validation"),
    C("WST-UAT-003", "M01 Waste Management", "Waste Generation",
      "Verify a facility officer cannot record a zero or negative generated weight.",
      PRE_W,
      "1. Open Waste Generation and select UAT Premise A and SW410.\n2. Enter weight 0 and a valid date. Save.\n3. Enter weight -10. Save.\n4. Enter weight 50.000 and Save to confirm the form still works afterwards.",
      "Weight 0; weight -10; then valid 50.000 kg (discard or cancel this extra row if created — or use it as WST-GEN-extra).",
      "0 and -10 are rejected. Message includes 'Enter a waste weight greater than zero.' No official pending row is created for the invalid attempts.",
      "High",
      "Minimum accepted weight is 0.001 kg.",
      "Validation"),
    C("WST-UAT-004", "M01 Waste Management", "Waste Generation",
      "Verify generated waste cannot be backdated into the future.",
      PRE_W,
      "1. Open Waste Generation.\n2. Select premise and waste type.\n3. Enter weight 10.000 kg.\n4. Set Date to tomorrow. Save.",
      "Date = tomorrow. Weight 10.000 kg.",
      "Save is rejected. Message: 'The waste generation date cannot be in the future.' No pending record is created.",
      "High",
      "Date control also has max = today.",
      "Validation"),
    C("WST-UAT-005", "M01 Waste Management", "Waste Generation",
      "Verify a decimal scheduled-waste quantity can be recorded and displayed at gram precision.",
      PRE_W,
      "1. Open Waste Generation.\n2. Select UAT Premise A and SW410.\n3. Enter 12.500 kg, date today, remarks 'UAT WST-GEN-02'.\n4. Save.\n5. Confirm the recent list and Pending Disposal show 12.500 kg.",
      "WST-GEN-02: 12.500 kg, today, SW410, remarks UAT WST-GEN-02. Capture reference WST-REF-02.",
      "Record saves. Registered weight displays as 12.500 kg on Generation recent list and Pending Disposal. Balance increases by 12.500 kg (not rounded to a whole kilogram).",
      "Medium",
      "Use this row later only if isolation is still clean; otherwise cancel it after the check.",
      "Positive"),
    C("WST-UAT-006", "M01 Waste Management", "Pending Disposal",
      "Verify newly generated scheduled waste appears in Pending Disposal with the correct premise, waste code and outstanding weight.",
      "WST-UAT-001 completed. WST-REF-01 known.",
      "1. Open Waste Management > Pending Disposal.\n2. Select UAT Premise A.\n3. Set Status to Pending.\n4. Search using the waste reference from WST-UAT-001.\n5. Read the row and the Pending by waste-type summary.",
      "WST-REF-01; 50.000 kg; SW410; UAT Premise A.",
      "The row is listed. Waste type / SW code matches SW410. Generated date matches. Registered (kg) = 50.000. Disposed (kg) is blank or 0. Status badge = Pending Collection. Pending Weight KPI includes this 50.000 kg. Summary table shows SW410 with pending kg including 50.000.",
      "Critical",
      "Screen title is Pending Disposal; status text is Pending Collection. Both are correct implemented labels.",
      "Positive"),
    C("WST-UAT-007", "M01 Waste Management", "Pending Disposal",
      "Verify several generation records for the same premise remain separate and the pending weight is the sum of outstanding rows.",
      "WST-REF-01 (50.000 kg) and WST-REF-02 (12.500 kg) both still Pending for the same premise and waste type. No other pending SW410 on that premise, or tester records the before/after totals.",
      "1. Note Pending Weight and SW410 summary kg before this check (or immediately after creating the two rows).\n2. Confirm both references appear as separate rows.\n3. Add the registered kg of all pending SW410 rows and compare with the SW410 summary Pending (kg) and the Pending Weight card.",
      "WST-REF-01 50.000 + WST-REF-02 12.500 = 62.500 kg (if only these two pending SW410 rows).",
      "Each generation remains its own row with its own reference. Pending Weight and SW410 Pending (kg) equal the sum of pending registered weights for that filter (62.500 kg if only these two).",
      "High",
      "If other pending rows exist, reconcile against the full filtered list — do not force 62.500.",
      "Positive"),
    C("WST-UAT-008", "M01 Waste Management", "Pending Disposal",
      "Verify a pending generation can be corrected (waste type, weight, date, remarks) before collection.",
      "A dedicated pending row exists (create WST-GEN-03: 20.000 kg, remarks UAT-EDIT-BEFORE) that will not be used in the 100/50/30 balance case.",
      "1. Open Pending Disposal.\n2. On the dedicated pending row, click Edit.\n3. Change weight to 25.000 kg, date if needed, remarks to 'UAT-EDIT-AFTER'.\n4. Save.\n5. Re-open the row / refresh the list.",
      "Before: 20.000 kg. After: 25.000 kg, remarks UAT-EDIT-AFTER. Optional correction reason.",
      "Save succeeds. List shows 25.000 kg and updated remarks. Pending summary kg increases by 5.000 versus the pre-edit value. Status remains Pending Collection. Official balance uses 25.000 kg, not 20.000 kg.",
      "High",
      "Edit is allowed only while Pending. After disposal the generation weight is locked.",
      "Positive"),
    C("WST-UAT-009", "M01 Waste Management", "Pending Disposal",
      "Verify a pending generation can be cancelled so it leaves the collection queue and no longer affects the premise balance, while remaining on the audit trail.",
      "A dedicated pending row exists that is not part of the official E2E transaction (create WST-GEN-04: 8.000 kg if needed).",
      "1. Note Current Balance / pending kg for the waste type.\n2. On the dedicated row, click Delete.\n3. Leave reason blank and confirm — expect block.\n4. Enter reason 'UAT cancel pending — not collected'.\n5. Confirm delete.\n6. Refresh Pending Disposal (Status Pending and Status All).\n7. Open Waste Records and search the reference.",
      "Reason required: 'UAT cancel pending — not collected'. Weight 8.000 kg.",
      "Blank reason is rejected: 'Enter the reason for deleting this pending waste record.' After confirm, row disappears from Pending. Modal text advised the row is cancelled and kept for audit. Waste Records / Status All can still find it as cancelled / not pending. Premise balance no longer includes the 8.000 kg. Notification confirms the cancel.",
      "High",
      "The row is not physically removed. Cancelled and Draft records are excluded from official totals.",
      "Positive"),
    C("WST-UAT-010", "M01 Waste Management", "Disposal",
      "Verify a facility officer can record a full collection/disposal equal to the registered weight, with both mandatory disposal photographs.",
      "A pending generation exists for full disposal (create WST-GEN-05: 40.000 kg, date today, remarks UAT-FULL-DISP). Two image files are available.",
      "1. Open Pending Disposal and find WST-GEN-05.\n2. Click Execute disposal.\n3. Confirm Section A shows the same reference, waste type, generated date, 40.000 kg and premise.\n4. Attach During disposal image and After disposal image.\n5. Set Disposal date = today (on or after generated date).\n6. Enter Actual disposed weight 40.000 kg.\n7. Save.\n8. Return to Pending Disposal, Status = Pending, then Status = Disposed.",
      "WST-GEN-05: registered 40.000 kg; actual 40.000 kg; two JPG/PNG images; disposal date = today.",
      "Save succeeds. Message: 'Disposal recorded. The waste record is now marked as Disposed.' User is returned to Pending Disposal. Row is no longer Pending Collection. Status = Disposed. Disposed (kg) = 40.000. A linked disposal transaction exists. Official balance decreases by 40.000 kg.",
      "Critical",
      "There is no approval step. One disposal per generation record.",
      "Positive"),
    C("WST-UAT-011", "M01 Waste Management", "Disposal",
      "Verify disposal cannot be completed without both during and after photographs.",
      "A pending generation exists (create WST-GEN-06: 5.000 kg if needed).",
      "1. Open Execute Disposal for the pending row.\n2. Enter disposal date and actual weight 5.000 kg but attach no images. Attempt Save.\n3. Attach only the During image. Attempt Save.\n4. Attach only the After image (remove During if needed). Attempt Save.",
      "Images missing; weight 5.000 kg.",
      "Save remains blocked or is rejected. Messages: 'Attach the during disposal image before saving.' and/or 'Attach the after disposal image before saving.' Record stays Pending Collection. No disposal transaction is created.",
      "High",
      "Save control is disabled until both images are attached.",
      "Validation"),
    C("WST-UAT-012", "M01 Waste Management", "Disposal",
      "Verify disposal date cannot be in the future and cannot be earlier than the generation date.",
      "Pending generation dated today or a known past date (use WST-GEN-06 if still pending).",
      "1. Open Execute Disposal.\n2. Attach both images.\n3. Set disposal date to tomorrow, actual weight equal to registered. Save.\n4. Set disposal date to one day before the generated date. Save.\n5. Set disposal date = generated date (or today). Do not save if this would consume the E2E row — cancel out after the messages are confirmed, or use a disposable pending row.",
      "Future date; date before generation; valid date = generated date.",
      "Future date rejected: 'The disposal date cannot be in the future.' Earlier-than-generation rejected: 'The disposal date cannot be earlier than the generation date.' Record remains pending until a valid save.",
      "High",
      "Use a disposable pending row so the E2E 50 kg row is not accidentally disposed here.",
      "Validation"),
    C("WST-UAT-013", "M01 Waste Management", "Disposal",
      "Verify a disposal that would take more scheduled waste than the premise holds is rejected.",
      "Known current balance for UAT Premise A + SW410 (from Dashboard Current Balance or Opening + net). A pending generation exists.",
      "1. Open Waste Dashboard, filter premise + SW410, note Current Balance (kg).\n2. Open Execute Disposal on a pending SW410 row.\n3. Attach both images.\n4. Enter Actual disposed weight = Current Balance + 100 kg.\n5. Save.",
      "Actual qty = current SW410 balance + 100 kg.",
      "Save is rejected. Message: 'Disposal quantity is not supported by the available balance for this event date. Review the quantity and earlier waste records.' Generation remains Pending Collection. Balance unchanged.",
      "Critical",
      "Actual disposed may exceed the registered weight of this one row if the premise holds enough of that SW code (opening + earlier production). This test uses an amount larger than the whole premise balance.",
      "Validation"),
    C("WST-UAT-014", "M01 Waste Management", "Disposal",
      "Verify a short collection (actual disposed less than registered) marks the generation as Disposed and leaves the remainder in the premise balance — not as a new pending row.",
      "Use the official E2E generation WST-REF-01 (50.000 kg) only if the controlled balance case is being executed now; otherwise create WST-GEN-07: 50.000 kg pending.",
      "1. Note Current Balance for the premise + SW code.\n2. Execute disposal on the 50.000 kg pending row.\n3. Attach both images.\n4. Disposal date today.\n5. Actual disposed weight 30.000 kg.\n6. Save.\n7. Open Pending Disposal Status = Pending and search the reference.\n8. Switch Status to Disposed and find the reference.\n9. Recalculate Current Balance.",
      "Registered 50.000 kg. Actual disposed 30.000 kg. Remainder 20.000 kg.",
      "Disposal saves. Generation status becomes Disposed (not left Pending). No second pending row is created for the 20.000 kg shortfall. Registered weight on the produced record remains 50.000 kg. Disposed (kg) on the list = 30.000. Premise current balance decreases by 30.000 kg only (remainder 20.000 kg stays in stock). Screen may note that the remainder stays in the premise balance.",
      "Critical",
      "This is the implemented partial-disposal rule. Do not expect a split pending record. One disposal per generation — the 20 kg cannot be disposed against the same generation again.",
      "Calculation"),
    C("WST-UAT-015", "M01 Waste Management", "Disposal",
      "Verify consignment note and receipt are optional and can be stored with reference numbers when provided.",
      "A disposable pending row exists (create WST-GEN-08: 6.000 kg). Optional PDF/JPG files available.",
      "1. Execute disposal.\n2. Attach both mandatory images.\n3. Attach a consignment note and enter reference 'CN-UAT-001'.\n4. Attach a consignment receipt and enter reference 'CR-UAT-001'.\n5. Enter actual = registered. Save.\n6. Open the record from Waste Records / View and confirm the documents and references are visible.",
      "CN-UAT-001; CR-UAT-001; two evidence images; optional note/receipt files.",
      "Disposal succeeds with or without consignment files. When provided, the files and reference numbers are stored on the disposal record and can be seen when the record is opened.",
      "Medium",
      "Consignment documents are optional. Evidence images are not.",
      "Positive"),
    C("WST-UAT-016", "M01 Waste Management", "Disposal",
      "Verify a generation that has already been disposed cannot be disposed a second time.",
      "WST-UAT-010 or WST-UAT-014 already disposed a row. Tester has that reference.",
      "1. Open Pending Disposal, Status = Disposed, find the disposed reference.\n2. Confirm Execute disposal is not offered (or open p_waste_dispose?id= of that record if the action is hidden).\n3. If the page opens, attempt to save another disposal.",
      "Already-disposed reference from WST-GEN-05 or WST-REF-01.",
      "Execute disposal is not available for Disposed rows. If the URL is opened, the system states 'This waste record is already disposed and can no longer be changed.' No second disposal transaction is created.",
      "High",
      "Remainder after a short collection is not disposed through the same generation row.",
      "Validation"),
    C("WST-UAT-017", "M01 Waste Management", "Waste Records",
      "Verify a user can find historical waste transactions by reference, date, waste type, premise and status.",
      "WST-REF-01 and at least one disposed record exist.",
      "1. Open Waste Management > Waste Record.\n2. Search the waste reference from the E2E generation.\n3. Filter premise = UAT Premise A, waste type = SW410, date from/to covering 15/09/2026.\n4. Filter type Produced, then Disposed.\n5. Filter status Final.\n6. Open (View) the produced row and the disposed row.",
      "WST-REF-01; UAT Premise A; SW410; period covering the test dates.",
      "Search returns the matching row(s). Filters reduce the list to matching premise, SW code, dates, type and status. View shows the same reference, dates, quantities and, for the lifecycle pair, the linked generation/disposal panel. Final produced and final disposed rows are both listed. Cancelled rows do not appear as Final.",
      "High",
      "Waste Record is the list. The Fifth Schedule form is the view/edit page and is not in the sidebar.",
      "Positive"),
    C("WST-UAT-018", "M01 Waste Management", "Opening Balance",
      "Verify a Waste Officer / Administrator can record the starting quantity held at a premise so later generation and disposal can be reconciled.",
      "Logged in as Waste Officer or Administrator. Page is reached via URL p_waste_opening_balance (not in the sidebar). Prefer a waste type reserved for the controlled balance case, or perform this BEFORE other SW410 movements on a clean UAT premise.",
      "1. Open p_waste_opening_balance.\n2. Add / save opening balance.\n3. Select UAT Premise A, SW410, as-at date 31/08/2026, quantity 100, unit kg.\n4. Save.\n5. Refresh the opening-balance list.\n6. Open Waste Dashboard for September 2026, same premise and SW410, and read Opening Balance.",
      "UAT-OB-01: Premise UAT Premise A; SW410; as-at 31/08/2026; 100.000 kg.",
      "Save succeeds. List shows 100.000 kg as at 31/08/2026. Dashboard Opening Balance for September 2026 = 100.000 kg (if no Final movements after 31/08/2026 and before 01/09/2026). Opening is not listed as Produced.",
      "Critical",
      "GAP-WST-01: Opening Balance is hidden from the sidebar. Zero is allowed; negative is not. One opening row per premise + SW code (save updates the same row).",
      "Positive"),
    C("WST-UAT-019", "M01 Waste Management", "Waste Balance",
      "Verify current scheduled-waste balance equals Opening + Generated − Disposed using a controlled dataset.",
      "Clean UAT Premise A + SW410 for the period, or tester records any extra Final SW410 movements and includes them. Recommended controlled set: Opening 100.000 kg (WST-UAT-018); Generated 50.000 kg (WST-REF-01); Disposed actual 30.000 kg (WST-UAT-014). WST-REF-02 and other extras must be cancelled or excluded from this SW code.",
      "1. Confirm opening 100.000 kg as at 31/08/2026.\n2. Confirm one Final produced 50.000 kg in September.\n3. Confirm one Final disposed 30.000 kg in September.\n4. Manually calculate 100 + 50 − 30 = 120.\n5. Open Waste Dashboard, month September 2026, premise UAT Premise A, SW410.\n6. Read Opening, Produced, Disposed, Current Balance.\n7. Open the balance table row for that premise + SW410.",
      "Opening 100.000 kg. Produced 50.000 kg. Disposed 30.000 kg. Expected closing / current = 120.000 kg. Net movement = 20.000 kg.",
      "Dashboard Opening = 100.000 kg. Produced = 50.000 kg. Disposed = 30.000 kg. Net Movement = 20.000 kg. Current Balance / Closing = 120.000 kg. Balance table Opening + Produced − Disposed = Closing. Cancelled rows (if any) are excluded. Units kg; 120.000 kg = 0.120 MT if MT is also shown.",
      "Critical",
      "Formula (implemented): Current = Opening kg + sum(Final Produced kg) − sum(Final Disposed kg), with opening applied as the starting point and only Final rows after the opening as-at date. Draft and Cancelled are excluded. If extra rows exist, replace 120 with the testers' own controlled arithmetic and attach the working.",
      "Calculation"),
    C("WST-UAT-020", "M01 Waste Management", "Waste Dashboard",
      "Verify every dashboard figure for the controlled period can be traced to the underlying waste transactions.",
      "Same controlled September 2026 / UAT Premise A / SW410 dataset as WST-UAT-019. Other premises may exist — filter to the UAT premise.",
      "1. Open Waste Dashboard.\n2. Set Month = September, Year = 2026, Premise = UAT Premise A, Waste type = SW410.\n3. Copy Opening, Produced, Disposed, Net Movement, Current Balance, Final Transactions, Pending Disposal kg.\n4. Open Waste Records with the same premise, SW code and September dates, Status Final — sum Produced and Disposed kg.\n5. Open Pending Disposal Status = Pending for that premise/SW — sum registered kg.\n6. Click the Pending Disposal KPI and confirm it opens the pending page.\n7. Review Produced/Disposed/Pending/Closing by SW and by premise tables.",
      "Expected (isolated SW410): Opening 100.000; Produced 50.000; Disposed 30.000; Net 20.000; Current 120.000; Final Transactions = 2 (one Produced + one Disposed) if no other Final SW410 rows; Pending Disposal kg = 0 after WST-UAT-014.",
      "Each KPI equals the independently summed source records for the same filter. Pending Disposal kg is the current pending collection total (not limited to the selected month — if this differs from 0 because of other pending rows, record the actual pending list). Click-through opens Waste Records or Pending Disposal with the filter applied. Presence of a number without reconciliation is a fail.",
      "Critical",
      "Pending Disposal kg and pending-by-SW are current outstanding, not period-bound. Draft count on the dashboard is all drafts, not period-bound. Record as observation if that surprises the business (GAP-WST-03).",
      "Calculation"),
    C("WST-UAT-021", "M01 Waste Management", "JKR Reporting",
      "Verify a Waste Officer / Administrator can generate a JKR scheduled-waste report version for a premise and period and open the PDF and Excel files.",
      "Logged in as Waste Officer or Administrator. Page via URL p_waste_report (not in the sidebar). Controlled September data exists.",
      "1. Open p_waste_report.\n2. Click Generate.\n3. Select UAT Premise A, Period from 01/09/2026, Period to/As-at 30/09/2026, include transaction appendix.\n4. Preview.\n5. Generate version.\n6. Open PDF and Open Excel from the version list.",
      "Premise UAT Premise A; 01/09/2026–30/09/2026; appendix on.",
      "Preview shows a summary table. Generate creates a new version number. Success: 'JKR report version generated. Historical versions are unchanged.' PDF title reflects JKR / GEMS Scheduled Waste Register. Excel and PDF open. Earlier versions remain listed.",
      "High",
      "GAP-WST-01: JKR Reports is hidden from the sidebar.",
      "Positive"),
    C("WST-UAT-022", "M01 Waste Management", "JKR Reporting",
      "Verify JKR report opening, produced, disposed and closing quantities match the official Final transactions for the same premise and period.",
      "WST-UAT-021 version generated. WST-UAT-019 expected figures known.",
      "1. Open the generated PDF/Excel.\n2. Locate the SW410 (or UAT waste type) summary row.\n3. Compare Opening, Produced, Disposed, Closing with the dashboard and with the testers' arithmetic.\n4. If appendix is included, confirm the 50.000 kg produced and 30.000 kg disposed lines appear.\n5. Confirm Excel note Opening + Produced − Disposed = Closing holds on the SW410 row.",
      "Expected SW410: Opening 100.000 kg; Produced 50.000 kg; Disposed 30.000 kg; Closing 120.000 kg.",
      "Report SW410 totals equal the dashboard and the manual 100 + 50 − 30 = 120 working. Appendix lines match the Final transactions. Cancelled rows are absent. Closing = Opening + Produced − Disposed.",
      "Critical",
      "Official totals use Final records only.",
      "Calculation"),
    C("WST-UAT-023", "M01 Waste Management", "JKR Reporting",
      "Verify JKR submission details can be recorded against a generated report version for customer/JKR acknowledgement.",
      "A JKR report version exists (WST-UAT-021). User is Waste Officer or Administrator.",
      "1. On p_waste_report, select the UAT version.\n2. Record JKR Submission.\n3. Enter submission date today, Submitted To 'JKR UAT', channel if listed, reference 'JKR-UAT-SUB-01', notes 'UAT submission'.\n4. Save (acknowledgement file optional).\n5. Re-open the version.",
      "Submitted To: JKR UAT. Reference: JKR-UAT-SUB-01. Date: today.",
      "Save succeeds. Message: 'JKR submission details recorded.' The version shows the submission date, submitted-to, channel and reference. The report files themselves are unchanged.",
      "Medium",
      "Submission is a register of the filing, not a workflow approval.",
      "Positive"),
    C("WST-UAT-024", "M01 Waste Management", "End-to-end",
      "Verify one scheduled-waste transaction can be followed from generation through pending collection, disposal, records, balance, dashboard and JKR report.",
      "UAT Premise A + SW410 reserved. Opening 100.000 kg as at 31/08/2026 already saved. No extra Final SW410 movements in September (cancel extras first). Two disposal images ready.",
      "1. Generate 50.000 kg on 15/09/2026 (or today), remarks 'UAT E2E WST-REF-01'. Capture reference.\n2. Open Pending Disposal — confirm Pending Collection, 50.000 kg, same premise and SW410.\n3. Execute disposal: both images, date on/after generation, actual 30.000 kg.\n4. Confirm row is Disposed and not pending.\n5. Open Waste Records — find produced 50.000 and disposed 30.000, linked.\n6. Dashboard September 2026: Opening 100, Produced 50, Disposed 30, Current 120.\n7. Generate JKR report 01/09/2026–30/09/2026 and confirm the same four figures for SW410.",
      "Single chain: Opening 100 kg; WST-REF-01 generated 50 kg; disposed 30 kg; expected current 120 kg.",
      "Every stage shows the same reference/premise/SW code. Status moves Pending Collection → Disposed. Balance and JKR closing both equal 120.000 kg. No approval step is required. Customer can sign this chain as the official M01 acceptance path.",
      "Critical",
      "Use the same physical transaction throughout. If dates were shifted because 15/09/2026 was in the future, keep that same date in the JKR period.",
      "E2E"),
    C("WST-UAT-025", "M01 Waste Management", "Pending Disposal",
      "Verify Pending Disposal explains when nothing is waiting for collection.",
      "Filter Pending Disposal to a premise with no pending rows (or Status Pending + a future date range).",
      "1. Open Pending Disposal.\n2. Select a premise/date/status combination with no pending rows.\n3. Read the list and the waste-type summary.",
      "UAT premise with zero pending, or dates that exclude all rows.",
      "List shows 'No pending disposal records.' Summary shows 'Nothing is pending disposal'. Pending Records = 0. Pending Weight = 0.000 kg. Page does not retain a previous premise's totals.",
      "Low",
      "",
      "Validation"),
    C("WST-UAT-026", "M01 Waste Management", "Waste Balance",
      "Verify a cancelled pending generation is excluded from official dashboard and JKR totals.",
      "WST-UAT-009 produced a cancelled row of 8.000 kg. Controlled SW410 figures otherwise known.",
      "1. Confirm the cancelled reference is not Status Final on Waste Records.\n2. Open Dashboard for the period and confirm Produced does not include the 8.000 kg.\n3. Confirm JKR produced total also excludes it.",
      "Cancelled 8.000 kg reference from WST-GEN-04.",
      "Produced, Disposed, Current Balance and JKR produced do not include the cancelled 8.000 kg.",
      "High",
      "Official totals = Final only.",
      "Calculation"),
    C("WST-UAT-027", "M01 Waste Management", "Access / Role",
      "Verify Waste User can record generation and disposal but cannot maintain opening balance or generate JKR reports, while Waste Officer can.",
      "Two accounts: Waste User (role 28) and Waste Officer (role 29), both assigned to UAT Premise A. Do not test the general user-admin screen — only the waste pages.",
      "1. Login as Waste User.\n2. Confirm sidebar Waste Management is visible.\n3. Create a small generation (1.000 kg) and confirm it is allowed.\n4. Open p_waste_opening_balance and p_waste_report and attempt to save / generate.\n5. Logout. Login as Waste Officer.\n6. Confirm opening balance and JKR generate are allowed.",
      "UAT-WASTE-USER; UAT-WASTE-OFFICER (TBC — actual usernames).",
      "Waste User: can generate and dispose; opening/JKR save is denied (capability message). Waste Officer: can generate/dispose and can save opening balance and generate JKR. Neither role sees a separate approval inbox.",
      "High",
      "Menu SQL grants Waste User and Waste Officer the operational waste pages. Accounts TBC. If a role still has no menu, record GAP-WST-02.",
      "Role"),
    C("WST-UAT-028", "M01 Waste Management", "Access / Role",
      "Verify a user without a waste role does not see Waste Management and cannot create official waste records.",
      "A user with no roles 1, 10, 28, 29 (for example PI Entry only).",
      "1. Login as that user.\n2. Confirm Waste Management is absent from the sidebar.\n3. Open p_waste_generation directly and attempt Save.",
      "UAT-NOACCESS or UAT-PI-ENTRY.",
      "No waste menu. Save rejected or page blocked. No new Final waste row.",
      "High",
      "Overlaps COM-UAT-007; execute once and cross-reference if preferred.",
      "Role"),
    C("WST-UAT-029", "M01 Waste Management", "Waste Records",
      "Verify opening a lifecycle record from Waste Records shows the generation details and the linked disposal (weight, date, evidence) together.",
      "WST-REF-01 already disposed (30.000 kg).",
      "1. Open Waste Record.\n2. Search WST-REF-01.\n3. View the produced row.\n4. Confirm the linked disposal panel shows 30.000 kg and disposal date.\n5. View the disposed row and confirm it points back to the same generation.",
      "WST-REF-01 / linked disposal.",
      "View page (Fifth Schedule layout) opens. Linked panel shows the pair. Registered 50.000 kg and actual disposed 30.000 kg are both visible. Documents/images can be opened.",
      "Medium",
      "The Fifth Schedule form is also used to key REGISTER records; this case only verifies viewing the V2 lifecycle pair.",
      "Positive"),
    C("WST-UAT-030", "M01 Waste Management", "Opening Balance",
      "Verify a negative opening quantity is rejected and a zero opening quantity is accepted.",
      "Waste Officer or Administrator on p_waste_opening_balance. Use a spare SW code (for example SW305) so the 100 kg SW410 opening is not overwritten.",
      "1. Attempt opening quantity -1 kg for UAT Premise A + SW305. Save.\n2. Save opening 0 kg for the same premise + SW305, as-at 31/08/2026.",
      "SW305; -1 kg then 0 kg.",
      "Negative rejected: 'Opening quantity must be zero or greater.' Zero saves successfully.",
      "Medium",
      "Opening upserts per premise + SW code — do not overwrite SW410 100 kg.",
      "Validation"),
    C("WST-UAT-031", "M01 Waste Management", "Pending Disposal",
      "Verify a user can narrow Pending Disposal by premise, waste type, status and date and can search by waste reference.",
      "Several pending/disposed rows exist across dates.",
      "1. Open Pending Disposal.\n2. Filter premise = UAT Premise A.\n3. Filter waste type = SW410.\n4. Set Status Pending, then Disposed, then All.\n5. Set From/To around the E2E date.\n6. Search WST-REF-01.",
      "WST-REF-01; UAT Premise A; SW410.",
      "Each filter reduces the list correctly. Search finds WST-REF-01. Status Pending hides disposed rows. Status Disposed hides pending rows. Date range excludes rows outside it.",
      "Medium",
      "Default status filter is Pending.",
      "Positive"),
    C("WST-UAT-032", "M01 Waste Management", "Waste Dashboard",
      "Verify changing the dashboard month/year (including a month with no movements) changes the official period figures and does not reuse the previous month's totals.",
      "September 2026 controlled figures known. August 2026 should have no UAT SW410 movements after opening as-at 31/08/2026.",
      "1. Open Dashboard, September 2026, UAT Premise A, SW410 — note Produced/Disposed/Current.\n2. Change to August 2026.\n3. Change to October 2026.\n4. Return to September 2026 and confirm the original figures reappear.",
      "August / September / October 2026.",
      "August 2026 Produced and Disposed for SW410 = 0.000 if no August Final rows; Opening/Current reflect opening 100.000 kg only. October Produced/Disposed = 0.000; Current remains 120.000 kg if no later movements. September figures return to 100 / 50 / 30 / 120.",
      "High",
      "Year-end: if testing December→January, Current must carry forward; Produced resets for the new month.",
      "Positive"),
]

# =============================================================================
# M02 KPI & APD
# =============================================================================
cases += [
    C("KPA-UAT-001", "M02 KPI & APD", "KPI Structure",
      "Verify a KPI Admin can open the shared KPI structure and see the four seeded groups, 21 performance indicators and a 100% weightage total.",
      PRE_K_ADMIN,
      "1. Login as KPI Admin.\n2. Open KPI & APD > KPI Structure.\n3. Select the shared template / UAT site that uses the global template.\n4. Count KPI groups and indicators.\n5. Read the active weightage message.",
      "Expected groups: 1 FMM Service Delivery (61%); 2 Asset Performance (20%); 3 Building Energy Efficiency (10%); 4 Safety & Statutory Compliance (9%). 21 PIs. Weightage 100%.",
      "Page title KPI Structure. Four active groups and 21 active PIs are listed with PI number, name, target, weightage and formula/calc type. Weightage message: 'Active weightage totals 100%.' Maximum APD (%) default is 5.00 unless previously changed.",
      "Critical",
      "Do not deactivate seeded PIs on a shared template used by other sites. Prefer a site-specific copy if the project has created one for UAT.",
      "Positive"),
    C("KPA-UAT-002", "M02 KPI & APD", "KPI Structure",
      "Verify a KPI Admin can add and later edit a KPI group on a UAT site-specific structure without damaging the shared template.",
      "KPI Admin. Prefer creating site-specific groups on UAT Site A only (first group on a site switches that site off the shared template). If UAT must stay on the shared template, skip create and only edit a UAT-named group previously prepared — or create and then deactivate at the end of UAT.",
      "1. Open KPI Structure for UAT Site A.\n2. Add group number UAT, name 'UAT Group', sort order 99, Active.\n3. Save.\n4. Edit the name to 'UAT Group Revised'. Save.\n5. Confirm the shared template (other site) still has the original four groups only.",
      "Group no UAT; name UAT Group then UAT Group Revised.",
      "First save: 'KPI group saved.' Group appears. Duplicate group number is rejected: 'KPI group UAT already exists.' Edit updates the name. Other sites still using the shared template are unchanged.",
      "High",
      "Creating the first site-specific group makes that site stop using the shared 21-PI template. Do this only on a dedicated UAT site. TBC with business whether UAT uses shared template or a site copy.",
      "Positive"),
    C("KPA-UAT-003", "M02 KPI & APD", "KPI Structure",
      "Verify a KPI Admin can open a seeded Performance Indicator, view its parameters and dry-run the formula with sample values.",
      PRE_K_ADMIN + " Use the shared template in read/test mode (do not change seeded formulas).",
      "1. Open KPI Structure.\n2. Open PI 1A Customer Satisfaction Survey rating.\n3. Confirm target 80%, weightage 5%, formula (p1/p2)*100, parameters p1 and p2.\n4. Use Test formula with p1=80, p2=100.\n5. Close without saving changes.",
      "PI 1A; test p1=80, p2=100; expected 80.",
      "PI fields match the seeded definition. Test formula returns 80 (or 80.0000). No change is written if the tester cancels/closes without save.",
      "High",
      "EXPRESSION parser supports pN, + − * /, min(), max() only.",
      "Positive"),
    C("KPA-UAT-004", "M02 KPI & APD", "KPI Structure",
      "Verify KPI group and PI definition cannot be saved without the required business identifiers.",
      PRE_K_ADMIN,
      "1. Open Add Group with number and name blank. Save.\n2. Open Add / Edit PI with PI number and name blank. Save.\n3. Set weightage 150 on a PI (or a UAT PI). Save.",
      "Blank group; blank PI; weightage 150.",
      "Group blocked: 'Enter the KPI group number and name.' PI blocked: 'Enter the PI number and name.' Weightage blocked: 'The weightage must be between 0 and 100.'",
      "High",
      "Do not save invalid changes to seeded PIs.",
      "Validation"),
    C("KPA-UAT-005", "M02 KPI & APD", "KPI Structure",
      "Verify the structure screen warns when active PI weightage does not total 100%, because APD exposures would no longer add up to the APD maximum.",
      "KPI Admin on a dedicated UAT site structure, or temporarily change a UAT-only PI weightage and restore it afterwards. Do not leave the shared template unbalanced.",
      "1. Note the current weightage message (should be 100%).\n2. On a UAT-only PI (or a temporary edit that will be reversed), change weightage so the active total is not 100% (for example 5 → 6).\n3. Save and read the banner.\n4. Restore the original weightage.",
      "Unbalanced total e.g. 101%.",
      "Warning: 'Active weightage totals {X}%. APD exposure will not add up to the maximum until this is 100%.' System still allows the save (warning, not hard stop). After restore, message returns to 'Active weightage totals 100%.'",
      "High",
      "GAP-KPA-02: unbalanced weightage is a warning only.",
      "Validation"),
    C("KPA-UAT-006", "M02 KPI & APD", "KPI Structure",
      "Verify a KPI Admin can deactivate a UAT-only Performance Indicator so it is no longer copied into a newly created month.",
      "A UAT-only PI exists on UAT Site A (created in KPA-UAT-002/003). Do not deactivate seeded 1A–4C on the shared template.",
      "1. On KPI Structure, deactivate the UAT-only PI.\n2. Confirm prompt: 'Deactivate this Performance Indicator?'\n3. Create (or inspect) a new month after deactivation and confirm that PI is absent from the snapshot.\n4. Confirm an already-created earlier month still shows the PI if it was snapshotted while active.",
      "UAT-only PI.",
      "PI status becomes Inactive. Success: 'Performance Indicator deactivated.' New months omit it. Existing snapshotted months keep the copy they were created with.",
      "Medium",
      "Deactivate is a status change, not a hard delete.",
      "Positive"),
    C("KPA-UAT-007", "M02 KPI & APD", "KPI Configuration",
      "Verify a KPI Admin can set the site Maximum APD percentage used when a new evaluation month is created.",
      PRE_K_ADMIN + " Dedicated UAT site.",
      "1. Open KPI Structure for UAT Site A.\n2. Set Maximum APD (%) to 5.00 if not already.\n3. Save.\n4. Attempt 101 and -1 to confirm the range, then restore 5.00.",
      "Valid 5.00%. Invalid 101 and -1.",
      "Valid save: 'KPI configuration saved.' Invalid: 'The maximum APD percentage must be between 0 and 100.' New months default to 5.00% (shown on Create Month as Max APD % and used in APD Maximum = MPV × 5%).",
      "High",
      "Per-month Max APD % can later be edited via Edit MPV (KPA-UAT-035).",
      "Positive"),
    C("KPA-UAT-008", "M02 KPI & APD", "PI Assignment",
      "Verify a KPI Admin can assign selected Performance Indicators to a PI Entry user for the UAT site.",
      "KPI Admin. At least one active user holds the PI Entry role (31) for UAT Site A. Do not open general User Management except to confirm the user already exists.",
      "1. Open KPI & APD > PI Assignment.\n2. Select UAT Site A.\n3. Select PI 1A, 1E, 3B and 4C (the calculation set).\n4. Select UAT-PI-ENTRY user.\n5. Save.",
      "Site UAT Site A. PIs 1A, 1E, 3B, 4C. User UAT-PI-ENTRY (TBC).",
      "Save succeeds: 'PI assignment saved.' Assignment list shows each PI + user + site as active. Assignable-user list contains only PI Entry role users.",
      "Critical",
      "If the list says 'No user holds the PI Entry role yet', stop and request the role on the existing UAT account. Do not treat User Management as an M02 test object.",
      "Positive"),
    C("KPA-UAT-009", "M02 KPI & APD", "PI Assignment",
      "Verify assigning the same PI to the same user again does not create a duplicate active assignment row.",
      "KPA-UAT-008 completed.",
      "1. Assign PI 1A to UAT-PI-ENTRY again on UAT Site A.\n2. Save.\n3. Count 1A assignment rows for that user.",
      "Duplicate of PI 1A / UAT-PI-ENTRY / UAT Site A.",
      "Still one active assignment for that site + PI + user. Implementation reactivates the same assignment rather than inserting a second active row.",
      "Medium",
      "",
      "Validation"),
    C("KPA-UAT-010", "M02 KPI & APD", "PI Assignment",
      "Verify a KPI Admin can remove a PI assignment so that PI Entry user can no longer submit that indicator.",
      "An extra assignment exists (assign PI 2A temporarily), or use a spare PI.",
      "1. On PI Assignment, remove the spare PI assignment.\n2. Login as PI Entry.\n3. Open that PI for the UAT month and confirm it is read-only / not assigned.",
      "Spare PI (e.g. 2A) assigned then removed.",
      "Remove success: 'PI assignment removed.' PI Entry sees 'This indicator is not assigned to you.' and cannot Save/Submit that PI. Other assigned PIs remain editable.",
      "High",
      "Remove is a deactivation (status 2), not a hard delete.",
      "Positive"),
    C("KPA-UAT-011", "M02 KPI & APD", "Monthly Evaluation",
      "Verify a KPI Admin can create the September 2026 monthly evaluation, snapshotting the KPI structure and calculating APD maximum from MPV.",
      PRE_K_ADMIN + " September 2026 must not already exist for UAT Site A (use October 2026 if September was created in a dry run — then keep that month as the official UAT month).",
      "1. Open Monthly Evaluation.\n2. Click Create Month.\n3. Select UAT Site A, Year 2026, Month September.\n4. Enter MPV 4147120.556.\n5. Confirm Max APD % preview is 5.00 and APD Maximum preview is 207,356.03.\n6. Remarks 'UAT September 2026'.\n7. Save.\n8. Open the new month.",
      "MPV 4,147,120.556. Max APD 5.00%. Expected APD Maximum RM 207,356.03. Period September 2026.",
      "Success: 'Monthly KPI evaluation created from the KPI template.' Month status = Open. List shows MPV 4,147,120.556, Max APD 5.00%, APD Maximum 207,356.03, Demerit 0, APD Deducted 0, progress 0 / 21 (or 0 / number of active PIs snapshotted). Indicator grid lists the snapshotted PIs, each Draft.",
      "Critical",
      "apdMax = round(MPV × maxApdPct / 100, 2) = round(207356.0278, 2) = 207,356.03. There is no approval step.",
      "Positive"),
    C("KPA-UAT-012", "M02 KPI & APD", "Monthly Evaluation",
      "Verify a second evaluation cannot be created for the same site and month.",
      "September 2026 already exists for UAT Site A.",
      "1. Click Create Month again.\n2. Select the same site, year and month.\n3. Enter any MPV. Save.",
      "Duplicate September 2026 / UAT Site A.",
      "Save rejected: 'A KPI evaluation for September 2026 already exists.' Only one month row remains.",
      "High",
      "",
      "Validation"),
    C("KPA-UAT-013", "M02 KPI & APD", "Monthly Evaluation",
      "Verify Monthly Payment Value cannot be negative when creating or editing a month.",
      "KPI Admin. Use Create Month for a spare unused month (e.g. January 2025) or Edit MPV on a disposable month — do not corrupt September 2026.",
      "1. Open Create Month for a spare period.\n2. Enter MPV -1. Save.\n3. Cancel without creating a valid spare month, or delete/ignore if created.",
      "MPV = -1.",
      "Rejected: 'The Monthly Payment Value cannot be negative.' September 2026 MPV remains 4,147,120.556.",
      "High",
      "",
      "Validation"),
    C("KPA-UAT-014", "M02 KPI & APD", "PI Entry",
      "Verify an authorised PI Entry user can open the UAT month and enter values only for the Performance Indicators assigned to them.",
      PRE_K_ENTRY + " Assignments from KPA-UAT-008 in place.",
      "1. Login as UAT-PI-ENTRY.\n2. Confirm KPI Structure and PI Assignment are not in the sidebar.\n3. Open Monthly Evaluation and open September 2026.\n4. Open PI 1A (assigned) and confirm fields are editable.\n5. Open an unassigned PI (e.g. 2A) and confirm it is read-only.",
      "UAT-PI-ENTRY; assigned 1A, 1E, 3B, 4C.",
      "PI Entry can open Summary, Monthly Evaluation and History. Structure/Assignment are not in the menu. Assigned PI shows editable parameter fields. Unassigned PI shows 'This indicator is not assigned to you.' and Save/Submit are not available. Grid may mark unassigned Draft PIs with the not-assigned icon.",
      "Critical",
      "PI Entry can see the other PIs in the month grid; they cannot edit them.",
      "Role"),
    C("KPA-UAT-015", "M02 KPI & APD", "PI Entry",
      "Verify a PI Entry user is prevented from submitting an indicator that is not assigned to them even if they open the URL.",
      "Unassigned eval_pi id known from the September grid (or copied from the browser URL of an unassigned PI opened as Admin).",
      "1. As PI Entry, open p_kpa_pi_entry?id= of an unassigned PI.\n2. Attempt to type a value and Save or Submit.",
      "Unassigned eval_pi URL.",
      "Save/Submit blocked. Message: 'This Performance Indicator is not assigned to you.' Values are not changed.",
      "High",
      "",
      "Role"),
    C("KPA-UAT-016", "M02 KPI & APD", "Access / Role",
      "Verify a PI Entry user cannot change KPI structure, formulas, parameters or PI assignments.",
      "Logged in as UAT-PI-ENTRY.",
      "1. Confirm KPI Structure and PI Assignment are absent from the sidebar.\n2. Paste p_kpa_structure and p_kpa_assignment.\n3. Attempt to save a group, a PI, or an assignment.",
      "UAT-PI-ENTRY.",
      "Pages are hidden. Direct URL shows a not-allowed state or save returns 'You are not allowed to change the KPI structure.' No structure/assignment change is stored.",
      "High",
      "",
      "Role"),
    C("KPA-UAT-017", "M02 KPI & APD", "PI Entry",
      "Verify a PI Entry user can capture parameter values for an assigned PI and save them as draft without locking the indicator.",
      PRE_K_ENTRY,
      "1. Open September 2026 > PI 1A.\n2. Enter p1 Total Rating Marks Obtained = 88.\n3. Enter p2 Total Possible Marks = 100.\n4. Click Save Draft (or allow autosave and click Save Draft).\n5. Leave the page and re-open PI 1A.",
      "Dataset A / PI 1A: p1=88, p2=100.",
      "Values remain 88 and 100. Achievement shows 88.0000%. Target 80%. Result = Target met. APD Deducted = 0.00. PI status remains Draft. Month remains Open.",
      "Critical",
      "Autosave may already store the numbers before Save Draft. Submit is a separate action (KPA-UAT-020).",
      "Positive"),
    C("KPA-UAT-018", "M02 KPI & APD", "PI Entry",
      "Verify a PI cannot be submitted while a required parameter is blank.",
      "PI 1A still Draft. Tester can temporarily clear p2.",
      "1. Open PI 1A.\n2. Clear p2.\n3. Click Submit and confirm the prompt if shown.",
      "p1=88; p2 blank.",
      "Submit is rejected. Message: 'Enter a value for \"Total Possible Marks\" before submitting.' (label as shown). Status remains Draft.",
      "High",
      "Restore p2=100 after the test.",
      "Validation"),
    C("KPA-UAT-019", "M02 KPI & APD", "PI Entry",
      "Verify division by zero does not produce a fake score and blocks submit.",
      "PI 1A Draft.",
      "1. Set p1=88, p2=0.\n2. Observe achievement.\n3. Attempt Submit.\n4. Restore p2=100.",
      "p1=88; p2=0.",
      "Achievement is not a number. Message: 'The achievement cannot be calculated yet. Check that every parameter is filled in and that no divisor is zero.' Submit blocked with that message or 'The achievement cannot be calculated. Review the parameter values.' No Target met / Not met score is stored as 0% unless the calculator truly produced 0.",
      "High",
      "A 0% pass/fail result is not acceptable here — the result must be Not calculated.",
      "Validation"),
    C("KPA-UAT-020", "M02 KPI & APD", "PI Entry",
      "Verify submitting a valid PI locks the values so the PI Entry user cannot change them.",
      "PI 1A Draft with p1=88, p2=100 (Dataset A).",
      "1. Open PI 1A.\n2. Click Submit.\n3. Confirm: 'Submit PI 1A? The values are locked once submitted.'\n4. Attempt to change p1.\n5. Attempt Save Draft.",
      "PI 1A Dataset A.",
      "Success: 'PI submitted. The values are now locked.' Status = Submitted. Fields disabled. Further save returns 'This PI has been submitted. Ask a KPI Admin to reopen it before changing the values.'",
      "Critical",
      "No approval step. Submit is the lock.",
      "Positive"),
    C("KPA-UAT-021", "M02 KPI & APD", "Monthly Evaluation",
      "Verify only a KPI Admin (or Administrator) can reopen a submitted PI, and that PI Entry cannot.",
      "PI 1A Submitted. Have both UAT-PI-ENTRY and KPI Admin sessions.",
      "1. As PI Entry, open submitted PI 1A and confirm Reopen is not available / not allowed.\n2. Login as KPI Admin.\n3. Open PI 1A and Reopen. Confirm the prompt.\n4. Confirm status returns to Draft and the month is Open.\n5. Re-enter 88 / 100 and Submit again so Dataset A remains locked for later reconciliation.",
      "PI 1A.",
      "PI Entry cannot reopen. KPI Admin reopen succeeds: 'PI reopened for editing.' Status Draft; month Open. After re-submit, values 88 / 100 and achievement 88.0000% are locked again.",
      "High",
      "Reopen of a non-submitted PI: 'Only a submitted PI can be reopened.'",
      "Role"),
    C("KPA-UAT-022", "M02 KPI & APD", "KPI Calculation",
      "Verify Dataset A (normal / meeting target) — PI 1A, 1E and 4C calculate achievement, pass and APD exactly as the engine defines.",
      "September 2026, MPV 4,147,120.556, max APD 5%, APD Maximum 207,356.03. Tester is KPI Admin or the assigned PI Entry.",
      "1. Open PI 1A. Enter p1=88, p2=100. Save. Record Target, Achievement, APD Exposure, APD Deducted, pass text.\n2. Open PI 1E. Enter p1=10, p2=10, p3=5, p4=5, p5=0, p6=0. Save. Record results.\n3. Open PI 4C. Enter p1=100, p2=100. Save. Record results.\n4. Compare with the manual working in Test Data.",
      "APD Maximum RM 207,356.03.\n\nPI 1A EXPRESSION (p1/p2)*100: 88/100*100 = 88.0000%. Target 80, GTE → PASS. Weight 5%. APD exposure = round(207356.03*5/100,2) = 10,367.80. Deducted 0.00. Demerit 0.\n\nPI 1E BACKLOG_AVG: buckets (10/10)=100, (5/5)=100, (0/0 treated as no backlog)=100. Mean 100.0000%. Target 100 → PASS. Exposure 10,367.80. Deducted 0.00.\n\nPI 4C AVG_PARAMS: mean(100,100)=100.0000%. Target 100 → PASS. Weight 3%. Exposure = round(207356.03*3/100,2) = 6,220.68. Deducted 0.00.",
      "System achievement, pass text (Target met), APD exposure and APD deducted match the manual working to 4 decimal places on achievement and 2 decimal places on RM. Month totals do not include APD for these three PIs.",
      "Critical",
      "Achievement is rounded to 4 decimal places. APD is rounded to 2 decimal places. A bucket with total 0 on PI 1E scores 100%.",
      "Calculation"),
    C("KPA-UAT-023", "M02 KPI & APD", "KPI Calculation",
      "Verify Dataset B (boundary / exactly on target) — PI 1A at 80%, PI 3B BEI exactly 170.6500, and PI 3C with zero wastage findings.",
      "Same September 2026 month. Prefer a second month (e.g. August 2026, same MPV) if Dataset A values must stay frozen — otherwise temporarily edit then restore Dataset A. Recommended: create August 2026 as the boundary month.",
      "1. Create or open August 2026 with the same MPV / 5% if September is reserved for Dataset A/C.\n2. PI 1A: p1=80, p2=100. Save.\n3. PI 3B: p1=142208.3333, p2=0, p3=10000, p4=12. Save.\n4. PI 3C: p1=0. Save.\n5. Compare with the manual working.",
      "Boundary month (recommended August 2026), same APD Maximum 207,356.03.\n\nPI 1A: 80/100*100 = 80.0000% = target 80 → PASS (GTE).\n\nPI 3B BEI: (142208.3333+0)/10000 × 12 = 170.6500. Target 170.6500, LTE → PASS. Displayed result % = 100. Weight 3%. Exposure 6,220.68. Deducted 0.00.\n\nPI 3C EXPRESSION 100-p1: 100-0 = 100.0000% = target → PASS.",
      "All three show Target met. PI 3B actual BEI 170.6500 (unit BEI, not %). PI 3B result column on the summary is 100 (pass/fail display), not 170.65. APD deducted 0.00 for each.",
      "Critical",
      "If p1 is entered as 142208.3333 and the field rounds to 4 dp, BEI must still be 170.6500. Lower-is-better for 3B.",
      "Calculation"),
    C("KPA-UAT-024", "M02 KPI & APD", "KPI Calculation",
      "Verify Dataset C (below target / high BEI) — failing PIs impose their full APD exposure and demerit points.",
      "Use October 2026 with MPV 4,147,120.556 and 5% so September Dataset A remains intact.",
      "1. Create October 2026 with the same MPV.\n2. PI 1A: p1=60, p2=100. Save.\n3. PI 1E: p1=10, p2=8, p3=5, p4=5, p5=0, p6=0. Save.\n4. PI 3B: p1=200000, p2=50000, p3=10000, p4=12. Save.\n5. PI 4C: p1=90, p2=80. Save.\n6. Compare with the manual working.",
      "October 2026. APD Maximum 207,356.03.\n\nPI 1A: 60.0000% < 80 → FAIL. Demerit 1. APD deducted 10,367.80.\n\nPI 1E: buckets 80%, 100%, 100%. Mean = 93.3333%. Target 100 → FAIL. Demerit 1. APD deducted 10,367.80.\n\nPI 3B: (250000/10000)×12 = 300.0000 > 170.6500 → FAIL. Result % = 0. Demerit 1. APD deducted 6,220.68.\n\nPI 4C: mean(90,80)=85.0000% < 100 → FAIL. Demerit 1. APD deducted 6,220.68.\n\nSum of these four deductions = 33,176.96. Sum of demerit = 4.",
      "Each PI shows Target not met, the achievement/BEI above, APD Deducted equal to full exposure, and the seeded demerit. October month cards increase by these amounts once the PIs are saved (even before submit). Summary Actual column is red for these PIs.",
      "Critical",
      "Failing a PI deducts the whole APD exposure, not a partial percentage of the miss.",
      "Calculation"),
    C("KPA-UAT-025", "M02 KPI & APD", "KPI Calculation",
      "Verify the workbook APD example: MPV 4,147,120.556 at 5% gives APD maximum RM 207,356.03, and a 5% weightage PI (1B) has exposure RM 10,367.80.",
      "September 2026 month exists with that MPV. KPI Admin.",
      "1. Open September 2026 month header and read APD Maximum.\n2. Open PI 1B (Customer Rating in Work Order sheet) and read APD Exposure (value is shown even before parameters if the month snapshot calculated it).\n3. Manually compute 4,147,120.556 × 5 / 100 = 207,356.0278 → 207,356.03 and 207,356.03 × 5 / 100 = 10,367.8015 → 10,367.80.",
      "MPV 4,147,120.556; 5%; PI 1B weight 5%.",
      "APD Maximum = 207,356.03. PI 1B APD Value / Exposure = 10,367.80. If 1B is later failed, APD Deducted becomes 10,367.80; if it passes, deducted stays 0.00.",
      "Critical",
      "This is the implementation-verified workbook example. Do not invent a different APD split.",
      "Calculation"),
    C("KPA-UAT-026", "M02 KPI & APD", "Monthly Evaluation",
      "Verify the monthly evaluation grid shows the evaluation month, each snapshotted PI, entered values / achievement and running demerit and APD totals.",
      "September 2026 with Dataset A PIs entered (1A, 1E, 4C).",
      "1. Open Monthly Evaluation, year 2026.\n2. Confirm the September row: period, MPV, max APD %, APD maximum, demerit, APD deducted, progress, status Open.\n3. Open the month.\n4. Confirm 1A / 1E / 4C show the Dataset A achievements and Target met.\n5. Confirm other PIs still Draft / not calculated.",
      "September 2026 Dataset A.",
      "Grid period is September 2026. Entered PIs show achievement and pass. Progress = submitted / total (e.g. 1/21 if only 1A was submitted). Status remains Open until every PI is submitted. Totals equal the sum of imposed demerit and deducted APD of PIs that have results.",
      "High",
      "",
      "Positive"),
    C("KPA-UAT-027", "M02 KPI & APD", "Monthly Evaluation",
      "Verify the month status becomes Completed only after every snapshotted PI has been submitted.",
      "KPI Admin. Either submit every remaining September PI with any valid passing values (long path) OR use a dedicated UAT site whose structure has only the four calculation PIs. TBC which path the business will execute.",
      "1. Note current progress (e.g. 1 / 21).\n2. Submit remaining PIs (Admin may enter and submit unassigned PIs).\n3. Refresh the month list.\n4. Reopen one PI and confirm the month returns to Open, then re-submit.",
      "All PIs in the snapshot.",
      "When progress is n / n submitted, status = Completed. Reopen of any PI returns status to Open. There is no approve button and no extra workflow.",
      "High",
      "TBC — Business confirmation required: whether UAT will submit all 21 seeded PIs or use a reduced UAT structure. If 21 must be submitted, schedule a dedicated data-entry session.",
      "Positive"),
    C("KPA-UAT-028", "M02 KPI & APD", "KPI / APD Summary",
      "Verify the KPI / APD Dashboard figures for September 2026 equal the monthly evaluation results (not an independent invented score).",
      "September 2026 Dataset A entered. Prefer after the calculation PIs are saved.",
      "1. Open KPI & APD > KPI / APD Summary.\n2. Select UAT Site A, Year 2026, Month September.\n3. Read Total Indicators, Total Weight, Demerit Points, APD Deducted.\n4. Find rows 1A, 1E, 4C in the Pavement Deduction table.\n5. Compare Target, Actual, Points Imposed, Weightage, APD Value, APD Deducted with the PI Entry screens.\n6. Use category chips (Service Delivery, Energy Efficiency, Safety & Compliance) and the keyword search.",
      "September 2026. Expected 1A Actual 88.0000 Target 80 Points Imposed 0 APD Deducted 0.00. 1E Actual 100.0000. 4C Actual 100.0000. Demerit / APD Deducted cards = sum of all PIs in that month (0.00 extra if only these three have results and they passed).",
      "Summary cards and table rows match the evaluation. BEI rows show target without a % suffix. Pass actuals are green; fail red; not calculated show an em-dash. Search/chips filter the table. If a month has not been created, the page shows the structure with empty results and 'No evaluation exists for this month yet. Showing the configured structure.' — that banner must not appear for September once created.",
      "Critical",
      "Table title in the product is 'Pavement Deduction for Performance Based KPI'.",
      "Calculation"),
    C("KPA-UAT-029", "M02 KPI & APD", "KPI History",
      "Verify completed or in-progress months remain in History with the same MPV, APD and demerit, and that an earlier month is not changed by later months.",
      "At least two months exist (e.g. August Dataset B, September Dataset A, October Dataset C).",
      "1. Open KPI History.\n2. Select UAT Site A, From year 2026, To year 2026.\n3. Read the monthly totals table for Aug/Sep/Oct.\n4. Confirm September still shows MPV 4,147,120.556 and APD Maximum 207,356.03 and the Dataset A deducted/demerit.\n5. Select PI 1A in the indicator trend and confirm Aug 80, Sep 88, Oct 60 (if those months were entered).\n6. Search/change the year range to a year with no data.",
      "2026 months as created in KPA-UAT-022/023/024.",
      "Each month's MPV, APD Maximum, APD Deducted, APD Retained (Maximum − Deducted), demerit and status match that month's evaluation. September numbers do not pick up October failures. Empty range shows 'No evaluation months in the selected range.' Charts follow the same figures.",
      "High",
      "History is a report of stored months, not a live recalculation against a changed template (see KPA-UAT-033).",
      "Positive"),
    C("KPA-UAT-030", "M02 KPI & APD", "Access / Role",
      "Verify a KPI Viewer can read the summary and history but cannot enter PI values or change structure.",
      "UAT-KPI-VIEWER account with role 32 only (TBC).",
      "1. Login as KPI Viewer.\n2. Confirm sidebar shows KPI & APD, KPI / APD Summary and KPI History only (no Monthly Evaluation, Structure, Assignment).\n3. Open Summary and History for UAT Site A / 2026.\n4. Confirm no Create Month, no Save and no Submit controls.\n5. Paste p_kpa_evaluation, p_kpa_structure, p_kpa_assignment and a PI Entry URL. Attempt any save.",
      "UAT-KPI-VIEWER (TBC).",
      "Viewer sees Summary and History values for the UAT site. No edit/submit/create controls on those pages. Hidden pages are blocked or save is rejected. Stored September/October values are not changed.",
      "High",
      "Role 32 nav grant is Summary + History only.",
      "Role"),
    C("KPA-UAT-031", "M02 KPI & APD", "Access / Role",
      "Verify a KPI Viewer cannot open Monthly Evaluation from the menu and cannot create a month.",
      "UAT-KPI-VIEWER.",
      "1. Login as KPI Viewer.\n2. Confirm Monthly Evaluation is not listed.\n3. Open p_kpa_evaluation directly.\n4. If the page renders, click Create Month and attempt to save.",
      "UAT-KPI-VIEWER.",
      "Monthly Evaluation is not in the sidebar. Direct access does not create a new month. Existing months remain unchanged.",
      "High",
      "",
      "Role"),
    C("KPA-UAT-032", "M02 KPI & APD", "End-to-end",
      "Verify one KPI can be taken from structure confirmation through assignment, PI entry, monthly evaluation, summary and history using the same site, PI and month.",
      "KPI Admin, PI Entry and (optional) KPI Viewer accounts. UAT Site A. Shared or site template ready. September 2026 is the official E2E month (create it if KPA-UAT-011 was skipped).",
      "1. As KPI Admin, open KPI Structure — confirm PI 1A is active, target 80%, formula (p1/p2)*100, weight 5%.\n2. Confirm Maximum APD % = 5.00.\n3. Assign PI 1A to UAT-PI-ENTRY for UAT Site A.\n4. Create September 2026 with MPV 4,147,120.556 if it does not exist.\n5. Logout. Login as PI Entry.\n6. Open Monthly Evaluation > September 2026 > PI 1A.\n7. Enter p1=88, p2=100. Save Draft. Confirm achievement 88.0000% Target met APD deducted 0.00.\n8. Submit PI 1A.\n9. Open KPI / APD Summary for September 2026 — row 1A Actual 88, Points Imposed 0, APD Deducted 0.00.\n10. Open KPI History 2026 — September row still shows the same MPV and APD Maximum 207,356.03.",
      "Same KPI: PI 1A. Same site: UAT Site A. Same month: September 2026. Same values: 88 / 100 → 88%.",
      "Every stage shows PI 1A, September 2026 and achievement 88.0000%. Submit locks the PI. Summary and History reconcile to the evaluation. No approval step appears. PI Entry never needed Structure access. APD exposure for 1A remains 10,367.80 and deducted remains 0.00.",
      "Critical",
      "Keep September reserved for this passing Dataset A chain. Use August/October for boundary/fail datasets.",
      "E2E"),
    C("KPA-UAT-033", "M02 KPI & APD", "KPI Structure",
      "Verify changing a PI target or formula after a month has been created does not rewrite that month's snapshotted target or result.",
      "September 2026 already contains PI 1A result 88.0000% against target 80. Dedicated UAT site preferred. If only the shared template is available, change a UAT-only PI instead of seeded 1A.",
      "1. Note September PI 1A target 80 and actual 88.\n2. As KPI Admin, on KPI Structure temporarily change PI 1A target to 90 (UAT site structure only).\n3. Re-open September 2026 PI 1A.\n4. Confirm the month still shows target 80 and actual 88 and still Target met.\n5. Restore structure target to 80.\n6. Create (or inspect) a brand-new future month and confirm it picks up target 90 only if the change was left in place — then restore 80 before that new month is kept.",
      "Structure target temporarily 90. September snapshot must stay 80.",
      "September PI 1A target remains 80.0000 and result remains Target met. Structure change does not recalculate historical months. Only months created after the change would receive the new target.",
      "High",
      "This is the snapshot rule. Do not leave the shared template target at 90.",
      "Positive"),
    C("KPA-UAT-034", "M02 KPI & APD", "KPI / APD Summary",
      "Verify the summary page for a month that has not yet been created shows the configured indicators with empty results rather than a blank page.",
      "A month with no evaluation, e.g. March 2025 on UAT Site A.",
      "1. Open KPI / APD Summary.\n2. Select UAT Site A, March 2025 (or another unused month).\n3. Read the banner and the table.",
      "Unused month.",
      "Banner: 'No evaluation exists for this month yet. Showing the configured structure.' Table lists configured PIs. Actual, demerit imposed and APD deducted are empty / zero. Status Not started. exists = false. Page is not blank.",
      "Medium",
      "",
      "Validation"),
    C("KPA-UAT-035", "M02 KPI & APD", "Monthly Evaluation",
      "Verify editing MPV or Max APD % on an existing month recalculates APD maximum and every indicator's APD exposure.",
      "KPI Admin. Use October 2026 (Dataset C) or a spare month — do not change September if it is the signed E2E month, or change and restore.",
      "1. Open the spare month.\n2. Edit MPV.\n3. Change MPV from 4,147,120.556 to 2,000,000.000.\n4. Keep Max APD % = 5.\n5. Save.\n6. Compute APD Maximum = 100,000.00 and PI 1A exposure = 5,000.00.\n7. Restore MPV 4,147,120.556 so later history checks still match, unless this month is disposable.",
      "New MPV 2,000,000.000. Max APD 5%. Expected APD Maximum 100,000.00. 5% PI exposure 5,000.00. Warning on the modal: 'Saving recalculates the APD value of every indicator in this month.'",
      "Save: 'Monthly KPI evaluation updated and recalculated.' Header APD Maximum = 100,000.00. Each PI exposure is recomputed from the new maximum. Achievement values do not change. Restore leaves the official figures back at 207,356.03 if required.",
      "High",
      "",
      "Positive"),
    C("KPA-UAT-036", "M02 KPI & APD", "KPI Configuration",
      "Verify Maximum APD percentage rejects values outside 0–100 when saved from KPI Structure.",
      PRE_K_ADMIN,
      "1. Open KPI Structure.\n2. Enter Maximum APD (%) = 101. Save.\n3. Enter -1. Save.\n4. Restore 5.00 and save.",
      "101; -1; then 5.00.",
      "Out-of-range values are rejected: 'The maximum APD percentage must be between 0 and 100.' 5.00 remains the stored value after restore.",
      "Medium",
      "Duplicates the invalid branch of KPA-UAT-007; execute once if preferred and mark the other as cross-referenced.",
      "Validation"),
]

# =============================================================================
# M03 ENERGY
# =============================================================================
cases += [
    C("ENR-UAT-001", "M03 Energy & Utility", "Daily Electricity",
      "Verify a utility officer can record a cumulative incoming-meter reading for a building and date, and that the system stores it as a reading (not as a typed daily kWh consumption).",
      PRE_E,
      "1. Login to GEMS.\n2. Open Energy Monitoring > Daily Electricity.\n3. Select UAT Site A, Month September, Year 2026.\n4. On Incoming No.1, date 31/08/2026, open August 2026 first if the September grid does not show 31/08.\n5. Enter Cumulative (kWh) 10,000.00 on 31/08/2026 (baseline).\n6. Switch to September 2026.\n7. Enter Incoming No.1 cumulative 10,100.00 on 01/09/2026.\n8. Tab or leave the cell and wait for Saved.\n9. Refresh the page.",
      "ENR-RDG-01: Incoming No.1. 31/08/2026 = 10,000.00. 01/09/2026 = 10,100.00. Expected 01/09 consumption = 100.00 kWh.",
      "Autosave shows Saved and 'Meter reading saved.' After refresh both cumulative values remain. 01/09/2026 Consumption (kWh) = 100.00. 31/08/2026 is the baseline (no consumption on the first reading day of a meter unless a previous reading exists). Total kWh card includes 100.00 for September once only 01/09 is in range (plus any later September days).",
      "Critical",
      "Users enter CUMULATIVE meter readings. Consumption is calculated. This is different from typing 100 kWh as a daily usage figure.",
      "Positive"),
    C("ENR-UAT-002", "M03 Energy & Utility", "Daily Electricity",
      "Verify a cumulative reading cannot be negative, cannot be in the future, and cannot be saved without a value.",
      PRE_E,
      "1. On Daily Electricity for today, enter cumulative -10 on Incoming No.1. Leave the cell.\n2. Attempt a reading dated tomorrow (if the date column exists for a future day in the current month, or change month to a future month if offered).\n3. Clear a cell that had no previous reading and confirm nothing is posted as a negative.",
      "Cumulative -10; date = tomorrow.",
      "Negative rejected: 'The cumulative meter reading cannot be negative.' Future date rejected: 'The reading date cannot be in the future.' No official reading is stored for the invalid attempts. Existing valid readings are unchanged.",
      "High",
      "The grid typically only lists days of the selected month; tomorrow appears only when testing near month-end or using the last day +1 via API-less UI (if tomorrow is not on the grid, record that the date control / month list prevents future months beyond current+1 and still blocks future days).",
      "Validation"),
    C("ENR-UAT-003", "M03 Energy & Utility", "Daily Electricity",
      "Verify a zero cumulative reading is accepted (meters may be reset) and is not treated as a missing reading.",
      PRE_E + " Use Incoming No.2 so Incoming No.1 E2E data is not disturbed.",
      "1. Select Incoming No.2 on a spare date (e.g. 01/07/2026 in July).\n2. Enter cumulative 0.00. Wait for Saved.\n3. Refresh.",
      "Incoming No.2; 01/07/2026; 0.00 kWh.",
      "Save succeeds. Value 0.00 remains after refresh. It is a stored reading, not a blank cell.",
      "Medium",
      "Zero is allowed. Negative is not. Clear the cell later if this spare meter should stay empty (ENR-UAT-008).",
      "Validation"),
    C("ENR-UAT-004", "M03 Energy & Utility", "Daily Electricity",
      "Verify decimal cumulative readings are stored to 2 decimal places and used in consumption.",
      PRE_E,
      "1. On Incoming No.1, enter 10,225.50 on 02/09/2026 (after 10,100.00 on 01/09).\n2. Confirm consumption on 02/09/2026.",
      "02/09/2026 cumulative 10,225.50. Previous 10,100.00. Expected consumption 125.50 kWh.",
      "Saved value shows 10225.50 / 10,225.50. Consumption 02/09 = 125.50 kWh (2 decimal places).",
      "Medium",
      "If the official E2E set must stay at 10,225.00, use 10,225.00 here and run the decimal check on Incoming No.2 instead (e.g. 1000.00 then 1000.25).",
      "Positive"),
    C("ENR-UAT-005", "M03 Energy & Utility", "Daily Electricity",
      "Verify entering a second cumulative value on the same meter and date overwrites the reading instead of creating a duplicate day.",
      PRE_E,
      "1. On Incoming No.1, 03/09/2026, enter 10,375.00. Save.\n2. Change the same cell to 10,380.00. Save.\n3. Refresh.\n4. Set it back to 10,375.00 if this day is part of the 375 kWh official set.",
      "Same meter + date; 10,375.00 then 10,380.00.",
      "Only one reading exists for Incoming No.1 on 03/09/2026. Latest value is stored. Consumption for 03/09 uses the latest cumulative minus the previous reading.",
      "High",
      "Unique key is meter + date (upsert).",
      "Positive"),
    C("ENR-UAT-006", "M03 Energy & Utility", "Daily Electricity",
      "Verify missed days between two readings receive an even share of the consumption (gap distribution).",
      PRE_E + " Use Incoming No.2 so the official Incoming No.1 September totals stay clean.",
      "1. Open September 2026, Incoming No.2.\n2. Enter cumulative 20,000.00 on 16/09/2026.\n3. Enter cumulative 20,300.00 on 18/09/2026.\n4. Read consumption for 16, 17 and 18 September.",
      "Incoming No.2: 16/09 = 20,000.00 (first reading / baseline). 18/09 = 20,300.00. gapDays = 2. delta = 300. perDay = 150.00.",
      "16/09 consumption is blank (first reading day has no prior interval). 17/09 consumption = 150.00 kWh. 18/09 consumption = 150.00 kWh. Warning box is empty (readings increased). Monthly total for Incoming No.2 includes 300.00 kWh from this gap.",
      "Critical",
      "Formula: perDay = (later cumulative − earlier cumulative) / calendar days between the two reading dates; that perDay is written onto each day after the earlier reading through to the later reading date.",
      "Calculation"),
    C("ENR-UAT-007", "M03 Energy & Utility", "Daily Electricity",
      "Verify a cumulative reading lower than the previous reading is stored but not spread, and the user is warned to check for a meter replacement or typing error.",
      PRE_E + " Use Incoming No.2 on spare days after the gap test, or a third meter.",
      "1. On Incoming No.2 enter 21,000.00 on 20/09/2026.\n2. Enter 20,500.00 on 21/09/2026.\n3. Read the amber warning and the consumption cells for 20–21/09.",
      "Rollback: 20/09 21,000.00 → 21/09 20,500.00.",
      "Both readings save. Consumption for the days in that interval stays blank (not a negative kWh). Warning: 'Incoming No.2: The reading on 2026-09-21 is lower than the reading on 2026-09-20. Check for a meter replacement or a typing error.' (date format as shown).",
      "High",
      "The save is not blocked. Days stay blank so an incomplete/invalid interval is visible.",
      "Validation"),
    C("ENR-UAT-008", "M03 Energy & Utility", "Daily Electricity",
      "Verify clearing a cumulative cell removes that reading so the day returns to unused.",
      PRE_E + " Use the Incoming No.2 rollback cell or the 0.00 July reading.",
      "1. Clear the cumulative cell for the spare reading.\n2. Wait for Saved / 'Meter reading removed.'\n3. Refresh.",
      "Cell cleared (empty).",
      "Reading is deleted. Cell is blank. Any warning caused only by that pair disappears. Official Incoming No.1 September E2E readings are untouched.",
      "Medium",
      "Clearing is the implemented delete. There is no separate Delete button on the grid.",
      "Positive"),
    C("ENR-UAT-009", "M03 Energy & Utility", "Daily Electricity",
      "Verify chiller running hours and the daily remark can be saved for the site (one note per date, not per meter).",
      PRE_E,
      "1. On 01/09/2026 enter Chiller hrs 12.5 and Remark 'UAT chiller note'.\n2. Wait for Saved.\n3. Refresh.\n4. Confirm the same note is not captured separately per meter.",
      "01/09/2026; 12.5 hours; remark UAT chiller note.",
      "Values persist after refresh. Message 'Daily note saved.' One remark/chiller value applies to the site-date row.",
      "Low",
      "No server-side negative check on chiller hours (GAP-ENR-02). Do not treat a negative chiller value as a required fail unless the business later mandates it.",
      "Positive"),
    C("ENR-UAT-010", "M03 Energy & Utility", "Monthly Summary",
      "Verify September monthly consumption for Incoming No.1 equals the sum of derived daily consumption from the controlled cumulative readings.",
      "Incoming No.1 readings: 31/08/2026 = 10,000.00; 01/09 = 10,100.00; 02/09 = 10,225.00; 03/09 = 10,375.00. No further Incoming No.1 readings in September (remove extras).",
      "1. Open Daily Electricity September 2026 and confirm consumption 100.00 + 125.00 + 150.00 on 1–3 Sep.\n2. Read the Incoming No.1 monthly total on the daily footer.\n3. Open Energy Monitoring > Monthly Summary, UAT Site A, Year 2026.\n4. Read September Incoming No.1 and Total kWh.",
      "Expected daily: 01/09=100.00; 02/09=125.00; 03/09=150.00. Expected September Incoming No.1 = 375.00 kWh. Manual: 100+125+150=375.",
      "Daily footer monthly total for Incoming No.1 = 375.00. Monthly Summary September Incoming No.1 = 375.00. September Total kWh includes 375.00 plus any other meters' September consumption (Incoming No.2 must be 0.00 / unused for the isolated figure). Chart September bar for Incoming No.1 = 375.",
      "Critical",
      "This is the adapted form of the 100+125+150=375 business example. Because the product stores cumulative readings, testers enter 10000 / 10100 / 10225 / 10375, not 100, 125, 150 as typed usage.",
      "Calculation"),
    C("ENR-UAT-011", "M03 Energy & Utility", "Monthly Summary",
      "Verify two incoming meters are totalled separately and then combined on the monthly summary.",
      "Incoming No.1 September = 375.00. Incoming No.2 has the 300.00 gap-distribution consumption from 16–18/09 (if still present) or is unused (0.00).",
      "1. Open Monthly Summary 2026.\n2. Read September columns Incoming No.1, Incoming No.2, Total kWh.\n3. Add the two meter columns and compare with Total kWh.",
      "If No.2 gap test remains: 375.00 + 300.00 = 675.00. If No.2 cleared: 375.00 + 0.00 = 375.00.",
      "Per-meter September figures match Daily Electricity monthly totals. Total kWh = sum of meters. Year footer equals the sum of the twelve month totals for each meter.",
      "High",
      "Clear Incoming No.2 before the official BEI E2E if BEI should use 375 kWh only.",
      "Positive"),
    C("ENR-UAT-012", "M03 Energy & Utility", "Period selection",
      "Verify changing site, month and year reloads the correct period and does not leave the previous period's readings on screen.",
      PRE_E,
      "1. Open Daily Electricity, UAT Site A, September 2026 — confirm the 10,100.00 reading is visible.\n2. Change to August 2026 — confirm 31/08 10,000.00 and no September rows.\n3. Change to October 2026 — September readings are gone.\n4. Open Monthly Summary, change year to 2025, then back to 2026.\n5. If a second site exists, switch site and confirm Incoming No.1 UAT readings disappear.",
      "August / September / October 2026; year 2025 vs 2026.",
      "Each change shows only that period's data. September 2026 figures return when that period is selected again. No mixing of sites.",
      "High",
      "",
      "Positive"),
    C("ENR-UAT-013", "M03 Energy & Utility", "Daily Electricity",
      "Verify days that no interval covers stay blank (not zero) so an incomplete month is obviously incomplete.",
      "Incoming No.1 has readings only on 31/08 and 1–3/09. Rest of September has no later reading.",
      "1. Open Daily Electricity September 2026.\n2. Inspect 04/09/2026 through 30/09/2026 consumption cells.\n3. Read Days Covered and Average Daily.",
      "No Incoming No.1 reading after 03/09/2026.",
      "04–30 September consumption shows em-dash / blank, not 0.00. Days Covered is 3 / 30 (plus any other meter days). Average Daily = total kWh / days that have data (375 / 3 = 125.00 if only those three days and only Incoming No.1).",
      "High",
      "Blank means 'not covered', not 'zero consumption'. Monthly Summary may still display 0.00 in a meter cell when the whole month has no data for that meter (GAP-ENR-01).",
      "Positive"),
    C("ENR-UAT-014", "M03 Energy & Utility", "Building Energy Index",
      "Verify BEI is calculated as (electricity + chilled water) / gross floor area × annualisation factor, and that a value at or below target is a pass.",
      "UAT Site A. Incoming No.1 September total 375.00 kWh. Incoming No.2 unused (0). Tester can save site configuration.",
      "1. Open Energy Monitoring > Building Energy Index.\n2. Select UAT Site A, Year 2026.\n3. Set Gross floor area 10,000 m², Target BEI 170.65, Annualisation factor 12. Save configuration.\n4. On September, leave Electricity as the derived 375.00 (or type it if not prefilled).\n5. Enter Chilled water 125.00.\n6. Save the month if required and read Actual BEI, result and pass/fail.",
      "Electricity 375.00. Chilled 125.00. Total energy 500.00. GFA 10,000. Factor 12. Manual BEI = (500 / 10000) × 12 = 0.6000. Target 170.65. 0.6000 ≤ 170.65 → PASS. Result % = 100.",
      "Electricity defaults from daily readings (375.00) with hint 'From daily readings'. Total energy 500.00. Actual BEI 0.6000. Month passes. Result 100%. Configuration save: 'Energy configuration saved.' Month save: 'Monthly BEI saved.'",
      "Critical",
      "Implemented formula: actualBei = round((electricityKwh + chilledWaterKwh) / floorAreaSqm × factor, 4); pass when actualBei <= targetBei + 0.00005. Factor 12 is required when the consumption is a monthly figure and the target is an annual BEI.",
      "Calculation"),
    C("ENR-UAT-015", "M03 Energy & Utility", "Building Energy Index",
      "Verify a month whose BEI is above the target fails (result 0%) using a controlled high-consumption override.",
      "Site config still GFA 10,000; target 170.65; factor 12. Use October 2026 so September pass case remains.",
      "1. Open BEI, year 2026, October.\n2. Enter Electricity 1,200,000.00 (override) and Chilled water 300,000.00.\n3. Save.\n4. Compute (1,500,000 / 10,000) × 12 = 1,800.0000.",
      "October electricity 1,200,000.00; chilled 300,000.00; GFA 10,000; factor 12; target 170.65. Expected BEI 1800.0000. FAIL. Result 0%.",
      "Actual BEI 1800.0000. Target not met / 0%. Status Draft until finalised. September row is still 0.6000 / 100%. Year cards count October as failing and September as passing.",
      "Critical",
      "Lower BEI is better. resultPct is 100 or 0, not a partial score.",
      "Calculation"),
    C("ENR-UAT-016", "M03 Energy & Utility", "Building Energy Index",
      "Verify BEI cannot be scored when gross floor area is missing / not set, and the user is told to enter it.",
      "Use a spare site if available. Otherwise temporarily set UAT Site A GFA to 0, run the check, then restore 10,000. Do not finalise.",
      "1. Set Gross floor area to blank/0 and Save configuration.\n2. Open a month with electricity 375.\n3. Read BEI and message.\n4. Restore GFA 10,000.",
      "GFA 0 or blank.",
      "Actual BEI is blank / not scored. Message: 'Enter a gross floor area greater than zero in the site configuration.' Finalise is rejected: 'The BEI cannot be finalised until it produces a value. Check the floor area and consumption.'",
      "High",
      "Restore GFA immediately.",
      "Validation"),
    C("ENR-UAT-017", "M03 Energy & Utility", "Building Energy Index",
      "Verify a zero or negative floor area is rejected or produces no BEI, and a zero/negative target prevents scoring.",
      "KPI Admin or Site Admin/Administrator who can save energy configuration.",
      "1. Attempt GFA -10. Save.\n2. Attempt Target BEI -1. Save.\n3. Set Target BEI 0 with GFA 10,000 and open September.\n4. Restore Target 170.65 and GFA 10,000.",
      "GFA -10; target -1; target 0.",
      "Negative GFA: 'The gross floor area cannot be negative.' Negative target: 'The target BEI cannot be negative.' Target 0: BEI number may still calculate but is not scored — message 'Set a target BEI in the site configuration to score this month.' Result Not scored.",
      "High",
      "Annualisation factor ≤ 0 is reset to 1.0 (not rejected with a message).",
      "Validation"),
    C("ENR-UAT-018", "M03 Energy & Utility", "Building Energy Index",
      "Verify typing over the electricity figure marks the month as an override, and clearing it returns the value derived from daily readings.",
      "September 2026 derived electricity 375.00.",
      "1. Open BEI September.\n2. Change Electricity to 400.00. Save.\n3. Confirm hint shows Override and the derived 375.00.\n4. Clear Electricity and Save.\n5. Confirm it returns to 375.00 From daily readings.",
      "Override 400.00 then clear.",
      "After override, electricity 400.00, BEI recalculates with 400 + chilled. Hint includes Override and the derived 375.00. After clear, electricity 375.00 and override flag is removed.",
      "High",
      "Override exists because Energy daily meters and KPI PI 3B do not currently share the same meter scope (GAP-KPA-03 / G08).",
      "Positive"),
    C("ENR-UAT-019", "M03 Energy & Utility", "Building Energy Index",
      "Verify a utility officer can finalise a month to lock it, and only a setup-capable user (Site Admin / KPI Admin / Administrator) can reopen it.",
      "September BEI saved with the official 375 / 125 / 0.6000 pass figures. Utility Reader and KPI Admin (or Administrator) accounts available.",
      "1. As Utility Reader (or Admin), Finalise September. Confirm: 'Finalise this month? The values are locked once finalised.'\n2. Attempt to change chilled water. Save.\n3. As Utility Reader, confirm Reopen is not available.\n4. As KPI Admin / Administrator / Site Admin, Reopen.\n5. Change is allowed again. Re-finalise if the customer wants the month locked after UAT.",
      "September 2026 official BEI row.",
      "Finalise: 'Monthly BEI finalised.' Status Final. Edit rejected: 'This month has been finalised. Reopen it before changing the values.' Reopen available only to setup-capable roles. After reopen, status Draft and values can change.",
      "High",
      "Site Admin has API reopen/setup but no Energy menu in the shipped nav SQL (GAP-ENR-03). Administrator or KPI Admin can demonstrate reopen if Site Admin cannot see the page.",
      "Positive"),
    C("ENR-UAT-020", "M03 Energy & Utility", "Meter setup",
      "Verify a setup-capable user can add an incoming meter and can deactivate it without deleting its historical readings.",
      "Logged in as Administrator or KPI Admin (Meter setup button visible). Dedicated UAT site.",
      "1. On Daily Electricity click Meter setup.\n2. Add meter name 'UAT Incoming No.3', description 'UAT only', order 3. Save.\n3. Confirm it appears on the daily grid.\n4. Enter a one-day cumulative on that meter, then deactivate the meter.\n5. Confirm prompt: 'Deactivate this meter? Existing readings are kept.'\n6. Confirm the meter is Inactive and no longer used for new days, while the earlier reading is retained.",
      "UAT Incoming No.3.",
      "Create: 'Incoming meter saved.' Duplicate name rejected: 'A meter named \"UAT Incoming No.3\" already exists at this site.' Deactivate: 'Incoming meter deactivated.' Historical reading remains available for totals of the days it covered.",
      "Medium",
      "Utility Reader cannot see Meter setup (ENR-UAT-021).",
      "Positive"),
    C("ENR-UAT-021", "M03 Energy & Utility", "Access / Role",
      "Verify a Utility Reader can enter readings and BEI values but cannot change meter setup or site BEI configuration.",
      "UAT-UTILITY-READER role 18 only (TBC).",
      "1. Login as Utility Reader.\n2. Confirm Energy Monitoring menu is visible.\n3. Enter or edit a spare cumulative reading — allowed.\n4. Confirm Meter setup is hidden.\n5. Open BEI — electricity/chilled editable; configuration Save disabled.\n6. Confirm Reopen on a Final month is not available.",
      "UAT-UTILITY-READER.",
      "Reader can record readings, daily notes and BEI month values / finalise. Meter setup button is hidden. Site GFA/target/factor cannot be saved. Reopen is hidden. Other sites are not selectable if the account is site-scoped.",
      "High",
      "",
      "Role"),
    C("ENR-UAT-022", "M03 Energy & Utility", "Access / Role",
      "Verify PI Entry / KPI Viewer have no Energy Monitoring menu, and Site Admin has no menu even though setup APIs exist.",
      "UAT-PI-ENTRY (31) and/or UAT-KPI-VIEWER (32). Optional Site Admin (19).",
      "1. Login as PI Entry — confirm Energy Monitoring is not in the sidebar.\n2. Paste p_energy_daily — page is view-only or denied; save of a reading is rejected: 'You are not allowed to record meter readings.'\n3. Repeat as KPI Viewer.\n4. As Site Admin, confirm Energy Monitoring is not in the sidebar (shipped grants). If the URL opens, meter setup / config may be allowed — record actual behaviour.",
      "Roles 31, 32, 19.",
      "Roles 31 and 32: no menu; cannot save readings. Role 19: no menu in shipped navigation (GAP-ENR-03). Record whether direct URL allows setup. No official reading is created by a view-only user.",
      "Medium",
      "TBC — Business confirmation required on whether Site Admin should receive the Energy menu.",
      "Role"),
    C("ENR-UAT-023", "M03 Energy & Utility", "End-to-end",
      "Verify one building and one reporting month can be followed from daily cumulative readings through monthly total to a manually checked BEI.",
      "UAT Site A. Incoming No.1 only (Incoming No.2 unused). Config GFA 10,000; target 170.65; factor 12.",
      "1. Enter Incoming No.1 cumulatives: 31/08/2026 = 10,000.00; 01/09 = 10,100.00; 02/09 = 10,225.00; 03/09 = 10,375.00.\n2. Confirm daily consumption 100, 125, 150 and September meter total 375.00 kWh.\n3. Open Monthly Summary 2026 — September Incoming No.1 = 375.00.\n4. Open BEI 2026. Confirm Electricity prefilled 375.00.\n5. Enter Chilled water 125.00.\n6. Manually calculate (375+125)/10000×12 = 0.6000.\n7. Compare with system Actual BEI.\n8. Confirm pass versus 170.65.",
      "Same site UAT Site A. Same period September 2026. Same meter Incoming No.1. Same 375 kWh + 125 kWh chilled.",
      "Daily total, monthly total and BEI electricity input are all 375.00 kWh. System BEI = 0.6000. Manual BEI = 0.6000. Month passes (100%). Figures still match after refresh.",
      "Critical",
      "Do not mix Incoming No.2 gap-test kWh into this official chain.",
      "E2E"),
    C("ENR-UAT-024", "M03 Energy & Utility", "Period selection",
      "Verify a reading pair that crosses 31 December / 1 January still produces consumption on 1 January and appears in the new year monthly total.",
      PRE_E + " Use Incoming No.2 to avoid touching September E2E.",
      "1. Open Daily Electricity December 2025 (or 2026 if executing after that year exists — use 31/12/2025 and 01/01/2026 if those dates are not in the future).\n2. Enter Incoming No.2 cumulative 50,000.00 on 31/12/2025.\n3. Switch to January 2026 and enter 50,200.00 on 01/01/2026.\n4. Confirm 01/01 consumption = 200.00.\n5. Open Monthly Summary year 2026 — January Incoming No.2 includes 200.00. Year 2025 December includes 0.00 from this pair (the first reading is baseline).",
      "31/12/2025 = 50,000.00; 01/01/2026 = 50,200.00. Expected 01/01/2026 consumption 200.00 kWh.",
      "1 January shows 200.00 kWh. January 2026 monthly total for Incoming No.2 includes 200.00. December 2025 does not invent consumption on 31/12 from this first reading. If 31/12/2025 is in the future relative to the UAT day, use the most recent 31 Dec / 1 Jan that is not in the future and record the dates used.",
      "High",
      "Future dates are blocked — pick a year already elapsed, or execute after 1 Jan.",
      "Positive"),
    C("ENR-UAT-025", "M03 Energy & Utility", "Export",
      "Verify Daily, Monthly and BEI screens can export a CSV that contains the same kWh / BEI figures shown on screen.",
      "September 2026 official figures exist.",
      "1. On Daily Electricity September 2026 click Export. Open the CSV.\n2. On Monthly Summary 2026 click Export. Open the CSV.\n3. On BEI 2026 click Export. Open the CSV.\n4. Compare Incoming No.1 September 375.00 and BEI 0.6000 with the screen.",
      "Filenames like 'GEMS Daily Electricity …', 'GEMS Monthly Electricity 2026', 'GEMS Building Energy Index 2026'.",
      "Files download. September 375.00 and BEI 0.6000 (and chilled 125.00) appear in the corresponding export. Export is not a substitute for on-screen reconciliation if figures disagree.",
      "Medium",
      "Export is client-side CSV, not the JKR PDF engine.",
      "Positive"),
    C("ENR-UAT-026", "M03 Energy & Utility", "Empty state",
      "Verify Daily Electricity explains when a site has no incoming meters instead of showing a broken grid.",
      "A spare UAT site with no meters, or deactivate all meters on a disposable site. Do not deactivate Incoming No.1 on the official UAT Site A.",
      "1. Open Daily Electricity for the empty site.\n2. Open Monthly Summary for that site.\n3. Open Meter setup (Admin) and confirm 'No meters yet'.",
      "Site with zero active meters.",
      "Daily: 'No incoming meters are configured for this site. Use Meter setup to add one.' Monthly chart: 'No incoming meters are configured for this site.' Meter modal: 'No meters yet'. No JavaScript error.",
      "Low",
      "",
      "Validation"),
]


def html_cell(text: str) -> str:
    return (
        str(text)
        .replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
        .replace("\n", "<br>")
    )


def md_table(rows: list[dict]) -> str:
    headers = [c for c in COLS if c != "Case Type"]
    out = ["<table>", "<thead><tr>"]
    for h in headers:
        out.append(f"<th>{h}</th>")
    out.append("</tr></thead><tbody>")
    for r in rows:
        out.append("<tr>")
        for h in headers:
            out.append(f"<td>{html_cell(r[h])}</td>")
        out.append("</tr>")
    out.append("</tbody></table>\n")
    return "\n".join(out)


def stats(rows: list[dict]) -> dict[str, dict[str, int]]:
    modules = [
        "M01 Waste Management",
        "M02 KPI & APD",
        "M03 Energy & Utility",
        "Common",
    ]
    types = ["Positive", "Validation", "Calculation", "Role", "E2E"]
    data = {m: {t: 0 for t in types} for m in modules}
    data["TOTAL"] = {t: 0 for t in types}
    for r in rows:
        m = r["Module"] if r["Module"] != "Common" else "Common"
        if m not in data:
            m = "Common"
        t = r["Case Type"]
        data[m][t] += 1
        data["TOTAL"][t] += 1
    for m in list(data):
        data[m]["Total"] = sum(data[m][t] for t in types)
    return data


def write_csv(rows: list[dict]) -> None:
    path = OUT / "GEMS_UAT_M01_M02_M03_Test_Cases.csv"
    headers = [c for c in COLS if c != "Case Type"]
    with path.open("w", encoding="utf-8-sig", newline="") as f:
        w = csv.DictWriter(f, fieldnames=headers, extrasaction="ignore")
        w.writeheader()
        w.writerows(rows)


def write_md(rows: list[dict]) -> None:
    s = stats(rows)
    com = [r for r in rows if r["UAT ID"].startswith("COM-")]
    wst = [r for r in rows if r["UAT ID"].startswith("WST-")]
    kpa = [r for r in rows if r["UAT ID"].startswith("KPA-")]
    enr = [r for r in rows if r["UAT ID"].startswith("ENR-")]

    def st_row(name, key):
        d = s[key]
        return (
            f"| {name} | {d['Positive']} | {d['Validation']} | {d['Calculation']} | "
            f"{d['Role']} | {d['E2E']} | {d['Total']} |"
        )

    md = f"""# GEMS User Acceptance Testing Script

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

{md_table(com)}

---

## 6. M01 — Waste Management

{md_table(wst)}

---

## 7. M02 — KPI & APD

{md_table(kpa)}

---

## 8. M03 — Energy & Utility

{md_table(enr)}

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
{st_row("M01 Waste Management", "M01 Waste Management")}
{st_row("M02 KPI & APD", "M02 KPI & APD")}
{st_row("M03 Energy & Utility", "M03 Energy & Utility")}
{st_row("Common", "Common")}
{st_row("TOTAL", "TOTAL")}

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
"""
    (OUT / "GEMS_UAT_M01_M02_M03_Waste_KPI_Energy.md").write_text(md, encoding="utf-8")


def main() -> None:
    ids = [r["UAT ID"] for r in cases]
    assert len(ids) == len(set(ids)), "Duplicate UAT IDs"
    write_csv(cases)
    write_md(cases)
    s = stats(cases)
    print(f"Wrote {len(cases)} cases")
    for key in ["Common", "M01 Waste Management", "M02 KPI & APD", "M03 Energy & Utility", "TOTAL"]:
        print(key, s[key])


if __name__ == "__main__":
    main()
