<?php

class WasteBase extends General {

    public const ROLE_WASTE_USER = 28;
    public const ROLE_WASTE_OFFICER = 29;
    public const ROLE_SITE_ADMIN = 19;

    public const DOC_SUPPORTING = 42;
    public const DOC_REPORT = 43;
    public const DOC_SUBMISSION = 44;

    public const AUDIT_CREATE = 236;
    public const AUDIT_UPDATE = 237;
    public const AUDIT_FINALISE = 238;
    public const AUDIT_AMEND = 239;
    public const AUDIT_CANCEL = 240;
    public const AUDIT_OPENING = 241;
    public const AUDIT_REPORT = 242;
    public const AUDIT_SUBMIT = 243;
    public const AUDIT_SETUP = 244;

    private $roleCache = null;

    public function adopt(General $auth): void {
        $this->userId = $auth->userId;
        $this->userSite = $auth->userSite;
        $this->isLogged = $auth->isLogged;
    }

    public function getRoleIds(): array {
        if ($this->roleCache !== null) {
            return $this->roleCache;
        }
        $this->roleCache = array();
        if (!$this->userId) {
            return $this->roleCache;
        }
        $rows = DbMysql::selectAll('sys_user_role', array('userId' => $this->userId));
        foreach ($rows as $row) {
            if (isset($row['roleId'])) {
                $this->roleCache[] = intval($row['roleId']);
            }
        }
        return $this->roleCache;
    }

    public function hasAnyRole(array $roleIds): bool {
        $have = $this->getRoleIds();
        foreach ($roleIds as $roleId) {
            if (in_array(intval($roleId), $have, true)) {
                return true;
            }
        }
        return false;
    }

    public function canRecord(): bool {
        return $this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_WASTE_USER, self::ROLE_WASTE_OFFICER));
    }

    public function canAmendFinal(): bool {
        return $this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_WASTE_OFFICER));
    }

    public function canOpening(): bool {
        return $this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_WASTE_OFFICER));
    }

    public function canReport(): bool {
        return $this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_WASTE_OFFICER));
    }

    public function canSetup(): bool {
        return $this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_SITE_ADMIN));
    }

    public function requireRecord(): void {
        if (!$this->canRecord()) {
            throw new Exception('You are not allowed to create or change waste records.', 31);
        }
    }

    public function requireAmend(): void {
        if (!$this->canAmendFinal()) {
            throw new Exception('You are not allowed to amend a Final waste record.', 31);
        }
    }

    public function requireOpening(): void {
        if (!$this->canOpening()) {
            throw new Exception('You are not allowed to maintain opening balances.', 31);
        }
    }

    public function requireReport(): void {
        if (!$this->canReport()) {
            throw new Exception('You are not allowed to generate JKR waste reports.', 31);
        }
    }

    public function requireSetup(): void {
        if (!$this->canSetup()) {
            throw new Exception('You are not allowed to maintain waste setup data.', 31);
        }
    }

    public function resolveSiteId($siteId = null): int {
        $requested = intval($siteId);
        if ($this->isAdministrator()) {
            if ($requested > 0) {
                return $requested;
            }
            return intval($this->userSite);
        }
        $own = intval($this->userSite);
        if ($own <= 0) {
            throw new Exception('Your account is not assigned to a premise.', 31);
        }
        if ($requested > 0 && $requested !== $own) {
            throw new Exception('The premise and its records are unavailable.', 31);
        }
        return $own;
    }

    public function assertSiteAccess(int $siteId): void {
        if (!$this->canAccessSite($siteId)) {
            throw new Exception('The premise and its records are unavailable.', 31);
        }
    }

    public function toKg($qty, string $unit): float {
        $value = floatval($qty);
        $u = strtoupper(trim($unit));
        if ($u === 'MT') {
            return round($value * 1000, 3);
        }
        return round($value, 3);
    }

    public function formatMt(float $kg): float {
        return round($kg / 1000, 6);
    }

    public function userDisplayName(int $userId): string {
        if ($userId <= 0) {
            return '';
        }
        $user = DbMysql::select('sys_user', array('userId' => $userId));
        if (empty($user)) {
            return '';
        }
        $name = trim(($user['userFirstName'] ?? '') . ' ' . ($user['userLastName'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        return strval($user['userName'] ?? '');
    }

    public function getSiteRow(int $siteId): array {
        $sql = "SELECT s.site_id AS siteId, s.site_name AS siteName, s.site_code AS siteCode,
                       s.site_desc AS siteDesc, s.site_status AS siteStatus
                FROM cli_site s";
        $row = DbMysql::selectSql($sql, array('s.site_id' => $siteId), true);
        return $row;
    }

    public function getSwCode(int $swCodeId): array {
        return DbMysql::select('ref_sw_code', array('swCodeId' => $swCodeId), true);
    }

    public function writeHistory(string $entityType, int $entityId, string $action, $oldValues, $newValues, ?string $reason = null, ?int $siteId = null, ?string $eventDate = null): void {
        DbMysql::insert('wst_history', array(
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'site_id' => $siteId,
            'event_date' => $eventDate,
            'action' => $action,
            'old_values' => $oldValues === null ? null : json_encode($oldValues),
            'new_values' => $newValues === null ? null : json_encode($newValues),
            'reason' => $reason,
            'user_id' => $this->userId
        ));
    }

    public function queryAll(string $sql, array $params = array()): array {
        $stmt = DbMysql::$DBH->prepare($sql);
        $stmt->execute($this->namedParamsForSql($sql, $params));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
        return $this->camelizeRows($rows);
    }

    public function queryOne(string $sql, array $params = array()): array {
        $stmt = DbMysql::$DBH->prepare($sql);
        $stmt->execute($this->namedParamsForSql($sql, $params));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;
        return $row ? $this->camelizeRow($row) : array();
    }

    // Native PDO (EMULATE_PREPARES=false) rejects binds that are not in the SQL.
    private function namedParamsForSql(string $sql, array $params): array {
        $bind = array();
        foreach ($params as $key => $value) {
            $name = ltrim((string) $key, ':');
            if ($name === '') {
                continue;
            }
            if (preg_match('/:' . preg_quote($name, '/') . '\b/', $sql)) {
                $bind[$name] = $value;
            }
        }
        return $bind;
    }

    public function camelizeRows(array $rows): array {
        $out = array();
        foreach ($rows as $row) {
            $out[] = $this->camelizeRow($row);
        }
        return $out;
    }

    public function camelizeRow(array $row): array {
        $out = array();
        foreach ($row as $key => $value) {
            if (strpos($key, '_') !== false) {
                $parts = explode('_', strtolower($key));
                $camel = $parts[0];
                for ($i = 1; $i < count($parts); $i++) {
                    $camel .= ucfirst($parts[$i]);
                }
                $out[$camel] = $value;
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    public function inPlaceholders(array $ids, string $prefix): array {
        $params = array();
        $keys = array();
        foreach (array_values($ids) as $i => $id) {
            $key = $prefix . $i;
            $keys[] = ':' . $key;
            $params[$key] = intval($id);
        }
        return array($keys, $params);
    }

    public function parseIdList($value): array {
        if (is_array($value)) {
            $raw = $value;
        } else if ($value === null || $value === '' || $value === 'all') {
            return array();
        } else {
            $raw = explode(',', strval($value));
        }
        $ids = array();
        foreach ($raw as $item) {
            $id = intval($item);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    public function normalizeDate($val): ?string {
        if (!is_string($val) && !is_numeric($val)) {
            return null;
        }
        $val = trim(strval($val));
        if ($val === '' || $val === '0000-00-00') {
            return null;
        }
        foreach (array('Y-m-d', 'd/m/Y', 'd-m-Y', 'j F Y', 'j F, Y') as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $val);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d');
            }
        }
        try {
            $dt = new DateTime($val);
            return $dt->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }

    public function saveUpload(array $fileUpload, int $documentId, string $folder, string $prefix): int {
        $fileUpload['width'] = $fileUpload['width'] ?? null;
        $fileUpload['height'] = $fileUpload['height'] ?? null;
        $temp = parent::uploadPrepare($fileUpload, $documentId);
        $filename = (new DateTime())->format('YmdHis') . '_' . $prefix . '_' . $this->userId;
        return parent::uploadSave($temp, $folder, $filename);
    }

    public function affectedReports(int $siteId, string $eventDate): array {
        $sql = "SELECT report_id AS reportId, period_start AS periodStart, period_end AS periodEnd,
                       version_no AS versionNo, generated_at AS generatedAt
                FROM wst_report
                WHERE site_id = :siteId AND period_start <= :eventFrom AND period_end >= :eventTo
                ORDER BY version_no DESC";
        return $this->queryAll($sql, array('siteId' => $siteId, 'eventFrom' => $eventDate, 'eventTo' => $eventDate));
    }
}
