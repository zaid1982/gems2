# PTW View Mode Implementation Guide

This implementation adds view mode functionality to the existing PTW form while preserving all current approval logic.

## Overview

The PTW form now supports a "view mode" that can be used by supervisors, SHE officers, and facility managers to review and approve PTW applications through modals or dedicated pages.

## Features

### ✅ View Mode Detection
- URL parameter-based: `ptw_form.html?mode=view&role={ROLE}&id={PTW_ID}`
- Automatic UI transformation for read-only viewing
- Role-specific approval sections

### ✅ Role-Based Interface
- **Supervisor**: PTW Supervisor Review interface
- **SHE**: Safety, Health & Environment Review interface  
- **Facility Manager**: Final approval interface

### ✅ Modal Integration
- Bootstrap modal support with iframe
- Responsive design (95% width, auto-height)
- Close and refresh functionality

### ✅ Approval Workflow
- Approve/Reject buttons for each role
- Required comments for rejections
- Digital signature tracking
- Audit trail logging

## Implementation Details

### 1. URL Parameters

| Parameter | Values | Description |
|-----------|--------|-------------|
| `mode` | `view` | Enables view mode |
| `role` | `supervisor`, `she`, `facility_manager` | User role |
| `id` | `PTW-XXXX-XXX` | PTW application ID |

### 2. CSS Classes

```css
.view-mode                    /* Applied to body in view mode */
.view-mode-header            /* Header styling for view mode */
.view-mode-indicator         /* "READ ONLY" badge */
.approval-section            /* Approval workflow container */
.approval-section.supervisor /* Supervisor-specific styling */
.approval-section.she        /* SHE-specific styling */
.approval-section.facility-manager /* FM-specific styling */
```

### 3. JavaScript Functions

#### Core Functions
- `initializeViewMode()` - Detects and configures view mode
- `loadPtwData(ptwId)` - Loads PTW data via API
- `populateFormWithData(data)` - Fills form with PTW data
- `handleApproval(role, ptwId, decision, comments)` - Processes approvals

#### Modal Integration
- `openPtwModal(ptwId, role)` - Opens PTW in modal (from external pages)

### 4. API Endpoints

#### GET PTW Data
```javascript
GET api/ptw_approval.php?action=get&id={PTW_ID}
```

Response:
```json
{
  "success": true,
  "data": {
    "id": "PTW-2025-001",
    "applicant_name": "John Doe",
    "work_type": "hot_work",
    "risk_level": "high",
    // ... all form data
  }
}
```

#### Submit Approval
```javascript
POST api/ptw_approval.php
{
  "action": "approve",
  "id": "PTW-2025-001",
  "role": "supervisor",
  "decision": "approved",
  "comments": "All safety measures verified",
  "timestamp": "2025-08-15T10:30:00Z"
}
```

Response:
```json
{
  "success": true,
  "message": "PTW approved successfully",
  "approver_name": "Ahmad Supervisor",
  "timestamp": "2025-08-15T10:30:00Z"
}
```

## Usage Examples

### 1. Direct View Mode Access
```html
<a href="ptw_form.html?mode=view&role=supervisor&id=PTW-2025-001">
  Review PTW-2025-001
</a>
```

### 2. Modal Integration
```javascript
function openPtwModal(ptwId, role) {
    const url = `ptw_form.html?mode=view&role=${role}&id=${ptwId}`;
    document.getElementById('ptwIframe').src = url;
    $('#ptwModal').modal('show');
}
```

### 3. Dashboard Integration
```html
<button onclick="openPtwModal('PTW-2025-001', 'supervisor')" 
        class="btn btn-primary">
    <i class="fas fa-user-tie"></i> Supervisor Review
</button>
```

## File Structure

```
gems2/
├── ptw_form.html                           # Main form (enhanced with view mode)
├── ptw_modal_integration_example.html      # Modal integration example
├── api/
│   └── ptw_approval.php                    # Approval API endpoint
└── js/pages/
    └── ptw_form.js                         # Form logic (existing, unchanged)
```

## Database Schema (Recommended)

```sql
-- PTW Applications table
CREATE TABLE ptw_applications (
    id VARCHAR(50) PRIMARY KEY,
    applicant_name VARCHAR(255),
    contractor_company VARCHAR(255),
    work_area VARCHAR(255),
    work_type VARCHAR(100),
    risk_level ENUM('low', 'medium', 'high', 'critical'),
    work_description TEXT,
    status ENUM('draft', 'pending_supervisor', 'pending_she', 'pending_facility_manager', 'approved', 'rejected'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- ... additional fields as needed
);

-- Approval tracking table
CREATE TABLE ptw_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ptw_id VARCHAR(50),
    role ENUM('supervisor', 'she', 'facility_manager'),
    decision ENUM('approved', 'rejected'),
    comments TEXT,
    approver_name VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (ptw_id) REFERENCES ptw_applications(id)
);
```

## Security Considerations

1. **Authentication**: Implement proper user authentication before allowing access to view mode
2. **Authorization**: Verify user has appropriate role permissions
3. **Input Validation**: Sanitize all input data
4. **Audit Trail**: Log all approval actions
5. **CSRF Protection**: Implement CSRF tokens for approval actions

## Integration Steps

### For Dashboard Pages:

1. **Add Modal HTML**:
```html
<div class="modal fade ptw-modal" id="ptwModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-body">
                <iframe id="ptwIframe" src=""></iframe>
            </div>
        </div>
    </div>
</div>
```

2. **Add JavaScript**:
```javascript
function openPtwModal(ptwId, role) {
    const url = `ptw_form.html?mode=view&role=${role}&id=${ptwId}`;
    document.getElementById('ptwIframe').src = url;
    $('#ptwModal').modal('show');
}
```

3. **Add Action Buttons**:
```html
<button onclick="openPtwModal('PTW-ID', 'supervisor')" class="btn btn-primary">
    Review PTW
</button>
```

## Testing

### Test URLs:
- Supervisor: `ptw_form.html?mode=view&role=supervisor&id=PTW-2025-001`
- SHE: `ptw_form.html?mode=view&role=she&id=PTW-2025-002`  
- Facility Manager: `ptw_form.html?mode=view&role=facility_manager&id=PTW-2025-003`

### Modal Example:
- Open: `ptw_modal_integration_example.html`

## Backward Compatibility

✅ **All existing functionality preserved**
- Normal form creation still works as before
- Test Insert button functionality intact
- All validation and submission logic unchanged
- JavaScript class structure maintained

## Future Enhancements

1. **Print/PDF Export**: Add print-friendly view mode
2. **Digital Signatures**: Integrate e-signature functionality
3. **Workflow Automation**: Auto-routing based on approval status
4. **Mobile Optimization**: Enhanced mobile view mode interface
5. **Real-time Updates**: WebSocket integration for live status updates
