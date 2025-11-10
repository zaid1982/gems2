# Material Returns - Quick Reference Card

## 🚀 Quick Start (5 minutes)

```bash
# 1. Run migration
mysql -u root -p gems2 < create_material_returns.sql

# 2. Test endpoint
curl -X GET "http://localhost/gems2/api/m_inventory.php/return_eligible_items/123" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "deviceid: test_device"
```

## 📋 API Cheat Sheet

| Endpoint | Method | Role | Body Required |
|----------|--------|------|---------------|
| `/return_eligible_items/:userId` | GET | Tech | No |
| `/request_return` | POST | Tech | ✅ Yes |
| `/storekeeper_pending_returns` | GET | SK | No |
| `/return_detail/:returnId` | GET | Both | No |
| `/confirm_return/:returnId` | PUT | SK | No |
| `/return_history?filters` | GET | Both | No |
| `/return_statistics?userId` | GET | Both | No |

## 💡 Common Use Cases

### Technician: Return 5 out of 10 parts
```json
POST /m_inventory.php/request_return
{
  "woTaskPartsId": "12345",
  "quantityReturned": 5,
  "returnReason": "damaged"
}
```

### Storekeeper: Confirm return
```bash
PUT /m_inventory.php/confirm_return/789
# Body: empty or {}
```

## ⚙️ Valid Return Reasons
- `unused_excess` - Unused/Excess
- `wrong_part` - Wrong Part
- `damaged` - Damaged/Defective
- `other` - Other

## 🔍 Quick Debug Queries

```sql
-- Check pending returns
SELECT * FROM material_returns WHERE return_status = 'pending';

-- Verify inventory
SELECT part_id, part_count, part_locked, 
       part_count - part_locked AS available 
FROM ast_part WHERE part_id = 'PT-001';

-- Check part status
SELECT part_sub_id, part_sub_status, part_sub_return_id 
FROM ast_part_sub 
WHERE wo_task_parts_id = 12345;
```

## 🚨 Common Errors

| Error | Cause | Fix |
|-------|-------|-----|
| "Item does not belong to you" | Wrong user | Check ownership |
| "Not enough parts in possession" | Parts used | Check status 36 count |
| "Return already exists" | Duplicate | Wait for confirmation |
| "Already completed" | Double confirm | Check return_status |

## 📊 Status Codes

- **36** - Parts Collected (can be returned)
- **47** - Returned (after confirmation)
- **48** - Return Pending (during request)

## 🎯 Key Tables

| Table | Purpose |
|-------|---------|
| `material_returns` | Return requests |
| `ast_part_sub` | Individual part instances |
| `ast_part` | Inventory totals |
| `inventory_logs` | Audit trail (optional) |

## 🔒 Transaction Flow

```
1. BEGIN TRANSACTION
2. Update material_returns → 'completed'
3. Update ast_part_sub (N×) → status 47
4. Update ast_part → part_locked--
5. Insert inventory_logs
6. COMMIT (or ROLLBACK on error)
```

## 📁 File Locations

```
api/function/f_material_return.php   - Business logic
api/m_inventory.php                  - API routes
create_material_returns.sql          - Schema
MATERIAL_RETURNS_API_TESTING.md      - Full tests
GEMS2_Material_Returns.postman_*     - Postman
```

## 🛠️ Useful Commands

```bash
# Enable debug logs
# Set Constant::$isLogged = true in constant.php

# View logs
tail -f logs/debug.log
tail -f logs/error.log

# Rollback migration
mysql -u root -p gems2 < create_material_returns.sql
# Then run the rollback section at bottom
```

## ✅ Pre-Flight Checklist

- [ ] Database migration run
- [ ] JWT tokens obtained
- [ ] Test data created (status 36 items)
- [ ] Postman collection imported
- [ ] Storekeeper role verified

---

**Questions?** See `MATERIAL_RETURNS_IMPLEMENTATION_SUMMARY.md`
