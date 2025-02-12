<?php

class NotiWeb extends General {

    private static $tableName = 'noti_web';
    private static $idName = 'notiWebId';

    function __construct(int $userId = 0, bool $isLogged = false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getByUserId (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $totalRow = DbMysql::count($this::$tableName, array('userId'=>$this->userId));
            $rows = DbMysql::selectAll($this::$tableName, array('userId'=>$this->userId), 0, false, 'notiWebTimestamp', 'desc', '50');
            return array('total' => $totalRow, 'data'=>$rows);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $type
     * @param int $userId
     * @param string $info
     * @return void
     * @throws Exception
     */
    public function insert (int $type, int $userId, string $info): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($type, 'type');
            parent::checkEmptyInteger($userId, 'userId');
            $columns = array('userId'=>$userId, 'notiWebType'=>$type);
            if ($type === 1 || $type === 2) {
                $columns['notiWebText'] = '<b>'.$info.'</b> to be assigned';
                $columns['notiWebTitle'] = $type === 2 ? 'Work Request' : 'Work Order';
                $columns['notiWebIcon'] = 'fa-user-plus';
                $columns['notiWebColor'] = 'winter-neva-gradient';
                $columns['notiWebTitle'] = 'p_wo_assign';
                $columns['navId'] = 23;
                $columns['navSecondId'] = 54;
            } else if ($type === 3) {
                $columns['notiWebText'] = '<b>'.$info.'</b> to be verified';
                $columns['notiWebTitle'] = 'Work Order';
                $columns['notiWebIcon'] = 'fa-list-check';
                $columns['notiWebColor'] = 'warm-flame-gradient';
                $columns['notiWebTitle'] = 'p_wo_verify';
                $columns['navId'] = 23;
                $columns['navSecondId'] = 55;
            }
            if (isset($columns['notiWebText'])) {
                DbMysql::insert($this::$tableName, $columns);
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $notiWebId
     * @return void
     * @throws Exception
     */
    public function delete (int $notiWebId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($notiWebId, $this::$idName);
            DbMysql::delete($this::$tableName, array($this::$idName=>$notiWebId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}