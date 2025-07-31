# Work Order Import System Documentation

## Overview

The Work Order Import System allows users to import completed work orders from external systems into the GEMS application. This feature enables centralization of work order data while maintaining the complex workflow and status tracking of the internal system.

## Features

- **File Format Support**: CSV, XLS, XLSX files
- **Template Download**: Pre-formatted template with sample data
- **Validation**: Real-time validation before import
- **Preview**: Preview of data before execution
- **Batch Processing**: Handles large imports with progress tracking
- **Error Handling**: Detailed error reporting and skipped row tracking
- **Import History**: Track all import attempts with results

## System Architecture

### Database Tables

1. **wo_import_batch** - Tracks import batches
2. **wo_import_log** - Logs individual row import results
3. **wo_task** - Extended with import-specific columns:
   - `wo_task_external_ref` - External system reference
   - `wo_task_is_imported` - Flag for imported work orders

### Core Files

1. **API Layer**:
   - `api/wo_import.php` - Main import API endpoint
   - `api/class/WoImportFileParser.php` - File parsing utility

2. **UI Layer**:
   - `wo_import.html` - Import interface
   - `wo_import_schema.sql` - Database schema

3. **Database**:
   - Import tracking tables
   - Extended wo_task table

## Import Template Format

### Required Columns

| Column | Description | Example |
|--------|-------------|---------|
| `external_wo_number` | Unique identifier from external system | EXT-WO-2024-001 |
| `description` | Work order description/complaint | Aircon not working |
| `location` | Location of work | Level 2, Office Block A |
| `wo_type` | Work order type (1-6) | 4 (Breakdown) |
| `severity` | Severity level (1-2) | 2 (Critical) |
| `assigned_to_email` | Technician email | tech@company.com |
| `created_date` | Date created | 2024-01-15 |
| `assigned_date` | Date assigned | 2024-01-15 |
| `completed_date` | Date completed | 2024-01-16 |
| `verified_date` | Date verified | 2024-01-16 |

### Optional Columns

| Column | Description | Example |
|--------|-------------|---------|
| `created_by_email` | Creator email | supervisor@company.com |
| `verified_by_email` | Verifier email | manager@company.com |
| `repair_description` | Repair work description | Replaced compressor |
| `longitude` | GPS longitude | 103.8198 |
| `latitude` | GPS latitude | 1.3521 |
| `rating` | Work order rating (1-5) | 5 |
| `asset_number` | Asset number | AC-001 |
| `zone_id` | Zone ID | 1 |

### Work Order Types

1. Client Complaint
2. Self Finding
3. Request
4. Breakdown
5. Defect
6. Public Complaint

### Severity Levels

1. Non-Critical
2. Critical

## API Endpoints

### GET /api/wo_import.php?action=get_template
Returns the import template structure and validation rules.

### POST /api/wo_import.php?action=validate_file
Validates uploaded file format and basic structure.
- **Parameters**: `import_file` (file)
- **Returns**: Validation result with file info

### POST /api/wo_import.php?action=preview_import
Previews import data with full validation.
- **Parameters**: `import_file` (file), `site_id` (string)
- **Returns**: Preview data with validation results

### POST /api/wo_import.php?action=execute_import
Executes the import process.
- **Parameters**: `import_file` (file), `site_id` (string), `import_options` (JSON)
- **Returns**: Import results and summary

### GET /api/wo_import.php?action=get_import_history
Returns import history for the current user.

## Validation Rules

### Data Validation

1. **Required Fields**: All required columns must have values
2. **Work Order Type**: Must be 1-6
3. **Severity**: Must be 1-2
4. **Email Validation**: Assigned technician must exist in system
5. **Date Sequence**: created ≤ assigned ≤ completed ≤ verified
6. **Unique References**: External WO numbers must be unique
7. **Rating**: Optional, must be 1-5 if provided

### File Validation

1. **Format**: CSV, XLS, XLSX only
2. **Size**: Maximum 10MB
3. **Structure**: Must contain required columns
4. **Data**: At least one valid data row

## Import Process

### Step 1: Template & Site Selection
- Download template or view sample data
- Select target site for imported work orders

### Step 2: File Upload
- Upload CSV/Excel file
- Drag-and-drop or browse interface
- Basic file validation

### Step 3: Preview & Validation
- Full data validation
- Preview of valid/invalid rows
- Error and warning display
- Approval to proceed

### Step 4: Import Execution
- Batch processing with transaction support
- Individual row error handling
- Progress tracking and logging
- Final results summary

## Database Integration

### Work Order Creation

Imported work orders are created directly in **completed status (16)** with all timestamps:

```sql
INSERT INTO wo_task (
    wo_task_no,
    wo_task_type,
    wo_task_location,
    wo_task_complaint,
    wo_task_severity,
    wo_task_assigned_to,
    wo_task_time_created,
    wo_task_time_assigned,
    wo_task_time_executed,
    wo_task_time_verified,
    wo_task_status,
    wo_task_external_ref,
    wo_task_is_imported,
    -- ... other fields
) VALUES (
    'WO...',  -- Generated WO number
    4,        -- Work order type
    'Location',
    'Description',
    2,        -- Severity
    123,      -- User ID
    '2024-01-15 08:00:00',
    '2024-01-15 09:00:00',
    '2024-01-16 15:00:00',
    '2024-01-16 16:00:00',
    16,       -- Completed status
    'EXT-WO-001',
    1         -- Imported flag
);
```

### Gamification Integration

Imported work orders participate in the gamification system:
- Count towards completion metrics
- Contribute to productivity scores
- Included in weekly/monthly reports
- Proper date attribution for performance tracking

## Error Handling

### File Level Errors
- Invalid file format
- File too large
- Corrupted files
- Missing required columns

### Row Level Errors
- Missing required data
- Invalid data formats
- Non-existent users
- Date sequence violations
- Duplicate external references

### System Level Errors
- Database connection issues
- Transaction failures
- Permission errors
- Disk space issues

## Security Considerations

1. **File Upload Security**:
   - MIME type validation
   - File size limits
   - Extension whitelisting
   - Temporary file handling

2. **Data Validation**:
   - SQL injection prevention
   - XSS protection
   - Input sanitization
   - Business rule validation

3. **Access Control**:
   - JWT authentication
   - Site-based permissions
   - Role-based access
   - Audit logging

## Performance Optimization

1. **Batch Processing**: Import in configurable batch sizes
2. **Transaction Management**: Rollback on failures
3. **Memory Management**: Stream large files
4. **Database Optimization**: Proper indexing and queries

## Installation Steps

1. **Database Setup**:
   ```sql
   -- Run the schema file
   SOURCE wo_import_schema.sql;
   ```

2. **File Deployment**:
   - Copy API files to `api/` directory
   - Copy HTML file to root directory
   - Set proper file permissions

3. **Configuration**:
   - Update database connection settings
   - Configure file upload limits
   - Set import batch sizes

4. **Optional - Excel Support**:
   ```bash
   # Install PhpSpreadsheet via Composer
   composer require phpoffice/phpspreadsheet
   ```

## Usage Guidelines

### For System Administrators

1. **Regular Monitoring**:
   - Check import success rates
   - Monitor disk space usage
   - Review error logs
   - Validate data integrity

2. **Maintenance Tasks**:
   - Clean up old import logs
   - Archive completed batches
   - Update validation rules
   - Performance tuning

### For End Users

1. **Data Preparation**:
   - Use provided template
   - Validate data in external system
   - Ensure user accounts exist
   - Check date consistency

2. **Import Best Practices**:
   - Import in smaller batches
   - Review preview carefully
   - Monitor import progress
   - Verify imported data

## Troubleshooting

### Common Issues

1. **Excel Files Not Supported**:
   - Install PhpSpreadsheet library
   - Or convert to CSV format

2. **User Not Found Errors**:
   - Verify email addresses exist in system
   - Check site assignments
   - Update user data first

3. **Date Format Issues**:
   - Use YYYY-MM-DD format
   - Ensure date sequence is logical
   - Check timezone considerations

4. **Large File Import Failures**:
   - Increase PHP memory limits
   - Split into smaller files
   - Check database timeouts

### Log Analysis

Import logs are stored in:
- `wo_import_batch` - Batch level information
- `wo_import_log` - Row level details
- Application logs - System errors

## Future Enhancements

1. **Real-time Import**: WebSocket-based progress updates
2. **Template Builder**: Dynamic template generation
3. **Data Mapping**: Column mapping interface
4. **Integration API**: Direct API integration with external systems
5. **Scheduled Imports**: Automated periodic imports
6. **Advanced Validation**: Custom validation rules
7. **Reporting Dashboard**: Import analytics and trends

## Support

For technical support or feature requests:
1. Check troubleshooting section
2. Review import logs
3. Contact system administrator
4. Reference API documentation
