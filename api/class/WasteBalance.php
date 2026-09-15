<?php

class WasteBalance extends WasteBase {

    public function getOpeningKg(int $siteId, int $swCodeId, ?string $asAt = null): array {
        $sql = "SELECT opening_id, as_at_date, qty, unit, qty_kg, location_id, remarks
                FROM wst_opening_balance
                WHERE site_id = :siteId AND sw_code_id = :swCodeId AND opening_status = 1";
        $params = array('siteId' => $siteId, 'swCodeId' => $swCodeId);
        if ($asAt) {
            $sql .= " AND as_at_date <= :asAt";
            $params['asAt'] = $asAt;
        }
        $sql .= " ORDER BY as_at_date DESC LIMIT 1";
        $row = $this->queryOne($sql, $params);
        if (empty($row)) {
            return array('qtyKg' => 0.0, 'asAtDate' => null, 'openingId' => null);
        }
        return array(
            'qtyKg' => floatval($row['qtyKg']),
            'asAtDate' => $row['asAtDate'],
            'openingId' => intval($row['openingId'])
        );
    }

    public function getLedger(int $siteId, int $swCodeId, ?string $upToDate = null, ?int $excludeTxnId = null): array {
        $opening = $this->getOpeningKg($siteId, $swCodeId, $upToDate);
        $sql = "SELECT txn_id, txn_ref, txn_type, event_date, qty, unit, qty_kg, txn_status
                FROM wst_transaction
                WHERE site_id = :siteId AND sw_code_id = :swCodeId AND txn_status = 'FINAL'";
        $params = array('siteId' => $siteId, 'swCodeId' => $swCodeId);
        if ($upToDate) {
            $sql .= " AND event_date <= :upTo";
            $params['upTo'] = $upToDate;
        }
        if ($excludeTxnId) {
            $sql .= " AND txn_id <> :excludeId";
            $params['excludeId'] = $excludeTxnId;
        }
        if (!empty($opening['asAtDate'])) {
            $sql .= " AND event_date > :cutover";
            $params['cutover'] = $opening['asAtDate'];
        }
        $sql .= " ORDER BY event_date ASC, txn_id ASC";
        $txns = $this->queryAll($sql, $params);
        return array(
            'openingKg' => floatval($opening['qtyKg']),
            'openingAsAt' => $opening['asAtDate'],
            'transactions' => $txns
        );
    }

    public function runningBalance(int $siteId, int $swCodeId, ?string $asAt = null, ?int $excludeTxnId = null): float {
        $ledger = $this->getLedger($siteId, $swCodeId, $asAt, $excludeTxnId);
        $balance = floatval($ledger['openingKg']);
        foreach ($ledger['transactions'] as $txn) {
            $qty = floatval($txn['qtyKg']);
            if ($txn['txnType'] === 'P') {
                $balance += $qty;
            } else {
                $balance -= $qty;
            }
        }
        return round($balance, 3);
    }

    public function preview(int $siteId, int $swCodeId, string $eventDate, string $txnType, float $qtyKg, ?int $excludeTxnId = null): array {
        $this->assertSiteAccess($siteId);
        $before = $this->runningBalance($siteId, $swCodeId, $eventDate, $excludeTxnId);
        $after = $txnType === 'D' ? $before - $qtyKg : $before + $qtyKg;
        return array(
            'balanceBeforeKg' => round($before, 3),
            'balanceAfterKg' => round($after, 3),
            'balanceBeforeMt' => $this->formatMt($before),
            'balanceAfterMt' => $this->formatMt($after)
        );
    }

    public function validateChronological(int $siteId, int $swCodeId, array $candidate, ?int $excludeTxnId = null): void {
        $eventDate = $candidate['eventDate'];
        $qtyKg = floatval($candidate['qtyKg']);
        $type = $candidate['txnType'];
        $sql = "SELECT txn_id, txn_type, event_date, qty_kg
                FROM wst_transaction
                WHERE site_id = :siteId AND sw_code_id = :swCodeId AND txn_status = 'FINAL'";
        $params = array('siteId' => $siteId, 'swCodeId' => $swCodeId);
        if ($excludeTxnId) {
            $sql .= " AND txn_id <> :excludeId";
            $params['excludeId'] = $excludeTxnId;
        }
        $opening = $this->getOpeningKg($siteId, $swCodeId);
        if (!empty($opening['asAtDate'])) {
            $sql .= " AND event_date > :cutover";
            $params['cutover'] = $opening['asAtDate'];
        }
        $sql .= " ORDER BY event_date ASC, txn_id ASC";
        $rows = $this->queryAll($sql, $params);

        $events = array();
        foreach ($rows as $row) {
            $events[] = array(
                'eventDate' => $row['eventDate'],
                'txnId' => intval($row['txnId']),
                'txnType' => $row['txnType'],
                'qtyKg' => floatval($row['qtyKg'])
            );
        }
        $events[] = array(
            'eventDate' => $eventDate,
            'txnId' => $excludeTxnId ? intval($excludeTxnId) : 999999999,
            'txnType' => $type,
            'qtyKg' => $qtyKg,
            'candidate' => true
        );
        usort($events, function ($a, $b) {
            if ($a['eventDate'] === $b['eventDate']) {
                return $a['txnId'] <=> $b['txnId'];
            }
            return strcmp($a['eventDate'], $b['eventDate']);
        });

        $balance = floatval($opening['qtyKg']);
        foreach ($events as $event) {
            if ($event['txnType'] === 'P') {
                $balance += $event['qtyKg'];
            } else {
                $balance -= $event['qtyKg'];
            }
            if ($balance < -0.0005) {
                throw new Exception('Disposal quantity is not supported by the available balance for this event date. Review the quantity and earlier waste records.', 31);
            }
        }
    }

    public function summarise(array $siteIds, array $swCodeIds, string $periodFrom, string $periodTo, string $asAt): array {
        $whereSite = '';
        $whereSw = '';
        $filterParams = array();
        if (!empty($siteIds)) {
            list($keys, $inParams) = $this->inPlaceholders($siteIds, 's');
            $whereSite = ' AND t.site_id IN (' . implode(',', $keys) . ')';
            $filterParams = array_merge($filterParams, $inParams);
        }
        if (!empty($swCodeIds)) {
            list($keys, $inParams) = $this->inPlaceholders($swCodeIds, 'c');
            $whereSw = ' AND t.sw_code_id IN (' . implode(',', $keys) . ')';
            $filterParams = array_merge($filterParams, $inParams);
        }

        $openingWhereSite = $whereSite ? str_replace('t.site_id', 'o.site_id', $whereSite) : '';
        $openingWhereSw = $whereSw ? str_replace('t.sw_code_id', 'o.sw_code_id', $whereSw) : '';

        $openingRows = $this->queryAll(
            "SELECT o.site_id, o.sw_code_id, o.qty_kg, o.as_at_date
             FROM wst_opening_balance o
             WHERE o.opening_status = 1 AND o.as_at_date < :periodFrom
             $openingWhereSite $openingWhereSw",
            array_merge($filterParams, array('periodFrom' => $periodFrom))
        );

        $openByKey = array();
        foreach ($openingRows as $row) {
            $key = intval($row['siteId']) . ':' . intval($row['swCodeId']);
            $openByKey[$key] = floatval($row['qtyKg']);
        }

        $prePeriod = $this->queryAll(
            "SELECT t.site_id, t.sw_code_id, t.txn_type, SUM(t.qty_kg) AS qty_kg
             FROM wst_transaction t
             WHERE t.txn_status = 'FINAL' AND t.event_date < :periodFrom
             $whereSite $whereSw
             GROUP BY t.site_id, t.sw_code_id, t.txn_type",
            array_merge($filterParams, array('periodFrom' => $periodFrom))
        );
        foreach ($prePeriod as $row) {
            $key = intval($row['siteId']) . ':' . intval($row['swCodeId']);
            if (!isset($openByKey[$key])) {
                $openByKey[$key] = 0.0;
            }
            $qty = floatval($row['qtyKg']);
            $openByKey[$key] += ($row['txnType'] === 'P' ? $qty : -$qty);
        }

        $periodMoves = $this->queryAll(
            "SELECT t.site_id, t.sw_code_id, t.txn_type, SUM(t.qty_kg) AS qty_kg, COUNT(*) AS txn_count
             FROM wst_transaction t
             WHERE t.txn_status = 'FINAL' AND t.event_date BETWEEN :periodFrom AND :periodTo
             $whereSite $whereSw
             GROUP BY t.site_id, t.sw_code_id, t.txn_type",
            array_merge($filterParams, array('periodFrom' => $periodFrom, 'periodTo' => $periodTo))
        );

        $toAsAt = $this->queryAll(
            "SELECT t.site_id, t.sw_code_id, t.txn_type, SUM(t.qty_kg) AS qty_kg
             FROM wst_transaction t
             WHERE t.txn_status = 'FINAL' AND t.event_date <= :asAt
             $whereSite $whereSw
             GROUP BY t.site_id, t.sw_code_id, t.txn_type",
            array_merge($filterParams, array('asAt' => $asAt))
        );

        $pairs = array();
        foreach (array_keys($openByKey) as $key) {
            $pairs[$key] = true;
        }
        foreach (array_merge($periodMoves, $toAsAt) as $row) {
            $pairs[intval($row['siteId']) . ':' . intval($row['swCodeId'])] = true;
        }

        $lines = array();
        $totalOpening = 0.0;
        $totalProduced = 0.0;
        $totalDisposed = 0.0;
        $totalClosing = 0.0;
        $txnCount = 0;

        foreach (array_keys($pairs) as $key) {
            list($siteId, $swCodeId) = array_map('intval', explode(':', $key));
            $produced = 0.0;
            $disposed = 0.0;
            $count = 0;
            foreach ($periodMoves as $row) {
                if (intval($row['siteId']) === $siteId && intval($row['swCodeId']) === $swCodeId) {
                    if ($row['txnType'] === 'P') {
                        $produced = floatval($row['qtyKg']);
                    } else {
                        $disposed = floatval($row['qtyKg']);
                    }
                    $count += intval($row['txnCount']);
                }
            }
            $opening = isset($openByKey[$key]) ? floatval($openByKey[$key]) : 0.0;
            $asAtOpening = $this->getOpeningKg($siteId, $swCodeId, $asAt);
            $closing = floatval($asAtOpening['qtyKg']);
            foreach ($toAsAt as $row) {
                if (intval($row['siteId']) === $siteId && intval($row['swCodeId']) === $swCodeId) {
                    $eventOk = true;
                    if (!empty($asAtOpening['asAtDate']) && $asAt < $asAtOpening['asAtDate']) {
                        $eventOk = false;
                    }
                    if ($eventOk) {
                        $qty = floatval($row['qtyKg']);
                        $closing += ($row['txnType'] === 'P' ? $qty : -$qty);
                    }
                }
            }
            // Closing from ledger is more reliable
            $closing = $this->runningBalance($siteId, $swCodeId, $asAt);

            $sw = $this->getSwCode($swCodeId);
            $site = $this->getSiteRow($siteId);
            $lines[] = array(
                'siteId' => $siteId,
                'siteName' => $site['siteName'] ?? '',
                'siteCode' => $site['siteCode'] ?? '',
                'swCodeId' => $swCodeId,
                'swCode' => $sw['swCode'] ?? '',
                'swDescription' => $sw['swDescription'] ?? '',
                'openingKg' => round($opening, 3),
                'producedKg' => round($produced, 3),
                'disposedKg' => round($disposed, 3),
                'netKg' => round($produced - $disposed, 3),
                'closingKg' => round($closing, 3),
                'openingMt' => $this->formatMt($opening),
                'producedMt' => $this->formatMt($produced),
                'disposedMt' => $this->formatMt($disposed),
                'closingMt' => $this->formatMt($closing),
                'transactionCount' => $count
            );
            $totalOpening += $opening;
            $totalProduced += $produced;
            $totalDisposed += $disposed;
            $totalClosing += $closing;
            $txnCount += $count;
        }

        usort($lines, function ($a, $b) {
            $c = strcmp($a['siteName'], $b['siteName']);
            return $c !== 0 ? $c : strcmp($a['swCode'], $b['swCode']);
        });

        return array(
            'openingKg' => round($totalOpening, 3),
            'producedKg' => round($totalProduced, 3),
            'disposedKg' => round($totalDisposed, 3),
            'netKg' => round($totalProduced - $totalDisposed, 3),
            'closingKg' => round($totalClosing, 3),
            'openingMt' => $this->formatMt($totalOpening),
            'producedMt' => $this->formatMt($totalProduced),
            'disposedMt' => $this->formatMt($totalDisposed),
            'closingMt' => $this->formatMt($totalClosing),
            'transactionCount' => $txnCount,
            'lines' => $lines
        );
    }
}
