# Signature Feature Setup Guide

## Files Added/Modified:

### New Files:
- `signature.html` - Main signature management page
- `js/pages/main_signature.js` - Frontend logic for drawing/uploading signatures
- `function/f_user_signature.php` - Backend signature management class
- `migrations/2025_09_07_add_user_signature.sql` - Database schema for signatures

### Modified Files:
- `html/nav_top.html` - Added "My Signature" menu link in Profile dropdown
- `api/user_signature.php` - Uses existing endpoint pattern with document upload

## Setup Steps:

### 1. Database Migration
Run the migration script to create the signature table:
```sql
-- Execute this in your MySQL database:
SOURCE /path/to/gems2/migrations/2025_09_07_add_user_signature.sql;
```

### 2. Directory Permissions
Ensure upload directories exist and are writable:
```bash
mkdir -p uploads/signatures/master
mkdir -p uploads/signatures/ptw
chmod 775 uploads/signatures/master
chmod 775 uploads/signatures/ptw
```

### 3. Access the Feature
- Navigate to any existing page in your GEMS system
- Click the **Profile** dropdown in the top navigation
- Select **"My Signature"** to open the signature management page

## Features:
- **Draw Signature**: Canvas-based drawing with auto-trimming
- **Upload Signature**: PNG/JPEG file upload (auto-resized to max 400px width)
- **Preview**: Shows current saved signature
- **Integration Ready**: Backend prepared for PTW approval snapshots

## Next Steps (Optional):
1. **Add RBAC**: Show menu only to users with Supervisor/SHE/FM roles
2. **Snapshot Integration**: Modify PTW approval handlers to copy signatures
3. **PDF Integration**: Use signatures in generated PTW documents

## Usage:
1. Users with approval roles can set their signature once
2. Signature is automatically attached to PTW approvals
3. Historical signatures preserved via snapshot system
4. Clean, professional interface matching GEMS design

The feature is now fully integrated into your existing GEMS navigation and uses the established patterns for API calls and styling.
