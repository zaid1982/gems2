<?php

/**
 * Monthly KPI evaluation.
 *
 * Creating a month snapshots the KPI template into kpa_evaluation_pi and
 * kpa_evaluation_param, so later changes to a target, weightage or formula never
 * rewrite history. PI Entry fills the parameters and submits; submitting locks
 * the indicator and only KPI Admin can reopen it. The evaluation becomes
 * COMPLETED once every indicator is submitted. There is no approval step.
 */
class KpaEvaluation extends KpaBase {

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const PI_DRAFT = 'DRAFT';
    public const PI_SUBMITTED = 'SUBMITTED';

    public function list(array $filters): array {
        $this->requireView();
        $sql = "SELECT e.*, s.site_name, s.site_code
                FROM kpa_evaluation e
                INNER JOIN cli_site s ON s.site_id = e.site_id
                WHERE 1 = 1";
        $params = array();
        if (!$this->isAdministrator() && !$this->hasAnyRole(array(self::ROLE_KPI_ADMIN))) {
            $sql .= " AND e.site_id = :ownSite";
            $params['ownSite'] = $this->resolveSiteId();
        } else if (!empty($filters['siteId'])) {
            $sql .= " AND e.site_id = :siteId";
            $params['siteId'] = intval($filters['siteId']);
        }
        if (!empty($filters['year'])) {
            $sql .= " AND e.eval_year = :year";
            $params['year'] = intval($filters['year']);
        }
        if (!empty($filters['status'])) {
            $sql .= " AND e.eval_status = :status";
            $params['status'] = strtoupper($filters['status']);
        }
        $sql .= " ORDER BY e.eval_year DESC, e.eval_month DESC";
        $rows = $this->queryAll($sql, $params);
        foreach ($rows as &$row) {
            $this->decorateEvaluation($row);
        }
        return $rows;
    }

    public function get(int $evalId): array {
        $this->requireView();
        $row = $this->queryOne(
            "SELECT e.*, s.site_name, s.site_code
             FROM kpa_evaluation e
             INNER JOIN cli_site s ON s.site_id = e.site_id
             WHERE e.eval_id = :id",
            array('id' => $evalId)
        );
        if (empty($row)) {
            throw new Exception('Monthly KPI evaluation not found.', 31);
        }
        $this->assertSiteAccess(intval($row['siteId']));
        $this->decorateEvaluation($row);
        $row['indicators'] = $this->listEvaluationPi($evalId, intval($row['siteId']));
        $row['groups'] = $this->groupTotals($row['indicators']);
        return $row;
    }

    /**
     * Create the month and snapshot the template.
     */
    public function create(array $columns): array {
        $this->requireAdmin();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $year = intval($columns['year'] ?? $columns['evalYear'] ?? 0);
        $month = intval($columns['month'] ?? $columns['evalMonth'] ?? 0);
        if ($year < 2000 || $year > 2999) {
            throw new Exception('Select a valid evaluation year.', 31);
        }
        if ($month < 1 || $month > 12) {
            throw new Exception('Select a valid evaluation month.', 31);
        }
        $existing = $this->queryOne(
            "SELECT eval_id FROM kpa_evaluation WHERE site_id = :siteId AND eval_year = :year AND eval_month = :month",
            array('siteId' => $siteId, 'year' => $year, 'month' => $month)
        );
        if (!empty($existing)) {
            throw new Exception('A KPI evaluation for ' . $this->monthName($month) . ' ' . $year . ' already exists.', 31);
        }

        $mpv = isset($columns['mpv']) && $columns['mpv'] !== ''
            ? round(floatval($columns['mpv']), 3)
            : $this->previousMpv($siteId, $year, $month);
        if ($mpv < 0) {
            throw new Exception('The Monthly Payment Value cannot be negative.', 31);
        }
        $maxApdPct = $this->maxApdPct($siteId);
        $apdMax = round($mpv * $maxApdPct / 100, 2);

        $template = $this->templateIndicators($siteId);
        if (empty($template)) {
            throw new Exception('No active Performance Indicators are configured. Set up the KPI structure first.', 31);
        }

        $evalId = DbMysql::insert('kpa_evaluation', array(
            'site_id' => $siteId,
            'eval_year' => $year,
            'eval_month' => $month,
            'mpv' => $mpv,
            'max_apd_pct' => $maxApdPct,
            'apd_max_amount' => $apdMax,
            'eval_status' => self::STATUS_OPEN,
            'pi_total' => count($template),
            'pi_submitted' => 0,
            'remarks' => $this->sanitizeText($columns['remarks'] ?? '', 500),
            'eval_created_by' => $this->userId
        ));

        foreach ($template as $pi) {
            $evalPiId = DbMysql::insert('kpa_evaluation_pi', array(
                'eval_id' => $evalId,
                'pi_id' => intval($pi['piId']),
                'group_no' => $pi['groupNo'],
                'group_name' => $pi['groupName'],
                'pi_no' => $pi['piNo'],
                'pi_name' => $pi['piName'],
                'target_value' => $pi['targetValue'],
                'target_unit' => $pi['targetUnit'],
                'demerit_point' => intval($pi['demeritPoint']),
                'weightage_pct' => $pi['weightagePct'],
                'pass_rule' => $pi['passRule'],
                'calc_type' => $pi['calcType'],
                'formula_expr' => $pi['formulaExpr'],
                'source_type' => $pi['sourceType'],
                'sort_order' => intval($pi['sortOrder']),
                'pi_status' => self::PI_DRAFT
            ));
            foreach ($this->templateParams(intval($pi['piId'])) as $param) {
                DbMysql::insert('kpa_evaluation_param', array(
                    'eval_pi_id' => $evalPiId,
                    'param_id' => intval($param['paramId']),
                    'param_key' => $param['paramKey'],
                    'param_label' => $param['paramLabel'],
                    'data_type' => $param['dataType'],
                    'source_type' => $param['sourceType'],
                    'gems_hook' => $param['gemsHook'],
                    'is_required' => intval($param['isRequired']) === 1 ? 1 : 0,
                    'sort_order' => intval($param['sortOrder']),
                    'source_used' => $param['sourceType']
                ));
            }
            // Fills apd_value so the grid shows the exposure before any entry.
            $this->recalculatePi($evalPiId);
        }
        $this->refreshTotals($evalId);
        $this->writeHistory('EVALUATION', $evalId, 'CREATE', null, array('year' => $year, 'month' => $month, 'mpv' => $mpv), null, $siteId);
        $this->saveAudit(self::AUDIT_EVAL_CREATE, 'Created KPI evaluation ' . $this->monthName($month) . ' ' . $year);
        return $this->get($evalId);
    }

    /**
     * Change the MPV (or remarks) while the month is open and recalculate APD.
     */
    public function update(int $evalId, array $columns): array {
        $this->requireAdmin();
        $current = DbMysql::select('kpa_evaluation', array('evalId' => $evalId), true);
        $this->assertSiteAccess(intval($current['siteId']));
        $data = array('eval_updated_by' => $this->userId);
        if (isset($columns['mpv']) && $columns['mpv'] !== '') {
            $mpv = round(floatval($columns['mpv']), 3);
            if ($mpv < 0) {
                throw new Exception('The Monthly Payment Value cannot be negative.', 31);
            }
            $maxApdPct = isset($columns['maxApdPct']) && $columns['maxApdPct'] !== ''
                ? round(floatval($columns['maxApdPct']), 2)
                : floatval($current['maxApdPct']);
            $data['mpv'] = $mpv;
            $data['max_apd_pct'] = $maxApdPct;
            $data['apd_max_amount'] = round($mpv * $maxApdPct / 100, 2);
        }
        if (isset($columns['remarks'])) {
            $data['remarks'] = $this->sanitizeText($columns['remarks'], 500);
        }
        DbMysql::update('kpa_evaluation', $data, array('eval_id' => $evalId));

        // APD exposure per indicator depends on the APD maximum, so redo them all.
        foreach ($this->queryAll("SELECT eval_pi_id FROM kpa_evaluation_pi WHERE eval_id = :id", array('id' => $evalId)) as $row) {
            $this->recalculatePi(intval($row['evalPiId']));
        }
        $this->refreshTotals($evalId);
        $this->writeHistory('EVALUATION', $evalId, 'UPDATE', $current, $data, null, intval($current['siteId']));
        $this->saveAudit(self::AUDIT_EVAL_UPDATE, 'Updated KPI evaluation ' . $evalId);
        return $this->get($evalId);
    }

    // -----------------------------------------------------------------------
    // PI entry
    // -----------------------------------------------------------------------

    public function getPi(int $evalPiId): array {
        $row = $this->evaluationPiRow($evalPiId);
        $this->assertSiteAccess(intval($row['siteId']));
        $this->requireView();
        $row['params'] = $this->evaluationParams($evalPiId);
        $row['canEdit'] = $row['piStatus'] === self::PI_DRAFT
            && $row['evalStatus'] !== self::STATUS_COMPLETED
            && $this->canEntry(intval($row['piId']), intval($row['siteId']));
        $row['canReopen'] = $row['piStatus'] === self::PI_SUBMITTED && $this->canAdmin();
        $row['monthName'] = $this->monthName(intval($row['evalMonth']));
        $row['submittedByName'] = $this->userDisplayName(intval($row['submittedBy'] ?? 0));
        return $row;
    }

    /**
     * Save the captured parameter values and recalculate the achievement.
     */
    public function savePiParams(int $evalPiId, array $columns): array {
        $row = $this->evaluationPiRow($evalPiId);
        $this->assertSiteAccess(intval($row['siteId']));
        $this->requireEntry(intval($row['piId']), intval($row['siteId']));
        if ($row['piStatus'] !== self::PI_DRAFT) {
            throw new Exception('This PI has been submitted. Ask a KPI Admin to reopen it before changing the values.', 31);
        }
        $input = is_array($columns['params'] ?? null) ? $columns['params'] : array();
        $stored = $this->evaluationParams($evalPiId);
        $byKey = array();
        foreach ($stored as $param) {
            $byKey[$param['paramKey']] = $param;
        }
        foreach ($input as $key => $value) {
            $key = strtolower(strval($key));
            if (!isset($byKey[$key])) {
                continue;
            }
            $clean = ($value === '' || $value === null) ? 'NULL' : round(floatval($value), 4);
            DbMysql::update('kpa_evaluation_param', array(
                'param_value' => $clean,
                'source_used' => 'MANUAL'
            ), array('eval_param_id' => intval($byKey[$key]['evalParamId'])));
        }
        if (isset($columns['remarks'])) {
            DbMysql::update('kpa_evaluation_pi', array(
                'remarks' => $this->sanitizeText($columns['remarks'], 500),
                'updated_by' => $this->userId
            ), array('eval_pi_id' => $evalPiId));
        }
        $this->recalculatePi($evalPiId);
        // Autosave from a field change only needs this PI recalculated. Rolling
        // the month totals and writing an audit row on every blur is what made
        // a slow DB freeze the entry screen.
        if (empty($columns['autosave'])) {
            $this->refreshTotals(intval($row['evalId']));
            $this->saveAudit(self::AUDIT_PARAMS, 'Saved parameters for PI ' . $row['piNo']);
        }
        return $this->getPi($evalPiId);
    }

    public function submitPi(int $evalPiId): array {
        $row = $this->evaluationPiRow($evalPiId);
        $this->assertSiteAccess(intval($row['siteId']));
        $this->requireEntry(intval($row['piId']), intval($row['siteId']));
        if ($row['piStatus'] === self::PI_SUBMITTED) {
            return $this->getPi($evalPiId);
        }
        // Every required parameter must be captured before the PI can be locked.
        foreach ($this->evaluationParams($evalPiId) as $param) {
            if (intval($param['isRequired']) === 1 && ($param['paramValue'] === null || $param['paramValue'] === '')) {
                throw new Exception('Enter a value for "' . $param['paramLabel'] . '" before submitting.', 31);
            }
        }
        $result = $this->recalculatePi($evalPiId);
        if ($result['actualValue'] === null) {
            throw new Exception($result['message'] ?: 'The achievement cannot be calculated. Review the parameter values.', 31);
        }
        DbMysql::update('kpa_evaluation_pi', array(
            'pi_status' => self::PI_SUBMITTED,
            'submitted_by' => $this->userId,
            'submitted_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->userId
        ), array('eval_pi_id' => $evalPiId));
        $this->refreshTotals(intval($row['evalId']));
        $this->writeHistory('EVALUATION_PI', $evalPiId, 'SUBMIT', array('piStatus' => self::PI_DRAFT), array('piStatus' => self::PI_SUBMITTED), null, intval($row['siteId']));
        $this->saveAudit(self::AUDIT_SUBMIT, 'Submitted PI ' . $row['piNo']);
        return $this->getPi($evalPiId);
    }

    public function reopenPi(int $evalPiId, array $columns): array {
        $this->requireAdmin();
        $row = $this->evaluationPiRow($evalPiId);
        $this->assertSiteAccess(intval($row['siteId']));
        if ($row['piStatus'] !== self::PI_SUBMITTED) {
            throw new Exception('Only a submitted PI can be reopened.', 31);
        }
        DbMysql::update('kpa_evaluation_pi', array(
            'pi_status' => self::PI_DRAFT,
            'reopened_by' => $this->userId,
            'reopened_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->userId
        ), array('eval_pi_id' => $evalPiId));
        // Reopening a PI takes the month out of COMPLETED.
        DbMysql::update('kpa_evaluation', array('eval_status' => self::STATUS_OPEN), array('eval_id' => intval($row['evalId'])));
        $this->refreshTotals(intval($row['evalId']));
        $this->writeHistory('EVALUATION_PI', $evalPiId, 'REOPEN', array('piStatus' => self::PI_SUBMITTED), array('piStatus' => self::PI_DRAFT), $columns['reason'] ?? null, intval($row['siteId']));
        $this->saveAudit(self::AUDIT_REOPEN, 'Reopened PI ' . $row['piNo']);
        return $this->getPi($evalPiId);
    }

    // -----------------------------------------------------------------------
    // Calculation
    // -----------------------------------------------------------------------

    /**
     * Recalculate one indicator: achievement, pass/fail, demerit and APD.
     */
    public function recalculatePi(int $evalPiId): array {
        $row = $this->evaluationPiRow($evalPiId);
        $values = array();
        foreach ($this->evaluationParams($evalPiId) as $param) {
            $values[$param['paramKey']] = $param['paramValue'] === null ? null : floatval($param['paramValue']);
        }
        $calc = new KpaCalculator();
        $result = $calc->evaluate($row, $values);
        $apd = $calc->apd(floatval($row['apdMaxAmount']), $row, $result['isPass']);

        DbMysql::update('kpa_evaluation_pi', array(
            'actual_value' => $result['actualValue'] === null ? 'NULL' : $result['actualValue'],
            'result_pct' => $result['resultPct'] === null ? 'NULL' : $result['resultPct'],
            'is_pass' => $result['isPass'] === null ? 'NULL' : ($result['isPass'] ? 1 : 0),
            'demerit_imposed' => $apd['demeritImposed'],
            'apd_value' => $apd['apdValue'],
            'apd_deducted' => $apd['apdDeducted'],
            'calc_message' => $result['message'] === null ? 'NULL' : $result['message']
        ), array('eval_pi_id' => $evalPiId));

        return array_merge($result, $apd);
    }

    /**
     * Roll the indicator results up to the month and close it when every
     * indicator has been submitted.
     */
    public function refreshTotals(int $evalId): void {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS pi_total,
                    SUM(CASE WHEN pi_status = 'SUBMITTED' THEN 1 ELSE 0 END) AS pi_submitted,
                    COALESCE(SUM(demerit_imposed), 0) AS total_demerit,
                    COALESCE(SUM(apd_deducted), 0) AS total_apd
             FROM kpa_evaluation_pi WHERE eval_id = :id",
            array('id' => $evalId)
        );
        $total = intval($row['piTotal'] ?? 0);
        $submitted = intval($row['piSubmitted'] ?? 0);
        DbMysql::update('kpa_evaluation', array(
            'pi_total' => $total,
            'pi_submitted' => $submitted,
            'total_demerit' => intval($row['totalDemerit'] ?? 0),
            'total_apd_deducted' => round(floatval($row['totalApd'] ?? 0), 2),
            'eval_status' => ($total > 0 && $submitted >= $total) ? self::STATUS_COMPLETED : self::STATUS_OPEN
        ), array('eval_id' => $evalId));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function decorateEvaluation(array &$row): void {
        $row['mpv'] = floatval($row['mpv']);
        $row['maxApdPct'] = floatval($row['maxApdPct']);
        $row['apdMaxAmount'] = floatval($row['apdMaxAmount']);
        $row['totalApdDeducted'] = floatval($row['totalApdDeducted']);
        $row['totalDemerit'] = intval($row['totalDemerit']);
        $row['piTotal'] = intval($row['piTotal']);
        $row['piSubmitted'] = intval($row['piSubmitted']);
        $row['monthName'] = $this->monthName(intval($row['evalMonth']));
        $row['periodLabel'] = $row['monthName'] . ' ' . $row['evalYear'];
        $row['apdRetained'] = round($row['apdMaxAmount'] - $row['totalApdDeducted'], 2);
        $row['canEdit'] = $this->canAdmin();
    }

    private function listEvaluationPi(int $evalId, int $siteId): array {
        $rows = $this->queryAll(
            "SELECT * FROM kpa_evaluation_pi WHERE eval_id = :id ORDER BY group_no, sort_order, pi_no",
            array('id' => $evalId)
        );
        $assigned = $this->assignedPiIds($siteId);
        $isAdmin = $this->canAdmin();
        foreach ($rows as &$row) {
            $row['targetValue'] = floatval($row['targetValue']);
            $row['weightagePct'] = floatval($row['weightagePct']);
            $row['demeritPoint'] = intval($row['demeritPoint']);
            $row['demeritImposed'] = intval($row['demeritImposed']);
            $row['apdValue'] = floatval($row['apdValue']);
            $row['apdDeducted'] = floatval($row['apdDeducted']);
            $row['actualValue'] = $row['actualValue'] === null ? null : floatval($row['actualValue']);
            $row['resultPct'] = $row['resultPct'] === null ? null : floatval($row['resultPct']);
            $row['isPass'] = $row['isPass'] === null ? null : intval($row['isPass']) === 1;
            $row['isAssigned'] = $isAdmin || in_array(intval($row['piId']), $assigned, true);
            $row['canEdit'] = $row['piStatus'] === self::PI_DRAFT && $row['isAssigned'];
            $row['canReopen'] = $row['piStatus'] === self::PI_SUBMITTED && $isAdmin;
            $row['submittedByName'] = $this->userDisplayName(intval($row['submittedBy'] ?? 0));
        }
        return $rows;
    }

    private function groupTotals(array $indicators): array {
        $groups = array();
        foreach ($indicators as $pi) {
            $key = $pi['groupNo'];
            if (!isset($groups[$key])) {
                $groups[$key] = array(
                    'groupNo' => $pi['groupNo'],
                    'groupName' => $pi['groupName'],
                    'piCount' => 0,
                    'submitted' => 0,
                    'weightagePct' => 0.0,
                    'demeritImposed' => 0,
                    'apdValue' => 0.0,
                    'apdDeducted' => 0.0
                );
            }
            $groups[$key]['piCount']++;
            if ($pi['piStatus'] === self::PI_SUBMITTED) {
                $groups[$key]['submitted']++;
            }
            $groups[$key]['weightagePct'] += floatval($pi['weightagePct']);
            $groups[$key]['demeritImposed'] += intval($pi['demeritImposed']);
            $groups[$key]['apdValue'] += floatval($pi['apdValue']);
            $groups[$key]['apdDeducted'] += floatval($pi['apdDeducted']);
        }
        foreach ($groups as &$group) {
            $group['weightagePct'] = round($group['weightagePct'], 2);
            $group['apdValue'] = round($group['apdValue'], 2);
            $group['apdDeducted'] = round($group['apdDeducted'], 2);
        }
        return array_values($groups);
    }

    private function evaluationPiRow(int $evalPiId): array {
        $row = $this->queryOne(
            "SELECT ep.*, e.eval_id, e.site_id, e.eval_year, e.eval_month, e.mpv, e.max_apd_pct,
                    e.apd_max_amount, e.eval_status, s.site_name, s.site_code
             FROM kpa_evaluation_pi ep
             INNER JOIN kpa_evaluation e ON e.eval_id = ep.eval_id
             INNER JOIN cli_site s ON s.site_id = e.site_id
             WHERE ep.eval_pi_id = :id",
            array('id' => $evalPiId)
        );
        if (empty($row)) {
            throw new Exception('Performance Indicator not found in this evaluation.', 31);
        }
        $row['targetValue'] = floatval($row['targetValue']);
        $row['weightagePct'] = floatval($row['weightagePct']);
        $row['apdMaxAmount'] = floatval($row['apdMaxAmount']);
        $row['apdValue'] = floatval($row['apdValue']);
        $row['apdDeducted'] = floatval($row['apdDeducted']);
        $row['actualValue'] = $row['actualValue'] === null ? null : floatval($row['actualValue']);
        $row['resultPct'] = $row['resultPct'] === null ? null : floatval($row['resultPct']);
        $row['isPass'] = $row['isPass'] === null ? null : intval($row['isPass']) === 1;
        return $row;
    }

    private function evaluationParams(int $evalPiId): array {
        $rows = $this->queryAll(
            "SELECT * FROM kpa_evaluation_param WHERE eval_pi_id = :id ORDER BY sort_order, param_key",
            array('id' => $evalPiId)
        );
        foreach ($rows as &$row) {
            $row['paramValue'] = $row['paramValue'] === null ? null : floatval($row['paramValue']);
            $row['isRequired'] = intval($row['isRequired']);
        }
        return $rows;
    }

    private function templateIndicators(int $siteId): array {
        $templateSite = $this->resolveTemplateSite($siteId);
        $rows = $this->queryAll(
            "SELECT p.pi_id, p.pi_no, p.pi_name, p.target_value, p.target_unit, p.demerit_point,
                    p.weightage_pct, p.pass_rule, p.calc_type, p.formula_expr, p.source_type, p.sort_order,
                    g.group_no, g.group_name, g.sort_order AS group_sort
             FROM kpa_pi p
             INNER JOIN kpa_group g ON g.group_id = p.group_id
             WHERE g.site_id = :siteId AND g.group_status = 1 AND p.pi_status = 1
             ORDER BY g.sort_order, g.group_no, p.sort_order, p.pi_no",
            array('siteId' => $templateSite)
        );
        foreach ($rows as &$row) {
            $row['targetValue'] = floatval($row['targetValue']);
            $row['weightagePct'] = floatval($row['weightagePct']);
        }
        return $rows;
    }

    private function templateParams(int $piId): array {
        return $this->queryAll(
            "SELECT param_id, param_key, param_label, data_type, source_type, gems_hook, is_required, sort_order
             FROM kpa_pi_param
             WHERE pi_id = :piId AND param_status = 1
             ORDER BY sort_order, param_key",
            array('piId' => $piId)
        );
    }

    /**
     * MPV defaults to the most recent month already captured for the site.
     */
    public function previousMpv(int $siteId, int $year, int $month): float {
        $row = $this->queryOne(
            "SELECT mpv FROM kpa_evaluation
             WHERE site_id = :siteId AND (eval_year < :year OR (eval_year = :year2 AND eval_month < :month))
             ORDER BY eval_year DESC, eval_month DESC LIMIT 1",
            array('siteId' => $siteId, 'year' => $year, 'year2' => $year, 'month' => $month)
        );
        return empty($row) ? 0.0 : round(floatval($row['mpv']), 3);
    }

    /**
     * Values the Create Month dialog needs before anything is saved.
     */
    public function newDefaults(array $filters): array {
        $this->requireAdmin();
        $siteId = $this->resolveSiteId($filters['siteId'] ?? null);
        $year = intval($filters['year'] ?? date('Y'));
        $month = intval($filters['month'] ?? date('n'));
        return array(
            'siteId' => $siteId,
            'year' => $year,
            'month' => $month,
            'mpv' => $this->previousMpv($siteId, $year, $month),
            'maxApdPct' => $this->maxApdPct($siteId),
            'piCount' => count($this->templateIndicators($siteId))
        );
    }
}
