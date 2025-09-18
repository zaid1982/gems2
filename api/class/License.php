<?php

class License extends General {

    public $licenseId = 0;
    public $licenseTitle = '';

    private static $tableName = 'lic_license';
    private static $idName = 'license_id';

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
                    l.site_id           AS site_id,
                    l.license_title     AS licenseTitle,
                    NULLIF(DATE_FORMAT(l.license_start_date, '%Y-%m-%d'), '0000-00-00') AS licenseStartDate,
                    NULLIF(DATE_FORMAT(l.license_end_date, '%Y-%m-%d'), '0000-00-00')   AS licenseEndDate,
                    l.upload_id         AS uploadId,
                    l.warning_days      AS warningDays,
                    NULLIF(DATE_FORMAT(l.warning_date, '%Y-%m-%d'), '0000-00-00') AS warningDate,
                    l.license_status    AS licenseStatus,
                    CASE WHEN l.license_end_date IS NULL OR l.license_end_date = '0000-00-00' THEN NULL
                         ELSE DATEDIFF(l.license_end_date, CURDATE()) END AS daysToExpire,
                    CASE 
                        WHEN l.license_end_date IS NULL OR l.license_end_date = '0000-00-00' THEN NULL
                        WHEN DATEDIFF(l.license_end_date, CURDATE()) < 0 THEN 3
                        WHEN l.warning_date IS NOT NULL AND CURDATE() >= l.warning_date THEN 2
                        WHEN DATEDIFF(l.license_end_date, CURDATE()) <= COALESCE(l.warning_days, 30) THEN 2
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
            $sql = /** @lang text */
                "SELECT 
                    l.license_id        AS licenseId,
                    l.site_id           AS siteId,
                    l.site_id           AS site_id,
                    l.license_title     AS licenseTitle,
                    NULLIF(DATE_FORMAT(l.license_start_date, '%Y-%m-%d'), '0000-00-00') AS licenseStartDate,
                    NULLIF(DATE_FORMAT(l.license_end_date, '%Y-%m-%d'), '0000-00-00')   AS licenseEndDate,
                    l.upload_id         AS uploadId,
                    l.warning_days      AS warningDays,
                    NULLIF(DATE_FORMAT(l.warning_date, '%Y-%m-%d'), '0000-00-00') AS warningDate,
                    l.license_status    AS licenseStatus
                FROM lic_license l";
            $where = array('l.license_id' => $licenseId);
            return DbMysql::selectSql($sql, $where, 0, false);
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

            // Normalize and validate dates (expect YYYY-MM-DD)
            $start = $this->normalizeDate($columns['licenseStartDate']);
            $end = $this->normalizeDate($columns['licenseEndDate']);
            if ($start === null || $end === null) {
                throw new Exception('Invalid start/end date');
            }

            $insertArr = array(
                'site_id' => $this->userSite,
                'license_title' => trim($columns['licenseTitle']),
                'license_start_date' => $start,
                'license_end_date' => $end,
                'license_status' => 1,
                'license_created_by' => $this->userId
            );

            // Optional warningDays (per-license warning threshold in days)
            if (isset($columns['warningDays']) && $columns['warningDays'] !== '') {
                $wd = intval($columns['warningDays']);
                if ($wd < 0) { throw new Exception('warningDays must be >= 0'); }
                // cap to 10 years worth of days
                if ($wd > 3650) { $wd = 3650; }
                $insertArr['warning_days'] = $wd;
            }

            if (!empty($columns['fileUpload']) && is_array($columns['fileUpload'])) {
                $uploadTemp = parent::uploadPrepare($columns['fileUpload'], self::$documentId);
                if (is_array($uploadTemp) && !empty($uploadTemp)) {
                    $filename = (new DateTime())->format('YmdHis') . '_LIC_' . $this->userId;
                    $uploadId = parent::uploadSave($uploadTemp, 'license', $filename);
                    $insertArr['upload_id'] = $uploadId;
                }
            }

            // Optional warningDate (takes precedence over warningDays when set)
            if (isset($columns['warningDate']) && $columns['warningDate'] !== '') {
                $wdate = $this->normalizeDate($columns['warningDate']);
                if ($wdate === null) { throw new Exception('Invalid warningDate'); }
                // Validate range: start <= warningDate <= end
                $sd = new DateTime($start);
                $ed = new DateTime($end);
                $wd = new DateTime($wdate);
                if ($wd < $sd || $wd > $ed) {
                    throw new Exception('warningDate must be within Start and End dates');
                }
                $insertArr['warning_date'] = $wdate;
            }

            $this->set(DbMysql::insert(self::$tableName, $insertArr));
            $this->licenseTitle = $insertArr['license_title'];
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

            $start = $this->normalizeDate($columns['licenseStartDate']);
            $end = $this->normalizeDate($columns['licenseEndDate']);
            if ($start === null || $end === null) {
                throw new Exception('Invalid start/end date');
            }

            $updateArr = array(
                'license_title' => trim($columns['licenseTitle']),
                'license_start_date' => $start,
                'license_end_date' => $end,
                'license_updated_by' => $this->userId
            );

            if (array_key_exists('warningDays', $columns)) {
                if ($columns['warningDays'] === '' || is_null($columns['warningDays'])) {
                    $updateArr['warning_days'] = null;
                } else {
                    $wd = intval($columns['warningDays']);
                    if ($wd < 0) { throw new Exception('warningDays must be >= 0'); }
                    if ($wd > 3650) { $wd = 3650; }
                    $updateArr['warning_days'] = $wd;
                }
            }

            if (!empty($columns['fileUpload']) && is_array($columns['fileUpload'])) {
                // Ensure optional keys exist
                $columns['fileUpload']['width'] = $columns['fileUpload']['width'] ?? null;
                $columns['fileUpload']['height'] = $columns['fileUpload']['height'] ?? null;
                $uploadTemp = parent::uploadPrepare($columns['fileUpload'], self::$documentId);
                if (is_array($uploadTemp) && !empty($uploadTemp)) {
                    $filename = (new DateTime())->format('YmdHis') . '_LIC_' . $this->userId;
                    $uploadId = parent::uploadSave($uploadTemp, 'license', $filename);
                    $updateArr['upload_id'] = $uploadId;
                }
            }

            // Optional warningDays (kept for backward compatibility)
            if (array_key_exists('warningDays', $columns)) {
                if ($columns['warningDays'] === '' || is_null($columns['warningDays'])) {
                    $updateArr['warning_days'] = null;
                } else {
                    $wd = intval($columns['warningDays']);
                    if ($wd < 0) { throw new Exception('warningDays must be >= 0'); }
                    if ($wd > 3650) { $wd = 3650; }
                    $updateArr['warning_days'] = $wd;
                }
            }

            // Optional warningDate (takes precedence when set)
            if (array_key_exists('warningDate', $columns)) {
                if ($columns['warningDate'] === '' || is_null($columns['warningDate'])) {
                    $updateArr['warning_date'] = null;
                } else {
                    $wdate = $this->normalizeDate($columns['warningDate']);
                    if ($wdate === null) { throw new Exception('Invalid warningDate'); }
                    // Validate within start/end
                    $sd = new DateTime($start);
                    $ed = new DateTime($end);
                    $wd = new DateTime($wdate);
                    if ($wd < $sd || $wd > $ed) {
                        throw new Exception('warningDate must be within Start and End dates');
                    }
                    $updateArr['warning_date'] = $wdate;
                }
            }

            // Enforce site scoping on update
            // selectColumn converts DB columns (snake_case) to camelCase keys internally,
            // so the output key must be 'siteId' (not 'site_id').
            $siteId = DbMysql::selectColumn(self::$tableName, array(self::$idName=>$licenseId), 'siteId', true);
            parent::checkEmptyInteger($siteId, 'site_id');
            if (!$this->isAdministrator() && $this->userSite && intval($siteId) !== intval($this->userSite)) {
                throw new Exception('Access denied to update license from different site');
            }
            DbMysql::update(self::$tableName, $updateArr, array(self::$idName => $licenseId));
            $this->set($licenseId);
            $this->licenseTitle = $updateArr['license_title'];
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
            DbMysql::update(self::$tableName, array('license_status' => 2, 'license_updated_by'=>$this->userId), array(self::$idName => $licenseId));
            $this->set($licenseId);
            // Same mapping note applies here: 'license_title' becomes 'licenseTitle'.
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

    /**
     * Normalize a date string to YYYY-MM-DD or return null if invalid/empty
     */
    private function normalizeDate($val): ?string
    {
        if (!is_string($val)) { return null; }
        $val = trim($val);
        if ($val === '' || $val === '0000-00-00') { return null; }
        
        // Accept formats: YYYY-MM-DD or DD/MM/YYYY
        $dt = DateTime::createFromFormat('Y-m-d', $val);
        if ($dt instanceof DateTime) { return $dt->format('Y-m-d'); }
        
        $dt = DateTime::createFromFormat('d/m/Y', $val);
        if ($dt instanceof DateTime) { return $dt->format('Y-m-d'); }
        
        // Try parsing common English formats like "1 September, 2025"
        $dt = DateTime::createFromFormat('j F, Y', $val);
        if ($dt instanceof DateTime) { return $dt->format('Y-m-d'); }
        
        $dt = DateTime::createFromFormat('j F Y', $val);
        if ($dt instanceof DateTime) { return $dt->format('Y-m-d'); }
        
        // Try generic parsing as last resort
        try {
            $dt = new DateTime($val);
            return $dt->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }
}
