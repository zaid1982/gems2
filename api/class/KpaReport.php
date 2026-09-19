<?php

/**
 * Read-only KPI / APD reporting: the monthly summary grid that feeds the
 * KPI/APD dashboard, and the multi-month history used for trends.
 */
class KpaReport extends KpaBase {

    /**
     * Monthly summary. Accepts either an evalId, or a site/year/month triple so
     * the dashboard can drive it straight from its period selectors.
     */
    public function summary(array $filters): array {
        $this->requireView();
        $evalId = intval($filters['evalId'] ?? 0);
        if ($evalId > 0) {
            $evaluation = $this->queryOne(
                "SELECT e.*, s.site_name, s.site_code
                 FROM kpa_evaluation e INNER JOIN cli_site s ON s.site_id = e.site_id
                 WHERE e.eval_id = :id",
                array('id' => $evalId)
            );
        } else {
            $siteId = $this->resolveSiteId($filters['siteId'] ?? null);
            $year = intval($filters['year'] ?? date('Y'));
            $month = intval($filters['month'] ?? date('n'));
            $evaluation = $this->queryOne(
                "SELECT e.*, s.site_name, s.site_code
                 FROM kpa_evaluation e INNER JOIN cli_site s ON s.site_id = e.site_id
                 WHERE e.site_id = :siteId AND e.eval_year = :year AND e.eval_month = :month",
                array('siteId' => $siteId, 'year' => $year, 'month' => $month)
            );
            if (empty($evaluation)) {
                // No month captured yet: show the template so the grid is not blank.
                return $this->templatePreview($siteId, $year, $month);
            }
        }
        if (empty($evaluation)) {
            throw new Exception('Monthly KPI evaluation not found.', 31);
        }
        $this->assertSiteAccess(intval($evaluation['siteId']));

        $rows = $this->queryAll(
            "SELECT eval_pi_id, pi_id, group_no, group_name, pi_no, pi_name, target_value, target_unit,
                    demerit_point, weightage_pct, pass_rule, calc_type, actual_value, result_pct, is_pass,
                    demerit_imposed, apd_value, apd_deducted, pi_status, calc_message, remarks, sort_order
             FROM kpa_evaluation_pi WHERE eval_id = :id
             ORDER BY group_no, sort_order, pi_no",
            array('id' => intval($evaluation['evalId']))
        );
        $indicators = array();
        $weightageTotal = 0.0;
        $apdValueTotal = 0.0;
        foreach ($rows as $row) {
            $row['targetValue'] = floatval($row['targetValue']);
            $row['weightagePct'] = floatval($row['weightagePct']);
            $row['demeritPoint'] = intval($row['demeritPoint']);
            $row['demeritImposed'] = intval($row['demeritImposed']);
            $row['apdValue'] = floatval($row['apdValue']);
            $row['apdDeducted'] = floatval($row['apdDeducted']);
            $row['actualValue'] = $row['actualValue'] === null ? null : floatval($row['actualValue']);
            $row['resultPct'] = $row['resultPct'] === null ? null : floatval($row['resultPct']);
            $row['isPass'] = $row['isPass'] === null ? null : intval($row['isPass']) === 1;
            $row['categoryKey'] = $this->categoryKey($row['groupNo']);
            $weightageTotal += $row['weightagePct'];
            $apdValueTotal += $row['apdValue'];
            $indicators[] = $row;
        }

        return array(
            'evalId' => intval($evaluation['evalId']),
            'siteId' => intval($evaluation['siteId']),
            'siteName' => $evaluation['siteName'],
            'siteCode' => $evaluation['siteCode'],
            'year' => intval($evaluation['evalYear']),
            'month' => intval($evaluation['evalMonth']),
            'monthName' => $this->monthName(intval($evaluation['evalMonth'])),
            'periodLabel' => $this->monthName(intval($evaluation['evalMonth'])) . ' ' . $evaluation['evalYear'],
            'status' => $evaluation['evalStatus'],
            'mpv' => floatval($evaluation['mpv']),
            'maxApdPct' => floatval($evaluation['maxApdPct']),
            'apdMaxAmount' => floatval($evaluation['apdMaxAmount']),
            'totalDemerit' => intval($evaluation['totalDemerit']),
            'totalApdDeducted' => floatval($evaluation['totalApdDeducted']),
            'apdRetained' => round(floatval($evaluation['apdMaxAmount']) - floatval($evaluation['totalApdDeducted']), 2),
            'piTotal' => intval($evaluation['piTotal']),
            'piSubmitted' => intval($evaluation['piSubmitted']),
            'weightageTotal' => round($weightageTotal, 2),
            'apdValueTotal' => round($apdValueTotal, 2),
            'indicators' => $indicators,
            'exists' => true,
            'refreshedAt' => date('Y-m-d H:i:s')
        );
    }

    /**
     * Per-month totals plus per-PI series for the history charts.
     */
    public function history(array $filters): array {
        $this->requireView();
        $siteId = $this->resolveSiteId($filters['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $fromYear = intval($filters['fromYear'] ?? ($filters['year'] ?? date('Y')));
        $toYear = intval($filters['toYear'] ?? ($filters['year'] ?? date('Y')));
        if ($toYear < $fromYear) {
            $swap = $fromYear;
            $fromYear = $toYear;
            $toYear = $swap;
        }

        $months = $this->queryAll(
            "SELECT eval_id, eval_year, eval_month, mpv, max_apd_pct, apd_max_amount,
                    total_demerit, total_apd_deducted, eval_status, pi_total, pi_submitted
             FROM kpa_evaluation
             WHERE site_id = :siteId AND eval_year BETWEEN :fromYear AND :toYear
             ORDER BY eval_year, eval_month",
            array('siteId' => $siteId, 'fromYear' => $fromYear, 'toYear' => $toYear)
        );
        $evalIds = array();
        foreach ($months as &$month) {
            $month['mpv'] = floatval($month['mpv']);
            $month['apdMaxAmount'] = floatval($month['apdMaxAmount']);
            $month['totalApdDeducted'] = floatval($month['totalApdDeducted']);
            $month['totalDemerit'] = intval($month['totalDemerit']);
            $month['apdRetained'] = round($month['apdMaxAmount'] - $month['totalApdDeducted'], 2);
            $month['monthName'] = $this->monthName(intval($month['evalMonth']));
            $month['periodLabel'] = substr($month['monthName'], 0, 3) . ' ' . $month['evalYear'];
            $evalIds[] = intval($month['evalId']);
        }

        $piSeries = array();
        if (!empty($evalIds)) {
            $keys = array();
            $params = array();
            foreach ($evalIds as $i => $id) {
                $keys[] = ':e' . $i;
                $params['e' . $i] = $id;
            }
            $rows = $this->queryAll(
                "SELECT ep.eval_id, ep.pi_no, ep.pi_name, ep.target_value, ep.result_pct, ep.actual_value,
                        ep.is_pass, ep.demerit_imposed, ep.apd_deducted, e.eval_year, e.eval_month
                 FROM kpa_evaluation_pi ep
                 INNER JOIN kpa_evaluation e ON e.eval_id = ep.eval_id
                 WHERE ep.eval_id IN (" . implode(',', $keys) . ")
                 ORDER BY ep.pi_no, e.eval_year, e.eval_month",
                $params
            );
            foreach ($rows as $row) {
                $piNo = $row['piNo'];
                if (!isset($piSeries[$piNo])) {
                    $piSeries[$piNo] = array(
                        'piNo' => $piNo,
                        'piName' => $row['piName'],
                        'targetValue' => floatval($row['targetValue']),
                        'points' => array()
                    );
                }
                $piSeries[$piNo]['points'][] = array(
                    'periodLabel' => substr($this->monthName(intval($row['evalMonth'])), 0, 3) . ' ' . $row['evalYear'],
                    'year' => intval($row['evalYear']),
                    'month' => intval($row['evalMonth']),
                    'resultPct' => $row['resultPct'] === null ? null : floatval($row['resultPct']),
                    'actualValue' => $row['actualValue'] === null ? null : floatval($row['actualValue']),
                    'isPass' => $row['isPass'] === null ? null : intval($row['isPass']) === 1,
                    'demeritImposed' => intval($row['demeritImposed']),
                    'apdDeducted' => floatval($row['apdDeducted'])
                );
            }
        }

        $site = $this->getSiteRow($siteId);
        return array(
            'siteId' => $siteId,
            'siteName' => $site['siteName'] ?? '',
            'fromYear' => $fromYear,
            'toYear' => $toYear,
            'months' => $months,
            'piSeries' => array_values($piSeries),
            'totals' => array(
                'apdMax' => round(array_sum(array_column($months, 'apdMaxAmount')), 2),
                'apdDeducted' => round(array_sum(array_column($months, 'totalApdDeducted')), 2),
                'demerit' => array_sum(array_column($months, 'totalDemerit'))
            ),
            'refreshedAt' => date('Y-m-d H:i:s')
        );
    }

    /**
     * Shape of summary() for a month that has not been created yet. The grid
     * shows the configured indicators with empty results.
     */
    private function templatePreview(int $siteId, int $year, int $month): array {
        $templateSite = $this->resolveTemplateSite($siteId);
        $rows = $this->queryAll(
            "SELECT p.pi_id, p.pi_no, p.pi_name, p.target_value, p.target_unit, p.demerit_point,
                    p.weightage_pct, p.pass_rule, p.calc_type, p.sort_order, g.group_no, g.group_name
             FROM kpa_pi p
             INNER JOIN kpa_group g ON g.group_id = p.group_id
             WHERE g.site_id = :siteId AND g.group_status = 1 AND p.pi_status = 1
             ORDER BY g.sort_order, g.group_no, p.sort_order, p.pi_no",
            array('siteId' => $templateSite)
        );
        $indicators = array();
        $weightageTotal = 0.0;
        foreach ($rows as $row) {
            $row['targetValue'] = floatval($row['targetValue']);
            $row['weightagePct'] = floatval($row['weightagePct']);
            $row['demeritPoint'] = intval($row['demeritPoint']);
            $row['demeritImposed'] = 0;
            $row['apdValue'] = 0.0;
            $row['apdDeducted'] = 0.0;
            $row['actualValue'] = null;
            $row['resultPct'] = null;
            $row['isPass'] = null;
            $row['piStatus'] = 'NOT_STARTED';
            $row['evalPiId'] = null;
            $row['categoryKey'] = $this->categoryKey($row['groupNo']);
            $weightageTotal += $row['weightagePct'];
            $indicators[] = $row;
        }
        $site = $this->getSiteRow($siteId);
        return array(
            'evalId' => 0,
            'siteId' => $siteId,
            'siteName' => $site['siteName'] ?? '',
            'siteCode' => $site['siteCode'] ?? '',
            'year' => $year,
            'month' => $month,
            'monthName' => $this->monthName($month),
            'periodLabel' => $this->monthName($month) . ' ' . $year,
            'status' => 'NOT_STARTED',
            'mpv' => 0.0,
            'maxApdPct' => $this->maxApdPct($siteId),
            'apdMaxAmount' => 0.0,
            'totalDemerit' => 0,
            'totalApdDeducted' => 0.0,
            'apdRetained' => 0.0,
            'piTotal' => count($indicators),
            'piSubmitted' => 0,
            'weightageTotal' => round($weightageTotal, 2),
            'apdValueTotal' => 0.0,
            'indicators' => $indicators,
            'exists' => false,
            'refreshedAt' => date('Y-m-d H:i:s')
        );
    }

    /**
     * Stable key for the dashboard category chips.
     */
    private function categoryKey(string $groupNo): string {
        switch ($groupNo) {
            case '1': return 'service';
            case '2': return 'asset';
            case '3': return 'energy';
            case '4': return 'safety';
            default: return 'group' . $groupNo;
        }
    }
}
