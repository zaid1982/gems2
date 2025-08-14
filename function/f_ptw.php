<?php
/**
 * PTW (Permit to Work) Functions
 * Following GEMS2 patterns and standards
 */

class Class_ptw {
    private $constant;
    private $fn_general;
    private $fn_task;
    private $fn_email;

    public function __set($name, $value) {
        $this->$name = $value;
    }

    public function __get($name) {
        return $this->$name;
    }

    /**
     * Get permits pending SHE approval
     */
    public function get_permits_for_she_approval($user_site_id) {
        // Using the db_select method
        $permits = Class_db::getInstance()->db_select('ptw_permit', array(
            'site_id' => $user_site_id,
            'ptw_supervisor_approval' => 'APPROVED',
            'ptw_she_approval' => 'PENDING',
            'ptw_status' => 'PENDING_SHE'
        ), 'approved_supervisor_date ASC');
        
        // Enhance with supervisor names
        foreach ($permits as &$permit) {
            if ($permit['approved_supervisor_by']) {
                $supervisor = Class_db::getInstance()->db_select_single('sys_user', array(
                    'user_id' => $permit['approved_supervisor_by']
                ));
                if ($supervisor) {
                    $permit['supervisor_name'] = $supervisor['user_first_name'] . ' ' . $supervisor['user_last_name'];
                }
            }
        }
        
        return $permits;
    }

    /**
     * Get recent SHE actions
     */
    public function get_she_recent_actions($user_id, $user_site_id) {
        // Get recent status history actions by the user
        $actions = Class_db::getInstance()->db_select('ptw_status_history', array(
            'action_by' => $user_id
        ), 'action_date DESC', '10');
        
        // Filter for SHE actions and enhance with permit numbers
        $she_actions = array();
        foreach ($actions as $action) {
            if (in_array($action['action_type'], array('SHE_APPROVED', 'SHE_REJECTED'))) {
                // Get permit details
                $permit = Class_db::getInstance()->db_select_single('ptw_permit', array(
                    'ptw_permit_id' => $action['ptw_permit_id'],
                    'site_id' => $user_site_id
                ));
                if ($permit) {
                    $action['ptw_permit_number'] = $permit['ptw_permit_number'];
                    $she_actions[] = $action;
                }
            }
        }
        
        return $she_actions;
    }

    /**
     * Get SHE summary statistics
     */
    public function get_she_summary_statistics($user_id, $user_site_id) {
        // Get pending count
        $pending_count = Class_db::getInstance()->db_count('ptw_permit', array(
            'site_id' => $user_site_id,
            'ptw_supervisor_approval' => 'APPROVED',
            'ptw_she_approval' => 'PENDING'
        ));
        
        // Get approved count (by current user)
        $approved_count = Class_db::getInstance()->db_count('ptw_permit', array(
            'site_id' => $user_site_id,
            'approved_she_by' => $user_id,
            'ptw_she_approval' => 'APPROVED'
        ));
        
        // Get rejected count (by current user)
        $rejected_count = Class_db::getInstance()->db_count('ptw_permit', array(
            'site_id' => $user_site_id,
            'approved_she_by' => $user_id,
            'ptw_she_approval' => 'REJECTED'
        ));
        
        return array(
            'pending' => $pending_count,
            'approved' => $approved_count,
            'rejected' => $rejected_count,
            'total' => $approved_count + $rejected_count
        );
    }

    /**
     * Get permits pending FM approval
     */
    public function get_permits_for_fm_approval($user_site_id) {
        // Using the db_select method
        $permits = Class_db::getInstance()->db_select('ptw_permit', array(
            'site_id' => $user_site_id,
            'ptw_supervisor_approval' => 'APPROVED',
            'ptw_she_approval' => 'APPROVED',
            'ptw_fm_approval' => 'PENDING',
            'ptw_status' => 'PENDING_FM'
        ), 'approved_she_date ASC');
        
        // Enhance with approval details
        foreach ($permits as &$permit) {
            if ($permit['approved_supervisor_by']) {
                $supervisor = Class_db::getInstance()->db_select_single('sys_user', array(
                    'user_id' => $permit['approved_supervisor_by']
                ));
                if ($supervisor) {
                    $permit['supervisor_name'] = $supervisor['user_first_name'] . ' ' . $supervisor['user_last_name'];
                }
            }
            
            if ($permit['approved_she_by']) {
                $she_officer = Class_db::getInstance()->db_select_single('sys_user', array(
                    'user_id' => $permit['approved_she_by']
                ));
                if ($she_officer) {
                    $permit['she_officer_name'] = $she_officer['user_first_name'] . ' ' . $she_officer['user_last_name'];
                }
            }
        }
        
        return $permits;
    }

    /**
     * Get recent FM actions
     */
    public function get_fm_recent_actions($user_id, $user_site_id) {
        // Get recent actions by the FM
        $actions = Class_db::getInstance()->db_select('ptw_status_history', array(
            'action_by' => $user_id
        ), 'action_date DESC', 10);
        
        // Enhance with permit information
        foreach ($actions as &$action) {
            $permit = Class_db::getInstance()->db_select_single('ptw_permit', array(
                'ptw_permit_id' => $action['ptw_permit_id'],
                'site_id' => $user_site_id
            ));
            if ($permit) {
                $action['permit_number'] = $permit['ptw_permit_number'];
                $action['permit_description'] = $permit['ptw_permit_description'];
            }
        }
        
        return $actions;
    }

    /**
     * Get FM summary statistics
     */
    public function get_fm_summary_statistics($user_id, $user_site_id) {
        // Get pending count
        $pending_count = Class_db::getInstance()->db_count('ptw_permit', array(
            'site_id' => $user_site_id,
            'ptw_supervisor_approval' => 'APPROVED',
            'ptw_she_approval' => 'APPROVED',
            'ptw_fm_approval' => 'PENDING'
        ));
        
        // Get approved count (by current user)
        $approved_count = Class_db::getInstance()->db_count('ptw_permit', array(
            'site_id' => $user_site_id,
            'approved_fm_by' => $user_id,
            'ptw_fm_approval' => 'APPROVED'
        ));
        
        // Get rejected count (by current user)
        $rejected_count = Class_db::getInstance()->db_count('ptw_permit', array(
            'site_id' => $user_site_id,
            'approved_fm_by' => $user_id,
            'ptw_fm_approval' => 'REJECTED'
        ));
        
        return array(
            'pending' => $pending_count,
            'approved' => $approved_count,
            'rejected' => $rejected_count,
            'total' => $approved_count + $rejected_count
        );
    }

    /**
     * Get permit details
     */
    public function get_permit_details($permit_id, $user_site_id) {
        // Get permit
        $permit = Class_db::getInstance()->db_select('ptw_permit', array(
            'ptw_permit_id' => $permit_id,
            'site_id' => $user_site_id
        ));
        
        if (count($permit) == 0) {
            throw new Exception('PTW permit not found');
        }
        
        $permit_data = $permit[0];
        
        // Get creator name
        if ($permit_data['created_by']) {
            $creator = Class_db::getInstance()->db_select_single('sys_user', array('user_id' => $permit_data['created_by']));
            if ($creator) {
                $permit_data['created_by_name'] = $creator['user_first_name'] . ' ' . $creator['user_last_name'];
            }
        }
        
        // Get supervisor name
        if ($permit_data['approved_supervisor_by']) {
            $supervisor = Class_db::getInstance()->db_select_single('sys_user', array('user_id' => $permit_data['approved_supervisor_by']));
            if ($supervisor) {
                $permit_data['supervisor_name'] = $supervisor['user_first_name'] . ' ' . $supervisor['user_last_name'];
            }
        }
        
        // Get SHE name
        if ($permit_data['ptw_she_approved_by']) {
            $she = Class_db::getInstance()->db_select_single('sys_user', array('user_id' => $permit_data['ptw_she_approved_by']));
            if ($she) {
                $permit_data['she_name'] = $she['user_first_name'] . ' ' . $she['user_last_name'];
            }
        }
        
        // Get FM name
        if ($permit_data['ptw_fm_approved_by']) {
            $fm = Class_db::getInstance()->db_select_single('sys_user', array('user_id' => $permit_data['ptw_fm_approved_by']));
            if ($fm) {
                $permit_data['fm_name'] = $fm['user_first_name'] . ' ' . $fm['user_last_name'];
            }
        }
        
        // Get workers
        $workers = Class_db::getInstance()->db_select('ptw_worker', array('ptw_permit_id' => $permit_id), 'ptw_worker_id');
        $permit_data['workers'] = $workers;
        
        return $permit_data;
    }

    /**
     * Get permit list with filters
     */
    public function get_permit_list($filters) {
        $where_conditions = array();
        
        if (isset($filters['site_id'])) {
            $where_conditions['site_id'] = $filters['site_id'];
        }
        
        if (isset($filters['ptw_status'])) {
            $where_conditions['ptw_status'] = $filters['ptw_status'];
        }
        
        if (isset($filters['ptw_risk_level'])) {
            $where_conditions['ptw_risk_level'] = $filters['ptw_risk_level'];
        }
        
        // For date filters, we'll need to get all records and filter in PHP
        // since the existing db_select doesn't support date comparisons
        $permits = Class_db::getInstance()->db_select('ptw_permit', $where_conditions, 'created_date DESC');
        
        // Apply date filters if needed
        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $filtered_permits = array();
            foreach ($permits as $permit) {
                $valid_from = strtotime($permit['ptw_valid_from']);
                $valid_to = strtotime($permit['ptw_valid_to']);
                
                $include = true;
                if (isset($filters['date_from']) && $valid_from < strtotime($filters['date_from'])) {
                    $include = false;
                }
                if (isset($filters['date_to']) && $valid_to > strtotime($filters['date_to'])) {
                    $include = false;
                }
                
                if ($include) {
                    $filtered_permits[] = $permit;
                }
            }
            $permits = $filtered_permits;
        }
        
        // Enhance with creator names
        foreach ($permits as &$permit) {
            if ($permit['created_by']) {
                $creator = Class_db::getInstance()->db_select_single('sys_user', array('user_id' => $permit['created_by']));
                if ($creator) {
                    $permit['created_by_name'] = $creator['user_first_name'] . ' ' . $creator['user_last_name'];
                }
            }
        }
        
        return $permits;
    }

    /**
     * Get permit statistics
     */
    public function get_permit_statistics($user_site_id) {
        $stats = array();
        
        $statuses = array('DRAFT', 'PENDING_SUPERVISOR', 'PENDING_SHE', 'PENDING_FM', 'APPROVED', 'ACTIVE', 'COMPLETED', 'EXPIRED', 'CANCELLED');
        
        foreach ($statuses as $status) {
            $count = Class_db::getInstance()->db_count('ptw_permit', array(
                'site_id' => $user_site_id,
                'ptw_status' => $status
            ));
            $stats[strtolower($status)] = $count;
        }
        
        return $stats;
    }

    /**
     * Create new PTW permit
     */
    public function create_permit($permit_data) {
        // If permit_data is passed as array with all fields, use it directly
        // Otherwise, build from individual parameters for backward compatibility
        if (isset($permit_data['ptw_permit_number'])) {
            // Direct array insert
            $permit_id = Class_db::getInstance()->db_insert('ptw_permit', $permit_data);
        } else {
            // Legacy format - extract data and build array
            $data = $permit_data;
            $user_id = isset($data['created_by']) ? $data['created_by'] : 1;
            $user_site_id = isset($data['site_id']) ? $data['site_id'] : 1;
            
            // Generate permit number
            $permit_number = $this->generate_permit_number();
            
            $insert_data = array(
                'ptw_permit_number' => $permit_number,
                'ptw_permit_description' => isset($data['description']) ? $data['description'] : '',
                'ptw_work_area' => isset($data['work_area']) ? $data['work_area'] : '',
                'ptw_work_type' => isset($data['work_type']) ? $data['work_type'] : 'COLD_WORK',
                'ptw_risk_level' => isset($data['risk_level']) ? $data['risk_level'] : 'LOW',
                'ptw_valid_from' => isset($data['valid_from']) ? $data['valid_from'] : date('Y-m-d H:i:s'),
                'ptw_valid_to' => isset($data['valid_to']) ? $data['valid_to'] : date('Y-m-d H:i:s', strtotime('+8 hours')),
                'ptw_applicant_name' => isset($data['applicant_name']) ? $data['applicant_name'] : '',
                'ptw_status' => isset($data['status']) ? $data['status'] : 'DRAFT',
                'site_id' => $user_site_id,
                'created_by' => $user_id,
                'created_date' => date('Y-m-d H:i:s')
            );
            
            // Add optional fields only if they exist and are not empty
            if (isset($data['contractor_company']) && !empty($data['contractor_company'])) {
                $insert_data['ptw_contractor_company'] = $data['contractor_company'];
            }
            if (isset($data['remarks']) && !empty($data['remarks'])) {
                $insert_data['ptw_remarks'] = $data['remarks'];
            }
            if (isset($data['applicant_contact']) && !empty($data['applicant_contact'])) {
                $insert_data['ptw_applicant_contact'] = $data['applicant_contact'];
            }
            if (isset($data['applicant_department']) && !empty($data['applicant_department'])) {
                $insert_data['ptw_applicant_company_dept'] = $data['applicant_department'];
            }
            
            $permit_id = Class_db::getInstance()->db_insert('ptw_permit', $insert_data);
        }
        
        if (!$permit_id) {
            throw new Exception('Failed to create PTW permit');
        }
        
        // Insert workers
        if (isset($data['workers']) && is_array($data['workers'])) {
            foreach ($data['workers'] as $worker) {
                $worker_data = array(
                    'ptw_permit_id' => $permit_id,
                    'worker_name' => $worker['name'],
                    'worker_contact_number' => $worker['contact_number'],
                    'worker_role' => $worker['role'],
                    'worker_is_certified' => $worker['is_certified'] ? 1 : 0,
                    'created_by' => $user_id
                );
                Class_db::getInstance()->db_insert('ptw_worker', $worker_data);
            }
        }
        
        // Log status history
        $history_data = array(
            'ptw_permit_id' => $permit_id,
            'action_type' => 'CREATED',
            'previous_status' => null,
            'new_status' => $data['status'],
            'remarks' => 'PTW permit created',
            'action_by' => $user_id
        );
        Class_db::getInstance()->db_insert('ptw_status_history', $history_data);
        
        return array(
            'permit_id' => $permit_id,
            'permit_number' => $permit_number
        );
    }

    /**
     * Update PTW permit
     */
    public function update_permit($permit_id, $data, $user_id, $user_site_id) {
        // Get current permit
        $current_permit = Class_db::getInstance()->db_select('ptw_permit', array('ptw_permit_id' => $permit_id, 'site_id' => $user_site_id));
        if (count($current_permit) == 0) {
            throw new Exception('PTW permit not found');
        }
        
        $current_status = $current_permit[0]['ptw_status'];
        
        $permit_data = array(
            'ptw_permit_description' => $data['description'],
            'ptw_work_area' => $data['work_area'],
            'ptw_work_type' => $data['work_type'],
            'ptw_risk_level' => $data['risk_level'],
            'ptw_valid_from' => $data['valid_from'],
            'ptw_valid_to' => $data['valid_to'],
            'ptw_contractor_company' => $data['contractor_company'],
            'ptw_remarks' => $data['remarks'],
            'ptw_applicant_name' => $data['applicant_name'],
            'ptw_applicant_contact' => $data['applicant_contact'],
            'ptw_applicant_company_dept' => $data['applicant_department'],
            'ptw_hazards' => $data['hazards'],
            'ptw_control_measures' => $data['control_measures'],
            'ptw_checklist_data' => $data['checklist_data'],
            'ptw_status' => $data['status'],
            'updated_by' => $user_id
        );
        
        $result = Class_db::getInstance()->db_update('ptw_permit', $permit_data, array('ptw_permit_id' => $permit_id));
        
        if (!$result) {
            throw new Exception('Failed to update PTW permit');
        }
        
        // Update workers - delete existing and re-insert
        Class_db::getInstance()->db_delete('ptw_worker', array('ptw_permit_id' => $permit_id));
        
        if (isset($data['workers']) && is_array($data['workers'])) {
            foreach ($data['workers'] as $worker) {
                $worker_data = array(
                    'ptw_permit_id' => $permit_id,
                    'worker_name' => $worker['name'],
                    'worker_contact_number' => $worker['contact_number'],
                    'worker_role' => $worker['role'],
                    'worker_is_certified' => $worker['is_certified'] ? 1 : 0,
                    'created_by' => $user_id
                );
                Class_db::getInstance()->db_insert('ptw_worker', $worker_data);
            }
        }
        
        // Log status history if status changed
        if ($current_status !== $data['status']) {
            $action_type = 'UPDATED';
            if ($data['status'] === 'PENDING_SUPERVISOR') {
                $action_type = 'SUBMITTED';
            }
            
            $history_data = array(
                'ptw_permit_id' => $permit_id,
                'action_type' => $action_type,
                'previous_status' => $current_status,
                'new_status' => $data['status'],
                'remarks' => 'PTW permit updated',
                'action_by' => $user_id
            );
            Class_db::getInstance()->db_insert('ptw_status_history', $history_data);
        }
        
        return array(
            'permit_id' => $permit_id,
            'permit_number' => $current_permit[0]['ptw_permit_number']
        );
    }

    /**
     * Generate permit number
     */
    private function generate_permit_number() {
        // Since sys_sequence doesn't exist, use a simple approach
        // Get the highest existing permit number and increment
        $latest_permits = Class_db::getInstance()->db_select('ptw_permit', array(), 'ptw_permit_id DESC', '1');
        
        if (count($latest_permits) > 0) {
            $latest_id = $latest_permits[0]['ptw_permit_id'];
            $next_number = $latest_id + 1;
        } else {
            $next_number = 1;
        }
        
        // Format: PTW00000001
        $permit_number = 'PTW' . str_pad($next_number, 8, '0', STR_PAD_LEFT);
        
        return $permit_number;
    }

    /**
     * Send basic PTW notification (placeholder for future email implementation)
     */
    public function send_ptw_notification($permit_id, $notification_type, $recipients = array()) {
        // TODO: Implement email notifications
        // This is a placeholder for future email notification implementation
        
        $permit = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => $permit_id));
        if (!$permit) {
            return false;
        }
        
        $message = '';
        switch ($notification_type) {
            case 'SHE_APPROVAL_NEEDED':
                $message = "PTW Permit {$permit['ptw_permit_number']} requires SHE approval";
                break;
            case 'FM_APPROVAL_NEEDED':
                $message = "PTW Permit {$permit['ptw_permit_number']} requires FM approval";
                break;
            case 'APPROVED':
                $message = "PTW Permit {$permit['ptw_permit_number']} has been approved";
                break;
            case 'REJECTED':
                $message = "PTW Permit {$permit['ptw_permit_number']} has been rejected";
                break;
        }
        
        // Log the notification for now (can be replaced with actual email sending)
        $this->fn_general->log_debug('PTW', 'send_ptw_notification', __LINE__, 
            "Notification: $notification_type - $message");
        
        return true;
    }
}
?>
