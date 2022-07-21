<?php

class AttTransaction extends General {

    public $attTransactionId = 0;
    private $shiftCurrent = 0;

    function __construct(int $userId = 0, bool $isLogged = false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $siteId
     * @param int $year
     * @param int $month
     * @return array
     * @throws Exception
     */
    public function getMonthlySite (int $siteId, int $year, int $month): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');
            $returnArr = array();
            $transactionList = DbMysql::selectSqlAll(
                /** @lang text */
                "SELECT
                    t.user_id AS user_ids, 
                    DAY(att_transaction_date) AS days, 
                    CASE 
                        WHEN att_transaction_result IS NOT NULL THEN att_transaction_result					
                        WHEN att_transaction_status <> 'Ready' THEN 'Present'	
                        WHEN y.att_type_mode IN ('Leave', 'Training') THEN y.att_type_name
                        WHEN att_transaction_date < CURDATE() AND y.att_type_mode IN ('Normal', '2 Shifts', '3 Shifts') THEN 'Absent'
                        ELSE NULL 
                    END AS result,
                    TIMEDIFF(att_transaction_shift_start, att_transaction_time_in) AS duration_in,
                    TIMEDIFF(att_transaction_time_out, att_transaction_shift_end) AS duration_out,		
                    TIMEDIFF(att_transaction_shift_end, att_transaction_shift_start) AS duration_needed,
                    TIMEDIFF(att_transaction_time_out, att_transaction_time_in) AS duration_work,
                    ST_X(att_transaction_location_in) AS location_in_x,
                    ST_Y(att_transaction_location_in) AS location_in_y,
                    ST_X(att_transaction_location_out) AS location_out_x,
                    ST_Y(att_transaction_location_out) AS location_out_y,
                    ST_CONTAINS(g.att_group_polygon, att_transaction_location_in) AS location_inside_in,
                    ST_CONTAINS(g.att_group_polygon, att_transaction_location_out) AS location_inside_out,
                    t.att_transaction_id,
                    t.att_transaction_date,
                    t.att_participant_id,
                    t.user_id, 
                    f.designation_id,
                    t.att_group_id,
                    p.asset_group_id,
                    t.att_type_id,                    
                    DAYNAME(t.att_transaction_date) AS day_name,
                    p.att_participant_shift_mode,
                    t.att_transaction_shift_start,
                    t.att_transaction_shift_end,
                    t.att_transaction_time_in,
                    t.att_transaction_time_out,
                    t.att_transaction_status
                FROM att_transaction t
                LEFT JOIN att_group g ON g.att_group_id = t.att_group_id
                LEFT JOIN att_participant p ON p.att_participant_id = t.att_participant_id
                LEFT JOIN att_type y ON y.att_type_id = t.att_type_id
                LEFT JOIN sys_user_profile f ON f.user_id = t.user_id AND f.user_profile_status = 1",
                array('g.siteId'=>$siteId, 'year(attTransactionDate)'=>$year, 'month(attTransactionDate)'=>$month), 2, false, 'userIds, days');
            foreach ($transactionList as $i=>$transactions) {
                $newArray = array();
                foreach ($transactions as $transaction) {
                    $transaction['durationInLate'] = null;
                    if ($transaction['durationIn'] !== null) {
                        $transaction['durationInLate'] = substr($transaction['durationIn'], 0, 1) === '-' ? 'Late' : 'Early';
                        $transaction['durationIn'] = parent::timeDisplay($transaction['durationIn'], true);
                    }
                    $transaction['durationOutLate'] = null;
                    if ($transaction['durationOut'] !== null) {
                        $transaction['durationOutLate'] = substr($transaction['durationOut'], 0, 1) === '-' ? 'Late' : 'Early';
                        $transaction['durationOut'] = parent::timeDisplay($transaction['durationOut'], true);
                    }
                    $transaction['locationInsideIn'] = $transaction['locationInsideIn'] !== null ? ($transaction['locationInsideIn'] === 1 ? 'Inside Parameter' : 'Outside Parameter') : null;
                    $transaction['locationInsideOut'] = $transaction['locationInsideOut'] !== null ? ($transaction['locationInsideOut'] === 1 ? 'Inside Parameter' : 'Outside Parameter') : null;
                    $transaction['durationEnoughHour'] = null;
                    if ($transaction['durationNeeded'] !== null) {
                        if ($transaction['durationWork'] !== null) {
                            $transaction['durationEnoughHour'] = $transaction['durationWork'] >= $transaction['durationNeeded'] ? 'Yes' : 'No';
                            $transaction['durationWork'] = parent::timeDisplay($transaction['durationWork'], true);
                        }
                        $transaction['durationNeeded'] = parent::timeDisplay($transaction['durationNeeded'], true);
                    }
                    $newArray[$transaction['days']] = $transaction;
                }
                $returnArr[] = array('userId'=>$i, 'data'=>$newArray);
            }
            return $returnArr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param DateTime $currentDate
     * @param string $shiftMode
     * @param int $shiftStart
     * @param string $holiday
     * @return int
     * @throws Exception
     */
    private function getNewType (DateTime $currentDate, string $shiftMode, int $shiftStart, string $holiday): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $attTypeId = 0;
            $today = new DateTime();
            $today->setTime(0, 0);
            $dayOfWeek = intval($currentDate->format('w'));

            if ($holiday === 'Sunday' && $dayOfWeek === 0) {
                $attTypeId = 12;
            } else if ($holiday === 'Saturday & Sunday' && ($dayOfWeek === 0 || $dayOfWeek === 6)) {
                $attTypeId = 12;
            } else if ($shiftMode === 'Normal') {
                $attTypeId = 1;
            } else if ($shiftMode === '2 Shifts' || $shiftMode === '3 Shifts') {
                if ($this->shiftCurrent === 0) {
                    $attTypeId = $shiftStart;
                    $this->shiftCurrent = $shiftStart;
                } else if ($dayOfWeek === 1) {
                    if ($shiftMode === '2 Shifts' && $this->shiftCurrent === 2) {
                        $attTypeId = 3;
                    } else if ($shiftMode === '2 Shifts' && $this->shiftCurrent === 3) {
                        $attTypeId = 2;
                    } else if ($shiftMode === '3 Shifts' && $this->shiftCurrent === 9) {
                        $attTypeId = 10;
                    } else if ($shiftMode === '3 Shifts' && $this->shiftCurrent === 10) {
                        $attTypeId = 11;
                    } else if ($shiftMode === '3 Shifts' && $this->shiftCurrent === 11) {
                        $attTypeId = 9;
                    }
                    $this->shiftCurrent = $attTypeId;
                } else {
                    $attTypeId = $this->shiftCurrent;
                }
            }

            if ($attTypeId === 0) {
                throw new Exception('Invalid $attTypeId for $shiftMode = '.$shiftMode.', $shiftCurrent = '.$this->shiftCurrent);
            }
            return $attTypeId;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param DateTime $currentDate
     * @param array $attGroup
     * @param string $shiftMode
     * @param int $attTypeId
     * @return array
     * @throws Exception
     */
    private function getShiftTime (DateTime $currentDate, array $attGroup, string $shiftMode, int $attTypeId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $shiftTime = array('', '');
            $today = $currentDate->format('Y-m-d');
            if ($shiftMode === 'Normal' && $attTypeId === 1) {
                $shiftTime = array($today.' '.$attGroup['attGroupNormalStart'], $today.' '.$attGroup['attGroupNormalEnd']);
            } else if ($shiftMode === '2 Shifts' && $attTypeId === 2) {
                $shiftTime = array($today.' '.$attGroup['attGroupAmStart'], $today.' '.$attGroup['attGroupAmEnd']);
            } else if ($shiftMode === '2 Shifts' && $attTypeId === 3) {
                $hour = intval(substr($attGroup['attGroupPmEnd'], 0, 2));
                $endDate = $hour < 10 ? $currentDate->modify( '+1 day' ) : $currentDate;
                $shiftTime = array($today.' '.$attGroup['attGroupPmStart'], $endDate->format('Y-m-d').' '.$attGroup['attGroupPmEnd']);
            } else if ($shiftMode === '3 Shifts' && $attTypeId === 9) {
                $shiftTime = array($today.' '.$attGroup['attGroupMorningStart'], $today.' '.$attGroup['attGroupMorningEnd']);
            } else if ($shiftMode === '3 Shifts' && $attTypeId === 10) {
                $hour = intval(substr($attGroup['attGroupPmEnd'], 0, 2));
                $endDate = $hour < 10 ? $currentDate->modify( '+1 day' ) : $currentDate;
                $shiftTime = array($today.' '.$attGroup['attGroupEveningStart'], $endDate->format('Y-m-d').' '.$attGroup['attGroupEveningEnd']);
            } else if ($shiftMode === '3 Shifts' && $attTypeId === 11) {
                $hour = intval(substr($attGroup['attGroupNightStart'], 0, 2));
                if ($hour < 3) {
                    $startDate = $currentDate->modify( '+1 day' );
                    $endDate = $startDate;
                } else {
                    $startDate = $currentDate;
                    $endDate = $currentDate->modify( '+1 day' );
                }
                $shiftTime = array($startDate->format('Y-m-d').' '.$attGroup['attGroupNightStart'], $endDate->format('Y-m-d').' '.$attGroup['attGroupNightEnd']);
            }
            return $shiftTime;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attParticipantId
     * @param int $year
     * @param int $month
     * @throws Exception
     */
    public function insertMonthly (int $attParticipantId, int $year, int $month): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attParticipantId, 'attParticipantId');
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');
            if ($month < 1 || $month > 12) {
                throw new Exception('Invalid month = '.$month);
            }
            $attParticipant = DbMysql::select('att_participant', array('attParticipantId'=>$attParticipantId), 1);
            $attGroup = DbMysql::select('att_group', array('attGroupId'=>$attParticipant['attGroupId']), 1);
            DbMysql::delete('att_Transaction', array('userId'=>$attParticipant['userId'], 'attGroupId'=>$attParticipant['attGroupId'], 'year(attTransactionDate)'=>$year, 'month(attTransactionDate)'=>$month));
            $this->shiftCurrent = 0;
            $dateProcess = new DateTime();
            $endOfMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            for ($day = 1; $day <= $endOfMonth; $day++) {
                $dateProcess->setDate($year, $month, $day);
                $attTypeId = $this->getNewType($dateProcess, $attParticipant['attParticipantShiftMode'], $attParticipant['attTypeId'], $attParticipant['attParticipantHoliday']);
                $shiftTime = $this->getShiftTime($dateProcess, $attGroup, $attParticipant['attParticipantShiftMode'], $attTypeId);
                DbMysql::insert('att_transaction', array('attTransactionDate'=>$dateProcess->format('Y-m-d'), 'attParticipantId'=>$attParticipantId, 'attGroupId'=>$attParticipant['attGroupId'],
                    'userId'=>$attParticipant['userId'], 'attTypeId'=>$attTypeId, 'attTransactionShiftStart'=>$shiftTime[0], 'attTransactionShiftEnd'=>$shiftTime[1]));
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attParticipantId
     * @param int $year
     * @param int $month
     * @throws Exception
     */
    public function updateMonthly (int $attParticipantId, int $year, int $month): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attParticipantId, 'attParticipantId');
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');
            if ($month < 1 || $month > 12) {
                throw new Exception('Invalid month = '.$month);
            }
            $attParticipant = DbMysql::select('att_participant', array('attParticipantId'=>$attParticipantId), 1);
            $attGroup = DbMysql::select('att_group', array('attGroupId'=>$attParticipant['attGroupId']), 1);
            $this->shiftCurrent = 0;
            $dateStart = new DateTime();
            $dateStart->setDate($year, $month, 1);
            $attTransactionList = DbMysql::selectAll('att_Transaction', array('attParticipantId'=>$attParticipantId, 'attTransactionDate'=>'>=|'.$dateStart->format('Y-m-d')), 0, true, 'attTransactionDate');
            foreach ($attTransactionList as $attTransaction) {
                $attTypeId = $this->getNewType(new DateTime($attTransaction['attTransactionDate']), $attParticipant['attParticipantShiftMode'], $attParticipant['attTypeId'], $attParticipant['attParticipantHoliday']);
                $shiftTime = $this->getShiftTime(new DateTime($attTransaction['attTransactionDate']), $attGroup, $attParticipant['attParticipantShiftMode'], $attTypeId);
                DbMysql::update('att_transaction', array('attGroupId'=>$attParticipant['attGroupId'], 'attTypeId'=>$attTypeId, 'attTransactionShiftStart'=>$shiftTime[0], 'attTransactionShiftEnd'=>$shiftTime[1]),
                    array('attTransactionId'=>$attTransaction['attTransactionId']));
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}