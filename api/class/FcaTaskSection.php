<?php

class FcaTaskSection extends General {

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $fcaTaskId
     * @param bool $isMobile
     * @return array
     * @throws Exception
     */
    public function getList (int $fcaTaskId, bool $isMobile = false): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($fcaTaskId, 'fcaTaskId');
            $fcaTaskSectionList = DbMysql::selectAll('fca_task_section', array('fcaTaskId'=>$fcaTaskId), 0, true, 'fcaTaskSectionCode');
            if ($isMobile) {
                $refStatus = DbMysql::select('ref_status', array(), 1, true);
                for ($i = 0; $i < count($fcaTaskSectionList); $i++) {
                    $fcaTaskSectionList[$i]['fcaTaskSectionStatusDesc'] = $refStatus[$fcaTaskSectionList[$i]['fcaTaskSectionStatus']]['statusDesc'];
                }
            }
            return $fcaTaskSectionList;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $fcaTaskId
     * @return void
     * @throws Exception
     */
    public function register (int $fcaTaskId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($fcaTaskId, 'fcaTaskId');
            DbMysql::insert('fca_task_section', array('fcaTaskId'=>$fcaTaskId, 'fcaTaskSectionCode'=>'A', 'fcaTaskSectionName'=>'Audit Details', 'fcaTaskSectionStatus'=>'17'));
            DbMysql::insert('fca_task_section', array('fcaTaskId'=>$fcaTaskId, 'fcaTaskSectionCode'=>'B', 'fcaTaskSectionName'=>'Recommendation', 'fcaTaskSectionStatus'=>'18'));
            DbMysql::insert('fca_task_section', array('fcaTaskId'=>$fcaTaskId, 'fcaTaskSectionCode'=>'C', 'fcaTaskSectionName'=>'Validation', 'fcaTaskSectionStatus'=>'18'));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}