<?php

class NotiMobile extends General {

    private static $tableName = 'noti_log';
    private static $idName = 'notiLogId';

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
            $totalRow = DbMysql::count($this::$tableName, array('userId' => $this->userId));
            $rows = DbMysql::selectAll(
                $this::$tableName,
                array('userId' => $this->userId),
                0,
                false,
                'notiLogTimeSent',
                'desc',
                '50'
            );
            return array('total' => $totalRow, 'data' => $rows);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getUnreadCount (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $count = DbMysql::count($this::$tableName, array(
                'userId' => $this->userId,
                'notiLogReadAt' => 'IS NULL',
            ));
            return array('count' => $count);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $notiLogId
     * @return void
     * @throws Exception
     */
    public function markRead (int $notiLogId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($notiLogId, $this::$idName);
            $row = DbMysql::select($this::$tableName, array(
                $this::$idName => $notiLogId,
                'userId' => $this->userId,
            ), 1);
            if (empty($row)) {
                throw new Exception('Notification not found');
            }
            DbMysql::update(
                $this::$tableName,
                array('notiLogReadAt' => date('Y-m-d H:i:s')),
                array($this::$idName => $notiLogId, 'userId' => $this->userId)
            );
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function markAllRead (): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            DbMysql::update(
                $this::$tableName,
                array('notiLogReadAt' => date('Y-m-d H:i:s')),
                array('userId' => $this->userId, 'notiLogReadAt' => 'IS NULL')
            );
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }
}
