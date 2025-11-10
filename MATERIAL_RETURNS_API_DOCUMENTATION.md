# Material Returns Module - Complete API Documentation

**Version:** 1.0.0  
**Date:** 10 November 2025  
**Base URL:** `https://gems.metadatasystem.my/api/m_inventory.php`  
**Authentication:** JWT Bearer Token  
**Status:** ✅ Production Ready

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [API Endpoints](#api-endpoints)
4. [Data Models](#data-models)
5. [Workflow](#workflow)
6. [Error Codes](#error-codes)
7. [Examples](#examples)
8. [Testing Guide](#testing-guide)

---

## Overview

The Material Returns module enables technicians to return collected parts back to inventory and allows storekeepers to confirm receipt and update inventory automatically.

### Key Features

- ✅ Partial returns supported (return items in batches)
- ✅ Instance-based tracking via `ast_part_sub`
- ✅ Automatic inventory updates on confirmation
- ✅ Transaction-safe operations with rollback
- ✅ Audit logging (codes 190, 191)
- ✅ Optional return deadline (informational only)
- ✅ Multiple return reasons supported

### User Roles

- **Technician (Role 8)**: Submit return requests
- **Storekeeper (Role 16)**: Confirm returns and update inventory

---

## Authentication

All endpoints require JWT authentication via headers:

```http
authorization: Bearer {jwt_token}
deviceid: {device_identifier}
```

### Getting JWT Token

```bash
curl -X POST https://gems.metadatasystem.my/api/login.php \
  -d 'action=login&username=your_username&password=your_password'
```

**Response:**
```json
{
  "success": true,
  "result": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
    "userId": "1",
    "userName": "admin",
    ...
  }
}
```

---

## API Endpoints

### 1. Get Return-Eligible Items

Get list of items that can be returned (status 36 - Parts Collected).

**Endpoint:** `GET /return_eligible_items/{userId}`

**Parameters:**
- `userId` (path) - Technician user ID

**Response:**
```json
{
  "success": true,
  "result": [
    {
      "woTaskPartsId": "1",
      "partId": "77",
      "partName": "LED Bulb 18W",
      "partCode": "LED-18W",
      "quantityCollected": 5,
      "technicianId": "1",
      "collectedDate": "2024-07-24 10:30:00",
      "workOrderNo": "WRDEMO20072400004",
      "partsInPossession": 5,
      "quantityAlreadyReturned": 0,
      "quantityAvailableToReturn": 5,
      "hasPendingReturn": false,
      "pendingReturnId": null,
      "pendingReturnQuantity": 0
    }
  ],
  "error": "",
  "errmsg": ""
}
```

**Business Rules:**
- Only shows items with status 36 (Parts Collected)
- Excludes items already fully returned
- Filters by technician ownership
- Shows available quantity accounting for parts consumed/installed

---

### 2. Submit Return Request

Technician submits a request to return parts.

**Endpoint:** `POST /request_return`

**Request Body:**
```json
{
  "woTaskPartsId": "1",
  "quantityReturned": 2,
  "returnReason": "unused_excess",
  "returnRemarks": "Items not needed for job",
  "returnDeadlineDate": "2025-11-17 17:00:00"
}
```

**Parameters:**
- `woTaskPartsId` (required) - ID from wo_task_parts table
- `quantityReturned` (required) - Number of items to return (1 to available quantity)
- `returnReason` (required) - Must be one of:
  - `unused_excess` - Collected too many
  - `wrong_part` - Incorrect item collected
  - `damaged` - Item damaged/defective
  - `other` - Other reason
- `returnRemarks` (optional) - Free text explanation
- `returnDeadlineDate` (optional) - Expected return date (not enforced)

**Response:**
```json
{
  "success": true,
  "result": 15,
  "error": "",
  "errmsg": ""
}
```
*Returns the new `return_id`*

**Validation:**
- ✅ Item must have status 36 (Parts Collected)
- ✅ User must own the item (via wo_task_request)
- ✅ Quantity must be ≤ available quantity
- ✅ Quantity must be ≤ parts still in possession
- ✅ Only one pending return per wo_task_parts_id allowed
- ✅ Return reason must be from valid list

**Error Examples:**
```json
{
  "success": false,
  "error": "[163] - Cannot return more than collected. Available to return: 3"
}

{
  "success": false,
  "error": "[172] - A pending return already exists for this item"
}

{
  "success": false,
  "error": "[157] - Item not eligible for return. Must be in Parts Collected status"
}
```

---

### 3. Get Storekeeper Pending Returns

Get all pending return requests awaiting confirmation.

**Endpoint:** `GET /storekeeper_pending_returns`

**Response:**
```json
{
  "success": true,
  "result": [
    {
      "returnId": "15",
      "woTaskPartsId": "1",
      "partId": "77",
      "technicianUserId": "1",
      "quantityReturned": 2,
      "returnStatus": "pending",
      "returnReason": "unused_excess",
      "returnRemarks": "Items not needed for job",
      "returnRequestDate": "2025-11-10 14:30:00",
      "returnDeadlineDate": "2025-11-17 17:00:00",
      "returnConfirmedDate": null,
      "storekeeperUserId": null,
      "partName": "LED Bulb 18W",
      "partCode": "LED-18W",
      "partUnit": "pcs",
      "technicianName": "John Doe",
      "workOrderNo": "WRDEMO20072400004",
      "siteName": "DEMO Site"
    }
  ],
  "error": "",
  "errmsg": ""
}
```

**Features:**
- Shows only status='pending' returns
- Joins 8 tables for complete information
- Sorted by request date (newest first)
- Includes technician name and site details

---

### 4. Get Return Details

Get details of a specific return request.

**Endpoint:** `GET /return_detail/{returnId}`

**Parameters:**
- `returnId` (path) - Material return ID

**Response:**
```json
{
  "success": true,
  "result": {
    "returnId": "15",
    "woTaskPartsId": "1",
    "partId": "77",
    "quantityReturned": 2,
    "returnStatus": "pending",
    "returnReason": "unused_excess",
    "returnRemarks": "Items not needed for job",
    "returnRequestDate": "2025-11-10 14:30:00",
    "returnDeadlineDate": "2025-11-17 17:00:00",
    "partName": "LED Bulb 18W",
    "technicianName": "John Doe",
    "workOrderNo": "WRDEMO20072400004"
  },
  "error": "",
  "errmsg": ""
}
```

**Use Cases:**
- View return details before confirmation
- Check return status
- Review return history

---

### 5. Confirm Return Receipt (CRITICAL)

Storekeeper confirms receipt and updates inventory atomically.

**Endpoint:** `PUT /confirm_return/{returnId}`

**Parameters:**
- `returnId` (path) - Material return ID to confirm

**Request Body:**
```json
{}
```
*(Empty body, or can include additional notes)*

**Response:**
```json
{
  "success": true,
  "result": "Return confirmed successfully",
  "error": "",
  "errmsg": ""
}
```

**What Happens:**
1. ✅ Validates return status is 'pending'
2. ✅ Begins database transaction
3. ✅ Updates `material_returns.return_status` → 'completed'
4. ✅ Sets `return_confirmed_date` and `storekeeper_user_id`
5. ✅ Updates `ast_part_sub` instances (FIFO):
   - Changes `part_sub_status` from 36 → 47 (Returned)
   - Sets `part_sub_return_id` reference
   - Sets `part_sub_returned_date` and `part_sub_returned_by`
6. ✅ Updates `ast_part.part_locked` (decreases by quantity)
7. ✅ Inserts `inventory_logs` audit record (if table exists)
8. ✅ Commits transaction (or rolls back on error)

**Inventory Calculation:**
```
BEFORE: part_available = 70  (count=100, locked=30)
RETURN: 5 items confirmed
AFTER:  part_available = 75  (count=100, locked=25)
```

**Critical Notes:**
- ⚠️ Uses row locking (`FOR UPDATE` in production)
- ⚠️ Transaction-safe - either all updates succeed or all roll back
- ⚠️ FIFO selection of `ast_part_sub` instances (oldest first)
- ⚠️ Validates enough parts exist before updating

**Error Examples:**
```json
{
  "success": false,
  "error": "[298] - Return request already completed or invalid status"
}

{
  "success": false,
  "error": "[320] - Not enough parts in collected status to process return. Expected: 5, Found: 3"
}
```

---

### 6. Get Return History

Get return history with optional filters.

**Endpoint:** `GET /return_history?userId={userId}&status={status}&dateFrom={dateFrom}&dateTo={dateTo}`

**Query Parameters:**
- `userId` (optional) - Filter by technician user ID
- `status` (optional) - Filter by status: `pending`, `completed`, or `all`
- `dateFrom` (optional) - Start date (YYYY-MM-DD)
- `dateTo` (optional) - End date (YYYY-MM-DD)

**Example:**
```
GET /return_history?userId=1&status=completed&dateFrom=2025-11-01&dateTo=2025-11-10
```

**Response:**
```json
{
  "success": true,
  "result": [
    {
      "returnId": "14",
      "woTaskPartsId": "2",
      "partId": "78",
      "technicianUserId": "1",
      "quantityReturned": 3,
      "returnStatus": "completed",
      "returnReason": "unused_excess",
      "returnRequestDate": "2025-11-09 10:00:00",
      "returnConfirmedDate": "2025-11-09 15:30:00",
      "storekeeperUserId": "5"
    }
  ],
  "error": "",
  "errmsg": ""
}
```

---

### 7. Get Return Statistics

Get summary statistics for returns.

**Endpoint:** `GET /return_statistics?userId={userId}`

**Query Parameters:**
- `userId` (optional) - Filter by user (omit for all users)

**Response:**
```json
{
  "success": true,
  "result": {
    "totalReturns": 25,
    "pendingReturns": 3,
    "completedReturns": 22,
    "totalQuantityReturned": 187
  },
  "error": "",
  "errmsg": ""
}
```

**Use Cases:**
- Dashboard metrics
- Performance tracking
- Audit reports

---

## Data Models

### material_returns Table

| Column | Type | Description |
|--------|------|-------------|
| return_id | BIGINT(20) PK | Auto-increment primary key |
| wo_task_parts_id | BIGINT(20) FK | Reference to original collection |
| part_id | BIGINT(20) FK | Reference to ast_part |
| technician_user_id | INT(11) FK | User requesting return |
| storekeeper_user_id | INT(11) FK | User confirming return |
| quantity_returned | INT | Number of items returned |
| return_status | ENUM | 'pending' or 'completed' |
| return_reason | VARCHAR(255) | Reason code |
| return_remarks | TEXT | Optional free text |
| return_request_date | DATETIME | When submitted |
| return_confirmed_date | DATETIME | When confirmed |
| return_deadline_date | DATETIME | Optional deadline |
| created_at | TIMESTAMP | Record creation |
| updated_at | TIMESTAMP | Last update |

### ast_part_sub Extensions

| Column | Type | Description |
|--------|------|-------------|
| part_sub_return_id | BIGINT(20) FK | Reference to material_returns |
| part_sub_returned_date | DATETIME | When returned |
| part_sub_returned_by | INT(11) FK | Storekeeper who confirmed |

### Status Codes

| Code | Description | Usage |
|------|-------------|-------|
| 36 | Parts Collected | Eligible for return |
| 47 | Returned | Part returned to inventory |
| 48 | Return Pending | Temporary status (future use) |

---

## Workflow

### Happy Path: Complete Return Process

```
┌──────────────┐
│  Technician  │
└──────┬───────┘
       │ 1. GET /return_eligible_items/1
       │    → Sees 5 items available
       │
       │ 2. POST /request_return
       │    { woTaskPartsId: 1, quantityReturned: 5, reason: "unused_excess" }
       │    → Returns return_id: 15
       ▼
┌──────────────┐
│   Database   │  Status: pending
└──────┬───────┘  record created
       │
       ▼
┌──────────────┐
│ Storekeeper  │
└──────┬───────┘
       │ 3. GET /storekeeper_pending_returns
       │    → Sees return_id: 15 pending
       │
       │ 4. GET /return_detail/15
       │    → Reviews details
       │
       │ 5. PUT /confirm_return/15
       │    → Confirms receipt
       ▼
┌──────────────┐
│  TRANSACTION │
│   BEGIN      │
└──────┬───────┘
       │ a. Update material_returns → completed
       │ b. Update ast_part_sub (5 rows) → status 47
       │ c. Update ast_part.part_locked -= 5
       │ d. Insert inventory_logs
       │ e. COMMIT
       ▼
┌──────────────┐
│   Inventory  │  part_available += 5
│   Updated    │  ✅ Complete
└──────────────┘
```

### Partial Returns Flow

```
Day 1: Collected 10 items
  └─> Return 3 items
       POST /request_return { quantity: 3 }
       → Confirmed
       → Available: 7

Day 3: Return 4 more items
  └─> Return 4 items
       POST /request_return { quantity: 4 }
       → Confirmed
       → Available: 3

Day 5: Return final 3 items
  └─> Return 3 items
       POST /request_return { quantity: 3 }
       → Confirmed
       → Available: 0 (fully returned)
```

---

## Error Codes

### Common Errors

| Error Message | Cause | Solution |
|---------------|-------|----------|
| Parameter Authorization empty | Missing JWT token | Include `authorization` header |
| Parameter Deviceid empty | Missing device ID | Include `deviceid` header |
| Invalid return reason | Reason not in whitelist | Use: unused_excess, wrong_part, damaged, or other |
| Cannot return more than collected | Quantity exceeds available | Check quantity_available_to_return |
| Item does not belong to you | Wrong technician | Only return own items |
| Item not eligible for return | Wrong status | Item must be status 36 |
| A pending return already exists | Duplicate request | Wait for confirmation or cancel existing |
| Not enough parts in possession | Parts consumed/installed | Check parts_in_possession count |
| Return request not found | Invalid return_id | Verify return exists |
| Return already completed | Duplicate confirmation | Check return_status first |

---

## Examples

### Example 1: Complete Return Flow

```bash
# Step 1: Login
TOKEN=$(curl -s -X POST https://gems.metadatasystem.my/api/login.php \
  -d 'action=login&username=technician1&password=pass123' \
  | jq -r '.result.token')

# Step 2: Get eligible items
curl -X GET "https://gems.metadatasystem.my/api/m_inventory.php/return_eligible_items/1" \
  -H "authorization: Bearer $TOKEN" \
  -H "deviceid: mobile-app-001"

# Step 3: Submit return
curl -X POST "https://gems.metadatasystem.my/api/m_inventory.php/request_return" \
  -H "authorization: Bearer $TOKEN" \
  -H "deviceid: mobile-app-001" \
  -H "Content-Type: application/json" \
  -d '{
    "woTaskPartsId": "1",
    "quantityReturned": 2,
    "returnReason": "unused_excess",
    "returnRemarks": "Job completed with fewer items"
  }'

# Step 4: Storekeeper views pending
STORE_TOKEN=$(curl -s -X POST https://gems.metadatasystem.my/api/login.php \
  -d 'action=login&username=storekeeper1&password=pass123' \
  | jq -r '.result.token')

curl -X GET "https://gems.metadatasystem.my/api/m_inventory.php/storekeeper_pending_returns" \
  -H "authorization: Bearer $STORE_TOKEN" \
  -H "deviceid: web-app-001"

# Step 5: Confirm return
curl -X PUT "https://gems.metadatasystem.my/api/m_inventory.php/confirm_return/15" \
  -H "authorization: Bearer $STORE_TOKEN" \
  -H "deviceid: web-app-001" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### Example 2: View History

```bash
# Get all completed returns for user
curl -X GET "https://gems.metadatasystem.my/api/m_inventory.php/return_history?userId=1&status=completed" \
  -H "authorization: Bearer $TOKEN" \
  -H "deviceid: mobile-app-001"

# Get statistics
curl -X GET "https://gems.metadatasystem.my/api/m_inventory.php/return_statistics?userId=1" \
  -H "authorization: Bearer $TOKEN" \
  -H "deviceid: mobile-app-001"
```

---

## Testing Guide

### Pre-requisites

1. **Database:** Migration completed (`create_material_returns_safe.sql`)
2. **Test Data:** Items with status 36 exist in `wo_task_parts`
3. **Users:** Valid technician and storekeeper accounts
4. **Tokens:** JWT tokens for both user types

### Test Checklist

#### Happy Path Tests
- [ ] GET eligible items returns correct list
- [ ] POST return request creates pending return
- [ ] GET pending returns shows new request
- [ ] GET return detail shows correct data
- [ ] PUT confirm return updates inventory
- [ ] GET history shows completed return
- [ ] GET statistics reflects updates

#### Error Handling Tests
- [ ] Invalid quantity rejected (exceeds collected)
- [ ] Invalid reason rejected
- [ ] Duplicate pending return blocked
- [ ] Unauthorized access blocked (wrong user)
- [ ] Invalid status rejected (non-36 items)
- [ ] Missing JWT returns error
- [ ] Missing deviceid returns error

#### Edge Cases
- [ ] Partial return (return 2, then 3 from 5 collected)
- [ ] Zero quantity rejected
- [ ] Negative quantity rejected
- [ ] Return after parts consumed (insufficient possession)
- [ ] Concurrent returns (transaction isolation)

### Database Verification Queries

```sql
-- Check return was created
SELECT * FROM material_returns WHERE return_id = ?;

-- Check inventory updated
SELECT part_id, part_count, part_locked, 
       (part_count - part_locked) AS available 
FROM ast_part 
WHERE part_id = ?;

-- Check part_sub instances updated
SELECT * FROM ast_part_sub 
WHERE part_sub_return_id = ?;

-- Check audit trail
SELECT * FROM sys_audit 
WHERE audit_code IN ('190', '191') 
ORDER BY audit_id DESC LIMIT 10;

-- Check inventory logs (optional)
SELECT * FROM inventory_logs 
WHERE reference_type = 'material_return' 
ORDER BY change_date DESC LIMIT 10;
```

---

## Postman Collection

Import: `GEMS2_Material_Returns.postman_collection.json`

**Collections:**
1. **Technician Endpoints** (5 requests)
   - Get Eligible Items
   - Submit Return
   - Get History
   - Get Statistics
   - View Return Detail

2. **Storekeeper Endpoints** (3 requests)
   - Get Pending Returns
   - Get Return Detail
   - Confirm Return

3. **Error Tests** (5 requests)
   - Invalid Quantity
   - Invalid Reason
   - Duplicate Pending
   - Unauthorized Access
   - Wrong Status

**Variables:**
- `base_url`: https://gems.metadatasystem.my/api
- `jwt_token_technician`: (your technician JWT)
- `jwt_token_storekeeper`: (your storekeeper JWT)
- `device_id`: test-device-001

---

## Production Deployment

### Checklist

- [x] Database migration completed
- [x] Views added to sql.php
- [x] API routes integrated
- [x] JWT authentication enforced
- [x] Transaction safety verified
- [ ] Load testing completed
- [ ] Security audit completed
- [ ] User training completed
- [ ] Documentation reviewed

### Monitoring

**Key Metrics:**
- Return request rate (per hour/day)
- Average confirmation time
- Inventory accuracy (audits)
- Error rate by endpoint
- Transaction rollback frequency

**Alerts:**
- High error rate (>5%)
- Slow confirmations (>5 min average)
- Inventory discrepancies
- Database transaction deadlocks

---

## Support

### Troubleshooting

**Problem:** "Parameter Authorization empty"  
**Solution:** Ensure header is lowercase `authorization: Bearer {token}`

**Problem:** "Not enough parts in possession"  
**Solution:** Parts may have been consumed/installed. Check `ast_part_sub` status.

**Problem:** "Return already completed"  
**Solution:** Check `material_returns.return_status` before confirming.

**Problem:** Transaction timeout  
**Solution:** Check for database locks, increase timeout, or optimize queries.

### Debug Mode

Enable detailed logging:
```php
// In api/class/Constant.php
public static $isLogged = true;
```

View logs:
```bash
tail -f /Applications/XAMPP/logs/gems/debug/debug_*.log
tail -f /Applications/XAMPP/logs/gems/error/error_*.log
```

---

## Appendices

### A. Database Schema Diagram

```
┌─────────────────────┐
│  material_returns   │
│─────────────────────│
│ return_id PK        │◄──┐
│ wo_task_parts_id FK │   │
│ part_id FK          │   │
│ technician_user_id  │   │
│ storekeeper_user_id │   │
│ quantity_returned   │   │
│ return_status       │   │
│ return_reason       │   │
│ ...                 │   │
└─────────────────────┘   │
                          │
┌─────────────────────┐   │
│   ast_part_sub      │   │
│─────────────────────│   │
│ part_sub_id PK      │   │
│ part_sub_return_id  │───┘
│ part_sub_status     │
│ part_sub_returned_* │
│ ...                 │
└─────────────────────┘
```

### B. API Response Codes

| HTTP Status | Meaning | When |
|-------------|---------|------|
| 200 | Success | Request processed successfully |
| 400 | Bad Request | Invalid parameters |
| 401 | Unauthorized | Invalid/missing JWT |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 500 | Server Error | Internal system error |

### C. Return Reason Codes

| Code | Display Name | When to Use |
|------|-------------|-------------|
| unused_excess | Unused / Excess | Collected more than needed |
| wrong_part | Wrong Part | Incorrect item collected |
| damaged | Damaged/Defective | Item is faulty |
| other | Other Reason | Any other case |

---

**Document Version:** 1.0.0  
**Last Updated:** 10 November 2025  
**Maintained By:** GEMS2 Development Team  
**Status:** ✅ Production Ready

---

**End of Documentation**
