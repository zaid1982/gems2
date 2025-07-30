# Fix for runMonthly Duplicate Record Issue

## Problem Identified
When running SYNC DATA (which triggers `runMonthly`), the system was creating duplicate records instead of updating existing ones for the same user/year/month/week combination.

## Root Cause Analysis

### Weekly Data Issue (`storeWeeklyData` function)
The function was checking for existing records using only:
- `user_id`
- `gmw_year` 
- `gmw_week` (calculated as ISO week of year)

**Missing:** `gmw_month` in the WHERE condition

This caused issues when:
- Same user had records in different months but same ISO week number
- System couldn't distinguish between weeks from different months
- Led to either wrong updates or duplicate insertions

### Monthly Data Issue (monthly aggregation)
The monthly aggregation was always setting `gmiId = 0` for new entries, which meant:
- Always inserting new records instead of checking for existing ones
- No attempt to retrieve existing monthly record IDs
- Potential for duplicate monthly records

## Solution Implemented

### 1. Fixed Weekly Data Storage
**File:** `api/function/f_gamification.php` - `storeWeeklyData()` function

**Before:**
```php
$existingRecord = Class_db::getInstance()->db_select_single2('gmi_weekly', array(
    'user_id' => strval($gmi['userId']),
    'gmw_year' => strval($year),
    'gmw_week' => strval($yearWeek)
));
```

**After:**
```php
$existingRecord = Class_db::getInstance()->db_select_single2('gmi_weekly', array(
    'user_id' => strval($gmi['userId']),
    'gmw_year' => strval($year),
    'gmw_month' => strval($month),  // ✅ ADDED
    'gmw_week' => strval($yearWeek)
));
```

**UPDATE query also fixed to include month condition:**
```php
Class_db::getInstance()->db_update('gmi_weekly', $weeklyData, array(
    'user_id' => strval($gmi['userId']),
    'gmw_year' => strval($year),
    'gmw_month' => strval($month),  // ✅ ADDED
    'gmw_week' => strval($yearWeek)
));
```

### 2. Fixed Monthly Data Storage
**File:** `api/function/f_gamification.php` - monthly aggregation in `runMonthly()`

**Before:**
```php
if (!array_key_exists($userId, $gmiMonthlyAggregated)) {
    $gmiMonthlyAggregated[$userId] = $this->setInitialGmiMonthArr($userId, $year, $month, $weeklyData['siteId'], 0);
}
```

**After:**
```php
if (!array_key_exists($userId, $gmiMonthlyAggregated)) {
    // ✅ Check if monthly record already exists
    $existingMonthlyRecord = Class_db::getInstance()->db_select_single2('gmi_monthly', array(
        'user_id' => strval($userId),
        'gmi_year' => strval($year),
        'gmi_month' => strval($month)
    ));
    
    $existingGmiId = !empty($existingMonthlyRecord) ? $existingMonthlyRecord['gmiId'] : 0;
    $gmiMonthlyAggregated[$userId] = $this->setInitialGmiMonthArr($userId, $year, $month, $weeklyData['siteId'], $existingGmiId);
}
```

## Key Benefits

1. **Prevents Duplicate Weekly Records:** Proper identification using user_id + year + month + week
2. **Prevents Duplicate Monthly Records:** Retrieves existing gmiId for updates instead of always inserting
3. **Maintains Data Integrity:** Updates existing records instead of creating duplicates
4. **SYNC DATA Reliability:** Multiple runs of SYNC DATA won't create duplicate entries
5. **Performance Improvement:** Fewer unnecessary database records

## Testing
- Created `test_runmonthly_duplicate.php` to verify the fix
- Tests running `runMonthly` twice and confirms no duplicates are created
- Validates that record counts remain stable on subsequent runs

## Impact
This fix ensures that the gamification system maintains clean, accurate data without duplicates, making SYNC DATA operations reliable and repeatable.
