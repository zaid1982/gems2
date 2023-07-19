<?php

class RefItem extends General {
    
    public $itemId = 0;
    private static $tableName = 'ref_item';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param bool $isMobile
     * @param int $itemTypeId
     * @return array
     * @throws Exception
     */
    public function getRef (bool $isMobile = false, int $itemTypeId = 0): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if ($isMobile) {
                parent::checkEmptyInteger($itemTypeId, 'itemTypeId');
                return DbMysql::selectAll($this::$tableName, array('itemTypeId'=>$itemTypeId, 'itemStatus'=>1), 0, false, 'itemTurn');
            } else {
                return DbMysql::selectAll($this::$tableName, array(), 1);
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}