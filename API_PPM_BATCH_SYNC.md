# PPM Offline Batch Sync API Documentation

## Overview

The PPM Offline Batch Sync API allows mobile technicians to synchronize all offline PPM task actions in a single request, dramatically reducing sync time from 60-120 seconds to under 5 seconds. This endpoint processes multiple actions atomically per section and provides immediate feedback on submission readiness.

**Version:** 1.0  
**Date:** November 11, 2025  
**Status:** Production Ready

---

## Key Features

- ✅ **Single Batch Request** - Replaces 20+ sequential API calls with one request
- ✅ **Per-Section Transactions** - Each action type has atomic transaction handling
- ✅ **Offline Time Preservation** - Maintains actual offline completion times
- ✅ **Idempotency** - Duplicate sync detection prevents data corruption
- ✅ **Submission Readiness** - Auto-validates all required sections and provides submit parameters
- ✅ **Partial Success Support** - Continues processing even if some actions fail
- ✅ **Performance** - 10-20x faster than sequential approach

---

## Endpoint Details

### Base URL
```
Production: https://gems.metadatasystem.my/gems2/api/m_ppm.php
```

### HTTP Method
```
POST
```

### Authentication
```
Bearer Token (JWT) required in Authorization header
```

### Content Type
```
application/json
```

---

## Request Structure

### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| `Authorization` | Bearer {jwt_token} | Yes | JWT authentication token |
| `Content-Type` | application/json | Yes | Request body format |

### Request Body

```json
{
  "action": "batch_sync_offline_actions",
  "metadata": {
    "ppmTaskId": "string",
    "deviceId": "string", 
    "syncTimestamp": "YYYY-MM-DD HH:MM:SS"
  },
  "actions": [
    {
      "actionId": "string",
      "actionType": "string",
      "timestamp": "YYYY-MM-DD HH:MM:SS",
      "payload": {}
    }
  ]
}
```

### Metadata Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `ppmTaskId` | string | Yes | PPM task identifier (from ppm_task.ppm_task_id) |
| `deviceId` | string | Yes | Unique mobile device identifier for idempotency |
| `syncTimestamp` | datetime | Yes | Client-side sync initiation timestamp (ISO format) |

### Action Object

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `actionId` | string | Yes | Unique identifier for this action (for tracking) |
| `actionType` | string | Yes | Type of action (see Action Types below) |
| `timestamp` | datetime | Yes | When this action was performed offline |
| `payload` | object | Yes | Action-specific data (see Payload Structures) |

---

## Action Types & Payloads

### 1. Start Time (`start_time`)

Records when technician started the PPM task.

**Payload:**
```json
{
  "startTime": "2025-11-11 08:00:00"
}
```

**Database Impact:** Updates `ppm_task.ppm_task_time_start`

**Response:**
```json
{
  "actionId": "action_1",
  "actionType": "start_time",
  "success": true,
  "message": "Start time saved successfully",
  "data": {
    "startTime": "2025-11-11 08:00:00"
  }
}
```

---

### 2. Qualitative Tasks (`save_qualitative_tasks`)

Saves inspection/observation results (Section C).

**Payload:**
```json
{
  "tasks": [
    {
      "ppmTaskQId": "101",
      "ppmTaskQResult": "1",
      "ppmTaskQRemark": "All systems operational"
    }
  ]
}
```

**Field Details:**
- `ppmTaskQId` - Qualitative task ID from checklist
- `ppmTaskQResult` - Result: "0" (Fail), "1" (Pass), "2" (N/A)
- `ppmTaskQRemark` - Optional remarks/notes

**Database Impact:** Inserts/updates `ppm_task_qual` records

**Response:**
```json
{
  "actionId": "action_2",
  "actionType": "save_qualitative_tasks",
  "success": true,
  "message": "Qualitative tasks saved successfully",
  "data": {
    "savedCount": 5
  }
}
```

---

### 3. Quantitative Tasks (`save_quantitative_tasks`)

Saves measurement/reading results (Section D).

**Payload:**
```json
{
  "tasks": [
    {
      "ppmTaskDId": "201",
      "ppmTaskDValue": "25.5",
      "ppmTaskDRemark": "Temperature reading - normal range"
    }
  ]
}
```

**Field Details:**
- `ppmTaskDId` - Quantitative task ID from checklist
- `ppmTaskDValue` - Measured value (numeric or text)
- `ppmTaskDRemark` - Optional remarks

**Database Impact:** Inserts/updates `ppm_task_quan` records

**Response:**
```json
{
  "actionId": "action_3",
  "actionType": "save_quantitative_tasks",
  "success": true,
  "message": "Quantitative tasks saved successfully",
  "data": {
    "savedCount": 3
  }
}
```

---

### 4. Lubricant Tasks (`save_lubricant_tasks`)

Saves lubrication activity results (Section E).

**Payload:**
```json
{
  "tasks": [
    {
      "ppmTaskEId": "301",
      "ppmTaskEResult": "1",
      "ppmTaskERemark": "Lubrication completed"
    }
  ]
}
```

**Status:** Currently supported in API structure, implementation may vary by deployment.

---

### 5. Checklist Tasks (`save_checklist_tasks`)

Saves checklist item completion (Section F).

**Payload:**
```json
{
  "tasks": [
    {
      "ppmTaskFId": "401",
      "ppmTaskFResult": "1",
      "ppmTaskFRemark": "Checklist item verified"
    }
  ]
}
```

**Status:** Currently supported in API structure, implementation may vary by deployment.

---

### 6. PPM Remark (`save_ppm_remark`)

Saves overall task summary/remark (Section G).

**Payload:**
```json
{
  "remark": "Maintenance completed successfully. All systems operational. Minor wear on belt tensioner noted for next service."
}
```

**Database Impact:** Updates `ppm_task.ppm_task_remark`

**Response:**
```json
{
  "actionId": "action_4",
  "actionType": "save_ppm_remark",
  "success": true,
  "message": "PPM remark saved successfully",
  "data": {
    "remarkLength": 125
  }
}
```

---

### 7. Material Request (`save_material_request`)

Records material/spare parts requests.

**Payload:**
```json
{
  "materials": [
    {
      "itemId": "ITEM001",
      "quantity": 2,
      "uomId": "PCS"
    },
    {
      "itemId": "ITEM002",
      "quantity": 1,
      "uomId": "SET"
    }
  ]
}
```

**Field Details:**
- `itemId` - Item code from inventory
- `quantity` - Quantity requested (positive integer)
- `uomId` - Unit of measure code

**Database Impact:** Inserts into `ppm_task_parts`

**Response:**
```json
{
  "actionId": "action_5",
  "actionType": "save_material_request",
  "success": true,
  "message": "Material requests saved successfully",
  "data": {
    "savedCount": 2
  }
}
```

---

### 8. Image Upload (`upload_ppm_maintenance_image`)

Uploads maintenance photos with geolocation.

**Payload:**
```json
{
  "image": "BASE64_ENCODED_IMAGE_DATA",
  "fileName": "maintenance_photo_1.jpg",
  "uploadType": "0",
  "longitude": "101.6869",
  "latitude": "3.1390"
}
```

**Field Details:**
- `image` - Base64 encoded image data (JPEG, PNG)
- `fileName` - Original filename
- `uploadType` - "0" (Before), "1" (During), "2" (After)
- `longitude` - GPS longitude coordinate
- `latitude` - GPS latitude coordinate

**Database Impact:** 
- Creates file in `../upload/ppm_maintenance/`
- Inserts into `sys_upload` and `ppm_task_upload`

**Response:**
```json
{
  "actionId": "action_6",
  "actionType": "upload_ppm_maintenance_image",
  "success": true,
  "message": "Image uploaded successfully",
  "data": {
    "fileName": "12345_1699707000_1234.jpg",
    "uploadId": "789",
    "fileSize": 45678
  }
}
```

---

### 9. Complete Task (`complete_ppm_task`)

Marks task as completed with offline end time.

**Payload:**
```json
{
  "endTime": "2025-11-11 10:45:00"
}
```

**⚠️ CRITICAL:** The `endTime` is preserved from offline capture. The API does NOT use server `NOW()` timestamp.

**Database Impact:** 
- Updates `ppm_task.ppm_task_time_serviced` with offline time
- Sets `ppm_task.ppm_task_completed_offline = 1`

**Response:**
```json
{
  "actionId": "action_7",
  "actionType": "complete_ppm_task",
  "success": true,
  "message": "Task completion saved successfully",
  "data": {
    "endTime": "2025-11-11 10:45:00"
  }
}
```

---

## Response Structure

### Success Response

```json
{
  "success": true,
  "message": "Batch sync completed",
  "results": [
    {
      "actionId": "string",
      "actionType": "string",
      "success": true|false,
      "message": "string",
      "data": {},
      "error": "string (only if success=false)"
    }
  ],
  "summary": {
    "totalActions": 7,
    "successCount": 7,
    "failedCount": 0,
    "syncTimestamp": "2025-11-11 14:30:00"
  },
  "submissionReady": {
    "canSubmit": true,
    "checkpoint": "2",
    "requiredSections": {
      "sectionA": true,
      "sectionC": true,
      "taskComplete": true
    },
    "optionalSections": {
      "sectionD": true,
      "sectionG": true,
      "materialRequest": true
    },
    "missingRequirements": [],
    "submitParams": {
      "ppmTaskId": "12345",
      "checkpoint": "2",
      "result": "1",
      "remark": "Completed offline"
    },
    "completedOffline": true
  }
}
```

### Submission Readiness Object

| Field | Type | Description |
|-------|------|-------------|
| `canSubmit` | boolean | True if all required sections are complete |
| `checkpoint` | string | Checkpoint value for submission (always "2" for completion) |
| `requiredSections` | object | Status of mandatory sections (A, C, completion) |
| `optionalSections` | object | Status of optional sections |
| `missingRequirements` | array | List of missing required sections (empty if ready) |
| `submitParams` | object | Parameters for `submit_ppm` endpoint (null if not ready) |
| `completedOffline` | boolean | True if task was completed offline |

### Submit Params Object

When `canSubmit` is true, use these parameters to call the existing `submit_ppm` action:

```json
{
  "ppmTaskId": "12345",
  "checkpoint": "2",
  "result": "1",
  "remark": "Maintenance completed successfully"
}
```

**Usage:**
```javascript
POST /api/m_ppm.php
{
  "action": "submit_ppm",
  "ppmTaskId": submitParams.ppmTaskId,
  "checkpoint": submitParams.checkpoint,
  "result": submitParams.result,
  "remark": submitParams.remark
}
```

---

## Error Responses

### Validation Error (400)
```json
{
  "success": false,
  "error": "Metadata field 'ppmTaskId' is required",
  "results": [],
  "summary": {
    "totalActions": 0,
    "successCount": 0,
    "failedCount": 0
  }
}
```

### Authentication Error (401)
```json
{
  "success": false,
  "error": "[Line 54] - Parameter Authorization empty",
  "results": [],
  "summary": {
    "totalActions": 0,
    "successCount": 0,
    "failedCount": 0
  }
}
```

### Task Not Found (400)
```json
{
  "success": false,
  "error": "PPM task validation failed: PPM task not found: 99999",
  "results": [],
  "summary": {
    "totalActions": 0,
    "successCount": 0,
    "failedCount": 0
  }
}
```

### Partial Success (200)

When some actions succeed and others fail:

```json
{
  "success": true,
  "message": "Batch sync completed",
  "results": [
    {
      "actionId": "action_1",
      "actionType": "start_time",
      "success": true,
      "message": "Start time saved successfully"
    },
    {
      "actionId": "action_2",
      "actionType": "save_qualitative_tasks",
      "success": false,
      "error": "No qualitative tasks provided"
    }
  ],
  "summary": {
    "totalActions": 2,
    "successCount": 1,
    "failedCount": 1
  },
  "submissionReady": {
    "canSubmit": false,
    "missingRequirements": [
      "Qualitative Tasks (Section C)",
      "Task Completion (End Time)"
    ]
  }
}
```

**Note:** Even with partial failures, HTTP status is 200 and `success: true` at the top level. Check individual action results.

---

## Idempotency

### How It Works

The API uses a combination of `ppmTaskId`, `syncTimestamp`, and `deviceId` to detect duplicate requests. If an identical request is received:

1. **First Request:** Processes all actions and stores result in `ppm_offline_sync_log`
2. **Duplicate Requests:** Returns cached response without re-processing

### Duplicate Response

```json
{
  "success": true,
  "message": "Duplicate sync request - already processed",
  "isDuplicate": true,
  "results": [],
  "summary": {
    "totalActions": 0,
    "successCount": 0,
    "failedCount": 0
  }
}
```

### Best Practices

- Use a consistent `syncTimestamp` for retry attempts of the same sync
- Include unique `deviceId` for each mobile device
- Check for `isDuplicate: true` in response
- Don't retry with same parameters if successful response received

---

## Transaction Handling

### Per-Section Atomic Transactions

Each action type operates in its own transaction:

```
Action 1: start_time
  ↓ BEGIN TRANSACTION
  ↓ UPDATE ppm_task
  ↓ COMMIT
  ✓ Success

Action 2: save_qualitative_tasks
  ↓ BEGIN TRANSACTION
  ↓ INSERT/UPDATE ppm_task_qual (multiple rows)
  ↓ COMMIT
  ✓ Success

Action 3: save_ppm_remark
  ↓ BEGIN TRANSACTION
  ↓ UPDATE ppm_task
  ✗ ROLLBACK (if error)
  ✗ Failure (doesn't affect Action 1 or 2)
```

### Benefits

- **Partial Success:** Some actions can succeed even if others fail
- **Data Integrity:** Each section maintains consistency
- **Retry Logic:** Failed actions can be retried independently

---

## Example: Complete Offline Sync Flow

### 1. Mobile App Collects Actions

```javascript
const offlineActions = [
  {
    actionId: generateUUID(),
    actionType: "start_time",
    timestamp: "2025-11-11 08:00:00",
    payload: { startTime: "2025-11-11 08:00:00" }
  },
  {
    actionId: generateUUID(),
    actionType: "save_qualitative_tasks",
    timestamp: "2025-11-11 09:00:00",
    payload: {
      tasks: qualitativeTaskResults
    }
  },
  // ... more actions
  {
    actionId: generateUUID(),
    actionType: "complete_ppm_task",
    timestamp: "2025-11-11 10:45:00",
    payload: { endTime: "2025-11-11 10:45:00" }
  }
];
```

### 2. Send Batch Sync Request

```javascript
const response = await fetch('https://gems.metadatasystem.my/gems2/api/m_ppm.php', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    action: "batch_sync_offline_actions",
    metadata: {
      ppmTaskId: taskId,
      deviceId: getDeviceId(),
      syncTimestamp: new Date().toISOString()
    },
    actions: offlineActions
  })
});

const result = await response.json();
```

### 3. Check Submission Readiness

```javascript
if (result.success) {
  console.log(`Synced ${result.summary.successCount}/${result.summary.totalActions} actions`);
  
  if (result.submissionReady.canSubmit) {
    // All required sections complete - can submit to workflow
    await submitToWorkflow(result.submissionReady.submitParams);
  } else {
    // Show what's missing
    console.log('Missing:', result.submissionReady.missingRequirements);
  }
}
```

### 4. Auto-Submit to Workflow

```javascript
async function submitToWorkflow(submitParams) {
  const response = await fetch('https://gems.metadatasystem.my/gems2/api/m_ppm.php', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${jwtToken}`,
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
      action: 'submit_ppm',
      ppmTaskId: submitParams.ppmTaskId,
      checkpoint: submitParams.checkpoint,
      result: submitParams.result,
      remark: submitParams.remark
    })
  });
  
  const result = await response.json();
  if (result.success) {
    console.log('Task submitted to workflow!');
  }
}
```

---

## Performance Metrics

### Before: Sequential Approach

```
API Call 1: start_time          →  3-5 seconds
API Call 2: save_qualitative    →  3-5 seconds
API Call 3: save_quantitative   →  3-5 seconds
API Call 4: save_remark         →  3-5 seconds
API Call 5: save_materials      →  3-5 seconds
API Call 6: upload_image_1      →  5-8 seconds
API Call 7: upload_image_2      →  5-8 seconds
API Call 8: upload_image_3      →  5-8 seconds
API Call 9: complete_task       →  3-5 seconds
...
TOTAL: 60-120 seconds for 20+ calls
```

### After: Batch Sync Approach

```
API Call 1: batch_sync (all actions) → 3-5 seconds
TOTAL: 3-5 seconds
```

### Improvement

- **Time Saved:** 55-115 seconds per sync
- **Speed Increase:** 10-20x faster
- **Network Requests:** 95% reduction (20+ → 1)
- **Battery Impact:** Significantly reduced
- **User Experience:** Near-instant sync vs 1-2 minute wait

---

## Database Schema

### ppm_offline_sync_log

Tracks all batch sync attempts for idempotency and audit trail.

```sql
CREATE TABLE ppm_offline_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ppm_task_id VARCHAR(50) NOT NULL,
    sync_timestamp DATETIME NOT NULL,
    device_id VARCHAR(100) NOT NULL,
    user_id INT NOT NULL,
    total_actions INT NOT NULL DEFAULT 0,
    success_count INT NOT NULL DEFAULT 0,
    failed_count INT NOT NULL DEFAULT 0,
    request_payload TEXT,
    response_payload TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sync (ppm_task_id, sync_timestamp, device_id),
    INDEX idx_ppm_task (ppm_task_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### ppm_task (modified)

Added flag to identify offline-completed tasks.

```sql
ALTER TABLE ppm_task 
ADD COLUMN ppm_task_completed_offline TINYINT(1) DEFAULT 0 
COMMENT '1 if task was completed offline';
```

---

## Testing Guide

### Test Scenarios

#### 1. Happy Path - All Actions Succeed
```bash
curl -X POST https://gems.metadatasystem.my/gems2/api/m_ppm.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d @test_data/happy_path.json
```

**Expected:** All actions return `success: true`, `canSubmit: true`

#### 2. Partial Success
- Include action with empty tasks array
- Expected: Some actions fail, others succeed
- Verify: `summary.failedCount > 0` and successful actions are saved

#### 3. Validation Errors
- Missing `ppmTaskId` in metadata
- Invalid `ppmTaskId` (doesn't exist)
- Empty actions array
- Expected: Error message, `success: false`

#### 4. Idempotency Test
- Send same request twice (identical syncTimestamp + deviceId)
- Expected: Second request returns `isDuplicate: true`
- Verify: No duplicate records in database

#### 5. Submission Readiness - Not Ready
- Send only `start_time` action
- Expected: `canSubmit: false`, missing requirements listed

#### 6. Submission Readiness - Ready
- Send: start_time, qualitative_tasks, complete_task
- Expected: `canSubmit: true`, submitParams provided

#### 7. Offline Time Preservation
- Complete task with specific `endTime`
- Verify: `ppm_task.ppm_task_time_serviced` matches payload
- Verify: `ppm_task_completed_offline = 1`

---

## Troubleshooting

### Issue: "Parameter Authorization empty"
**Cause:** Missing or invalid JWT token  
**Solution:** Include valid JWT in Authorization header: `Bearer {token}`

### Issue: "PPM task not found"
**Cause:** Invalid ppmTaskId  
**Solution:** Verify task exists in database, check ID format

### Issue: "Failed to update start time - task may not exist"
**Cause:** Task deleted or wrong ppmTaskId  
**Solution:** Query database to confirm task exists

### Issue: Actions succeed but canSubmit is false
**Cause:** Missing required sections (A, C, or completion)  
**Solution:** Check `missingRequirements` array, send missing actions

### Issue: Duplicate sync not detected
**Cause:** Different syncTimestamp or deviceId  
**Solution:** Ensure retry uses identical metadata for idempotency

### Issue: Images not uploading
**Cause:** Invalid base64, large file size  
**Solution:** Validate base64 encoding, compress images before upload

---

## Rate Limits & Constraints

- **Max Actions Per Batch:** No hard limit, recommended < 100 for performance
- **Max Image Size:** 5MB per image (base64 encoded)
- **Max Payload Size:** 50MB total request body
- **Timeout:** 60 seconds server timeout
- **Concurrent Requests:** JWT-based user concurrency applies

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-11-11 | Initial release - Complete batch sync implementation |

---

## Support & Contact

For API issues or questions:
- Technical Lead: Development Team
- Email: support@metadatasystem.my
- Documentation: This file

---

## Related Endpoints

- `POST /api/m_ppm.php?action=submit_ppm` - Submit completed task to workflow
- `GET /api/m_ppm.php?type=ppm_section_status&ppmTaskId={id}` - Check section completion status
- `GET /api/m_ppm.php?type=pending_task` - Get pending PPM tasks for user

---

**END OF DOCUMENTATION**
