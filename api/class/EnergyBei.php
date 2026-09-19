<?php

/**
 * Site BEI configuration and the monthly Building Energy Index result.
 *
 * Electricity defaults to the monthly total derived from the daily meter
 * readings; a site may override it because the daily readings and the figure
 * used for PI 3B do not currently cover the same scope (gap G08).
 */
class EnergyBei extends EnergyBase {

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_FINAL = 'FINAL';

    public function getConfig(?int $siteId = null): array {
        $this->requireView();
        $siteId = $this->resolveSiteId($siteId);
        $this->assertSiteAccess($siteId);
        $site = $this->getSiteRow($siteId);
        $config = DbMysql::select('enr_site_config', array('siteId' => $siteId));
        return array(
            'siteId' => $siteId,
            'siteName' => $site['siteName'] ?? '',
            'floorAreaSqm' => empty($config) ? 0.0 : floatval($config['floorAreaSqm']),
            'targetBei' => empty($config) ? 0.0 : floatval($config['targetBei']),
            'annualiseFactor' => empty($config) ? 1.0 : floatval($config['annualiseFactor']),
            'configured' => !empty($config),
            'canSetup' => $this->canSetup()
        );
    }

    public function saveConfig(array $columns): array {
        $this->requireSetup();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $floorArea = round(floatval($columns['floorAreaSqm'] ?? 0), 2);
        if ($floorArea < 0) {
            throw new Exception('The gross floor area cannot be negative.', 31);
        }
        $targetBei = round(floatval($columns['targetBei'] ?? 0), 4);
        if ($targetBei < 0) {
            throw new Exception('The target BEI cannot be negative.', 31);
        }
        $factor = round(floatval($columns['annualiseFactor'] ?? 1), 4);
        if ($factor <= 0) {
            $factor = 1.0;
        }
        $data = array(
            'site_id' => $siteId,
            'floor_area_sqm' => $floorArea,
            'target_bei' => $targetBei,
            'annualise_factor' => $factor,
            'config_status' => 1
        );
        $existing = DbMysql::select('enr_site_config', array('siteId' => $siteId));
        if (empty($existing)) {
            $data['config_created_by'] = $this->userId;
            DbMysql::insert('enr_site_config', $data);
        } else {
            unset($data['site_id']);
            $data['config_updated_by'] = $this->userId;
            DbMysql::update('enr_site_config', $data, array('site_id' => $siteId));
        }
        $this->saveAudit(self::AUDIT_CONFIG, 'Updated energy configuration for site ' . $siteId);
        return $this->getConfig($siteId);
    }

    /**
     * Twelve months of BEI. A month with no saved row is returned as a preview
     * calculated from the daily readings so the grid is never blank.
     */
    public function list(array $filters): array {
        $this->requireView();
        $siteId = $this->resolveSiteId($filters['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $year = intval($filters['year'] ?? date('Y'));
        $config = $this->getConfig($siteId);

        $saved = array();
        foreach ($this->queryAll(
            "SELECT * FROM enr_monthly_bei WHERE site_id = :siteId AND bei_year = :year ORDER BY bei_month",
            array('siteId' => $siteId, 'year' => $year)
        ) as $row) {
            $saved[intval($row['beiMonth'])] = $row;
        }

        $reading = new EnergyReading();
        $reading->adopt($this);
        $calc = new EnergyCalculator();
        // One pass over the year's readings instead of re-deriving each month.
        $derivedTotals = $reading->yearTotalsByMonth($siteId, $year);

        $months = array();
        for ($month = 1; $month <= 12; $month++) {
            $derived = $derivedTotals[$month] ?? 0.0;
            $row = $saved[$month] ?? null;
            if ($row) {
                $electricity = floatval($row['electricityKwh']);
                $chilled = floatval($row['chilledWaterKwh']);
                $floorArea = floatval($row['floorAreaSqm']) ?: $config['floorAreaSqm'];
                $target = floatval($row['targetBei']) ?: $config['targetBei'];
                $factor = floatval($row['annualiseFactor']) ?: $config['annualiseFactor'];
                $isOverride = intval($row['electricityIsOverride']) === 1;
                $status = $row['beiStatus'];
                $remarks = strval($row['remarks'] ?? '');
                $beiId = intval($row['beiId']);
            } else {
                $electricity = $derived;
                $chilled = 0.0;
                $floorArea = $config['floorAreaSqm'];
                $target = $config['targetBei'];
                $factor = $config['annualiseFactor'];
                $isOverride = false;
                $status = 'NOT_SAVED';
                $remarks = '';
                $beiId = 0;
            }
            $result = $calc->bei($electricity, $chilled, $floorArea, $target, $factor);
            $months[] = array(
                'beiId' => $beiId,
                'year' => $year,
                'month' => $month,
                'monthName' => $this->monthName($month),
                'periodLabel' => substr($this->monthName($month), 0, 3) . ' ' . $year,
                'electricityKwh' => round($electricity, 2),
                'derivedElectricityKwh' => round($derived, 2),
                'electricityIsOverride' => $isOverride,
                'chilledWaterKwh' => round($chilled, 2),
                'floorAreaSqm' => $floorArea,
                'targetBei' => $target,
                'annualiseFactor' => $factor,
                'totalKwh' => $result['totalKwh'],
                'actualBei' => $result['actualBei'],
                'resultPct' => $result['resultPct'],
                'isPass' => $result['isPass'],
                'message' => $result['message'],
                'beiStatus' => $status,
                'remarks' => $remarks,
                'saved' => $row !== null
            );
        }

        return array(
            'siteId' => $siteId,
            'siteName' => $config['siteName'],
            'year' => $year,
            'config' => $config,
            'months' => $months,
            'canRecord' => $this->canRecord(),
            'refreshedAt' => date('Y-m-d H:i:s')
        );
    }

    /**
     * Save one month. Leaving the electricity figure empty re-derives it from
     * the daily readings and clears the override flag.
     */
    public function save(array $columns): array {
        $this->requireRecord();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $year = intval($columns['year'] ?? date('Y'));
        $month = intval($columns['month'] ?? date('n'));
        $this->checkPeriod($year, $month);
        $config = $this->getConfig($siteId);

        $existing = $this->queryOne(
            "SELECT bei_id, bei_status FROM enr_monthly_bei WHERE site_id = :siteId AND bei_year = :year AND bei_month = :month",
            array('siteId' => $siteId, 'year' => $year, 'month' => $month)
        );
        if (!empty($existing) && $existing['beiStatus'] === self::STATUS_FINAL) {
            throw new Exception('This month has been finalised. Reopen it before changing the values.', 31);
        }

        $reading = new EnergyReading();
        $reading->adopt($this);
        $derived = $reading->monthTotalKwh($siteId, $year, $month);

        $rawElectricity = $columns['electricityKwh'] ?? '';
        $isOverride = !($rawElectricity === '' || $rawElectricity === null);
        $electricity = $isOverride ? round(floatval($rawElectricity), 2) : $derived;
        if ($electricity < 0) {
            throw new Exception('The electricity consumption cannot be negative.', 31);
        }
        $chilled = round(floatval($columns['chilledWaterKwh'] ?? 0), 2);
        if ($chilled < 0) {
            throw new Exception('The chilled water consumption cannot be negative.', 31);
        }
        $floorArea = isset($columns['floorAreaSqm']) && $columns['floorAreaSqm'] !== ''
            ? round(floatval($columns['floorAreaSqm']), 2)
            : $config['floorAreaSqm'];
        $target = isset($columns['targetBei']) && $columns['targetBei'] !== ''
            ? round(floatval($columns['targetBei']), 4)
            : $config['targetBei'];
        $factor = isset($columns['annualiseFactor']) && $columns['annualiseFactor'] !== ''
            ? round(floatval($columns['annualiseFactor']), 4)
            : $config['annualiseFactor'];
        if ($factor <= 0) {
            $factor = 1.0;
        }

        $calc = new EnergyCalculator();
        $result = $calc->bei($electricity, $chilled, $floorArea, $target, $factor);

        $data = array(
            'site_id' => $siteId,
            'bei_year' => $year,
            'bei_month' => $month,
            'electricity_kwh' => $electricity,
            'electricity_is_override' => $isOverride ? 1 : 0,
            'chilled_water_kwh' => $chilled,
            'floor_area_sqm' => $floorArea,
            'target_bei' => $target,
            'annualise_factor' => $factor,
            'total_kwh' => $result['totalKwh'],
            'actual_bei' => $result['actualBei'] === null ? 'NULL' : $result['actualBei'],
            'result_pct' => $result['resultPct'] === null ? 'NULL' : $result['resultPct'],
            'remarks' => $this->sanitizeText($columns['remarks'] ?? '', 500),
            'bei_status' => self::STATUS_DRAFT
        );
        if (!empty($existing)) {
            unset($data['site_id'], $data['bei_year'], $data['bei_month']);
            $data['bei_updated_by'] = $this->userId;
            DbMysql::update('enr_monthly_bei', $data, array('bei_id' => intval($existing['beiId'])));
        } else {
            $data['bei_created_by'] = $this->userId;
            DbMysql::insert('enr_monthly_bei', $data);
        }
        if (empty($columns['autosave'])) {
            $this->saveAudit(self::AUDIT_BEI, 'Saved BEI for ' . $this->monthName($month) . ' ' . $year);
        }
        return $this->list(array('siteId' => $siteId, 'year' => $year));
    }

    public function finalise(int $beiId): array {
        $this->requireRecord();
        $existing = DbMysql::select('enr_monthly_bei', array('beiId' => $beiId), true);
        $this->assertSiteAccess(intval($existing['siteId']));
        if ($existing['actualBei'] === null) {
            throw new Exception('The BEI cannot be finalised until it produces a value. Check the floor area and consumption.', 31);
        }
        DbMysql::update('enr_monthly_bei', array(
            'bei_status' => self::STATUS_FINAL,
            'bei_updated_by' => $this->userId
        ), array('bei_id' => $beiId));
        $this->saveAudit(self::AUDIT_BEI_FINAL, 'Finalised BEI ' . $beiId);
        return $this->list(array('siteId' => intval($existing['siteId']), 'year' => intval($existing['beiYear'])));
    }

    public function reopen(int $beiId): array {
        $this->requireSetup();
        $existing = DbMysql::select('enr_monthly_bei', array('beiId' => $beiId), true);
        $this->assertSiteAccess(intval($existing['siteId']));
        DbMysql::update('enr_monthly_bei', array(
            'bei_status' => self::STATUS_DRAFT,
            'bei_updated_by' => $this->userId
        ), array('bei_id' => $beiId));
        $this->saveAudit(self::AUDIT_BEI, 'Reopened BEI ' . $beiId);
        return $this->list(array('siteId' => intval($existing['siteId']), 'year' => intval($existing['beiYear'])));
    }
}
