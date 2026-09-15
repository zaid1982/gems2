<?php

class WasteDashboard extends WasteBase {

    public function get(array $filters): array {
        $periodFrom = $this->normalizeDate($filters['periodFrom'] ?? '') ?: date('Y-m-01');
        $periodTo = $this->normalizeDate($filters['periodTo'] ?? '') ?: date('Y-m-t');
        $asAt = $this->normalizeDate($filters['asAt'] ?? '') ?: $periodTo;
        $siteIds = $this->parseIdList($filters['siteIds'] ?? ($filters['siteId'] ?? ''));
        $swCodeIds = $this->parseIdList($filters['swCodeIds'] ?? ($filters['swCodeId'] ?? ''));
        $granularity = strtolower(strval($filters['granularity'] ?? 'day'));
        if (!in_array($granularity, array('day', 'week', 'month'), true)) {
            $granularity = 'day';
        }

        if (!$this->isAdministrator()) {
            $siteIds = array($this->resolveSiteId());
        } else if (empty($siteIds) && !empty($filters['siteId'])) {
            $siteIds = array(intval($filters['siteId']));
        }

        $bal = new WasteBalance();
        $bal->adopt($this);
        $summary = $bal->summarise($siteIds, $swCodeIds, $periodFrom, $periodTo, $asAt);

        $draftCount = $this->countByStatus('DRAFT', $siteIds, $swCodeIds);
        $finalCount = intval($summary['transactionCount']);

        return array(
            'filters' => array(
                'periodFrom' => $periodFrom,
                'periodTo' => $periodTo,
                'asAt' => $asAt,
                'siteIds' => $siteIds,
                'swCodeIds' => $swCodeIds,
                'unit' => 'kg',
                'granularity' => $granularity
            ),
            'kpis' => array(
                'openingKg' => $summary['openingKg'],
                'producedKg' => $summary['producedKg'],
                'disposedKg' => $summary['disposedKg'],
                'netKg' => $summary['netKg'],
                'currentKg' => $summary['closingKg'],
                'openingMt' => $summary['openingMt'],
                'producedMt' => $summary['producedMt'],
                'disposedMt' => $summary['disposedMt'],
                'currentMt' => $summary['closingMt'],
                'finalTransactions' => $finalCount,
                'draftRecords' => $draftCount
            ),
            'balanceTable' => $summary['lines'],
            'trend' => $this->trend($siteIds, $swCodeIds, $periodFrom, $periodTo, $granularity),
            'bySwCode' => $this->bySwCode($summary['lines']),
            'premiseComparison' => $this->premiseComparison($summary['lines']),
            'analysis' => $this->analysis($siteIds, $swCodeIds, $periodFrom, $periodTo, $filters),
            'recent' => $this->recent($siteIds, $swCodeIds),
            'refreshedAt' => date('Y-m-d H:i:s')
        );
    }

    private function countByStatus(string $status, array $siteIds, array $swCodeIds): int {
        $sql = "SELECT COUNT(*) AS cnt FROM wst_transaction t WHERE t.txn_status = :status";
        $params = array('status' => $status);
        if (!empty($siteIds)) {
            list($keys, $inParams) = $this->inPlaceholders($siteIds, 's');
            $sql .= ' AND t.site_id IN (' . implode(',', $keys) . ')';
            $params = array_merge($params, $inParams);
        } else if (!$this->isAdministrator()) {
            $sql .= ' AND t.site_id = :own';
            $params['own'] = $this->resolveSiteId();
        }
        if (!empty($swCodeIds)) {
            list($keys, $inParams) = $this->inPlaceholders($swCodeIds, 'c');
            $sql .= ' AND t.sw_code_id IN (' . implode(',', $keys) . ')';
            $params = array_merge($params, $inParams);
        }
        $row = $this->queryOne($sql, $params);
        return intval($row['cnt'] ?? 0);
    }

    private function trend(array $siteIds, array $swCodeIds, string $from, string $to, string $granularity): array {
        $expr = "DATE(t.event_date)";
        if ($granularity === 'week') {
            $expr = "DATE_FORMAT(t.event_date, '%x-W%v')";
        } else if ($granularity === 'month') {
            $expr = "DATE_FORMAT(t.event_date, '%Y-%m')";
        }
        $sql = "SELECT $expr AS bucket, t.txn_type, SUM(t.qty_kg) AS qty_kg, COUNT(*) AS txn_count
                FROM wst_transaction t
                WHERE t.txn_status = 'FINAL' AND t.event_date BETWEEN :fromDate AND :toDate";
        $params = array('fromDate' => $from, 'toDate' => $to);
        if (!empty($siteIds)) {
            list($keys, $inParams) = $this->inPlaceholders($siteIds, 's');
            $sql .= ' AND t.site_id IN (' . implode(',', $keys) . ')';
            $params = array_merge($params, $inParams);
        }
        if (!empty($swCodeIds)) {
            list($keys, $inParams) = $this->inPlaceholders($swCodeIds, 'c');
            $sql .= ' AND t.sw_code_id IN (' . implode(',', $keys) . ')';
            $params = array_merge($params, $inParams);
        }
        $sql .= " GROUP BY bucket, t.txn_type ORDER BY bucket";
        $rows = $this->queryAll($sql, $params);
        $out = array();
        foreach ($rows as $row) {
            $key = strval($row['bucket']);
            if (!isset($out[$key])) {
                $out[$key] = array('period' => $key, 'producedKg' => 0, 'disposedKg' => 0, 'producedCount' => 0, 'disposedCount' => 0);
            }
            if ($row['txnType'] === 'P') {
                $out[$key]['producedKg'] = floatval($row['qtyKg']);
                $out[$key]['producedCount'] = intval($row['txnCount']);
            } else {
                $out[$key]['disposedKg'] = floatval($row['qtyKg']);
                $out[$key]['disposedCount'] = intval($row['txnCount']);
            }
        }
        return array_values($out);
    }

    private function bySwCode(array $lines): array {
        $grouped = array();
        foreach ($lines as $line) {
            $code = $line['swCode'];
            if (!isset($grouped[$code])) {
                $grouped[$code] = array(
                    'swCode' => $code,
                    'swDescription' => $line['swDescription'],
                    'producedKg' => 0,
                    'disposedKg' => 0,
                    'closingKg' => 0
                );
            }
            $grouped[$code]['producedKg'] += $line['producedKg'];
            $grouped[$code]['disposedKg'] += $line['disposedKg'];
            $grouped[$code]['closingKg'] += $line['closingKg'];
        }
        return array_values($grouped);
    }

    private function premiseComparison(array $lines): array {
        $grouped = array();
        foreach ($lines as $line) {
            $id = $line['siteId'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = array(
                    'siteId' => $id,
                    'siteName' => $line['siteName'],
                    'siteCode' => $line['siteCode'],
                    'producedKg' => 0,
                    'disposedKg' => 0,
                    'currentKg' => 0
                );
            }
            $grouped[$id]['producedKg'] += $line['producedKg'];
            $grouped[$id]['disposedKg'] += $line['disposedKg'];
            $grouped[$id]['currentKg'] += $line['closingKg'];
        }
        return array_values($grouped);
    }

    private function analysis(array $siteIds, array $swCodeIds, string $from, string $to, array $filters): array {
        $extra = '';
        $params = array('fromDate' => $from, 'toDate' => $to);
        if (!empty($siteIds)) {
            list($keys, $inParams) = $this->inPlaceholders($siteIds, 's');
            $extra .= ' AND t.site_id IN (' . implode(',', $keys) . ')';
            $params = array_merge($params, $inParams);
        }
        if (!empty($swCodeIds)) {
            list($keys, $inParams) = $this->inPlaceholders($swCodeIds, 'c');
            $extra .= ' AND t.sw_code_id IN (' . implode(',', $keys) . ')';
            $params = array_merge($params, $inParams);
        }
        if (!empty($filters['packagingTypeId'])) {
            $extra .= ' AND t.packaging_type_id = :packId';
            $params['packId'] = intval($filters['packagingTypeId']);
        }
        if (!empty($filters['handlingMethodId'])) {
            $extra .= ' AND t.handling_method_id = :handId';
            $params['handId'] = intval($filters['handlingMethodId']);
        }
        if (!empty($filters['transporterId'])) {
            $extra .= ' AND t.transporter_id = :trId';
            $params['trId'] = intval($filters['transporterId']);
        }

        $build = function (string $join, string $labelExpr, string $idExpr) use ($extra, $params) {
            $sql = "SELECT $idExpr AS group_id, $labelExpr AS group_name, t.txn_type, SUM(t.qty_kg) AS qty_kg
                    FROM wst_transaction t
                    $join
                    WHERE t.txn_status = 'FINAL' AND t.event_date BETWEEN :fromDate AND :toDate
                    $extra
                    GROUP BY group_id, group_name, t.txn_type
                    ORDER BY group_name";
            return $this->queryAll($sql, $params);
        };

        return array(
            'packaging' => $build('LEFT JOIN wst_ref_value v ON v.ref_value_id = t.packaging_type_id', "COALESCE(v.value_name, 'Not specified')", 't.packaging_type_id'),
            'handling' => $build('LEFT JOIN wst_ref_value v ON v.ref_value_id = t.handling_method_id', "COALESCE(v.value_name, 'Not specified')", 't.handling_method_id'),
            'transporter' => $build('LEFT JOIN wst_ref_value v ON v.ref_value_id = t.transporter_id', "COALESCE(NULLIF(t.transporter_text,''), v.value_name, 'Not specified')", 't.transporter_id')
        );
    }

    private function recent(array $siteIds, array $swCodeIds): array {
        $sql = "SELECT t.txn_id, t.txn_ref, t.txn_type, t.event_date, t.qty, t.unit, t.qty_kg, t.txn_status,
                       s.site_name, c.sw_code
                FROM wst_transaction t
                INNER JOIN cli_site s ON s.site_id = t.site_id
                INNER JOIN ref_sw_code c ON c.sw_code_id = t.sw_code_id
                WHERE 1 = 1";
        $params = array();
        if (!empty($siteIds)) {
            list($keys, $inParams) = $this->inPlaceholders($siteIds, 's');
            $sql .= ' AND t.site_id IN (' . implode(',', $keys) . ')';
            $params = array_merge($params, $inParams);
        } else if (!$this->isAdministrator()) {
            $sql .= ' AND t.site_id = :own';
            $params['own'] = $this->resolveSiteId();
        }
        if (!empty($swCodeIds)) {
            list($keys, $inParams) = $this->inPlaceholders($swCodeIds, 'c');
            $sql .= ' AND t.sw_code_id IN (' . implode(',', $keys) . ')';
            $params = array_merge($params, $inParams);
        }
        $sql .= " ORDER BY t.txn_id DESC LIMIT 8";
        return $this->queryAll($sql, $params);
    }
}
