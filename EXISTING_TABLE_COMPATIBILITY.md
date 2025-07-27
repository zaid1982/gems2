# Weekly Gamification System - Compatibility Guide for Existing Table

## ✅ **Great News!** Your existing `gmi_weekly` table will work perfectly!

Your current table structure is compatible with the new weekly gamification system. Here's how it maps:

## Table Structure Compatibility

### Your Existing Table: `gmi_weekly`
```sql
gmw_id                   -> Primary key (auto increment)
user_id                  -> ✅ User identifier
site_id                  -> ✅ Site identifier  
gmw_year                 -> ✅ Year (we use this instead of separate month)
gmw_week                 -> ✅ Week number (1-53 in year)
gmw_ppm_*               -> ✅ All PPM fields supported
gmw_wo_*                -> ✅ All WO fields supported  
gmw_point_*             -> ✅ All point calculation fields
gmw_mbv                 -> ✅ Merit-Based Value
gmw_tier_point          -> ✅ Tier multiplier
gmw_productivity_*      -> ✅ Productivity calculations
gmw_wo_rework           -> ✅ Additional field (bonus!)
gmw_point_rework        -> ✅ Rework points (bonus!)
```

## Key Adaptations Made

### 1. **Week Numbering System**
- **Your system**: Uses `gmw_year` + `gmw_week` (ISO week 1-53)
- **Adaptation**: We convert month-based weeks to year-based weeks automatically
- **Benefits**: More precise, handles year boundaries correctly

### 2. **Field Name Mapping**
The PHP code automatically maps between naming conventions:
```php
// Internal processing uses 'gmw_' prefix to match your table
$data['gmwPpmTotal'] = $weeklyPpmCount;
$data['gmwPointTotal'] = $calculatedPoints;
// etc.
```

### 3. **Additional Fields Support**
Your table has extra fields we don't use yet but preserve:
- `gmw_wo_rework` - For future rework tracking
- `gmw_point_rework` - For rework point penalties

## How It Works

### Weekly Calculation Process
```
1. Month Input: runMonthly(2025, 1)  // January 2025
2. Week Breakdown: 
   - Week 1: Jan 1-7   -> ISO Week 1
   - Week 2: Jan 8-14  -> ISO Week 2  
   - Week 3: Jan 15-21 -> ISO Week 3
   - Week 4: Jan 22-28 -> ISO Week 4
   - Week 5: Jan 29-31 -> ISO Week 5 (if exists)
3. Store: Each week in gmi_weekly with gmw_year=2025, gmw_week=1,2,3,4,5
4. Aggregate: Sum all weeks for monthly total
```

### Database Operations
```php
// Insert/Update weekly record
$data = array(
    'userId' => 123,
    'siteId' => 5,
    'gmwYear' => 2025,
    'gmwWeek' => 3,          // ISO week number
    'gmwPpmTotal' => 10,
    'gmwPointTotal' => 1500,
    // ... all other fields
);

// Check existing record
$existing = db_select_single2('gmi_weekly', array(
    'user_id' => 123,
    'gmw_year' => 2025,
    'gmw_week' => 3
));
```

## Implementation Benefits

### ✅ **Advantages of Your Table Structure**
1. **No Month Column Needed**: ISO weeks handle cross-month scenarios
2. **Smaller Data Types**: `smallint`, `tinyint` save storage space
3. **Rework Support**: Ready for future rework penalty calculations
4. **Year-Week Precision**: More accurate than month-week approximations

### 🔧 **What We Modified**
1. **Week Calculation**: Convert month-weeks to ISO weeks
2. **Field Mapping**: Use `gmw_` prefix throughout calculation engine
3. **Data Types**: Respect your smaller integer types
4. **Query Structure**: Adapted for `gmw_year` + `gmw_week` lookups

## Usage Examples

### Running Calculations
```php
$gamification = new Class_gamification();
$gamification->runMonthly(2025, 1); // Calculates all weeks in January 2025
```

### Query Weekly Data
```sql
-- Get all weekly scores for user 123 in 2025
SELECT * FROM gmi_weekly 
WHERE user_id = 123 AND gmw_year = 2025 
ORDER BY gmw_week;

-- Get January 2025 weeks (approximately weeks 1-5)
SELECT * FROM gmi_weekly 
WHERE user_id = 123 AND gmw_year = 2025 AND gmw_week BETWEEN 1 AND 5;
```

### Monthly Aggregation View
```sql
-- See monthly totals derived from weekly data
SELECT * FROM vw_gmi_monthly_from_weekly 
WHERE user_id = 123 AND gmw_year = 2025;
```

## Setup Steps

### 1. **Run SQL Setup** (Optional Views Only)
```bash
mysql -u your_user -p your_database < gmi_weekly_setup.sql
```
This only creates views - your existing table is untouched!

### 2. **Test the System**
```bash
# Visit in browser:
http://localhost/gems2/test_weekly_gamification.php
```

### 3. **Customize Daily Views**
Update the views in the SQL file to match your actual table names:
- `ppm_task` -> Your actual PPM table
- `work_order` -> Your actual WO table
- `ppm_assist` -> Your actual PPM assist table
- `wo_assist` -> Your actual WO assist table

## Migration Notes

### ✅ **No Data Migration Needed**
- Your existing `gmi_weekly` table works as-is
- No structural changes required
- Existing data preserved

### ⚙️ **Only Need to Update**
1. Daily views to match your actual table structure
2. Field names in views if different from examples
3. Date/status column names to match your schema

## Troubleshooting

### Common Issues
1. **Missing Daily Views**: Update view definitions with correct table names
2. **Week Number Confusion**: Our system uses ISO weeks (1-53 per year)
3. **Data Type Mismatches**: We respect your smaller integer types

### Debug Steps
```php
// Check week calculation
$week = $gamification->calculateWeekOfYear(2025, 1, 3); // Week 3 of January
echo "ISO Week: $week"; // Should show 3

// Check data storage
$weeklyData = db_select2('gmi_weekly', array('gmw_year' => 2025, 'gmw_week' => 3));
print_r($weeklyData);
```

## Summary

🎉 **Your existing `gmi_weekly` table is perfect for the new weekly calculation system!**

The main advantages:
- ✅ No table structure changes needed
- ✅ More precise week handling with ISO weeks
- ✅ Support for rework calculations (future feature)
- ✅ Efficient storage with appropriate data types
- ✅ Clean separation of weekly vs monthly data

Just update the daily views to match your actual table structure and you're ready to go!
