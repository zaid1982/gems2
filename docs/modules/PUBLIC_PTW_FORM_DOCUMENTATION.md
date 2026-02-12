# Public PTW Form Documentation

## Overview
The PTW (Permit to Work) form has been converted to a fully public application that requires no authentication or login. This allows external contractors, visitors, and workers to submit permit applications directly.

## Key Features

### ✅ **No Authentication Required**
- Form is completely accessible without login
- No JWT tokens or session management needed
- Removed all dependencies on `common.js` authentication functions

### ✅ **Self-Contained JavaScript**
- Replaced all `common.js` functions with standard JavaScript
- Custom loading indicators instead of system-wide loaders
- Native alert functions instead of toastr dependencies
- Independent dropdown population and validation

### ✅ **Public User Assignment**
- All submissions automatically assigned to "Public User" (user_id: 1349)
- Backend automatically detects public form submissions via `public_user` parameter
- Maintains proper audit trail and data integrity

### ✅ **Enhanced User Experience**
- Interactive help system with clickable `?` icons
- Contextual examples and guidance for every field
- Smart tooltip system with auto-hide functionality
- Smooth animations and visual feedback
- Mobile-responsive help system

## Technical Implementation

### **Frontend Changes (ptw_form.html)**
```javascript
// Replaced common.js functions:
// mzGetDataVersion() → removed
// mzGetLocalArray() → removed  
// mzGetUserInfoByParam() → removed
// initiatePages() → removed
// ShowLoader/HideLoader → custom loading indicators
// toastr → custom alert functions

// New public mode initialization:
ptwFormClass_.setPublicMode(true);
ptwFormClass_.init();
```

### **JavaScript Class Updates (ptw_form.js)**
```javascript
// Added public mode support:
setPublicMode(isPublic = true)
initializeFormForPublic()
populateWorkTypeDropdown()

// Replaced authentication dependencies:
showError() → custom alert function
showSuccess() → custom alert function
loadPtwData() → disabled for public forms
```

### **Backend Support (api/ptw.php)**
```php
// Public form detection:
$is_public_form = isset($_POST['public_user']) && $_POST['public_user'] === 'Public User';

// Automatic public user assignment:
$public_users = Class_db::getInstance()->db_select('sys_user', array('user_first_name' => 'Public User'));
$jwt_data = (object) array('userId' => $public_users[0]['user_id']);
```

## Form Sections

### **1. Basic Information**
- PTW Description (required)
- Work Area (required)
- Work Type dropdown (populated via JavaScript)
- Valid From/To dates (auto-filled with today/tomorrow)

### **2. Risk Assessment**
- Risk Level selection (High/Critical)
- Identified Hazards
- Control Measures

### **3. Applicant Information**
- Applicant Name (required)
- Contact Number
- Department/Company
- Contractor Company
- Remarks

### **4. Workers Information**
- Dynamic worker addition
- Name and role fields
- Unlimited worker entries

### **5. Safety Checklist**
- General safety requirements
- Work-type specific checklists (auto-populated)
- Comprehensive checkbox validation

### **6. Contractor Declaration**
- Compliance declarations (Yes/No radio buttons)
- PPE requirements checklist
- Final contractor confirmation checkbox

## User Experience Flow

1. **Access Form**: Direct URL access with no login required
2. **Get Help**: Click `?` icons for field-specific guidance and examples
3. **Fill Information**: Complete all required sections with contextual help
4. **Submit Application**: Single button submission with immediate feedback
5. **Receive Confirmation**: Success message with automatic form clearing
6. **Form Reset**: Ready for next user with all help examples available

## Interactive Help System

### **Help Icon Features**
- **Contextual Guidance**: Each field has specific help content
- **Real Examples**: Practical examples of what to enter
- **Smart Display**: Only one help box shown at a time
- **Auto-Hide**: Help boxes close when clicking elsewhere
- **Smooth Animation**: Fade-in effects with scroll-to-view

### **Help Content Coverage**
- ✅ **PTW Description**: Work activity examples and requirements
- ✅ **Work Area**: Location specification guidelines
- ✅ **Work Type**: Detailed explanations of each category
- ✅ **Date Fields**: Validity period planning advice
- ✅ **Risk Assessment**: Risk level guidelines and hazard identification
- ✅ **Control Measures**: Safety measure examples and requirements
- ✅ **Applicant Info**: Contact and company information guidance
- ✅ **Safety Checklist**: Checkbox completion guidance

### **Help System Functions**
```javascript
// Toggle help display
toggleHelp(helpId)

// Auto-hide when clicking outside
document.addEventListener('click', closeHelpHandler)

// Smooth scroll to help content
helpElement.scrollIntoView({ behavior: 'smooth' })
```

## Approval Workflow

Public submissions enter the standard 3-tier approval process:
1. **Supervisor Approval** → PENDING_SUPERVISOR
2. **SHE Officer Review** → PENDING_SHE  
3. **Facility Manager Final Approval** → ACTIVE

## Security Considerations

### ✅ **Data Integrity**
- All submissions properly logged and audited
- Public user assignment maintains traceability
- Standard validation and security checks applied

### ✅ **Form Protection**
- Input validation and sanitization
- CSRF protection via backend validation
- Proper error handling and user feedback

### ✅ **System Isolation**
- No access to internal user data or authentication systems
- Limited to form submission functionality only
- No data loading or management capabilities

## File Structure

```
/gems2/
├── ptw_form.html                 # Public form interface
├── js/pages/ptw_form.js         # Form logic and validation
├── api/ptw.php                  # Backend API handler
└── PUBLIC_PTW_FORM_DOCUMENTATION.md
```

## Deployment Notes

### **Dependencies Removed**
- `js/common.js` - Authentication and system functions
- `js/pages/modal_change_password.js` - User management
- System-wide session management
- User authentication requirements

### **New Dependencies Added**
- Custom loading indicators
- Native dropdown styling
- Enhanced CSS for public form presentation

## Browser Compatibility

- ✅ Chrome/Edge 80+
- ✅ Firefox 75+
- ✅ Safari 13+
- ✅ Mobile browsers (responsive design)

## Maintenance

### **Regular Checks**
- Verify public user account exists in database
- Monitor form submission success rates
- Review approval workflow performance
- Update work type checklists as needed

### **Database Requirements**
```sql
-- Ensure public user exists:
SELECT * FROM sys_user WHERE user_first_name = 'Public User';

-- Verify submissions are being logged:
SELECT * FROM ptw_permit WHERE created_by = 1349 ORDER BY created_date DESC LIMIT 10;
```

## Support

For technical issues or questions about the public PTW form:
1. Check browser console for JavaScript errors
2. Verify database connectivity
3. Review API logs for submission errors
4. Test with different work types and scenarios

---

**Last Updated**: August 14, 2025  
**Version**: 2.0 (Public Release)  
**Status**: Production Ready ✅
