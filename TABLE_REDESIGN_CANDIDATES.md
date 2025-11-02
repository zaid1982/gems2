# Table Redesign Candidates

This document lists all pages that contain DataTables and are candidates for the modern table design implementation.

## ✅ Already Redesigned
1. **home.html** - Dashboard with WO and PPM tables (✓ Complete with modern action buttons)

---

## 📋 High Priority - Main Data Management Pages

### Work Orders & Maintenance
1. **wo_assign.html** - Work Order Assignment
   - JS: `js/pages/main_wo_assign.js`
   - Tables: Work order assignment lists

2. **wo_verify.html** - Work Order Verification
   - JS: `js/pages/main_wo_verify.js`
   - Tables: Work order verification lists

3. **mrf.html** - Maintenance Request Forms
   - JS: `js/pages/main_mrf.js`
   - Table: `#dtMrfList` - MRF list with action buttons

4. **helpdesk.html** - Helpdesk Management
   - JS: `js/pages/main_helpdesk.js`
   - Table: `#dtHdkWo` - Help desk work orders

5. **complaint.html** - Public Complaints
   - JS: Related to WO system
   - Tables: Complaint tracking

### PPM (Planned Preventive Maintenance)
6. **ppm_management.html** - PPM Management
   - JS: `js/pages/main_ppm_management.js`
   - Tables: PPM task lists

7. **ppm_asset.html** - PPM Assets
   - JS: `js/pages/main_ppm_asset.js`
   - Tables: Asset PPM schedules

8. **ppm_group.html** - PPM Groups
   - JS: `js/pages/main_ppm_group.js`
   - Tables: PPM group management

9. **ppm_set_management.html** - PPM Set Management
   - JS: `js/pages/main_ppm_set_management.js`
   - Tables: PPM set configurations

10. **ppm_set_form.html** - PPM Set Form
    - JS: `js/pages/main_ppm_set_form.js`
    - Table: `#dtPpsa` - PPM set assets

11. **ppm_reschedule.html** - PPM Reschedule
    - JS: `js/pages/main_ppm_reschedule.js`
    - Tables: PPM rescheduling

12. **ppm_import.html** - PPM Import
    - Tables: Import validation/results

### Permit to Work (PTW)
13. **ptw.html** - PTW Dashboard
    - Tables: `#dtMyPtw`, `#dtPendingApproval`, `#dtActivePtw`, `#dtExpiringSoon`
    - Multiple PTW lists

14. **ptw_fm_dashboard.html** - FM Dashboard (Already has gems-page-header)
    - Tables: `#dtPendingFM`, `#dtPendingCancel`, `#dtPendingSuspend`, extension requests
    - Action buttons need modernization

15. **ptw_she_dashboard.html** - SHE Dashboard
    - Table: `#dtPendingSHE`
    - SHE approval lists

16. **ptw_supervisor.html** - Supervisor Dashboard
    - Tables: `#dtPendingPTW`, `#dtHistoryPTW`, `#dtActivePTWForClosure`, `#dtClosedPTW`, `#dtSuspendedPermits`
    - Multiple PTW management tables

17. **ptw_supervisor_dashboard.html** - Supervisor Dashboard Alt
    - Table: `#permitsTable`
    - Permit listing

18. **ptw_list.html** - PTW List
    - Tables: PTW listing/search

19. **ptw_management.html** - PTW Management
    - Tables: PTW administrative functions

### Assets
20. **asset.html** - Asset Management
    - JS: `js/pages/main_asset.js`
    - Tables: Asset listing with CRUD operations

21. **asset_group.html** - Asset Groups
    - JS: `js/pages/main_asset_group.js`
    - Tables: Asset group management

22. **asset_category.html** - Asset Categories
    - JS: `js/pages/main_asset_category.js`
    - Tables: Asset category management

23. **asset_type.html** - Asset Types
    - JS: `js/pages/main_asset_type.js`
    - Tables: `#dtAtyAssetType`, `#dtAtyAssetModel`
    - Asset type and model management

24. **asset_brand.html** - Asset Brands
    - JS: `js/pages/main_asset_brand.js`
    - Tables: Asset brand management

### FCA (Facility Condition Assessment)
25. **fca_task.html** - FCA Tasks (Already has gems-page-header)
    - JS: `js/pages/main_fca_task.js`
    - Tables: `#dtFctObserve`, `#dtFctRecommend`, `#dtFctValidate`, `#dtFctCorrection`
    - Multiple FCA workflow tables

26. **fca_all.html** - FCA All (Already has gems-page-header)
    - Tables: All FCA records

27. **fca_report.html** - FCA Reports (Already has gems-page-header)
    - Tables: FCA reporting

28. **fca_zone.html** - FCA Zones (Already has gems-page-header)
    - Tables: FCA zone management

29. **fca_defect.html** - FCA Defects (Already has gems-page-header)
    - Tables: FCA defect tracking

---

## 📊 Secondary Priority - Configuration & Admin

### Client & Site Management
30. **client.html** - Client Management
    - JS: `js/pages/main_client.js`
    - Tables: Client listing

31. **site.html** - Site Management
    - JS: `js/pages/main_site.js`
    - Tables: Site listing

32. **contract.html** - Contract Management
    - JS: `js/pages/main_contract.js`
    - Tables: Contract listing with location codes and users
    - Subtables: `#dtSctLocationCode`, `#dtSctLocationUser`

33. **zone.html** - Zone Management
    - JS: `js/pages/main_zone.js`
    - Tables: Zone listing

### Space Management
34. **space.html** - Space Management (Already has gems-page-header)
    - JS: `js/pages/main_space.js`
    - Tables: Space listing

35. **space_manage.html** - Space Management Detail
    - JS: `js/pages/main_space_manage.js`
    - Tables: `#dtSpmAssets`, `#dtSpmResv`, `#dtSpmPickAsset`
    - Space-related assets and reservations

36. **space_type.html** - Space Types
    - Tables: Space type configuration

37. **space_category.html** - Space Categories
    - Tables: Space category configuration

38. **space_location.html** - Space Locations
    - Tables: Space location management

39. **space_browse.html** - Space Browser
    - Tables: Space browsing/search

40. **space_calendar.html** - Space Calendar
    - May have table views

41. **my_reservations.html** - User Reservations
    - Tables: User's space reservations

### Utilities & Waste
42. **utility.html** - Utility Management (Already has gems-page-header)
    - JS: `js/pages/main_utility.js`
    - Tables: `#dtUtmMonthlyData`, `#dtUtmMonthlyWaterData`
    - Monthly utility tracking

43. **waste_main.html** - Waste Management (Already has gems-page-header)
    - JS: `js/pages/main_waste_main.js`
    - Table: `#dtWtm`
    - Waste tracking

44. **waste_type.html** - Waste Types (Already has gems-page-header)
    - Tables: Waste type configuration

### Attendance
45. **attendance.html** - Attendance
    - Tables: Attendance records

46. **attendance_site.html** - Attendance by Site (Already has gems-page-header)
    - JS: `js/pages/section_attendance_site.js`
    - Tables: `#dtSacGroup`, `#dtSacParticipant`, `#dtSacPlanner`
    - Multiple attendance views

47. **attendance_group.html** - Attendance Groups
    - Tables: Attendance group management

### Gamification
48. **gamification.html** - Gamification (Already has gems-page-header)
    - JS: `js/pages/main_gamification.js`
    - Tables: Gamification data

49. **gamification_config.html** - Gamification Config
    - Tables: Gamification settings

50. **section_user_game.js** - User Game Section
    - Table: `#dtSugHistory`, `#dtWoDetails`
    - User gamification history

---

## 🔧 Lower Priority - Support & Config

### User Management
51. **user_management.html** - User Management
    - Tables: User listing and roles

52. **designation.html** - Designations
    - Tables: Job designations

### Inventory & Store
53. **inventory.html** - Inventory
    - Tables: Inventory tracking

54. **store_management.html** - Store Management
    - JS: `js/pages/main_store_management.js`
    - Table: `#dtStmData`
    - Store item management

55. **item_management.html** - Item Management
    - Tables: Item catalog

56. **item_type_management.html** - Item Types
    - Tables: Item type configuration

57. **purchase_request.html** - Purchase Requests
    - Tables: PR tracking

### Reference Data
58. **failure_code.html** - Failure Codes
    - Tables: Failure code management

59. **severity.html** - Severity Levels
    - Tables: Severity configuration

60. **checklist.html** - Checklists
    - JS: `js/pages/main_checklist.js`
    - Tables: Checklist management
    - Subtable: `#dtSckChecklistQual`

61. **drawing_records.html** - Drawing Records
    - JS: `js/pages/main_drawing_records.js`
    - Table: `#dtDwrData`
    - Drawing/document management

62. **license.html** - License Management
    - Already uses modern design pattern (reference implementation)

### Reporting
63. **report_wo_summary.html** - WO Summary Reports
    - Tables: WO reporting

64. **report_wo_pending.html** - WO Pending Reports
    - JS: `js/pages/main_report_wo_pending.js`
    - Table: `#dtRwpWoPending`

65. **report_ppm_summary.html** - PPM Summary Reports
    - Tables: PPM reporting

66. **report_all_summary.html** - All Summary Reports
    - Tables: Combined reporting

67. **finance_report1.html** - Finance Report 1
    - Tables: Financial reporting

68. **finance_report2.html** - Finance Report 2
    - Tables: Financial reporting

69. **finance_report3.html** - Finance Report 3
    - Tables: Financial reporting

70. **finance_report4.html** - Finance Report 4
    - Tables: Financial reporting

71. **finance_report5.html** - Finance Report 5
    - Tables: Financial reporting

72. **finance_report6.html** - Finance Report 6
    - Tables: Financial reporting

### KPI
73. **kpi_in.html** - KPI Internal
    - Tables: KPI tracking

74. **kpi_ppns.html** - KPI PPNS
    - JS: `js/pages/main_kpi_ppns.js`
    - Table: `#dtKppData`

### Visitor Management
75. **vm_visits.html** - VM Visits
    - Table: `#visitsTable`
    - Visitor visit records

76. **vm_visit.html** - VM Visit Detail
    - Visit details

77. **vm_hosts.html** - VM Hosts
    - Table: Host management
    - DataTable for host listing

78. **vm_admin_visits.html** - VM Admin Visits
    - Table: `#tbl`
    - Admin view of all visits

### Other
79. **audit_trail.html** - Audit Trail
    - Tables: System audit logs

80. **track_monitoring.html** - Track Monitoring
    - Tables: Tracking data

81. **wo_import.html** - WO Import
    - Tables: Import validation

82. **validate_import.html** - Validate Import
    - Tables: Import validation results

---

## 📈 Implementation Statistics

- **Total Pages with Tables**: ~82 pages
- **Already Redesigned**: 1 page (home.html)
- **High Priority**: 29 pages (WO, PPM, PTW, Assets, FCA)
- **Secondary Priority**: 21 pages (Configuration, Admin)
- **Lower Priority**: 31 pages (Support, Config, Reporting)

---

## 🎯 Recommended Approach

### Phase 1: High-Traffic Data Management (Weeks 1-2)
Focus on pages users interact with daily:
- Work Orders (wo_assign, wo_verify, mrf, helpdesk)
- PTW dashboards (ptw_fm_dashboard, ptw_she_dashboard, ptw_supervisor)
- Asset management (asset.html)
- FCA tasks (already has header, just need table upgrade)

### Phase 2: Core Configuration (Week 3)
Essential configuration pages:
- PPM management pages
- Client/Site/Contract management
- Space management core pages

### Phase 3: Supporting Features (Week 4)
Less frequent but important:
- Attendance tracking
- Utilities & Waste
- Gamification
- Inventory/Store

### Phase 4: Reports & Reference (Week 5)
Lower priority maintenance pages:
- All reporting pages
- Reference data tables
- User management
- Audit trails

---

## 🔍 Implementation Checklist per Page

For each page, verify:
- [ ] Replace old card wrapper with `.modern-table-card` or `.home-table-card`
- [ ] Update table header with gradient `.table-header`
- [ ] Add `.table-controls` section with search and column visibility
- [ ] Wrap table in `.table-body`
- [ ] Update action column to use `.action-btn-group`
- [ ] Change action buttons from `<a><i>` to `<button class="btn-action btn-view/edit/delete">`
- [ ] Update status rendering to use `.status-badge` classes
- [ ] Add `.action-cell` class to action column
- [ ] Test mobile responsive behavior
- [ ] Verify tooltips work on action buttons

---

## Notes

- All CSS is now centralized in `css/gems2-ui.css`
- Design pattern documented in `DESIGN_SYSTEM_DOCS.md`
- Reference implementation: `home.html` + `js/pages/main_home.js`
- Pages marked "Already has gems-page-header" only need table card + action button upgrades
