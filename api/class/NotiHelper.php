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
        $data = array(
            'noti_text_id' => (string)$notiTextId,
            'module' => self::resolveModule($notiTextId),
            'network_source' => self::resolveNetworkSource(),
        );
        if (!empty($notiParam['task_no'])) {
            $data['task_no'] = (string)$notiParam['task_no'];
        }
        if (!empty($notiParam['wo_no'])) {
            $data['wo_no'] = (string)$notiParam['wo_no'];
        }
        return json_encode($data);
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
