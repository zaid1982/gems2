# Work Order Import System - Flexible Template Support

## Overview
The GEMS 2.0 Work Order Import System now supports flexible template formats, allowing users to import work orders using their existing column names and data formats.

## User's Template Format Support

### Your Column Names → System Mapping
The system automatically maps your existing column names to GEMS fields:

| Your Column Name | Maps To | Description |
|------------------|---------|-------------|
| Date | created_date | Work order creation date |
| Request No. | external_wo_number | External work order reference |
| Work Order No. | external_wo_number | External work order reference |
| Complaint Description | description | **Work order title/description** |
| Location | location | Work location |
| Complaint Type | wo_type | Work order type |
| Complainant | created_by_name | Person who reported the issue |
| Severity | severity | Issue severity level |
| PIC Name | assigned_to_name | **Primary technician name** |
| Fixed By | assigned_to_name | Technician who fixed the issue |
| Assigned By | created_by_name | Person who assigned the work |
| Verified By | verified_by_name | Person who verified completion |
| Repair Description | repair_description | Detailed repair work done |
| Rating | rating | Work order rating (1-5) |
| Complaint Time | created_date | When issue was reported |
| Assigned Time | assigned_date | When work was assigned |
| Executed Time | completed_date | When work was completed |
| Verified Time | verified_date | When work was verified |

## Key Features

### 1. Name-Based User Identification
- **Use Names Instead of Emails**: The system looks up users by their full names
- **Automatic Resolution**: Names are automatically converted to email addresses for system processing
- **Flexible Matching**: Supports both full name and first/last name combinations

### 2. Text Value Support
The system accepts both numeric codes and text descriptions:

#### Work Order Types
- **Text**: "Client Complaint", "Breakdown", "Request", etc.
- **Numeric**: 1=Client Complaint, 2=Self Finding, 3=Request, 4=Breakdown, 5=Defect, 6=Public Complaint

#### Severity Levels
- **Text**: "Critical", "Non-Critical", "High", "Low", "Urgent"
- **Numeric**: 1=Non-Critical, 2=Critical

### 3. Backward Compatibility
- Existing GEMS column names still work
- Email-based user identification still supported
- Standard template format remains valid

## Sample Import Data

```csv
Date,Request No.,Location,Complaint Type,Complainant,Complaint Description,Severity,PIC Name,Repair Description,Rating,Complaint Time,Assigned Time,Executed Time,Verified Time
2024-01-15,REQ-2024-001,Level 2 Office Block A,Breakdown,John Doe,Aircon not working in office area,Critical,Mike Johnson,Replaced faulty compressor unit,5,2024-01-15 09:00:00,2024-01-15 09:30:00,2024-01-16 14:00:00,2024-01-16 16:00:00
```

## Import Process

### Step 1: Template & Site Selection
- Download the standard template OR use your existing format
- Select the target site for import
- The system supports CSV, XLS, and XLSX files

### Step 2: File Upload & Validation
- Upload your file with work order data
- System validates and maps columns automatically
- Preview shows processed data before import

### Step 3: Preview & Review
- Review mapped data and validation results
- Check for any missing users or validation errors
- Confirm data looks correct before proceeding

### Step 4: Import Execution
- System creates work orders with status 16 (Completed)
- Generates GEMS work order numbers
- Integrates with gamification system
- Provides detailed import summary

## Validation Rules

### Required Fields
- External WO Number (Request No. or Work Order No.)
- Complaint Description (becomes work order title)
- Location
- Complaint Type/Work Order Type
- Severity
- PIC Name/Fixed By (technician name)
- All date fields (Created, Assigned, Completed, Verified)

### Optional Fields
- Complainant/Assigned By
- Verified By
- Repair Description
- Rating (1-5)
- GPS coordinates
- Asset information

### Data Quality Checks
- Date sequence validation (Created ≤ Assigned ≤ Completed ≤ Verified)
- User name lookup in site user database
- Duplicate external WO number detection
- Valid work order type and severity values

## Error Handling

### Name Resolution Issues
- If a name cannot be found in the user database, the system will:
  - Log a warning message
  - Skip the work order if it's the assigned technician
  - Continue processing if it's optional users

### Data Format Issues
- Invalid dates: Clear error message with expected format
- Invalid types: Explanation of valid values
- Missing required fields: Specific field identification

## Integration Points

### Database Tables
- **wo_task**: Main work order records
- **wo_import_batch**: Import tracking
- **wo_import_log**: Detailed import history
- **sys_user**: User lookup for name resolution

### Gamification System
- Automatic points calculation for completed work orders
- Weekly gamification updates
- Performance tracking integration

## Files Modified

### Backend (PHP)
- `api/wo_import.php`: Main import processing with flexible column mapping
- `api/WoImportFileParser.php`: File parsing utilities

### Frontend (HTML/JavaScript)
- `wo_import.html`: User interface with flexible format guidance

### Database
- `wo_import_schema.sql`: Database structure for import tracking

## Testing

The system has been tested with:
- ✅ Column mapping functionality
- ✅ Name-to-email resolution
- ✅ Text-to-numeric value conversion
- ✅ Date format handling
- ✅ Validation logic
- ✅ Import execution flow

## Usage Instructions

1. **For Your Existing Format**:
   - Use your current CSV/Excel file format
   - Ensure user names match those in the GEMS system
   - Use "Complaint Description" for work order titles
   - System will handle all mapping automatically

2. **For New Users**:
   - Download the standard template
   - Follow the column naming conventions
   - Use either names or email addresses for users

3. **Import Process**:
   - Access the import page in GEMS
   - Select your site
   - Upload your file
   - Review the preview
   - Execute the import

The system is now fully compatible with your existing template format while maintaining all GEMS functionality and data integrity.
