<?php

class WoTaskParts extends General {

    public $woTaskPartsId = 0;
    private static $tableName = 'wo_task_parts';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $woTaskPartsId
     * @return array
     * @throws Exception
     */
    public function get (int $woTaskPartsId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskPartsId, 'woTaskPartsId');
            $this->woTaskPartsId = $woTaskPartsId;
            return DbMysql::select($this::$tableName, array('woTaskPartsId'=>$woTaskPartsId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskRequestId
     * @return array
     * @throws Exception
     */
    public function getListMobile (int $woTaskRequestId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskRequestId, 'woTaskRequestId');
            return DbMysql::selectSqlAll(
            /** @lang text */
                "SELECT 
                        tp.wo_task_parts_id,
                        ag.asset_group_name,
                        it.item_type_desc,
                        im.item_description,
                        tp.wo_task_parts_quantity,
                        st.status_desc,
                        st.status_color_code		
                    FROM wo_task_parts tp
                    LEFT JOIN ast_part pt ON pt.part_id = tp.part_id
                    LEFT JOIN ast_asset_group ag ON ag.asset_group_id = pt.asset_group_id
                    LEFT JOIN ref_item_type it ON it.item_type_id = pt.item_type_id
                    LEFT JOIN ref_item im ON im.item_id = pt.item_id
                    LEFT JOIN ref_status st ON st.status_id = tp.wo_task_parts_status",
                array('tp.woTaskRequestId'=>$woTaskRequestId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskPartsId
     * @return array
     * @throws Exception
     */
    public function getDetailsMobile (int $woTaskPartsId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskPartsId, 'woTaskPartsId');
            $arrDetails = array();
            $woTaskParts = DbMysql::select($this::$tableName, array('woTaskPartsId'=>$woTaskPartsId), 1);
            $astPart = DbMysql::select('ast_part', array('partId'=>$woTaskParts['partId']), 1);
            $arrDetails['woTaskPartsId'] = $woTaskPartsId;
            $arrDetails['assetGroupId'] = $astPart['assetGroupId'];
            $arrDetails['itemTypeId'] = $astPart['itemTypeId'];
            $arrDetails['partId'] = $woTaskParts['partId'];
            $arrDetails['woTaskPartsQuantity'] = $woTaskParts['woTaskPartsQuantity'];
            $arrDetails['woTaskPartsRemark'] = $woTaskParts['woTaskPartsRemark'];
            $arrDetails['status'] = DbMysql::selectColumn('ref_status', array('statusId'=>$woTaskParts['woTaskPartsStatus']), 'statusDesc', true);
            return $arrDetails;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskRequestId
     * @param array $inputParams
     * @return int
     * @throws Exception
     */
    public function insert (int $woTaskRequestId, array $inputParams): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskRequestId, 'woTaskRequestId');
            $params = parent::arraySpliceAssoc($inputParams, array('partId', 'woTaskPartsQuantity', 'woTaskPartsRemark'));
            parent::checkMandatoryArray($params, array('partId', 'woTaskPartsQuantity'), true);
            $params['woTaskRequestId'] = $woTaskRequestId;
            $params['woTaskPartsStatus'] = 32;
            $woTaskRequest = DbMysql::select('wo_task_request', array('woTaskRequestId'=>$woTaskRequestId), true);
            if ($woTaskRequest['woTaskRequestStatus'] !== 32) {
                throw new Exception(str_replace('__', $woTaskRequest['woTaskRequestNo'], Constant::$woTaskParts['errRequestAlreadySubmitted']), 31);
            } else if ($woTaskRequest['woTaskRequestOrderBy'] !== $this->userId) {
                throw new Exception(Constant::$woTaskParts['errNotAllowed'], 31);
            }
            if (DbMysql::count($this::$tableName, array('woTaskRequestId'=>$woTaskRequestId, 'partId'=>$params['partId'])) > 0) {
                $itemId = DbMysql::selectColumn('ast_part', array('partId'=>$params['partId']), 'itemId', true);
                $itemDescription = DbMysql::selectColumn('ref_item', array('itemId'=>$itemId), 'itemDescription', true);
                throw new Exception(str_replace('__', $itemDescription, Constant::$woTaskParts['errAlreadyExist']), 31);
            }
            return DbMysql::insert($this::$tableName, $params);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskPartsId
     * @param array $inputParams
     * @throws Exception
     */
    public function update (int $woTaskPartsId, array $inputParams): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskPartsId, 'woTaskPartsId');
            parent::checkEmptyInteger($this->userId, 'userId');
            $params = $this->arraySpliceAssoc($inputParams, array('woTaskPartsQuantity', 'woTaskPartsRemark'));
            parent::checkMandatoryArray($params, array('woTaskPartsQuantity'), true);
            $woTaskPart = $this->get($woTaskPartsId);
            if ($woTaskPart['woTaskPartsStatus'] !== 32) {
                $itemId = DbMysql::selectColumn('ast_part', array('partId'=>$woTaskPart['partId']), 'itemId', true);
                $itemDescription = DbMysql::selectColumn('ref_item', array('itemId'=>$itemId), 'itemDescription', true);
                throw new Exception(str_replace('__', $itemDescription, Constant::$woTaskParts['errAlreadySubmitted']), 31);
            }
            $woTaskRequest = DbMysql::select('wo_task_request', array('woTaskRequestId'=>$woTaskPart['woTaskRequestId']), true);
            if ($woTaskRequest['woTaskRequestStatus'] !== 32) {
                throw new Exception(str_replace('__', $woTaskRequest['woTaskRequestNo'], Constant::$woTaskParts['errRequestAlreadySubmitted']), 31);
            } else if ($woTaskRequest['woTaskRequestOrderBy'] !== $this->userId) {
                throw new Exception(Constant::$woTaskParts['errNotAllowed'], 31);
            }
            DbMysql::update($this::$tableName, $params, array('woTaskPartsId'=>$woTaskPartsId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskPartsId
     * @throws Exception
     */
    public function delete (int $woTaskPartsId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskPartsId, 'woTaskPartsId');
            parent::checkEmptyInteger($this->userId, 'userId');
            $woTaskPart = $this->get($woTaskPartsId);
            if ($woTaskPart['woTaskPartsStatus'] !== 32) {
                $itemId = DbMysql::selectColumn('ast_part', array('partId'=>$woTaskPart['partId']), 'itemId', true);
                $itemDescription = DbMysql::selectColumn('ref_item', array('itemId'=>$itemId), 'itemDescription', true);
                throw new Exception(str_replace('__', $itemDescription, Constant::$woTaskParts['errAlreadySubmitted']), 31);
            }
            $woTaskRequest = DbMysql::select('wo_task_request', array('woTaskRequestId'=>$woTaskPart['woTaskRequestId']), true);
            if ($woTaskRequest['woTaskRequestStatus'] !== 32) {
                throw new Exception(str_replace('__', $woTaskRequest['woTaskRequestNo'], Constant::$woTaskParts['errRequestAlreadySubmitted']), 31);
            } else if ($woTaskRequest['woTaskRequestOrderBy'] !== $this->userId) {
                throw new Exception(Constant::$woTaskParts['errNotAllowed'], 31);
            }
            DbMysql::delete($this::$tableName, array('woTaskPartsId'=>$woTaskPartsId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }
}