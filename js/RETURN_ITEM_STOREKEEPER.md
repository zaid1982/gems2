# Return Item Module - Backend Implementation Specification

> **Project:** GEMS 2.0 Facilities Management System  
> **Module:** Material Return Workflow  
> **Backend Framework:** PHP (Backend/gems2)  
> **Database:** MySQL  
> **API Base URL:** `https://gems.metadatasystem.my`  
> **Date:** 9 November 2025

---

## 📋 Business Requirements

### Overview
Technicians can return items they previously collected from the storekeeper. The workflow involves:
1. **Technician** submits a return request
2. **Storekeeper** confirms receipt of the returned item
3. **System** updates inventory (`partAvailable` increases)

### Key Rules
- ✅ Only items with status "Parts Collected" (36) can be returned
- ✅ Full quantity returns only (no partial returns)
- ✅ Return reasons captured via predefined dropdown
- ✅ Inventory updates **only after** storekeeper confirms receipt
- ✅ No rejection workflow (storekeeper can only confirm)
- ✅ One return request per `woTaskPartsId` (no duplicates)
- ✅ Single store per collection (no multi-store filtering)
- ✅ All technicians and storekeepers have access

---

## 🗄️ Database Schema

### New Table: `material_returns`

```sql
CREATE TABLE material_returns (
    returnId INT PRIMARY KEY AUTO_INCREMENT,
    woTaskPartsId INT NOT NULL COMMENT 'Reference to original material collection',
    partId INT NOT NULL COMMENT 'Reference to parts table',
    technicianUserId INT NOT NULL COMMENT 'User who requested the return',
    storekeeperUserId INT NULL COMMENT 'User who confirmed the return',
    quantityReturned INT NOT NULL COMMENT 'Quantity being returned (always full)',
    returnStatus ENUM('pending', 'completed') DEFAULT 'pending' COMMENT 'Return workflow status',
    returnReason VARCHAR(255) NOT NULL COMMENT 'Predefined reason from dropdown',
    returnRemarks TEXT NULL COMMENT 'Optional free text remarks',
    returnRequestDate DATETIME NOT NULL COMMENT 'When technician submitted return',
    returnConfirmedDate DATETIME NULL COMMENT 'When storekeeper confirmed receipt',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (woTaskPartsId) REFERENCES wo_task_parts(woTaskPartsId) ON DELETE RESTRICT,
    FOREIGN KEY (partId) REFERENCES parts(partId) ON DELETE RESTRICT,
    FOREIGN KEY (technicianUserId) REFERENCES users(userId) ON DELETE RESTRICT,
    FOREIGN KEY (storekeeperUserId) REFERENCES users(userId) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_technician (technicianUserId),
    INDEX idx_status (returnStatus),
    INDEX idx_request_date (returnRequestDate),
    INDEX idx_wo_task_parts (woTaskPartsId),
    
    -- Constraint: Only one return per woTaskPartsId
    UNIQUE KEY unique_wo_task_parts_return (woTaskPartsId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Material return requests and confirmations';
```

### Predefined Return Reasons (Dropdown Options)
```php
const RETURN_REASONS = [
    'unused_excess' => 'Unused/Excess',
    'wrong_part' => 'Wrong Part',
    'damaged' => 'Damaged/Defective',
    'other' => 'Other'
];
```

---

## 🔌 API Endpoints Specification

### Authentication
All endpoints require:
- **Header:** `Authorization: Bearer <token>`
- **Header:** `deviceid: <device_id>`
- **Session validation:** Check token validity and device ID match
- **Error response on auth failure:**
  ```json
  {
    "success": false,
    "errmsg": "Signature verification failed"
  }
  ```

---

## 1️⃣ Get Technician's Collected Items

**Endpoint:** `GET /material_returns/technician_items/:userId`

**Description:** List all items a technician has collected (status = 36 "Parts Collected") that are eligible for return.

**Path Parameters:**
- `userId` (int, required) - The technician's user ID

**Authorization:**
- User must be logged in
- User can only view their own items (validate `userId` matches session user)

**SQL Query Logic:**
```sql
SELECT 
    wtp.woTaskPartsId,
    wtp.partId,
    p.itemDescription,
    CAST(wtp.woTaskPartsQuantity AS UNSIGNED) as quantityCollected,
    wtp.woTaskPartsDateCollected as collectedDate,
    wt.woTaskNo,
    s.storeName,
    CASE WHEN mr.returnId IS NOT NULL THEN 1 ELSE 0 END as hasReturn,
    mr.returnStatus
FROM wo_task_parts wtp
INNER JOIN parts p ON wtp.partId = p.partId
LEFT JOIN wo_task wt ON wtp.woTaskId = wt.woTaskId
LEFT JOIN stores s ON p.storeId = s.storeId
LEFT JOIN material_returns mr ON wtp.woTaskPartsId = mr.woTaskPartsId
WHERE wtp.userId = :userId
  AND wtp.woTaskPartStatus = '36'  -- Parts Collected
ORDER BY wtp.woTaskPartsDateCollected DESC
```

**Response (Success):**
```json
{
  "success": true,
  "result": [
    {
      "woTaskPartsId": "12345",
      "partId": "PT-001",
      "itemDescription": "Bolt M8x20mm Steel hex head",
      "quantityCollected": 10,
      "collectedDate": "2025-11-05 14:30:00",
      "woTaskNo": "WRDEMO25110500012",
      "storeName": "Main Workshop",
      "hasReturn": false,
      "returnStatus": null
    },
    {
      "woTaskPartsId": "12346",
      "partId": "PT-002",
      "itemDescription": "Wire 2.5mm Red",
      "quantityCollected": 15,
      "collectedDate": "2025-11-03 09:15:00",
      "woTaskNo": "WRDEMO25110300008",
      "storeName": "Main Workshop",
      "hasReturn": true,
      "returnStatus": "pending"
    }
  ]
}
```

**Response (Error):**
```json
{
  "success": false,
  "errmsg": "Unauthorized access"
}
```

**Edge Cases:**
- If no collected items found, return empty array: `"result": []`
- Items with existing return requests show `hasReturn: true` and cannot be returned again

---

## 2️⃣ Submit Return Request

**Endpoint:** `POST /material_returns/request_return`

**Description:** Technician submits a request to return a collected item.

**Request Body:**
```json
{
  "woTaskPartsId": "12345",
  "returnReason": "unused_excess",
  "returnRemarks": "Completed work earlier than expected, parts not needed"
}
```

**Request Fields:**
- `woTaskPartsId` (string, required) - ID of the material collection to return
- `returnReason` (string, required) - One of: `unused_excess`, `wrong_part`, `damaged`, `other`
- `returnRemarks` (string, optional) - Free text remarks (max 1000 chars)

**Authorization:**
- User must be logged in
- User must be the one who collected the item (validate `woTaskPartsId` belongs to session user)

**Validation Rules:**
1. ✅ `woTaskPartsId` must exist in `wo_task_parts` table
2. ✅ Item status must be `36` (Parts Collected)
3. ✅ Item must belong to requesting user
4. ✅ No existing return request for this `woTaskPartsId` (check UNIQUE constraint)
5. ✅ `returnReason` must be one of the predefined values
6. ✅ `returnRemarks` max length 1000 characters (optional)

**Processing Logic:**
```php
// 1. Validate woTaskPartsId
$partDetails = DB::select("
    SELECT userId, partId, woTaskPartsQuantity, woTaskPartStatus 
    FROM wo_task_parts 
    WHERE woTaskPartsId = ?
", [$woTaskPartsId]);

if (empty($partDetails)) {
    return error("Invalid woTaskPartsId");
}

// 2. Verify ownership and status
if ($partDetails[0]->userId != $sessionUserId) {
    return error("Unauthorized: Item does not belong to you");
}

if ($partDetails[0]->woTaskPartStatus != '36') {
    return error("Item not eligible for return (not in Parts Collected status)");
}

// 3. Check for duplicate return
$existingReturn = DB::select("
    SELECT returnId FROM material_returns WHERE woTaskPartsId = ?
", [$woTaskPartsId]);

if (!empty($existingReturn)) {
    return error("Return request already exists for this item");
}

// 4. Validate return reason
if (!in_array($returnReason, ['unused_excess', 'wrong_part', 'damaged', 'other'])) {
    return error("Invalid return reason");
}

// 5. Insert return request
$returnId = DB::insert("
    INSERT INTO material_returns (
        woTaskPartsId, partId, technicianUserId, quantityReturned,
        returnStatus, returnReason, returnRemarks, returnRequestDate
    ) VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW())
", [
    $woTaskPartsId,
    $partDetails[0]->partId,
    $sessionUserId,
    $partDetails[0]->woTaskPartsQuantity,
    $returnReason,
    $returnRemarks
]);
```

**Response (Success):**
```json
{
  "success": true,
  "returnId": "789",
  "message": "Return request submitted successfully"
}
```

**Response (Validation Error):**
```json
{
  "success": false,
  "errmsg": "Return request already exists for this item"
}
```

**Response (Auth Error):**
```json
{
  "success": false,
  "errmsg": "Unauthorized: Item does not belong to you"
}
```

---

## 3️⃣ Get Storekeeper Pending Returns

**Endpoint:** `GET /material_returns/storekeeper_pending`

**Description:** List all pending return requests waiting for storekeeper confirmation.

**Authorization:**
- User must be logged in
- User role must be Storekeeper (validate role)

**SQL Query Logic:**
```sql
SELECT 
    mr.returnId,
    mr.woTaskPartsId,
    mr.quantityReturned,
    mr.returnReason,
    mr.returnRemarks,
    mr.returnRequestDate,
    mr.returnStatus,
    p.partId,
    p.itemDescription,
    p.partCode,
    u.userName as technicianName,
    u.userId as technicianId,
    wt.woTaskNo,
    s.storeName
FROM material_returns mr
INNER JOIN parts p ON mr.partId = p.partId
INNER JOIN users u ON mr.technicianUserId = u.userId
LEFT JOIN wo_task_parts wtp ON mr.woTaskPartsId = wtp.woTaskPartsId
LEFT JOIN wo_task wt ON wtp.woTaskId = wt.woTaskId
LEFT JOIN stores s ON p.storeId = s.storeId
WHERE mr.returnStatus = 'pending'
ORDER BY mr.returnRequestDate DESC
```

**Response (Success):**
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
      "quantityReturned": 10,
      "returnReason": "unused_excess",
      "returnRemarks": "Completed work early, parts not needed",
      "returnRequestDate": "2025-11-08 10:30:00",
      "woTaskNo": "WRDEMO25110500012",
      "storeName": "Main Workshop"
    },
    {
      "returnId": "790",
      "woTaskPartsId": "12346",
      "technicianName": "Jane Smith",
      "technicianId": "124",
      "itemDescription": "Wire 2.5mm Red",
      "partCode": "PT-002",
      "quantityReturned": 15,
      "returnReason": "wrong_part",
      "returnRemarks": "Ordered by mistake",
      "returnRequestDate": "2025-11-08 09:15:00",
      "woTaskNo": "WRDEMO25110300008",
      "storeName": "Main Workshop"
    }
  ],
  "pendingCount": 2
}
```

**Response (No Pending Returns):**
```json
{
  "success": true,
  "result": [],
  "pendingCount": 0
}
```

**Response (Auth Error):**
```json
{
  "success": false,
  "errmsg": "Unauthorized: Storekeeper access required"
}
```

---

## 4️⃣ Get Return Request Details

**Endpoint:** `GET /material_returns/storekeeper_detail/:returnId`

**Description:** Get full details of a specific return request.

**Path Parameters:**
- `returnId` (int, required) - The return request ID

**Authorization:**
- User must be logged in
- User role must be Storekeeper

**SQL Query Logic:**
```sql
SELECT 
    mr.*,
    p.itemDescription,
    p.partCode,
    p.partAvailable as currentStock,
    u.userName as technicianName,
    u.userId as technicianId,
    wtp.woTaskPartsQuantity as originalQuantity,
    wtp.woTaskPartsDateCollected as collectedDate,
    wt.woTaskNo,
    s.storeName
FROM material_returns mr
INNER JOIN parts p ON mr.partId = p.partId
INNER JOIN users u ON mr.technicianUserId = u.userId
INNER JOIN wo_task_parts wtp ON mr.woTaskPartsId = wtp.woTaskPartsId
LEFT JOIN wo_task wt ON wtp.woTaskId = wt.woTaskId
LEFT JOIN stores s ON p.storeId = s.storeId
WHERE mr.returnId = :returnId
```

**Response (Success):**
```json
{
  "success": true,
  "result": {
    "returnId": "789",
    "woTaskPartsId": "12345",
    "partId": "PT-001",
    "partCode": "PT-001",
    "itemDescription": "Bolt M8x20mm Steel hex head",
    "currentStock": 45,
    "technicianName": "John Doe",
    "technicianId": "123",
    "quantityReturned": 10,
    "originalQuantity": 10,
    "returnReason": "unused_excess",
    "returnRemarks": "Completed work earlier than expected, parts not needed",
    "returnStatus": "pending",
    "returnRequestDate": "2025-11-08 10:30:00",
    "returnConfirmedDate": null,
    "woTaskNo": "WRDEMO25110500012",
    "storeName": "Main Workshop",
    "collectedDate": "2025-11-05 14:30:00"
  }
}
```

**Response (Not Found):**
```json
{
  "success": false,
  "errmsg": "Return request not found"
}
```

---

## 5️⃣ Confirm Return Receipt

**Endpoint:** `PUT /material_returns/confirm_receipt/:returnId`

**Description:** Storekeeper confirms receipt of returned item. This updates inventory.

**Path Parameters:**
- `returnId` (int, required) - The return request ID to confirm

**Request Body:**
```json
{}
```
No payload required (body can be empty object)

**Authorization:**
- User must be logged in
- User role must be Storekeeper

**Validation Rules:**
1. ✅ `returnId` must exist
2. ✅ Current `returnStatus` must be `pending`
3. ✅ Cannot confirm already completed returns

**Processing Logic (CRITICAL - Use Database Transaction):**
```php
DB::beginTransaction();

try {
    // 1. Validate return exists and is pending
    $returnDetails = DB::select("
        SELECT mr.*, p.partId, p.partAvailable 
        FROM material_returns mr
        INNER JOIN parts p ON mr.partId = p.partId
        WHERE mr.returnId = ? AND mr.returnStatus = 'pending'
        FOR UPDATE
    ", [$returnId]);
    
    if (empty($returnDetails)) {
        throw new Exception("Return request not found or already completed");
    }
    
    $return = $returnDetails[0];
    
    // 2. Update return status
    DB::update("
        UPDATE material_returns 
        SET returnStatus = 'completed',
            returnConfirmedDate = NOW(),
            storekeeperUserId = ?
        WHERE returnId = ?
    ", [$sessionUserId, $returnId]);
    
    // 3. Update inventory (ADD quantity back to stock)
    $newStock = $return->partAvailable + $return->quantityReturned;
    
    DB::update("
        UPDATE parts 
        SET partAvailable = ?
        WHERE partId = ?
    ", [$newStock, $return->partId]);
    
    // 4. Log inventory change (optional, for audit trail)
    DB::insert("
        INSERT INTO inventory_logs (
            partId, changeType, quantityChange, 
            newQuantity, userId, changeDate, reason
        ) VALUES (?, 'return', ?, ?, ?, NOW(), ?)
    ", [
        $return->partId,
        $return->quantityReturned,
        $newStock,
        $sessionUserId,
        "Material return confirmed - returnId: {$returnId}"
    ]);
    
    DB::commit();
    
    return success("Return confirmed. Inventory updated.", ["newStock" => $newStock]);
    
} catch (Exception $e) {
    DB::rollback();
    return error("Failed to confirm return: " . $e->getMessage());
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Return confirmed. Inventory updated.",
  "newStock": 55
}
```

**Response (Validation Error):**
```json
{
  "success": false,
  "errmsg": "Return request not found or already completed"
}
```

**Response (Database Error):**
```json
{
  "success": false,
  "errmsg": "Failed to confirm return: Database error occurred"
}
```

---

## 6️⃣ Get Return History (Optional)

**Endpoint:** `GET /material_returns/history`

**Description:** View completed returns (for reports/audit).

**Query Parameters:**
- `userId` (int, optional) - Filter by technician
- `status` (string, optional) - Filter by status (`pending`, `completed`, or `all`)
- `dateFrom` (date, optional) - Filter from date (format: YYYY-MM-DD)
- `dateTo` (date, optional) - Filter to date (format: YYYY-MM-DD)

**Authorization:**
- User must be logged in
- Storekeeper can view all returns
- Technician can only view their own returns

**Response (Success):**
```json
{
  "success": true,
  "result": [
    {
      "returnId": "788",
      "itemDescription": "Screwdriver Set",
      "quantityReturned": 1,
      "returnReason": "unused_excess",
      "returnStatus": "completed",
      "returnRequestDate": "2025-11-07 15:00:00",
      "returnConfirmedDate": "2025-11-07 16:30:00",
      "technicianName": "John Doe"
    }
  ]
}
```

---

## 🔒 Security Requirements

### Input Validation
- ✅ Sanitize all user inputs
- ✅ Validate `woTaskPartsId` exists and belongs to user
- ✅ Validate `returnReason` against whitelist
- ✅ Limit `returnRemarks` to 1000 characters
- ✅ Prevent SQL injection (use prepared statements)

### Authorization Checks
- ✅ Verify JWT token on every request
- ✅ Match `deviceid` header with stored device ID
- ✅ Technician can only access their own items
- ✅ Storekeeper role required for confirm/pending endpoints

### Data Integrity
- ✅ Use database transactions for inventory updates
- ✅ Lock rows during update (`FOR UPDATE` clause)
- ✅ Rollback on any error during confirmation
- ✅ UNIQUE constraint prevents duplicate returns
- ✅ Foreign key constraints maintain referential integrity

---

## 📊 Database Indexes Performance

Create these indexes for optimal performance:

```sql
-- Already in CREATE TABLE statement above, but verify:
CREATE INDEX idx_material_returns_technician ON material_returns(technicianUserId);
CREATE INDEX idx_material_returns_status ON material_returns(returnStatus);
CREATE INDEX idx_material_returns_request_date ON material_returns(returnRequestDate);
CREATE INDEX idx_material_returns_wo_task_parts ON material_returns(woTaskPartsId);
CREATE UNIQUE INDEX unique_wo_task_parts_return ON material_returns(woTaskPartsId);
```

---

## 🧪 Testing Checklist

### Unit Tests
- [ ] Validate return reason whitelist
- [ ] Check UNIQUE constraint on duplicate returns
- [ ] Verify quantity calculation (always full)

### Integration Tests
- [ ] Test full workflow: list → request → confirm → verify inventory
- [ ] Test duplicate return prevention
- [ ] Test transaction rollback on inventory update failure
- [ ] Test unauthorized access attempts
- [ ] Test invalid `woTaskPartsId`
- [ ] Test confirming already completed return

### Edge Cases
- [ ] Return request for item not collected yet (status != 36)
- [ ] Concurrent confirmation attempts (race condition)
- [ ] Inventory update with negative stock (shouldn't happen, but validate)
- [ ] Session expiry during return submission

---

## 📝 Error Codes Reference

| HTTP Code | Error Message | Scenario |
|-----------|--------------|----------|
| 200 | Success | Operation completed successfully |
| 400 | Invalid woTaskPartsId | Item doesn't exist or invalid ID |
| 400 | Item not eligible for return | Item status is not 36 (Parts Collected) |
| 400 | Return request already exists | Duplicate return attempt |
| 400 | Invalid return reason | Reason not in whitelist |
| 401 | Signature verification failed | Invalid or expired token |
| 403 | Unauthorized access | User accessing another user's data |
| 403 | Storekeeper access required | Non-storekeeper accessing storekeeper endpoints |
| 404 | Return request not found | Invalid returnId |
| 409 | Return already completed | Attempting to confirm completed return |
| 500 | Database error occurred | Transaction rollback or DB failure |

---

## 🚀 Deployment Notes

### Pre-Deployment
1. ✅ Create `material_returns` table in production database
2. ✅ Verify all foreign key relationships exist
3. ✅ Test endpoints in staging environment
4. ✅ Create Postman collection for testing
5. ✅ Document response formats

### Post-Deployment
1. ✅ Monitor database transaction performance
2. ✅ Set up alerts for failed transactions
3. ✅ Create database backup schedule
4. ✅ Add inventory audit logs
5. ✅ Monitor API response times

---

## 📞 API Response Format Standard

All endpoints follow GEMS standard response format:

**Success Response:**
```json
{
  "success": true,
  "result": <data>,
  "message": "Optional success message"
}
```

**Error Response:**
```json
{
  "success": false,
  "errmsg": "Human-readable error message"
}
```

---

## 🔗 Related Existing Endpoints

For reference, these existing endpoints handle similar workflows:

- `POST /wo_request/request_parts` - Technician requests materials
- `PUT /wo_request/approve_request/:id` - Storekeeper approves request
- `PUT /wo_request/reserve_request/:id` - Storekeeper reserves parts
- `PUT /wo_request/check_out_request/:id` - Storekeeper checks out parts (inventory deduction happens here)

The return module **reverses** the check-out process by **adding** quantity back to `partAvailable`.

---

**Implementation Status:** ⏸️ Awaiting Backend Development  
**Priority:** Medium  
**Estimated Effort:** 2-3 days  
**Last Updated:** 9 November 2025