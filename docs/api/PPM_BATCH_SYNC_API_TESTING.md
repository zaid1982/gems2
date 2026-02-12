# PPM Batch Sync API - Testing Guide

## Endpoint
```
POST https://gems.metadatasystem.my/gems2/api/m_ppm.php
```

## Headers
```
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

## Request Body Structure

```json
{
  "action": "batch_sync_offline_actions",
  "metadata": {
    "ppmTaskId": "12345",
    "deviceId": "DEVICE_UUID_123",
    "syncTimestamp": "2025-11-11 14:30:00"
  },
  "actions": [
    {
      "actionId": "action_1",
      "actionType": "start_time",
      "timestamp": "2025-11-11 08:00:00",
      "payload": {
        "startTime": "2025-11-11 08:00:00"
      }
    },
    {
      "actionId": "action_2",
      "actionType": "save_qualitative_tasks",
      "timestamp": "2025-11-11 09:00:00",
      "payload": {
        "tasks": [
          {
            "ppmTaskQId": "101",
            "ppmTaskQResult": "1",
            "ppmTaskQRemark": "All good"
          },
          {
            "ppmTaskQId": "102",
            "ppmTaskQResult": "0",
            "ppmTaskQRemark": "Needs attention"
          }
        ]
      }
    },
    {
      "actionId": "action_3",
      "actionType": "save_quantitative_tasks",
      "timestamp": "2025-11-11 09:30:00",
      "payload": {
        "tasks": [
          {
            "ppmTaskDId": "201",
            "ppmTaskDValue": "25.5",
            "ppmTaskDRemark": "Temperature reading"
          }
        ]
      }
    },
    {
      "actionId": "action_4",
      "actionType": "save_ppm_remark",
      "timestamp": "2025-11-11 10:00:00",
      "payload": {
        "remark": "Maintenance completed successfully. All systems operational."
      }
    },
    {
      "actionId": "action_5",
      "actionType": "save_material_request",
      "timestamp": "2025-11-11 10:15:00",
      "payload": {
        "materials": [
          {
            "itemId": "ITEM001",
            "quantity": 2,
            "uomId": "PCS"
          }
        ]
      }
    },
    {
      "actionId": "action_6",
      "actionType": "upload_ppm_maintenance_image",
      "timestamp": "2025-11-11 10:30:00",
      "payload": {
        "image": "BASE64_ENCODED_IMAGE_DATA_HERE",
        "fileName": "maintenance_photo_1.jpg",
        "uploadType": "0",
        "longitude": "101.6869",
        "latitude": "3.1390"
      }
    },
    {
      "actionId": "action_7",
      "actionType": "complete_ppm_task",
      "timestamp": "2025-11-11 10:45:00",
      "payload": {
        "endTime": "2025-11-11 10:45:00"
      }
    }
  ]
}
```

## Expected Response

### Success Response
```json
{
  "success": true,
  "message": "Batch sync completed",
  "results": [
    {
      "actionId": "action_1",
      "actionType": "start_time",
      "success": true,
      "message": "Start time saved successfully",
      "data": {
        "startTime": "2025-11-11 08:00:00"
      }
    },
    {
      "actionId": "action_2",
      "actionType": "save_qualitative_tasks",
      "success": true,
      "message": "Qualitative tasks saved successfully",
      "data": {
        "savedCount": 2
      }
    },
    {
      "actionId": "action_3",
      "actionType": "save_quantitative_tasks",
      "success": true,
      "message": "Quantitative tasks saved successfully",
      "data": {
        "savedCount": 1
      }
    },
    {
      "actionId": "action_4",
      "actionType": "save_ppm_remark",
      "success": true,
      "message": "PPM remark saved successfully",
      "data": {
        "remarkLength": 63
      }
    },
    {
      "actionId": "action_5",
      "actionType": "save_material_request",
      "success": true,
      "message": "Material requests saved successfully",
      "data": {
        "savedCount": 1
      }
    },
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
    },
    {
      "actionId": "action_7",
      "actionType": "complete_ppm_task",
      "success": true,
      "message": "Task completion saved successfully",
      "data": {
        "endTime": "2025-11-11 10:45:00"
      }
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
      "remark": "Maintenance completed successfully. All systems operational."
    },
    "completedOffline": true
  }
}
```

### Partial Failure Response
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
    "failedCount": 1,
    "syncTimestamp": "2025-11-11 14:30:00"
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

### Validation Error Response
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

### Duplicate Sync Response (Idempotency)
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

## Testing Scenarios

### 1. Happy Path - All Actions Succeed
- Include all 7 action types in sequence
- Verify all actions return success: true
- Check submissionReady.canSubmit is true
- Verify submitParams are provided

### 2. Partial Success
- Send batch with some invalid data (e.g., empty tasks array)
- Verify failed actions show success: false with error message
- Check summary shows correct successCount and failedCount
- Confirm successful actions are still saved (per-section transactions)

### 3. Validation Errors
- Missing metadata fields
- Invalid ppmTaskId (task doesn't exist)
- Empty actions array
- Invalid action types
- Verify appropriate error messages

### 4. Idempotency Test
- Send same request twice with identical syncTimestamp and deviceId
- Second request should return duplicate response without re-processing
- Verify no duplicate records in database

### 5. Submission Readiness
- **Not Ready**: Only start_time action
  - canSubmit: false
  - Missing requirements listed
- **Ready**: start_time + qualitative_tasks + complete_task
  - canSubmit: true
  - submitParams provided
  
### 6. Offline End Time Preservation
- Send complete_task with specific endTime from offline
- Verify ppm_task.ppm_task_time_serviced matches payload time
- Confirm it does NOT use server NOW() time
- Check ppm_task_completed_offline flag is set to 1

### 7. Large Batch Performance
- Send 50+ actions in single request
- Measure response time (target: < 5 seconds)
- Compare with old sequential approach (60-120 seconds)

### 8. Image Upload
- Send base64 encoded image
- Verify file is saved in ../upload/ppm_maintenance/
- Check upload record created in sys_upload table
- Confirm ppm_task_upload record with correct metadata

## Database Verification

After successful sync, verify:

### ppm_task table
```sql
SELECT ppm_task_time_start, ppm_task_time_serviced, 
       ppm_task_remark, ppm_task_completed_offline
FROM ppm_task 
WHERE ppm_task_id = <ppmTaskId>;
```

### ppm_task_qual table
```sql
SELECT * FROM ppm_task_qual 
WHERE ppm_task_id = <ppmTaskId>;
```

### ppm_task_quan table
```sql
SELECT * FROM ppm_task_quan 
WHERE ppm_task_id = <ppmTaskId>;
```

### ppm_task_parts table
```sql
SELECT * FROM ppm_task_parts 
WHERE ppm_task_id = <ppmTaskId>;
```

### ppm_task_upload table
```sql
SELECT * FROM ppm_task_upload 
WHERE ppm_task_id = <ppmTaskId>;
```

### ppm_offline_sync_log table
```sql
SELECT * FROM ppm_offline_sync_log 
WHERE ppm_task_id = <ppmTaskId>
ORDER BY created_at DESC;
```

## Postman Collection Setup

1. **Environment Variables**
   - `base_url`: `https://gems.metadatasystem.my/gems2/api`
   - `jwt_token`: Your JWT token

2. **Pre-request Script** (for JWT)
```javascript
pm.environment.set("jwt_token", "YOUR_JWT_TOKEN_HERE");
```

3. **Tests Script** (assertions)
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has success field", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
});

pm.test("All actions succeeded", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.summary.failedCount).to.equal(0);
});

pm.test("Submission ready", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.submissionReady.canSubmit).to.be.true;
});
```

## Mobile App Integration Notes

1. **Data Transformation**
   - Convert individual API calls to action objects
   - Batch all offline actions before syncing
   - Use device UUID as deviceId
   - Generate unique actionId for each action

2. **Auto-Submit Flow**
```dart
// After successful sync
if (response['submissionReady']['canSubmit'] == true) {
  final submitParams = response['submissionReady']['submitParams'];
  
  // Call existing submit_ppm endpoint
  await submitPpm(
    ppmTaskId: submitParams['ppmTaskId'],
    checkpoint: submitParams['checkpoint'],
    result: submitParams['result'],
    remark: submitParams['remark']
  );
}
```

3. **Error Handling**
   - Check individual action results
   - Retry failed actions separately
   - Show user which actions need manual intervention

## Performance Expectations

| Metric | Old Approach | New Batch Sync |
|--------|-------------|----------------|
| API Calls | 20+ sequential | 1 batch |
| Total Time | 60-120 seconds | 3-5 seconds |
| Network Overhead | High (20+ requests) | Minimal (1 request) |
| Battery Impact | High | Low |
| User Wait Time | 1-2 minutes | < 5 seconds |
| Improvement | Baseline | **10-20x faster** |
