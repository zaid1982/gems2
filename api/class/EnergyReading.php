<?php

/**
 * Incoming meter setup, cumulative reading capture and the daily / monthly
 * consumption views.
 */
class EnergyReading extends EnergyBase {

    // -----------------------------------------------------------------------
    // Meters
    // -----------------------------------------------------------------------

    public function meters(?int $siteId = null, bool $activeOnly = false): array {
        $this->requireView();
        $siteId = $this->resolveSiteId($siteId);
        $this->assertSiteAccess($siteId);
        return $this->listMeters($siteId, $activeOnly);
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
            $existing = DbMysql::select('enr_meter', array('meterId' => $meterId), true);
            $this->assertSiteAccess(intval($existing['siteId']));
            unset($data['site_id']);
            $data['meter_updated_by'] = $this->userId;
            DbMysql::update('enr_meter', $data, array('meter_id' => $meterId));
        } else {
            $dup = $this->queryOne(
                "SELECT meter_id FROM enr_meter WHERE site_id = :siteId AND meter_name = :name",
                array('siteId' => $siteId, 'name' => $name)
            );
            if (!empty($dup)) {
                throw new Exception('A meter named "' . $name . '" already exists at this site.', 31);
            }
            $data['meter_created_by'] = $this->userId;
            $meterId = DbMysql::insert('enr_meter', $data);
        }
        $this->saveAudit(self::AUDIT_METER, 'Updated energy meter ' . $name);
        return DbMysql::select('enr_meter', array('meterId' => $meterId), true);
    }

    public function deactivateMeter(int $meterId): void {
        $this->requireSetup();
        $existing = DbMysql::select('enr_meter', array('meterId' => $meterId), true);
        $this->assertSiteAccess(intval($existing['siteId']));
        DbMysql::update('enr_meter', array(
            'meter_status' => 2,
            'meter_updated_by' => $this->userId
        ), array('meter_id' => $meterId));
        $this->saveAudit(self::AUDIT_METER, 'Deactivated energy meter ' . $existing['meterName']);
    }

    // -----------------------------------------------------------------------
    // Loaders
    // -----------------------------------------------------------------------

    /**
     * Every reading for every meter in the range, in one round trip, keyed by
     * meter id.
     *
     * The readings immediately before and after the range are included per
     * meter, because a reading outside the range can still describe days inside
     * it: the one before lets the first day be derived, and the one after
     * covers a gap that swallows the whole range. Without the trailing reading
     * a month sitting entirely inside a gap would look empty on the daily grid
     * while the yearly summary reported consumption for it.
     *
     * @return array [meterId => rows ordered by reading_date]
     */
    private function loadReadings(array $meters, string $from, string $to): array {
        $readings = array();
        // PDO runs with ATTR_EMULATE_PREPARES = false, which rejects a named
        // placeholder used more than once, so each UNION branch gets its own
        // set of names rather than sharing one IN list.
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
        $columns = "reading_id, meter_id, reading_date, cumulative_kwh, max_demand_kw, remark, image_upload_id";
        $rows = $this->queryAll(
            "SELECT $columns FROM enr_reading
             WHERE meter_id IN (" . implode(',', $inRange) . ")
               AND reading_date BETWEEN :fromDate AND :toDate
             UNION
             SELECT $columns FROM enr_reading r
             WHERE r.meter_id IN (" . implode(',', $inBefore) . ") AND r.reading_date = (
                 SELECT MAX(r2.reading_date) FROM enr_reading r2
                 WHERE r2.meter_id = r.meter_id AND r2.reading_date < :fromDate2
             )
             UNION
             SELECT $columns FROM enr_reading r
             WHERE r.meter_id IN (" . implode(',', $inAfter) . ") AND r.reading_date = (
                 SELECT MIN(r3.reading_date) FROM enr_reading r3
                 WHERE r3.meter_id = r.meter_id AND r3.reading_date > :toDate2
             )
             ORDER BY meter_id, reading_date",
            $params
        );
        foreach ($rows as $row) {
            $readings[intval($row['meterId'])][] = $row;
        }
        return $readings;
    }

    private function loadNotes(int $siteId, string $from, string $to): array {
        $notes = array();
        foreach ($this->queryAll(
            "SELECT note_date, chiller_running_hours, remark FROM enr_daily_note
             WHERE site_id = :siteId AND note_date BETWEEN :fromDate AND :toDate",
            array('siteId' => $siteId, 'fromDate' => $from, 'toDate' => $to)
        ) as $note) {
            $notes[strval($note['noteDate'])] = $note;
        }
        return $notes;
    }

    // -----------------------------------------------------------------------
    // Daily grid
    // -----------------------------------------------------------------------

    /**
     * Workbook-style grid for one month: a row per day with one cell group per
     * incoming meter, the day total, chiller hours and a remark.
     */
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
        $notes = $this->loadNotes($siteId, $first, $last);

        $calc = new EnergyCalculator();
        $grid = $calc->monthGrid($year, $month, $meters, $readings, $notes);
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
            'refreshedAt' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Upsert one cumulative reading.
     */
    public function saveReading(array $columns): array {
        $this->requireRecord();
        $meterId = intval($columns['meterId'] ?? 0);
        if ($meterId <= 0) {
            throw new Exception('Select an incoming meter.', 31);
        }
        $meter = DbMysql::select('enr_meter', array('meterId' => $meterId), true);
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
        $raw = $columns['cumulativeKwh'] ?? '';
        if ($raw === '' || $raw === null) {
            throw new Exception('Enter the cumulative meter reading.', 31);
        }
        $cumulative = round(floatval($raw), 2);
        if ($cumulative < 0) {
            throw new Exception('The cumulative meter reading cannot be negative.', 31);
        }
        $maxDemand = ($columns['maxDemandKw'] ?? '') === '' ? 'NULL' : round(floatval($columns['maxDemandKw']), 2);

        $existing = $this->queryOne(
            "SELECT reading_id FROM enr_reading WHERE meter_id = :meterId AND reading_date = :date",
            array('meterId' => $meterId, 'date' => $date)
        );
        if (!empty($existing)) {
            DbMysql::update('enr_reading', array(
                'cumulative_kwh' => $cumulative,
                'max_demand_kw' => $maxDemand,
                'remark' => $this->sanitizeText($columns['remark'] ?? '', 300),
                'reading_updated_by' => $this->userId
            ), array('reading_id' => intval($existing['readingId'])));
        } else {
            DbMysql::insert('enr_reading', array(
                'meter_id' => $meterId,
                'reading_date' => $date,
                'cumulative_kwh' => $cumulative,
                'max_demand_kw' => $maxDemand,
                'remark' => $this->sanitizeText($columns['remark'] ?? '', 300),
                'reading_created_by' => $this->userId
            ));
        }
        if (empty($columns['autosave'])) {
            $this->saveAudit(self::AUDIT_READING, 'Saved reading for ' . $meter['meterName'] . ' on ' . $date);
        }
        return $this->daily(array(
            'siteId' => intval($meter['siteId']),
            'year' => intval(date('Y', strtotime($date))),
            'month' => intval(date('n', strtotime($date)))
        ));
    }

    public function deleteReading(int $readingId): array {
        $this->requireRecord();
        $existing = DbMysql::select('enr_reading', array('readingId' => $readingId), true);
        $meter = DbMysql::select('enr_meter', array('meterId' => intval($existing['meterId'])), true);
        $this->assertSiteAccess(intval($meter['siteId']));
        DbMysql::delete('enr_reading', array('reading_id' => $readingId));
        $this->saveAudit(self::AUDIT_READING_DELETE, 'Removed reading ' . $readingId . ' for ' . $meter['meterName']);
        return $this->daily(array(
            'siteId' => intval($meter['siteId']),
            'year' => intval(date('Y', strtotime($existing['readingDate']))),
            'month' => intval(date('n', strtotime($existing['readingDate'])))
        ));
    }

    /**
     * Chiller running hours and the per-day remark.
     */
    public function saveDailyNote(array $columns): array {
        $this->requireRecord();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $date = $this->normalizeDate($columns['date'] ?? ($columns['noteDate'] ?? ''));
        if (!$date) {
            throw new Exception('Enter the date for this note.', 31);
        }
        $hours = ($columns['chillerRunningHours'] ?? '') === '' ? 'NULL' : round(floatval($columns['chillerRunningHours']), 2);
        $remark = $this->sanitizeText($columns['remark'] ?? '', 300);
        $existing = $this->queryOne(
            "SELECT note_date FROM enr_daily_note WHERE site_id = :siteId AND note_date = :date",
            array('siteId' => $siteId, 'date' => $date)
        );
        if (!empty($existing)) {
            DbMysql::update('enr_daily_note', array(
                'chiller_running_hours' => $hours,
                'remark' => $remark,
                'note_updated_by' => $this->userId
            ), array('site_id' => $siteId, 'note_date' => $date));
        } else {
            DbMysql::insert('enr_daily_note', array(
                'site_id' => $siteId,
                'note_date' => $date,
                'chiller_running_hours' => $hours,
                'remark' => $remark,
                'note_updated_by' => $this->userId
            ));
        }
        return $this->daily(array(
            'siteId' => $siteId,
            'year' => intval(date('Y', strtotime($date))),
            'month' => intval(date('n', strtotime($date)))
        ));
    }

    // -----------------------------------------------------------------------
    // Monthly summary
    // -----------------------------------------------------------------------

    /**
     * Twelve months of totals per meter plus the total and average daily.
     *
     * The whole year is loaded and distributed once, then each month is
     * assembled in memory, so this costs two queries rather than two per month.
     */
    public function monthly(array $filters): array {
        $this->requireView();
        $siteId = $this->resolveSiteId($filters['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $year = intval($filters['year'] ?? date('Y'));
        $meters = $this->listMeters($siteId, true);

        $yearFrom = sprintf('%04d-01-01', $year);
        $yearTo = sprintf('%04d-12-31', $year);
        $readings = $this->loadReadings($meters, $yearFrom, $yearTo);
        $notes = $this->loadNotes($siteId, $yearFrom, $yearTo);

        $calc = new EnergyCalculator();
        $prepared = $calc->prepare($meters, $readings);

        $months = array();
        $yearTotal = 0.0;
        $meterYearTotals = array();
        foreach ($meters as $meter) {
            $meterYearTotals[intval($meter['meterId'])] = 0.0;
        }
        for ($month = 1; $month <= 12; $month++) {
            $grid = $calc->buildMonth($year, $month, $meters, $prepared, $notes);
            $perMeter = array();
            foreach ($grid['meterTotals'] as $total) {
                $perMeter[] = $total;
                $meterYearTotals[intval($total['meterId'])] += floatval($total['totalKwh']);
            }
            $yearTotal += floatval($grid['totalKwh']);
            $months[] = array(
                'year' => $year,
                'month' => $month,
                'monthName' => $this->monthName($month),
                'periodLabel' => substr($this->monthName($month), 0, 3) . ' ' . $year,
                'meterTotals' => $perMeter,
                'totalKwh' => floatval($grid['totalKwh']),
                'averageDaily' => floatval($grid['averageDaily']),
                'daysWithData' => intval($grid['daysWithData']),
                'daysInMonth' => intval($grid['daysInMonth'])
            );
        }

        $meterTotals = array();
        foreach ($meters as $meter) {
            $meterId = intval($meter['meterId']);
            $meterTotals[] = array(
                'meterId' => $meterId,
                'meterName' => $meter['meterName'],
                'totalKwh' => round($meterYearTotals[$meterId], 2)
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
            'totalKwh' => round($yearTotal, 2),
            'refreshedAt' => date('Y-m-d H:i:s')
        );
    }

    /**
     * Total kWh for one month, used to prefill the BEI electricity figure.
     */
    public function monthTotalKwh(int $siteId, int $year, int $month): float {
        $this->checkPeriod($year, $month);
        $meters = $this->listMeters($siteId, true);
        $first = sprintf('%04d-%02d-01', $year, $month);
        $last = date('Y-m-t', strtotime($first));
        $calc = new EnergyCalculator();
        $grid = $calc->monthGrid($year, $month, $meters, $this->loadReadings($meters, $first, $last));
        return floatval($grid['totalKwh']);
    }

    /**
     * Total kWh for every month of a year, keyed by month number. Used by the
     * BEI grid so it does not have to derive each month separately.
     *
     * @return array [month => totalKwh]
     */
    public function yearTotalsByMonth(int $siteId, int $year): array {
        $meters = $this->listMeters($siteId, true);
        $readings = $this->loadReadings($meters, sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year));
        $calc = new EnergyCalculator();
        $prepared = $calc->prepare($meters, $readings);
        $totals = array();
        for ($month = 1; $month <= 12; $month++) {
            $totals[$month] = floatval($calc->buildMonth($year, $month, $meters, $prepared)['totalKwh']);
        }
        return $totals;
    }
}
