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
            if ($notiWeb['notiWebType'] === 4) {
                $fileLink = str_replace('api/', '', $notiWeb['notiWebLink']);
                if (file_exists($fileLink)) {
                    unlink($fileLink);
                }
            }
            DbMysql::delete($this::$tableName, array($this::$idName=>$notiWebId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}