# GEMS Development Files Organization

## ✅ Successfully Moved Files

### Total Files Moved: 28 files + 1 README

**Moved to `/developer/debug/` (10 files):**
- debug_data_check.php
- debug_existing_data.php  
- debug_gamification_config.php
- debug_gamification_detailed.php
- debug_runmonthly.php
- debug_site_lookup.php
- debug_test.php
- debug_trade_ratios_api.php
- debug_trade_ratios.php
- debug_trade_ratio.html

**Moved to `/developer/test/` (16 files):**
- test_api_site.html
- test_api.html
- test_async.php
- test_firebase.php
- test_login.html
- test_map.html
- test_oop.php
- test_runmonthly_duplicate.php
- test_simple.php
- test_site_groups_local.php
- test_site_groups.php
- test_trade_ratio.html
- test_trade_ratio.php
- test_weekly_gamification.php
- test_weekly_system.php

**Moved to `/developer/validation/` (2 files):**
- quick_validate.php (utility validation script)
- check_weights.php (weight checking utility)

## 📁 Production-Ready Structure

The main directory now contains only production files:
- All `.html` application pages (home.html, wo_import.html, etc.)
- All `api/` production endpoints
- Production assets (css/, js/, img/, etc.)
- Production validation dashboard (validate_import.html)

## 🚀 Deployment

### Quick Deploy (excluding developer files):
```bash
rsync -av --exclude='developer/' /path/to/gems2/ user@production:/var/www/gems2/
```

### Using the deployment script:
```bash
./deploy_production.sh
```

### Manual verification:
```bash
# Check no debug/test files in production
ls -la | grep -E "(debug|test)"  # Should return nothing
```

## 📝 Notes

1. **Developer folder** contains all debugging and testing tools
2. **Production deployment** should exclude the entire `developer/` folder
3. **Validation**: Use `validate_import.html` for production, not `quick_validate.php`
4. **Access**: Developer tools can still be accessed via `developer/validation/quick_validate.php` for debugging

## 🔒 Security

The developer folder should be:
- Excluded from production deployments
- Protected by web server configuration if accidentally deployed
- Used only in development/staging environments
