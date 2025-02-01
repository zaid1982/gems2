<?php

class WoTaskUpload extends General {
    private static $tableName = 'wo_task_upload';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $woTaskPublicId
     * @return array
     * @throws Exception
     */
    public function get (int $woTaskPublicId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskPublicId, 'woTaskPublicId');
            return DbMysql::select($this::$tableName, array('woTaskPublicId'=>$woTaskPublicId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskId
     * @return array
     * @throws Exception
     */
    public function getByWoTaskId (int $woTaskId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskId, 'woTaskId');
            $rows = DbMysql::selectSqlAll(
                /** @lang text */
                "SELECT
                        upl.upload_folder,
                        upl.upload_filename,
                        upl.upload_extension,
                        upl.upload_name,
                        upl.upload_uplname,
                        upl.upload_file_width,
                        upl.upload_file_height,
                        tpl.*
                    FROM wo_task_upload tpl 
                    LEFT JOIN sys_upload upl ON upl.upload_id = tpl.upload_id",
                array('woTaskId'=>$woTaskId));
            $results = array(array(), array(), array(), array(), array());
            foreach ($rows as $row) {
                $row['url'] = Constant::$url.$row['uploadFolder'].'/'.$row['uploadFilename'].'.'.$row['uploadExtension'];
                $results[$row['woTaskUploadType']][] = $row;
            }
            return $results;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }
}