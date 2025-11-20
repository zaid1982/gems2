# Mobile Material Return APIs

These endpoints power the new mobile "Return to Store" page. All requests must include a valid `Authorization: Bearer <jwt>` header (and `deviceid` when required for mobile clients). Responses follow the standard `{ success, result, error, errmsg }` envelope used across the core APIs. Returned items now flow through a lightweight "return ticket" so a storekeeper can verify condition before stock counts change.

## 1. GET `/api/wo_request.php/list_mobile_return`

Returns every part instance that the current user has collected (status `36`) and not yet returned. Results are scoped to the caller's site.

### Response Fields (`result` array)
| Field | Description |
| --- | --- |
| `woTaskNo` | Work order / request number that the part was pulled for. |
| `woTaskRequestNo` | Material request number. |
| `woTaskRequestId` | Request primary key. |
| `woTaskPartsId` | Line item id on the request. |
| `partId` | Inventory part id. |
| `itemDescription` | Human readable description from `ref_item`. |
| `partSubId` | Unique part-instance id (serial). This is what the return API consumes. |
| `partSubNo` | Label/serial number shown on the part. |
| `checkOutTime` | Timestamp when the storekeeper checked the item out. |
| `partSubStatus` | Should be `36` (collected). Included for completeness. |

### Example
```bash
curl -s \
  -H "Authorization: Bearer <token>" \
  http://localhost/gems2/api/wo_request.php/list_mobile_return
```

## 2. POST `/api/wo_request.php/return_parts`

Accepts one or more part instances, creates a return ticket, and marks each `ast_part_sub` row as `37` (Pending Verification) instead of immediately restocking. The backend still validates ownership (based on `part_sub_collected_by`) and saves an audit trail of who initiated the return. The same payload structure applies; the transaction guarantees that either the entire ticket is recorded or none of it is.

### Request Body
```json
{
  "items": [
    {
      "partSubIds": [38, 37, 36]
    },
    {
      "woTaskPartsId": "1630",
      "quantity": 2
    }
  ]
}
```

- `partSubIds` *(array, optional)*: Explicit list of `part_sub_id` values to return. The API will return exactly these instances.
- `woTaskPartsId` *(string, optional)*: Line item id to return from. Required when `partSubIds` is omitted.
- `quantity` *(integer, optional)*: Number of items to return when using `woTaskPartsId`. The API will pick the earliest collected instances. If `partSubIds` is provided, `quantity` must match the array length (or be omitted).
- `returnReason` *(string, optional)*: One of `unused_excess`, `wrong_part`, `damaged`, `other`. Defaults to `unused_excess` when omitted.
- `returnRemarks` *(string, optional)*: Free-text detail explaining the reason (shown to the storekeeper during verification).

> Each entry in `items` must specify either `partSubIds` **or** `woTaskPartsId`. Duplicate `part_sub_id` values across entries are rejected.

### Successful Response (`result` object)
| Field | Description |
| --- | --- |
| `totalReturned` | Sum of all instances processed in this call. |
| `returnTicketIds` | Array of every ticket generated inside this payload. When only one ticket is created the response also echoes `returnTicketId`. |
| `items` | Array summarizing every line that now sits in Pending Verification. Each element contains `returnTicketId`, `returnReason`, `returnRemarks`, `woTaskRequestId`, `woTaskPartsId`, `partId`, `quantityReturned`, `partSubIds`, `woTaskNo`, and `woTaskRequestNo`. |

### Example
```bash
curl -s -X POST \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
        "items": [
          { "partSubIds": [38] },
          { "woTaskPartsId": "1586", "quantity": 1 }
        ]
      }' \
  http://localhost/gems2/api/wo_request.php/return_parts
```

**Sample JSON (success):**
```json
{
  "success": true,
  "result": {
    "returnTicketId": "5123",
    "returnTicketIds": ["5123"],
    "totalReturned": 2,
    "items": [
      {
        "returnTicketId": "5123",
        "returnReason": "unused_excess",
        "returnRemarks": "Leftover after repair",
        "woTaskRequestId": "1306",
        "woTaskPartsId": "1630",
        "partId": "13",
        "quantityReturned": 1,
        "partSubIds": ["38"],
        "woTaskNo": "WODEMO25052200151",
        "woTaskRequestNo": "RQDEMO25052200134"
      },
      {
        "returnTicketId": "5124",
        "returnReason": "wrong_part",
        "returnRemarks": "Wrong rating",
        "woTaskRequestId": "1265",
        "woTaskPartsId": "1586",
        "partId": "51",
        "quantityReturned": 1,
        "partSubIds": ["20482"],
        "woTaskNo": "WRDEMO23013100117",
        "woTaskRequestNo": "RQDEMO23020100090"
      }
    ]
  },
  "error": "",
  "errmsg": "Return queued for store verification"
}
```

### Failure Modes
- `Invalid return payload` – `items` array missing or malformed.
- `Quantity mismatch for partSubIds` – `quantity` does not match supplied `partSubIds` length.
- `No items resolved for return` – IDs were already returned or not owned by the caller.
- Standard envelope errors such as `Parameter Authorization empty` when JWT is missing.

## Integration Notes
- Always refresh the list by calling `list_mobile_return` after a return to reflect the updated inventory.
- The API enforces uniqueness per `part_sub_id` within the request; frontends should prevent users from selecting the same item twice before submission.
- Because inventory math runs inside one transaction, do not split multi-item returns unless the UX specifically requires per-item confirmation.
- After a technician submits a return ticket, the UI should show the ticket status (Pending Verification, Issue Found, Completed) so the tech knows whether the store has accepted it.

## Process Flow (Text Diagram)

```
Technician opens mobile Return page
  ↓ selects part_sub_id instances (status 36)
POST /api/wo_request.php/return_parts
  ↓ server validates ownership + payload
  ↓ creates material_returns ticket(s)
  ↓ sets selected ast_part_sub rows → status 37 (Pending Verification)
Storekeeper (role 16) opens list
  ↓ GET /api/wo_request.php/list_return_verification[ /site/{id} ][?detail=1]
  ↓ reviews ticket metadata + pending part_sub_ids
POST /api/wo_request.php/verify_return
  ↙                ↘
approve subset      reject subset (remark required)
  ↓                    ↓
ast_part_sub → 46    ast_part_sub → 38
ast_part.part_count+ approved qty
  ↓
Ticket auto-closes (return_status=completed) when no rows remain at status 37
```

## 3. GET `/api/wo_request.php/list_return_verification`

Storekeepers (role `16`) and admins (`1` / `10`) call this to see every open ticket (status Pending Verification). Non-admins are auto-scoped to their own site; admins can append `/site/{siteId}` to inspect another location. Add `?detail=1` to include part-level rows.

### URL patterns
- `GET /api/wo_request.php/list_return_verification` → current user’s site scope
- `GET /api/wo_request.php/list_return_verification/site/{siteId}` → admin override
- Append `?detail=1` to either path for part-level `items`

### Response Fields (`result` array)
| Field | Description |
| --- | --- |
| `returnTicketId` | Ticket primary key. |
| `woTaskNo` | Work order/request reference. |
| `woTaskRequestNo` | Material request reference. |
| `technicianName` | Technician full name who initiated the return. |
| `siteId` / `siteName` | Location metadata for dashboards. |
| `submittedAt` | Timestamp when the ticket was created. |
| `itemCount` | Number of part instances pending verification. |
| `partSubIds` | Flat list of pending `part_sub_id` values (helps quick-select). |
| `items` | Optional nested array (same structure as the mobile list) when `?detail=1` is provided. |

### Example
```bash
curl -s \
  -H "Authorization: Bearer <storekeeper token>" \
  http://localhost/gems2/api/wo_request.php/list_return_verification?detail=1
```

## 4. POST `/api/wo_request.php/verify_return`

Finalizes a ticket. Payload indicates which part instances are approved or rejected. Approved items flip back to status `46` (In Store) and increment `ast_part.part_count`; rejected ones move to `38` (Issue Found) so technicians can act. If `partSubIds` is omitted the action applies to all rows still pending for that ticket. Rejecting any row requires a remark.

### Request Body
```json
{
  "returnTicketId": "5123",
  "action": "approve", // or "reject"
  "partSubIds": [38, 37],
  "remark": "Left cable missing"
}
```

### Request Notes
- `action` supports `approve` (restock) or `reject` (flag issue). Mixed decisions can be handled by calling the endpoint multiple times with different `partSubIds` subsets.
- `partSubIds` *(optional)* defaults to every pending entry when omitted.
- `remark` *(required when rejecting)* is appended to the technician’s original note so they understand the issue.

### Successful Response (`result` object)
| Field | Description |
| --- | --- |
| `returnTicketId` | Echo of the processed ticket. |
| `action` | `approved`, `partially_approved`, `rejected`, or `pending` (some rows still awaiting verification). |
| `approvedCount` | Number of part instances restocked in this call. |
| `rejectedCount` | Number of part instances flagged for follow-up in this call. |
| `pendingCount` | Items still awaiting a decision after this call. |
| `items` | Detailed array including `partSubId`, `status`, and optional `remark`. |

### Failure Modes
- `Ticket not found or already closed` – invalid `returnTicketId` or another storekeeper already processed it.
- `partSubIds mismatch ticket` – payload references items that do not belong to the ticket.
- `action invalid for selection` – e.g., trying to approve an empty list.
- `Remark required when rejecting returned items` – triggered when `action="reject"` without `remark`.

## Status Codes Recap
- `36` – Collected (technician currently holds the part).
- `37` – Pending Verification (submitted via `return_parts`).
- `38` – Issue Found / Rejected during verification.
- `46` – In Store (passed verification, available for future picks).
