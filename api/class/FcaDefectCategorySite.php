<?php

class FcaDefectCategorySite extends General {

    public $fcaDefectCategorySiteId = 0;
    public $fcaDefectCategoryName = '';
    public $siteName = '';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRef(): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $returnArr = array();
            $refArr = DbMysql::selectAll('v_defect_category_site', array());
            foreach ($refArr as $ref) {
                $returnArr[intval($ref['siteId'])] = $ref['defectCategoryList'];
            }
            return $returnArr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
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
            return DbMysql::selectAll('fca_defect_category_site', array());
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getListGrouped(): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            return DbMysql::selectAll('v_defect_category_site', array());
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @throws Exception
     */
    public function set (array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkMandatoryArray($columns, array('siteId', 'fcaDefectCategoryId'), true);
            $this->fcaDefectCategoryName = DbMysql::selectColumn('fca_defect_category', array('fcaDefectCategoryId'=>$columns['fcaDefectCategoryId']),'fcaDefectCategoryName', true);
            $this->siteName = DbMysql::selectColumn('cli_site', array('siteId'=>$columns['siteId']),'siteCode', true);
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
            parent::checkMandatoryArray($columns, array('siteId', 'fcaDefectCategoryId'), true);
            if (DbMysql::count('fca_defect_category_site', $columns) > 0) {
                $errMsg = str_replace('___', $this->siteName, Constant::$fcaDefectCategorySite['errAlreadyExist']);
                $errMsg = str_replace('__', $this->fcaDefectCategoryName, $errMsg);
                throw new Exception($errMsg, 31);
            }
            DbMysql::insert('fca_defect_category_site', $columns);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function delete (array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkMandatoryArray($columns, array('siteId', 'fcaDefectCategoryId'), true);
            DbMysql::delete('fca_defect_category_site', $columns);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}