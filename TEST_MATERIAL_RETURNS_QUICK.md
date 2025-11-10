# Quick Test Guide - Material Returns

## Step 1: Database Setup (2 minutes)

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gems2
mysql -u root -p gems2 < create_material_returns.sql
```

Verify:
```sql
mysql -u root -p gems2 -e "DESCRIBE material_returns"
```

## Step 2: Get Test Data (Find existing items)

```sql
-- Find items that can be returned (status 36 = Parts Collected)
SELECT 
    wtp.wo_task_parts_id,
    wtp.part_id,
    wtp.wo_task_parts_quantity,
    wtr.wo_task_request_order_by as technician_user_id,
    p.part_name
FROM wo_task_parts wtp
JOIN wo_task_request wtr ON wtp.wo_task_request_id = wtr.wo_task_request_id
JOIN ast_part p ON wtp.part_id = p.part_id
WHERE wtp.wo_task_parts_status = '36'
LIMIT 5;
```

**Copy these values:**
- `wo_task_parts_id`: ______
- `technician_user_id`: ______

## Step 3: Test with cURL (No Postman needed!)

### A. Get JWT Token First
```bash
# Login as technician
curl -X POST http://localhost/gems2/api/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "technician_username",
    "password": "password"
  }'
```

Copy the `token` from response.

### B. List Returnable Items
```bash
curl -X GET "http://localhost/gems2/api/m_inventory.php/return_eligible_items/USER_ID" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

Replace:
- `USER_ID` = technician_user_id from Step 2
- `YOUR_JWT_TOKEN` = token from Step 3A

**Expected:** JSON array with eligible items

### C. Submit Return Request
```bash
curl -X POST "http://localhost/gems2/api/m_inventory.php/request_return" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "woTaskPartsId": "WO_TASK_PARTS_ID",
    "quantityReturned": 2,
    "returnReason": "unused_excess",
    "returnRemarks": "Test return"
  }'
```

Replace:
- `WO_TASK_PARTS_ID` = wo_task_parts_id from Step 2

**Expected:** `{"success":true,"result":RETURN_ID}`

Copy the `RETURN_ID`.

### D. View Pending Returns (as Storekeeper)
```bash
curl -X GET "http://localhost/gems2/api/m_inventory.php/storekeeper_pending_returns" \
  -H "Authorization: Bearer STOREKEEPER_JWT_TOKEN"
```

**Expected:** Array with your pending return

### E. Confirm Return Receipt
```bash
curl -X PUT "http://localhost/gems2/api/m_inventory.php/confirm_return/RETURN_ID" \
  -H "Authorization: Bearer STOREKEEPER_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

Replace `RETURN_ID` with value from Step 3C.

**Expected:** `{"success":true,"result":"Return confirmed successfully"}`

### F. Verify Inventory Updated
```sql
-- Check the return was recorded
SELECT * FROM material_returns WHERE return_id = RETURN_ID;

-- Check inventory unlocked
SELECT part_id, part_count, part_locked, (part_count - part_locked) as available
FROM ast_part 
WHERE part_id = PART_ID;

-- Check part_sub instances updated to status 47
SELECT * FROM ast_part_sub 
WHERE part_sub_return_id = RETURN_ID;
```

## Step 4: Quick Error Tests

### Test 1: Invalid Quantity
```bash
curl -X POST "http://localhost/gems2/api/m_inventory.php/request_return" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "woTaskPartsId": "WO_TASK_PARTS_ID",
    "quantityReturned": 999,
    "returnReason": "unused_excess"
  }'
```
**Expected:** Error "Cannot return more than collected"

### Test 2: Invalid Return Reason
```bash
curl -X POST "http://localhost/gems2/api/m_inventory.php/request_return" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "woTaskPartsId": "WO_TASK_PARTS_ID",
    "quantityReturned": 1,
    "returnReason": "invalid_reason"
  }'
```
**Expected:** Error "Invalid return reason"

### Test 3: Duplicate Pending Return
Submit the same return twice without confirming.
**Expected:** Error "A pending return already exists"

## Step 5: Test Partial Returns

```bash
# Return 2 items first
curl -X POST "http://localhost/gems2/api/m_inventory.php/request_return" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "woTaskPartsId": "WO_TASK_PARTS_ID",
    "quantityReturned": 2,
    "returnReason": "unused_excess"
  }'

# Get return ID, then confirm it
curl -X PUT "http://localhost/gems2/api/m_inventory.php/confirm_return/RETURN_ID" \
  -H "Authorization: Bearer STOREKEEPER_JWT_TOKEN"

# Return remaining items
curl -X POST "http://localhost/gems2/api/m_inventory.php/request_return" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "woTaskPartsId": "SAME_WO_TASK_PARTS_ID",
    "quantityReturned": 3,
    "returnReason": "unused_excess"
  }'
```

## Common Issues

### "Parameter Authorization empty"
- Check JWT token is valid
- Format: `Authorization: Bearer YOUR_TOKEN` (note the space after Bearer)

### "Item not eligible for return"
- Check `wo_task_parts_status = '36'` in database
- Status must be "Parts Collected"

### "Not enough parts in possession"
- Check `ast_part_sub` table has records with status 36
- Parts may have been installed/consumed (status changed)

### "Item does not belong to you"
- User ID in request must match `wo_task_request.wo_task_request_order_by`

## Success Indicators

✅ **Return request created:** `material_returns` table has new row with status 'pending'  
✅ **Return confirmed:** Status changes to 'completed', dates populated  
✅ **Inventory updated:** `ast_part.part_locked` decreased  
✅ **Parts tracked:** `ast_part_sub.part_sub_status` = 47, return_id populated  
✅ **Audit logged:** `sys_audit` has entries with codes 190 (request) and 191 (confirm)

## 5-Minute Full Test

```bash
# 1. Setup
mysql -u root -p gems2 < create_material_returns.sql

# 2. Find test data
mysql -u root -p gems2 -e "SELECT wo_task_parts_id, wo_task_request_order_by FROM wo_task_parts wtp JOIN wo_task_request wtr ON wtp.wo_task_request_id = wtr.wo_task_request_id WHERE wo_task_parts_status = '36' LIMIT 1"

# 3. Get token (use your credentials)
TOKEN=$(curl -s -X POST http://localhost/gems2/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"tech1","password":"pass"}' | jq -r '.token')

# 4. List items
curl -X GET "http://localhost/gems2/api/m_inventory.php/return_eligible_items/1" \
  -H "Authorization: Bearer $TOKEN"

# 5. Submit return
curl -X POST "http://localhost/gems2/api/m_inventory.php/request_return" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"woTaskPartsId":"123","quantityReturned":1,"returnReason":"unused_excess"}'

# 6. Confirm (as storekeeper)
curl -X PUT "http://localhost/gems2/api/m_inventory.php/confirm_return/1" \
  -H "Authorization: Bearer $STOREKEEPER_TOKEN"

# 7. Verify
mysql -u root -p gems2 -e "SELECT * FROM material_returns WHERE return_id = 1"
```

## Done! 🎉

Your material returns module is working if:
- All cURL commands return valid JSON
- Database tables update correctly
- Inventory calculations are accurate
- Error messages appear for invalid requests
