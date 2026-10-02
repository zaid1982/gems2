<?php

class NotiHelper {

    /**
     * @param int $notiTextId
     * @return string
     */
    public static function resolveModule (int $notiTextId): string {
        if ($notiTextId >= 1 && $notiTextId <= 4) {
            return 'ppm';
        }
        if ($notiTextId >= 5 && $notiTextId <= 16) {
            return 'wo';
        }
        if ($notiTextId >= 17 && $notiTextId <= 21) {
            return 'mr';
        }
        if ($notiTextId >= 22 && $notiTextId <= 25) {
            return 'fca';
        }
        return 'general';
    }

    /**
     * @return string gemsPlus|gems20
     */
    public static function resolveNetworkSource (): string {
        $config = @parse_ini_file(__DIR__ . '/../library/config.ini', true);
        $source = '';
        if (!empty($config['app']['network_source'])) {
            $source = trim((string)$config['app']['network_source']);
        }
        if ($source === 'gems20' || $source === 'gemsPlus') {
            return $source;
        }
        return 'gemsPlus';
    }

    /**
     * @param int $notiTextId
     * @param array $notiParam
     * @return string JSON
     */
    public static function buildNotiData (int $notiTextId, array $notiParam): string {
        $module = self::resolveModule($notiTextId);
        $data = array(
            'noti_text_id' => (string)$notiTextId,
            'module' => $module,
            'network_source' => self::resolveNetworkSource(),
        );
        if (!empty($notiParam['task_no'])) {
            $data['task_no'] = (string)$notiParam['task_no'];
        }
        if (!empty($notiParam['wo_no'])) {
            $data['wo_no'] = (string)$notiParam['wo_no'];
        }

        foreach (self::recordFields($module) as $field) {
            if (!empty($notiParam[$field])) {
                $data[$field] = (string)$notiParam[$field];
            }
        }

        // Callers only pass the document number. Look up the row so a tap can
        // open that task. A miss leaves the number only, and the app opens search.
        if (!empty($data['task_no']) && strpos($data['task_no'], ',') === false && self::recordIncomplete($module, $data)) {
            try {
                $found = self::lookupRecord($module, $data['task_no']);
                foreach ($found as $field => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    if (empty($data[$field])) {
                        $data[$field] = (string)$value;
                    }
                }
            } catch (Throwable $ex) {
                error_log('NotiHelper lookup failed: '.$ex->getMessage());
            }
        }

        return json_encode($data);
    }

    /**
     * @param string $module
     * @return string[]
     */
    private static function recordFields (string $module): array {
        if ($module === 'ppm') {
            return array('ppm_task_id', 'site_name', 'task_status');
        }
        if ($module === 'wo') {
            return array('wo_task_id', 'site_name', 'task_status', 'wo_task_type', 'wo_task_type_init');
        }
        if ($module === 'mr') {
            return array('wo_task_request_id', 'wo_task_id');
        }
        return array();
    }

    /**
     * @param string $module
     * @param array $data
     * @return bool
     */
    private static function recordIncomplete (string $module, array $data): bool {
        if ($module === 'ppm') {
            return empty($data['ppm_task_id']) || empty($data['site_name']) || empty($data['task_status']);
        }
        if ($module === 'wo') {
            return empty($data['wo_task_id']) || empty($data['site_name']) || empty($data['task_status']);
        }
        if ($module === 'mr') {
            return empty($data['wo_task_request_id']) || empty($data['wo_task_id']);
        }
        return false;
    }

    /**
     * @param string $module
     * @param string $taskNo
     * @return array
     * @throws Exception
     */
    private static function lookupRecord (string $module, string $taskNo): array {
        if ($module === 'ppm') {
            $sql = "SELECT
                    ppm_task.ppm_task_id,
                    cli_site.site_name,
                    ref_status.status_desc AS task_status
                FROM ppm_task
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ast_asset.contract_id
                LEFT JOIN cli_site ON cli_site.site_id = cli_contract.site_id
                LEFT JOIN ref_status ON ref_status.status_id = ppm_task.ppm_task_status
                WHERE ppm_task.ppm_task_no = :task_no
                LIMIT 1";
        } else if ($module === 'wo') {
            $sql = "SELECT
                    wo_task.wo_task_id,
                    cli_site.site_name,
                    ref_status.status_desc AS task_status,
                    CASE wo_task.wo_task_type
                        WHEN 1 THEN 'Client Complaint'
                        WHEN 2 THEN 'Self Finding'
                        WHEN 3 THEN 'Request'
                        WHEN 4 THEN 'Breakdown'
                        WHEN 5 THEN 'Defect'
                        WHEN 6 THEN 'Public Complaint'
                        ELSE ''
                    END AS wo_task_type,
                    CASE wo_task.wo_task_type_init
                        WHEN 1 THEN 'Client Complaint'
                        WHEN 2 THEN 'Self Finding'
                        WHEN 3 THEN 'Request'
                        WHEN 4 THEN 'Breakdown'
                        WHEN 5 THEN 'Defect'
                        WHEN 6 THEN 'Public Complaint'
                        ELSE ''
                    END AS wo_task_type_init
                FROM wo_task
                LEFT JOIN cli_site ON cli_site.site_id = wo_task.site_id
                LEFT JOIN ref_status ON ref_status.status_id = wo_task.wo_task_status
                WHERE wo_task.wo_task_no = :task_no
                LIMIT 1";
        } else if ($module === 'mr') {
            $sql = "SELECT
                    wo_task_request.wo_task_request_id,
                    wo_task_request.wo_task_id
                FROM wo_task_request
                WHERE wo_task_request.wo_task_request_no = :task_no
                LIMIT 1";
        } else {
            return array();
        }

        return self::fetchAssoc($sql, array(':task_no' => $taskNo));
    }

    /**
     * Uses whichever database connection the request already opened.
     *
     * @param string $sql
     * @param array $params
     * @return array
     * @throws Exception
     */
    private static function fetchAssoc (string $sql, array $params): array {
        if (class_exists('Class_db', false)) {
            $row = Class_db::getInstance()->db_fetch_one_prepared($sql, $params);
            if ($row !== null) {
                return $row;
            }
        }
        if (class_exists('DbMysql', false) && !empty(DbMysql::$DBH)) {
            $stmt = DbMysql::$DBH->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : array();
        }
        return array();
    }

    /**
     * @param string $notiTextTitle
     * @param string $notiTextHtml
     * @param array $notiParameters
     * @param array $notiParam
     * @return array{title:string,html:string}
     * @throws Exception
     */
    public static function applyTemplateParams (string $notiTextTitle, string $notiTextHtml, array $notiParameters, array $notiParam): array {
        foreach ($notiParameters as $parameter) {
            $paramCode = isset($parameter['noti_param_code']) ? $parameter['noti_param_code'] : $parameter['notiParamCode'];
            if (!array_key_exists($paramCode, $notiParam)) {
                throw new Exception('[' . __LINE__ . '] - Index '.$paramCode.' in array notiParam empty');
            }
            if (strpos($notiTextTitle, '['.$paramCode.']') !== false) {
                $notiTextTitle = str_replace('['.$paramCode.']', $notiParam[$paramCode], $notiTextTitle);
            }
            if (strpos($notiTextHtml, '['.$paramCode.']') !== false) {
                $notiTextHtml = str_replace('['.$paramCode.']', $notiParam[$paramCode], $notiTextHtml);
            }
        }
        return array('title' => $notiTextTitle, 'html' => $notiTextHtml);
    }

    /**
     * @param mixed $userId
     * @return int[]
     */
    public static function normalizeUserIds ($userId): array {
        if (is_array($userId)) {
            return array_values(array_filter(array_map('intval', $userId)));
        }
        if (!empty($userId)) {
            return array(intval($userId));
        }
        return array();
    }
}
