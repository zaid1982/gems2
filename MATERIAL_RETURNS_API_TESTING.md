# Material Returns API - Testing Guide

**Implementation Date:** 9 November 2025  
**Module:** GEMS2 Inventory Management  
**Base URL:** `http://localhost/gems2/api/m_inventory.php`

---

## 🚀 Quick Start

### 1. Database Setup

```bash
# Run the migration script
mysql -u root -p gems2 < create_material_returns.sql

# Verify tables created
mysql -u root -p gems2 -e "SHOW TABLES LIKE '%return%'"
```

### 2. Test Prerequisites

- ✅ Existing technician user with collected parts (status 36)
- ✅ Existing storekeeper user with appropriate role
- ✅ Valid JWT tokens for both users
- ✅ At least one `wo_task_parts` record with `wo_task_parts_status = '36'`

---

## 📋 API Endpoints Summary

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/return_eligible_items/:userId` | Technician | List returnable items |
| POST | `/request_return` | Technician | Submit return request |
| GET | `/storekeeper_pending_returns` | Storekeeper | List pending returns |
| GET | `/return_detail/:returnId` | Both | Get return details |
| PUT | `/confirm_return/:returnId` | Storekeeper | Confirm receipt |
| GET | `/return_history?filters` | Both | View history |
| GET | `/return_statistics?userId` | Both | Get statistics |

---

## 🧪 Test Cases

### Test 1: Get Return-Eligible Items

**Endpoint:** `GET /m_inventory.php/return_eligible_items/{userId}`

**Headers:**
```http
Authorization: Bearer {jwt_token}
deviceid: {device_id}
```

**Expected Response (Success):**
```json
{
  "success": true,
  "result": [
    {
      "woTaskPartsId": "12345",
      "partId": "PT-001",
      "quantityCollected": 10,
      "collectedDate": "2025-11-05 14:30:00",
      "woTaskNo": "WRDEMO25110500012",
      "itemDescription": "Bolt M8x20mm Steel hex head",
      "storeName": "Main Workshop",
      "quantityAlreadyReturned": 0,
      "quantityAvailableToReturn": 10,
      "partsInPossession": 10,
      "hasPendingReturn": false,
      "pendingReturnId": null
    }
  ]
}
```

**Test Scenarios:**
- ✅ User with collected parts (status 36)
- ✅ User with no collected parts (empty array)
- ✅ User with partially returned items
- ✅ User with pending return requests

---

### Test 2: Submit Return Request (Full Return)

**Endpoint:** `POST /m_inventory.php/request_return`

**Headers:**
```http
Authorization: Bearer {jwt_token}
deviceid: {device_id}
Content-Type: application/json
```

**Request Body:**
```json
{
  "woTaskPartsId": "12345",
  "quantityReturned": 10,
  "returnReason": "unused_excess",
  "returnRemarks": "Job completed early, parts not needed",
  "returnDeadlineDate": "2025-11-15 17:00:00"
}
```

**Expected Response (Success):**
```json
{
  "success": true,
  "result": {
    "returnId": "789"
  },
  "errmsg": "Return request submitted successfully. Waiting for storekeeper confirmation."
}
```

**Valid Return Reasons:**
- `unused_excess` - Unused/Excess
- `wrong_part` - Wrong Part
- `damaged` - Damaged/Defective
- `other` - Other

**Test Scenarios:**
- ✅ Full quantity return
- ✅ Optional `returnDeadlineDate` omitted
- ✅ Optional `returnRemarks` empty
- ❌ Invalid `woTaskPartsId`
- ❌ Invalid `returnReason`
- ❌ Quantity = 0 or negative
- ❌ Item not owned by user
- ❌ Item status not 36
- ❌ Pending return already exists

---

### Test 3: Submit Return Request (Partial Return)

**Endpoint:** `POST /m_inventory.php/request_return`

**Request Body:**
```json
{
  "woTaskPartsId": "12345",
  "quantityReturned": 5,
  "returnReason": "damaged",
  "returnRemarks": "Found 5 units defective during inspection"
}
```

**Expected Response (Success):**
```json
{
  "success": true,
  "result": {
    "returnId": "790"
  },
  "errmsg": "Return request submitted successfully. Waiting for storekeeper confirmation."
}
```

**Test Scenarios:**
- ✅ Return 5 out of 10 collected
- ✅ Return remaining 5 later (separate request)
- ❌ Return more than collected
- ❌ Return more than available (after previous returns)

---

### Test 4: Get Storekeeper Pending Returns

**Endpoint:** `GET /m_inventory.php/storekeeper_pending_returns`

**Headers:**
```http
Authorization: Bearer {storekeeper_jwt_token}
deviceid: {device_id}
```

**Expected Response (Success):**
```json
{
  "success": true,
  "result": [
    {
      "returnId": "789",
      "woTaskPartsId": "12345",
      "technicianName": "John Doe",
      "technicianId": "123",
      "itemDescription": "Bolt M8x20mm Steel hex head",
      "partCode": "PT-001",
      "quantityReturned": 5,
      "returnReason": "unused_excess",
      "returnRemarks": "Completed work early",
      "returnRequestDate": "2025-11-08 10:30:00",
      "returnDeadlineDate": "2025-11-15 17:00:00",
      "woTaskNo": "WRDEMO25110500012",
      "storeName": "Main Workshop",
      "currentStock": 45
    }
  ]
}
```

**Test Scenarios:**
- ✅ Multiple pending returns
- ✅ No pending returns (empty array)
- ✅ Sorted by request date (newest first)

---

### Test 5: Get Return Detail

**Endpoint:** `GET /m_inventory.php/return_detail/{returnId}`

**Headers:**
```http
Authorization: Bearer {jwt_token}
deviceid: {device_id}
```

**Expected Response (Success):**
```json
{
  "success": true,
  "result": {
    "returnId": "789",
    "woTaskPartsId": "12345",
    "partId": "PT-001",
    "itemDescription": "Bolt M8x20mm Steel hex head",
    "technicianName": "John Doe",
    "quantityReturned": 5,
    "returnReason": "unused_excess",
    "returnRemarks": "Completed work early",
    "returnStatus": "pending",
    "returnRequestDate": "2025-11-08 10:30:00",
    "returnConfirmedDate": null,
    "woTaskNo": "WRDEMO25110500012",
    "currentStock": 45
  }
}
```

**Test Scenarios:**
- ✅ Valid returnId (pending)
- ✅ Valid returnId (completed)
- ❌ Invalid returnId

---

### Test 6: Confirm Return Receipt (Critical Transaction)

**Endpoint:** `PUT /m_inventory.php/confirm_return/{returnId}`

**Headers:**
```http
Authorization: Bearer {storekeeper_jwt_token}
deviceid: {device_id}
```

**Request Body:** (empty or `{}`)

**Expected Response (Success):**
```json
{
  "success": true,
  "result": {
    "newAvailable": 50,
    "newLocked": 40,
    "returnedQuantity": 5
  },
  "errmsg": "Return confirmed successfully. Inventory updated with 5 items. New available stock: 50"
}
```

**Database Changes Verified:**
1. ✅ `material_returns.return_status` = `'completed'`
2. ✅ `material_returns.return_confirmed_date` = NOW()
3. ✅ `material_returns.storekeeper_user_id` = storekeeper's userId
4. ✅ 5× `ast_part_sub.part_sub_status` = `'47'` (Returned)
5. ✅ 5× `ast_part_sub.part_sub_return_id` = returnId
6. ✅ 5× `ast_part_sub.part_sub_returned_date` = NOW()
7. ✅ `ast_part.part_locked` decreased by 5
8. ✅ `inventory_logs` entry created (if table exists)

**Test Scenarios:**
- ✅ Confirm valid pending return
- ✅ Verify inventory calculation: `part_available = part_count - part_locked`
- ❌ Confirm already-completed return
- ❌ Confirm with invalid returnId
- ❌ Not enough parts in status 36 (parts consumed)
- 🔒 **Transaction rollback** if inventory update fails
- 🔒 **Concurrent confirmation** prevention (race condition)

---

### Test 7: Get Return History

**Endpoint:** `GET /m_inventory.php/return_history?userId=123&status=completed&dateFrom=2025-11-01&dateTo=2025-11-30`

**Headers:**
```http
Authorization: Bearer {jwt_token}
deviceid: {device_id}
```

**Query Parameters (all optional):**
- `userId` - Filter by technician
- `status` - `pending`, `completed`, or `all`
- `dateFrom` - Start date (YYYY-MM-DD)
- `dateTo` - End date (YYYY-MM-DD)

**Expected Response (Success):**
```json
{
  "success": true,
  "result": [
    {
      "returnId": "788",
      "woTaskPartsId": "12344",
      "quantityReturned": 3,
      "returnReason": "wrong_part",
      "returnStatus": "completed",
      "returnRequestDate": "2025-11-07 15:00:00",
      "returnConfirmedDate": "2025-11-07 16:30:00",
      "technicianUserId": "123"
    }
  ]
}
```

---

### Test 8: Get Return Statistics

**Endpoint:** `GET /m_inventory.php/return_statistics?userId=123`

**Headers:**
```http
Authorization: Bearer {jwt_token}
deviceid: {device_id}
```

**Expected Response (Success):**
```json
{
  "success": true,
  "result": {
    "totalReturns": 5,
    "pendingReturns": 1,
    "completedReturns": 4,
    "totalQuantityReturned": 18
  }
}
```

---

## 🔒 Security Tests

### Auth Validation
```bash
# Missing JWT token
curl -X GET http://localhost/gems2/api/m_inventory.php/return_eligible_items/123 \
  -H "deviceid: test-device"
# Expected: 401 - "Parameter Authorization empty"

# Invalid JWT token
curl -X GET http://localhost/gems2/api/m_inventory.php/return_eligible_items/123 \
  -H "Authorization: Bearer invalid_token_here" \
  -H "deviceid: test-device"
# Expected: 401 - "Signature verification failed"
```

### Ownership Validation
```bash
# User A tries to return User B's items
curl -X POST http://localhost/gems2/api/m_inventory.php/request_return \
  -H "Authorization: Bearer {userA_token}" \
  -H "deviceid: test-device" \
  -H "Content-Type: application/json" \
  -d '{
    "woTaskPartsId": "99999",
    "quantityReturned": 5,
    "returnReason": "unused_excess"
  }'
# Expected: 400 - "Unauthorized: Item does not belong to you"
```

---

## ⚠️ Error Handling Tests

### Invalid Input
```json
// Missing required field
{
  "woTaskPartsId": "12345",
  "returnReason": "unused_excess"
}
// Expected: "Parameter quantityReturned empty"

// Invalid return reason
{
  "woTaskPartsId": "12345",
  "quantityReturned": 5,
  "returnReason": "invalid_reason"
}
// Expected: "Invalid return reason. Must be one of: unused_excess, wrong_part, damaged, other"

// Zero quantity
{
  "woTaskPartsId": "12345",
  "quantityReturned": 0,
  "returnReason": "unused_excess"
}
// Expected: "Quantity returned must be greater than 0"
```

### Business Logic Errors
```json
// Return more than collected
{
  "woTaskPartsId": "12345",
  "quantityReturned": 20,
  "returnReason": "unused_excess"
}
// Expected: "Cannot return more than collected. Available to return: 10"

// Duplicate pending return
{
  "woTaskPartsId": "12345",
  "quantityReturned": 5,
  "returnReason": "unused_excess"
}
// Expected: "A pending return already exists for this item. Please wait for storekeeper confirmation"

// Parts already consumed/installed
{
  "woTaskPartsId": "12345",
  "quantityReturned": 10,
  "returnReason": "unused_excess"
}
// Expected: "Cannot return 10 items. Only 3 parts still in your possession (others may have been installed/used)"
```

---

## 🧪 Transaction Rollback Tests

### Scenario: Confirm return with inventory error

**Setup:**
1. Create pending return (returnId = 999)
2. Manually corrupt `ast_part_sub` data (delete some records)
3. Attempt to confirm return

**Expected Behavior:**
- ✅ Transaction rolls back
- ✅ `material_returns.return_status` remains `'pending'`
- ✅ No changes to `ast_part.part_locked`
- ✅ Error message returned
- ✅ Database remains consistent

**Test Command:**
```sql
-- Corrupt data
DELETE FROM ast_part_sub WHERE wo_task_parts_id = 12345 LIMIT 3;

-- Try to confirm (via API)
-- Should fail with: "Not enough parts in collected status to process return"

-- Verify rollback
SELECT return_status FROM material_returns WHERE return_id = 999;
-- Expected: 'pending'
```

---

## 📊 Performance Tests

### Concurrent Return Confirmations

**Scenario:** Two storekeepers confirm same return simultaneously

**Test Script:**
```bash
#!/bin/bash
# Requires GNU parallel or similar
echo "Testing concurrent confirmations..."

parallel -j 2 curl -X PUT \
  http://localhost/gems2/api/m_inventory.php/confirm_return/999 \
  -H "Authorization: Bearer {token}" \
  -H "deviceid: test-device" \
  ::: A B

# Expected: One succeeds, one fails with "Return request already completed"
```

---

## 📝 Manual Verification Checklist

After running all tests, manually verify:

### Database Integrity
```sql
-- Check return counts
SELECT COUNT(*) FROM material_returns;
SELECT COUNT(*) FROM material_returns WHERE return_status = 'pending';
SELECT COUNT(*) FROM material_returns WHERE return_status = 'completed';

-- Check part_sub status transitions
SELECT part_sub_status, COUNT(*) 
FROM ast_part_sub 
WHERE wo_task_parts_id IN (SELECT wo_task_parts_id FROM material_returns)
GROUP BY part_sub_status;
-- Expected: Status 36 (collected) and 47 (returned)

-- Check inventory calculations
SELECT 
  p.part_id,
  p.part_count,
  p.part_locked,
  p.part_count - p.part_locked AS calculated_available
FROM ast_part p
WHERE p.part_id IN (SELECT DISTINCT part_id FROM material_returns);

-- Check foreign key integrity
SELECT COUNT(*) FROM material_returns mr
LEFT JOIN wo_task_parts wtp ON mr.wo_task_parts_id = wtp.wo_task_parts_id
WHERE wtp.wo_task_parts_id IS NULL;
-- Expected: 0 (no orphaned records)

-- Check inventory logs (if table exists)
SELECT * FROM inventory_logs 
WHERE reference_type = 'material_return' 
ORDER BY change_date DESC 
LIMIT 10;
```

### View Verification
```sql
-- Test vw_return_eligible_items
SELECT * FROM vw_return_eligible_items LIMIT 5;

-- Test vw_storekeeper_pending_returns
SELECT * FROM vw_storekeeper_pending_returns;

-- Verify calculated fields
SELECT 
  wo_task_parts_id,
  quantity_collected,
  quantity_already_returned,
  quantity_available_to_return,
  parts_in_possession
FROM vw_return_eligible_items
WHERE quantity_available_to_return <> (quantity_collected - quantity_already_returned);
-- Expected: Empty result (calculations consistent)
```

---

## 🐛 Known Issues & Limitations

1. **Partial Returns Multiple Times**
   - ✅ Supported: Can submit multiple partial returns for same `wo_task_parts_id`
   - ⚠️ Limitation: Only one pending return at a time

2. **Return Window**
   - ℹ️ `return_deadline_date` is optional and not enforced
   - ℹ️ No automatic expiration of return requests

3. **Inventory Logs**
   - ℹ️ Optional table - logs only created if `inventory_logs` table exists
   - ℹ️ No impact on core functionality

4. **Race Conditions**
   - ⚠️ Two technicians returning parts from same `wo_task_parts_id` simultaneously
   - ✅ Mitigated by: Pending return check, transaction locking

---

## 📞 Debugging Tips

### Enable Debug Logging
```php
// In api/library/constant.php
public static $isLogged = true;
```

### Check Logs
```bash
tail -f /Applications/XAMPP/xamppfiles/htdocs/gems2/logs/debug.log
tail -f /Applications/XAMPP/xamppfiles/htdocs/gems2/logs/error.log
```

### Common Issues

**Issue:** "Return request not found"
- Check `returnId` exists in `material_returns` table
- Verify user has access (ownership for technicians)

**Issue:** "Not enough parts in collected status"
- Parts may have been marked as installed/used (status changed from 36)
- Check `ast_part_sub` table for actual status

**Issue:** "Invalid locked quantity calculation"
- Inventory data corrupted (locked > count)
- Run integrity check: `SELECT * FROM ast_part WHERE part_locked > part_count`

---

## ✅ Test Completion Checklist

- [ ] Database schema created successfully
- [ ] All GET endpoints return valid responses
- [ ] POST request_return accepts full returns
- [ ] POST request_return accepts partial returns
- [ ] PUT confirm_return updates inventory correctly
- [ ] Transaction rollback works on error
- [ ] Auth validation blocks unauthorized access
- [ ] Ownership validation prevents cross-user access
- [ ] All error messages are user-friendly
- [ ] Database integrity maintained after all operations
- [ ] Views return accurate calculated data
- [ ] Concurrent operations handled safely
- [ ] Audit logs created for all operations
- [ ] Postman collection created and tested

---

**Testing Date:** 9 November 2025  
**Tested By:** _________________  
**Status:** ⬜ Pass / ⬜ Fail  
**Notes:** _________________
