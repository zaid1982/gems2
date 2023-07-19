<?php

class RefItemType extends General {

    public $itemTypeId = 0;
    private static $tableName = 'ref_item_type';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param bool $isMobile
     * @param int $assetGroupId
     * @return array
     * @throws Exception
     */
    public function getRef (bool $isMobile = false, int $assetGroupId = 0): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if ($isMobile) {
                parent::checkEmptyInteger($assetGroupId, 'assetGroupId');
                return DbMysql::selectAll($this::$tableName, array('assetGroupId'=>$assetGroupId, 'itemTypeStatus'=>1), 0, false, 'itemTypeTurn');
            } else {
                return DbMysql::selectAll($this::$tableName, array(), 1);
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

}