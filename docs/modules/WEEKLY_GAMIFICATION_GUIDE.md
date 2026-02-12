# Weekly Gamification System - Implementation Guide

## Overview

The gamification system has been updated to calculate scores on a **weekly basis within each month**, with the final monthly score being the **cumulative sum of all weekly scores** in that month.

## Key Changes

### Previous System (Monthly Aggregation)
- Calculated scores once per month based on monthly totals
- Applied tier multipliers based on monthly performance
- Single calculation per user per month

### New System (Weekly Calculations with Monthly Cumulation)
- Calculates scores **weekly** within each month (Week 1, Week 2, Week 3, Week 4/5)
- Each week's score is calculated independently with its own MBV and tier multipliers
- **Monthly score = Sum of all weekly scores in that month**
- Stores both weekly detail data and monthly aggregated data

## Technical Implementation

### Database Changes

#### New Table: `gmi_weekly`
Stores individual weekly calculation results:
- `user_id`, `gmi_year`, `gmi_month`, `gmi_week`
- All PPM and WO metrics for the week
- Weekly point calculations
- Weekly MBV and tier information

#### New Views: Daily Data Views
- `vw_gamification_ppm_daily` - PPM tasks by date range
- `vw_gamification_ppm_assist_daily` - PPM assistance by date range  
- `vw_gamification_wo_daily` - Work orders by date range
- `vw_gamification_wo_assist_daily` - WO assistance by date range

### Code Changes

#### Modified `runMonthly()` Function
```php
public function runMonthly($year, $month) {
    // 1. Get number of weeks in month (4-5 weeks)
    // 2. For each week:
    //    - Calculate weekly date range
    //    - Get weekly task data
    //    - Calculate weekly scores with weekly MBV/tier
    //    - Store in gmi_weekly table
    // 3. Aggregate all weekly scores into monthly totals
    // 4. Store monthly aggregated data in gmi_monthly table
}
```

#### New Helper Methods
- `getWeeksInMonth($year, $month)` - Calculate weeks in month
- `getWeekDateRange($year, $month, $week)` - Get date range for specific week
- `calculateWeeklyScores()` - Core weekly calculation logic
- `setInitialGmiWeekArr()` - Initialize weekly data structure
- `storeWeeklyData()` - Save weekly results to database

## Calculation Logic

### Weekly Score Calculation
For each week (Monday-Sunday within the month):

1. **Gather Weekly Data**: PPM tasks, WO tasks, assists completed in that week
2. **Calculate Weekly MBV**: `(OnTime + Within) - Late` for the week
3. **Determine Weekly Tier Multiplier**:
   - MBV ≤ 50: 1x multiplier
   - 51 ≤ MBV ≤ 100: 3x multiplier  
   - MBV > 100: 5x multiplier
4. **Calculate Weekly Points**:
   - Completion points: `(completed/total) × 0.3 × 10000`
   - On-time points: `(within/total) × tier_multiplier × 0.7 × 10000`
   - Late penalty: `-(late/completed) × tier_multiplier × 0.15 × 10000`
   - Self-finding bonus: `self_finding_count × 5`
5. **Weekly Total** = Sum of all weekly point components

### Monthly Aggregation
```
Monthly Score = Week1_Score + Week2_Score + Week3_Score + Week4_Score + [Week5_Score]
```

## Usage Examples

### Running Weekly Calculations
```php
$gamification = new Class_gamification();
$gamification->runMonthly(2025, 1); // Calculate January 2025
```

This will:
1. Calculate Week 1 (Jan 1-7) scores
2. Calculate Week 2 (Jan 8-14) scores  
3. Calculate Week 3 (Jan 15-21) scores
4. Calculate Week 4 (Jan 22-28) scores
5. Calculate Week 5 (Jan 29-31) scores if applicable
6. Sum all weekly scores for final monthly score

### Querying Weekly Data
```sql
-- Get all weekly scores for a user in January 2025
SELECT * FROM gmi_weekly 
WHERE user_id = 123 AND gmi_year = 2025 AND gmi_month = 1 
ORDER BY gmi_week;

-- Get monthly totals from weekly aggregation
SELECT * FROM vw_gmi_monthly_from_weekly 
WHERE user_id = 123 AND gmi_year = 2025 AND gmi_month = 1;
```

## Configuration

All existing configuration parameters remain the same:
- `mbv_tier1_threshold` (50)
- `mbv_tier2_threshold` (100)  
- `mbv_tier1_multiplier` (1x)
- `mbv_tier2_multiplier` (3x)
- `mbv_tier3_multiplier` (5x)
- Point weights and scale factors

New optional configuration:
- `weekly_calculation_enabled` (1) - Enable/disable weekly mode

## Benefits

1. **More Responsive**: Weekly feedback instead of waiting for month-end
2. **Fairer Scoring**: Performance fluctuations within month are captured
3. **Better Motivation**: Users can see progress each week
4. **Detailed Analytics**: Week-by-week performance tracking
5. **Flexibility**: Can still view monthly aggregates for reporting

## Migration Notes

### Database Setup
1. Run `gmi_weekly_setup.sql` to create new tables and views
2. Update existing views to support date range parameters
3. Test with sample data before production deployment

### Backward Compatibility
- Monthly scores are still calculated and stored in `gmi_monthly`
- Existing APIs and reports should continue to work
- Weekly data provides additional granular insights

### Performance Considerations
- Weekly calculations require more database operations
- Consider running calculations during off-peak hours
- Index optimization on date fields for better query performance

## Troubleshooting

### Common Issues
1. **Missing Weekly Views**: Ensure daily views are created with proper date filtering
2. **Date Range Errors**: Verify week calculation logic handles month boundaries correctly
3. **Performance**: Monitor query execution time with large datasets

### Debugging
- Enable debug logging in `f_gamification.php`
- Check `gmi_weekly` table for stored weekly results
- Verify date ranges with `getWeekDateRange()` function

## Future Enhancements

1. **Real-time Updates**: Update weekly scores as tasks are completed
2. **Weekly Reports**: Dashboard showing weekly progress
3. **Weekly Competitions**: Week-based leaderboards and competitions
4. **Trend Analysis**: Week-over-week performance tracking
