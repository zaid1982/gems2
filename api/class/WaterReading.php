<?php

/**
 * Water meters, cumulative readings and the daily / monthly views.
 *
 * Consumption uses EnergyCalculator: a reading entered today closes yesterday,
 * and a missed morning is spread evenly across the days that later reading
 * closes. The later date stays blank until the next reading.
 */
class WaterBase extends EnergyBase {

    public const AUDIT_WTR_METER = 262;
    public const AUDIT_WTR_READING = 263;
    public const AUDIT_WTR_READING_DELETE = 264;
    public const AUDIT_WTR_TARIFF = 265;
    public const AUDIT_WTR_BILL = 266;

    public function requireSetup(): void {
        if (!$this->canSetup()) {
            throw new Exception('You are not allowed to change the water configuration.', 31);
        }
    }

    public function requireView(): void {
        if (!$this->canView()) {
            throw new Exception('You are not allowed to view water data.', 31);
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
            throw new Exception('The site and its water records are unavailable.', 31);
        }
        return $own;
    }

    public function assertSiteAccess(int $siteId): void {
        if ($this->isAdministrator() || $this->hasAnyRole(array(self::ROLE_KPI_ADMIN))) {
            return;
        }
        if (intval($this->userSite) !== $siteId) {
            throw new Exception('The site and its water records are unavailable.', 31);
        }
    }
}

class WaterReading extends WaterBase {

    public function meters(?int $siteId = null, bool $activeOnly = false): array {
        $this->requireView();
        $siteId = $this->resolveSiteId($siteId);
        $this->assertSiteAccess($siteId);
        return $this->listMeters($siteId, $activeOnly);
    }

    public function listMeters(int $siteId, bool $activeOnly = true): array {
        $sql = "SELECT meter_id, site_id, meter_name, meter_desc, sort_order, meter_status
                FROM wtr_meter WHERE site_id = :siteId";
        if ($activeOnly) {
            $sql .= " AND meter_status = 1";
        }
        $sql .= " ORDER BY sort_order, meter_id";
        return $this->queryAll($sql, array('siteId' => $siteId));
    }

    public function saveMeter(array $columns, ?int $meterId = null): array {
        $this->requireSetup();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $name = $this->sanitizeText($columns['meterName'] ?? '', 100);
        if ($name === '') {
            throw new Exception('Enter the meter name.', 31);
        }
        $data = array(
            'site_id' => $siteId,
            'meter_name' => $name,
            'meter_desc' => $this->sanitizeText($columns['meterDesc'] ?? '', 200),
            'sort_order' => intval($columns['sortOrder'] ?? 1) ?: 1,
            'meter_status' => isset($columns['meterStatus']) ? intval($columns['meterStatus']) : 1
        );
        if ($meterId) {
            $existing = DbMysql::select('wtr_meter', array('meterId' => $meterId), true);
            $this->assertSiteAccess(intval($existing['siteId']));
            unset($data['site_id']);
            $data['meter_updated_by'] = $this->userId;
            DbMysql::update('wtr_meter', $data, array('meter_id' => $meterId));
        } else {
            $dup = $this->queryOne(
                "SELECT meter_id FROM wtr_meter WHERE site_id = :siteId AND meter_name = :name",
                array('siteId' => $siteId, 'name' => $name)
            );
            if (!empty($dup)) {
                throw new Exception('A meter named "' . $name . '" already exists at this site.', 31);
            }
            $data['meter_created_by'] = $this->userId;
            $meterId = DbMysql::insert('wtr_meter', $data);
        }
        $this->saveAudit(self::AUDIT_WTR_METER, 'Updated water meter ' . $name);
        return DbMysql::select('wtr_meter', array('meterId' => $meterId), true);
    }

    public function deactivateMeter(int $meterId): void {
        $this->requireSetup();
        $existing = DbMysql::select('wtr_meter', array('meterId' => $meterId), true);
        $this->assertSiteAccess(intval($existing['siteId']));
        DbMysql::update('wtr_meter', array(
            'meter_status' => 2,
            'meter_updated_by' => $this->userId
        ), array('meter_id' => $meterId));
        $this->saveAudit(self::AUDIT_WTR_METER, 'Deactivated water meter ' . $existing['meterName']);
    }

    /**
     * Readings inside the range, plus the cumulative reading immediately
     * before and after it. An IoT-only row is not used as that boundary,
     * because it does not move the register.
     *
     * @return array [meterId => rows ordered by reading_date]
     */
    private function loadReadings(array $meters, string $from, string $to): array {
        $readings = array();
        $params = array('fromDate' => $from, 'fromDate2' => $from, 'toDate' => $to, 'toDate2' => $to);
        $inRange = array();
        $inBefore = array();
        $inAfter = array();
        foreach ($meters as $i => $meter) {
            $meterId = intval($meter['meterId']);
            $readings[$meterId] = array();
            $inRange[] = ':ra' . $i;
            $inBefore[] = ':rb' . $i;
            $inAfter[] = ':rc' . $i;
            $params['ra' . $i] = $meterId;
            $params['rb' . $i] = $meterId;
            $params['rc' . $i] = $meterId;
        }
        if (empty($inRange)) {
            return $readings;
        }
        $columns = "reading_id, meter_id, reading_date, cumulative_volume, iot_volume, remark";
        $rows = $this->queryAll(
            "SELECT $columns FROM wtr_reading
             WHERE meter_id IN (" . implode(',', $inRange) . ")
               AND reading_date BETWEEN :fromDate AND :toDate
             UNION
             SELECT $columns FROM wtr_reading r
             WHERE r.meter_id IN (" . implode(',', $inBefore) . ")
               AND r.cumulative_volume IS NOT NULL
               AND r.reading_date = (
                 SELECT MAX(r2.reading_date) FROM wtr_reading r2
                 WHERE r2.meter_id = r.meter_id
                   AND r2.cumulative_volume IS NOT NULL
                   AND r2.reading_date < :fromDate2
             )
             UNION
             SELECT $columns FROM wtr_reading r
             WHERE r.meter_id IN (" . implode(',', $inAfter) . ")
               AND r.cumulative_volume IS NOT NULL
               AND r.reading_date = (
                 SELECT MIN(r3.reading_date) FROM wtr_reading r3
                 WHERE r3.meter_id = r.meter_id
                   AND r3.cumulative_volume IS NOT NULL
                   AND r3.reading_date > :toDate2
             )
             ORDER BY meter_id, reading_date",
            $params
        );
        foreach ($rows as $row) {
            $readings[intval($row['meterId'])][] = $row;
        }
        return $readings;
    }

    public function daily(array $filters): array {
        $this->requireView();
        $siteId = $this->resolveSiteId($filters['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $year = intval($filters['year'] ?? date('Y'));
        $month = intval($filters['month'] ?? date('n'));
        $this->checkPeriod($year, $month);

        $meters = $this->listMeters($siteId, true);
        $first = sprintf('%04d-%02d-01', $year, $month);
        $last = date('Y-m-t', strtotime($first));
        $readings = $this->loadReadings($meters, $first, $last);

        $calc = new EnergyCalculator();
        $billing = new WaterBilling();
        $prepared = $calc->prepare($meters, $readings);
        $grid = $this->assembleMonth($year, $month, $meters, $prepared, $billing, array(), null, false);
        $site = $this->getSiteRow($siteId);

        return array_merge($grid, array(
            'siteId' => $siteId,
            'siteName' => $site['siteName'] ?? '',
            'year' => $year,
            'month' => $month,
            'monthName' => $this->monthName($month),
            'periodLabel' => $this->monthName($month) . ' ' . $year,
            'meters' => $meters,
            'canRecord' => $this->canRecord(),
            'canSetup' => $this->canSetup(),
            'refreshedAt' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Upsert the cumulative register, the optional IoT consumption and the remark.
     * Clearing all three removes the row. Send every field: an omitted value is
     * stored as empty.
     */
    public function saveReading(array $columns): array {
        $this->requireRecord();
        $meterId = intval($columns['meterId'] ?? 0);
        if ($meterId <= 0) {
            throw new Exception('Select a water meter.', 31);
        }
        $meter = DbMysql::select('wtr_meter', array('meterId' => $meterId), true);
        $this->assertSiteAccess(intval($meter['siteId']));
        if (intval($meter['meterStatus']) !== 1) {
            throw new Exception('This meter is inactive.', 31);
        }
        $date = $this->normalizeDate($columns['readingDate'] ?? '');
        if (!$date) {
            throw new Exception('Enter the reading date.', 31);
        }
        if ($date > date('Y-m-d')) {
            throw new Exception('The reading date cannot be in the future.', 31);
        }
        $cumulative = $this->optionalNumber($columns['cumulativeVolume'] ?? '', 'cumulative meter reading');
        $iot = $this->optionalNumber($columns['iotVolume'] ?? '', 'IoT consumption');
        $remark = $this->sanitizeText($columns['remark'] ?? '', 300);
        $blank = $cumulative === 'NULL' && $iot === 'NULL' && $remark === '';

        $existing = $this->queryOne(
            "SELECT reading_id FROM wtr_reading WHERE meter_id = :meterId AND reading_date = :readingDate",
            array('meterId' => $meterId, 'readingDate' => $date)
        );
        if ($blank) {
            if (!empty($existing)) {
                DbMysql::delete('wtr_reading', array('reading_id' => intval($existing['readingId'])));
                if (empty($columns['autosave'])) {
                    $this->saveAudit(self::AUDIT_WTR_READING_DELETE, 'Removed water reading for ' . $meter['meterName'] . ' on ' . $date);
                }
            }
        } else if (!empty($existing)) {
            DbMysql::update('wtr_reading', array(
                'cumulative_volume' => $cumulative,
                'iot_volume' => $iot,
                'remark' => $remark,
                'reading_updated_by' => $this->userId
            ), array('reading_id' => intval($existing['readingId'])));
            if (empty($columns['autosave'])) {
                $this->saveAudit(self::AUDIT_WTR_READING, 'Saved water reading for ' . $meter['meterName'] . ' on ' . $date);
            }
        } else {
            DbMysql::insert('wtr_reading', array(
                'meter_id' => $meterId,
                'reading_date' => $date,
                'cumulative_volume' => $cumulative,
                'iot_volume' => $iot,
                'remark' => $remark,
                'reading_created_by' => $this->userId
            ));
            if (empty($columns['autosave'])) {
                $this->saveAudit(self::AUDIT_WTR_READING, 'Saved water reading for ' . $meter['meterName'] . ' on ' . $date);
            }
        }
        return $this->daily(array(
            'siteId' => intval($meter['siteId']),
            'year' => intval(date('Y', strtotime($date))),
            'month' => intval(date('n', strtotime($date)))
        ));
    }

    public function deleteReading(int $readingId): array {
        $this->requireRecord();
        $existing = DbMysql::select('wtr_reading', array('readingId' => $readingId), true);
        $meter = DbMysql::select('wtr_meter', array('meterId' => intval($existing['meterId'])), true);
        $this->assertSiteAccess(intval($meter['siteId']));
        DbMysql::delete('wtr_reading', array('reading_id' => $readingId));
        $this->saveAudit(self::AUDIT_WTR_READING_DELETE, 'Removed water reading ' . $readingId . ' for ' . $meter['meterName']);
        return $this->daily(array(
            'siteId' => intval($meter['siteId']),
            'year' => intval(date('Y', strtotime($existing['readingDate']))),
            'month' => intval(date('n', strtotime($existing['readingDate'])))
        ));
    }

    /**
     * Twelve months of consumption, the bill, and the change from the previous
     * month. January is compared with December of the previous year.
     */
    public function monthly(array $filters): array {
        $this->requireView();
        $siteId = $this->resolveSiteId($filters['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $year = intval($filters['year'] ?? date('Y'));
        if ($year < 2000 || $year > 2999) {
            throw new Exception('Select a valid year.', 31);
        }
        $meters = $this->listMeters($siteId, true);
        $from = sprintf('%04d-12-01', $year - 1);
        $to = sprintf('%04d-12-31', $year);
        $readings = $this->loadReadings($meters, $from, $to);

        $calc = new EnergyCalculator();
        $billing = new WaterBilling();
        $prepared = $calc->prepare($meters, $readings);
        $sets = $billing->setsForSite($siteId);
        $actuals = $billing->actualsForYear($siteId, $year);

        $previous = $this->assembleMonth($year - 1, 12, $meters, $prepared, $billing, $sets, null);
        $months = array();
        $yearVolume = 0.0;
        $yearRm = 0.0;
        $yearRmCount = 0;
        $meterYearTotals = array();
        foreach ($meters as $meter) {
            $meterYearTotals[intval($meter['meterId'])] = 0.0;
        }
        for ($month = 1; $month <= 12; $month++) {
            $actual = array_key_exists($month, $actuals) ? $actuals[$month] : null;
            $grid = $this->assembleMonth($year, $month, $meters, $prepared, $billing, $sets, $actual);
            $grid['volumeVariancePct'] = $billing->changePct(
                $grid['daysWithData'] > 0 ? $grid['totalVolume'] : null,
                $previous['daysWithData'] > 0 ? $previous['totalVolume'] : null
            );
            $grid['costVariancePct'] = $billing->changePct($grid['calculatedRm'], $previous['calculatedRm']);
            unset($grid['rows']);
            foreach ($grid['meterTotals'] as $total) {
                $meterYearTotals[intval($total['meterId'])] += floatval($total['totalVolume']);
            }
            $yearVolume += floatval($grid['totalVolume']);
            if ($grid['calculatedRm'] !== null) {
                $yearRm += floatval($grid['calculatedRm']);
                $yearRmCount++;
            }
            $months[] = $grid;
            $previous = $grid;
        }

        $meterTotals = array();
        foreach ($meters as $meter) {
            $meterId = intval($meter['meterId']);
            $meterTotals[] = array(
                'meterId' => $meterId,
                'meterName' => $meter['meterName'],
                'totalVolume' => round($meterYearTotals[$meterId], 2)
            );
        }
        $site = $this->getSiteRow($siteId);
        return array(
            'siteId' => $siteId,
            'siteName' => $site['siteName'] ?? '',
            'year' => $year,
            'meters' => $meters,
            'months' => $months,
            'meterTotals' => $meterTotals,
            'totalVolume' => round($yearVolume, 2),
            'totalRm' => $yearRmCount > 0 ? round($yearRm, 2) : null,
            'canRecord' => $this->canRecord(),
            'canSetup' => $this->canSetup(),
            'refreshedAt' => date('Y-m-d H:i:s')
        );
    }

    /**
     * One month of the workbook. Consumption stays at full precision until it
     * is rounded for the response. The bill uses that rounded total, so the
     * RM figure matches the m³ on screen. The daily grid passes $withBill false
     * so a missing tariff is not reported there.
     *
     * @param array|null $actualRm pass null when the invoice column is not needed
     */
    private function assembleMonth(int $year, int $month, array $meters, array $prepared, WaterBilling $billing, array $sets, $actualRm, bool $withBill = true): array {
        $first = sprintf('%04d-%02d-01', $year, $month);
        $last = date('Y-m-t', strtotime($first));
        $daysInMonth = intval(date('t', strtotime($first)));
        $perMeter = $prepared['perMeter'] ?? array();
        $perEstimated = $prepared['perMeterEstimated'] ?? array();
        $readingByMeterDate = $prepared['readingIndex'] ?? array();

        $issues = array();
        foreach ($prepared['issues'] ?? array() as $issue) {
            if ($issue['date'] >= $first && $issue['date'] <= $last) {
                $issues[] = $issue;
            }
        }

        $rows = array();
        $meterRaw = array();
        $totalRaw = 0.0;
        $daysWithData = 0;
        $estimatedDays = 0;
        $highest = null;
        $lowest = null;
        foreach ($meters as $meter) {
            $meterRaw[intval($meter['meterId'])] = 0.0;
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $cells = array();
            $dayTotal = 0.0;
            $hasAny = false;
            $dayEstimated = false;
            foreach ($meters as $meter) {
                $meterId = intval($meter['meterId']);
                $reading = $readingByMeterDate[$meterId][$date] ?? null;
                $consumption = $perMeter[$meterId][$date] ?? null;
                $estimated = !empty($perEstimated[$meterId][$date]);
                if ($consumption !== null) {
                    $dayTotal += $consumption;
                    $meterRaw[$meterId] += $consumption;
                    $hasAny = true;
                    if ($estimated) {
                        $dayEstimated = true;
                    }
                }
                $shown = $consumption === null ? null : round($consumption, 2);
                $iot = $reading && $reading['iotVolume'] !== null && $reading['iotVolume'] !== ''
                    ? floatval($reading['iotVolume']) : null;
                $cells[] = array(
                    'meterId' => $meterId,
                    'meterName' => $meter['meterName'],
                    'readingId' => $reading ? intval($reading['readingId']) : null,
                    'cumulativeVolume' => $reading && $reading['cumulativeVolume'] !== null && $reading['cumulativeVolume'] !== ''
                        ? floatval($reading['cumulativeVolume']) : null,
                    'consumptionVolume' => $shown,
                    'estimated' => $estimated && $consumption !== null,
                    'iotVolume' => $iot,
                    'variancePct' => $billing->variancePct($shown, $iot),
                    'remark' => $reading ? strval($reading['remark'] ?? '') : ''
                );
            }
            if ($hasAny) {
                $daysWithData++;
                $totalRaw += $dayTotal;
                if ($highest === null || $dayTotal > $highest) {
                    $highest = $dayTotal;
                }
                if ($lowest === null || $dayTotal < $lowest) {
                    $lowest = $dayTotal;
                }
            }
            if ($dayEstimated) {
                $estimatedDays++;
            }
            $rows[] = array(
                'date' => $date,
                'day' => $day,
                'dayName' => date('D', strtotime($date)),
                'meters' => $cells,
                'totalVolume' => $hasAny ? round($dayTotal, 2) : null,
                'estimated' => $dayEstimated
            );
        }

        $totals = array();
        foreach ($meters as $meter) {
            $meterId = intval($meter['meterId']);
            $totals[] = array(
                'meterId' => $meterId,
                'meterName' => $meter['meterName'],
                'totalVolume' => round($meterRaw[$meterId], 2)
            );
        }

        $totalVolume = round($totalRaw, 2);
        $tiers = $withBill ? $billing->tiersForMonth($sets, $year, $month) : array();
        $tariffMissing = $withBill && $daysWithData > 0 && empty($tiers);
        $calculated = null;
        $billMessage = null;
        if ($withBill && $daysWithData > 0 && !empty($tiers)) {
            $calculated = $billing->quote($totalVolume, $tiers);
        } else if ($tariffMissing) {
            $billMessage = 'No tariff is effective for this month.';
        }
        $actual = $actualRm === null || $actualRm === '' ? null : round(floatval($actualRm), 2);
        $difference = ($actual !== null && $calculated !== null) ? round($actual - $calculated, 2) : null;

        return array(
            'year' => $year,
            'month' => $month,
            'monthName' => $this->monthName($month),
            'periodLabel' => substr($this->monthName($month), 0, 3) . ' ' . $year,
            'rows' => $rows,
            'meterTotals' => $totals,
            'totalVolume' => $totalVolume,
            'averageDaily' => $daysWithData > 0 ? round($totalRaw / $daysWithData, 2) : 0.0,
            'highestDaily' => $highest === null ? null : round($highest, 2),
            'lowestDaily' => $lowest === null ? null : round($lowest, 2),
            'daysWithData' => $daysWithData,
            'estimatedDays' => $estimatedDays,
            'daysInMonth' => $daysInMonth,
            'billDays' => $daysInMonth,
            'calculatedRm' => $calculated,
            'tariffMissing' => $tariffMissing,
            'billMessage' => $billMessage,
            'actualRm' => $actual,
            'differenceRm' => $difference,
            'issues' => $issues
        );
    }

    /**
     * @return string a decimal, or the sentinel 'NULL' which DbMysql stores as SQL NULL
     */
    private function optionalNumber($raw, string $label): string {
        if ($raw === null || $raw === '') {
            return 'NULL';
        }
        if (!is_numeric($raw)) {
            throw new Exception('Enter a valid ' . $label . '.', 31);
        }
        $number = round(floatval($raw), 2);
        if ($number < 0) {
            throw new Exception('The ' . $label . ' cannot be negative.', 31);
        }
        return number_format($number, 2, '.', '');
    }
}
