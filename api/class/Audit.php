<?php


class Audit extends General {
    function __construct() {
    }

    /**
     * @throws Exception
     */
    public function insertAudit () {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            //$ipaddress = self::getIpAddress();
        } catch (Exception|Throwable $ex) {
            $this->logError(__CLASS__,__FUNCTION__, $ex->getLine(), $ex->getMessage());
            throw new Exception($ex->getMessage(), $ex->getCode());
        }
    }
}