# 🚀 Weekly Gamification System - Deployment Guide

## Overview
This deployment guide covers the implementation of the weekly gamification system where monthly scores are calculated as cumulative sums of weekly scores. The system maintains backward compatibility while introducing enhanced weekly granularity.

---

## 📋 Pre-Deployment Checklist

### 1. **Environment Requirements**
- [ ] PHP 7.0+ with MySQL support
- [ ] MySQL/MariaDB database access
- [ ] Backup permissions for database
- [ ] Web server write permissions for logs

### 2. **Database Backup**
```sql
-- Create backup tables before deployment
CREATE TABLE gmi_monthly_backup_$(date +%Y%m%d) AS SELECT * FROM gmi_monthly;
CREATE TABLE gmi_config_backup_$(date +%Y%m%d) AS SELECT * FROM gmi_config;

-- Verify backups
SELECT COUNT(*) FROM gmi_monthly_backup_$(date +%Y%m%d);
SELECT COUNT(*) FROM gmi_config_backup_$(date +%Y%m%d);
```

---

## 🗄️ Database Migration

### Step 1: Create Weekly Table
Execute the following SQL to create the `gmi_weekly` table:

```sql
-- Deploy gmi_weekly table structure
CREATE TABLE `gmi_weekly` (
  `gmw_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `gmw_year` int(4) NOT NULL,
  `gmw_week` int(2) NOT NULL,
  `gmw_ppm_tier_name` varchar(50) DEFAULT 'Under Rated',
  `gmw_ppm_tier_point` decimal(3,1) DEFAULT '0.5',
  `gmw_ppm_total` int(11) DEFAULT '0',
  `gmw_ppm_completed` int(11) DEFAULT '0',
  `gmw_ppm_on_time` int(11) DEFAULT '0',
  `gmw_ppm_late` int(11) DEFAULT '0',
  `gmw_ppm_within` int(11) DEFAULT '0',
  `gmw_ppm_assist` int(11) DEFAULT '0',
  `gmw_wo_tier_name` varchar(50) DEFAULT 'Under Rated',
  `gmw_wo_tier_point` decimal(3,1) DEFAULT '0.5',
  `gmw_wo_total` int(11) DEFAULT '0',
  `gmw_wo_completed` int(11) DEFAULT '0',
  `gmw_wo_on_time` int(11) DEFAULT '0',
  `gmw_wo_late` int(11) DEFAULT '0',
  `gmw_wo_rework` int(11) DEFAULT '0',
  `gmw_wo_self_finding` int(11) DEFAULT '0',
  `gmw_wo_assist` int(11) DEFAULT '0',
  `gmw_mbv` int(11) DEFAULT '0',
  `gmw_tier_point` tinyint(1) DEFAULT '1',
  `gmw_point_completed` decimal(15,2) DEFAULT '0.00',
  `gmw_point_on_time` decimal(15,2) DEFAULT '0.00',
  `gmw_point_late` decimal(15,2) DEFAULT '0.00',
  `gmw_point_rework` decimal(15,2) DEFAULT '0.00',
  `gmw_point_self_finding` decimal(15,2) DEFAULT '0.00',
  `gmw_point_total` decimal(15,2) DEFAULT '0.00',
  `gmw_productivity_level` decimal(5,2) DEFAULT '0.00',
  `gmw_productivity_deduction` decimal(5,2) DEFAULT '0.00',
  `gmw_point_less_productive` decimal(15,2) DEFAULT '0.00',
  `gmw_point_before_minus` decimal(15,2) DEFAULT '0.00',
  `gmw_point_after_minus` decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY (`gmw_id`),
  UNIQUE KEY `unique_user_week` (`user_id`,`gmw_year`,`gmw_week`),
  KEY `idx_year_week` (`gmw_year`,`gmw_week`),
  KEY `idx_user_year` (`user_id`,`gmw_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**Verification:**
```sql
-- Verify table creation
DESCRIBE gmi_weekly;
SELECT COUNT(*) as table_exists FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'gmi_weekly';
```

### Step 2: Setup Configuration Table
```sql
-- Create configuration table if not exists
CREATE TABLE IF NOT EXISTS `gmi_config` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text NOT NULL,
  `data_type` enum('string','int','float','boolean') DEFAULT 'string',
  `description` text,
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert default configuration values
INSERT INTO `gmi_config` (`config_key`, `config_value`, `data_type`, `description`) VALUES
('tier_medalist_threshold', '150', 'int', 'Minimum completed tasks for Medalist tier'),
('tier_finisher_threshold', '80', 'int', 'Minimum completed tasks for Finisher tier'),
('mbv_tier1_threshold', '50', 'int', 'MBV threshold for tier 1 multiplier'),
('mbv_tier2_threshold', '100', 'int', 'MBV threshold for tier 2 multiplier'),
('mbv_tier1_multiplier', '1', 'int', 'Point multiplier for tier 1 (MBV ≤ 50)'),
('mbv_tier2_multiplier', '3', 'int', 'Point multiplier for tier 2 (51 ≤ MBV ≤ 100)'),
('mbv_tier3_multiplier', '5', 'int', 'Point multiplier for tier 3 (MBV > 100)'),
('weight_completed', '0.3', 'float', 'Weight for completion points (0-1)'),
('weight_ontime', '0.7', 'float', 'Weight for on-time points (0-1)'),
('weight_late_penalty', '0.15', 'float', 'Weight for late penalty (0-1)'),
('self_finding_points', '5', 'int', 'Points per self-finding work order'),
('point_scale_factor', '10000', 'int', 'Scaling factor for all calculations'),
('productivity_base', '90', 'int', 'Base productivity percentage'),
('wo_ontime_multiplier', '2', 'int', 'Work order on-time multiplier')
ON DUPLICATE KEY UPDATE 
  `config_value` = VALUES(`config_value`),
  `updated_at` = CURRENT_TIMESTAMP;
```

**Verification:**
```sql
-- Verify configuration data
SELECT config_key, config_value, data_type FROM gmi_config WHERE status = 1;
```

---

## 📁 File Deployment

### Core Backend Files
Deploy these files to your production server:

```bash
# Core gamification logic
/api/function/f_gamification.php

# SQL view definitions  
/api/library/sql.php

# API endpoints
/api/gamification.php
/api/gamification_config.php

# Frontend files
/js/pages/main_gamification.js
/gamification.html
/gamification_config.html
```

### File Permissions
```bash
# Set appropriate permissions
chmod 644 /path/to/api/function/f_gamification.php
chmod 644 /path/to/api/library/sql.php
chmod 644 /path/to/api/gamification.php
chmod 644 /path/to/api/gamification_config.php
chmod 644 /path/to/js/pages/main_gamification.js
chmod 644 /path/to/gamification.html
chmod 644 /path/to/gamification_config.html
```

---

## 🧪 Testing & Verification

### Phase 1: Configuration Testing
1. **Access Configuration Panel**
   ```
   URL: http://your-domain/gamification_config.html
   ✓ Page loads without errors
   ✓ Default values display correctly
   ✓ Save/Reset buttons functional
   ```

2. **Test Configuration API**
   ```bash
   # Test GET configuration
   curl -X GET "http://your-domain/api/gamification_config.php?action=get_all"
   
   # Test POST configuration
   curl -X POST "http://your-domain/api/gamification_config.php" \
        -d "action=save_all&config={\"tier_medalist_threshold\":150}"
   ```

### Phase 2: Weekly Calculation Testing
1. **Access Gamification Dashboard**
   ```
   URL: http://your-domain/gamification.html
   ✓ Page loads successfully
   ✓ Weekly/Monthly toggle works
   ✓ SYNC DATA button visible
   ```

2. **Test Weekly Calculation**
   ```javascript
   // Open browser console and run:
   console.log('Testing weekly calculation...');
   
   // Click SYNC DATA button in Weekly mode
   // Check for errors in console
   // Verify success message appears
   ```

3. **Database Verification**
   ```sql
   -- Check if weekly data was created
   SELECT 
       gmw_year, 
       gmw_week, 
       COUNT(*) as user_count,
       SUM(gmw_point_total) as total_points
   FROM gmi_weekly 
   WHERE gmw_year = YEAR(CURDATE()) 
   GROUP BY gmw_year, gmw_week 
   ORDER BY gmw_year DESC, gmw_week DESC 
   LIMIT 5;
   
   -- Check monthly aggregation
   SELECT 
       gmi_year, 
       gmi_month, 
       COUNT(*) as user_count,
       SUM(gmi_point_total) as total_points
   FROM gmi_monthly 
   WHERE gmi_year = YEAR(CURDATE()) 
   GROUP BY gmi_year, gmi_month 
   ORDER BY gmi_year DESC, gmi_month DESC 
   LIMIT 3;
   ```

### Phase 3: Data Integrity Testing
1. **Verify Weekly-Monthly Relationship**
   ```sql
   -- Compare weekly sum vs monthly total for current month
   SELECT 
       w.user_id,
       SUM(w.gmw_point_total) as weekly_sum,
       m.gmi_point_total as monthly_total,
       (SUM(w.gmw_point_total) - m.gmi_point_total) as difference
   FROM gmi_weekly w
   LEFT JOIN gmi_monthly m ON m.user_id = w.user_id 
       AND m.gmi_year = w.gmw_year 
       AND m.gmi_month = MONTH(CURDATE())
   WHERE w.gmw_year = YEAR(CURDATE())
   GROUP BY w.user_id, m.gmi_point_total
   HAVING ABS(difference) > 0.01  -- Check for discrepancies
   LIMIT 10;
   ```

2. **Performance Testing**
   ```sql
   -- Time the weekly calculation
   SET @start_time = NOW(3);
   -- Run SYNC DATA operation
   SET @end_time = NOW(3);
   SELECT TIMEDIFF(@end_time, @start_time) as execution_time;
   ```

### Phase 4: User Experience Testing
1. **Functionality Checklist**
   ```
   ✓ SYNC DATA button shows loading state
   ✓ Success/error messages display properly
   ✓ Weekly mode calculations complete without timeout
   ✓ Monthly view aggregates correctly
   ✓ Configuration changes take effect immediately
   ```

2. **Cross-Browser Testing**
   ```
   ✓ Chrome/Edge (latest)
   ✓ Firefox (latest) 
   ✓ Safari (if applicable)
   ✓ Mobile browsers (responsive)
   ```

---

## 🔍 Post-Deployment Monitoring

### Key Metrics to Monitor
1. **Performance Metrics**
   - Weekly calculation execution time
   - Database query performance
   - Memory usage during aggregation
   - User response times

2. **Error Monitoring**
   ```bash
   # Monitor PHP error logs
   tail -f /var/log/php/error.log | grep gamification
   
   # Monitor MySQL slow query log
   tail -f /var/log/mysql/slow.log
   ```

3. **Database Growth**
   ```sql
   -- Monitor table sizes
   SELECT 
       table_name,
       ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
   FROM information_schema.tables 
   WHERE table_schema = DATABASE() 
   AND table_name IN ('gmi_weekly', 'gmi_monthly', 'gmi_config')
   ORDER BY size_mb DESC;
   ```

---

## 🚨 Troubleshooting

### Common Issues and Solutions

1. **SQL View Errors**
   ```
   Error: "Sql not exist : vw_gamification_ppm_daily"
   Solution: Verify sql.php file deployed correctly with new views
   ```

2. **String Conversion Errors**
   ```
   Error: "Non-string value passed to get_whereAnd_str"
   Solution: Check f_gamification.php has strval() conversions
   ```

3. **Date Range Issues**
   ```
   Error: SQL syntax errors with date parameters
   Solution: Verify views use [dateStart] and [dateEnd] parameters
   ```

4. **Performance Issues**
   ```
   Issue: Slow weekly calculations
   Solution: Check database indexes on date columns
   ```

### Emergency Rollback
```sql
-- Quick rollback procedure
-- 1. Backup current state
CREATE TABLE gmi_monthly_current AS SELECT * FROM gmi_monthly;

-- 2. Restore from backup
DROP TABLE gmi_monthly;
RENAME TABLE gmi_monthly_backup_YYYYMMDD TO gmi_monthly;

-- 3. Remove weekly table if needed
DROP TABLE gmi_weekly;

-- 4. Deploy previous PHP files
```

---

## 📊 Success Criteria

### Deployment Success Indicators
- [ ] All database tables created successfully
- [ ] Configuration values loaded correctly
- [ ] Weekly calculations execute without errors
- [ ] Monthly aggregation matches weekly sums
- [ ] UI responds within acceptable time limits
- [ ] No critical errors in logs
- [ ] User feedback is positive

### Performance Benchmarks
- Weekly calculation: < 30 seconds for typical dataset
- Page load time: < 3 seconds
- Database queries: < 2 seconds average
- Memory usage: Within server limits

---

## 📞 Support Information

### Key Contacts
- **Developer**: [Your Development Team]
- **Database Admin**: [DBA Contact]
- **System Admin**: [SysAdmin Contact]

### Documentation References
- Database schema: `gmi_weekly` table structure
- API documentation: `gamification.php` endpoints
- Configuration guide: `gamification_config.html` usage

### Escalation Path
1. Check error logs and troubleshooting section
2. Contact development team for code issues
3. Contact DBA for database performance issues
4. Contact system admin for infrastructure issues

---

**Deployment Date**: `[To be filled during deployment]`  
**Deployed By**: `[To be filled during deployment]`  
**Version**: `Weekly Gamification System v2.0`  
**Branch**: `gamification-v2`

---

*This document should be updated with actual deployment details and kept as a reference for future maintenance and troubleshooting.*
