<?php

class PpmTask extends General {

    private static $tableName = 'ppm_task';
    private static $idName = 'ppmTaskId';

    function __construct(int $userId = 0, bool $isLogged = false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function get (int $id): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($id, 'id');
            return DbMysql::select($this::$tableName, array($this::$idName=>$id), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $ppmId
     * @return array
     * @throws Exception
     */
    public function getList (int $ppmId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($ppmId, 'ppmId');
            return DbMysql::selectAll($this::$tableName, array('ppmId'=>$ppmId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $assetId
     * @return array
     * @throws Exception
     */
    public function getListByAsset (int $assetId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($assetId, 'assetId');
            $ppmTaskArr = array();
            $ppmArr = DbMysql::selectAll('ppm', array('assetId'=>$assetId));
            foreach ($ppmArr as $ppm) {
                $ppmTaskArr2 = DbMysql::selectAll($this::$tableName, array('ppmId'=>$ppm['ppmId']));
                foreach ($ppmTaskArr2 as $ppmTask2) {
                    $ppmTaskArr[] = $ppmTask2;
                }
            }
            $ppmAssetArr = DbMysql::selectAll('ppm_asset', array('assetId'=>$assetId));
            foreach ($ppmAssetArr as $ppmAsset) {
                $ppmTaskArr2 = DbMysql::selectAll($this::$tableName, array('ppmId'=>$ppmAsset['ppmId']));
                foreach ($ppmTaskArr2 as $ppmTask2) {
                    $ppmTaskArr[] = $ppmTask2;
                }
            }
            return $ppmTaskArr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return int
     * @throws Exception
     */
    public function insert (array $columns): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return DbMysql::insert($this::$tableName, $columns);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $id
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function update (int $id, array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($id, $this::$idName);
            DbMysql::update($this::$tableName, $columns, array($this::$idName=>$id));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function delete (int $id): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($id, $this::$idName);
            DbMysql::delete($this::$tableName, array($this::$idName=>$id));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}