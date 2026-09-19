<?php

/**
 * Shared plumbing for the Energy & Utility Monitoring module: role capabilities,
 * site scoping and the query helpers used by the reading and BEI classes.
 */
class EnergyBase extends General {

    public const ROLE_UTILITY = 18;
    public const ROLE_SITE_ADMIN = 19;
    public const ROLE_KPI_ADMIN = 30;
    public const ROLE_PI_ENTRY = 31;
    public const ROLE_KPI_VIEWER = 32;

    public const AUDIT_METER = 256;
    public const AUDIT_READING = 257;
    public const AUDIT_READING_DELETE = 258;
    public const AUDIT_CONFIG = 259;
    public const AUDIT_BEI = 260;
    public const AUDIT_BEI_FINAL = 261;

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

    public function canRecord(): bool {
        return $this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_UTILITY, self::ROLE_KPI_ADMIN));
    }

    public function canSetup(): bool {
        return $this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_SITE_ADMIN, self::ROLE_KPI_ADMIN));
    }

    public function canView(): bool {
        return $this->canRecord() || $this->canSetup()
            || $this->hasAnyRole(array(self::ROLE_PI_ENTRY, self::ROLE_KPI_VIEWER));
    }

    public function requireRecord(): void {
        if (!$this->canRecord()) {
            throw new Exception('You are not allowed to record meter readings.', 31);
        }
    }

    public function requireSetup(): void {
        if (!$this->canSetup()) {
            throw new Exception('You are not allowed to change the energy configuration.', 31);
        }
    }

    public function requireView(): void {
        if (!$this->canView()) {
            throw new Exception('You are not allowed to view energy data.', 31);
        }
    }

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
            throw new Exception('The site and its energy records are unavailable.', 31);
        }
        return $own;
    }

    public function assertSiteAccess(int $siteId): void {
        if ($this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_KPI_ADMIN))) {
            return;
        }
        if (intval($this->userSite) !== $siteId) {
            throw new Exception('The site and its energy records are unavailable.', 31);
        }
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

    public function getSiteRow(int $siteId): array {
        return DbMysql::selectSql(
            "SELECT s.site_id AS siteId, s.site_name AS siteName, s.site_code AS siteCode, s.site_status AS siteStatus
             FROM cli_site s",
            array('s.site_id' => $siteId),
            true
        );
    }

    /**
     * Active incoming meters for a site, in display order.
     */
    public function listMeters(int $siteId, bool $activeOnly = true): array {
        $sql = "SELECT meter_id, site_id, meter_name, meter_desc, sort_order, meter_status
                FROM enr_meter WHERE site_id = :siteId";
        if ($activeOnly) {
            $sql .= " AND meter_status = 1";
        }
        $sql .= " ORDER BY sort_order, meter_id";
        return $this->queryAll($sql, array('siteId' => $siteId));
    }

    public function monthName(int $month): string {
        $names = array(1 => 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December');
        return $names[$month] ?? strval($month);
    }

    public function checkPeriod(int $year, int $month): void {
        if ($year < 2000 || $year > 2999) {
            throw new Exception('Select a valid year.', 31);
        }
        if ($month < 1 || $month > 12) {
            throw new Exception('Select a valid month.', 31);
        }
    }

    // -----------------------------------------------------------------------
    // Query helpers
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
}
