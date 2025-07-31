# Developer Tools ### Test Files (test/) - 17 files
Contains test cases, sample data, and testing utilities:

**Work Order Testing:**
- `test_wo_import_20_tickets.csv` - Test data for work order import (20 tickets)
- `test_api_site.html` - Site API testing interface
- `test_api.html` - General API testing utilities

**PPM Testing:**
- `test_ppm_import_20_tasks.csv` - Test data for PPM import (20 tasks)les

This folder contains development, debugging, and testing files that should **NOT** be deployed to production.

## Folder Structure

### `/debug/` - Debugging Scripts
Contains debugging utilities and diagnostic scripts:
- `debug_data_check.php` - Data validation debugging
- `debug_existing_data.php` - Existing data debugging  
- `debug_gamification_detailed.php` - Gamification system debugging
- `debug_runmonthly.php` - Monthly process debugging
- `debug_site_lookup.php` - Site lookup debugging
- `debug_test.php` - General debugging tests
- `debug_trade_ratios.php` - Trade ratio debugging
- `debug_gamification_config.php` - API gamification debugging
- `debug_trade_ratios_api.php` - API trade ratio debugging

### `/test/` - Testing Files
Contains test scripts and testing interfaces:
- `test_api.html` & `test_api_site.html` - API testing pages
- `test_login.html` - Login testing
- `test_map.html` - Map functionality testing
- `test_runmonthly_duplicate.php` - Monthly duplicate testing
- `test_simple.php` - Simple functionality testing
- `test_site_groups.php` - Site groups testing
- `test_trade_ratio.html` - Trade ratio testing
- `test_weekly_gamification.php` - Weekly gamification testing
- `test_weekly_system.php` - Weekly system testing
- Various API testing files (`test_*.php`)

### `/validation/` - Validation Utilities
Contains quick validation and utility scripts:
- `quick_validate.php` - Quick import validation script (simple HTML output)
- `check_weights.php` - Weight checking utility

## Production Deployment

**Important**: This entire `developer/` folder should be **excluded** from production deployments.

### Production Files (Keep in main directory):
- `validate_import.html` - Production validation dashboard
- `wo_import.html` - Work order import interface  
- `ppm_import.html` - PPM import interface
- `gamification.html` - Main gamification interface
- All other main application `.html` files

### New Import Systems (Production Ready):

**Work Order Import:** `wo_import.html` + `api/wo_import.php`
- 23-column customer template format
- 4-step wizard interface with comprehensive validation
- Supports bulk import with error handling and progress tracking

**PPM Import:** `ppm_import.html` + `api/ppm_import.php`
- 28-column PPM template format covering task details, asset info, scheduling, workflow execution
- Same 4-step wizard concept with PPM-specific validation rules
- Integration with existing PPM workflow system and asset management

Both systems follow the same proven pattern:
1. File Selection & Template Download
2. File Upload & Processing
3. Preview & Validation  
4. Import Results & History

### Deployment Script Example:
```bash
# Exclude developer folder when deploying
rsync -av --exclude='developer/' /local/gems2/ user@production:/var/www/gems2/
```

## Usage

These files are for development purposes only:
- Use for debugging issues
- Testing new features
- Quick validation checks
- Performance analysis

For production validation, users should use `validate_import.html` instead of the utilities in this folder.
