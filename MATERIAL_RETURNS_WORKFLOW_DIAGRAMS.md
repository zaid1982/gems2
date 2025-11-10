# Material Returns - System Workflow Diagram

## 📊 Overall Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    GEMS2 Material Returns                       │
│                         Workflow                                │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐                               ┌──────────────┐
│  Technician  │                               │ Storekeeper  │
│   (Mobile)   │                               │    (Web)     │
└──────┬───────┘                               └──────┬───────┘
       │                                              │
       │ GET /return_eligible_items                   │
       ├──────────────────────────►                   │
       │                                              │
       │ ◄──────────────────────────                  │
       │  [List of parts collected]                   │
       │                                              │
       │ POST /request_return                         │
       │ { woTaskPartsId, quantity,                   │
       │   returnReason }                             │
       ├──────────────────────────►                   │
       │                           │                  │
       │                           ▼                  │
       │                    ┌──────────────┐          │
       │                    │  Validation  │          │
       │                    │  - Status 36?│          │
       │                    │  - Ownership?│          │
       │                    │  - Quantity? │          │
       │                    └──────┬───────┘          │
       │                           │                  │
       │                           ▼                  │
       │                    ┌──────────────┐          │
       │                    │   Insert     │          │
       │                    │material_     │          │
       │                    │returns       │          │
       │                    │status='      │          │
       │                    │pending'      │          │
       │                    └──────────────┘          │
       │                                              │
       │ ◄──────────────────────────                  │
       │  { success: true, returnId }                 │
       │                                              │
       │                                              │ GET /storekeeper_pending_returns
       │                                              ├────────────────────────►
       │                                              │
       │                                              │ ◄────────────────────────
       │                                              │  [List of pending returns]
       │                                              │
       │                                              │ PUT /confirm_return/{id}
       │                                              ├────────────────────────►
       │                                              │
       │                                              │         ▼
       │                                              │  ┌──────────────┐
       │                                              │  │ BEGIN TRANS  │
       │                                              │  └──────┬───────┘
       │                                              │         │
       │                                              │         ▼
       │                                              │  ┌──────────────────────┐
       │                                              │  │ 1. Update return     │
       │                                              │  │    status='completed'│
       │                                              │  └──────┬───────────────┘
       │                                              │         │
       │                                              │         ▼
       │                                              │  ┌──────────────────────┐
       │                                              │  │ 2. Update N× parts   │
       │                                              │  │    ast_part_sub      │
       │                                              │  │    status=47         │
       │                                              │  └──────┬───────────────┘
       │                                              │         │
       │                                              │         ▼
       │                                              │  ┌──────────────────────┐
       │                                              │  │ 3. Update inventory  │
       │                                              │  │    part_locked--     │
       │                                              │  └──────┬───────────────┘
       │                                              │         │
       │                                              │         ▼
       │                                              │  ┌──────────────────────┐
       │                                              │  │ 4. Log audit         │
       │                                              │  │    inventory_logs    │
       │                                              │  └──────┬───────────────┘
       │                                              │         │
       │                                              │         ▼
       │                                              │  ┌──────────────┐
       │                                              │  │ COMMIT       │
       │                                              │  └──────────────┘
       │                                              │
       │                                              │ ◄────────────────────────
       │                                              │  { success: true,
       │                                              │    newAvailable: 50 }
       │                                              │
       └──────────────────────────────────────────────┴──────────────────────────┘
```

## 🗄️ Database Schema Relationships

```
┌───────────────────────────────────────────────────────────────────┐
│                        Database Tables                            │
└───────────────────────────────────────────────────────────────────┘

         ┌──────────────────┐
         │   wo_task_parts  │
         │  (status = 36)   │
         └────────┬─────────┘
                  │
                  │ FK: wo_task_parts_id
                  │
                  ▼
         ┌──────────────────┐           ┌──────────────────┐
         │material_returns  │           │   ast_part_sub   │
         │                  │           │                  │
         │ - return_id (PK) │           │ - part_sub_id    │
         │ - wo_task_parts_id◄──────────┼ - wo_task_parts_id
         │ - part_id        ├──────────►│ - part_sub_status│
         │ - quantity       │           │   (36→47)        │
         │ - return_status  │           │ - part_sub_      │
         │   (pending/      │           │   return_id (FK) │
         │    completed)    │           └────────┬─────────┘
         └────────┬─────────┘                    │
                  │                              │
                  │ FK: part_id                  │ FK: part_id
                  │                              │
                  ▼                              ▼
         ┌──────────────────────────────────────┐
         │           ast_part                   │
         │                                      │
         │ - part_id (PK)                       │
         │ - part_count (total in store)       │
         │ - part_locked (reserved/checked out) │
         │ - part_available (calculated:        │
         │   part_count - part_locked)          │
         └──────────────────────────────────────┘
```

## 🔄 Status Transition Flow

```
┌──────────────────────────────────────────────────────────────┐
│                  Part Status Lifecycle                       │
└──────────────────────────────────────────────────────────────┘

    CHECKOUT FLOW                  RETURN FLOW
    ═════════════                  ═══════════

┌──────────────┐               ┌──────────────┐
│ Status: 46   │               │ Status: 36   │
│ (Available)  │               │ (Collected)  │
└──────┬───────┘               └──────┬───────┘
       │                              │
       │ Storekeeper                  │ Technician
       │ reserves parts               │ requests return
       │                              │
       ▼                              ▼
┌──────────────┐               ┌──────────────┐
│ Status: 51   │               │ Status: 48   │
│ (Reserved)   │               │ (Return      │
└──────┬───────┘               │  Pending)    │
       │                       └──────┬───────┘
       │ Storekeeper                  │
       │ checks out                   │ Storekeeper
       │                              │ confirms
       ▼                              │
┌──────────────┐                      │
│ Status: 36   ├──────────────────────┘
│ (Collected)  │
└──────┬───────┘
       │
       │ Storekeeper
       │ confirms return
       │
       ▼
┌──────────────┐
│ Status: 47   │
│ (Returned)   │
└──────────────┘
       │
       │ Part unlocked
       │ Available again
       │
       ▼
┌──────────────┐
│ Status: 46   │
│ (Available)  │
└──────────────┘
```

## 🧮 Inventory Calculation Logic

```
┌───────────────────────────────────────────────────────────────┐
│             How part_available is Calculated                  │
└───────────────────────────────────────────────────────────────┘

                    ast_part table
    ┌─────────────────────────────────────────────┐
    │  part_count = 100  (Total in inventory)     │
    │  part_locked = 30  (Currently checked out)  │
    └─────────────────────────────────────────────┘
                         │
                         │ Calculation:
                         │ part_available = part_count - part_locked
                         ▼
    ┌─────────────────────────────────────────────┐
    │  part_available = 70  (Can be issued)       │
    └─────────────────────────────────────────────┘


    EXAMPLE: Return 5 items
    ───────────────────────

    BEFORE Return:
    ┌─────────────────────────────────────────────┐
    │  part_count  = 100                          │
    │  part_locked = 30                           │
    │  available   = 70                           │
    └─────────────────────────────────────────────┘

    Storekeeper confirms return of 5:
    ┌─────────────────────────────────────────────┐
    │  UPDATE ast_part                            │
    │  SET part_locked = part_locked - 5          │
    │  WHERE part_id = 'PT-001'                   │
    └─────────────────────────────────────────────┘

    AFTER Return:
    ┌─────────────────────────────────────────────┐
    │  part_count  = 100  (unchanged)             │
    │  part_locked = 25   (decreased by 5)        │
    │  available   = 75   (increased by 5)        │
    └─────────────────────────────────────────────┘
```

## 🔀 Partial Return Scenario

```
┌───────────────────────────────────────────────────────────────┐
│         Technician Collected 10 Parts → Returns in Batches    │
└───────────────────────────────────────────────────────────────┘

    Day 1: Technician collects 10 parts
    ─────────────────────────────────────
    ┌──────────────────────────────────┐
    │ wo_task_parts                    │
    │ - woTaskPartsId: 12345           │
    │ - woTaskPartsQuantity: 10        │
    │ - woTaskPartsStatus: 36          │
    └──────────────────────────────────┘
    
    ┌──────────────────────────────────┐
    │ ast_part_sub (10 records)        │
    │ - partSubId: 001-010             │
    │ - partSubStatus: 36 (all)        │
    │ - partSubReturnId: NULL (all)    │
    └──────────────────────────────────┘


    Day 3: Return 3 damaged parts
    ──────────────────────────────
    POST /request_return
    { woTaskPartsId: 12345, quantityReturned: 3 }
    
    ┌──────────────────────────────────┐
    │ material_returns                 │
    │ - returnId: 100                  │
    │ - woTaskPartsId: 12345           │
    │ - quantityReturned: 3            │
    │ - returnStatus: 'pending'        │
    └──────────────────────────────────┘

    Storekeeper confirms:
    ┌──────────────────────────────────┐
    │ ast_part_sub (3 updated)         │
    │ - partSubId: 001,002,003         │
    │ - partSubStatus: 47 (Returned)   │
    │ - partSubReturnId: 100           │
    └──────────────────────────────────┘
    
    Remaining: 7 parts still status 36


    Day 5: Return remaining 7 parts
    ────────────────────────────────
    POST /request_return
    { woTaskPartsId: 12345, quantityReturned: 7 }
    
    ┌──────────────────────────────────┐
    │ material_returns                 │
    │ - returnId: 101                  │
    │ - woTaskPartsId: 12345           │
    │ - quantityReturned: 7            │
    │ - returnStatus: 'pending'        │
    └──────────────────────────────────┘

    Storekeeper confirms:
    ┌──────────────────────────────────┐
    │ ast_part_sub (7 updated)         │
    │ - partSubId: 004-010             │
    │ - partSubStatus: 47 (Returned)   │
    │ - partSubReturnId: 101           │
    └──────────────────────────────────┘
    
    Result: All 10 parts returned (2 separate returns)
```

## 🔐 Security & Validation Flow

```
┌───────────────────────────────────────────────────────────────┐
│             Request Validation Pipeline                       │
└───────────────────────────────────────────────────────────────┘

    API Request
        │
        ▼
    ┌─────────────────────┐
    │ 1. JWT Auth Check   │
    │    - Valid token?   │
    │    - Not expired?   │
    └─────────┬───────────┘
              │ ✓
              ▼
    ┌─────────────────────┐
    │ 2. Device ID Check  │
    │    - Matches user?  │
    └─────────┬───────────┘
              │ ✓
              ▼
    ┌─────────────────────┐
    │ 3. Ownership Check  │
    │    - User's item?   │
    │    - Correct site?  │
    └─────────┬───────────┘
              │ ✓
              ▼
    ┌─────────────────────┐
    │ 4. Status Check     │
    │    - Status = 36?   │
    │    - Not consumed?  │
    └─────────┬───────────┘
              │ ✓
              ▼
    ┌─────────────────────┐
    │ 5. Quantity Check   │
    │    - qty > 0?       │
    │    - qty ≤ avail?   │
    │    - Parts exist?   │
    └─────────┬───────────┘
              │ ✓
              ▼
    ┌─────────────────────┐
    │ 6. Duplicate Check  │
    │    - No pending?    │
    └─────────┬───────────┘
              │ ✓
              ▼
    ┌─────────────────────┐
    │ 7. Business Logic   │
    │    - Valid reason?  │
    │    - Optional date? │
    └─────────┬───────────┘
              │ ✓
              ▼
        Process Request
              │
              ▼
        Return Success


    ⚠️ Any step fails → Return error immediately
```

## 📊 Monitoring Dashboard (Conceptual)

```
┌───────────────────────────────────────────────────────────────┐
│              Material Returns Dashboard                       │
└───────────────────────────────────────────────────────────────┘

    STOREKEEPER VIEW
    ────────────────
    ┌─────────────────────────────────────────────────┐
    │  Pending Returns: 8                             │
    │  ┌───────────────────┐  ┌───────────────────┐  │
    │  │ Oldest: 3 days    │  │ Urgent: 2         │  │
    │  └───────────────────┘  └───────────────────┘  │
    └─────────────────────────────────────────────────┘
    
    │ Return ID │ Technician │ Item         │ Qty │ Days │
    ├───────────┼────────────┼──────────────┼─────┼──────┤
    │ 101       │ John Doe   │ Bolt M8      │ 5   │ 3    │
    │ 102       │ Jane Smith │ Wire 2.5mm   │ 10  │ 2    │
    │ 103       │ Mike Chen  │ Screwdriver  │ 1   │ 1    │
    └───────────┴────────────┴──────────────┴─────┴──────┘
    
    [Confirm] [View Details] [Filter]


    TECHNICIAN VIEW
    ───────────────
    ┌─────────────────────────────────────────────────┐
    │  My Collected Items: 12                         │
    │  ┌───────────────────┐  ┌───────────────────┐  │
    │  │ Returnable: 10    │  │ Pending: 1        │  │
    │  └───────────────────┘  └───────────────────┘  │
    └─────────────────────────────────────────────────┘
    
    │ Item         │ WO No      │ Collected │ Qty │ Status   │
    ├──────────────┼────────────┼───────────┼─────┼──────────┤
    │ Bolt M8      │ WR25110001 │ 5 Nov     │ 10  │ ✅ Return│
    │ Wire 2.5mm   │ WR25110002 │ 6 Nov     │ 5   │ ⏳ Pending│
    │ Screwdriver  │ WR25110003 │ 7 Nov     │ 2   │ ✅ Return│
    └──────────────┴────────────┴───────────┴─────┴──────────┘
    
    [Request Return] [View History]
```

---

**For Implementation Details:** See `MATERIAL_RETURNS_IMPLEMENTATION_SUMMARY.md`  
**For API Testing:** See `MATERIAL_RETURNS_API_TESTING.md`  
**For Quick Reference:** See `MATERIAL_RETURNS_QUICK_REFERENCE.md`
