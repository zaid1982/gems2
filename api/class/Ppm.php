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
                LEFT JOIN ppm_task ppt ON ppt.ppm_id = ppm.ppm_id
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
    public function insert (array $columns): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return DbMysql::insert($this::$tableName, $columns);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return int
     * @throws Exception
     */
    public function insertAssetGroup (array $columns): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (!isset($columns['checklistId'])) {
                throw new Exception('Parameter checklistId not exist');
            }
            $ppmChecklist = DbMysql::select('ppm_checklist', array('checklistId'=>$columns['checklistId']), true);
            $columns['ppmTaskNo'] = $ppmChecklist['checklistDocumentNo'];
            $columns['ppmIssueNo'] = $ppmChecklist['checklistIssueNo'];
            $columns['ppmCreatedBy'] = $this->userId;
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
            $current = $this->get($id);
            if ($current['ppmStatus'] === 11) {
                DbMysql::update($this::$tableName, $columns, array($this::$idName=>$id));
                if ($current['assetTypeId'] !== $columns['assetTypeId'] && DbMysql::count('ppm_asset', array($this::$idName=>$id)) > 0) {
                    DbMysql::delete('ppm_asset', array($this::$idName=>$id));
                }
            } else if ($current['ppmStatus'] === 1) {
                $params = $this->arraySpliceAssocMultiple($columns, array('ppmName', 'ppmRemark'));
                DbMysql::update($this::$tableName, $params, array($this::$idName=>$id));
            } else {
                throw new Exception('Invalid current status = '. $current['status']);
            }
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
            $current = $this->get($id);
            if ($current['ppmStatus'] === 1) {
                throw new Exception(Constant::$ppm['errAssigned']);
            } else if ($current['ppmStatus'] === 11 && $current['ppmIsGroup'] === 1) {
                if (DbMysql::count('ppm_asset', array($this::$idName=>$id)) > 0) {
                    DbMysql::delete('ppm_asset', array($this::$idName=>$id));
                }
            } else {
                throw new Exception('Invalid current status = '. $current['status']);
            }
            DbMysql::delete($this::$tableName, array($this::$idName=>$id));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $applyDate
     * @param $modifier
     * @param $duration
     * @return array
     * @throws Exception
     */
    private function getDates ($startDate, $endDate, $applyDate, $modifier, $duration): array {
        try {
            $newDates = array();
            $begin = new DateTime($startDate);
            $end = new DateTime($endDate);
            if ($duration === 'P1D') {
                $end = $end->modify($modifier);
            } else if ($duration === 'P1W') {
                $begin = $begin->modify($modifier);
                $begin = $begin->modify('-1 day');
                $end = $end->modify('+1 day');
            } else {
                $begin = $begin->modify($modifier);
                $end = $end->modify('+2 day');
            }
            $apply = new DateTime($applyDate);
            $interval = new DateInterval($duration);
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                if ($duration === 'P1W') {
                    if ($date > $apply) {
                        $newDates[] = $date->format("Y-m-d");
                    }
                } else if ($duration === 'P1W') {
                    if ($date->format("D") == 'Mon') {
                        $date->modify( '+6 day' );
                    } else if ($date->format("D") == 'Tue') {
                        $date->modify( '+5 day' );
                    } else if ($date->format("D") == 'Wed') {
                        $date->modify( '+4 day' );
                    } else if ($date->format("D") == 'Thu') {
                        $date->modify( '+3 day' );
                    } else if ($date->format("D") == 'Fri') {
                        $date->modify( '+2 day' );
                    } else if ($date->format("D") == 'Sat') {
                        $date->modify( '+1 day' );
                    }
                    if ($date > $apply && $date < $end) {
                        $newDates[] = $date->format("Y-m-d");
                    }
                } else {
                    $xx = $date->modify( '-1 day' );
                    if ($xx > $apply) {
                        $newDates[] = $xx->format("Y-m-d");
                    }
                }
            }
            return $newDates;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $ppmDate
     * @param $frequency
     * @return mixed
     * @throws Exception
     */
    private function getPpmStartDate ($ppmDate, $frequency) {
        try {
            $ppmDate = new DateTime($ppmDate);
            if ($frequency === 1) {   // yearly
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-1 month');
                //$ppmDate->modify('-1 year');
                //$ppmDate->modify('+1 day');
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else if ($frequency === 2) {    // quarterly
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-1 month');
                //$ppmDate->modify('+1 day');
                //$ppmDate->modify('-3 month');
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else if ($frequency === 3) {    // monthly
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-1 month');
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else if ($frequency === 4) {    // weekly
                $ppmDate->modify('-6 day');
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else if ($frequency === 5) {    // daily
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else if ($frequency === 6) {    // half-annually
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-1 month');
                //$ppmDate->modify('+1 day');
                //$ppmDate->modify('-6 month');
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter frequency invalid');
            }
            return $ppmStartDate;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $ppmId
     * @throws Exception
     */
    public function createPpmTask (int $ppmId, string $extensionDateStart=''): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($ppmId, $this::$idName);
            $ppm = $this->get($ppmId);
            if (empty($extensionDateStart)) {
                if ($ppm['ppmIsGroup'] !== 1) {
                    throw new Exception('Invalid ppmIsGroup');
                } else if ($ppm['ppmStatus'] !== 11) {
                    throw new Exception('Invalid current status = '. $ppm['status']);
                }
            } else {
                if ($ppm['ppmStatus'] !== 1) {
                    throw new Exception('Invalid current status = '. $ppm['status']);
                }
            }
            parent::checkMandatoryArray($ppm, array('checklistId', 'assetTypeId', 'contractId', 'ppmDateStart'));

            $contractId = $ppm['contractId'];
            $checklistId = $ppm['checklistId'];
            $contract = DbMysql::select('cli_contract', array('contractId'=>$contractId), true);
            $checklist = DbMysql::select('ppm_checklist', array('checklistId'=>$checklistId), true);
            $contractDateStart = !empty($extensionDateStart) ? $extensionDateStart : $contract['contractDateStart'];
            $contractDateEnd = $contract['contractDateEnd'];
            $siteId = $contract['siteId'];
            $ppmDateStart = $ppm['ppmDateStart'];

            $isYearly = false; $isHalfAnnually = false; $isQuarterly = false; $isMonthly = false; $isWeekly = false; $isDaily = false;
            $checklistQuals = DbMysql::selectAll('ppm_checklist_qual', array('checklistId'=>$checklistId, 'checklistQualStatus'=>1), 0, false, 'abs(checklistQualNumb)');
            foreach ($checklistQuals as $checklistQual) {
                switch ($checklistQual['frequencyId']) {
                    case 1: $isYearly = true; break;
                    case 2: $isQuarterly = true; break;
                    case 3: $isMonthly = true; break;
                    case 4: $isWeekly = true; break;
                    case 5: $isDaily = true; break;
                    case 6: $isHalfAnnually = true; break;
                }
            }

            $checklistQuans = DbMysql::selectAll('ppm_checklist_quan', array('checklistId'=>$checklistId, 'checklistQuanStatus'=>1), 0, false, 'abs(checklistQuanNumb)');
            foreach ($checklistQuans as $checklistQuan) {
                switch ($checklistQuan['frequencyId']) {
                    case 1: $isYearly = true; break;
                    case 2: $isQuarterly = true; break;
                    case 3: $isMonthly = true; break;
                    case 4: $isWeekly = true; break;
                    case 5: $isDaily = true; break;
                    case 6: $isHalfAnnually = true; break;
                }
            }

            $dailyDates = $this->getDates($contractDateStart, $contractDateEnd, $ppmDateStart, '+1 day', 'P1D');
            $weeklyDates = $this->getDates($contractDateStart, $contractDateEnd, $ppmDateStart, '+1 week', 'P1W');
            $monthlyDates = $this->getDates($contractDateStart, $contractDateEnd, $ppmDateStart, '+1 month', 'P1M');
            $quarterlyDates = $this->getDates($contractDateStart, $contractDateEnd, $ppmDateStart, '+3 month', 'P3M');
            $halfAnnuallyDates = $this->getDates($contractDateStart, $contractDateEnd, $ppmDateStart, '+6 month', 'P6M');
            $yearlyDates = $this->getDates($contractDateStart, $contractDateEnd, $ppmDateStart, '+1 year', 'P1Y');

            $tempDays = array();
            foreach($dailyDates as $dateStr){
                if ($isDaily) {
                    $tempDays[] = $dateStr;
                }
                if ($isWeekly && in_array($dateStr, $weeklyDates) && !in_array($dateStr, $tempDays)) {
                    $tempDays[] = $dateStr;
                }
                if ($isMonthly && in_array($dateStr, $monthlyDates) && !in_array($dateStr, $tempDays)) {
                    $tempDays[] = $dateStr;
                }
                if ($isQuarterly && in_array($dateStr, $quarterlyDates) && !in_array($dateStr, $tempDays)) {
                    $tempDays[] = $dateStr;
                }
                if ($isHalfAnnually && in_array($dateStr, $halfAnnuallyDates) && !in_array($dateStr, $tempDays)) {
                    $tempDays[] = $dateStr;
                }
                if ($isYearly && in_array($dateStr, $yearlyDates) && !in_array($dateStr, $tempDays)) {
                    $tempDays[] = $dateStr;
                }
            }
            if (count($tempDays) === 0) {
                throw new Exception('[' . __LINE__ . '] - No dates available between the start cycle date and contract end date', 31);
            }

            $siteCode = DbMysql::selectColumn('cli_site', array('siteId'=>$siteId), 'siteCode', true);
            $runningNo = DbMysql::selectColumn('cli_site', array('siteId'=>$siteId), 'siteRunningNo', true);
            foreach($tempDays as $key => $dateStr){
                $runningNoTemp = 100000 + $runningNo;
                $runningNoStr = substr(strval($runningNoTemp), 1);
                $ppmTaskNo = 'P'.$siteCode.substr($dateStr, 2, 2).substr($dateStr, 5, 2).substr($dateStr, 8, 2).$runningNoStr;
                $runningNo++;

                $transactionId = DbMysql::insert('wfl_transaction', array('transactionNo'=>$ppmTaskNo, 'flowId'=>1, 'userId'=>$this->userId, 'groupId'=>1, 'transactionDateDue'=>'|CURDATE() + INTERVAL 30 DAY', 'transactionStatus'=>'5'));
                $taskId = DbMysql::insert('wfl_task', array('transactionId'=>$transactionId, 'checkpointId'=>1, 'roleId'=>5, 'groupId'=>1, 'taskCreatedUser'=>$this->userId, 'taskCreatedGroup'=>1,'taskClaimedUser'=>$this->userId, 'taskTimeClaimed'=>'NOW()', 'taskDateDue'=>$dateStr, 'taskStatus'=>5));
                $ppmTaskId =  DbMysql::insert('ppm_task', array('ppmTaskNo'=>$ppmTaskNo, 'ppmTaskScheduleDate'=>$dateStr, 'ppmId'=>$ppmId, 'ppmTaskGuideline'=>$checklist['checklistGuideline'], 'ppmTaskStatus'=>12, 'transactionId'=>$transactionId));

                DbMysql::insert('ppm_task_section', array('ppmTaskSectionName'=>'A', 'ppmTaskId'=>$ppmTaskId, 'ppmTaskSectionStatus'=>17));
                DbMysql::insert('ppm_task_section', array('ppmTaskSectionName'=>'B', 'ppmTaskId'=>$ppmTaskId, 'ppmTaskSectionStatus'=>17));
                DbMysql::insert('ppm_task_section', array('ppmTaskSectionName'=>'C', 'ppmTaskId'=>$ppmTaskId, 'ppmTaskSectionStatus'=>18));
                DbMysql::insert('ppm_task_section', array('ppmTaskSectionName'=>'D', 'ppmTaskId'=>$ppmTaskId, 'ppmTaskSectionStatus'=>empty($checklistQuans)?19:18));
                DbMysql::insert('ppm_task_section', array('ppmTaskSectionName'=>'E', 'ppmTaskId'=>$ppmTaskId, 'ppmTaskSectionStatus'=>18));
                DbMysql::insert('ppm_task_section', array('ppmTaskSectionName'=>'F', 'ppmTaskId'=>$ppmTaskId, 'ppmTaskSectionStatus'=>18));
                DbMysql::insert('ppm_task_section', array('ppmTaskSectionName'=>'G', 'ppmTaskId'=>$ppmTaskId, 'ppmTaskSectionStatus'=>18));
                DbMysql::insert('ppm_task_section', array('ppmTaskSectionName'=>'H', 'ppmTaskId'=>$ppmTaskId, 'ppmTaskSectionStatus'=>18));

                foreach ($checklistQuals as $checklistQual) {
                    $qualResult = null;
                    $qualFrequency = $checklistQual['frequencyId'];
                    if ($qualFrequency === 1 && !in_array($dateStr, $yearlyDates)) {
                        $qualResult = 2;
                    } else if ($qualFrequency === 2 && !in_array($dateStr, $quarterlyDates)) {
                        $qualResult = 2;
                    } else if ($qualFrequency === 3 && !in_array($dateStr, $monthlyDates)) {
                        $qualResult = 2;
                    } else if ($qualFrequency === 4 && !in_array($dateStr, $weeklyDates)) {
                        $qualResult = 2;
                    } else if ($qualFrequency === 5 && !in_array($dateStr, $dailyDates)) {
                        $qualResult = 2;
                    } else if ($qualFrequency === 6 && !in_array($dateStr, $halfAnnuallyDates)) {
                        $qualResult = 2;
                    }
                    DbMysql::insert('ppm_task_qual', array('ppmTaskQualNumb'=>$checklistQual['checklistQualNumb'], 'ppmTaskQualDesc'=>$checklistQual['checklistQualDesc'], 'frequencyId'=>$qualFrequency,
                        'ppmTaskQualResult'=>$qualResult, 'ppmTaskId'=>$ppmTaskId, 'checklistQualId'=>$checklistQual['checklistQualId']));
                }

                foreach ($checklistQuans as $checklistQuan) {
                    $quanResult = null;
                    $quanFrequency = $checklistQuan['frequencyId'];
                    if ($quanFrequency === 1 && !in_array($dateStr, $yearlyDates)) {
                        $quanResult = 2;
                    } else if ($quanFrequency === 2 && !in_array($dateStr, $quarterlyDates)) {
                        $quanResult = 2;
                    } else if ($quanFrequency === 3 && !in_array($dateStr, $monthlyDates)) {
                        $quanResult = 2;
                    } else if ($quanFrequency === 4 && !in_array($dateStr, $weeklyDates)) {
                        $quanResult = 2;
                    } else if ($quanFrequency === 5 && !in_array($dateStr, $dailyDates)) {
                        $quanResult = 2;
                    } else if ($quanFrequency === 6 && !in_array($dateStr, $halfAnnuallyDates)) {
                        $quanResult = 2;
                    }
                    DbMysql::insert('ppm_task_quan', array('ppmTaskQuanNumb'=>$checklistQuan['checklistQuanNumb'], 'ppmTaskQuanDesc'=>$checklistQuan['checklistQuanDesc'], 'frequencyId'=>$quanFrequency,
                        'ppmTaskQuanUnit'=>$checklistQuan['checklistQuanUnit'], 'ppmTaskQuanSetValues'=>$checklistQuan['checklistQuanSetValues'], 'ppmTaskQuanResult'=>$quanResult, 'ppmTaskId'=>$ppmTaskId, 'checklistQuanId'=>$checklistQuan['checklistQuanId']));
                }

                $highestFrequency = '';
                if ($isDaily && in_array($dateStr, $dailyDates)) {
                    DbMysql::insert('ppm_task_frequency', array('ppmTaskId'=>$ppmTaskId, 'frequencyId'=>5));
                    $highestFrequency = 5;
                }
                if ($isWeekly && in_array($dateStr, $weeklyDates)) {
                    DbMysql::insert('ppm_task_frequency', array('ppmTaskId'=>$ppmTaskId, 'frequencyId'=>4));
                    $highestFrequency = 4;
                }
                if ($isMonthly && in_array($dateStr, $monthlyDates)) {
                    DbMysql::insert('ppm_task_frequency', array('ppmTaskId'=>$ppmTaskId, 'frequencyId'=>3));
                    $highestFrequency = 3;
                }
                if ($isQuarterly && in_array($dateStr, $quarterlyDates)) {
                    DbMysql::insert('ppm_task_frequency', array('ppmTaskId'=>$ppmTaskId, 'frequencyId'=>2));
                    $highestFrequency = 2;
                }
                if ($isHalfAnnually && in_array($dateStr, $halfAnnuallyDates)) {
                    DbMysql::insert('ppm_task_frequency', array('ppmTaskId'=>$ppmTaskId, 'frequencyId'=>6));
                    $highestFrequency = 6;
                }
                if ($isYearly && in_array($dateStr, $yearlyDates)) {
                    DbMysql::insert('ppm_task_frequency', array('ppmTaskId'=>$ppmTaskId, 'frequencyId'=>1));
                    $highestFrequency = 1;
                }
                $ppmStartDate = $this->getPpmStartDate($dateStr, $highestFrequency);
                DbMysql::update('ppm_task', array('ppmTaskStartDate'=>$ppmStartDate), array('ppmTaskId'=>$ppmTaskId));
                DbMysql::update('wfl_task', array('taskStatus'=>8, 'taskTimeClaimed'=>null), array('transactionId'=>$transactionId));
                DbMysql::update('wfl_transaction', array('transactionDateDue'=>$dateStr, 'transactionStatus'=>12, 'assetNo'=>$ppm['ppmName']), array('transactionId'=>$transactionId));
            }
            if (empty($extensionDateStart)) {
                DbMysql::update('ppm', array('ppmStatus'=>1), array('ppmId'=>$ppmId));
            }
            DbMysql::update('cli_site', array('siteRunningNo'=>$runningNo), array('siteId'=>$siteId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @return void
     * @throws Exception
     */
    public function extendContractPpm (array $params): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkMandatoryArray($params, array('contractId', 'contractDateStart', 'contractDateEnd'));
            $contractId = $params['contractId'];
            $ppmList = DbMysql::selectAll($this::$tableName, array('contractId'=>$contractId, 'ppmStatus'=>1));
            foreach ($ppmList as $row) {
                try {
                    DbMysql::beginTransaction();
                    $this->createPpmTask($row['ppmId'], $params['contractDateStart']);
                    DbMysql::commit();
                } catch (Exception $ey) {
                    DbMysql::rollback();
                    parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Error when execute create ppm task for ppmId = '.$row['ppmId']);
                }
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}