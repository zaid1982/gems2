# Test Case: Work Order Import - 20 Tickets with attendance2

## Test Objective
Test the work order import functionality with 20 sample tickets where all technicians are assigned to the user "attendance2".

## Test File
- **File**: `test_wo_import_20_tickets.csv`
- **Location**: `/developer/test/`
- **Format**: Customer template (23 columns)

## Test Data Specifications

### User Assignment
- **All technicians**: `attendance2`
- **PIC Name**: `attendance2` (Person In Charge)
- **Fixed By**: `attendance2` (Technician who performed the work)

### Test Coverage

#### Sites Covered:
- Site A (8 tickets)
- Site B (6 tickets) 
- Site C (6 tickets)

#### Complaint Types:
- **Breakdown**: 5 tickets (Critical issues)
- **Client Complaint**: 4 tickets (Mixed severity)
- **Self Finding**: 4 tickets (Proactive findings)
- **Request**: 3 tickets (Non-critical requests)
- **Defect**: 2 tickets (System defects)
- **Public Complaint**: 2 tickets (External complaints)

#### Severity Distribution:
- **Critical**: 10 tickets (50%)
- **Non-Critical**: 10 tickets (50%)

#### Trade Types:
- **Mechanical**: 8 tickets (HVAC, doors, pumps)
- **Electrical**: 5 tickets (Lighting, power, audio)
- **Plumbing**: 2 tickets (Water systems)
- **General**: 5 tickets (Installation, maintenance)

#### Time Periods:
- **Date Range**: January 15, 2025 - February 23, 2025
- **Response Times**: 10 minutes to 3 hours
- **Completion Times**: Same day to next day

## Expected Test Results

### Import Validation:
1. ✅ All 20 tickets should be validated successfully
2. ✅ User "attendance2" should be found in system
3. ✅ All required fields populated
4. ✅ Date formats recognized (YYYY-MM-DD and DD/MM/YYYY)
5. ✅ Time formats validated

### Data Mapping:
1. ✅ Site names mapped to site IDs
2. ✅ Complaint types mapped to system values
3. ✅ Severity levels mapped correctly
4. ✅ Trade types assigned properly
5. ✅ User assignments processed

### Gamification Integration:
1. ✅ All tickets assigned to attendance2's PPM group
2. ✅ Completion scores calculated
3. ✅ Performance metrics updated

## Test Execution Steps

1. **Access Import Page**: Navigate to `wo_import.html`
2. **Upload File**: Select `test_wo_import_20_tickets.csv`
3. **Validate**: Run validation checks
4. **Preview**: Review parsed data
5. **Execute**: Complete import process
6. **Verify**: Check import results

## Verification Points

### Database Checks:
```sql
-- Check imported tickets
SELECT COUNT(*) FROM wo_task WHERE wo_task_is_imported = 1 AND wo_task_external_ref LIKE 'REQ-2025-%';

-- Verify user assignment
SELECT wo_task_assigned_to, COUNT(*) FROM wo_task 
WHERE wo_task_external_ref LIKE 'REQ-2025-%' 
GROUP BY wo_task_assigned_to;

-- Check workflow integration
SELECT COUNT(*) FROM wfl_transaction wt 
JOIN wo_task wo ON wt.transaction_id = wo.transaction_id 
WHERE wo.wo_task_external_ref LIKE 'REQ-2025-%';
```

### Expected Issues to Test:
1. ❓ User "attendance2" exists in system
2. ❓ User has proper site permissions
3. ❓ PPM group assignment available
4. ❓ All sites exist in system

## Success Criteria

1. **100% Import Success**: All 20 tickets imported without errors
2. **Proper Assignment**: All tickets assigned to attendance2
3. **Workflow Creation**: All tickets have associated workflow transactions
4. **Gamification Integration**: Scores and metrics updated correctly
5. **Data Integrity**: No orphaned records or missing references

## Notes

- File includes helpful comments for understanding data structure
- Mix of date formats to test parsing flexibility
- Variety of scenarios (breakdown, maintenance, complaints)
- Realistic repair descriptions and time estimates
- Some tickets have assistants, some don't (testing optional fields)
