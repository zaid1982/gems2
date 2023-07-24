<?php

class AttTransaction extends General {

    public $attTransactionId = 0;
    public $attTransactionDate = '';
    public $attParticipantName = '';
    private $shiftCurrent = 0;

    function __construct(int $userId = 0, bool $isLogged = false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $attTransactionId
     * @throws Exception
     */
    public function set (int $attTransactionId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attTransactionId, 'attTransactionId');
            $this->attTransactionId = $attTransactionId;
            $attTransaction = DbMysql::select('att_transaction', array('attTransactionId'=>$this->attTransactionId), true);
            $this->attParticipantName = DbMysql::selectColumn('sys_user', array('userId'=>$attTransaction['userId']),'userFirstName', true);
            $this->attTransactionDate = $attTransaction['attTransactionDate'];
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $id
     * @param string $typeIndex
     * @param int $year
     * @param int $month
     * @return array
     * @throws Exception
     */
    public function getMonthlySheet (int $id, string $typeIndex, int $year, int $month): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($id, 'id');
            parent::checkEmptyString($typeIndex, 'typeIndex');
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
                    TIMEDIFF(att_transaction_shift_end, att_transaction_time_out) AS duration_out,		
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
                    t.att_transaction_result,
                    t.att_transaction_status
                FROM att_transaction t
                LEFT JOIN att_group g ON g.att_group_id = t.att_group_id
                LEFT JOIN att_participant p ON p.att_participant_id = t.att_participant_id
                LEFT JOIN att_type y ON y.att_type_id = t.att_type_id
                LEFT JOIN sys_user_profile f ON f.user_id = t.user_id AND f.user_profile_status = 1",
                array($typeIndex=>$id, 'p.attParticipantStatus'=>1, 'year(attTransactionDate)'=>$year, 'month(attTransactionDate)'=>$month), 2, false, 'userIds, days');
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
     * @param int $generateType
     * @param int $shiftCurrent
     * @throws Exception
     */
    public function insertMonthly (int $attParticipantId, int $year, int $month, int $generateType, int $shiftCurrent = 0): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attParticipantId, 'attParticipantId');
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');
            parent::checkEmptyInteger($generateType, 'generateType');
            if ($month < 1 || $month > 12) {
                throw new Exception('Invalid month = '.$month);
            }
            $attParticipant = DbMysql::select('att_participant', array('attParticipantId'=>$attParticipantId), true);
            $attGroup = DbMysql::select('att_group', array('attGroupId'=>$attParticipant['attGroupId']), true);
            DbMysql::delete('att_transaction', array('userId'=>$attParticipant['userId'], 'attGroupId'=>$attParticipant['attGroupId'], 'year(attTransactionDate)'=>$year, 'month(attTransactionDate)'=>$month));
            $this->shiftCurrent = $shiftCurrent;
            $dateNow = new DateTime();
            $dateNow->setTime(0, 0);
            $dateProcess = new DateTime();
            $endOfMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            for ($day = 1; $day <= $endOfMonth; $day++) {
                $dateProcess->setDate($year, $month, $day);
                $dateString = $dateProcess->format('Y-m-d');
                $checkPastDay = $dateNow->diff($dateProcess);
                $attTypeId = $generateType === 1 && $checkPastDay->format('%r') === '-' ? 13 : $this->getNewType($dateProcess, $attParticipant['attParticipantShiftMode'], $attParticipant['attTypeId'], $attParticipant['attParticipantHoliday']);
                $shiftTime = $this->getShiftTime($dateProcess, $attGroup, $attParticipant['attParticipantShiftMode'], $attTypeId);
                $shiftResult = DbMysql::count('att_type', array('attTypeId'=>$attTypeId, 'attTypeMode'=>'Leave')) === 1 ? 'Leave' : null;
                DbMysql::insert('att_transaction', array('attTransactionDate'=>$dateString, 'attParticipantId'=>$attParticipantId, 'attGroupId'=>$attParticipant['attGroupId'],
                    'userId'=>$attParticipant['userId'], 'attTypeId'=>$attTypeId, 'attTransactionShiftStart'=>$shiftTime[0], 'attTransactionShiftEnd'=>$shiftTime[1], 'attTransactionResult'=>$shiftResult));
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attParticipantId
     * @param int $year
     * @param int $month
     * @param int $generateType
     * @param int $shiftCurrent
     * @throws Exception
     */
    public function updateMonthly (int $attParticipantId, int $year, int $month, int $generateType, int $shiftCurrent = 0): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attParticipantId, 'attParticipantId');
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');
            parent::checkEmptyInteger($generateType, 'generateType');
            if ($month < 1 || $month > 12) {
                throw new Exception('Invalid month = '.$month);
            }
            $attParticipant = DbMysql::select('att_participant', array('attParticipantId'=>$attParticipantId), true);
            $attGroup = DbMysql::select('att_group', array('attGroupId'=>$attParticipant['attGroupId']), true);
            $this->shiftCurrent = $shiftCurrent;
            $dateNow = new DateTime();
            $dateNow->setTime(0, 0);
            $attTransactionList = DbMysql::selectAll('att_transaction', array('attParticipantId'=>$attParticipantId, 'year(attTransactionDate)'=>$year, 'month(attTransactionDate)'=>$month), 0, true, 'attTransactionDate');
            foreach ($attTransactionList as $attTransaction) {
                $dateProcess = new DateTime($attTransaction['attTransactionDate']);
                $checkPastDay = $dateNow->diff($dateProcess);
                if ($generateType === 1 && $checkPastDay->format('%r') === '-') {
                    // skip
                } else {
                    $attTypeId = $this->getNewType($dateProcess, $attParticipant['attParticipantShiftMode'], $attParticipant['attTypeId'], $attParticipant['attParticipantHoliday']);
                    $shiftTime = $this->getShiftTime($dateProcess, $attGroup, $attParticipant['attParticipantShiftMode'], $attTypeId);
                    $shiftResult = DbMysql::count('att_type', array('attTypeId'=>$attTypeId, 'attTypeMode'=>'Leave')) === 1 ? 'Leave' : null;
                    DbMysql::update('att_transaction', array('attGroupId'=>$attParticipant['attGroupId'], 'attTypeId'=>$attTypeId, 'attTransactionShiftStart'=>$shiftTime[0], 'attTransactionShiftEnd'=>$shiftTime[1], 'attTransactionResult'=>$shiftResult),
                        array('attTransactionId'=>$attTransaction['attTransactionId']));
                }
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $id
     * @param int $year
     * @param int $month
     * @throws Exception
     */
    public function rescheduleSite (int $id, string $typeIndex, int $year, int $month): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($id, 'id');
            parent::checkEmptyString($typeIndex, 'typeIndex');
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');
            $dateStart = new DateTime();
            $dateStart->setDate($year, $month, 1);
            $participantList = DbMysql::selectSqlAll(
            /** @lang text */
                "SELECT
                    g.att_group_id, p.att_participant_id, p.att_participant_shift_mode, COUNT(t.att_transaction_id) AS total
                FROM att_group g
                LEFT JOIN att_participant p ON p.att_group_id = g.att_group_id
                LEFT JOIN att_transaction t ON t.att_participant_id = p.att_participant_id AND YEAR(t.att_transaction_date) = $year AND MONTH(t.att_transaction_date) = $month
                WHERE g.att_group_status = 1 AND $typeIndex = $id AND p.att_participant_id IS NOT NULL AND p.att_participant_status = 1 
                GROUP BY g.att_group_id, p.att_participant_id ORDER BY g.att_group_id, p.att_participant_id");
            foreach ($participantList as $participant) {
                $attParticipantId = $participant['attParticipantId'];
                $shiftCurrent = DbMysql::selectColumn('att_transaction', array('attParticipantId'=>$attParticipantId, 'attTransactionDate'=>'<|'.$dateStart->format('Y-m-d'), 'attTypeId'=>'IN|1,2,3,9,10,11'),
                    'attTypeId', false, 'attTransactionDate', 'DESC');
                if ($participant['total'] === 0) {
                    $this->insertMonthly($attParticipantId, $year, $month, 2, !empty($shiftCurrent) ? $shiftCurrent : 0);
                } else {
                    $this->updateMonthly($attParticipantId, $year, $month, 2, !empty($shiftCurrent) ? $shiftCurrent : 0);
                }
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attTransactionId
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function update (int $attTransactionId, array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attTransactionId, 'attTransactionId');
            if (!isset($columns['attTypeId']) && !isset($columns['attTransactionResult'])) {
                throw new Exception(Constant::$attTransaction['errUpdateValidation'], 31);
            }
            $attTransaction = DbMysql::select('att_transaction', array('attTransactionId'=>$attTransactionId), true);
            $attParticipant = DbMysql::select('att_participant', array('attParticipantId'=>$attTransaction['attParticipantId']), true);
            $attGroup = DbMysql::select('att_group', array('attGroupId'=>$attTransaction['attGroupId']), true);
            $shiftTime = $this->getShiftTime(new DateTime($attTransaction['attTransactionDate']), $attGroup, $attParticipant['attParticipantShiftMode'], ($columns['attTypeId'] ?? $attTransaction['attTypeId']));
            $columns['attTransactionShiftStart'] = $shiftTime[0];
            $columns['attTransactionShiftEnd'] = $shiftTime[1];
            DbMysql::update('att_transaction', $columns, array('attTransactionId'=>$attTransactionId));
            $this->set($attTransactionId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMobileInfo (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            $dateNow = new DateTime();
            $dateProcess = new DateTime();
            $infoArr = array('date'=>null, 'attTransactionId'=>null, 'currentTime'=>$dateProcess->format('j/n/Y g:i:s A'), 'currentShift'=>null, 'button'=>null, 'status'=>null, 'shiftStart'=>null, 'shiftEnd'=>null, 'timeClockIn'=>null, 'timeClockOut'=>null,
                'duration'=>null, 'weeklyRequiredHours'=>null, 'weeklyDuration'=>null, 'weeklyProgress'=>null, 'nextShiftStart'=>null, 'remark'=>null);
            $nextCheckIn = false;

            if (intval($dateProcess->format('g')) < 12) {
                $dateTemp = new DateTime();
                $dateTemp->sub(new DateInterval('P1D'));
                $attTransactionTemp = DbMysql::select('att_transaction', array('userId'=>$this->userId, 'attTransactionDate'=>$dateTemp->format('Y-m-d'), 'attTypeId'=>'IN|3,10,11'));
                if (!empty($attTransactionTemp)) {
                    $shiftStart = new DateTime($attTransactionTemp['attTransactionShiftStart']);
                    $intervalStart = $dateProcess->diff($shiftStart);
                    $diffStart = floatval($intervalStart->format('%r%h.%I'));
                    $shiftEnd = new DateTime($attTransactionTemp['attTransactionShiftEnd']);
                    $intervalEnd = $dateProcess->diff($shiftEnd);
                    $diffEnd = floatval($intervalEnd->format('%r%h.%I'));
                    if ($attTransactionTemp['attTransactionStatus'] === 'Ready' && $diffStart <= 2 && $diffEnd > 0) {
                        $dateProcess->sub(new DateInterval('P1D'));
                    } else if ($attTransactionTemp['attTransactionStatus'] === 'Checked In' && $diffEnd >= -3) {
                        $dateProcess->sub(new DateInterval('P1D'));
                    }
                }
            }
            $infoArr['date'] = $dateProcess->format('j/n/Y');

            $attTransaction = DbMysql::select('att_transaction', array('userId'=>$this->userId, 'attTransactionDate'=>$dateProcess->format('Y-m-d')));
            if (empty($attTransaction)) {
                $infoArr['status'] = 'Not Available';
            }
            else {
                $attTransactionId = $attTransaction['attTransactionId'];
                $dbDuration = DbMysql::selectSql(
                    /** @lang text */
                    "SELECT 
                        WEEK(att_transaction_date) AS week_no,
                        SEC_TO_TIME(SUM(TO_SECONDS(att_transaction_time_out) - TO_SECONDS(att_transaction_time_in))) AS time_diff,	
                        SUM(TO_SECONDS(att_transaction_time_out) - TO_SECONDS(att_transaction_time_in)) AS time_diff_sec	
                    FROM att_transaction t
                    INNER JOIN (SELECT user_id, WEEK(att_transaction_date) AS week_no FROM att_transaction WHERE att_transaction_id = $attTransactionId) a ON a.week_no = WEEK(t.att_transaction_date) AND a.user_id = t.user_id
                    GROUP BY week_no",
                    array(), true);
                $infoArr['attTransactionId'] = $attTransactionId;
                $infoArr['weeklyDuration'] = parent::timeDisplay($dbDuration['timeDiff'], true);
                $infoArr['weeklyRequiredHours'] = DbMysql::selectColumn('att_participant', array('attParticipantId'=>$attTransaction['attParticipantId']), 'attParticipantReqWeekHours', true).' hours';
                $infoArr['weeklyProgress'] = !empty($dbDuration['timeDiff']) ? round(intval($dbDuration['timeDiffSec'])/(intval($infoArr['weeklyRequiredHours'])*60*60)*100, 2).'%' : '0%';

                $infoArr['shiftStart'] = parent::timeDisplayPretty($attTransaction['attTransactionShiftStart']);
                $infoArr['shiftEnd'] = parent::timeDisplayPretty($attTransaction['attTransactionShiftEnd']);
                $infoArr['timeClockIn'] = parent::timeDisplayPretty($attTransaction['attTransactionTimeIn'], true);
                $infoArr['timeClockOut'] = parent::timeDisplayPretty($attTransaction['attTransactionTimeOut'], true);
                $infoArr['currentShift'] = DbMysql::selectColumn('att_type', array('attTypeId'=>$attTransaction['attTypeId']), 'attTypeName', true);

                if ($attTransaction['attTransactionResult'] === 'Leave' || $attTransaction['attTransactionResult'] === 'Training') {
                    $infoArr['status'] = $attTransaction['attTransactionResult'];
                    $nextCheckIn = true;
                }
                else if ($attTransaction['attTransactionStatus'] === 'Checked Out') {
                    $infoArr['status'] = 'Checked Out';
                    $nextCheckIn = true;
                    parent::checkEmptyString($attTransaction['attTransactionTimeIn'], 'attTransactionTimeIn');
                    parent::checkEmptyString($attTransaction['attTransactionTimeOut'], 'attTransactionTimeOut');
                    $timeIn = new DateTime($attTransaction['attTransactionTimeIn']);
                    $timeOut = new DateTime($attTransaction['attTransactionTimeOut']);
                    $intervalDuration = $timeIn->diff($timeOut);
                    $infoArr['duration'] = parent::timeDisplay($intervalDuration->format('%H:%i:%s'), true);
                }
                else {
                    $shiftStart = new DateTime($attTransaction['attTransactionShiftStart']);
                    $intervalStart = $dateNow->diff($shiftStart);
                    $diffStart = floatval($intervalStart->format('%r%h.%I'));
                    $shiftEnd = new DateTime($attTransaction['attTransactionShiftEnd']);
                    $intervalEnd = $dateNow->diff($shiftEnd);
                    $diffEnd = floatval($intervalEnd->format('%r%h.%I'));
                    if ($attTransaction['attTransactionStatus'] === 'Ready') {
                        if ($diffStart > 2) {
                            $infoArr['status'] = 'Not Available';
                            $infoArr['remark'] = 'Check in started 2 hours before working / shift start time';
                        } else if ($diffEnd <= 0) {
                            $infoArr['status'] = 'Not Available';
                            $infoArr['remark'] = 'Check in should be before working / shift end time';
                            $nextCheckIn = true;
                        } else {
                            $infoArr['status'] = 'Ready';
                            $infoArr['button'] = 'Check In';
                        }
                    } else if ($attTransaction['attTransactionStatus'] === 'Checked In') {
                        parent::checkEmptyString($attTransaction['attTransactionTimeIn'], 'attTransactionTimeIn');
                        $timeIn = new DateTime($attTransaction['attTransactionTimeIn']);
                        $intervalDuration = $timeIn->diff($dateNow);
                        $infoArr['duration'] = parent::timeDisplay($intervalDuration->format('%H:%i:%s'), true);
                        if ($diffEnd < -3) {
                            $infoArr['status'] = 'Not Available';
                            $infoArr['remark'] = 'Check out only available 3 hours after working / shift end time';
                            $nextCheckIn = true;
                        } else {
                            $infoArr['status'] = 'Checked In';
                            $infoArr['button'] = 'Check Out';
                        }
                    } else {
                        $infoArr['status'] = 'Not Available';
                        $nextCheckIn = true;
                    }
                }
            }

            if ($nextCheckIn) {
                $nextCheckIn = DbMysql::selectColumn('att_transaction', array('userId'=>$this->userId, 'attTransactionDate'=>'>|'.$dateNow->format('Y-m-d H:i:s'), 'attTransactionShiftStart'=>'IS NOT NULL'), 'attTransactionShiftStart');
                $infoArr['nextShiftStart'] = !empty($nextCheckIn) ? 'Next shift started at '.parent::timeDisplayPretty($nextCheckIn) : null;
            }

            return $infoArr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param string $date
     * @return array
     * @throws Exception
     */
    public function getMobileCalendarInfo (string $date): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            $dateProcess = DateTime::createFromFormat('Y-m-d', $date);
            if ($dateProcess === false) {
                throw new Exception('Invalid date format - '.$date);
            }
            $dateNow = new DateTime();
            $infoArr = array('date'=>null, 'currentTime'=>$dateProcess->format('j/n/Y g:i:s A'), 'currentShift'=>null, 'status'=>null, 'shiftStart'=>null, 'shiftEnd'=>null, 'timeClockIn'=>null, 'timeClockOut'=>null, 'duration'=>null);
            $infoArr['date'] = $dateProcess->format('j/n/Y');
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, '$dateProcess = '.$dateProcess->format('c'));

            $attTransaction = DbMysql::select('att_transaction', array('userId'=>$this->userId, 'attTransactionDate'=>$dateProcess->format('Y-m-d')));
            if (empty($attTransaction)) {
                $infoArr['status'] = 'Not Available';
            }
            else {
                $infoArr['shiftStart'] = parent::timeDisplayPretty($attTransaction['attTransactionShiftStart']);
                $infoArr['shiftEnd'] = parent::timeDisplayPretty($attTransaction['attTransactionShiftEnd']);
                $infoArr['timeClockIn'] = parent::timeDisplayPretty($attTransaction['attTransactionTimeIn'], true);
                $infoArr['timeClockOut'] = parent::timeDisplayPretty($attTransaction['attTransactionTimeOut'], true);
                $infoArr['currentShift'] = DbMysql::selectColumn('att_type', array('attTypeId'=>$attTransaction['attTypeId']), 'attTypeName', true);

                if ($attTransaction['attTransactionResult'] === 'Leave' || $attTransaction['attTransactionResult'] === 'Training') {
                    $infoArr['status'] = $attTransaction['attTransactionResult'];
                }
                else if ($attTransaction['attTransactionStatus'] === 'Checked Out') {
                    $infoArr['status'] = 'Checked Out';
                    parent::checkEmptyString($attTransaction['attTransactionTimeIn'], 'attTransactionTimeIn');
                    parent::checkEmptyString($attTransaction['attTransactionTimeOut'], 'attTransactionTimeOut');
                    $timeIn = new DateTime($attTransaction['attTransactionTimeIn']);
                    $timeOut = new DateTime($attTransaction['attTransactionTimeOut']);
                    $intervalDuration = $timeIn->diff($timeOut);
                    $infoArr['duration'] = parent::timeDisplay($intervalDuration->format('%H:%i:%s'), true);
                }
                else if ($attTransaction['attTransactionStatus'] === 'Checked In') {
                    $infoArr['status'] = 'Checked In';
                    parent::checkEmptyString($attTransaction['attTransactionTimeIn'], 'attTransactionTimeIn');
                    $timeIn = new DateTime($attTransaction['attTransactionTimeIn']);
                    $intervalDuration = $timeIn->diff($dateNow);
                    $infoArr['duration'] = parent::timeDisplay($intervalDuration->format('%H:%i:%s'), true);
                }
                else {
                    $shiftStart = new DateTime($attTransaction['attTransactionShiftStart']);
                    $infoArr['status'] = $dateNow > $shiftStart ? 'Absent' : 'Ready';
                }
            }

            return $infoArr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $year
     * @param int $month
     * @return array
     * @throws Exception
     */
    public function getMobileCalendarDot (int $year, int $month): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');
            parent::checkEmptyInteger($this->userId, 'userId');

            $returnArr = array();
            $dateNow = new DateTime();
            $dateNow->setTime(0, 0);
            $dateProcess = new DateTime();
            $dateProcess->setTime(0, 0);
            $endOfMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            for ($day = 1; $day <= $endOfMonth; $day++) {
                $dateProcess->setDate($year, $month, $day);
                $returnArr[$day] = array('date'=>$dateProcess->format('Y-m-d'), 'status'=>null, 'color'=>null);
            }

            $attTransactionArr = DbMysql::selectAll('att_transaction', array('userId'=>$this->userId, 'year(attTransactionDate)'=>$year, 'month(attTransactionDate)'=>$month), 0, false, 'att_transaction_date');
            foreach ($attTransactionArr as $attTransaction) {
                $dateTransaction = new DateTime($attTransaction['attTransactionDate']);
                $dateIndex = intval($dateTransaction->format('d'));
                $status = !empty($attTransaction['attTransactionResult']) ? $attTransaction['attTransactionResult'] : $attTransaction['attTransactionStatus'];
                $returnArr[$dateIndex]['status'] = $status;
                if ($attTransaction['attTypeId'] === 12 || $attTransaction['attTypeId'] === 13) {
                    $returnArr[$dateIndex]['color'] = null;
                    $returnArr[$dateIndex]['status'] = 'Weekend';
                } else if ($status === 'Leave') {
                    $returnArr[$dateIndex]['color'] = 'orange';
                    $returnArr[$dateIndex]['color'] = null;
                } else if ($status === 'Present') {
                    $returnArr[$dateIndex]['color'] = 'green';
                } else if ($status === 'Absent') {
                    $returnArr[$dateIndex]['color'] = 'red';
                } else if ($status === 'Ready') {
                    $intervalDuration = $dateNow->diff($dateTransaction);
                    if ($intervalDuration->format('%r') === '-') {
                        $returnArr[$dateIndex]['status'] = 'Absent';
                        $returnArr[$dateIndex]['color'] = 'red';
                    } else {
                        $returnArr[$dateIndex]['color'] = 'blue';
                    }
                }
            }
            return $returnArr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attTransactionId
     * @param array $params
     * @return void
     * @throws Exception
     */
    public function checkIn (int $attTransactionId, array $params): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attTransactionId, 'attTransactionId');
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkMandatoryArray($params, array('latitude', 'longitude'));
            $attTransaction = DbMysql::select('att_transaction', array('attTransactionId'=>$attTransactionId), true);
            $attTypeMode = DbMysql::selectColumn('att_type', array('attTypeId'=>$attTransaction['attTypeId']), 'attTypeMode', true);
            if ($attTransaction['attTransactionStatus'] === 'Ready') {
                if ($attTransaction['userId'] !== $this->userId) {
                    throw new Exception('You are not allowed to check in this attendance. Please contact administrator!', 31);
                } else if ($attTypeMode === 'Leave') {
                    throw new Exception('No need to perform attendance check in because your current status is on leave. Please contact administrator!', 31);
                } else if ($attTypeMode === 'Training') {
                    throw new Exception('No need to perform attendance check in because you are currently in training. Please contact administrator!', 31);
                }
                DbMysql::update('att_transaction', array('attTransactionTimeIn'=>'NOW()', 'attTransactionResult'=>'Present', 'attTransactionStatus'=>'Checked In', 'attTransactionLocationIn'=>'|ST_GEOMFROMTEXT(\'POINT('.$params['latitude'].' '.$params['longitude'].')\')'),
                    array('attTransactionId'=>$attTransactionId));
                $this->set($attTransactionId);
            } else if ($attTransaction['attTransactionStatus'] === 'Checked In') {
                throw new Exception('This attendance already checked in. Please refresh the page!', 31);
            } else if ($attTransaction['attTransactionStatus'] === 'Checked Out') {
                throw new Exception('This attendance already checked out. Please refresh the page!', 31);
            } else {
                throw new Exception('Invalid Attendance Transaction Status');
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attTransactionId
     * @param array $params
     * @return void
     * @throws Exception
     */
    public function checkOut (int $attTransactionId, array $params): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attTransactionId, 'attTransactionId');
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkMandatoryArray($params, array('latitude', 'longitude'));
            $attTransaction = DbMysql::select('att_transaction', array('attTransactionId'=>$attTransactionId), true);
            $attTypeMode = DbMysql::selectColumn('att_type', array('attTypeId'=>$attTransaction['attTypeId']), 'attTypeMode', true);
            if ($attTransaction['attTransactionStatus'] === 'Ready') {
                throw new Exception('This attendance not yet checked in. Please refresh the page!', 31);
            } else if ($attTransaction['attTransactionStatus'] === 'Checked In') {
                if ($attTransaction['userId'] !== $this->userId) {
                    throw new Exception('You are not allowed to check in this attendance. Please contact administrator!', 31);
                } else if ($attTypeMode === 'Leave') {
                    throw new Exception('No need to perform attendance check in because your current status is on leave. Please contact administrator!', 31);
                } else if ($attTypeMode === 'Training') {
                    throw new Exception('No need to perform attendance check in because you are currently in training. Please contact administrator!', 31);
                }
                DbMysql::update('att_transaction', array('attTransactionTimeOut'=>'NOW()', 'attTransactionStatus'=>'Checked Out', 'attTransactionLocationOut'=>'|ST_GEOMFROMTEXT(\'POINT('.$params['latitude'].' '.$params['longitude'].')\')'),
                    array('attTransactionId'=>$attTransactionId));
                $this->set($attTransactionId);
            } else if ($attTransaction['attTransactionStatus'] === 'Checked Out') {
                throw new Exception('This attendance already checked out. Please refresh the page!', 31);
            } else {
                throw new Exception('Invalid Attendance Transaction Status');
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $siteId
     * @return string
     * @throws Exception
     */
    public function getSiteName (int $siteId): string {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            return DbMysql::selectColumn('cli_site', array('siteId'=>$siteId), 'siteName', true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attGroupId
     * @return string
     * @throws Exception
     */
    public function getAttGroupName (int $attGroupId): string {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attGroupId, 'attGroupId');
            return DbMysql::selectColumn('att_group', array('attGroupId'=>$attGroupId), 'attGroupName', true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attGroupId
     * @param string $attTransactionDate
     * @return array
     * @throws Exception
     */
    public function getDailyGroup (int $attGroupId, string $attTransactionDate): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attGroupId, 'attGroupId');
            parent::checkEmptyString($attTransactionDate, 'attTransactionDate');
            $transactionList = DbMysql::selectSqlAll(
            /** @lang text */
                "SELECT
                    CASE 
                        WHEN att_transaction_result IS NOT NULL THEN att_transaction_result					
                        WHEN att_transaction_status <> 'Ready' THEN 'Present'	
                        WHEN y.att_type_mode IN ('Leave', 'Training') THEN y.att_type_name
                        WHEN att_transaction_date < CURDATE() AND y.att_type_mode IN ('Normal', '2 Shifts', '3 Shifts') THEN 'Absent'
                        ELSE NULL 
                    END AS result,
                    TIMEDIFF(att_transaction_shift_start, att_transaction_time_in) AS duration_in,
                    TIMEDIFF(att_transaction_shift_end, att_transaction_time_out) AS duration_out,		
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
                    t.att_group_id,
                    p.asset_group_id,
                    t.att_type_id,                    
                    p.att_participant_shift_mode,
                    t.att_transaction_shift_start,
                    t.att_transaction_shift_end,
                    t.att_transaction_time_in,
                    t.att_transaction_time_out,
                    t.att_transaction_result,
                    t.att_transaction_status,
					p.att_participant_status
                FROM att_transaction t
                LEFT JOIN att_group g ON g.att_group_id = t.att_group_id
                LEFT JOIN att_participant p ON p.att_participant_id = t.att_participant_id
                LEFT JOIN att_type y ON y.att_type_id = t.att_type_id",
                array('t.attGroupId'=>$attGroupId, 'attTransactionDate'=>$attTransactionDate));
            foreach ($transactionList as $i=>$transaction) {
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
                $transactionList[$i] = $transaction;
            }
            return $transactionList;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}