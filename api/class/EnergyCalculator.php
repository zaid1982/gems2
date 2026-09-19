<?php

/**
 * Daily electricity consumption from cumulative meter readings.
 *
 * Readings are not taken every day. When a reading is missed the next reading
 * covers the whole gap, so the difference is spread evenly across the days it
 * covers. Example: a reading on day 16 followed by the next on day 18 gives
 *
 *     perDay = (kWh_18 - kWh_16) / 2
 *
 * and both day 17 and day 18 are credited with perDay. Days before the first
 * reading and after the last reading of the month stay blank rather than being
 * reported as zero consumption.
 *
 * A reading lower than the one before it (meter replacement or a typo) cannot
 * be spread, so those days are left blank and flagged for the UI.
 */
class EnergyCalculator {

    /**
     * @param array $readings rows of {readingDate, cumulativeKwh}, any order
     * @return array {consumption: [date => kwh], issues: [...]}
     */
    public function distribute(array $readings): array {
        $points = array();
        foreach ($readings as $row) {
            $date = strval($row['readingDate'] ?? '');
            if ($date === '') {
                continue;
            }
            $points[$date] = floatval($row['cumulativeKwh'] ?? 0);
        }
        ksort($points);

        $consumption = array();
        $issues = array();
        $prevDate = null;
        $prevValue = null;
        foreach ($points as $date => $value) {
            if ($prevDate !== null) {
                $gapDays = $this->dayDiff($prevDate, $date);
                $delta = $value - $prevValue;
                if ($gapDays <= 0) {
                    // Duplicate date; the unique key should prevent this.
                    $issues[] = array(
                        'date' => $date,
                        'type' => 'DUPLICATE',
                        'message' => 'More than one reading exists for ' . $date . '.'
                    );
                } else if ($delta < 0) {
                    $issues[] = array(
                        'date' => $date,
                        'type' => 'NEGATIVE',
                        'message' => 'The reading on ' . $date . ' is lower than the reading on ' . $prevDate
                            . '. Check for a meter replacement or a typing error.'
                    );
                } else {
                    // Kept at full precision so the monthly and yearly totals
                    // add back up to the meter difference exactly. Rounding
                    // happens only where a value is emitted.
                    $perDay = $delta / $gapDays;
                    for ($i = 1; $i <= $gapDays; $i++) {
                        $day = date('Y-m-d', strtotime($prevDate . ' +' . $i . ' day'));
                        $consumption[$day] = $perDay;
                    }
                }
            }
            $prevDate = $date;
            $prevValue = $value;
        }

        return array('consumption' => $consumption, 'issues' => $issues);
    }

    /**
     * Distribute every meter's readings once, so a caller that needs several
     * months (the monthly summary, the BEI grid) can load a whole year in one
     * query and then assemble each month in memory.
     *
     * @param array $meters   active meters in display order
     * @param array $readings all readings keyed by meterId, including the last
     *                        reading before the range so its first day can be derived
     * @return array {perMeter, readingIndex, issues} for buildMonth()
     */
    public function prepare(array $meters, array $readings): array {
        $perMeter = array();
        $issues = array();
        foreach ($meters as $meter) {
            $meterId = intval($meter['meterId']);
            $result = $this->distribute($readings[$meterId] ?? array());
            $perMeter[$meterId] = $result['consumption'];
            foreach ($result['issues'] as $issue) {
                $issue['meterId'] = $meterId;
                $issue['meterName'] = $meter['meterName'];
                $issues[] = $issue;
            }
        }

        $readingIndex = array();
        foreach ($readings as $meterId => $rows) {
            foreach ($rows as $row) {
                $readingIndex[$meterId][strval($row['readingDate'])] = $row;
            }
        }

        return array('perMeter' => $perMeter, 'readingIndex' => $readingIndex, 'issues' => $issues);
    }

    /**
     * Build the workbook-style grid for one month.
     *
     * @param array $readings all readings keyed by meterId, including the last
     *                        reading before the month so day 1 can be derived
     * @param array $notes    chiller hours / remark keyed by date
     * @return array {rows, meterTotals, totalKwh, averageDaily, daysWithData, issues}
     */
    public function monthGrid(int $year, int $month, array $meters, array $readings, array $notes = array()): array {
        return $this->buildMonth($year, $month, $meters, $this->prepare($meters, $readings), $notes);
    }

    /**
     * Assemble one month from already-distributed data. No queries, no maths
     * beyond summing, so calling it twelve times is cheap.
     *
     * @param array $prepared output of prepare()
     */
    public function buildMonth(int $year, int $month, array $meters, array $prepared, array $notes = array()): array {
        $first = sprintf('%04d-%02d-01', $year, $month);
        $last = date('Y-m-t', strtotime($first));
        $daysInMonth = intval(date('t', strtotime($first)));

        $perMeter = $prepared['perMeter'] ?? array();
        $readingByMeterDate = $prepared['readingIndex'] ?? array();

        // Only surface problems that land inside the month on screen.
        $issues = array();
        foreach ($prepared['issues'] ?? array() as $issue) {
            if ($issue['date'] >= $first && $issue['date'] <= $last) {
                $issues[] = $issue;
            }
        }

        $rows = array();
        $meterTotals = array();
        $totalKwh = 0.0;
        $daysWithData = 0;
        foreach ($meters as $meter) {
            $meterTotals[intval($meter['meterId'])] = 0.0;
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $cells = array();
            $dayTotal = 0.0;
            $hasAny = false;
            foreach ($meters as $meter) {
                $meterId = intval($meter['meterId']);
                $reading = $readingByMeterDate[$meterId][$date] ?? null;
                $consumption = $perMeter[$meterId][$date] ?? null;
                if ($consumption !== null) {
                    $dayTotal += $consumption;
                    $meterTotals[$meterId] += $consumption;
                    $hasAny = true;
                }
                $cells[] = array(
                    'meterId' => $meterId,
                    'meterName' => $meter['meterName'],
                    'readingId' => $reading ? intval($reading['readingId']) : null,
                    'cumulativeKwh' => $reading ? floatval($reading['cumulativeKwh']) : null,
                    'maxDemandKw' => $reading && $reading['maxDemandKw'] !== null ? floatval($reading['maxDemandKw']) : null,
                    'remark' => $reading ? strval($reading['remark'] ?? '') : '',
                    'consumptionKwh' => $consumption === null ? null : round($consumption, 2)
                );
            }
            if ($hasAny) {
                $daysWithData++;
                $totalKwh += $dayTotal;
            }
            $note = $notes[$date] ?? null;
            $rows[] = array(
                'date' => $date,
                'day' => $day,
                'dayName' => date('D', strtotime($date)),
                'meters' => $cells,
                'totalKwh' => $hasAny ? round($dayTotal, 2) : null,
                'chillerRunningHours' => $note && $note['chillerRunningHours'] !== null ? floatval($note['chillerRunningHours']) : null,
                'remark' => $note ? strval($note['remark'] ?? '') : ''
            );
        }

        $totals = array();
        foreach ($meters as $meter) {
            $meterId = intval($meter['meterId']);
            $totals[] = array(
                'meterId' => $meterId,
                'meterName' => $meter['meterName'],
                'totalKwh' => round($meterTotals[$meterId], 2)
            );
        }

        return array(
            'rows' => $rows,
            'meterTotals' => $totals,
            'totalKwh' => round($totalKwh, 2),
            'averageDaily' => $daysWithData > 0 ? round($totalKwh / $daysWithData, 2) : 0.0,
            'daysWithData' => $daysWithData,
            'daysInMonth' => $daysInMonth,
            'issues' => $issues
        );
    }

    /**
     * BEI = total energy / gross floor area, optionally annualised.
     * Passing is actual BEI at or below the target.
     */
    public function bei(float $electricityKwh, float $chilledWaterKwh, float $floorAreaSqm, float $targetBei, float $annualiseFactor = 1.0): array {
        $totalKwh = round($electricityKwh + $chilledWaterKwh, 2);
        if ($floorAreaSqm <= 0) {
            return array(
                'totalKwh' => $totalKwh,
                'actualBei' => null,
                'resultPct' => null,
                'isPass' => null,
                'message' => 'Enter a gross floor area greater than zero in the site configuration.'
            );
        }
        $factor = $annualiseFactor > 0 ? $annualiseFactor : 1.0;
        $actual = round(($totalKwh / $floorAreaSqm) * $factor, 4);
        if ($targetBei <= 0) {
            return array(
                'totalKwh' => $totalKwh,
                'actualBei' => $actual,
                'resultPct' => null,
                'isPass' => null,
                'message' => 'Set a target BEI in the site configuration to score this month.'
            );
        }
        $isPass = $actual <= $targetBei + 0.00005;
        return array(
            'totalKwh' => $totalKwh,
            'actualBei' => $actual,
            'resultPct' => $isPass ? 100.0 : 0.0,
            'isPass' => $isPass,
            'message' => null
        );
    }

    private function dayDiff(string $from, string $to): int {
        $a = new DateTime($from);
        $b = new DateTime($to);
        return intval($a->diff($b)->days) * ($b < $a ? -1 : 1);
    }
}
