# PPM Offline Mode - Batch Sync API Specification

**Document Version:** 1.0  
**Created:** 11 November 2025  
**Status:** 📋 Specification for Backend Implementation  
**Priority:** HIGH - Critical for improving offline sync performance

---

## 🎯 Executive Summary

### Problem Statement
Currently, PPM offline mode syncs actions **one-by-one** in sequential order. If a technician completes 20 actions offline (start task, fill Section C with 5 tasks, fill Section D with 8 tasks, add 3 images, complete task), the mobile app makes **20 separate API calls** during sync. This creates:
- ⏱️ **Slow sync times** (1-2 minutes for large tasks)
- 🔋 **Battery drain** from multiple network requests
- 📶 **Higher failure rates** (if connection drops mid-sync, partial state)
- 😩 **Poor UX** (users must wait for sync to complete before continuing work)

### Proposed Solution
Create a **single batch sync endpoint** that accepts all pending actions for a PPM task in one API call. Mobile app packages all queued actions and sends them together, backend processes them atomically.

**Benefits:**
- ⚡ **10-20x faster sync** (1 request vs 20 requests)
- 🔋 **Reduced battery usage** (single network transaction)
- 🛡️ **Atomic operations** (all-or-nothing, no partial state)
- 💚 **Better UX** (sync completes in seconds, not minutes)
- 🧪 **Easier testing** (single endpoint to validate)

---

## 📊 Current State Analysis

### Existing Individual Endpoints
All these actions currently have separate API calls:

| Action Type | Current Endpoint | Method | Payload Keys |
|-------------|-----------------|--------|--------------|
| **Task Start** | `/api/m_ppm.php` | POST | `action=update_section_a_start_time`, `ppmTaskId`, `pmStartDateTime` |
| **Section C (Qualitative)** | `/api/m_ppm.php` | POST | `action=save_qualitative_tasks`, `ppmTaskId`, `ppmTaskQual[i][id]`, `ppmTaskQual[i][result]`, `ppmTaskQual[i][remark]` |
| **Section D (Quantitative)** | `/api/m_ppm.php` | POST | `action=save_quantitative_tasks`, `ppmTaskId`, `ppmTaskQuan[i][id]`, `ppmTaskQuan[i][setValues]`, `ppmTaskQuan[i][measuredValues]`, `ppmTaskQuan[i][limit]`, `ppmTaskQuan[i][result]`, `ppmTaskQuan[i][remark]` |
| **Section E (Lubricant)** | `/api/m_ppm.php` | POST | `action=save_lubricant_tasks`, `ppmTaskId`, `ppmTaskLubri[i][...]` |
| **Section F (Checklist)** | `/api/m_ppm.php` | POST | `action=save_checklist_tasks`, `ppmTaskId`, `ppmTaskChecklist[i][...]` |
| **Section G (Remark)** | `/api/m_ppm.php` | POST | `action=save_ppm_remark`, `ppmTaskId`, `ppmTaskRemark` |
| **Section H (Material Request)** | `/api/m_ppm.php` | POST | `action=save_material_request`, `ppmTaskId`, materials data |
| **Image Upload** | `/api/m_ppm.php` | POST | `action=upload_ppm_maintenance_image`, `ppmTaskId`, `fileUpload[data]`, `fileUpload[name]`, geo data, description |
| **Task Complete** | `/api/m_ppm.php` | POST | `action=complete_ppm_task`, `ppmTaskId`, `endTime`, `completedOffline=true` |

### Current Sync Flow (Sequential)
```
Mobile App                         Backend API
─────────                          ───────────
1. Get 20 pending actions
2. For each action:
   ├─ POST action 1             ─→  Process & respond
   ├─ Wait for response         ←─  200 OK
   ├─ Remove from queue
   ├─ POST action 2             ─→  Process & respond
   ├─ Wait for response         ←─  200 OK
   ├─ Remove from queue
   └─ ... (repeat 18 more times)

Total Time: ~60-120 seconds
Network Requests: 20
Risk: If request #15 fails, sync stops
```

### Proposed Batch Sync Flow
```
Mobile App                         Backend API
─────────                          ───────────
1. Get 20 pending actions
2. Package into batch payload
3. POST batch                    ─→  Process all actions atomically
4. Wait for response             ←─  200 OK with detailed results
5. Remove all from queue

Total Time: ~3-5 seconds
Network Requests: 1
Risk: All-or-nothing, clear error handling
```

---

## 🔄 Data Structure Mapping: Mobile → API

### Why We Need Structure Transformation

The mobile app currently stores pending actions in a **flat, action-oriented format** optimized for queuing and retry logic. The new batch API uses a **structured, payload-nested format** for cleaner processing and validation.

### Mobile Database Structure (Current)

**Table:** `ppm_pending_actions`

| Column | Type | Example Value |
|--------|------|---------------|
| `id` | INTEGER | `1` |
| `ppm_task_id` | TEXT | `"PPM-2025-001"` |
| `action` | TEXT | `"save_qualitative_tasks"` |
| `payload_json` | TEXT | `'{"action":"save_qualitative_tasks","ppmTaskId":"PPM-2025-001","ppmTaskQual[0][id]":"12345","ppmTaskQual[0][result]":"OK","ppmTaskQual[0][remark]":"Good"}'` |
| `created_at` | TEXT | `"2025-11-11T08:15:42+08:00"` |

**Key Characteristics:**
- ✅ Simple flat structure (easy to insert/delete)
- ✅ Stores raw form-data style payload (direct from API calls)
- ❌ Inconsistent payload format per action type
- ❌ Hard to validate/parse on backend

---

### Batch API Structure (Proposed)

**Nested JSON with strong typing:**

```json
{
  "action": "batch_sync_offline_actions",
  "ppmTaskId": "PPM-2025-001",
  "userId": "USER123",
  "syncTimestamp": "2025-11-11T10:45:32+08:00",
  "deviceId": "ABC123",
  "actions": [
    {
      "sequenceId": 1,
      "actionType": "save_qualitative_tasks",
      "createdAt": "2025-11-11T08:15:42+08:00",
      "payload": {
        "tasks": [
          {"id": "12345", "result": "OK", "remark": "Good"}
        ]
      }
    }
  ]
}
```

**Key Characteristics:**
- ✅ Strongly typed structure (easy to validate)
- ✅ Consistent payload format (nested objects/arrays)
- ✅ Clear action ordering (`sequenceId`)
- ✅ Metadata separation (action type vs payload data)

---

### Transformation Mapping Table

This table shows how mobile app transforms stored data → batch API format:

| Mobile App Field | Mobile Example | API Field | API Example | Transformation Logic |
|------------------|----------------|-----------|-------------|---------------------|
| **Top Level (Batch Metadata)** |
| `ppm_task_id` (from all actions) | `"PPM-2025-001"` | `ppmTaskId` | `"PPM-2025-001"` | Take from first action (all share same task ID) |
| N/A (from session) | N/A | `userId` | `"USER123"` | Get from authenticated user session |
| `DateTime.now()` | Current timestamp | `syncTimestamp` | `"2025-11-11T10:45:32+08:00"` | Generate at sync time (ISO8601 format) |
| N/A (from device) | N/A | `deviceId` | `"iPhone14_ABC123"` | Get from `device_info_plus` package |
| N/A | N/A | `action` | `"batch_sync_offline_actions"` | Static constant |
| **Action Level (Per Queued Action)** |
| Row index (computed) | `1`, `2`, `3` | `sequenceId` | `1`, `2`, `3` | Enumerate actions in order (1-based) |
| `action` | `"save_qualitative_tasks"` | `actionType` | `"save_qualitative_tasks"` | Direct copy (no transformation) |
| `created_at` | `"2025-11-11T08:15:42+08:00"` | `createdAt` | `"2025-11-11T08:15:42+08:00"` | Direct copy (already ISO8601) |
| `payload_json` | `'{"action":"...","ppmTaskQual[0][id]":"12345",...}'` | `payload` | `{"tasks":[{"id":"12345",...}]}` | **Parse & restructure** (see action-specific mappings below) |

---

### Action-Specific Payload Transformations

Each action type has a unique transformation from flat form-data style → structured JSON:

#### 1️⃣ **Task Start** (`start_time`)

**Mobile Stored Payload (Flat):**
```json
{
  "action": "update_section_a_start_time",
  "ppmTaskId": "PPM-2025-001",
  "pmStartDateTime": "2025-11-11 08:00:00"
}
```

**API Payload (Structured):**
```json
{
  "sequenceId": 1,
  "actionType": "start_time",
  "createdAt": "2025-11-11T08:00:15+08:00",
  "payload": {
    "pmStartDateTime": "2025-11-11 08:00:00"
  }
}
```

**Transformation:**
| Mobile Key | Mobile Value | API Key | API Value | Logic |
|------------|--------------|---------|-----------|-------|
| `action` | `"update_section_a_start_time"` | `actionType` | `"start_time"` | Map action name → simplified type |
| `pmStartDateTime` | `"2025-11-11 08:00:00"` | `payload.pmStartDateTime` | `"2025-11-11 08:00:00"` | Move to nested payload |

---

#### 2️⃣ **Section C - Qualitative Tasks** (`save_qualitative_tasks`)

**Mobile Stored Payload (Flat Form-Data Style):**
```json
{
  "action": "save_qualitative_tasks",
  "ppmTaskId": "PPM-2025-001",
  "ppmTaskQual[0][id]": "12345",
  "ppmTaskQual[0][result]": "OK",
  "ppmTaskQual[0][remark]": "All normal",
  "ppmTaskQual[1][id]": "12346",
  "ppmTaskQual[1][result]": "NOT OK",
  "ppmTaskQual[1][remark]": "Minor leak"
}
```

**API Payload (Structured Array):**
```json
{
  "sequenceId": 2,
  "actionType": "save_qualitative_tasks",
  "createdAt": "2025-11-11T08:15:42+08:00",
  "payload": {
    "tasks": [
      {"id": "12345", "result": "OK", "remark": "All normal"},
      {"id": "12346", "result": "NOT OK", "remark": "Minor leak"}
    ]
  }
}
```

**Transformation Logic:**
```dart
// Mobile transformation code
final payload = json.decode(action.payloadJson);
final tasks = <Map<String, String>>[];

// Parse flat form-data keys: ppmTaskQual[0][id], ppmTaskQual[0][result], etc.
final taskIndices = <int>{};
payload.keys.where((k) => k.startsWith('ppmTaskQual[')).forEach((key) {
  final indexMatch = RegExp(r'\[(\d+)\]').firstMatch(key);
  if (indexMatch != null) taskIndices.add(int.parse(indexMatch.group(1)!));
});

for (final i in taskIndices) {
  tasks.add({
    'id': payload['ppmTaskQual[$i][id]'] ?? '',
    'result': payload['ppmTaskQual[$i][result]'] ?? '',
    'remark': payload['ppmTaskQual[$i][remark]'] ?? '',
  });
}

final apiPayload = {
  'sequenceId': sequenceId,
  'actionType': 'save_qualitative_tasks',
  'createdAt': action.createdAt.toIso8601String(),
  'payload': {'tasks': tasks},
};
```

| Mobile Key Pattern | Mobile Values | API Structure | Transformation |
|-------------------|---------------|---------------|----------------|
| `ppmTaskQual[i][id]` | `"12345"`, `"12346"` | `payload.tasks[i].id` | Group by index `[i]`, create array of objects |
| `ppmTaskQual[i][result]` | `"OK"`, `"NOT OK"` | `payload.tasks[i].result` | Extract value for each index |
| `ppmTaskQual[i][remark]` | `"All normal"`, `"Minor leak"` | `payload.tasks[i].remark` | Extract value for each index |

---

#### 3️⃣ **Section D - Quantitative Tasks** (`save_quantitative_tasks`)

**Mobile Stored Payload (Flat):**
```json
{
  "action": "save_quantitative_tasks",
  "ppmTaskId": "PPM-2025-001",
  "ppmTaskQuan[0][id]": "67890",
  "ppmTaskQuan[0][setValues]": "220",
  "ppmTaskQuan[0][measuredValues]": "218",
  "ppmTaskQuan[0][limit]": "210-230",
  "ppmTaskQuan[0][result]": "OK",
  "ppmTaskQuan[0][remark]": "Within tolerance"
}
```

**API Payload (Structured):**
```json
{
  "sequenceId": 3,
  "actionType": "save_quantitative_tasks",
  "createdAt": "2025-11-11T08:30:20+08:00",
  "payload": {
    "tasks": [
      {
        "id": "67890",
        "setValues": "220",
        "measuredValues": "218",
        "limit": "210-230",
        "result": "OK",
        "remark": "Within tolerance"
      }
    ]
  }
}
```

**Transformation:** Same pattern as Section C (array parsing)

| Mobile Key Pattern | API Structure |
|-------------------|---------------|
| `ppmTaskQuan[i][id]` → | `payload.tasks[i].id` |
| `ppmTaskQuan[i][setValues]` → | `payload.tasks[i].setValues` |
| `ppmTaskQuan[i][measuredValues]` → | `payload.tasks[i].measuredValues` |
| `ppmTaskQuan[i][limit]` → | `payload.tasks[i].limit` |
| `ppmTaskQuan[i][result]` → | `payload.tasks[i].result` |
| `ppmTaskQuan[i][remark]` → | `payload.tasks[i].remark` |

---

#### 4️⃣ **Section G - Remark** (`save_ppm_remark`)

**Mobile Stored Payload:**
```json
{
  "action": "save_ppm_remark",
  "ppmTaskId": "PPM-2025-001",
  "ppmTaskRemark": "Equipment in good condition. Recommend next service in 3 months."
}
```

**API Payload:**
```json
{
  "sequenceId": 4,
  "actionType": "save_ppm_remark",
  "createdAt": "2025-11-11T10:30:00+08:00",
  "payload": {
    "remark": "Equipment in good condition. Recommend next service in 3 months."
  }
}
```

**Transformation:**
| Mobile Key | API Key | Logic |
|------------|---------|-------|
| `ppmTaskRemark` | `payload.remark` | Rename key, nest in payload |

---

#### 5️⃣ **Image Upload** (`upload_ppm_maintenance_image`)

**Mobile Stored Payload:**
```json
{
  "action": "upload_ppm_maintenance_image",
  "ppmTaskId": "PPM-2025-001",
  "fileUpload[data]": "/9j/4AAQSkZJRg...",
  "fileUpload[name]": "IMG_20251111_090000.jpg",
  "description": "Before maintenance",
  "latitude": "3.1569",
  "longitude": "101.7123",
  "timestamp": "2025-11-11 09:00:00"
}
```

**API Payload:**
```json
{
  "sequenceId": 5,
  "actionType": "upload_ppm_maintenance_image",
  "createdAt": "2025-11-11T09:00:05+08:00",
  "payload": {
    "fileUpload": {
      "data": "/9j/4AAQSkZJRg...",
      "name": "IMG_20251111_090000.jpg"
    },
    "description": "Before maintenance",
    "latitude": "3.1569",
    "longitude": "101.7123",
    "timestamp": "2025-11-11 09:00:00"
  }
}
```

**Transformation:**
| Mobile Key Pattern | API Structure | Logic |
|-------------------|---------------|-------|
| `fileUpload[data]` → | `payload.fileUpload.data` | Group `fileUpload[*]` keys into nested object |
| `fileUpload[name]` → | `payload.fileUpload.name` | Extract bracket notation → object key |
| `description` → | `payload.description` | Direct copy to payload |
| `latitude`, `longitude`, `timestamp` → | `payload.{latitude,longitude,timestamp}` | Direct copy to payload |

---

#### 6️⃣ **Task Completion** (`complete_ppm_task`)

**Mobile Stored Payload:**
```json
{
  "action": "complete_ppm_task",
  "ppmTaskId": "PPM-2025-001",
  "endTime": "2025-11-11T10:45:00+08:00",
  "completedOffline": true
}
```

**API Payload:**
```json
{
  "sequenceId": 6,
  "actionType": "complete_ppm_task",
  "createdAt": "2025-11-11T10:45:00+08:00",
  "payload": {
    "endTime": "2025-11-11 10:45:00",
    "completedOffline": true
  }
}
```

**Transformation:**
| Mobile Key | API Key | Logic |
|------------|---------|-------|
| `endTime` | `payload.endTime` | Direct copy (convert ISO8601 → MySQL datetime if needed) |
| `completedOffline` | `payload.completedOffline` | Direct copy (boolean) |

---

### Summary: Transformation Rules

| Transformation Type | Mobile Format | API Format | Example Action Types |
|---------------------|---------------|------------|---------------------|
| **Simple Key Rename** | `{"key": "value"}` | `{"payload": {"newKey": "value"}}` | `start_time`, `save_ppm_remark`, `complete_ppm_task` |
| **Array Expansion** | `{"arr[0][k]": "v1", "arr[1][k]": "v2"}` | `{"payload": {"items": [{"k":"v1"},{"k":"v2"}]}}` | `save_qualitative_tasks`, `save_quantitative_tasks` |
| **Nested Object Grouping** | `{"obj[key1]": "v1", "obj[key2]": "v2"}` | `{"payload": {"obj": {"key1":"v1","key2":"v2"}}}` | `upload_ppm_maintenance_image` |
| **Direct Pass-Through** | `{"k1": "v1", "k2": "v2"}` | `{"payload": {"k1":"v1","k2":"v2"}}` | Any action with flat structure |

---

### Mobile App Transformation Code (Pseudocode)

```dart
Future<Map<String, dynamic>> buildBatchPayload(
  String ppmTaskId,
  List<PPMPendingActionEntity> actions,
) async {
  final batchPayload = {
    'action': 'batch_sync_offline_actions',
    'ppmTaskId': ppmTaskId,
    'userId': await _getUserId(),
    'syncTimestamp': DateTime.now().toIso8601String(),
    'deviceId': await _getDeviceId(),
    'actions': [],
  };

  for (var i = 0; i < actions.length; i++) {
    final action = actions[i];
    final storedPayload = json.decode(action.payloadJson) as Map<String, dynamic>;
    
    // Transform based on action type
    final transformedPayload = _transformPayload(action.action, storedPayload);
    
    batchPayload['actions'].add({
      'sequenceId': i + 1,
      'actionType': _mapActionType(action.action),
      'createdAt': action.createdAt.toIso8601String(),
      'payload': transformedPayload,
    });
  }

  return batchPayload;
}

Map<String, dynamic> _transformPayload(String actionType, Map<String, dynamic> stored) {
  switch (actionType) {
    case 'save_qualitative_tasks':
      return _transformQualitativeTasks(stored);
    case 'save_quantitative_tasks':
      return _transformQuantitativeTasks(stored);
    case 'upload_ppm_maintenance_image':
      return _transformImageUpload(stored);
    case 'save_ppm_remark':
      return {'remark': stored['ppmTaskRemark']};
    case 'complete_ppm_task':
      return {
        'endTime': stored['endTime'],
        'completedOffline': stored['completedOffline'],
      };
    default:
      // Generic transformation: remove action/ppmTaskId, keep rest
      final payload = Map<String, dynamic>.from(stored);
      payload.remove('action');
      payload.remove('ppmTaskId');
      return payload;
  }
}

Map<String, dynamic> _transformQualitativeTasks(Map<String, dynamic> stored) {
  final tasks = <Map<String, String>>[];
  final taskIndices = <int>{};
  
  // Find all unique indices: ppmTaskQual[0][*], ppmTaskQual[1][*], etc.
  for (final key in stored.keys) {
    if (key.startsWith('ppmTaskQual[')) {
      final match = RegExp(r'\[(\d+)\]').firstMatch(key);
      if (match != null) taskIndices.add(int.parse(match.group(1)!));
    }
  }
  
  // Build array of task objects
  for (final i in taskIndices.toList()..sort()) {
    tasks.add({
      'id': stored['ppmTaskQual[$i][id]'] ?? '',
      'result': stored['ppmTaskQual[$i][result]'] ?? '',
      'remark': stored['ppmTaskQual[$i][remark]'] ?? '',
    });
  }
  
  return {'tasks': tasks};
}

Map<String, dynamic> _transformImageUpload(Map<String, dynamic> stored) {
  return {
    'fileUpload': {
      'data': stored['fileUpload[data]'],
      'name': stored['fileUpload[name]'],
    },
    'description': stored['description'] ?? '',
    'latitude': stored['latitude'] ?? '',
    'longitude': stored['longitude'] ?? '',
    'timestamp': stored['timestamp'] ?? '',
  };
}
```

---

## 🏗️ API Specification

### Endpoint Details

**URL:** `/api/m_ppm.php`  
**Method:** `POST`  
**Content-Type:** `application/json` (⚠️ Important: Must support JSON body, not just form-data)  
**Authentication:** Bearer token (existing `Authorization` header)  
**Device ID:** Required (existing `deviceid` header)

### Request Structure

#### Top-Level Payload
```json
{
  "action": "batch_sync_offline_actions",
  "ppmTaskId": "string (required)",
  "userId": "string (required)",
  "syncTimestamp": "ISO8601 datetime (required)",
  "deviceId": "string (required)",
  "actions": [
    {/* Action 1 */},
    {/* Action 2 */},
    {/* Action N */}
  ]
}
```

#### Action Object Structure
Each action in the `actions` array follows this schema:

```json
{
  "sequenceId": "integer (1-based index for ordering)",
  "actionType": "string (enum: see Action Types table)",
  "createdAt": "ISO8601 datetime (when action was queued on mobile)",
  "payload": {
    /* Action-specific data (varies by actionType) */
  }
}
```

---

## 📋 Action Types Reference

### 1. Task Start
**actionType:** `start_time`

**Payload:**
```json
{
  "pmStartDateTime": "2025-11-11 08:30:00"
}
```

**Business Logic:**
- Update `ppm_task_schedule` table: set `pmStartDateTime` column
- Record in audit log (if applicable)
- Validate: `pmStartDateTime` must not be in future (allow past for offline scenarios)

**SQL Example:**
```sql
UPDATE ppm_task_schedule 
SET pmStartDateTime = ? 
WHERE ppmTaskId = ?
```

---

### 2. Section C - Qualitative Tasks
**actionType:** `save_qualitative_tasks`

**Payload:**
```json
{
  "tasks": [
    {
      "id": "12345",
      "result": "OK",
      "remark": "All normal"
    },
    {
      "id": "12346",
      "result": "NOT OK",
      "remark": "Minor leak detected"
    }
  ]
}
```

**Fields:**
- `id` (string, required): `ppmTaskQualId` - unique ID of qualitative task
- `result` (string, required): Enum - `"OK"`, `"NOT OK"`, `"N/A"`
- `remark` (string, optional): Free text, max 500 chars

**Business Logic:**
- Update `ppm_task_qualitative` table
- For each task: `UPDATE ppm_task_qualitative SET result = ?, remark = ? WHERE ppmTaskQualId = ?`
- Validate: `id` must exist and belong to `ppmTaskId`

**SQL Example:**
```sql
UPDATE ppm_task_qualitative 
SET ppmTaskQualResult = ?, ppmTaskQualRemark = ?, updated_at = NOW() 
WHERE ppmTaskQualId = ? AND ppmTaskId = ?
```

---

### 3. Section D - Quantitative Tasks
**actionType:** `save_quantitative_tasks`

**Payload:**
```json
{
  "tasks": [
    {
      "id": "67890",
      "setValues": "220",
      "measuredValues": "218",
      "limit": "210-230",
      "result": "OK",
      "remark": "Within tolerance"
    }
  ]
}
```

**Fields:**
- `id` (string, required): `ppmTaskQuanId`
- `setValues` (string, optional): Expected value
- `measuredValues` (string, required): Actual measured value
- `limit` (string, optional): Acceptable range (e.g., "10-20", "±5%")
- `result` (string, required): Enum - `"OK"`, `"NOT OK"`, `"N/A"`
- `remark` (string, optional): Free text, max 500 chars

**Business Logic:**
- Update `ppm_task_quantitative` table
- Auto-calculate result if `measuredValues` and `limit` provided (optional enhancement)
- Validate: Numeric fields if applicable

**SQL Example:**
```sql
UPDATE ppm_task_quantitative 
SET setValues = ?, measuredValues = ?, `limit` = ?, result = ?, remark = ?, updated_at = NOW()
WHERE ppmTaskQuanId = ? AND ppmTaskId = ?
```

---

### 4. Section E - Lubricant Tasks
**actionType:** `save_lubricant_tasks`

**Payload:**
```json
{
  "tasks": [
    {
      "id": "11111",
      "lubricantType": "Grease",
      "quantity": "2",
      "unit": "kg",
      "remark": "Applied to bearing"
    }
  ]
}
```

**Fields:**
- `id` (string, required): `ppmTaskLubricantId`
- `lubricantType` (string, required): Type of lubricant
- `quantity` (string, optional): Amount used
- `unit` (string, optional): Unit (kg, L, ml, etc.)
- `remark` (string, optional): Free text

**Business Logic:**
- Update `ppm_task_lubricant` table
- Track lubricant usage if inventory integration exists

---

### 5. Section F - Checklist Tasks
**actionType:** `save_checklist_tasks`

**Payload:**
```json
{
  "tasks": [
    {
      "id": "22222",
      "checked": true,
      "remark": "Confirmed"
    }
  ]
}
```

**Fields:**
- `id` (string, required): `ppmTaskChecklistId`
- `checked` (boolean, required): true = completed, false = not completed
- `remark` (string, optional): Notes

**Business Logic:**
- Update `ppm_task_checklist` table
- Store boolean as 1/0 or 'true'/'false' depending on DB schema

---

### 6. Section G - Remark
**actionType:** `save_ppm_remark`

**Payload:**
```json
{
  "remark": "Equipment in good condition. Recommend next service in 3 months."
}
```

**Fields:**
- `remark` (string, required): Overall technician remarks, max 2000 chars

**Business Logic:**
- Update `ppm_task_schedule` table: set `ppmTaskRemark` column
- Overwrite existing remark (last action wins)

**SQL Example:**
```sql
UPDATE ppm_task_schedule 
SET ppmTaskRemark = ?, updated_at = NOW() 
WHERE ppmTaskId = ?
```

---

### 7. Section H - Material Request
**actionType:** `save_material_request`

**Payload:**
```json
{
  "materials": [
    {
      "partId": "33333",
      "partName": "Air Filter",
      "quantity": "2",
      "urgency": "normal"
    }
  ]
}
```

**Fields:**
- `materials` (array, required): List of requested parts
  - `partId` (string, optional): If selected from inventory
  - `partName` (string, required): Part description
  - `quantity` (string, required): Quantity needed
  - `urgency` (string, optional): Enum - `"normal"`, `"urgent"`

**Business Logic:**
- Insert into `ppm_material_requests` table (or similar)
- Link to `ppmTaskId`
- Notify storekeeper (optional)

---

### 8. Image Upload
**actionType:** `upload_ppm_maintenance_image`

**Payload:**
```json
{
  "fileUpload": {
    "data": "base64_encoded_image_string",
    "name": "IMG_20251111_083045.jpg"
  },
  "description": "Before maintenance",
  "latitude": "3.1569",
  "longitude": "101.7123",
  "timestamp": "2025-11-11 08:30:45"
}
```

**Fields:**
- `fileUpload.data` (string, required): Base64-encoded image
- `fileUpload.name` (string, required): Original filename
- `description` (string, optional): Image caption, max 200 chars
- `latitude` (string, optional): GPS coordinates
- `longitude` (string, optional): GPS coordinates
- `timestamp` (string, required): ISO8601 when photo was taken

**Business Logic:**
- Decode base64 → save to file system
- Store metadata in `ppm_task_uploads` table
- Generate thumbnail (optional)
- Max file size: 5MB (compressed on mobile)

**SQL Example:**
```sql
INSERT INTO ppm_task_uploads 
(ppmTaskId, uploadType, uploadName, uploadSrc, description, latitude, longitude, timestamp, created_at)
VALUES (?, 'maintenance_image', ?, ?, ?, ?, ?, ?, NOW())
```

---

### 9. Task Completion
**actionType:** `complete_ppm_task`

**Payload:**
```json
{
  "endTime": "2025-11-11 10:45:00",
  "completedOffline": true
}
```

**Fields:**
- `endTime` (string, required): ISO8601 datetime when task completed
- `completedOffline` (boolean, required): Flag to indicate offline completion

**Business Logic:**
- Update `ppm_task_schedule` table: 
  - Set `pmEndDateTime` = endTime
  - Set `ppmTaskStatus` = "Completed" (or your status code)
  - Set `completedOffline` flag (for audit)
- Calculate duration: `pmEndDateTime - pmStartDateTime`
- Trigger any completion workflows (notifications, SLA tracking, etc.)
- Validate: Cannot complete if not started (`pmStartDateTime` must exist)

**SQL Example:**
```sql
UPDATE ppm_task_schedule 
SET pmEndDateTime = ?, ppmTaskStatus = 'Completed', completedOffline = 1, updated_at = NOW()
WHERE ppmTaskId = ? AND pmStartDateTime IS NOT NULL
```

---

## 📤 Response Structure

### Success Response (HTTP 200)

```json
{
  "success": true,
  "message": "Batch sync completed successfully",
  "ppmTaskId": "TASK123",
  "syncedAt": "2025-11-11T10:45:32+08:00",
  "summary": {
    "totalActions": 15,
    "successCount": 15,
    "failedCount": 0,
    "skippedCount": 0
  },
  "results": [
    {
      "sequenceId": 1,
      "actionType": "start_time",
      "status": "success",
      "message": "Task start time updated"
    },
    {
      "sequenceId": 2,
      "actionType": "save_qualitative_tasks",
      "status": "success",
      "message": "5 qualitative tasks updated"
    },
    {
      "sequenceId": 3,
      "actionType": "upload_ppm_maintenance_image",
      "status": "success",
      "message": "Image uploaded successfully",
      "uploadId": "IMG_67890"
    }
  ]
}
```

### Partial Success Response (HTTP 200)
⚠️ Some actions succeeded, some failed (non-critical failures)

```json
{
  "success": true,
  "message": "Batch sync completed with warnings",
  "ppmTaskId": "TASK123",
  "syncedAt": "2025-11-11T10:45:32+08:00",
  "summary": {
    "totalActions": 15,
    "successCount": 13,
    "failedCount": 2,
    "skippedCount": 0
  },
  "results": [
    {
      "sequenceId": 1,
      "actionType": "start_time",
      "status": "success",
      "message": "Task start time updated"
    },
    {
      "sequenceId": 8,
      "actionType": "upload_ppm_maintenance_image",
      "status": "failed",
      "message": "Image file corrupted or too large",
      "errorCode": "IMAGE_INVALID"
    },
    {
      "sequenceId": 12,
      "actionType": "save_qualitative_tasks",
      "status": "failed",
      "message": "Task ID 12346 not found",
      "errorCode": "TASK_NOT_FOUND"
    }
  ]
}
```

### Complete Failure Response (HTTP 400/422)
Critical validation error before processing

```json
{
  "success": false,
  "message": "Batch sync failed: Invalid request",
  "errorCode": "VALIDATION_ERROR",
  "errors": [
    {
      "field": "ppmTaskId",
      "message": "PPM Task ID not found or does not belong to user"
    },
    {
      "field": "actions[3].payload.id",
      "message": "Task ID is required for save_qualitative_tasks action"
    }
  ]
}
```

### Authentication/Authorization Error (HTTP 401/403)

```json
{
  "success": false,
  "message": "Unauthorized: Invalid or expired token",
  "errorCode": "AUTH_FAILED"
}
```

### Server Error (HTTP 500)

```json
{
  "success": false,
  "message": "Internal server error during batch sync",
  "errorCode": "SERVER_ERROR",
  "details": "Database connection failed" 
}
```

---

## 🔒 Validation Rules

### Request-Level Validation
1. ✅ **Authentication**: Valid bearer token required
2. ✅ **Device ID**: Must match registered device for user
3. ✅ **PPM Task ID**: Must exist and be accessible to user (role check)
4. ✅ **User ID**: Must match authenticated user
5. ✅ **Actions array**: Must not be empty, max 100 actions per batch
6. ✅ **Sequence IDs**: Must be unique and sequential (1, 2, 3, ...)
7. ✅ **Action types**: Must be valid enum values
8. ✅ **Task state**: Task must be in "In Progress" status (cannot sync completed tasks)

### Action-Level Validation
Each action type has specific validation:

| Action Type | Key Validations |
|-------------|-----------------|
| `start_time` | - `pmStartDateTime` required<br>- Must be valid datetime<br>- Cannot start already-started task |
| `save_qualitative_tasks` | - Task IDs must exist and belong to `ppmTaskId`<br>- `result` must be valid enum<br>- `remark` max 500 chars |
| `save_quantitative_tasks` | - Task IDs must exist<br>- `measuredValues` required<br>- Numeric validation if applicable |
| `upload_ppm_maintenance_image` | - Base64 data required<br>- Max decoded size 5MB<br>- Valid image format (JPEG/PNG)<br>- Filename max 255 chars |
| `complete_ppm_task` | - Task must be started first (`pmStartDateTime` exists)<br>- `endTime` must be after `pmStartDateTime`<br>- Cannot complete already-completed task |

### Error Handling Strategy
**Philosophy:** Fail gracefully. Process as many actions as possible, return detailed results.

**Decision Tree:**
```
1. Validate entire request first (auth, ppmTaskId, structure)
   ├─ FAIL → Return HTTP 400/401/403, no actions processed
   └─ PASS → Continue

2. For each action in sequence order:
   ├─ Validate action-specific rules
   │  ├─ FAIL (critical) → Mark as failed, continue to next
   │  └─ PASS → Process action
   │     ├─ SUCCESS → Mark as success
   │     └─ ERROR → Mark as failed, log error
   └─ Continue to next action

3. Return HTTP 200 with detailed results
   ├─ summary.failedCount = 0 → Full success
   └─ summary.failedCount > 0 → Partial success (mobile can retry failed actions)
```

---

## 🗄️ Database Considerations

### Transaction Handling
**Recommendation:** Use **per-section transactions** rather than single transaction for entire batch.

**Rationale:**
- Single transaction: If action #10 fails, rollback all? No, we want partial progress.
- Per-section transactions: Each section (C, D, E, etc.) is atomic within itself.

**Example:**
```sql
-- Transaction 1: Start Time
BEGIN;
UPDATE ppm_task_schedule SET pmStartDateTime = ? WHERE ppmTaskId = ?;
COMMIT;

-- Transaction 2: Section C Tasks (atomic)
BEGIN;
UPDATE ppm_task_qualitative SET result = ?, remark = ? WHERE ppmTaskQualId = 12345;
UPDATE ppm_task_qualitative SET result = ?, remark = ? WHERE ppmTaskQualId = 12346;
UPDATE ppm_task_qualitative SET result = ?, remark = ? WHERE ppmTaskQualId = 12347;
COMMIT; -- All Section C tasks succeed or fail together

-- Transaction 3: Section D Tasks (atomic)
BEGIN;
-- ... Section D updates
COMMIT;
```

### Idempotency
**Problem:** What if mobile sends same batch twice (retry due to network timeout)?

**Solutions:**
1. **Soft approach**: Last-write-wins. Re-applying same data produces same result.
2. **Strict approach**: Track `syncTimestamp` in request, reject duplicate timestamps.
3. **Recommended**: Use sequence tracking table (see below).

**Sync Tracking Table (Optional):**
```sql
CREATE TABLE ppm_offline_sync_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ppm_task_id VARCHAR(50) NOT NULL,
  user_id VARCHAR(50) NOT NULL,
  device_id VARCHAR(100) NOT NULL,
  sync_timestamp DATETIME NOT NULL,
  total_actions INT NOT NULL,
  success_count INT NOT NULL,
  failed_count INT NOT NULL,
  request_payload TEXT, -- Store full JSON for audit
  response_payload TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_sync (ppm_task_id, sync_timestamp, device_id)
);
```

**Usage:**
- Before processing: Check if `sync_timestamp` already exists for this `ppm_task_id` + `device_id`
- If exists: Return cached response (from `response_payload`)
- If not exists: Process normally, insert into log

---

## 🧪 Testing Scenarios

### Test Case 1: Happy Path (All Actions Success)
**Setup:**
- PPM Task in "In Progress" status
- User has valid permissions
- All task IDs valid

**Request:**
```json
{
  "action": "batch_sync_offline_actions",
  "ppmTaskId": "PPM001",
  "userId": "USER123",
  "syncTimestamp": "2025-11-11T10:00:00+08:00",
  "deviceId": "ABC123",
  "actions": [
    {"sequenceId": 1, "actionType": "start_time", "payload": {"pmStartDateTime": "2025-11-11 08:00:00"}},
    {"sequenceId": 2, "actionType": "save_qualitative_tasks", "payload": {"tasks": [{"id": "Q1", "result": "OK", "remark": "Good"}]}},
    {"sequenceId": 3, "actionType": "complete_ppm_task", "payload": {"endTime": "2025-11-11 10:00:00", "completedOffline": true}}
  ]
}
```

**Expected Response:** HTTP 200, `success: true`, all actions status="success"

---

### Test Case 2: Partial Failure (Invalid Task ID)
**Setup:**
- Action 2 has invalid task ID "Q999" (doesn't exist)

**Request:** Same as Test Case 1, but action 2 has invalid task ID

**Expected Response:** 
- HTTP 200 (because some actions succeeded)
- `summary.successCount = 2` (actions 1 and 3)
- `summary.failedCount = 1` (action 2)
- `results[1].status = "failed"`, `errorCode = "TASK_NOT_FOUND"`

---

### Test Case 3: Authentication Failure
**Setup:** Invalid or expired bearer token

**Expected Response:** HTTP 401, `success: false`, `errorCode: "AUTH_FAILED"`

---

### Test Case 4: Task Not Started (Cannot Complete)
**Setup:**
- PPM Task exists but `pmStartDateTime` is NULL
- Request includes `complete_ppm_task` action

**Expected Response:** 
- HTTP 200 (other actions may succeed)
- Complete action fails with `errorCode: "TASK_NOT_STARTED"`

---

### Test Case 5: Large Batch (50 Actions)
**Setup:**
- 1 start_time
- 20 qualitative tasks
- 15 quantitative tasks
- 10 images
- 1 remark
- 1 material request
- 1 complete_ppm_task

**Expected Response:** HTTP 200, all actions processed in <5 seconds

---

### Test Case 6: Duplicate Sync (Idempotency)
**Setup:**
- Send same batch twice with same `syncTimestamp`

**Expected Response:** 
- First request: Processes normally
- Second request: Returns cached response or re-processes with same result (no side effects)

---

## 📱 Mobile App Integration Changes

### Before (Current Implementation)
```dart
Future<void> syncPendingActions() async {
  final pending = await _database.getPPMPendingActions();
  
  for (final action in pending) {
    final body = json.decode(action.payloadJson);
    await _post(body); // 1 network request per action
    await _database.removePPMPendingAction(action.id!);
  }
}
```

### After (Batch Sync)
```dart
Future<void> syncPendingActions() async {
  final pending = await _database.getPPMPendingActions();
  if (pending.isEmpty) return;
  
  // Group by ppmTaskId
  final taskGroups = <String, List<PPMPendingActionEntity>>{};
  for (final action in pending) {
    taskGroups.putIfAbsent(action.ppmTaskId, () => []).add(action);
  }
  
  // Sync each task's actions in batch
  for (final entry in taskGroups.entries) {
    final ppmTaskId = entry.key;
    final actions = entry.value;
    
    final batchPayload = {
      'action': 'batch_sync_offline_actions',
      'ppmTaskId': ppmTaskId,
      'userId': userId, // Get from session
      'syncTimestamp': DateTime.now().toIso8601String(),
      'deviceId': deviceId, // Get from device info
      'actions': actions.map((a) => {
        'sequenceId': actions.indexOf(a) + 1,
        'actionType': _mapActionType(a.action),
        'createdAt': a.createdAt.toIso8601String(),
        'payload': json.decode(a.payloadJson),
      }).toList(),
    };
    
    final response = await _postBatch(batchPayload); // 1 network request for all actions
    
    if (response['success'] == true) {
      // Remove all successfully synced actions
      for (final result in response['results']) {
        if (result['status'] == 'success') {
          final actionId = actions[result['sequenceId'] - 1].id;
          await _database.removePPMPendingAction(actionId!);
        }
      }
    }
  }
}

String _mapActionType(String action) {
  // Map internal action names to API action types
  const mapping = {
    'save_qualitative_tasks': 'save_qualitative_tasks',
    'save_quantitative_tasks': 'save_quantitative_tasks',
    'save_ppm_remark': 'save_ppm_remark',
    'complete_ppm_task': 'complete_ppm_task',
    // ... add all action types
  };
  return mapping[action] ?? action;
}
```

---

## 🚀 Rollout Plan

### Phase 1: Backend Development (Week 1-2)
- [ ] Create new endpoint handler for `batch_sync_offline_actions`
- [ ] Implement request validation
- [ ] Implement per-action processing with error handling
- [ ] Add idempotency check (optional but recommended)
- [ ] Create response builder
- [ ] Unit tests for each action type
- [ ] Integration tests with test database

### Phase 2: Mobile App Integration (Week 3)
- [ ] Update `PPMRepository` to use batch endpoint
- [ ] Keep fallback to individual endpoints (feature flag)
- [ ] Add action type mapping logic
- [ ] Update error handling for batch responses
- [ ] Test with mock server
- [ ] Test with staging server

### Phase 3: Testing & QA (Week 4)
- [ ] End-to-end testing: Offline → fill forms → go online → sync
- [ ] Test all action types (C, D, E, F, G, H, images, complete)
- [ ] Test failure scenarios (partial success, invalid data)
- [ ] Test large batches (50+ actions)
- [ ] Performance testing (measure sync time improvement)
- [ ] Load testing (simulate 100 concurrent batch syncs)

### Phase 4: Gradual Rollout (Week 5)
- [ ] Deploy to production with feature flag OFF
- [ ] Enable for 10% of users (A/B test)
- [ ] Monitor: success rate, sync time, error logs
- [ ] If metrics good: increase to 50%
- [ ] If metrics good: enable for 100%
- [ ] Remove old sequential sync code (after 2 weeks stability)

---

## 📊 Success Metrics

### Target KPIs
| Metric | Current (Sequential) | Target (Batch) | Measurement Method |
|--------|---------------------|----------------|-------------------|
| **Avg Sync Time** | 60-120 seconds | <5 seconds | Mobile app logs |
| **Sync Success Rate** | 85% | >95% | Backend response tracking |
| **Network Requests per Task** | 15-20 | 1 | API call counter |
| **Battery Usage** | High (multiple radios) | Low (single request) | User feedback |
| **User Satisfaction** | 3.5/5 | >4.5/5 | In-app survey |

### Monitoring & Logging
**Backend:**
- Log all batch sync requests to `ppm_offline_sync_log` table
- Track: `ppm_task_id`, `user_id`, `total_actions`, `success_count`, `failed_count`, `duration_ms`
- Alert if: `success_rate < 90%` or `avg_duration > 10 seconds`

**Mobile:**
- Log sync start/end timestamps
- Track: actions_sent, actions_succeeded, actions_failed, network_time_ms
- Send analytics event: `ppm_offline_sync_completed`

---

## 🔧 Backward Compatibility

### Strategy: Dual Support (Recommended)
Backend supports **both** old individual endpoints AND new batch endpoint for 3 months.

**Why?**
- Allows gradual rollout
- Users on old app versions still work
- Easy rollback if issues found

**Implementation:**
- Keep existing `/api/m_ppm.php` logic for single actions
- Add new handler for `action=batch_sync_offline_actions`
- Mobile app tries batch first, falls back to sequential on error

**Timeline:**
- Month 1-2: Both endpoints active, mobile uses batch
- Month 3: Monitor usage, confirm <5% traffic on old endpoints
- Month 4: Deprecate old endpoints (return 410 Gone), force app update

---

## ❓ Open Questions for Backend Team

1. **Image Storage:** Do you want images uploaded inline in batch (base64) or should we keep separate image upload endpoint?
   - **Pro inline:** True single request
   - **Con inline:** Large payload size (5MB+ per image)
   - **Recommendation:** Keep images in batch but set max 3 images per batch, or separate endpoint

2. **Transaction Scope:** Do you prefer:
   - A) Per-action transactions (partial success possible)
   - B) Per-section transactions (e.g., all Section C tasks atomic)
   - C) Entire batch transaction (all-or-nothing)
   - **Recommendation:** Option B (per-section)

3. **Idempotency:** Should we implement strict idempotency check or allow re-application?
   - **Recommendation:** Soft idempotency (last-write-wins) + optional sync log for audit

4. **Max Batch Size:** What's reasonable limit for `actions` array?
   - **Recommendation:** 100 actions (typical task has 10-30)

5. **Response Size:** Should we include full action details in response or just summary?
   - **Recommendation:** Include per-action status for mobile retry logic

6. **Priority Handling:** Should certain actions process first (e.g., start_time before everything)?
   - **Recommendation:** Yes, respect `sequenceId` order (mobile sends in logical order)

---

## 📞 Support & Contact

**For Backend Implementation Questions:**
- Contact: GEMS Backend Team
- Document Owner: Mobile Development Team
- Last Updated: 11 November 2025

**Change Log:**
| Date | Version | Changes |
|------|---------|---------|
| 2025-11-11 | 1.0 | Initial specification |

---

## 📎 Appendices

### Appendix A: Full Example Request
```json
{
  "action": "batch_sync_offline_actions",
  "ppmTaskId": "PPM-2025-001",
  "userId": "USR123",
  "syncTimestamp": "2025-11-11T10:45:32+08:00",
  "deviceId": "iPhone14_ABC123DEF456",
  "actions": [
    {
      "sequenceId": 1,
      "actionType": "start_time",
      "createdAt": "2025-11-11T08:00:15+08:00",
      "payload": {
        "pmStartDateTime": "2025-11-11 08:00:00"
      }
    },
    {
      "sequenceId": 2,
      "actionType": "save_qualitative_tasks",
      "createdAt": "2025-11-11T08:15:42+08:00",
      "payload": {
        "tasks": [
          {"id": "Q001", "result": "OK", "remark": "Normal operation"},
          {"id": "Q002", "result": "OK", "remark": "No issues"},
          {"id": "Q003", "result": "NOT OK", "remark": "Minor leak detected"}
        ]
      }
    },
    {
      "sequenceId": 3,
      "actionType": "save_quantitative_tasks",
      "createdAt": "2025-11-11T08:30:20+08:00",
      "payload": {
        "tasks": [
          {
            "id": "QUAN001",
            "setValues": "220",
            "measuredValues": "218",
            "limit": "210-230",
            "result": "OK",
            "remark": "Within range"
          }
        ]
      }
    },
    {
      "sequenceId": 4,
      "actionType": "upload_ppm_maintenance_image",
      "createdAt": "2025-11-11T09:00:05+08:00",
      "payload": {
        "fileUpload": {
          "data": "/9j/4AAQSkZJRgABAQAAAQABAAD...", 
          "name": "IMG_20251111_090000.jpg"
        },
        "description": "Before maintenance - showing minor leak",
        "latitude": "3.1569",
        "longitude": "101.7123",
        "timestamp": "2025-11-11 09:00:00"
      }
    },
    {
      "sequenceId": 5,
      "actionType": "save_ppm_remark",
      "createdAt": "2025-11-11T10:30:00+08:00",
      "payload": {
        "remark": "Equipment serviced successfully. Minor leak repaired. Recommend inspection in 3 months."
      }
    },
    {
      "sequenceId": 6,
      "actionType": "complete_ppm_task",
      "createdAt": "2025-11-11T10:45:00+08:00",
      "payload": {
        "endTime": "2025-11-11 10:45:00",
        "completedOffline": true
      }
    }
  ]
}
```

### Appendix B: Full Example Response
```json
{
  "success": true,
  "message": "Batch sync completed successfully",
  "ppmTaskId": "PPM-2025-001",
  "syncedAt": "2025-11-11T10:45:35+08:00",
  "processingTime": 1247,
  "summary": {
    "totalActions": 6,
    "successCount": 6,
    "failedCount": 0,
    "skippedCount": 0
  },
  "results": [
    {
      "sequenceId": 1,
      "actionType": "start_time",
      "status": "success",
      "message": "Task start time updated successfully",
      "processedAt": "2025-11-11T10:45:33+08:00"
    },
    {
      "sequenceId": 2,
      "actionType": "save_qualitative_tasks",
      "status": "success",
      "message": "3 qualitative tasks updated successfully",
      "processedAt": "2025-11-11T10:45:33+08:00",
      "details": {
        "tasksUpdated": 3
      }
    },
    {
      "sequenceId": 3,
      "actionType": "save_quantitative_tasks",
      "status": "success",
      "message": "1 quantitative task updated successfully",
      "processedAt": "2025-11-11T10:45:34+08:00",
      "details": {
        "tasksUpdated": 1
      }
    },
    {
      "sequenceId": 4,
      "actionType": "upload_ppm_maintenance_image",
      "status": "success",
      "message": "Image uploaded successfully",
      "processedAt": "2025-11-11T10:45:34+08:00",
      "details": {
        "uploadId": "IMG_67890",
        "fileSize": "1.2MB",
        "thumbnailGenerated": true
      }
    },
    {
      "sequenceId": 5,
      "actionType": "save_ppm_remark",
      "status": "success",
      "message": "PPM remark saved successfully",
      "processedAt": "2025-11-11T10:45:34+08:00"
    },
    {
      "sequenceId": 6,
      "actionType": "complete_ppm_task",
      "status": "success",
      "message": "PPM task marked as completed",
      "processedAt": "2025-11-11T10:45:35+08:00",
      "details": {
        "duration": "2 hours 45 minutes",
        "startTime": "2025-11-11 08:00:00",
        "endTime": "2025-11-11 10:45:00"
      }
    }
  ]
}
```

---

**END OF SPECIFICATION**
