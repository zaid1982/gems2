<?php

class RefItemType extends General {

    public int $itemTypeId = 0;
    private static string $tableName = 'ref_item_type';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRef (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return DbMysql::selectAll($this::$tableName, array(), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $storeId
     * @param int $assetGroupId
     * @return array
     * @throws Exception
     */
    public function getRefRequestMobile (int $storeId, int $assetGroupId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($storeId, 'storeId');
            parent::checkEmptyInteger($assetGroupId, 'assetGroupId');
            return DbMysql::selectSqlAll(/** @lang text */
                    "SELECT 
                        pt.item_type_id,
                        it.item_type_desc
                    FROM ast_part pt
                    LEFT JOIN ref_item_type it ON it.item_type_id = pt.item_type_id",
                array('pt.storeId'=>$storeId, 'pt.assetGroupId'=>$assetGroupId, 'it.itemTypeStatus'=>1), 0, false, 'itemTypeDesc', '', '', 'itemTypeId');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

}