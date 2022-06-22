<?php

class FcaDefectCategory extends General {

    public $fcaDefectCategoryId = 0;
    public $fcaDefectCategoryName = '';

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
            return DbMysql::selectAll('fca_defect_category', array(), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getList(): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            return DbMysql::selectAll('fca_defect_category', array());
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $fcaDefectCategoryId
     * @return array
     * @throws Exception
     */
    public function get(int $fcaDefectCategoryId=0): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fcaDefectCategoryId = !empty($fcaDefectCategoryId) ? $fcaDefectCategoryId : $this->fcaDefectCategoryId;
            return DbMysql::select('fca_defect_category', array('fcaDefectCategoryId'=>$this->fcaDefectCategoryId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $fcaDefectCategoryId
     * @throws Exception
     */
    public function set (int $fcaDefectCategoryId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($fcaDefectCategoryId, 'fcaDefectCategoryId');
            $this->fcaDefectCategoryId = $fcaDefectCategoryId;
            $this->fcaDefectCategoryName = DbMysql::selectColumn('fca_defect_category', array('fcaDefectCategoryId'=>$fcaDefectCategoryId),'fcaDefectCategoryName', true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function insert (array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkMandatoryArray($columns, array('fcaDefectCategoryName', 'fcaDefectCategoryStatus'), true);
            if (DbMysql::count('fca_defect_category', array('fcaDefectCategoryName'=>$columns['fcaDefectCategoryName'])) > 0) {
                throw new Exception(str_replace('__', $columns['fcaDefectCategoryName'], Constant::$fcaDefectCategory['errAlreadyExist']), 31);
            }
            $this->set(DbMysql::insert('fca_defect_category', $columns));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $fcaDefectCategoryId
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function update (int $fcaDefectCategoryId, array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($fcaDefectCategoryId, 'fcaDefectCategoryId');
            parent::checkMandatoryArray($columns, array('fcaDefectCategoryName', 'fcaDefectCategoryStatus'), true);
            if (DbMysql::count('fca_defect_category', array('fcaDefectCategoryName'=>$columns['fcaDefectCategoryName'], 'fcaDefectCategoryId'=>'<>|'.$fcaDefectCategoryId)) > 0) {
                throw new Exception(str_replace('__', $columns['fcaDefectCategoryName'], Constant::$fcaDefectCategory['errAlreadyExist']), 31);
            }
            DbMysql::update('fca_defect_category', $columns, array('fcaDefectCategoryId'=>$fcaDefectCategoryId));
            $this->set($fcaDefectCategoryId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function delete (): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->fcaDefectCategoryId, 'fcaDefectCategoryId');
            if (DbMysql::count('fca_task', array('fcaDefectCategoryId'=>$this->fcaDefectCategoryId)) > 0) {
                throw new Exception(str_replace('__', $this->fcaDefectCategoryName, Constant::$fcaDefectCategory['errStillExist']), 31);
            }
            DbMysql::delete('fca_defect_category', array('fcaDefectCategoryId'=>$this->fcaDefectCategoryId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}