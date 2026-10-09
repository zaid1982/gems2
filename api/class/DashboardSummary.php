<?php

class DashboardSummary extends General {

    private $adminCache = null;
    private $tableCache = array();

    public function isAdministrator(): bool {
        if ($this->adminCache === null) {
            $this->adminCache = parent::isAdministrator();
        }
        return $this->adminCache;
    }

    public function parseFilters(array $input): array {
        $fromRaw = trim(strval($input['dateFrom'] ?? ''));
        $toRaw = trim(strval($input['dateTo'] ?? ''));
        $from = $fromRaw === '' ? null : $this->normalizeDate($fromRaw);
        $to = $toRaw === '' ? null : $this->normalizeDate($toRaw);
        if ($fromRaw !== '' && $from === null) {
            throw new Exception('Date From is not a valid date.', 31);
        }
        if ($toRaw !== '' && $to === null) {
            throw new Exception('Date To is not a valid date.', 31);
        }
        if ($from === null) {
            $from = date('Y-m-01');
        }
        if ($to === null) {
            $to = date('Y-m-t');
        }
        $granularity = strtolower(trim(strval($input['granularity'] ?? 'day')));
        if (!in_array($granularity, array('day', 'week', 'month'), true)) {
            $granularity = 'day';
        }
        $clientId = intval($input['clientId'] ?? 0);
        if ($clientId < 0) {
            $clientId = 0;
        }
        return array(
            'dateFrom' => $from,
            'dateTo' => $to,
            'granularity' => $granularity,
            'clientId' => $clientId,
            'requestedSiteIds' => $this->parseIdList($input['siteIds'] ?? ($input['siteId'] ?? '')),
            'emptyRange' => strcmp($from, $to) > 0,
            'siteIds' => array(),
            'scope' => 'period'
        );
    }

    public function context(array $input): array {
        $filters = $this->parseFilters($input);
        $filters['siteIds'] = $this->resolveSiteIds($filters['requestedSiteIds'], $filters['clientId']);
        return $filters;
    }

    public function resolveSiteIds(array $requested, int $clientId = 0): array {
        $requested = array_values(array_unique(array_map('intval', $requested)));
        if (!$this->isAdministrator()) {
            $own = intval($this->userSite);
            if ($own <= 0) {
                throw new Exception('Your account is not assigned to a site.', 31);
            }
            foreach ($requested as $siteId) {
                if ($siteId !== $own) {
                    throw new Exception('The requested site is unavailable.', 31);
                }
            }
            if ($clientId > 0) {
                $row = $this->queryOne(
                    'SELECT client_id AS client_id FROM cli_site WHERE site_id = :siteId',
                    array('siteId' => $own)
                );
                if (empty($row) || intval($row['clientId']) !== $clientId) {
                    throw new Exception('The requested site is unavailable.', 31);
                }
            }
            return array($own);
        }

        $params = array();
        $sql = 'SELECT site_id AS site_id FROM cli_site WHERE site_status = 1';
        if ($clientId > 0) {
            $sql .= ' AND client_id = :clientId';
            $params['clientId'] = $clientId;
        }
        if (!empty($requested)) {
            list($keys, $inParams) = $this->inPlaceholders($requested, 'req');
            $sql .= ' AND site_id IN (' . implode(',', $keys) . ')';
            $params = array_merge($params, $inParams);
        }
        $sql .= ' ORDER BY site_id';
        $rows = $this->queryAll($sql, $params);
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = intval($row['siteId']);
        }
        if (!empty($requested) && count($ids) !== count($requested)) {
            throw new Exception('One or more requested sites are unavailable.', 31);
        }
        return $ids;
    }

    public function me(): array {
        $management = $this->isAdministrator();
        if ($management) {
            $sites = $this->siteRows(array());
        } else {
            $own = intval($this->userSite);
            $sites = $own > 0 ? $this->siteRows(array($own), false) : array();
        }
        $clients = array();
        $accountClientId = 0;
        $ownSiteId = intval($this->userSite);
        foreach ($sites as $site) {
            $clientId = intval($site['clientId']);
            if ($clientId > 0 && !isset($clients[$clientId])) {
                $clients[$clientId] = array(
                    'clientId' => $clientId,
                    'clientName' => $site['clientName']
                );
            }
            if ($ownSiteId > 0 && intval($site['siteId']) === $ownSiteId) {
                $accountClientId = $clientId;
            }
        }
        if ($accountClientId === 0 && $ownSiteId > 0) {
            $ownSite = $this->siteRows(array($ownSiteId), false);
            if (!empty($ownSite)) {
                $accountClientId = intval($ownSite[0]['clientId']);
            }
        }
        return array(
            'userId' => intval($this->userId),
            'siteId' => $ownSiteId,
            'clientId' => $accountClientId,
            'isManagement' => $management,
            'sites' => array_values($sites),
            'clients' => array_values($clients),
            'defaultPeriod' => array(
                'from' => date('Y-m-01'),
                'to' => date('Y-m-t')
            ),
            'modules' => array(
                'workOrder' => $this->tableExists('wo_task'),
                'ppm' => $this->tableExists('ppm_task'),
                'assets' => $this->tableExists('ast_asset'),
                'ptw' => $this->tableExists('ptw_permit'),
                'waste' => $this->tableExists('wst_transaction'),
                'license' => $this->tableExists('lic_license'),
                'kpa' => $this->tableExists('kpa_evaluation'),
                'energy' => $this->tableExists('enr_meter')
            )
        );
    }

    public function workOrders(array $input): array {
        $filters = $this->context($input);
        $filters['scope'] = 'period';
        $kpis = $this->blankWorkOrders();
        if (!$this->tableExists('wo_task')) {
            return $this->unavailable($filters, 'workOrder');
        }
        if ($filters['emptyRange'] || empty($filters['siteIds'])) {
            return $this->envelope($filters, $kpis, array(), array(), array('byType' => $this->blankWorkTypes()));
        }

        list($keys, $params) = $this->inPlaceholders($filters['siteIds'], 'wo');
        $params['dateFrom'] = $filters['dateFrom'];
        $params['dateTo'] = $filters['dateTo'];
        $period = $this->periodExpr('t.wo_task_time_created', $filters['granularity']);
        $rows = $this->queryAll(
            "SELECT t.site_id AS site_id, $period AS period, COUNT(*) AS total,
                    SUM(t.wo_task_status = 16) AS completed,
                    SUM(t.wo_task_status = 25) AS cancelled,
                    SUM(t.wo_task_status IN (24, 26)) AS responding,
                    SUM(t.wo_task_status IN (13, 21)) AS in_progress,
                    SUM(t.wo_task_status = 15) AS verify_count,
                    SUM(t.wo_task_type = 1) AS type_complaint,
                    SUM(t.wo_task_type = 2) AS type_finding,
                    SUM(t.wo_task_type = 3) AS type_request,
                    SUM(t.wo_task_type = 4) AS type_breakdown,
                    SUM(t.wo_task_type = 5) AS type_defect,
                    SUM(t.wo_task_type = 6) AS type_public
             FROM wo_task t
             WHERE t.site_id IN (" . implode(',', $keys) . ")
               AND t.wo_task_time_created >= :dateFrom
               AND t.wo_task_time_created < DATE_ADD(:dateTo, INTERVAL 1 DAY)
             GROUP BY t.site_id, $period",
            $params
        );
        $siteAcc = array();
        $trendAcc = array();
        $types = $this->blankWorkTypes();
        foreach ($rows as $row) {
            $siteId = intval($row['siteId']);
            $counts = $this->workOrderCounts($row);
            if (!isset($siteAcc[$siteId])) {
                $siteAcc[$siteId] = $this->blankWorkOrders();
            }
            foreach ($counts as $key => $value) {
                $siteAcc[$siteId][$key] += $value;
            }
            $types[0]['y'] += intval($row['typeComplaint'] ?? 0);
            $types[1]['y'] += intval($row['typeFinding'] ?? 0);
            $types[2]['y'] += intval($row['typeRequest'] ?? 0);
            $types[3]['y'] += intval($row['typeBreakdown'] ?? 0);
            $types[4]['y'] += intval($row['typeDefect'] ?? 0);
            $types[5]['y'] += intval($row['typePublic'] ?? 0);
            $bucket = strval($row['period']);
            if (!isset($trendAcc[$bucket])) {
                $trendAcc[$bucket] = array('period' => $bucket, 'created' => 0, 'completed' => 0);
            }
            $trendAcc[$bucket]['created'] += $counts['total'];
            $trendAcc[$bucket]['completed'] += $counts['completed'];
        }
        ksort($trendAcc);
        $bySite = array();
        foreach ($this->catalog($filters['siteIds']) as $siteId => $site) {
            $counts = isset($siteAcc[$siteId]) ? $siteAcc[$siteId] : $this->blankWorkOrders();
            $bySite[] = array_merge($site, $counts);
            foreach ($kpis as $key => $value) {
                $kpis[$key] += $counts[$key];
            }
        }
        $knownTypes = 0;
        foreach ($types as $type) {
            $knownTypes += $type['y'];
        }
        $types[] = array('name' => 'Other', 'y' => max(0, $kpis['total'] - $knownTypes));
        return $this->envelope($filters, $kpis, $bySite, array_values($trendAcc), array('byType' => $types));
    }

    public function ppm(array $input): array {
        $filters = $this->context($input);
        $filters['scope'] = 'period';
        $kpis = $this->blankPpm();
        if (!$this->tableExists('ppm_task')) {
            return $this->unavailable($filters, 'ppm');
        }
        if ($filters['emptyRange'] || empty($filters['siteIds'])) {
            return $this->envelope($filters, $kpis, array(), array());
        }

        list($keys, $params) = $this->inPlaceholders($filters['siteIds'], 'ppm');
        $params['dateFrom'] = $filters['dateFrom'];
        $params['dateTo'] = $filters['dateTo'];
        $period = $this->periodExpr('pt.ppm_task_start_date', $filters['granularity']);
        $rows = $this->queryAll(
            "SELECT c.site_id AS site_id, $period AS period, COUNT(*) AS scheduled,
                    SUM(pt.ppm_task_status = 16) AS done,
                    SUM(pt.ppm_task_status = 12) AS status_open,
                    SUM(pt.ppm_task_status IN (13, 21)) AS status_progress,
                    SUM(pt.ppm_task_status = 14) AS status_check,
                    SUM(pt.ppm_task_status = 15) AS status_verify,
                    SUM((pt.ppm_task_time_serviced IS NULL AND CURDATE() > pt.ppm_task_schedule_date)
                        OR DATE(pt.ppm_task_time_serviced) > pt.ppm_task_schedule_date) AS late
             FROM ppm_task pt
             INNER JOIN ppm p ON p.ppm_id = pt.ppm_id
             INNER JOIN cli_contract c ON c.contract_id = p.contract_id
             WHERE c.site_id IN (" . implode(',', $keys) . ")
               AND c.contract_status = 1
               AND pt.ppm_task_start_date >= :dateFrom
               AND pt.ppm_task_start_date < DATE_ADD(:dateTo, INTERVAL 1 DAY)
             GROUP BY c.site_id, $period",
            $params
        );
        $siteAcc = array();
        $trendAcc = array();
        $sumFields = array('scheduled', 'done', 'late', 'statusOpen', 'statusInProgress', 'statusCheck', 'statusVerify', 'statusCompleted', 'statusOther');
        foreach ($rows as $row) {
            $siteId = intval($row['siteId']);
            $counts = $this->ppmCounts($row);
            if (!isset($siteAcc[$siteId])) {
                $siteAcc[$siteId] = $this->blankPpm();
            }
            foreach ($sumFields as $field) {
                $siteAcc[$siteId][$field] += $counts[$field];
            }
            $bucket = strval($row['period']);
            if (!isset($trendAcc[$bucket])) {
                $trendAcc[$bucket] = array('period' => $bucket, 'scheduled' => 0, 'completed' => 0);
            }
            $trendAcc[$bucket]['scheduled'] += $counts['scheduled'];
            $trendAcc[$bucket]['completed'] += $counts['done'];
        }
        ksort($trendAcc);
        $bySite = array();
        foreach ($this->catalog($filters['siteIds']) as $siteId => $site) {
            $counts = isset($siteAcc[$siteId]) ? $siteAcc[$siteId] : $this->blankPpm();
            $counts['percDone'] = $this->percent($counts['done'], $counts['scheduled']);
            $bySite[] = array_merge($site, $counts);
            foreach ($sumFields as $field) {
                $kpis[$field] += $counts[$field];
            }
        }
        $kpis['percDone'] = $this->percent($kpis['done'], $kpis['scheduled']);
        return $this->envelope($filters, $kpis, $bySite, array_values($trendAcc));
    }

    public function assets(array $input): array {
        $filters = $this->context($input);
        $filters['scope'] = 'current';
        $kpis = array('active' => 0);
        if (!$this->tableExists('ast_asset')) {
            return $this->unavailable($filters, 'assets');
        }
        if (empty($filters['siteIds'])) {
            return $this->envelope($filters, $kpis, array(), array());
        }
        list($keys, $params) = $this->inPlaceholders($filters['siteIds'], 'asset');
        $rows = $this->queryAll(
            "SELECT c.site_id AS site_id, COUNT(*) AS active
             FROM ast_asset a
             INNER JOIN cli_contract c ON c.contract_id = a.contract_id AND c.contract_status = 1
             WHERE c.site_id IN (" . implode(',', $keys) . ")
             GROUP BY c.site_id",
            $params
        );
        $indexed = $this->rowsBySite($rows);
        $bySite = array();
        foreach ($this->catalog($filters['siteIds']) as $siteId => $site) {
            $active = intval($indexed[$siteId]['active'] ?? 0);
            $bySite[] = array_merge($site, array('active' => $active));
            $kpis['active'] += $active;
        }
        return $this->envelope($filters, $kpis, $bySite, array());
    }

    public function ptw(array $input): array {
        $filters = $this->context($input);
        $filters['scope'] = 'current';
        $kpis = $this->blankPtw();
        if (!$this->tableExists('ptw_permit')) {
            return $this->unavailable($filters, 'ptw');
        }
        if (empty($filters['siteIds'])) {
            return $this->envelope($filters, $kpis, array(), array());
        }
        list($keys, $params) = $this->inPlaceholders($filters['siteIds'], 'ptw');
        $rows = $this->queryAll(
            "SELECT site_id AS site_id, COUNT(*) AS total,
                    SUM(ptw_status = 'ACTIVE') AS active,
                    SUM(ptw_status IN ('PENDING_SUPERVISOR', 'PENDING_SHE', 'PENDING_FM')) AS pending_approval,
                    SUM(ptw_status = 'APPROVED') AS approved,
                    SUM(ptw_status = 'COMPLETED') AS completed,
                    SUM(ptw_status = 'EXPIRED') AS expired,
                    SUM(ptw_status = 'CANCELLED') AS cancelled,
                    SUM(ptw_status = 'DRAFT') AS draft,
                    SUM(ptw_risk_level = 'CRITICAL' AND ptw_status NOT IN ('CANCELLED', 'COMPLETED', 'EXPIRED')) AS critical,
                    SUM(ptw_status = 'ACTIVE' AND ptw_valid_to >= NOW() AND ptw_valid_to < DATE_ADD(NOW(), INTERVAL 7 DAY)) AS expiring7d
             FROM ptw_permit
             WHERE site_id IN (" . implode(',', $keys) . ")
             GROUP BY site_id",
            $params
        );
        $indexed = $this->rowsBySite($rows);
        $bySite = array();
        foreach ($this->catalog($filters['siteIds']) as $siteId => $site) {
            $counts = $this->ptwCounts(isset($indexed[$siteId]) ? $indexed[$siteId] : array());
            $bySite[] = array_merge($site, $counts);
            foreach ($kpis as $key => $value) {
                $kpis[$key] += $counts[$key];
            }
        }
        return $this->envelope($filters, $kpis, $bySite, array());
    }

    public function licenses(array $input): array {
        $filters = $this->context($input);
        $filters['scope'] = 'current';
        $kpis = array('expired' => 0, 'expiring30' => 0, 'expiring90' => 0);
        if (!$this->tableExists('lic_license')) {
            return $this->unavailable($filters, 'license');
        }
        if (empty($filters['siteIds'])) {
            return $this->envelope($filters, $kpis, array(), array(), array('items' => array()));
        }
        list($keys, $params) = $this->inPlaceholders($filters['siteIds'], 'lic');
        $rows = $this->queryAll(
            "SELECT site_id AS site_id,
                    SUM(license_status = 1 AND license_end_date < CURDATE()) AS expired,
                    SUM(license_status = 1 AND license_end_date >= CURDATE() AND license_end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS expiring30,
                    SUM(license_status = 1 AND license_end_date >= CURDATE() AND license_end_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)) AS expiring90
             FROM lic_license
             WHERE site_id IN (" . implode(',', $keys) . ")
             GROUP BY site_id",
            $params
        );
        $indexed = $this->rowsBySite($rows);
        $bySite = array();
        foreach ($this->catalog($filters['siteIds']) as $siteId => $site) {
            $row = isset($indexed[$siteId]) ? $indexed[$siteId] : array();
            $counts = array(
                'expired' => intval($row['expired'] ?? 0),
                'expiring30' => intval($row['expiring30'] ?? 0),
                'expiring90' => intval($row['expiring90'] ?? 0)
            );
            $bySite[] = array_merge($site, $counts);
            foreach ($counts as $key => $value) {
                $kpis[$key] += $value;
            }
        }
        $items = $this->queryAll(
            "SELECT l.license_id AS license_id, l.license_title AS license_title,
                    l.license_end_date AS license_end_date, l.site_id AS site_id,
                    s.site_name AS site_name, s.site_code AS site_code,
                    DATEDIFF(l.license_end_date, CURDATE()) AS days_remaining
             FROM lic_license l
             INNER JOIN cli_site s ON s.site_id = l.site_id
             WHERE l.license_status = 1
               AND l.license_end_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
               AND l.site_id IN (" . implode(',', $keys) . ")
             ORDER BY l.license_end_date, s.site_name
             LIMIT 8",
            $params
        );
        $cleanItems = array();
        foreach ($items as $item) {
            $cleanItems[] = array(
                'licenseId' => intval($item['licenseId'] ?? 0),
                'title' => strval($item['licenseTitle'] ?? ''),
                'siteId' => intval($item['siteId'] ?? 0),
                'siteName' => strval($item['siteName'] ?? ''),
                'siteCode' => strval($item['siteCode'] ?? ''),
                'endDate' => strval($item['licenseEndDate'] ?? ''),
                'daysRemaining' => intval($item['daysRemaining'] ?? 0)
            );
        }
        return $this->envelope($filters, $kpis, $bySite, array(), array('items' => $cleanItems));
    }

    public function waste(array $input): array {
        $filters = $this->context($input);
        $filters['scope'] = 'period';
        $kpis = array('generatedKg' => 0, 'disposedKg' => 0, 'pendingKg' => 0);
        if (!$this->tableExists('wst_transaction')) {
            return $this->unavailable($filters, 'waste');
        }
        if ($filters['emptyRange'] || empty($filters['siteIds'])) {
            return $this->envelope($filters, $kpis, array(), array());
        }

        list($keys, $params) = $this->inPlaceholders($filters['siteIds'], 'wst');
        $params['dateFrom'] = $filters['dateFrom'];
        $params['dateTo'] = $filters['dateTo'];
        $period = $this->periodExpr('t.event_date', $filters['granularity']);
        $rows = $this->queryAll(
            "SELECT t.site_id AS site_id, $period AS period,
                    SUM(CASE WHEN t.txn_type = 'P' THEN t.qty_kg ELSE 0 END) AS generated_kg,
                    SUM(CASE WHEN t.txn_type = 'D' THEN t.qty_kg ELSE 0 END) AS disposed_kg
             FROM wst_transaction t
             WHERE t.txn_status = 'FINAL'
               AND t.event_date >= :dateFrom
               AND t.event_date <= :dateTo
               AND t.site_id IN (" . implode(',', $keys) . ")
             GROUP BY t.site_id, $period",
            $params
        );
        $pendingRows = $this->queryAll(
            "SELECT t.site_id AS site_id, SUM(t.qty_kg) AS pending_kg
             FROM wst_transaction t
             WHERE t.txn_type = 'P' AND t.txn_status = 'FINAL' AND t.collection_status = 'PENDING'
               AND t.site_id IN (" . implode(',', $keys) . ")
             GROUP BY t.site_id",
            $params
        );
        $siteAcc = array();
        $trendAcc = array();
        foreach ($rows as $row) {
            $siteId = intval($row['siteId']);
            if (!isset($siteAcc[$siteId])) {
                $siteAcc[$siteId] = array('generatedKg' => 0.0, 'disposedKg' => 0.0);
            }
            $siteAcc[$siteId]['generatedKg'] += floatval($row['generatedKg'] ?? 0);
            $siteAcc[$siteId]['disposedKg'] += floatval($row['disposedKg'] ?? 0);
            $bucket = strval($row['period']);
            if (!isset($trendAcc[$bucket])) {
                $trendAcc[$bucket] = array('period' => $bucket, 'generatedKg' => 0.0, 'disposedKg' => 0.0);
            }
            $trendAcc[$bucket]['generatedKg'] += floatval($row['generatedKg'] ?? 0);
            $trendAcc[$bucket]['disposedKg'] += floatval($row['disposedKg'] ?? 0);
        }
        ksort($trendAcc);
        $pending = $this->rowsBySite($pendingRows);
        $bySite = array();
        foreach ($this->catalog($filters['siteIds']) as $siteId => $site) {
            $generated = round(floatval($siteAcc[$siteId]['generatedKg'] ?? 0), 3);
            $disposed = round(floatval($siteAcc[$siteId]['disposedKg'] ?? 0), 3);
            $waiting = round(floatval($pending[$siteId]['pendingKg'] ?? 0), 3);
            $bySite[] = array_merge($site, array(
                'generatedKg' => $generated,
                'disposedKg' => $disposed,
                'pendingKg' => $waiting
            ));
            $kpis['generatedKg'] += $generated;
            $kpis['disposedKg'] += $disposed;
            $kpis['pendingKg'] += $waiting;
        }
        $kpis['generatedKg'] = round($kpis['generatedKg'], 3);
        $kpis['disposedKg'] = round($kpis['disposedKg'], 3);
        $kpis['pendingKg'] = round($kpis['pendingKg'], 3);
        $trend = array();
        foreach ($trendAcc as $row) {
            $trend[] = array(
                'period' => $row['period'],
                'generatedKg' => round($row['generatedKg'], 3),
                'disposedKg' => round($row['disposedKg'], 3)
            );
        }
        return $this->envelope($filters, $kpis, $bySite, $trend);
    }

    private function blankWorkOrders(): array {
        return array(
            'total' => 0,
            'open' => 0,
            'responding' => 0,
            'inProgress' => 0,
            'verify' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'other' => 0
        );
    }

    private function blankWorkTypes(): array {
        return array(
            array('name' => 'Complaint', 'y' => 0),
            array('name' => 'Finding', 'y' => 0),
            array('name' => 'Request', 'y' => 0),
            array('name' => 'Breakdown', 'y' => 0),
            array('name' => 'Defect', 'y' => 0),
            array('name' => 'Public complaint', 'y' => 0)
        );
    }

    private function workOrderCounts(array $row): array {
        $total = intval($row['total'] ?? 0);
        $completed = intval($row['completed'] ?? 0);
        $cancelled = intval($row['cancelled'] ?? 0);
        $responding = intval($row['responding'] ?? 0);
        $inProgress = intval($row['inProgress'] ?? 0);
        $verify = intval($row['verifyCount'] ?? 0);
        $named = $completed + $cancelled + $responding + $inProgress + $verify;
        return array(
            'total' => $total,
            'open' => max(0, $total - $completed - $cancelled),
            'responding' => $responding,
            'inProgress' => $inProgress,
            'verify' => $verify,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'other' => max(0, $total - $named)
        );
    }

    private function blankPpm(): array {
        return array(
            'scheduled' => 0,
            'done' => 0,
            'late' => 0,
            'percDone' => 0,
            'statusOpen' => 0,
            'statusInProgress' => 0,
            'statusCheck' => 0,
            'statusVerify' => 0,
            'statusCompleted' => 0,
            'statusOther' => 0
        );
    }

    private function ppmCounts(array $row): array {
        $scheduled = intval($row['scheduled'] ?? 0);
        $done = intval($row['done'] ?? 0);
        $statusOpen = intval($row['statusOpen'] ?? 0);
        $statusInProgress = intval($row['statusProgress'] ?? 0);
        $statusCheck = intval($row['statusCheck'] ?? 0);
        $statusVerify = intval($row['statusVerify'] ?? 0);
        $named = $statusOpen + $statusInProgress + $statusCheck + $statusVerify + $done;
        return array(
            'scheduled' => $scheduled,
            'done' => $done,
            'late' => intval($row['late'] ?? 0),
            'percDone' => $this->percent($done, $scheduled),
            'statusOpen' => $statusOpen,
            'statusInProgress' => $statusInProgress,
            'statusCheck' => $statusCheck,
            'statusVerify' => $statusVerify,
            'statusCompleted' => $done,
            'statusOther' => max(0, $scheduled - $named)
        );
    }

    private function blankPtw(): array {
        return array(
            'total' => 0,
            'active' => 0,
            'pendingApproval' => 0,
            'approved' => 0,
            'completed' => 0,
            'expired' => 0,
            'cancelled' => 0,
            'draft' => 0,
            'other' => 0,
            'critical' => 0,
            'expiring7d' => 0
        );
    }

    private function ptwCounts(array $row): array {
        $total = intval($row['total'] ?? 0);
        $active = intval($row['active'] ?? 0);
        $pending = intval($row['pendingApproval'] ?? 0);
        $approved = intval($row['approved'] ?? 0);
        $completed = intval($row['completed'] ?? 0);
        $expired = intval($row['expired'] ?? 0);
        $cancelled = intval($row['cancelled'] ?? 0);
        $draft = intval($row['draft'] ?? 0);
        $named = $active + $pending + $approved + $completed + $expired + $cancelled + $draft;
        return array(
            'total' => $total,
            'active' => $active,
            'pendingApproval' => $pending,
            'approved' => $approved,
            'completed' => $completed,
            'expired' => $expired,
            'cancelled' => $cancelled,
            'draft' => $draft,
            'other' => max(0, $total - $named),
            'critical' => intval($row['critical'] ?? 0),
            'expiring7d' => intval($row['expiring7d'] ?? 0)
        );
    }

    private function unavailable(array $filters, string $module): array {
        $payload = $this->envelope($filters, array(), array(), array());
        $payload['available'] = false;
        $payload['module'] = $module;
        return $payload;
    }

    private function envelope(array $filters, array $kpis, array $bySite, array $trend, array $extra = array()): array {
        $payload = array(
            'filters' => array(
                'siteIds' => array_values(array_map('intval', $filters['siteIds'])),
                'clientId' => intval($filters['clientId']),
                'dateFrom' => $filters['dateFrom'],
                'dateTo' => $filters['dateTo'],
                'granularity' => $filters['granularity'],
                'scope' => $filters['scope'] ?? 'period'
            ),
            'kpis' => $kpis,
            'bySite' => array_values($bySite),
            'trend' => array_values($trend),
            'refreshedAt' => date('Y-m-d H:i:s'),
            'available' => true
        );
        foreach ($extra as $key => $value) {
            $payload[$key] = $value;
        }
        return $payload;
    }

    private function periodExpr(string $column, string $granularity): string {
        if ($granularity === 'week') {
            return "DATE_FORMAT($column, '%x-W%v')";
        }
        if ($granularity === 'month') {
            return "DATE_FORMAT($column, '%Y-%m')";
        }
        return "DATE($column)";
    }

    private function percent($part, $whole): float {
        $whole = floatval($whole);
        if ($whole <= 0) {
            return 0.0;
        }
        return round((floatval($part) / $whole) * 100, 1);
    }

    private function catalog(array $siteIds): array {
        $indexed = array();
        foreach ($this->siteRows($siteIds, false) as $site) {
            $indexed[intval($site['siteId'])] = $site;
        }
        return $indexed;
    }

    private function siteRows(array $siteIds, bool $activeOnly = true): array {
        $params = array();
        $sql = "SELECT s.site_id AS site_id, s.site_name AS site_name, s.site_code AS site_code,
                       s.client_id AS client_id, c.client_name AS client_name
                FROM cli_site s
                LEFT JOIN cli_client c ON c.client_id = s.client_id";
        $where = array();
        if ($activeOnly) {
            $where[] = 's.site_status = 1';
        }
        if (!empty($siteIds)) {
            list($keys, $inParams) = $this->inPlaceholders($siteIds, 'site');
            $where[] = 's.site_id IN (' . implode(',', $keys) . ')';
            $params = array_merge($params, $inParams);
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.client_name, s.site_name, s.site_id';
        $rows = $this->queryAll($sql, $params);
        $sites = array();
        foreach ($rows as $row) {
            $sites[] = array(
                'siteId' => intval($row['siteId'] ?? 0),
                'siteName' => strval($row['siteName'] ?? ''),
                'siteCode' => strval($row['siteCode'] ?? ''),
                'clientId' => intval($row['clientId'] ?? 0),
                'clientName' => strval($row['clientName'] ?? '')
            );
        }
        return $sites;
    }

    private function rowsBySite(array $rows): array {
        $out = array();
        foreach ($rows as $row) {
            $out[intval($row['siteId'])] = $row;
        }
        return $out;
    }

    public function tableExists(string $name): bool {
        if (array_key_exists($name, $this->tableCache)) {
            return $this->tableCache[$name];
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            $this->tableCache[$name] = false;
            return false;
        }
        $row = $this->queryOne(
            'SELECT COUNT(*) AS table_count
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :tableName',
            array('tableName' => $name)
        );
        $this->tableCache[$name] = intval($row['tableCount'] ?? 0) > 0;
        return $this->tableCache[$name];
    }

    public function queryAll(string $sql, array $params = array()): array {
        $stmt = DbMysql::$DBH->prepare($sql);
        $stmt->execute($this->namedParamsForSql($sql, $params));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
        return $this->camelizeRows($rows);
    }

    public function queryOne(string $sql, array $params = array()): array {
        $stmt = DbMysql::$DBH->prepare($sql);
        $stmt->execute($this->namedParamsForSql($sql, $params));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;
        return $row ? $this->camelizeRow($row) : array();
    }

    private function namedParamsForSql(string $sql, array $params): array {
        $bind = array();
        foreach ($params as $key => $value) {
            $name = ltrim((string) $key, ':');
            if ($name === '') {
                continue;
            }
            if (preg_match('/:' . preg_quote($name, '/') . '\b/', $sql)) {
                $bind[$name] = $value;
            }
        }
        return $bind;
    }

    public function camelizeRows(array $rows): array {
        $out = array();
        foreach ($rows as $row) {
            $out[] = $this->camelizeRow($row);
        }
        return $out;
    }

    public function camelizeRow(array $row): array {
        $out = array();
        foreach ($row as $key => $value) {
            if (strpos($key, '_') !== false) {
                $parts = explode('_', strtolower($key));
                $camel = $parts[0];
                for ($i = 1; $i < count($parts); $i++) {
                    $camel .= ucfirst($parts[$i]);
                }
                $out[$camel] = $value;
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    public function inPlaceholders(array $ids, string $prefix): array {
        $params = array();
        $keys = array();
        foreach (array_values($ids) as $i => $id) {
            $key = $prefix . $i;
            $keys[] = ':' . $key;
            $params[$key] = intval($id);
        }
        return array($keys, $params);
    }

    public function parseIdList($value): array {
        if (is_array($value)) {
            $raw = $value;
        } else if ($value === null || $value === '' || $value === 'all') {
            return array();
        } else {
            $raw = explode(',', strval($value));
        }
        $ids = array();
        foreach ($raw as $item) {
            $id = intval($item);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    public function normalizeDate($val): ?string {
        if (!is_string($val) && !is_numeric($val)) {
            return null;
        }
        $val = trim(strval($val));
        if ($val === '' || $val === '0000-00-00') {
            return null;
        }
        foreach (array('Y-m-d', 'd/m/Y', 'd-m-Y') as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $val);
            $errors = DateTime::getLastErrors();
            $clean = $dt instanceof DateTime && (empty($errors['warning_count']) && empty($errors['error_count']));
            if ($clean && $dt->format($fmt) === $val) {
                return $dt->format('Y-m-d');
            }
        }
        return null;
    }
}
