<?php

class AstPart extends General {

    public $partId = 0;
    private static $tableName = 'ast_part';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $partId
     * @return array
     * @throws Exception
     */
    public function get (int $partId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($partId, 'partId');
            $this->partId = $partId;
            return DbMysql::select($this::$tableName, array('partId'=>$partId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $partId
     * @return string
     * @throws Exception
     */
    public function getItemDescription (int $partId): string {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($partId, 'partId');
            $astPart = $this->get($partId);
            return DbMysql::selectColumn('ref_item', array('itemId'=>$astPart['itemId']), 'itemDescription', true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $storeId
     * @param int $itemTypeId
     * @return array
     * @throws Exception
     */
    public function getRefRequestMobile (int $storeId, int $itemTypeId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($storeId, 'storeId');
            parent::checkEmptyInteger($itemTypeId, 'itemTypeId');
            return DbMysql::selectSqlAll(/** @lang text */
                "SELECT 
                        pt.part_id,
                        CONCAT(im.item_description, ' - ', pt.part_count - pt.part_locked) AS item_description   
                    FROM ast_part pt
                    LEFT JOIN ref_item im ON im.item_id = pt.item_id",
                array('pt.storeId'=>$storeId, 'pt.itemTypeId'=>$itemTypeId, 'im.itemStatus'=>1), 0, false, 'itemDescription');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}