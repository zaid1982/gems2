# Performance History Row Click Feature - Work Order Details

## Overview
Added functionality to the Performance History table in the gamification system where users can click on a month row to view detailed work order information that was used in the gamification calculation for that specific month.

## Implementation Details

### 1. Frontend Changes

#### HTML Structure (`html/section_user_game.html`)
- Added a modal `#modalWoDetails` to display work order details
- Modal includes:
  - Header with month/year information
  - Summary cards showing total and completed WO counts
  - DataTable showing detailed WO information with columns:
    - WO No, Description, Asset, Status, Priority
    - Created Date, Target Date, Completed Date
    - On-Time Status (with colored badges)
    - WO Type (Self-Finding, Assist-Given, etc.)

#### JavaScript Changes (`js/pages/section_user_game.js`)
- Added row click handler to Performance History table (`#dtSugHistory`)
- Added hover tooltip showing "Click to see work order details for [Month Year]"
- Added `showWoDetails(year, month, userId)` function that:
  - Fetches WO data via API call
  - Updates modal title and summary statistics
  - Initializes/updates DataTable with WO details
  - Shows the modal with formatted data
- Added modal cleanup on close

### 2. Backend Changes

#### API Endpoint (`api/gamification.php`)
- Added new endpoint: `GET /api/gamification/wo_details/{year}/{month}/{userId}`
- Calls `getWoDetailsForGamification()` method in the gamification class

#### Database Method (`api/function/f_gamification.php`)
- Added `getWoDetailsForGamification($year, $month, $userId)` method
- Uses the same data sources as the gamification calculation:
  - `vw_gamification_wo_daily` - for assigned WOs
  - `vw_gamification_wo_assist_daily` - for assist WOs
- Retrieves detailed WO information from the `wo` table
- Joins with `asset` table for asset descriptions
- Calculates on-time status and WO type classification
- Returns formatted array with all WO details

## Features

### Row Click Functionality
- **Target**: Each row in the Performance History table represents a month
- **Action**: Click on any month row to view WO details for that month
- **Visual Feedback**: Cursor changes to pointer, tooltip shows click instruction

### Work Order Details Modal
- **Title**: Shows selected month and year
- **Summary Cards**: 
  - Total WO count for the month
  - Completed WO count for the month
- **Detailed Table**: Shows all work orders used in gamification calculation
- **Sorting**: Default sort by creation date (newest first)
- **Export Options**: DataTable includes built-in export functionality

### Data Accuracy
- **Same Logic**: Uses identical data selection logic as the gamification calculation
- **Complete Coverage**: Shows both assigned WOs and assist WOs
- **Status Classification**: 
  - On-Time: Completed before or on target date
  - Late: Completed after target date or pending past target
  - Pending: Not completed and target date not yet reached
- **Type Classification**:
  - Self-Finding: Requestor and assignee are the same person
  - Assist-Given: User is the requestor, someone else is assigned
  - Assist-Received: User is assigned, someone else is the requestor
  - Normal: Standard work orders

## Usage

1. Navigate to any user's profile page through the gamification leaderboard
2. Scroll to the Performance History table
3. Click on any month row to see work order details
4. Modal will open showing:
   - Month/year in the title
   - Summary statistics
   - Detailed work order table
5. Use table features (search, sort, export) as needed
6. Close modal to return to user profile

## Technical Notes

- **API Path**: `/api/gamification/wo_details/{year}/{month}/{userId}`
- **Database Views Used**: `vw_gamification_wo_daily`, `vw_gamification_wo_assist_daily`
- **Primary Tables**: `wo`, `asset`
- **Date Format**: YYYY-MM-DD for API parameters
- **Error Handling**: Comprehensive try-catch blocks with logging
- **Performance**: Efficient queries using existing gamification views

## Dependencies

- **JavaScript Libraries**: jQuery, DataTables, Bootstrap Modal, Moment.js
- **PHP Classes**: Class_gamification, Class_db, Class_general
- **Database Views**: Existing gamification views for data consistency
- **CSS Framework**: Bootstrap for modal and table styling

This feature provides complete transparency into the gamification calculation by showing users exactly which work orders contributed to their monthly scores.
