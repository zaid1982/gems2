<?php

class NotiWeb extends General {

    private static $tableName = 'noti_web';
    private static $idName = 'notiWebId';

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

            // Check if user account is active before inserting web notification
            $userStatus = DbMysql::selectColumn('sys_user', array('userId'=>$userId), 'userStatus');
            if (empty($userStatus) || $userStatus !== 1) {
                parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Skipping web notification for disabled user: '.$userId);
                return; // Skip web notification for disabled accounts
            }

            $columns = array('userId'=>$userId, 'notiWebType'=>$type);
            if ($type === 1 || $type === 2) {
                $columns['notiWebText'] = '<b>'.$info.'</b> to be assigned';
                $columns['notiWebTitle'] = $type === 2 ? 'Work Request' : 'Work Order';
                $columns['notiWebIcon'] = 'fa-user-plus';
                $columns['notiWebColor'] = 'winter-neva-gradient';
                $columns['notiWebLink'] = 'p_wo_assign';
                $columns['navId'] = 23;
                $columns['navSecondId'] = 54;
            } else if ($type === 3) {
                $columns['notiWebText'] = '<b>'.$info.'</b> to be verified';
                $columns['notiWebTitle'] = 'Work Order';
                $columns['notiWebIcon'] = 'fa-list-check';
                $columns['notiWebColor'] = 'warm-flame-gradient';
                $columns['notiWebLink'] = 'p_wo_verify';
                $columns['navId'] = 23;
                $columns['navSecondId'] = 55;
            } else if ($type === 4) {
                $columns['notiWebText'] = '<b>WO Images</b> zip file';
                $columns['notiWebTitle'] = 'WO Images';
                $columns['notiWebIcon'] = 'fa-file-zipper';
                $columns['notiWebColor'] = 'tempting-azure-gradient';
                $columns['notiWebLink'] = $info;
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
            $notiWeb = $this->get($notiWebId);
            $this->unlinkType4($notiWeb);
            DbMysql::delete($this::$tableName, array($this::$idName=>$notiWebId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Clears every web notification for the JWT user. Type-4 rows unlink
     * their zip the same way single delete does.
     * @return void
     * @throws Exception
     */
    public function deleteByUserId (): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            $rows = DbMysql::selectAll($this::$tableName, array('userId'=>$this->userId), 0, false);
            foreach ($rows as $row) {
                $this->unlinkType4($row);
            }
            DbMysql::delete($this::$tableName, array('userId'=>$this->userId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $row
     * @return void
     */
    private function unlinkType4 (array $row): void {
        if (intval($row['notiWebType'] ?? 0) !== 4) {
            return;
        }
        $fileLink = str_replace('api/', '', $row['notiWebLink'] ?? '');
        if ($fileLink !== '' && file_exists($fileLink)) {
            unlink($fileLink);
        }
    }
}