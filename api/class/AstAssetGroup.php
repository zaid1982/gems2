<?php

class AstAssetGroup extends General {

    public $assetGroupId = 0;
    private static $tableName = 'ast_asset_group';

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
     * @return array
     * @throws Exception
     */
    public function getRefRequestMobile (int $storeId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($storeId, 'storeId');
            return DbMysql::selectSqlAll(/** @lang text */
                    "SELECT 
                        pt.asset_group_id,
                        ag.asset_group_name
                    FROM ast_part pt
                    LEFT JOIN ast_asset_group ag ON ag.asset_group_id = pt.asset_group_id",
                array('pt.storeId'=>$storeId, 'ag.assetGroupStatus'=>1), 0, false, 'assetGroupName', '', '', 'assetGroupId');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}
