<?php

class Class_ptw {

    private $constant;
    private $fn_general;
    private $fn_task;
    private $fn_email;

    function __construct() {
        $this->fn_general = new Class_general();
    }

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false) {
                $msg = substr($msg, $pos + 2);
            }
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "] - " . $msg;
        } else {
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "]";
        }
    }

    /**
     * @param $property
     * @return mixed
     * @throws Exception
     */
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * Execute raw SQL query with prepared statements
     * @param string $sql
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function execute_raw_query($sql, $params = array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Executing SQL: ' . $sql);
            
            $db = Class_db::getInstance();
            $stmt = $db->DBH->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            $code = $ex->getCode();
            if (!is_int($code)) { $code = is_numeric($code) ? intval($code) : 0; }
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, $ex->getMessage()), $code);
        }
    }

    /**
     * Build a normalized brief user object from a DB row.
     * @param array $row
     * @return array
     */
    private function build_user_brief($row) {
        if (!$row || !is_array($row)) { return array(); }
        $first = isset($row['user_first_name']) ? trim((string)$row['user_first_name']) : '';
        $last  = isset($row['user_last_name']) ? trim((string)$row['user_last_name']) : '';
        $disp  = trim(($first . ' ' . $last));
        if ($disp === '') {
            $disp = isset($row['user_name']) && $row['user_name'] !== '' ? $row['user_name'] : (isset($row['email']) ? $row['email'] : (isset($row['user_email']) ? $row['user_email'] : ''));
        }
        return array(
            'userId'          => isset($row['user_id']) ? (int)$row['user_id'] : null,
            'userFirstName'   => $first,
            'userLastName'    => $last,
            'userName'        => isset($row['user_name']) ? $row['user_name'] : null,
            'email'           => isset($row['profile_email']) && $row['profile_email'] !== '' ? $row['profile_email'] : (isset($row['user_email']) ? $row['user_email'] : null),
            'contactNo'       => isset($row['user_contact_no']) ? $row['user_contact_no'] : null,
            'designationId'   => isset($row['designation_id']) ? $row['designation_id'] : null,
            'designationDesc' => isset($row['designation_desc']) ? $row['designation_desc'] : null,
            'siteId'          => isset($row['site_id']) ? $row['site_id'] : null,
            'displayName'     => $disp
        );
    }

    /**
     * Resolve a user brief by user_id with profile+designation joins.
     * @param int|string $user_id
     * @return array|null
     */
    private function load_user_brief($user_id) {
        try {
            if ($user_id === null || $user_id === '' ) { return null; }
          $sql = "SELECT u.user_id, u.user_first_name, u.user_last_name, u.user_email, u.site_id,
                           up.user_email AS profile_email, up.user_contact_no, up.designation_id,
                           d.designation_desc
                      FROM sys_user u
                 LEFT JOIN sys_user_profile up ON up.user_id = u.user_id AND up.user_profile_status = 1
                 LEFT JOIN ref_designation d ON d.designation_id = up.designation_id
                     WHERE u.user_id = ?";
            $rows = $this->execute_raw_query($sql, array(strval($user_id)));
            if (!$rows || count($rows) === 0) { return null; }
            return $this->build_user_brief($rows[0]);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'load_user_brief error: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Get next running sequence for a given site and type (REQUEST | PERMIT) for current date (per-site-per-day)
     * Creates the row if missing.
     * @param int $site_id
     * @param string $seq_type 'REQUEST' or 'PERMIT'
     * @return int next sequence value
     */
    private function get_next_sequence($site_id, $seq_type) {
        try {
            $site_id = intval($site_id);
            $seq_type = strtoupper($seq_type) === 'PERMIT' ? 'PERMIT' : 'REQUEST';

            // Ensure the row exists for today
            $sqlInsert = "INSERT INTO ptw_number_sequence (site_id, seq_date, seq_type, next_value, updated_at)
                           VALUES (?, CURDATE(), ?, 1, NOW())
                           ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)";
            $this->execute_raw_query($sqlInsert, array($site_id, $seq_type));

            // Atomically increment and get new value
            $sqlUpdate = "UPDATE ptw_number_sequence
                          SET next_value = next_value + 1, updated_at = NOW()
                          WHERE site_id = ? AND seq_date = CURDATE() AND seq_type = ?";
            $this->execute_raw_query($sqlUpdate, array($site_id, $seq_type));

            // Read back
            $row = $this->execute_raw_query(
                "SELECT next_value FROM ptw_number_sequence WHERE site_id = ? AND seq_date = CURDATE() AND seq_type = ?",
                array($site_id, $seq_type)
            );
            if (!empty($row)) {
                // next_value has already been incremented; use it as the current assigned number
                return intval($row[0]['next_value']);
            }
            // Fallback to 1
            return 1;
        } catch (Exception $ex) {
            // As a safe fallback, derive from time to avoid blocking
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'Sequence failed, using time-based fallback: ' . $ex->getMessage());
            return intval(date('His')); // not ideal, but guarantees a changing number
        }
    }

    private function pad_site($site_id) {
        $n = intval($site_id);
        return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
    }

    private function get_site_code($site_id) {
        // Resolve 3-letter site code from cli_site.siteCode (fallback to numeric padded if missing)
        try {
            $row = Class_db::getInstance()->db_select_single('cli_site', array('siteId' => strval($site_id)));
            if ($row) {
                $code = isset($row['siteCode']) ? $row['siteCode'] : (isset($row['site_code']) ? $row['site_code'] : '');
                if (!empty($code)) {
                    return strtoupper(substr($code, 0, 3));
                }
            }
        } catch (Exception $e) {
            // ignore and fallback
        }
        // Fallback to 2-digit numeric padded (legacy)
        return $this->pad_site($site_id);
    }

    private function format_request_number($site_id, $seq_val) {
        $site = $this->pad_site($site_id);
        $seq = str_pad((string)intval($seq_val), 3, '0', STR_PAD_LEFT);
        return 'RQPTW' . $site . date('ymd') . $seq;
    }

    private function format_permit_number($site_id, $seq_val) {
        // PTWLLLYYMMDDXXX where LLL is site code
        $site = $this->get_site_code($site_id);
        $seq = str_pad((string)intval($seq_val), 3, '0', STR_PAD_LEFT);
        return 'PTW' . $site . date('ymd') . $seq;
    }

    /**
     * Assign Request Number at first submission (idempotent).
     */
    public function assign_request_number($permit_id, $site_id, $user_id) {
        try {
            $permit = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => strval($permit_id)));
            if (!$permit) { throw new Exception('Permit not found'); }
            if (!empty($permit['ptw_request_number'])) { return $permit['ptw_request_number']; }

            $seq = $this->get_next_sequence($site_id, 'REQUEST');
            $req_no = $this->format_request_number($site_id, $seq);

            Class_db::getInstance()->db_update('ptw_permit', array(
                'ptw_request_number' => $req_no,
                'updated_by' => $user_id,
                'updated_date' => date('Y-m-d H:i:s')
            ), array('ptw_permit_id' => strval($permit_id)));

            // Optional: log in status history as SUBMITTED (do not alter enum)
            try {
                Class_db::getInstance()->db_insert('ptw_status_history', array(
                    'ptw_permit_id'   => $permit_id,
                    'action_type'     => 'SUBMITTED',
                    'previous_status' => $permit['ptw_status'],
                    'new_status'      => 'PENDING_SUPERVISOR',
                    'remarks'         => 'Request No: ' . $req_no,
                    'action_by'       => $user_id
                ));
            } catch (Exception $ignore) {}

            return $req_no;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw $ex;
        }
    }

    /**
     * Assign Permit Number at first qualifying approval (now Supervisor approval stage).
     * Idempotent: if already assigned, returns existing number.
     */
    public function assign_permit_number($permit_id, $site_id, $user_id) {
        try {
            $permit = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => strval($permit_id)));
            if (!$permit) { throw new Exception('Permit not found'); }
            if (!empty($permit['ptw_permit_number'])) { return $permit['ptw_permit_number']; }

            $seq = $this->get_next_sequence($site_id, 'PERMIT');
            $ptw_no = $this->format_permit_number($site_id, $seq);

            Class_db::getInstance()->db_update('ptw_permit', array(
                'ptw_permit_number' => $ptw_no,
                'updated_by' => $user_id,
                'updated_date' => date('Y-m-d H:i:s')
            ), array('ptw_permit_id' => strval($permit_id)));

            // Optional: log in status history with FM_APPROVED (enum exists)
            // history will be logged by approval flow; keep minimal here

            return $ptw_no;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw $ex;
        }
    }

    /**
     * Map internal backend status (plus context) to URS display status.
     * Optionally detects "Extended" by inspecting provided history array; if not provided and detection needed,
     * will lazily query (single COUNT) only when current status would otherwise map to Approved (ACTIVE).
     * @param array $permit Row from ptw_permit (or merged API structure)
     * @param array|null $history Optional array of history rows (each needs action_type)
     * @return string User-facing display status
     */
    public function map_display_status($permit, $history = null) {
        $status = isset($permit['ptw_status']) ? strtoupper($permit['ptw_status']) : '';
        $sup = strtoupper($permit['ptw_supervisor_approval'] ?? '');
        $she = strtoupper($permit['ptw_she_approval'] ?? '');
        $fm  = strtoupper($permit['ptw_fm_approval'] ?? '');

        // Base direct mappings
        switch ($status) {
            case 'DRAFT': return 'Draft';
            case 'SUBMITTED': return 'New Request'; // transitional pre PENDING_SUPERVISOR
            case 'PENDING_SUPERVISOR': return 'New Request';
            case 'PENDING_SHE': return 'SHE Approval';
            case 'PENDING_FM': return 'FM Approval';
            case 'PENDING_EXTENSION': return 'Extension Requested';
            case 'ACTIVE': return 'Approved'; // legacy/alternate to ACTIVE
            case 'APPROVED': return 'Approved'; // legacy/alternate to ACTIVE
            case 'EXTENDED': return 'Extended'; // new explicit enum after approval
            case 'PENDING_CLOSURE': return 'Closure Requested';
            case 'PENDING_CANCELLATION': return 'Cancellation Requested';
            case 'PENDING_SUSPENSION': return 'Suspension Requested';
            case 'COMPLETED': return 'Closed';
            case 'CANCELLED': return 'Cancelled';
            case 'SUSPENDED': return 'Suspended';
            case 'REJECTED': return 'Rejected';
        }

    // ACTIVE -> could be Approved or Extended (legacy before EXTENDED enum existed)
    if ($status === 'ACTIVE') {
            // Detect extended: criteria -> history contains action_type 'EXTENDED'
            $isExtended = false;
            if (is_array($history)) {
                foreach ($history as $h) {
                    if (isset($h['action_type']) && strtoupper($h['action_type']) === 'EXTENDED') { $isExtended = true; break; }
                }
            } else {
                // Lazy single COUNT only if needed
                try {
                    $pid = $permit['ptw_permit_id'] ?? $permit['permit_id'] ?? null;
                    if ($pid) {
                        $row = Class_db::getInstance()->db_select("SELECT COUNT(1) c FROM ptw_status_history WHERE ptw_permit_id = :pid AND action_type = 'EXTENDED'", [], null, null, 0, [':pid' => strval($pid)]);
                        if (is_array($row) && count($row) > 0) { $isExtended = intval($row[0]['c']) > 0; }
                    }
                } catch (Exception $e) { /* ignore */ }
            }
            return $isExtended ? 'Extended' : 'Approved';
        }

        // Fallback - if approvals chain defines a meaningful stage not captured in ptw_status
        if ($sup !== 'APPROVED') { return 'New Request'; }
        if ($sup === 'APPROVED' && $she !== 'APPROVED') { return 'SHE Approval'; }
        if ($she === 'APPROVED' && $fm !== 'APPROVED') { return 'FM Approval'; }
        if ($fm === 'APPROVED') { return 'Approved'; }

        return 'Unknown';
    }

    /**
     * Get PTW permit list with filters
     * @param array $filters
     * @return array
     * @throws Exception
     */
    public function get_permit_list($filters = array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            
            // Use simple db_select to avoid JOIN issues
            $where_conditions = array();
            if (isset($filters['site_id'])) {
                $where_conditions['site_id'] = strval($filters['site_id']);
            }
            if (isset($filters['ptw_status']) && !empty($filters['ptw_status'])) {
                $where_conditions['ptw_status'] = $filters['ptw_status'];
            }
            if (isset($filters['ptw_risk_level']) && !empty($filters['ptw_risk_level'])) {
                $where_conditions['ptw_risk_level'] = $filters['ptw_risk_level'];
            }
            
            $permits = Class_db::getInstance()->db_select('ptw_permit', $where_conditions, 'created_date DESC');
            
            return $permits;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get PTW permit details with workers and documents
     * @param int $permit_id
     * @param int $site_id
     * @return array|null
     * @throws Exception
     */
    public function get_permit_details($permit_id, $site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($permit_id, $site_id));
            // Normalize to strings for DB where builders that expect string values
            $permitIdStr = strval($permit_id);
            $siteIdStr = strval($site_id);
            
            // Get permit basic details
        $sql = "SELECT p.*, 
               CONCAT_WS(' ', u.user_first_name, u.user_last_name) as created_by_name,
               s.site_name,
               CONCAT_WS(' ', su.user_first_name, su.user_last_name) as approved_supervisor_name,
               CONCAT_WS(' ', she.user_first_name, she.user_last_name) as approved_she_name,
               CONCAT_WS(' ', fm.user_first_name, fm.user_last_name) as approved_fm_name,
               CONCAT_WS(' ', psu.user_first_name, psu.user_last_name) as ptw_supervisor_full_name
                    FROM ptw_permit p
                    LEFT JOIN sys_user u ON p.created_by = u.user_id
                    LEFT JOIN sys_site s ON p.site_id = s.site_id
                    LEFT JOIN sys_user su ON p.approved_supervisor_by = su.user_id
                    LEFT JOIN sys_user she ON p.approved_she_by = she.user_id
            LEFT JOIN sys_user fm ON p.approved_fm_by = fm.user_id
            LEFT JOIN sys_user psu ON p.ptw_supervisor_id = psu.user_id
            WHERE p.ptw_permit_id = ? AND p.site_id = ?";

        $permit = $this->execute_raw_query($sql, array($permitIdStr, $siteIdStr));
            
            if (empty($permit)) {
                return null;
            }
            
            $permit_data = $permit[0];
            
            // Get workers
            $workers = Class_db::getInstance()->db_select('ptw_worker', array('ptw_permit_id' => $permitIdStr));
            $permit_data['workers'] = $workers;
            
            // Get documents
            $documents = Class_db::getInstance()->db_select('ptw_document', array('ptw_permit_id' => $permitIdStr));
            $permit_data['documents'] = $documents;
            
            // Get approval log
            $approval_log = Class_db::getInstance()->db_select('ptw_approval_log', 
                array('ptw_permit_id' => $permitIdStr), 'approved_date DESC');
            $permit_data['approval_log'] = $approval_log;

            // Attach nested user objects for approvals to remove client-side lookups
            try {
                $supId = null; $sheId = null; $fmId = null;
                // Supervisor ID: prefer approved_supervisor_by, fallback to ptw_supervisor_id, only if > 0
                if (isset($permit_data['approved_supervisor_by']) && $permit_data['approved_supervisor_by'] !== null && $permit_data['approved_supervisor_by'] !== '') {
                    $v = intval($permit_data['approved_supervisor_by']); if ($v > 0) { $supId = $v; }
                }
                if ($supId === null && isset($permit_data['ptw_supervisor_id'])) {
                    $v = intval($permit_data['ptw_supervisor_id']); if ($v > 0) { $supId = $v; }
                }
                if ($supId === null && isset($permit_data['supervisor_id'])) {
                    $v = intval($permit_data['supervisor_id']); if ($v > 0) { $supId = $v; }
                }
                // SHE ID
                if (isset($permit_data['approved_she_by']) && $permit_data['approved_she_by'] !== null && $permit_data['approved_she_by'] !== '') {
                    $v = intval($permit_data['approved_she_by']); if ($v > 0) { $sheId = $v; }
                }
                // FM ID
                if (isset($permit_data['approved_fm_by']) && $permit_data['approved_fm_by'] !== null && $permit_data['approved_fm_by'] !== '') {
                    $v = intval($permit_data['approved_fm_by']); if ($v > 0) { $fmId = $v; }
                }

                $permit_data['supervisor'] = $supId ? $this->load_user_brief($supId) : null;
                $permit_data['she']        = $sheId ? $this->load_user_brief($sheId) : null;
                $permit_data['fm']         = $fmId ? $this->load_user_brief($fmId) : null;

                // If names are empty from CONCAT, backfill from nested users or stored fields
                if (empty($permit_data['approved_supervisor_name']) || trim((string)$permit_data['approved_supervisor_name']) === '') {
                    if ($permit_data['supervisor']) {
                        $permit_data['approved_supervisor_name'] = $permit_data['supervisor']['displayName'];
                    } elseif (!empty($permit_data['ptw_supervisor_full_name'])) {
                        $permit_data['approved_supervisor_name'] = $permit_data['ptw_supervisor_full_name'];
                    } elseif (!empty($permit_data['ptw_contractor_supervisor'])) {
                        // last-resort textual fallback
                        $permit_data['approved_supervisor_name'] = $permit_data['ptw_contractor_supervisor'];
                    }
                }
                if ((empty($permit_data['approved_she_name']) || trim((string)$permit_data['approved_she_name']) === '') && $permit_data['she']) {
                    $permit_data['approved_she_name'] = $permit_data['she']['displayName'];
                }
                if ((empty($permit_data['approved_fm_name']) || trim((string)$permit_data['approved_fm_name']) === '') && $permit_data['fm']) {
                    $permit_data['approved_fm_name'] = $permit_data['fm']['displayName'];
                }
            } catch (Exception $e) {
                // Non-fatal; keep response without nested users
                $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'Failed to attach nested users: ' . $e->getMessage());
            }
            
            return $permit_data;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get PTW statistics for dashboard
     * @param int $site_id
     * @return array
     * @throws Exception
     */
    public function get_permit_statistics($site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN ptw_status IN ('PENDING_SUPERVISOR', 'PENDING_SHE', 'PENDING_FM') THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN ptw_status = 'ACTIVE' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN ptw_risk_level = 'CRITICAL' THEN 1 ELSE 0 END) as critical
                    FROM ptw_permit 
                    WHERE site_id = ?";
            
            $result = $this->execute_raw_query($sql, array(strval($site_id)));
            
            return $result[0];
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0007', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Create new PTW permit
     * @param array $permit_data
     * @return int permit_id
     * @throws Exception
     */
    public function create_permit($permit_data) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($permit_data));
            
            $permit_id = Class_db::getInstance()->db_insert('ptw_permit', $permit_data);
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'PTW permit created with ID: ' . $permit_id);
            // Issue public token immediately for view access (best-effort; skip if columns not present)
            try {
                $valid_to = isset($permit_data['ptw_valid_to']) ? $permit_data['ptw_valid_to'] : null;
                $this->issue_public_token($permit_id, $valid_to);
            } catch (Exception $e) {
                $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'Failed to issue public token (non-fatal): ' . $e->getMessage());
            }

            return $permit_id;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0008', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Generate and store a public token for viewing a permit.
     * Sets optional expiry and enables the public link. Safe to call multiple times; will rotate token.
     * Swallows errors if columns don't exist (for backward compatibility).
     * @param int $permit_id
     * @param string|null $valid_to MySQL DATETIME; if provided, expiry defaults to valid_to + 30 days
     * @param int $ttl_days Additional TTL days if valid_to not provided (default 365)
     * @return string|null The token or null if failed
     */
    public function issue_public_token($permit_id, $valid_to = null, $ttl_days = 365) {
        try {
            $token = bin2hex(random_bytes(32)); // 64-char hex
            $expires_at = null;
            if (!empty($valid_to)) {
                $ts = strtotime($valid_to . ' +30 days');
                if ($ts !== false) { $expires_at = date('Y-m-d H:i:s', $ts); }
            }
            if ($expires_at === null) {
                $expires_at = date('Y-m-d H:i:s', strtotime("+{$ttl_days} days"));
            }

            Class_db::getInstance()->db_update('ptw_permit', array(
                'public_token' => $token,
                'public_token_expires_at' => $expires_at,
                'public_link_enabled' => '1',
                'public_token_revoked_at' => null,
                'updated_date' => date('Y-m-d H:i:s')
            ), array('ptw_permit_id' => strval($permit_id)));

            return $token;
        } catch (Exception $ex) {
            // Likely columns missing; log and continue without blocking
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'issue_public_token error: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Get an existing public token; if missing, issue a new one with default expiry.
     * @param int $permit_id
     * @return string|null
     */
    public function get_or_create_public_token($permit_id) {
        try {
            $row = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => strval($permit_id)));
            if (!$row) { return null; }
            if (!empty($row['public_token'])) { return $row['public_token']; }
            $valid_to = isset($row['ptw_valid_to']) ? $row['ptw_valid_to'] : null;
            return $this->issue_public_token($permit_id, $valid_to);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'get_or_create_public_token error: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Update PTW permit
     * @param int $permit_id
     * @param array $update_data
     * @return bool
     * @throws Exception
     */
    public function update_permit($permit_id, $update_data) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($permit_id, $update_data));
            
            Class_db::getInstance()->db_update('ptw_permit', $update_data, array('ptw_permit_id' => strval($permit_id)));
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'PTW permit updated: ' . $permit_id);
            
            return true;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0009', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Delete PTW permit
     * @param int $permit_id
     * @return bool
     * @throws Exception
     */
    public function delete_permit($permit_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($permit_id));
            
            // Delete related records first (cascade should handle this, but being explicit)
            Class_db::getInstance()->db_delete('ptw_worker', array('ptw_permit_id' => strval($permit_id)));
            Class_db::getInstance()->db_delete('ptw_document', array('ptw_permit_id' => strval($permit_id)));
            Class_db::getInstance()->db_delete('ptw_approval_log', array('ptw_permit_id' => strval($permit_id)));
            
            // Delete permit
            Class_db::getInstance()->db_delete('ptw_permit', array('ptw_permit_id' => strval($permit_id)));
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'PTW permit deleted: ' . $permit_id);
            
            return true;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0010', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Add worker to PTW permit
     * @param array $worker_data
     * @return int worker_id
     * @throws Exception
     */
    public function add_worker($worker_data) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($worker_data));
            
            $worker_id = Class_db::getInstance()->db_insert('ptw_worker', $worker_data);
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'PTW worker added with ID: ' . $worker_id);
            
            return $worker_id;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0011', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Submit permit for approval
     * @param int $permit_id
     * @param int $user_id
     * @return bool
     * @throws Exception
     */
    public function submit_for_approval($permit_id, $user_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($permit_id, $user_id));
            
            // Ensure request number is assigned at first submission
            try {
                $permit = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => strval($permit_id)));
                if ($permit) {
                    $this->assign_request_number($permit_id, $permit['site_id'], $user_id);
                }
            } catch (Exception $e) {
                // Do not block submission on numbering failure; log and continue
                $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'assign_request_number failed: ' . $e->getMessage());
            }

            // Update permit status
            $update_data = array(
                'ptw_status' => 'PENDING_SUPERVISOR',
                'updated_by' => $user_id,
                'updated_date' => date('Y-m-d H:i:s')
            );
            
            Class_db::getInstance()->db_update('ptw_permit', $update_data, array('ptw_permit_id' => strval($permit_id)));
            
            // Log approval request
            $log_data = array(
                'ptw_permit_id' => $permit_id,
                'approval_type' => 'SUPERVISOR',
                'previous_status' => 'DRAFT',
                'new_status' => 'PENDING_SUPERVISOR',
                'remarks' => 'Submitted for supervisor approval',
                'approved_by' => $user_id,
                'approved_date' => date('Y-m-d H:i:s')
            );
            
            Class_db::getInstance()->db_insert('ptw_approval_log', $log_data);
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'PTW permit submitted for approval: ' . $permit_id);
            
            return true;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0012', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Approve PTW permit
     * @param int $permit_id
     * @param string $approval_type (SUPERVISOR, SHE, FM)
     * @param int $user_id
     * @param string $remarks
     * @return bool
     * @throws Exception
     */
    public function approve_permit($permit_id, $approval_type, $user_id, $remarks = '') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($permit_id, $approval_type, $user_id));
            
            // Get current permit status
            $permit = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => strval($permit_id)));
            if (!$permit) {
                throw new Exception('PTW permit not found');
            }
            
            $current_status = $permit['ptw_status'];
            $new_status = '';
            $approval_field = '';
            $approval_date_field = '';
            
            // Determine next status and fields to update
            switch ($approval_type) {
                case 'SUPERVISOR':
                    if ($current_status != 'PENDING_SUPERVISOR') {
                        throw new Exception('Permit is not pending supervisor approval');
                    }
                    $new_status = 'PENDING_SHE';
                    $approval_field = 'approved_supervisor_by';
                    $approval_date_field = 'approved_supervisor_date';
                    break;
                    
                case 'SHE':
                    if ($current_status != 'PENDING_SHE') {
                        throw new Exception('Permit is not pending SHE approval');
                    }
                    $new_status = 'PENDING_FM';
                    $approval_field = 'approved_she_by';
                    $approval_date_field = 'approved_she_date';
                    break;
                    
                case 'FM':
                    if ($current_status != 'PENDING_FM') {
                        throw new Exception('Permit is not pending FM approval');
                    }
                    $new_status = 'APPROVED';
                    $approval_field = 'approved_fm_by';
                    $approval_date_field = 'approved_fm_date';
                    break;
                    
                default:
                    throw new Exception('Invalid approval type');
            }
            
            // Update permit
            $update_data = array(
                'ptw_status' => $new_status,
                $approval_field => $user_id,
                $approval_date_field => date('Y-m-d H:i:s'),
                'updated_by' => $user_id,
                'updated_date' => date('Y-m-d H:i:s')
            );
            
            Class_db::getInstance()->db_update('ptw_permit', $update_data, array('ptw_permit_id' => strval($permit_id)));
            
            // Log approval
            $log_data = array(
                'ptw_permit_id' => $permit_id,
                'approval_type' => $approval_type,
                'previous_status' => $current_status,
                'new_status' => $new_status,
                'remarks' => $remarks,
                'approved_by' => $user_id,
                'approved_date' => date('Y-m-d H:i:s')
            );
            
            Class_db::getInstance()->db_insert('ptw_approval_log', $log_data);
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "PTW permit approved by {$approval_type}: {$permit_id}");
            
            return true;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0013', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get PTW permits pending SHE approval
     * @param int $site_id
     * @return array
     * @throws Exception
     */
    public function get_permits_for_she_approval($site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Getting permits for SHE approval for site: {$site_id}");
            
            // Get permits with PENDING_SHE status
            $permits = Class_db::getInstance()->db_select('ptw_permit', array(
                'ptw_status' => 'PENDING_SHE',
                'site_id' => strval($site_id)
            ));
            
            return $permits;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0014', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get recent SHE actions/approvals
     * @param int $user_id
     * @param int $site_id
     * @return array
     * @throws Exception
     */
    public function get_she_recent_actions($user_id, $site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Getting recent SHE actions for user: {$user_id}, site: {$site_id}");
            
            // Get permits that have been processed by SHE
            $actions = Class_db::getInstance()->db_select('ptw_permit', array(
                'site_id' => strval($site_id)
            ));
            
            // Filter to only include permits with SHE action in the last 30 days
            $recent_actions = array();
            foreach ($actions as $action) {
                if (!empty($action['approved_she_date'])) {
                    $action_date = strtotime($action['approved_she_date']);
                    $thirty_days_ago = strtotime('-30 days');
                    
                    if ($action_date >= $thirty_days_ago) {
                        $action['action_date'] = $action['approved_she_date'];
                        $action['action_type'] = (in_array($action['ptw_status'], array('CANCELLED','REJECTED'))) ? 'SHE_REJECTED' : 'SHE_APPROVED';
                        $action['remarks'] = '';
                        $recent_actions[] = $action;
                    }
                }
            }
            
            // Sort by date descending and limit to 20
            usort($recent_actions, function($a, $b) {
                return strtotime($b['action_date']) - strtotime($a['action_date']);
            });
            
            return array_slice($recent_actions, 0, 20);
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0015', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get summary statistics for SHE dashboard
     * @param int $user_id
     * @param int $site_id
     * @return array
     * @throws Exception
     */
    public function get_she_summary_statistics($user_id, $site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Getting SHE summary stats for user: {$user_id}, site: {$site_id}");
            
            $stats = array();
            
            // Get all permits for the site
            $all_permits = Class_db::getInstance()->db_select('ptw_permit', array('site_id' => strval($site_id)));
            
            // Count by status
            $pending = 0;
            $approved = 0;
            $rejected = 0;
            
            $thirty_days_ago = strtotime('-30 days');
            
            foreach ($all_permits as $permit) {
                switch ($permit['ptw_status']) {
                    case 'PENDING_SHE':
                        $pending++;
                        break;
                    case 'PENDING_FM':
                    case 'APPROVED':
                    case 'ACTIVE':
                        // Count as approved if SHE approved in last 30 days
                        if (!empty($permit['approved_she_date'])) {
                            $approved_date = strtotime($permit['approved_she_date']);
                            if ($approved_date >= $thirty_days_ago) {
                                $approved++;
                            }
                        }
                        break;
                    case 'CANCELLED':
                        // Count as rejected if in last 30 days and rejected by SHE
                        if (!empty($permit['approved_she_date'])) {
                            $rejected_date = strtotime($permit['approved_she_date']);
                            if ($rejected_date >= $thirty_days_ago) {
                                $rejected++;
                            }
                        }
                        break;
                }
            }
            
            $stats['pending'] = $pending;
            $stats['approved'] = $approved;
            $stats['rejected'] = $rejected;
            $stats['total'] = $approved + $rejected;
            
            return $stats;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0016', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Send PTW notification
     * @param int $permit_id
     * @param string $notification_type
     * @return bool
     * @throws Exception
     */
    public function send_ptw_notification($permit_id, $notification_type) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Sending PTW notification: ' . $notification_type . ' for permit: ' . $permit_id);
            
            // Get permit details
            $permit = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => strval($permit_id)));
            if (!$permit) {
                throw new Exception('PTW permit not found');
            }
            
            // Choose best available reference number
            $refNo = isset($permit['ptw_permit_number']) && $permit['ptw_permit_number'] !== ''
                ? $permit['ptw_permit_number']
                : (isset($permit['ptw_request_number']) ? $permit['ptw_request_number'] : '');
            // Log notification (simple logging for now)
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Notification sent: {$notification_type} for PTW {$refNo}");
            
            // In a complete implementation, this would send actual emails/SMS
            // For now, we just log the notification
            return true;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            return false;
        }
    }

    /**
     * Get permits pending FM approval
     * @param int $user_id
     * @param int $site_id
     * @return array
     * @throws Exception
     */
    public function get_permits_for_fm_approval($user_id, $site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Getting FM approval permits for user_id: $user_id, site_id: $site_id");
            
            // Get permits that have been approved by SHE but not yet by FM
            $permits = Class_db::getInstance()->db_select('ptw_permit', array(
                'site_id' => strval($site_id),
                'approved_she_by' => 'is not NULL', // SHE has approved
                'approved_fm_by' => 'is NULL', // FM has not approved yet
                'ptw_status' => 'PENDING_FM'
            ), 'ptw_permit_id DESC');
            
            return $permits ? $permits : array();
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0017', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get permits that have extension requests flagged
     * Criteria: ptw_status in ('ACTIVE','SUSPENDED','PENDING_EXTENSION') and any extension request fields populated
     * @param int $site_id
     * @return array
     * @throws Exception
     */
    public function get_extension_requests($site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Getting extension requests for site: {$site_id}");

            // Fetch eligible permits with possible extension markers
            $statuses = array('ACTIVE','SUSPENDED','PENDING_EXTENSION');
            $permits = array();
            foreach ($statuses as $st) {
                $list = Class_db::getInstance()->db_select('ptw_permit', array(
                    'site_id' => strval($site_id),
                    'ptw_status' => $st
                ), 'ptw_permit_id DESC');
                if ($list && is_array($list)) { $permits = array_merge($permits, $list); }
            }

            // Filter to only those with extension markers present
            $result = array();
            if ($permits) {
                foreach ($permits as $p) {
                    $has_request = (
                        (isset($p['ptw_extension_requested_to']) && !empty($p['ptw_extension_requested_to'])) ||
                        (isset($p['ptw_extension_requested_by']) && !empty($p['ptw_extension_requested_by'])) ||
                        (isset($p['ptw_extension_requested_remarks']) && !empty($p['ptw_extension_requested_remarks'])) ||
                        (isset($p['ptw_extension_requested_at']) && !empty($p['ptw_extension_requested_at']))
                    );
                    if ($has_request) { $result[] = $p; }
                }
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0017', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get FM summary statistics
     * @param int $user_id
     * @param int $site_id
     * @return array
     * @throws Exception
     */
    public function get_fm_summary_statistics($user_id, $site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Getting FM summary statistics for user_id: $user_id, site_id: $site_id");
            
            $stats = array(
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            );
            
            // Get permits that need FM approval
            $pending_permits = Class_db::getInstance()->db_select('ptw_permit', array(
                'site_id' => strval($site_id),
                'approved_she_by' => 'is not NULL',
                'approved_fm_by' => 'is NULL',
                'ptw_status' => 'PENDING_FM'
            ));
            $stats['pending'] = $pending_permits ? count($pending_permits) : 0;
            
            // Get permits approved/rejected by FM in last 30 days
            $thirty_days_ago = strtotime('-30 days');
            $all_permits = Class_db::getInstance()->db_select('ptw_permit', array(
                'site_id' => strval($site_id),
                'approved_fm_by' => 'is not NULL'
            ));
            
            $approved = 0;
            $rejected = 0;
            
            if ($all_permits) {
                foreach ($all_permits as $permit) {
                    switch ($permit['ptw_status']) {
                        case 'APPROVED':
                        case 'ACTIVE':
                        case 'COMPLETED':
                            // Count as approved if approved by FM in last 30 days
                            if (!empty($permit['approved_fm_by'])) {
                                $approved_date = strtotime($permit['approved_fm_by']);
                                if ($approved_date >= $thirty_days_ago) {
                                    $approved++;
                                }
                            }
                            break;
                        case 'CANCELLED':
                            // Count as rejected if rejected by FM in last 30 days
                            if (!empty($permit['approved_fm_by'])) {
                                $rejected_date = strtotime($permit['approved_fm_by']);
                                if ($rejected_date >= $thirty_days_ago) {
                                    $rejected++;
                                }
                            }
                            break;
                    }
                }
            }
            
            $stats['approved'] = $approved;
            $stats['rejected'] = $rejected;
            $stats['total'] = $approved + $rejected;
            
            return $stats;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0018', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * List permits pending cancellation for FM
     * @param int $site_id
     * @return array
     * @throws Exception
     */
    public function get_cancellation_requests($site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Getting cancellation requests for site: {$site_id}");
            return Class_db::getInstance()->db_select('ptw_permit', array(
                'site_id' => strval($site_id),
                'ptw_status' => 'PENDING_CANCELLATION'
            ), 'ptw_permit_id DESC') ?: array();
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0019', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * List permits pending suspension for FM
     * @param int $site_id
     * @return array
     * @throws Exception
     */
    public function get_suspension_requests($site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Getting suspension requests for site: {$site_id}");
            return Class_db::getInstance()->db_select('ptw_permit', array(
                'site_id' => strval($site_id),
                'ptw_status' => 'PENDING_SUSPENSION'
            ), 'ptw_permit_id DESC') ?: array();
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0020', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * List suspended permits for FM view
     * @param int $site_id
     * @return array
     * @throws Exception
     */
    public function get_suspended_permits($site_id) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Getting suspended permits for site: {$site_id}");
            return Class_db::getInstance()->db_select('ptw_permit', array(
                'site_id' => strval($site_id),
                'ptw_status' => 'SUSPENDED'
            ), 'ptw_permit_id DESC') ?: array();
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0021', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}

?>
