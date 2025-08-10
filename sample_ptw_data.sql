-- Sample PTW data for testing
-- This script creates sample PTW permits to populate the management page

-- First, let's insert sample PTW permits
INSERT INTO `ptw_permit` (
    `ptw_permit_number`,
    `ptw_permit_description`,
    `ptw_work_area`,
    `ptw_work_type`,
    `ptw_risk_level`,
    `ptw_applicant_user_id`,
    `ptw_valid_from`,
    `ptw_valid_to`,
    `ptw_contractor_company`,
    `ptw_contractor_person`,
    `ptw_contractor_phone`,
    `ptw_worker_count`,
    `ptw_safety_precautions`,
    `ptw_emergency_contact`,
    `ptw_status`,
    `site_id`,
    `created_by`,
    `created_date`,
    `updated_by`,
    `updated_date`
) VALUES 
(
    'PTW20250109001',
    'Hot work - Welding repair on steam pipeline in boiler room area',
    'Boiler Room - Section A',
    'HOT_WORK',
    'HIGH',
    1, -- Assuming user ID 1 exists
    '2025-01-09 08:00:00',
    '2025-01-09 17:00:00',
    'ABC Maintenance Sdn Bhd',
    'Ahmad Rahman',
    '+6012-3456789',
    3,
    '{"ppe_required":true,"fire_watch":true,"gas_testing":true,"lockout_tagout":true}',
    'Emergency Hotline: +6012-9876543',
    'ACTIVE',
    19, -- Site ID from the debug logs
    1, -- Created by user ID 1
    NOW(),
    1,
    NOW()
),
(
    'PTW20250109002',
    'Electrical work - Installation of new lighting system in warehouse',
    'Warehouse - Zone B',
    'ELECTRICAL',
    'MEDIUM',
    2, -- Assuming user ID 2 exists
    '2025-01-10 09:00:00',
    '2025-01-10 16:00:00',
    'XYZ Electrical Services',
    'Lee Wei Ming',
    '+6013-2468135',
    2,
    '{"ppe_required":true,"fire_watch":false,"gas_testing":false,"lockout_tagout":true}',
    'Site Safety Officer: +6014-1357924',
    'PENDING_SHE',
    19,
    2,
    NOW(),
    2,
    NOW()
),
(
    'PTW20250109003',
    'Confined space work - Tank cleaning and inspection',
    'Storage Tank Farm - Tank T-101',
    'CONFINED_SPACE',
    'CRITICAL',
    3, -- Assuming user ID 3 exists
    '2025-01-11 07:00:00',
    '2025-01-11 15:00:00',
    'Professional Tank Services',
    'Kumar Selvam',
    '+6015-9876543',
    4,
    '{"ppe_required":true,"fire_watch":true,"gas_testing":true,"lockout_tagout":true,"atmospheric_monitoring":true}',
    'Emergency Response Team: +6019-8765432',
    'PENDING_SUPERVISOR',
    19,
    1,
    NOW(),
    1,
    NOW()
),
(
    'PTW20250108001',
    'Cold work - Mechanical maintenance on cooling system',
    'Equipment Room - Chiller Area',
    'COLD_WORK',
    'LOW',
    1,
    '2025-01-08 08:30:00',
    '2025-01-08 17:30:00',
    'Cool Tech Solutions',
    'Raj Patel',
    '+6016-1234567',
    2,
    '{"ppe_required":true,"fire_watch":false,"gas_testing":false,"lockout_tagout":true}',
    'Maintenance Supervisor: +6017-7654321',
    'COMPLETED',
    19,
    2,
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    2,
    DATE_SUB(NOW(), INTERVAL 1 DAY)
),
(
    'PTW20250107001',
    'Work at height - Roof maintenance and gutter cleaning',
    'Building A - Rooftop',
    'HEIGHT_WORK',
    'HIGH',
    2,
    '2025-01-07 08:00:00',
    '2025-01-07 16:00:00',
    'Heights Safety Contractors',
    'Maria Santos',
    '+6018-2468013',
    3,
    '{"ppe_required":true,"fire_watch":false,"gas_testing":false,"lockout_tagout":false,"fall_protection":true}',
    'Site Manager: +6019-1357902',
    'CANCELLED',
    19,
    3,
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    3,
    DATE_SUB(NOW(), INTERVAL 2 DAY)
);

-- Add sample worker records for the PTW permits
INSERT INTO `ptw_worker` (
    `ptw_permit_id`,
    `worker_name`,
    `worker_ic_number`,
    `worker_phone`,
    `worker_designation`,
    `worker_company`,
    `certification_number`,
    `certification_expiry`
) VALUES 
-- Workers for PTW20250109001 (Hot work)
(1, 'Ahmad Rahman', '850312-10-1234', '+6012-3456789', 'Senior Welder', 'ABC Maintenance Sdn Bhd', 'WLD-2024-001', '2025-12-31'),
(1, 'Hassan Ali', '900415-08-5678', '+6013-4567890', 'Welder Assistant', 'ABC Maintenance Sdn Bhd', 'WLD-2024-002', '2025-11-30'),
(1, 'Fatimah Zahra', '780920-12-9876', '+6014-5678901', 'Fire Watch', 'ABC Maintenance Sdn Bhd', 'FW-2024-003', '2025-10-15'),

-- Workers for PTW20250109002 (Electrical work)
(2, 'Lee Wei Ming', '821205-10-2468', '+6013-2468135', 'Electrical Technician', 'XYZ Electrical Services', 'ELC-2024-005', '2025-09-30'),
(2, 'Tan Ah Beng', '750818-08-1357', '+6015-3579246', 'Electrician', 'XYZ Electrical Services', 'ELC-2024-006', '2025-08-31'),

-- Workers for PTW20250109003 (Confined space)
(3, 'Kumar Selvam', '830710-05-9876', '+6015-9876543', 'Confined Space Supervisor', 'Professional Tank Services', 'CS-2024-010', '2025-12-15'),
(3, 'David Wong', '881122-10-5432', '+6016-8765432', 'Tank Cleaning Specialist', 'Professional Tank Services', 'CS-2024-011', '2025-11-20'),
(3, 'Siti Nurhaliza', '920305-12-1098', '+6017-6543210', 'Safety Monitor', 'Professional Tank Services', 'SM-2024-012', '2025-10-25'),
(3, 'Robert Lim', '790825-08-7654', '+6018-5432109', 'Gas Tester', 'Professional Tank Services', 'GT-2024-013', '2025-09-15');

-- Add sample status history records
INSERT INTO `ptw_status_history` (
    `ptw_permit_id`,
    `status_from`,
    `status_to`,
    `remarks`,
    `updated_by`,
    `updated_date`
) VALUES 
-- Status history for active permit
(1, 'DRAFT', 'PENDING_SUPERVISOR', 'Initial submission for supervisor approval', 1, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'PENDING_SUPERVISOR', 'PENDING_SHE', 'Approved by supervisor, forwarded to SHE', 4, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'PENDING_SHE', 'ACTIVE', 'Approved by SHE officer, permit activated', 5, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),

-- Status history for completed permit
(4, 'DRAFT', 'PENDING_SUPERVISOR', 'Initial submission', 2, DATE_SUB(NOW(), INTERVAL 1 DAY, INTERVAL 4 HOUR)),
(4, 'PENDING_SUPERVISOR', 'ACTIVE', 'Fast-track approval for low risk work', 4, DATE_SUB(NOW(), INTERVAL 1 DAY, INTERVAL 3 HOUR)),
(4, 'ACTIVE', 'COMPLETED', 'Work completed successfully', 2, DATE_SUB(NOW(), INTERVAL 1 DAY, INTERVAL 1 HOUR)),

-- Status history for cancelled permit
(5, 'DRAFT', 'PENDING_SUPERVISOR', 'Initial submission', 2, DATE_SUB(NOW(), INTERVAL 2 DAY, INTERVAL 3 HOUR)),
(5, 'PENDING_SUPERVISOR', 'CANCELLED', 'Weather conditions unsuitable for height work', 4, DATE_SUB(NOW(), INTERVAL 2 DAY, INTERVAL 2 HOUR));

-- Add approval log entries
INSERT INTO `ptw_approval_log` (
    `ptw_permit_id`,
    `approval_type`,
    `previous_status`,
    `new_status`,
    `remarks`,
    `approved_by`,
    `approved_date`
) VALUES 
-- Approval logs for active permit
(1, 'SUPERVISOR', 'PENDING_SUPERVISOR', 'PENDING_SHE', 'Safety requirements reviewed and approved', 4, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'SHE', 'PENDING_SHE', 'ACTIVE', 'All safety protocols verified, permit activated', 5, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),

-- Approval logs for completed permit
(4, 'SUPERVISOR', 'PENDING_SUPERVISOR', 'ACTIVE', 'Low risk maintenance work approved', 4, DATE_SUB(NOW(), INTERVAL 1 DAY, INTERVAL 3 HOUR)),
(4, 'COMPLETION', 'ACTIVE', 'COMPLETED', 'Work completed as per specifications', 2, DATE_SUB(NOW(), INTERVAL 1 DAY, INTERVAL 1 HOUR)),

-- Approval logs for cancelled permit
(5, 'CANCELLATION', 'PENDING_SUPERVISOR', 'CANCELLED', 'High wind conditions pose safety risk for height work', 4, DATE_SUB(NOW(), INTERVAL 2 DAY, INTERVAL 2 HOUR));

-- Note: This script assumes:
-- 1. User IDs 1, 2, 3, 4, 5 exist in sys_user table
-- 2. Site ID 19 exists (from the debug logs showing userSite: 19)
-- 3. The PTW tables have been created using ptw_database_setup.sql
-- 
-- If users don't exist, you may need to adjust the user IDs to match existing users
-- in your sys_user table, or create sample users first.
