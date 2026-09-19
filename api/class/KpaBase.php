<?php

/**
 * Shared plumbing for the KPI & APD module: role capabilities, site scoping and
 * the small query helpers used by the structure, evaluation and report classes.
 *
 * The KPI template lives under site_id = 0. A site may override the template by
 * creating its own groups; resolveTemplateSite() decides which one applies.
 */
class KpaBase extends General {

    public const ROLE_KPI_ADMIN = 30;
    public const ROLE_PI_ENTRY = 31;
    public const ROLE_KPI_VIEWER = 32;

    public const TEMPLATE_SITE = 0;

    public const AUDIT_STRUCTURE = 249;
    public const AUDIT_EVAL_CREATE = 250;
    public const AUDIT_EVAL_UPDATE = 251;
    public const AUDIT_PARAMS = 252;
    public const AUDIT_SUBMIT = 253;
    public const AUDIT_REOPEN = 254;
    public const AUDIT_ASSIGN = 255;

    public const PASS_RULES = array('GTE_TARGET', 'LTE_TARGET', 'EQ_TARGET');
    public const CALC_TYPES = array('EXPRESSION', 'BACKLOG_AVG', 'BEI', 'AVG_PARAMS', 'DIRECT');
    public const SOURCE_TYPES = array('MANUAL', 'GEMS');
    public const DATA_TYPES = array('NUMBER', 'INT', 'PERCENT');

    private $roleCache = null;
    private $assignedCache = null;

    public function adopt(General $auth): void {
        $this->userId = $auth->userId;
        $this->userSite = $auth->userSite;
        $this->isLogged = $auth->isLogged;
    }

    // -----------------------------------------------------------------------
    // Capabilities
    // -----------------------------------------------------------------------

    public function getRoleIds(): array {
        if ($this->roleCache !== null) {
            return $this->roleCache;
        }
        $this->roleCache = array();
        if (!$this->userId) {
            return $this->roleCache;
        }
        foreach (DbMysql::selectAll('sys_user_role', array('userId' => $this->userId)) as $row) {
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

    public function canAdmin(): bool {
        return $this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_KPI_ADMIN));
    }

    public function canView(): bool {
        return $this->canAdmin() || $this->hasAnyRole(array(self::ROLE_PI_ENTRY, self::ROLE_KPI_VIEWER));
    }

    /**
     * PI Entry may only touch the indicators assigned to them. KPI Admin and
     * Administrator may touch any indicator.
     */
    public function canEntry(?int $piId = null, ?int $siteId = null): bool {
        if ($this->canAdmin()) {
            return true;
        }
        if (!$this->hasAnyRole(array(self::ROLE_PI_ENTRY))) {
            return false;
        }
        if ($piId === null) {
            return true;
        }
        return in_array(intval($piId), $this->assignedPiIds($siteId), true);
    }

    public function assignedPiIds(?int $siteId = null): array {
        $key = strval($siteId ?? 'all');
        if (is_array($this->assignedCache) && array_key_exists($key, $this->assignedCache)) {
            return $this->assignedCache[$key];
        }
        if (!is_array($this->assignedCache)) {
            $this->assignedCache = array();
        }
        $sql = "SELECT pi_id FROM kpa_pi_assignment WHERE user_id = :userId AND assign_status = 1";
        $params = array('userId' => $this->userId);
        if ($siteId !== null) {
            $sql .= " AND site_id = :siteId";
            $params['siteId'] = $siteId;
        }
        $ids = array();
        foreach ($this->queryAll($sql, $params) as $row) {
            $ids[] = intval($row['piId']);
        }
        $this->assignedCache[$key] = $ids;
        return $ids;
    }

    public function requireAdmin(): void {
        if (!$this->canAdmin()) {
            throw new Exception('You are not allowed to change the KPI structure.', 31);
        }
    }

    public function requireView(): void {
        if (!$this->canView()) {
            throw new Exception('You are not allowed to view KPI results.', 31);
        }
    }

    public function requireEntry(?int $piId = null, ?int $siteId = null): void {
        if (!$this->canEntry($piId, $siteId)) {
            throw new Exception('This Performance Indicator is not assigned to you.', 31);
        }
    }

    // -----------------------------------------------------------------------
    // Site scoping
    // -----------------------------------------------------------------------

    public function resolveSiteId($siteId = null): int {
        $requested = intval($siteId);
        if ($this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_KPI_ADMIN))) {
            if ($requested > 0) {
                return $requested;
            }
            return intval($this->userSite);
        }
        $own = intval($this->userSite);
        if ($own <= 0) {
            throw new Exception('Your account is not assigned to a site.', 31);
        }
        if ($requested > 0 && $requested !== $own) {
            throw new Exception('The site and its KPI records are unavailable.', 31);
        }
        return $own;
    }

    public function assertSiteAccess(int $siteId): void {
        if ($this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_KPI_ADMIN))) {
            return;
        }
        if (intval($this->userSite) !== $siteId) {
            throw new Exception('The site and its KPI records are unavailable.', 31);
        }
    }

    /**
     * A site uses its own structure when it has at least one active group,
     * otherwise it falls back to the shared template.
     */
    public function resolveTemplateSite(int $siteId): int {
        if ($siteId === self::TEMPLATE_SITE) {
            return self::TEMPLATE_SITE;
        }
        $row = $this->queryOne(
            "SELECT COUNT(*) AS cnt FROM kpa_group WHERE site_id = :siteId AND group_status = 1",
            array('siteId' => $siteId)
        );
        return intval($row['cnt'] ?? 0) > 0 ? $siteId : self::TEMPLATE_SITE;
    }

    public function maxApdPct(int $siteId): float {
        $config = DbMysql::select('kpa_config', array('siteId' => $siteId));
        if (empty($config)) {
            return 5.00;
        }
        return round(floatval($config['maxApdPct']), 2);
    }

    public function getSiteRow(int $siteId): array {
        return DbMysql::selectSql(
            "SELECT s.site_id AS siteId, s.site_name AS siteName, s.site_code AS siteCode, s.site_status AS siteStatus
             FROM cli_site s",
            array('s.site_id' => $siteId),
            true
        );
    }

    public function listSites(): array {
        $sql = "SELECT s.site_id, s.site_name, s.site_code, s.site_status FROM cli_site s WHERE s.site_status = 1";
        $params = array();
        if (!$this->isAdministrator() && !$this->hasAnyRole(array(self::ROLE_KPI_ADMIN))) {
            $sql .= " AND s.site_id = :siteId";
            $params['siteId'] = $this->resolveSiteId();
        }
        $sql .= " ORDER BY s.site_name";
        return $this->queryAll($sql, $params);
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
        return $name !== '' ? $name : strval($user['userName'] ?? '');
    }

    public function monthName(int $month): string {
        $names = array(1 => 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December');
        return $names[$month] ?? strval($month);
    }

    public function writeHistory(string $entityType, int $entityId, string $action, $oldValues, $newValues, ?string $reason = null, ?int $siteId = null): void {
        DbMysql::insert('kpa_history', array(
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'site_id' => $siteId,
            'action' => $action,
            'old_values' => $oldValues === null ? null : json_encode($oldValues),
            'new_values' => $newValues === null ? null : json_encode($newValues),
            'reason' => $reason,
            'user_id' => $this->userId
        ));
    }

    // -----------------------------------------------------------------------
    // Query helpers (mirror WasteBase so both modules read the same way)
    // -----------------------------------------------------------------------

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

    public function normalizeDate($val): ?string {
        if (!is_string($val) && !is_numeric($val)) {
            return null;
        }
        $val = trim(strval($val));
        if ($val === '' || $val === '0000-00-00') {
            return null;
        }
        foreach (array('Y-m-d', 'd/m/Y', 'd-m-Y') as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $val);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d');
            }
        }
        try {
            return (new DateTime($val))->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * DbMysql treats a value starting with "|" as raw SQL, so free text must
     * never be written with a leading pipe.
     */
    public function sanitizeText($value, int $maxLength = 0): string {
        $text = trim(strval($value ?? ''));
        $text = ltrim($text, '|');
        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }
        return $text;
    }

    public function checkOption(string $value, array $allowed, string $label): string {
        $value = strtoupper(trim($value));
        if (!in_array($value, $allowed, true)) {
            throw new Exception('Select a valid ' . $label . '. Allowed: ' . implode(', ', $allowed) . '.', 31);
        }
        return $value;
    }
}
