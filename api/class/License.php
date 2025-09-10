<?php

class License extends General {

    public $licenseId = 0;
    public $licenseTitle = '';

    private static $tableName = 'lic_license';
    private static $idName = 'licenseId';

    // Document type ID for uploads (ensure exists in ref_document)
    private static $documentId = 29; // 'License Document'

    function __construct(int $userId = 0, bool $isLogged = false)
    {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * List licenses for current site with computed daysToExpire and status
     * status: 1=Valid (>30 days), 2=Warning (0..30 days), 3=Expired (<0 days)
     * @return array
     * @throws Exception
     */
    public function getList(): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userSite, 'userSite');
            $sql = /** @lang text */
                "SELECT 
                    l.license_id        AS licenseId,
                    l.site_id           AS siteId,
                    l.license_title     AS licenseTitle,
                    DATE_FORMAT(l.license_start_date, '%Y-%m-%d') AS licenseStartDate,
                    DATE_FORMAT(l.license_end_date, '%Y-%m-%d')   AS licenseEndDate,
                    l.upload_id         AS uploadId,
                    l.license_status    AS licenseStatus,
                    DATEDIFF(l.license_end_date, CURDATE()) AS daysToExpire,
                    CASE 
                        WHEN DATEDIFF(l.license_end_date, CURDATE()) < 0 THEN 3
                        WHEN DATEDIFF(l.license_end_date, CURDATE()) <= 30 THEN 2
                        ELSE 1
                    END AS expiryStatus
                FROM lic_license l";
            $where = array('l.site_id'=>$this->userSite, 'l.license_status'=>1);
            return DbMysql::selectSqlAll($sql, $where, 0, false, 'license_end_date', 'ASC');
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Get single license
     * @param int $licenseId
     * @return array
     * @throws Exception
     */
    public function get(int $licenseId): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($licenseId, self::$idName);
            return DbMysql::select(self::$tableName, array(self::$idName=>$licenseId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Insert new license
     * Expected columns: licenseTitle, licenseStartDate (YYYY-MM-DD), licenseEndDate (YYYY-MM-DD), fileUpload (optional upload object)
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function insert(array $columns): void
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkMandatoryArray($columns, array('licenseTitle', 'licenseStartDate', 'licenseEndDate'));
            parent::checkEmptyInteger($this->userSite, 'userSite');

            $insertArr = array(
                'siteId' => $this->userSite,
                'licenseTitle' => trim($columns['licenseTitle']),
                'licenseStartDate' => $columns['licenseStartDate'],
                'licenseEndDate' => $columns['licenseEndDate'],
                'licenseStatus' => 1,
                'licenseCreatedBy' => $this->userId
            );

            if (!empty($columns['fileUpload']) && is_array($columns['fileUpload'])) {
                $uploadTemp = parent::uploadPrepare($columns['fileUpload'], self::$documentId);
                if (is_array($uploadTemp) && !empty($uploadTemp)) {
                    $filename = (new DateTime())->format('YmdHis') . '_LIC_' . $this->userId;
                    $uploadId = parent::uploadSave($uploadTemp, 'license', $filename);
                    $insertArr['uploadId'] = $uploadId;
                }
            }

            $this->set(DbMysql::insert(self::$tableName, $insertArr));
            $this->licenseTitle = $insertArr['licenseTitle'];
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Update license
     * @param int $licenseId
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function update(int $licenseId, array $columns): void
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($licenseId, self::$idName);
            parent::checkMandatoryArray($columns, array('licenseTitle', 'licenseStartDate', 'licenseEndDate'));

            $updateArr = array(
                'licenseTitle' => trim($columns['licenseTitle']),
                'licenseStartDate' => $columns['licenseStartDate'],
                'licenseEndDate' => $columns['licenseEndDate'],
                'licenseUpdatedBy' => $this->userId
            );

            if (!empty($columns['fileUpload']) && is_array($columns['fileUpload'])) {
                // Ensure optional keys exist
                $columns['fileUpload']['width'] = $columns['fileUpload']['width'] ?? null;
                $columns['fileUpload']['height'] = $columns['fileUpload']['height'] ?? null;
                $uploadTemp = parent::uploadPrepare($columns['fileUpload'], self::$documentId);
                if (is_array($uploadTemp) && !empty($uploadTemp)) {
                    $filename = (new DateTime())->format('YmdHis') . '_LIC_' . $this->userId;
                    $uploadId = parent::uploadSave($uploadTemp, 'license', $filename);
                    $updateArr['uploadId'] = $uploadId;
                }
            }

            // Enforce site scoping on update
            $siteId = DbMysql::selectColumn(self::$tableName, array(self::$idName=>$licenseId), 'site_id', true);
            parent::checkEmptyInteger($siteId, 'site_id');
            if (!$this->isAdministrator() && $this->userSite && intval($siteId) !== intval($this->userSite)) {
                throw new Exception('Access denied to update license from different site');
            }
            DbMysql::update(self::$tableName, $updateArr, array(self::$idName => $licenseId));
            $this->set($licenseId);
            $this->licenseTitle = $updateArr['licenseTitle'];
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Soft delete license (set status=2)
     * @param int $licenseId
     * @return void
     * @throws Exception
     */
    public function delete(int $licenseId): void
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($licenseId, self::$idName);
            DbMysql::update(self::$tableName, array('licenseStatus' => 2, 'licenseUpdatedBy'=>$this->userId), array(self::$idName => $licenseId));
            $this->set($licenseId);
            $this->licenseTitle = DbMysql::selectColumn(self::$tableName, array(self::$idName=>$licenseId), 'licenseTitle', true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Set current licenseId
     * @param int $licenseId
     * @return void
     * @throws Exception
     */
    public function set(int $licenseId): void
    {
        try {
            parent::checkEmptyInteger($licenseId, self::$idName);
            $this->licenseId = $licenseId;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }
}
