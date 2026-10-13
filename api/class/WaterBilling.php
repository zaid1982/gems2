<?php

require_once __DIR__ . '/EnergyBase.php';
require_once __DIR__ . '/WaterReading.php';

/**
 * Water tariff and the optional monthly invoice.
 *
 * The calculated bill is not stored. A month uses the latest tariff whose
 * effective date is on or before the first day of that month. Tiers are a
 * monthly block on the whole month's consumption: the first 35 m³ at the
 * first rate, and everything above that at the next rate.
 */
class WaterBilling extends WaterBase {

    /**
     * (manual − IoT) / IoT × 100. Blank or zero IoT has no variance.
     * No status band is applied; the percentage is the result.
     */
    public function variancePct($manual, $iot): ?float {
        if ($manual === null || $manual === '') {
            return null;
        }
        if ($iot === null || $iot === '') {
            return null;
        }
        $iotVal = floatval($iot);
        if (abs($iotVal) < 0.0000001) {
            return null;
        }
        return round(((floatval($manual) - $iotVal) / $iotVal) * 100, 2);
    }

    /**
     * Bill a monthly volume against an ordered tier set.
     * An empty set means the tariff has not been configured.
     *
     * @param array $tiers rows of {fromVolume, toVolume|null, rateRm}
     */
    public function quote(float $volume, array $tiers): ?float {
        if (empty($tiers)) {
            return null;
        }
        if ($volume < 0) {
            $volume = 0.0;
        }
        $remaining = $volume;
        $amount = 0.0;
        foreach ($tiers as $tier) {
            $from = floatval($tier['fromVolume'] ?? 0);
            $to = $tier['toVolume'] ?? null;
            $rate = floatval($tier['rateRm'] ?? 0);
            if ($to === null || $to === '') {
                $take = $remaining;
            } else {
                $width = floatval($to) - $from;
                $take = min($remaining, $width > 0 ? $width : 0.0);
            }
            if ($take > 0) {
                $amount += $take * $rate;
                $remaining -= $take;
            }
            if ($remaining <= 0.0000001) {
                break;
            }
        }
        return round($amount, 2);
    }

    /**
     * Latest set whose effective date is on or before the first of the month.
     *
     * @param array $sets output of setsForSite()
     */
    public function tiersForMonth(array $sets, int $year, int $month): array {
        $first = sprintf('%04d-%02d-01', $year, $month);
        $best = array();
        $bestDate = '';
        foreach ($sets as $set) {
            $from = strval($set['effectiveFrom'] ?? '');
            if ($from !== '' && $from <= $first && $from >= $bestDate) {
                $best = $set['tiers'] ?? array();
                $bestDate = $from;
            }
        }
        return $best;
    }

    public function listTariff(array $filters): array {
        $this->requireView();
        $siteId = $this->resolveSiteId($filters['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $site = $this->getSiteRow($siteId);
        return array(
            'siteId' => $siteId,
            'siteName' => $site['siteName'] ?? '',
            'sets' => $this->setsForSite($siteId),
            'canSetup' => $this->canSetup()
        );
    }

    public function setsForSite(int $siteId): array {
        $rows = $this->queryAll(
            "SELECT tariff_id, effective_from, tier_no, from_volume, to_volume, rate_rm
             FROM wtr_tariff
             WHERE site_id = :siteId
             ORDER BY effective_from, tier_no",
            array('siteId' => $siteId)
        );
        $sets = array();
        foreach ($rows as $row) {
            $key = substr(strval($row['effectiveFrom']), 0, 10);
            if (!isset($sets[$key])) {
                $sets[$key] = array('effectiveFrom' => $key, 'tiers' => array());
            }
            $sets[$key]['tiers'][] = array(
                'tariffId' => intval($row['tariffId']),
                'tierNo' => intval($row['tierNo']),
                'fromVolume' => floatval($row['fromVolume']),
                'toVolume' => $row['toVolume'] === null || $row['toVolume'] === '' ? null : floatval($row['toVolume']),
                'rateRm' => floatval($row['rateRm'])
            );
        }
        return array_values($sets);
    }

    /**
     * Replace every tier for one effective date. previousEffectiveFrom drops
     * the old date when the user renames the set.
     */
    public function saveTariff(array $columns): array {
        $this->requireSetup();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $date = $this->normalizeDate($columns['effectiveFrom'] ?? '');
        if (!$date) {
            throw new Exception('Enter the date this tariff takes effect.', 31);
        }
        $clean = $this->normalizeTiers($columns['tiers'] ?? array());
        $previous = $this->normalizeDate($columns['previousEffectiveFrom'] ?? '');

        DbMysql::delete('wtr_tariff', array('site_id' => $siteId, 'effective_from' => $date));
        foreach ($clean as $i => $tier) {
            DbMysql::insert('wtr_tariff', array(
                'site_id' => $siteId,
                'effective_from' => $date,
                'tier_no' => $i + 1,
                'from_volume' => $this->decimal($tier['from'], 2),
                'to_volume' => $tier['to'] === null ? 'NULL' : $this->decimal($tier['to'], 2),
                'rate_rm' => $this->decimal($tier['rate'], 4),
                'tariff_created_by' => $this->userId
            ));
        }
        if ($previous && $previous !== $date) {
            DbMysql::delete('wtr_tariff', array('site_id' => $siteId, 'effective_from' => $previous));
        }
        $this->saveAudit(self::AUDIT_WTR_TARIFF, 'Saved water tariff effective ' . $date);
        return $this->listTariff(array('siteId' => $siteId));
    }

    public function deleteTariff(array $columns): array {
        $this->requireSetup();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $date = $this->normalizeDate($columns['effectiveFrom'] ?? '');
        if (!$date) {
            throw new Exception('Select the tariff to remove.', 31);
        }
        DbMysql::delete('wtr_tariff', array('site_id' => $siteId, 'effective_from' => $date));
        $this->saveAudit(self::AUDIT_WTR_TARIFF, 'Removed water tariff effective ' . $date);
        return $this->listTariff(array('siteId' => $siteId));
    }

    /**
     * @return array [month => actualRm] for one year
     */
    public function actualsForYear(int $siteId, int $year): array {
        $map = array();
        foreach ($this->queryAll(
            "SELECT bill_month, actual_rm FROM wtr_bill_actual
             WHERE site_id = :siteId AND bill_year = :billYear",
            array('siteId' => $siteId, 'billYear' => $year)
        ) as $row) {
            $map[intval($row['billMonth'])] = round(floatval($row['actualRm']), 2);
        }
        return $map;
    }

    /**
     * Store or clear the utility invoice for one month.
     * Returns the site and year so the caller can reload the summary.
     */
    public function saveActual(array $columns): array {
        if (!$this->canRecord() && !$this->canSetup()) {
            throw new Exception('You are not allowed to record the water invoice.', 31);
        }
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $year = intval($columns['year'] ?? 0);
        $month = intval($columns['month'] ?? 0);
        $this->checkPeriod($year, $month);
        $raw = $columns['actualRm'] ?? '';
        $existing = $this->queryOne(
            "SELECT bill_id FROM wtr_bill_actual
             WHERE site_id = :siteId AND bill_year = :billYear AND bill_month = :billMonth",
            array('siteId' => $siteId, 'billYear' => $year, 'billMonth' => $month)
        );
        if ($raw === '' || $raw === null) {
            if (!empty($existing)) {
                DbMysql::delete('wtr_bill_actual', array('bill_id' => intval($existing['billId'])));
            }
        } else {
            if (!is_numeric($raw)) {
                throw new Exception('Enter a valid invoice amount.', 31);
            }
            $amount = round(floatval($raw), 2);
            if ($amount < 0) {
                throw new Exception('The invoice amount cannot be negative.', 31);
            }
            $stored = $this->decimal($amount, 2);
            if (!empty($existing)) {
                DbMysql::update('wtr_bill_actual', array(
                    'actual_rm' => $stored,
                    'bill_updated_by' => $this->userId
                ), array('bill_id' => intval($existing['billId'])));
            } else {
                DbMysql::insert('wtr_bill_actual', array(
                    'site_id' => $siteId,
                    'bill_year' => $year,
                    'bill_month' => $month,
                    'actual_rm' => $stored,
                    'bill_updated_by' => $this->userId
                ));
            }
        }
        $this->saveAudit(self::AUDIT_WTR_BILL, 'Saved water invoice for ' . $year . '-' . $month);
        return array('siteId' => $siteId, 'year' => $year);
    }

    /**
     * Percentage change. Zero or missing previous value has no percentage.
     */
    public function changePct($current, $previous): ?float {
        if ($current === null || $previous === null) {
            return null;
        }
        $prev = floatval($previous);
        if (abs($prev) < 0.0000001) {
            return null;
        }
        return round(((floatval($current) - $prev) / $prev) * 100, 2);
    }

    /**
     * @param array $tiers client rows
     * @return array list of {from, to|null, rate} in band order
     */
    private function normalizeTiers($tiers): array {
        if (!is_array($tiers) || count($tiers) < 1) {
            throw new Exception('Add at least one tariff tier.', 31);
        }
        if (count($tiers) > 12) {
            throw new Exception('A tariff can have at most 12 tiers.', 31);
        }
        $clean = array();
        foreach ($tiers as $tier) {
            if (!is_array($tier)) {
                throw new Exception('Enter a from, to and rate for every tier.', 31);
            }
            $fromRaw = $tier['fromVolume'] ?? '';
            if ($fromRaw === '' || $fromRaw === null || !is_numeric($fromRaw)) {
                throw new Exception('Enter the start of every tier.', 31);
            }
            $from = round(floatval($fromRaw), 2);
            if ($from < 0) {
                throw new Exception('A tier cannot start below zero.', 31);
            }
            $toRaw = $tier['toVolume'] ?? null;
            $open = $toRaw === null || $toRaw === '';
            $to = null;
            if (!$open) {
                if (!is_numeric($toRaw)) {
                    throw new Exception('Enter a valid upper bound, or leave the last tier open.', 31);
                }
                $to = round(floatval($toRaw), 2);
                if ($to <= $from) {
                    throw new Exception('Each tier\'s upper bound must be above its lower bound.', 31);
                }
            }
            $rateRaw = $tier['rateRm'] ?? '';
            if ($rateRaw === '' || $rateRaw === null || !is_numeric($rateRaw)) {
                throw new Exception('Enter a rate for every tier.', 31);
            }
            $rate = round(floatval($rateRaw), 4);
            if ($rate < 0) {
                throw new Exception('A tariff rate cannot be negative.', 31);
            }
            $clean[] = array('from' => $from, 'to' => $to, 'rate' => $rate);
        }
        usort($clean, function ($a, $b) {
            if ($a['from'] == $b['from']) {
                return 0;
            }
            return $a['from'] < $b['from'] ? -1 : 1;
        });
        if (abs($clean[0]['from']) > 0.001) {
            throw new Exception('The first tier has to start at 0.', 31);
        }
        $last = count($clean) - 1;
        for ($i = 0; $i <= $last; $i++) {
            if ($i === $last) {
                if ($clean[$i]['to'] !== null) {
                    throw new Exception('Leave the last tier\'s upper bound empty so usage above it is still billed.', 31);
                }
                continue;
            }
            if ($clean[$i]['to'] === null) {
                throw new Exception('Only the last tier can be open-ended.', 31);
            }
            if (abs($clean[$i + 1]['from'] - $clean[$i]['to']) > 0.001) {
                throw new Exception('Tiers must meet without a gap or an overlap. The next tier has to start where the previous one ends.', 31);
            }
        }
        return $clean;
    }

    private function decimal(float $value, int $scale): string {
        return number_format($value, $scale, '.', '');
    }
}
