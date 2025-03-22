<?php

class AstAsset extends General {

    private static $tableName = 'ast_asset';
    private static $idName = 'assetId';

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
     * @param int $id
     * @throws Exception
     */
    public function getRepairCost (int $id) {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($id, 'id');
            return DbMysql::selectSql(
                /** @lang text */
                "SELECT
                    SUM(wp.wo_task_parts_quantity * asb.part_sub_cost) AS total_cost
                FROM wo_task_request wr
                LEFT JOIN wo_task wo ON wo.wo_task_id = wr.wo_task_id
                LEFT JOIN wo_task_parts wp ON wp.wo_task_request_id = wr.wo_task_request_id
                LEFT JOIN ast_part_sub asb ON asb.wo_task_parts_id = wp.wo_task_parts_id
                WHERE wo.asset_id = $id AND asb.part_sub_status IN (36, 37)")['totalCost'];
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