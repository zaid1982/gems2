<?php

/**
 * KPI definition: site configuration, KPI groups, Performance Indicators,
 * PI parameters and PI-to-user assignment. KPI Admin only for every write.
 */
class KpaStructure extends KpaBase {

    // -----------------------------------------------------------------------
    // Configuration
    // -----------------------------------------------------------------------

    public function getConfig(?int $siteId = null): array {
        $siteId = $this->resolveSiteId($siteId);
        $this->assertSiteAccess($siteId);
        $site = $this->getSiteRow($siteId);
        return array(
            'siteId' => $siteId,
            'siteName' => $site['siteName'] ?? '',
            'siteCode' => $site['siteCode'] ?? '',
            'maxApdPct' => $this->maxApdPct($siteId),
            'templateSiteId' => $this->resolveTemplateSite($siteId),
            'usesTemplate' => $this->resolveTemplateSite($siteId) === self::TEMPLATE_SITE
        );
    }

    public function saveConfig(array $columns): array {
        $this->requireAdmin();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $pct = round(floatval($columns['maxApdPct'] ?? 5), 2);
        if ($pct < 0 || $pct > 100) {
            throw new Exception('The maximum APD percentage must be between 0 and 100.', 31);
        }
        $existing = DbMysql::select('kpa_config', array('siteId' => $siteId));
        if (empty($existing)) {
            DbMysql::insert('kpa_config', array(
                'site_id' => $siteId,
                'max_apd_pct' => $pct,
                'config_status' => 1,
                'config_created_by' => $this->userId
            ));
        } else {
            DbMysql::update('kpa_config', array(
                'max_apd_pct' => $pct,
                'config_updated_by' => $this->userId
            ), array('site_id' => $siteId));
        }
        $this->writeHistory('CONFIG', $siteId, 'SAVE', $existing, array('maxApdPct' => $pct), null, $siteId);
        $this->saveAudit(self::AUDIT_STRUCTURE, 'Updated KPI configuration for site ' . $siteId);
        return $this->getConfig($siteId);
    }

    // -----------------------------------------------------------------------
    // Groups
    // -----------------------------------------------------------------------

    public function listGroups(?int $siteId = null, bool $activeOnly = false): array {
        $templateSite = $siteId === null ? self::TEMPLATE_SITE : $this->resolveTemplateSite($this->resolveSiteId($siteId));
        $sql = "SELECT g.group_id, g.site_id, g.group_no, g.group_name, g.sort_order, g.group_status,
                       (SELECT COUNT(*) FROM kpa_pi p WHERE p.group_id = g.group_id AND p.pi_status = 1) AS pi_count,
                       (SELECT COALESCE(SUM(p.weightage_pct), 0) FROM kpa_pi p WHERE p.group_id = g.group_id AND p.pi_status = 1) AS weightage_total
                FROM kpa_group g
                WHERE g.site_id = :siteId";
        $params = array('siteId' => $templateSite);
        if ($activeOnly) {
            $sql .= " AND g.group_status = 1";
        }
        $sql .= " ORDER BY g.sort_order, g.group_no";
        return $this->queryAll($sql, $params);
    }

    public function saveGroup(array $columns, ?int $groupId = null): array {
        $this->requireAdmin();
        $siteId = isset($columns['siteId']) && $columns['siteId'] !== '' && intval($columns['siteId']) !== self::TEMPLATE_SITE
            ? $this->resolveSiteId($columns['siteId'])
            : self::TEMPLATE_SITE;
        $groupNo = strtoupper(trim(strval($columns['groupNo'] ?? '')));
        $groupName = $this->sanitizeText($columns['groupName'] ?? '', 200);
        if ($groupNo === '' || $groupName === '') {
            throw new Exception('Enter the KPI group number and name.', 31);
        }
        $data = array(
            'site_id' => $siteId,
            'group_no' => $groupNo,
            'group_name' => $groupName,
            'sort_order' => intval($columns['sortOrder'] ?? 1) ?: 1,
            'group_status' => isset($columns['groupStatus']) ? intval($columns['groupStatus']) : 1
        );
        if ($groupId) {
            $existing = DbMysql::select('kpa_group', array('groupId' => $groupId), true);
            unset($data['site_id']);
            $data['group_updated_by'] = $this->userId;
            DbMysql::update('kpa_group', $data, array('group_id' => $groupId));
            $this->writeHistory('GROUP', $groupId, 'UPDATE', $existing, $data, null, $siteId);
        } else {
            $dup = $this->queryOne(
                "SELECT group_id FROM kpa_group WHERE site_id = :siteId AND group_no = :groupNo",
                array('siteId' => $siteId, 'groupNo' => $groupNo)
            );
            if (!empty($dup)) {
                throw new Exception('KPI group ' . $groupNo . ' already exists.', 31);
            }
            $data['group_created_by'] = $this->userId;
            $groupId = DbMysql::insert('kpa_group', $data);
            $this->writeHistory('GROUP', $groupId, 'CREATE', null, $data, null, $siteId);
        }
        $this->saveAudit(self::AUDIT_STRUCTURE, 'Updated KPI group ' . $groupNo);
        return DbMysql::select('kpa_group', array('groupId' => $groupId), true);
    }

    public function deactivateGroup(int $groupId): void {
        $this->requireAdmin();
        $existing = DbMysql::select('kpa_group', array('groupId' => $groupId), true);
        DbMysql::update('kpa_group', array(
            'group_status' => 2,
            'group_updated_by' => $this->userId
        ), array('group_id' => $groupId));
        DbMysql::update('kpa_pi', array('pi_status' => 2, 'pi_updated_by' => $this->userId), array('group_id' => $groupId));
        $this->writeHistory('GROUP', $groupId, 'DEACTIVATE', $existing, array('groupStatus' => 2), null, intval($existing['siteId']));
        $this->saveAudit(self::AUDIT_STRUCTURE, 'Deactivated KPI group ' . $existing['groupNo']);
    }

    // -----------------------------------------------------------------------
    // Performance Indicators
    // -----------------------------------------------------------------------

    public function listPi(?int $siteId = null, bool $activeOnly = false): array {
        $templateSite = $siteId === null ? self::TEMPLATE_SITE : $this->resolveTemplateSite($this->resolveSiteId($siteId));
        $sql = "SELECT p.*, g.group_no, g.group_name, g.site_id, g.sort_order AS group_sort,
                       (SELECT COUNT(*) FROM kpa_pi_param x WHERE x.pi_id = p.pi_id AND x.param_status = 1) AS param_count
                FROM kpa_pi p
                INNER JOIN kpa_group g ON g.group_id = p.group_id
                WHERE g.site_id = :siteId";
        $params = array('siteId' => $templateSite);
        if ($activeOnly) {
            $sql .= " AND p.pi_status = 1 AND g.group_status = 1";
        }
        $sql .= " ORDER BY g.sort_order, g.group_no, p.sort_order, p.pi_no";
        $rows = $this->queryAll($sql, $params);
        foreach ($rows as &$row) {
            $row['targetValue'] = floatval($row['targetValue']);
            $row['weightagePct'] = floatval($row['weightagePct']);
            $row['demeritPoint'] = intval($row['demeritPoint']);
            $row['paramCount'] = intval($row['paramCount']);
        }
        return $rows;
    }

    public function getPi(int $piId): array {
        $row = $this->queryOne(
            "SELECT p.*, g.group_no, g.group_name, g.site_id
             FROM kpa_pi p
             INNER JOIN kpa_group g ON g.group_id = p.group_id
             WHERE p.pi_id = :id",
            array('id' => $piId)
        );
        if (empty($row)) {
            throw new Exception('Performance Indicator not found.', 31);
        }
        $row['targetValue'] = floatval($row['targetValue']);
        $row['weightagePct'] = floatval($row['weightagePct']);
        $row['demeritPoint'] = intval($row['demeritPoint']);
        $row['params'] = $this->listParams($piId);
        return $row;
    }

    public function savePi(array $columns, ?int $piId = null): array {
        $this->requireAdmin();
        $groupId = intval($columns['groupId'] ?? 0);
        if ($groupId <= 0) {
            throw new Exception('Select the KPI group for this indicator.', 31);
        }
        $group = DbMysql::select('kpa_group', array('groupId' => $groupId), true);
        $piNo = strtoupper(trim(strval($columns['piNo'] ?? '')));
        $piName = $this->sanitizeText($columns['piName'] ?? '', 300);
        if ($piNo === '' || $piName === '') {
            throw new Exception('Enter the PI number and name.', 31);
        }
        $calcType = $this->checkOption(strval($columns['calcType'] ?? 'EXPRESSION'), self::CALC_TYPES, 'calculation type');
        $passRule = $this->checkOption(strval($columns['passRule'] ?? 'GTE_TARGET'), self::PASS_RULES, 'pass rule');
        $sourceType = $this->checkOption(strval($columns['sourceType'] ?? 'MANUAL'), self::SOURCE_TYPES, 'source');
        $weightage = round(floatval($columns['weightagePct'] ?? 0), 2);
        if ($weightage < 0 || $weightage > 100) {
            throw new Exception('The weightage must be between 0 and 100.', 31);
        }
        $formula = trim(strval($columns['formulaExpr'] ?? ''));
        if ($calcType === 'EXPRESSION') {
            if ($formula === '') {
                throw new Exception('Enter the formula for an expression indicator.', 31);
            }
            // Reject a broken formula before it reaches a monthly evaluation.
            $calc = new KpaCalculator();
            $probe = array();
            if (preg_match_all('/p[0-9]+/i', $formula, $matches)) {
                foreach ($matches[0] as $key) {
                    $probe[strtolower($key)] = 1;
                }
            }
            $check = $calc->test($formula, $probe);
            if (!$check['ok']) {
                throw new Exception('The formula is not valid: ' . $check['message'], 31);
            }
        } else {
            $formula = '';
        }

        $data = array(
            'group_id' => $groupId,
            'pi_no' => $piNo,
            'pi_name' => $piName,
            'pi_description' => $this->sanitizeText($columns['piDescription'] ?? ''),
            'target_value' => round(floatval($columns['targetValue'] ?? 0), 4),
            'target_unit' => $this->sanitizeText($columns['targetUnit'] ?? '%', 10) ?: '%',
            'demerit_point' => intval($columns['demeritPoint'] ?? 1),
            'weightage_pct' => $weightage,
            'pass_rule' => $passRule,
            'calc_type' => $calcType,
            'formula_expr' => $formula,
            'source_type' => $sourceType,
            'sort_order' => intval($columns['sortOrder'] ?? 1) ?: 1,
            'pi_status' => isset($columns['piStatus']) ? intval($columns['piStatus']) : 1,
            'effective_from' => $this->normalizeDate($columns['effectiveFrom'] ?? '')
        );
        if ($piId) {
            $existing = DbMysql::select('kpa_pi', array('piId' => $piId), true);
            $data['pi_updated_by'] = $this->userId;
            DbMysql::update('kpa_pi', $data, array('pi_id' => $piId));
            $this->writeHistory('PI', $piId, 'UPDATE', $existing, $data, null, intval($group['siteId']));
        } else {
            $dup = $this->queryOne(
                "SELECT pi_id FROM kpa_pi WHERE group_id = :groupId AND pi_no = :piNo",
                array('groupId' => $groupId, 'piNo' => $piNo)
            );
            if (!empty($dup)) {
                throw new Exception('PI ' . $piNo . ' already exists in this KPI group.', 31);
            }
            $data['pi_created_by'] = $this->userId;
            $piId = DbMysql::insert('kpa_pi', $data);
            $this->writeHistory('PI', $piId, 'CREATE', null, $data, null, intval($group['siteId']));
        }
        $this->saveAudit(self::AUDIT_STRUCTURE, 'Updated PI ' . $piNo);
        return $this->getPi($piId);
    }

    public function deactivatePi(int $piId): void {
        $this->requireAdmin();
        $existing = DbMysql::select('kpa_pi', array('piId' => $piId), true);
        DbMysql::update('kpa_pi', array(
            'pi_status' => 2,
            'pi_updated_by' => $this->userId
        ), array('pi_id' => $piId));
        $this->writeHistory('PI', $piId, 'DEACTIVATE', $existing, array('piStatus' => 2), null, null);
        $this->saveAudit(self::AUDIT_STRUCTURE, 'Deactivated PI ' . $existing['piNo']);
    }

    // -----------------------------------------------------------------------
    // PI parameters
    // -----------------------------------------------------------------------

    public function listParams(int $piId): array {
        $rows = $this->queryAll(
            "SELECT param_id, pi_id, param_key, param_label, data_type, source_type, gems_hook,
                    is_required, sort_order, param_status
             FROM kpa_pi_param
             WHERE pi_id = :piId AND param_status = 1
             ORDER BY sort_order, param_key",
            array('piId' => $piId)
        );
        foreach ($rows as &$row) {
            $row['isRequired'] = intval($row['isRequired']) === 1;
        }
        return $rows;
    }

    public function saveParam(int $piId, array $columns, ?int $paramId = null): array {
        $this->requireAdmin();
        DbMysql::select('kpa_pi', array('piId' => $piId), true);
        $key = strtolower(trim(strval($columns['paramKey'] ?? '')));
        if (!preg_match('/^p[0-9]+$/', $key)) {
            throw new Exception('The parameter key must be p1, p2, p3 and so on.', 31);
        }
        $label = $this->sanitizeText($columns['paramLabel'] ?? '', 200);
        if ($label === '') {
            throw new Exception('Enter the parameter label.', 31);
        }
        $data = array(
            'pi_id' => $piId,
            'param_key' => $key,
            'param_label' => $label,
            'data_type' => $this->checkOption(strval($columns['dataType'] ?? 'NUMBER'), self::DATA_TYPES, 'data type'),
            'source_type' => $this->checkOption(strval($columns['sourceType'] ?? 'MANUAL'), self::SOURCE_TYPES, 'source'),
            'gems_hook' => $this->sanitizeText($columns['gemsHook'] ?? '', 60),
            'is_required' => !empty($columns['isRequired']) ? 1 : 0,
            'sort_order' => intval($columns['sortOrder'] ?? 1) ?: 1,
            'param_status' => 1
        );
        if ($paramId) {
            $existing = DbMysql::select('kpa_pi_param', array('paramId' => $paramId), true);
            unset($data['pi_id']);
            DbMysql::update('kpa_pi_param', $data, array('param_id' => $paramId));
            $this->writeHistory('PARAM', $paramId, 'UPDATE', $existing, $data, null, null);
        } else {
            $dup = $this->queryOne(
                "SELECT param_id FROM kpa_pi_param WHERE pi_id = :piId AND param_key = :key",
                array('piId' => $piId, 'key' => $key)
            );
            if (!empty($dup)) {
                throw new Exception('Parameter ' . $key . ' already exists for this indicator.', 31);
            }
            $paramId = DbMysql::insert('kpa_pi_param', $data);
            $this->writeHistory('PARAM', $paramId, 'CREATE', null, $data, null, null);
        }
        $this->saveAudit(self::AUDIT_STRUCTURE, 'Updated PI parameter ' . $key);
        return $this->listParams($piId);
    }

    public function removeParam(int $piId, int $paramId): array {
        $this->requireAdmin();
        $existing = DbMysql::select('kpa_pi_param', array('paramId' => $paramId), true);
        DbMysql::update('kpa_pi_param', array('param_status' => 2), array('param_id' => $paramId));
        $this->writeHistory('PARAM', $paramId, 'REMOVE', $existing, array('paramStatus' => 2), null, null);
        $this->saveAudit(self::AUDIT_STRUCTURE, 'Removed PI parameter ' . $existing['paramKey']);
        return $this->listParams($piId);
    }

    /**
     * Dry-run a formula with sample values so the administrator can verify it.
     */
    public function testFormula(int $piId, array $columns): array {
        $this->requireAdmin();
        $pi = DbMysql::select('kpa_pi', array('piId' => $piId), true);
        $expr = trim(strval($columns['formulaExpr'] ?? $pi['formulaExpr'] ?? ''));
        $values = array();
        $input = is_array($columns['params'] ?? null) ? $columns['params'] : array();
        foreach ($input as $key => $value) {
            $values[strtolower(strval($key))] = $value === '' ? null : floatval($value);
        }
        $calc = new KpaCalculator();
        $calcType = strtoupper(strval($columns['calcType'] ?? $pi['calcType'] ?? 'EXPRESSION'));
        if ($calcType !== 'EXPRESSION') {
            $result = $calc->evaluate(array(
                'calcType' => $calcType,
                'formulaExpr' => $expr,
                'targetValue' => floatval($columns['targetValue'] ?? $pi['targetValue']),
                'targetUnit' => strval($columns['targetUnit'] ?? $pi['targetUnit']),
                'passRule' => strval($columns['passRule'] ?? $pi['passRule'])
            ), $values);
            return array(
                'ok' => $result['actualValue'] !== null,
                'value' => $result['actualValue'],
                'isPass' => $result['isPass'],
                'message' => $result['message']
            );
        }
        $test = $calc->test($expr, $values);
        if ($test['ok']) {
            $test['isPass'] = $calc->passes(
                floatval($test['value']),
                floatval($columns['targetValue'] ?? $pi['targetValue']),
                strval($columns['passRule'] ?? $pi['passRule'])
            );
        } else {
            $test['isPass'] = null;
        }
        return $test;
    }

    /**
     * Flat structure listing used by the KPI Structure report/export.
     */
    public function structureReport(?int $siteId = null): array {
        $this->requireView();
        $rows = $this->listPi($siteId, false);
        $weightageTotal = 0.0;
        foreach ($rows as $row) {
            if (intval($row['piStatus']) === 1) {
                $weightageTotal += floatval($row['weightagePct']);
            }
        }
        return array(
            'rows' => $rows,
            'weightageTotal' => round($weightageTotal, 2),
            'weightageBalanced' => abs($weightageTotal - 100) < 0.01,
            'piCount' => count($rows)
        );
    }

    // -----------------------------------------------------------------------
    // PI assignment
    // -----------------------------------------------------------------------

    public function listAssignments(?int $siteId = null): array {
        $siteId = $this->resolveSiteId($siteId);
        $this->assertSiteAccess($siteId);
        return $this->queryAll(
            "SELECT a.assign_id, a.site_id, a.pi_id, a.user_id, a.assign_status,
                    p.pi_no, p.pi_name, g.group_no, g.group_name,
                    u.user_name, u.user_first_name, u.user_last_name
             FROM kpa_pi_assignment a
             INNER JOIN kpa_pi p ON p.pi_id = a.pi_id
             INNER JOIN kpa_group g ON g.group_id = p.group_id
             INNER JOIN sys_user u ON u.user_id = a.user_id
             WHERE a.site_id = :siteId AND a.assign_status = 1
             ORDER BY g.sort_order, p.sort_order, u.user_name",
            array('siteId' => $siteId)
        );
    }

    /**
     * Candidate users for assignment: anyone holding the PI Entry role.
     */
    public function listEntryUsers(?int $siteId = null): array {
        $siteId = $this->resolveSiteId($siteId);
        $sql = "SELECT DISTINCT u.user_id, u.user_name, u.user_first_name, u.user_last_name, u.site_id
                FROM sys_user u
                INNER JOIN sys_user_role r ON r.user_id = u.user_id
                WHERE r.role_id = :roleId AND u.user_status = 1";
        $params = array('roleId' => self::ROLE_PI_ENTRY);
        if (!$this->isAdministrator()) {
            $sql .= " AND u.site_id = :siteId";
            $params['siteId'] = $siteId;
        }
        $sql .= " ORDER BY u.user_name";
        return $this->queryAll($sql, $params);
    }

    public function saveAssignment(array $columns): array {
        $this->requireAdmin();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $piId = intval($columns['piId'] ?? 0);
        if ($piId <= 0) {
            throw new Exception('Select a Performance Indicator.', 31);
        }
        DbMysql::select('kpa_pi', array('piId' => $piId), true);
        $userIds = $columns['userIds'] ?? ($columns['userId'] ?? array());
        if (!is_array($userIds)) {
            $userIds = array_filter(array_map('trim', explode(',', strval($userIds))));
        }
        if (empty($userIds)) {
            throw new Exception('Select at least one user.', 31);
        }
        foreach ($userIds as $rawUserId) {
            $userId = intval($rawUserId);
            if ($userId <= 0) {
                continue;
            }
            $existing = $this->queryOne(
                "SELECT assign_id FROM kpa_pi_assignment WHERE site_id = :siteId AND pi_id = :piId AND user_id = :userId",
                array('siteId' => $siteId, 'piId' => $piId, 'userId' => $userId)
            );
            if (!empty($existing)) {
                DbMysql::update('kpa_pi_assignment', array('assign_status' => 1), array('assign_id' => intval($existing['assignId'])));
                continue;
            }
            DbMysql::insert('kpa_pi_assignment', array(
                'site_id' => $siteId,
                'pi_id' => $piId,
                'user_id' => $userId,
                'assign_status' => 1,
                'assign_created_by' => $this->userId
            ));
        }
        $this->saveAudit(self::AUDIT_ASSIGN, 'Assigned PI ' . $piId . ' at site ' . $siteId);
        return $this->listAssignments($siteId);
    }

    public function removeAssignment(int $assignId): array {
        $this->requireAdmin();
        $existing = DbMysql::select('kpa_pi_assignment', array('assignId' => $assignId), true);
        $siteId = intval($existing['siteId']);
        $this->assertSiteAccess($siteId);
        DbMysql::update('kpa_pi_assignment', array('assign_status' => 2), array('assign_id' => $assignId));
        $this->saveAudit(self::AUDIT_ASSIGN, 'Removed PI assignment ' . $assignId);
        return $this->listAssignments($siteId);
    }
}
