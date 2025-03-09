<?php

class Ppm extends General {

    private static $tableName = 'ppm';
    private static $idName = 'ppmId';

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
     * @param int $contractId
     * @return array
     * @throws Exception
     */
    public function getListPpmGroup (int $contractId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($contractId, 'contractId');
            return DbMysql::selectSqlAll( /** @lang text */
                "SELECT 
                    ppm.*,
                    COUNT(ppt.ppm_task_id) AS total_task,
                    COUNT(ast.ppm_asset_id) AS total_asset
                FROM ppm
                LEFT JOIN ppm_task ppt ON ppt.ppm_id = ppm.asset_id
                LEFT JOIN ppm_asset ast ON ast.ppm_id = ppm.ppm_id
                WHERE ppm.ppm_is_group = 1 AND contract_id = $contractId
                GROUP BY ppm.ppm_id");
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return int
     * @throws Exception
     */
    public function insertGroupAsset (array $columns): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (!isset($columns['checklistId'])) {
                throw new Exception('Parameter checklistId not exist');
            }
            $ppmChecklist = DbMysql::select('ppm_checklist', array('checklistId'=>$columns['checklistId']), true);
            $columns['ppmTaskNo'] = $ppmChecklist['checklistDocumentNo'];
            $columns['ppmIssueNo'] = $ppmChecklist['checklistIssueNo'];
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