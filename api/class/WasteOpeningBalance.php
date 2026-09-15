<?php

class WasteOpeningBalance extends WasteBase {

    public function list(?int $siteId = null): array {
        $sql = "SELECT o.opening_id, o.site_id, o.sw_code_id, o.as_at_date, o.qty, o.unit, o.qty_kg,
                       o.location_id, o.remarks, o.upload_id, o.opening_status,
                       o.opening_created_by, o.opening_created_at, o.opening_updated_at,
                       s.site_name, s.site_code, c.sw_code, c.sw_description, loc.location_name
                FROM wst_opening_balance o
                INNER JOIN cli_site s ON s.site_id = o.site_id
                INNER JOIN ref_sw_code c ON c.sw_code_id = o.sw_code_id
                LEFT JOIN wst_location loc ON loc.location_id = o.location_id
                WHERE o.opening_status = 1";
        $params = array();
        if (!$this->isAdministrator()) {
            $sql .= " AND o.site_id = :siteId";
            $params['siteId'] = $this->resolveSiteId();
        } else if ($siteId) {
            $sql .= " AND o.site_id = :siteId";
            $params['siteId'] = intval($siteId);
        }
        $sql .= " ORDER BY s.site_name, c.sw_code";
        $rows = $this->queryAll($sql, $params);
        foreach ($rows as &$row) {
            $row['recordedByName'] = $this->userDisplayName(intval($row['openingCreatedBy'] ?? 0));
            $row['qtyMt'] = $this->formatMt(floatval($row['qtyKg']));
        }
        return $rows;
    }

    public function save(array $columns, ?int $openingId = null): array {
        $this->requireOpening();
        parent::checkMandatoryArray($columns, array('swCodeId', 'asAtDate', 'qty', 'unit'));
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $qty = floatval($columns['qty']);
        if ($qty < 0) {
            throw new Exception('Opening quantity must be zero or greater.', 31);
        }
        $unit = strtoupper(trim(strval($columns['unit'])));
        if (!in_array($unit, array('KG', 'MT'), true)) {
            $unit = 'KG';
        }
        $asAt = $this->normalizeDate($columns['asAtDate']);
        if (!$asAt) {
            throw new Exception('Enter the opening balance as-at date.', 31);
        }
        $sw = $this->getSwCode(intval($columns['swCodeId']));
        $data = array(
            'site_id' => $siteId,
            'sw_code_id' => intval($sw['swCodeId']),
            'as_at_date' => $asAt,
            'qty' => $qty,
            'unit' => $unit,
            'qty_kg' => $this->toKg($qty, $unit),
            'location_id' => intval($columns['locationId'] ?? 0) ?: null,
            'remarks' => trim(strval($columns['remarks'] ?? '')),
            'opening_status' => 1
        );
        if (!empty($columns['fileUpload']) && is_array($columns['fileUpload'])) {
            $data['upload_id'] = $this->saveUpload($columns['fileUpload'], self::DOC_SUPPORTING, 'waste/opening', 'WOB');
        }
        $existing = $openingId
            ? DbMysql::select('wst_opening_balance', array('openingId' => $openingId), true)
            : DbMysql::select('wst_opening_balance', array('siteId' => $siteId, 'swCodeId' => intval($sw['swCodeId'])));
        if (!empty($existing)) {
            $this->assertSiteAccess(intval($existing['siteId']));
            $data['opening_updated_by'] = $this->userId;
            $id = intval($existing['openingId']);
            unset($data['site_id'], $data['sw_code_id']);
            DbMysql::update('wst_opening_balance', $data, array('opening_id' => $id));
            $this->writeHistory('OPENING_BALANCE', $id, 'AMEND', $existing, $data, $columns['reason'] ?? 'Updated opening balance', $siteId, $asAt);
            $openingId = $id;
        } else {
            $data['opening_created_by'] = $this->userId;
            $openingId = DbMysql::insert('wst_opening_balance', $data);
            $this->writeHistory('OPENING_BALANCE', $openingId, 'CREATE', null, $data, null, $siteId, $asAt);
        }
        $this->saveAudit(self::AUDIT_OPENING, 'Saved opening balance ' . $sw['swCode'] . ' for site ' . $siteId);
        $rows = $this->list($siteId);
        foreach ($rows as $row) {
            if (intval($row['openingId']) === intval($openingId)) {
                $row['reportImpact'] = $this->affectedReports($siteId, $asAt);
                return $row;
            }
        }
        return array('openingId' => $openingId);
    }
}
