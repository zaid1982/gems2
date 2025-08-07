<?php
// Create test PTW data using config.ini database settings

require_once 'api/function/db.php';
require_once 'api/function/f_general.php';

try {
    $fn_general = new Class_general();
    Class_db::getInstance()->db_connect();
    
    echo "Connected to database successfully!\n";
    
    // Create test PTW permits with PENDING_SHE status
    $test_permits = array(
        array(
            'ptw_permit_number' => 'PTW202508070001',
            'ptw_permit_description' => 'Electrical maintenance on main panel',
            'ptw_work_area' => 'Electrical Room A',
            'ptw_work_type' => 'Cold Work',
            'ptw_risk_level' => 'HIGH',
            'ptw_status' => 'PENDING_SHE',
            'ptw_valid_from' => '2025-08-08 08:00:00',
            'ptw_valid_to' => '2025-08-08 17:00:00',
            'ptw_applicant_name' => 'John Doe',
            'ptw_applicant_contact' => '+60123456789',
            'ptw_applicant_company_dept' => 'Maintenance Department',
            'ptw_contractor_company' => 'ABC Electrical Services',
            'site_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ),
        array(
            'ptw_permit_number' => 'PTW202508070002',
            'ptw_permit_description' => 'Hot work on pipe welding',
            'ptw_work_area' => 'Workshop Area B',
            'ptw_work_type' => 'Hot Work',
            'ptw_risk_level' => 'CRITICAL',
            'ptw_status' => 'PENDING_SHE',
            'ptw_valid_from' => '2025-08-09 09:00:00',
            'ptw_valid_to' => '2025-08-09 16:00:00',
            'ptw_applicant_name' => 'Jane Smith',
            'ptw_applicant_contact' => '+60123456788',
            'ptw_applicant_company_dept' => 'Fabrication Department',
            'ptw_contractor_company' => 'XYZ Welding Co.',
            'site_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ),
        array(
            'ptw_permit_number' => 'PTW202508070003',
            'ptw_permit_description' => 'Confined space entry for tank cleaning',
            'ptw_work_area' => 'Storage Tank C',
            'ptw_work_type' => 'Confined Space',
            'ptw_risk_level' => 'CRITICAL',
            'ptw_status' => 'PENDING_SHE',
            'ptw_valid_from' => '2025-08-10 07:00:00',
            'ptw_valid_to' => '2025-08-10 15:00:00',
            'ptw_applicant_name' => 'Mike Johnson',
            'ptw_applicant_contact' => '+60123456787',
            'ptw_applicant_company_dept' => 'Safety Department',
            'ptw_contractor_company' => 'Safety First Solutions',
            'site_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        )
    );
    
    // Insert test permits
    foreach ($test_permits as $permit) {
        try {
            $permit_id = Class_db::getInstance()->db_insert('ptw_permit', $permit);
            echo "Created test PTW permit: {$permit['ptw_permit_number']} (ID: {$permit_id})\n";
        } catch (Exception $e) {
            echo "Failed to create permit {$permit['ptw_permit_number']}: " . $e->getMessage() . "\n";
        }
    }
    
    // Check if permits were created
    echo "\nChecking created permits:\n";
    $permits = Class_db::getInstance()->db_select('ptw_permit', array('site_id' => '1', 'ptw_status' => 'PENDING_SHE'));
    echo "Found " . count($permits) . " permits pending SHE approval\n";
    
    foreach ($permits as $permit) {
        echo "- {$permit['ptw_permit_number']}: {$permit['ptw_permit_description']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
