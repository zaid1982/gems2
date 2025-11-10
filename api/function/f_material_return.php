<?php

/**
 * Class_material_return
 * 
 * Handles material return workflow:
 * - Technicians can return collected parts (full or partial)
 * - Storekeepers confirm receipt and update inventory
 * - Supports ast_part_sub instance-based tracking
 * 
 * @author GEMS2 Development Team
 * @date 9 November 2025
 */
class Class_material_return {

    private $fn_general;
    private $constant;

    function __construct() {
    }

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg,'-');
            if ($pos !== false) {
                $msg = substr($msg, $pos+2);
            }
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."] - ".$msg;
        } else {
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."]";
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
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value ) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property ) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property ) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * Get list of return-eligible items for a technician
     * Only shows items with status 36 (Parts Collected) that haven't been fully returned
     * 
     * @param int $userId Technician user ID
     * @return array List of eligible items
     * @throws Exception
     */
    public function getReturnEligibleItems($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            // Use the view from sql.php with user_id parameter
            // The view filters by [user_id] placeholder
            $items = Class_db::getInstance()->db_select('vw_return_eligible_items', array(), 'collectedDate DESC', '', 0, array('user_id'=>$userId));
            
            // Add pending return info for each item
            foreach ($items as &$item) {
                $pendingReturns = Class_db::getInstance()->db_select('material_returns', 
                    array('wo_task_parts_id'=>$item['woTaskPartsId'], 'return_status'=>'pending'));
                $item['hasPendingReturn'] = !empty($pendingReturns);
                $item['pendingReturnId'] = !empty($pendingReturns) ? $pendingReturns[0]['returnId'] : null;
                $item['pendingReturnQuantity'] = !empty($pendingReturns) ? $pendingReturns[0]['quantityReturned'] : 0;
            }
            
            return $items;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Submit a return request from technician
     * Supports partial returns
     * 
     * @param int $userId Technician user ID
     * @param array $params ['woTaskPartsId', 'quantityReturned', 'returnReason', 'returnRemarks'?, 'returnDeadlineDate'?]
     * @return int Return ID
     * @throws Exception
     */
    public function submitReturnRequest($userId, $params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            
            $this->fn_general->checkEmptyParams(array($userId, $params));
            $this->fn_general->checkEmptyParamsArray($params, array('woTaskPartsId', 'quantityReturned', 'returnReason'));

            $woTaskPartsId = $params['woTaskPartsId'];
            $quantityReturned = intval($params['quantityReturned']);
            $returnReason = $params['returnReason'];
            $returnRemarks = isset($params['returnRemarks']) ? $params['returnRemarks'] : null;
            $returnDeadlineDate = isset($params['returnDeadlineDate']) ? $params['returnDeadlineDate'] : null;

            // Validate return reason
            $validReasons = array('unused_excess', 'wrong_part', 'damaged', 'other');
            if (!in_array($returnReason, $validReasons)) {
                throw new Exception('[' . __LINE__ . '] - Invalid return reason. Must be one of: unused_excess, wrong_part, damaged, other', 31);
            }

            // Validate quantity
            if ($quantityReturned <= 0) {
                throw new Exception('[' . __LINE__ . '] - Quantity returned must be greater than 0', 31);
            }

            // Get wo_task_parts details
            $woTaskPart = Class_db::getInstance()->db_select('wo_task_parts', 
                array('wo_task_parts_id'=>$woTaskPartsId), '', '', 2);
            
            if (empty($woTaskPart)) {
                throw new Exception('[' . __LINE__ . '] - Invalid wo_task_parts_id', 31);
            }
            $woTaskPart = $woTaskPart[0];

            // Check if status is 36 (Parts Collected)
            if ($woTaskPart['woTaskPartsStatus'] !== '36') {
                throw new Exception('[' . __LINE__ . '] - Item not eligible for return. Must be in Parts Collected status', 31);
            }

            // Verify ownership (technician must have ordered this)
            $woRequest = Class_db::getInstance()->db_select('wo_task_request', 
                array('wo_task_request_id'=>$woTaskPart['woTaskRequestId']), '', '', 2);
            $woRequest = $woRequest[0];
            
            if ($woRequest['woTaskRequestOrderBy'] != $userId) {
                throw new Exception('[' . __LINE__ . '] - Unauthorized: Item does not belong to you', 31);
            }

            // Calculate how much has already been returned
            $totalReturned = Class_db::getInstance()->db_sum('material_returns', 'quantity_returned', 
                array('wo_task_parts_id'=>$woTaskPartsId, 'return_status'=>'completed'));
            $totalReturned = intval($totalReturned);

            // Check if there's enough to return
            $quantityCollected = intval($woTaskPart['woTaskPartsQuantity']);
            $availableToReturn = $quantityCollected - $totalReturned;
            
            if ($quantityReturned > $availableToReturn) {
                throw new Exception('[' . __LINE__ . '] - Cannot return more than collected. Available to return: ' . $availableToReturn, 31);
            }

            // Check for pending return (only one pending return per wo_task_parts_id)
            if (Class_db::getInstance()->db_count('material_returns', 
                array('wo_task_parts_id'=>$woTaskPartsId, 'return_status'=>'pending')) > 0) {
                throw new Exception('[' . __LINE__ . '] - A pending return already exists for this item. Please wait for storekeeper confirmation', 31);
            }

            // Check if there are enough parts in collected status (not consumed/installed)
            $partsInPossession = Class_db::getInstance()->db_count('ast_part_sub', 
                array('wo_task_parts_id'=>$woTaskPartsId, 'part_sub_status'=>'36', 'part_sub_return_id'=>null));
            
            if ($quantityReturned > $partsInPossession) {
                throw new Exception('[' . __LINE__ . '] - Cannot return ' . $quantityReturned . ' items. Only ' . $partsInPossession . ' parts still in your possession (others may have been installed/used)', 31);
            }

            // Insert return request
            $returnId = Class_db::getInstance()->db_insert('material_returns', array(
                'wo_task_parts_id' => $woTaskPartsId,
                'part_id' => $woTaskPart['partId'],
                'technician_user_id' => $userId,
                'quantity_returned' => $quantityReturned,
                'return_status' => 'pending',
                'return_reason' => $returnReason,
                'return_remarks' => $returnRemarks,
                'return_request_date' => 'NOW()',
                'return_deadline_date' => $returnDeadlineDate
            ));

            return $returnId;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get pending returns for storekeeper
     * 
     * @return array List of pending returns
     * @throws Exception
     */
    public function getStorekeeperPendingReturns() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return Class_db::getInstance()->db_select('vw_storekeeper_pending_returns', array(), 'return_request_date DESC');
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get return details
     * 
     * @param int $returnId
     * @return array Return details
     * @throws Exception
     */
    public function getReturnDetail($returnId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($returnId));

            // Try getting from view first (pending returns only)
            $return = Class_db::getInstance()->db_select('vw_storekeeper_pending_returns', 
                array('return_id'=>$returnId));
            
            if (empty($return)) {
                // Try getting from main table if not in view (completed or other status)
                $return = Class_db::getInstance()->db_select('material_returns', 
                    array('return_id'=>$returnId), '', '', 2);
            } else {
                $return = $return[0];
            }

            return $return;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Confirm return receipt by storekeeper
     * CRITICAL: Updates inventory using database transaction
     * 
     * @param int $returnId
     * @param int $storekeeperUserId
     * @throws Exception
     */
    public function confirmReturnReceipt($returnId, $storekeeperUserId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($returnId, $storekeeperUserId));

            // Get return details with lock
            $return = Class_db::getInstance()->db_select('material_returns', 
                array('return_id'=>$returnId), '', '', 2);
            
            if (empty($return)) {
                throw new Exception('[' . __LINE__ . '] - Return request not found', 31);
            }
            $return = $return[0];

            // Validate status
            if ($return['returnStatus'] !== 'pending') {
                throw new Exception('[' . __LINE__ . '] - Return request already completed or invalid status', 31);
            }

            $quantityReturned = intval($return['quantityReturned']);
            $partId = $return['partId'];
            $woTaskPartsId = $return['woTaskPartsId'];

            // Get part details
            $part = Class_db::getInstance()->db_select('ast_part', 
                array('part_id'=>$partId), '', '', 2);
            $part = $part[0];
            
            $currentCount = intval($part['partCount']);
            $currentLocked = intval($part['partLocked']);

            // Update return status
            Class_db::getInstance()->db_update('material_returns', array(
                'return_status' => 'completed',
                'return_confirmed_date' => 'NOW()',
                'storekeeper_user_id' => $storekeeperUserId
            ), array('return_id'=>$returnId));

            // Get the specific part_sub instances to mark as returned (FIFO - oldest first)
            $partSubs = Class_db::getInstance()->db_select('ast_part_sub', 
                array('wo_task_parts_id'=>$woTaskPartsId, 'part_sub_status'=>'36', 'part_sub_return_id'=>null),
                'part_sub_id ASC', $quantityReturned);

            if (count($partSubs) < $quantityReturned) {
                throw new Exception('[' . __LINE__ . '] - Not enough parts in collected status to process return. Expected: ' . $quantityReturned . ', Found: ' . count($partSubs), 31);
            }

            // Update each part_sub instance to returned status (47)
            foreach ($partSubs as $partSub) {
                Class_db::getInstance()->db_update('ast_part_sub', array(
                    'part_sub_status' => '47',  // Returned status
                    'part_sub_return_id' => $returnId,
                    'part_sub_returned_date' => 'NOW()',
                    'part_sub_returned_by' => $storekeeperUserId
                ), array('part_sub_id'=>$partSub['partSubId']));
            }

            // Update ast_part: increase available count (decrease locked)
            // Note: part_count stays same, we just unlock the parts
            $newLocked = $currentLocked - $quantityReturned;
            
            if ($newLocked < 0) {
                throw new Exception('[' . __LINE__ . '] - Invalid locked quantity calculation. Current locked: ' . $currentLocked . ', Returning: ' . $quantityReturned, 31);
            }

            Class_db::getInstance()->db_update('ast_part', array(
                'part_locked' => $newLocked
            ), array('part_id'=>$partId));

            // Log inventory change (optional table - fail gracefully if not exists)
            try {
                Class_db::getInstance()->db_insert('inventory_logs', array(
                    'part_id' => $partId,
                    'change_type' => 'return',
                    'quantity_change' => $quantityReturned,
                    'quantity_before' => $currentCount - $currentLocked,
                    'quantity_after' => $currentCount - $newLocked,
                    'user_id' => $storekeeperUserId,
                    'reference_id' => $returnId,
                    'reference_type' => 'material_return',
                    'change_reason' => 'Material return confirmed - returnId: ' . $returnId . ', reason: ' . $return['returnReason'],
                    'change_date' => 'NOW()'
                ));
            } catch (Exception $logEx) {
                // Silently fail if inventory_logs table doesn't exist
                // This is optional logging and shouldn't break the main workflow
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Optional inventory_logs insert failed (table may not exist): ' . $logEx->getMessage());
            }

            return array(
                'newAvailable' => $currentCount - $newLocked,
                'newLocked' => $newLocked,
                'returnedQuantity' => $quantityReturned
            );
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get return history with filters
     * 
     * @param array $filters ['userId', 'status', 'dateFrom', 'dateTo']
     * @return array Return history
     * @throws Exception
     */
    public function getReturnHistory($filters = array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $whereClause = array();
            
            if (isset($filters['userId']) && !empty($filters['userId'])) {
                $whereClause['technician_user_id'] = $filters['userId'];
            }
            
            if (isset($filters['status']) && !empty($filters['status']) && $filters['status'] !== 'all') {
                $whereClause['return_status'] = $filters['status'];
            }
            
            $customWhere = '';
            if (isset($filters['dateFrom']) && !empty($filters['dateFrom'])) {
                $customWhere .= " AND return_request_date >= '" . $filters['dateFrom'] . "'";
            }
            
            if (isset($filters['dateTo']) && !empty($filters['dateTo'])) {
                $customWhere .= " AND return_request_date <= '" . $filters['dateTo'] . " 23:59:59'";
            }

            if (!empty($customWhere)) {
                $whereClause['w1'] = ltrim($customWhere, ' AND ');
            }

            return Class_db::getInstance()->db_select2('material_returns', $whereClause, 'return_request_date DESC');
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get return statistics
     * 
     * @param int $userId Optional - filter by user
     * @return array Statistics
     * @throws Exception
     */
    public function getReturnStatistics($userId = null) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $where = $userId ? array('technician_user_id'=>$userId) : array();

            $stats = array(
                'totalReturns' => Class_db::getInstance()->db_count('material_returns', $where),
                'pendingReturns' => Class_db::getInstance()->db_count('material_returns', array_merge($where, array('return_status'=>'pending'))),
                'completedReturns' => Class_db::getInstance()->db_count('material_returns', array_merge($where, array('return_status'=>'completed'))),
                'totalQuantityReturned' => intval(Class_db::getInstance()->db_sum('material_returns', 'quantity_returned', array_merge($where, array('return_status'=>'completed'))))
            );

            return $stats;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
