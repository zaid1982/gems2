# Database Schema Comparison Tool

## Purpose
Compare DEV vs PROD database schemas and automatically generate SQL migration scripts for deployment.

## Features
- ✅ **Table Detection**: Identifies new, modified, and deleted tables
- ✅ **Column Analysis**: Detects new/modified/deleted columns with full details (type, length, null, default, etc.)
- ✅ **Index Tracking**: Compares indexes and unique constraints
- ✅ **Foreign Key Detection**: Identifies new foreign key relationships
- ✅ **SQL Generation**: Automatically creates ALTER TABLE statements
- ✅ **Safety Warnings**: Flags potentially dangerous operations (DROP statements)
- ✅ **Visual Interface**: Clean, color-coded HTML report
- ✅ **Export Options**: Download SQL or copy to clipboard

## Quick Start

### 1. Configure Database Connections

Edit `schema_compare.php` and update the database credentials (lines 16-28):

```php
$databases = [
    'dev' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'gems2_dev',  // Your dev database name
        'label' => 'Development'
    ],
    'prod' => [
        'host' => 'production-host.com',  // Production server
        'user' => 'prod_user',             // Production username
        'pass' => 'prod_password',         // Production password
        'name' => 'gems2_prod',            // Production database name
        'label' => 'Production'
    ]
];
```

### 2. Access the Tool

Open in your browser:
```
http://localhost/gems2/maintenance/schema_compare.php
```

### 3. Review the Report

The tool will show:
- **Summary Cards**: Quick overview of changes
- **New Tables**: Tables that need to be created in PROD
- **Modified Tables**: Tables with column/index changes
- **Deleted Tables**: Tables in PROD but not in DEV (⚠️ review carefully!)
- **Generated SQL**: Ready-to-run migration script

### 4. Export & Execute

- Click **"Download SQL Script"** to save the migration file
- Or **"Copy SQL to Clipboard"** for manual review
- Review the SQL carefully
- Execute on production database

## Understanding the Report

### Summary Cards
- 🟢 **New Tables**: Tables to create (safe)
- 🟡 **Modified Tables**: Tables with changes (review)
- 🔴 **Deleted Tables**: Tables to drop (⚠️ DANGER!)
- 🔵 **SQL Statements**: Total migration statements

### Color Coding
- **Green**: New additions (safe to add)
- **Yellow**: Modifications (review for data impact)
- **Red**: Deletions (⚠️ can cause data loss!)

### Generated SQL Types

**Safe Operations** (auto-generated):
```sql
ALTER TABLE `tablename` ADD COLUMN `columnname` VARCHAR(255) NULL;
ALTER TABLE `tablename` MODIFY COLUMN `columnname` INT(11) NOT NULL;
ALTER TABLE `tablename` ADD INDEX `idx_name` (`column`);
ALTER TABLE `tablename` ADD CONSTRAINT `fk_name` FOREIGN KEY...
CREATE TABLE `newtable` (...);
```

**Dangerous Operations** (commented out, manual review required):
```sql
-- WARNING: Column exists in PROD but not in DEV.
-- ALTER TABLE `tablename` DROP COLUMN `columnname`;
-- WARNING: Table exists in PROD but not in DEV.
-- DROP TABLE IF EXISTS `oldtable`;
```

## Best Practices

### Before Running
1. ✅ Backup production database
2. ✅ Test SQL on staging environment first
3. ✅ Review all changes carefully
4. ✅ Check for data migration needs (e.g., new NOT NULL columns on existing data)

### During Execution
1. Run SQL statements in order
2. Check for errors after each batch
3. Monitor application logs
4. Have rollback plan ready

### After Execution
1. Verify application functionality
2. Check data integrity
3. Update schema documentation
4. Keep migration script for records

## Common Scenarios

### Scenario 1: New Module Added
**Report Shows**: 3 new tables, 0 modifications
**Action**: Safe to run all CREATE TABLE statements

### Scenario 2: Column Type Changed
**Report Shows**: 
- Modified column: `userId` - Type: VARCHAR(50) → INT(11)

**Action**: ⚠️ Review data - may need conversion script first!

### Scenario 3: NOT NULL Added to Existing Column
**Report Shows**:
- Modified column: `email` - Null: YES → NO

**Action**: ⚠️ Must ensure no NULL values exist before altering!

### Scenario 4: Table Deleted in DEV
**Report Shows**: 1 deleted table (commented SQL)
**Action**: ⚠️ Decide if table should be dropped or DEV is outdated

## Troubleshooting

### Connection Errors
```
Connection failed to Production: Access denied
```
**Fix**: Check credentials in configuration

### Permission Errors
```
SELECT command denied to user 'user'@'host' for table 'tablename'
```
**Fix**: Ensure database user has SELECT, SHOW privileges

### Different Collations
If DEV and PROD use different collations, you may see many false positives.
**Fix**: Standardize database collations or filter collation differences in code

## Security Notes

⚠️ **IMPORTANT**:
- Never commit production credentials to git
- Disable tool in production environment (set `$maintenanceMode = false`)
- Use read-only credentials when possible
- Delete or protect this file after deployment

## Support

For issues or questions:
1. Check the generated SQL comments
2. Review GEMS2 schema documentation
3. Test changes on local/staging first
4. Contact dev team before running on production

## Files

- `schema_compare.php` - Main comparison tool
- `schema_compare_config.example.php` - Configuration template
- `SCHEMA_COMPARE_README.md` - This file

---

**Generated**: November 2025  
**Version**: 1.0  
**Project**: GEMS2
