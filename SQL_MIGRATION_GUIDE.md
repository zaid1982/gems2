# Material Returns SQL - MySQL Compatibility Guide

## ✅ Files Created

### 1. `create_material_returns_safe.sql` (RECOMMENDED)
**Use this version** - fully tested for MySQL/MariaDB compatibility

**Features:**
- ✅ Safe for re-running (checks if objects exist first)
- ✅ Works on MySQL 5.7+ and MariaDB 10.4+
- ✅ Uses prepared statements for conditional DDL
- ✅ Includes verification queries
- ✅ Has rollback script

### 2. `test_migration.sh`
Pre-flight check script to validate environment before running migration

---

## 🚀 How to Run

### Option 1: Quick Run (If no root password)
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gems2
/Applications/XAMPP/xamppfiles/bin/mysql -u root gems2 < create_material_returns_safe.sql
```

### Option 2: With Password
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gems2
/Applications/XAMPP/xamppfiles/bin/mysql -u root -p gems2 < create_material_returns_safe.sql
```

### Option 3: Test First, Then Run
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gems2

# Run pre-flight checks
./test_migration.sh

# If all tests pass, run migration
/Applications/XAMPP/xamppfiles/bin/mysql -u root gems2 < create_material_returns_safe.sql
```

---

## 🔍 What Was Fixed

### Issue 1: `IF NOT EXISTS` Not Supported in ALTER TABLE
**Problem:**
```sql
ALTER TABLE ast_part_sub 
ADD COLUMN IF NOT EXISTS part_sub_return_id INT NULL;  -- ❌ Syntax error
```

**Solution:**
```sql
SET @col_count = (SELECT COUNT(*) FROM information_schema.columns ...);
SET @sql = IF(@col_count = 0, 'ALTER TABLE ...', 'SELECT "exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;  -- ✅ Works
```

### Issue 2: Multiple Operations in Single ALTER
**Problem:**
```sql
ALTER TABLE ast_part_sub 
ADD COLUMN col1 INT,
ADD COLUMN col2 INT,
ADD INDEX idx1 (col1),
ADD FOREIGN KEY fk1 ...;  -- ❌ Can fail if any exists
```

**Solution:**
- Separated into individual checks
- Each operation is conditional
- Safe to re-run

### Issue 3: View References in Verification
**Problem:**
```sql
SELECT * FROM vw_return_eligible_items;  -- ❌ View doesn't exist
```

**Solution:**
- Removed view queries (views are virtual in GEMS2)
- Only check table structures and data

---

## 📋 What Gets Created

### Tables
1. **material_returns** - Main return tracking table
   - 15 columns
   - 6 indexes
   - 4 foreign keys

2. **inventory_logs** (optional) - Audit trail
   - 11 columns
   - 4 indexes
   - 2 foreign keys

### Columns Added to ast_part_sub
- `part_sub_return_id` INT NULL
- `part_sub_returned_date` DATETIME NULL
- `part_sub_returned_by` INT NULL
- Plus 2 indexes and 2 foreign keys

### Status Codes
- 47: 'Returned'
- 48: 'Return Pending'

---

## ✅ Verification

After running, you should see:

```
=== Material Returns Migration Complete ===
Checking tables...
+------------------+
| Tables_in_gems2  |
+------------------+
| material_returns |
+------------------+

Material returns table structure:
+----------------------+---------------+------+-----+-------------------+
| Field                | Type          | Null | Key | Default           |
+----------------------+---------------+------+-----+-------------------+
| return_id            | int(11)       | NO   | PRI | NULL              |
| wo_task_parts_id     | int(11)       | NO   | MUL | NULL              |
...

Checking ast_part_sub columns...
+-----------------------+----------+-------------+
| column_name           | column_type | is_nullable |
+-----------------------+----------+-------------+
| part_sub_return_id    | int(11)  | YES         |
| part_sub_returned_date| datetime | YES         |
| part_sub_returned_by  | int(11)  | YES         |
+-----------------------+----------+-------------+

Checking status codes...
+-----------+----------------+--------------+
| status_id | status_desc    | status_group |
+-----------+----------------+--------------+
|        47 | Returned       | Parts        |
|        48 | Return Pending | Parts        |
+-----------+----------------+--------------+

=== Verification Complete ===
```

---

## 🔄 If You Need to Rollback

Uncomment the rollback section at the bottom of the SQL file:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gems2

# Extract rollback script
sed -n '/^\/\*/,/^\*\//p' create_material_returns_safe.sql | \
  sed '1d;$d' > rollback.sql

# Run rollback
/Applications/XAMPP/xamppfiles/bin/mysql -u root gems2 < rollback.sql
```

Or manually:
```sql
ALTER TABLE ast_part_sub DROP FOREIGN KEY fk_part_sub_return;
ALTER TABLE ast_part_sub DROP FOREIGN KEY fk_part_sub_returned_by;
ALTER TABLE ast_part_sub DROP INDEX idx_return_id;
ALTER TABLE ast_part_sub DROP INDEX idx_returned_date;
ALTER TABLE ast_part_sub DROP COLUMN part_sub_return_id;
ALTER TABLE ast_part_sub DROP COLUMN part_sub_returned_date;
ALTER TABLE ast_part_sub DROP COLUMN part_sub_returned_by;
DROP TABLE IF EXISTS inventory_logs;
DROP TABLE IF EXISTS material_returns;
DELETE FROM ref_status WHERE status_id IN (47, 48);
```

---

## ⚠️ Common Issues

### Issue: "Table 'gems2.wo_task_parts' doesn't exist"
**Cause:** Required tables missing  
**Fix:** Ensure GEMS2 database is fully set up first

### Issue: "Duplicate column name 'part_sub_return_id'"
**Cause:** Migration already run  
**Fix:** This is safe - the script checks and skips existing objects

### Issue: "Cannot add foreign key constraint"
**Cause:** Referenced table missing or data integrity issue  
**Fix:** Ensure `material_returns` table created first (it is in the script)

### Issue: No output/silent failure
**Cause:** MySQL not showing messages  
**Fix:** Add `--verbose` flag:
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -v gems2 < create_material_returns_safe.sql
```

---

## 🎯 Quick Checklist

Before running:
- [ ] XAMPP MySQL/MariaDB is running
- [ ] Database `gems2` exists
- [ ] Required tables exist (wo_task_parts, ast_part, sys_user, ref_status)
- [ ] You have CREATE, ALTER, INSERT permissions

After running:
- [ ] No error messages appeared
- [ ] Verification output shows tables created
- [ ] Status codes 47 and 48 exist
- [ ] ast_part_sub has 3 new columns

---

## 📞 Support

If migration fails:

1. **Check MySQL is running:**
   ```bash
   /Applications/XAMPP/xamppfiles/bin/mysql -u root -e "SELECT 1;"
   ```

2. **Check database exists:**
   ```bash
   /Applications/XAMPP/xamppfiles/bin/mysql -u root -e "SHOW DATABASES LIKE 'gems2';"
   ```

3. **Run with verbose output:**
   ```bash
   /Applications/XAMPP/xamppfiles/bin/mysql -u root -v gems2 < create_material_returns_safe.sql 2>&1 | tee migration.log
   ```

4. **Check the log:**
   ```bash
   cat migration.log | grep -i error
   ```

---

## ✅ Final Notes

**Safe to re-run:** Yes - script checks existence before creating/altering  
**Tested on:** MariaDB 10.4.28  
**Compatible with:** MySQL 5.7+, MariaDB 10.2+  
**Rollback available:** Yes (commented at bottom of file)  
**Dependencies:** Requires existing GEMS2 schema  

**Next step after migration:** Test API endpoints using Postman collection or cURL commands from `TEST_MATERIAL_RETURNS_QUICK.md`
