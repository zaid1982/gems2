<?php

class WasteTransaction extends WasteBase {

    public function list(array $filters): array {
        $sql = "SELECT t.txn_id, t.txn_ref, t.site_id, t.txn_type, t.event_date, t.sw_code_id,
                       t.qty, t.unit, t.qty_kg, t.txn_status, t.external_ref, t.wo_ref,
                       t.source_activity, t.location_text, t.txn_created_at, t.finalised_at,
                       t.txn_created_by, t.finalised_by,
                       t.entry_mode, t.collection_status, t.disposal_txn_id, t.parent_txn_id,
                       s.site_name, s.site_code, c.sw_code, c.sw_description,
                       loc.location_name, hm.value_name AS handling_method,
                       (SELECT COUNT(*) FROM wst_transaction_document d WHERE d.txn_id = t.txn_id AND d.doc_status = 1) AS doc_count
                FROM wst_transaction t
                INNER JOIN cli_site s ON s.site_id = t.site_id
                INNER JOIN ref_sw_code c ON c.sw_code_id = t.sw_code_id
                LEFT JOIN wst_location loc ON loc.location_id = t.location_id
                LEFT JOIN wst_ref_value hm ON hm.ref_value_id = t.handling_method_id
                WHERE 1 = 1";
        $params = array();
        if (!$this->isAdministrator()) {
            $sql .= " AND t.site_id = :ownSite";
            $params['ownSite'] = $this->resolveSiteId();
        } else if (!empty($filters['siteId'])) {
            $sql .= " AND t.site_id = :siteId";
            $params['siteId'] = intval($filters['siteId']);
        }
        if (!empty($filters['from'])) {
            $sql .= " AND t.event_date >= :fromDate";
            $params['fromDate'] = $this->normalizeDate($filters['from']);
        }
        if (!empty($filters['to'])) {
            $sql .= " AND t.event_date <= :toDate";
            $params['toDate'] = $this->normalizeDate($filters['to']);
        }
        if (!empty($filters['type']) && in_array($filters['type'], array('P', 'D'), true)) {
            $sql .= " AND t.txn_type = :txnType";
            $params['txnType'] = $filters['type'];
        }
        if (!empty($filters['swCodeId'])) {
            $sql .= " AND t.sw_code_id = :swCodeId";
            $params['swCodeId'] = intval($filters['swCodeId']);
        }
        if (!empty($filters['status'])) {
            $sql .= " AND t.txn_status = :status";
            $params['status'] = strtoupper($filters['status']);
        }
        if (!empty($filters['ref'])) {
            $sql .= " AND (t.txn_ref LIKE :ref1 OR t.client_ref LIKE :ref2)";
            $params['ref1'] = '%' . $filters['ref'] . '%';
            $params['ref2'] = '%' . $filters['ref'] . '%';
        }
        if (!empty($filters['extRef'])) {
            $sql .= " AND t.external_ref LIKE :extRef";
            $params['extRef'] = '%' . $filters['extRef'] . '%';
        }
        if (!empty($filters['collectionStatus'])) {
            $sql .= " AND t.collection_status = :collectionStatus";
            $params['collectionStatus'] = strtoupper($filters['collectionStatus']);
        }
        if (!empty($filters['entryMode'])) {
            $sql .= " AND t.entry_mode = :entryMode";
            $params['entryMode'] = strtoupper($filters['entryMode']);
        }
        $sql .= " ORDER BY t.event_date DESC, t.txn_id DESC";
        $rows = $this->queryAll($sql, $params);
        foreach ($rows as &$row) {
            $row['hasEvidence'] = intval($row['docCount']) > 0;
            $row['balanceEffectKg'] = null;
            if ($row['txnStatus'] === 'FINAL') {
                $row['balanceEffectKg'] = $row['txnType'] === 'P' ? floatval($row['qtyKg']) : -floatval($row['qtyKg']);
            }
            $row['recordedByName'] = $this->userDisplayName(intval($row['txnCreatedBy']));
            $row['finalisedByName'] = $this->userDisplayName(intval($row['finalisedBy'] ?? 0));
        }
        return $rows;
    }

    public function get(int $txnId): array {
        $sql = "SELECT t.*, s.site_name, s.site_code, c.sw_code, c.sw_description, c.sw_group,
                       p.profile_alias, loc.location_name,
                       hm.value_name AS handling_method, pk.value_name AS packaging_type,
                       tr.value_name AS transporter_name, rc.value_name AS receiver_name
                FROM wst_transaction t
                INNER JOIN cli_site s ON s.site_id = t.site_id
                INNER JOIN ref_sw_code c ON c.sw_code_id = t.sw_code_id
                LEFT JOIN wst_waste_profile p ON p.profile_id = t.profile_id
                LEFT JOIN wst_location loc ON loc.location_id = t.location_id
                LEFT JOIN wst_ref_value hm ON hm.ref_value_id = t.handling_method_id
                LEFT JOIN wst_ref_value pk ON pk.ref_value_id = t.packaging_type_id
                LEFT JOIN wst_ref_value tr ON tr.ref_value_id = t.transporter_id
                LEFT JOIN wst_ref_value rc ON rc.ref_value_id = t.receiver_id
                WHERE t.txn_id = :id";
        $row = $this->queryOne($sql, array('id' => $txnId));
        if (empty($row)) {
            throw new Exception('Waste record not found.', 31);
        }
        $this->assertSiteAccess(intval($row['siteId']));
        $row['documents'] = $this->listDocuments($txnId);
        $row['history'] = $this->listHistory($txnId);
        $row['recordedByName'] = $this->userDisplayName(intval($row['txnCreatedBy'] ?? 0));
        $row['finalisedByName'] = $this->userDisplayName(intval($row['finalisedBy'] ?? 0));
        $row['updatedByName'] = $this->userDisplayName(intval($row['txnUpdatedBy'] ?? 0));
        $bal = (new WasteBalance());
        $bal->adopt($this);
        $preview = $bal->preview(intval($row['siteId']), intval($row['swCodeId']), $row['eventDate'], $row['txnType'], floatval($row['qtyKg']), $row['txnStatus'] === 'FINAL' ? $txnId : null);
        $row['balanceBeforeKg'] = $preview['balanceBeforeKg'];
        $row['projectedBalanceAfterKg'] = $row['txnType'] === 'P'
            ? round($preview['balanceBeforeKg'] + floatval($row['qtyKg']), 3)
            : round($preview['balanceBeforeKg'] - floatval($row['qtyKg']), 3);
        $row['affectedReports'] = $this->affectedReports(intval($row['siteId']), $row['eventDate']);
        $row['linked'] = $this->linkedRecord($row);
        return $row;
    }

    /**
     * Produced records expose their disposal, Disposed records expose their
     * originating generation. Used by the record detail screen.
     */
    private function linkedRecord(array $row): array {
        $linkedId = intval($row['disposalTxnId'] ?? 0) ?: intval($row['parentTxnId'] ?? 0);
        if ($linkedId <= 0) {
            return array();
        }
        $linked = $this->queryOne(
            "SELECT t.txn_id, t.txn_ref, t.txn_type, t.event_date, t.qty, t.unit, t.qty_kg, t.txn_status,
                    t.collection_status, t.consignment_note_ref, t.consignment_receipt_ref, t.disposal_remarks,
                    c.sw_code
             FROM wst_transaction t
             INNER JOIN ref_sw_code c ON c.sw_code_id = t.sw_code_id
             WHERE t.txn_id = :id",
            array('id' => $linkedId)
        );
        if (empty($linked)) {
            return array();
        }
        $linked['documents'] = $this->listDocuments($linkedId);
        return $linked;
    }

    public function create(array $columns): array {
        $this->requireRecord();
        $clientRef = trim(strval($columns['clientRef'] ?? ''));
        if ($clientRef !== '') {
            $existing = DbMysql::select('wst_transaction', array('clientRef' => $clientRef));
            if (!empty($existing)) {
                return $this->get(intval($existing['txnId']));
            }
        }
        $mapped = $this->mapColumns($columns, false);
        $mapped['txn_status'] = 'DRAFT';
        $mapped['txn_created_by'] = $this->userId;
        $mapped['txn_ref'] = $this->nextRef(intval($mapped['site_id']));
        if ($clientRef !== '') {
            $mapped['client_ref'] = $clientRef;
        }
        $txnId = DbMysql::insert('wst_transaction', $mapped);
        $this->writeHistory('TRANSACTION', $txnId, 'CREATE', null, $mapped, null, intval($mapped['site_id']), $mapped['event_date']);
        $this->saveAudit(self::AUDIT_CREATE, 'Created waste record ' . $mapped['txn_ref']);
        if (!empty($columns['documents']) && is_array($columns['documents'])) {
            foreach ($columns['documents'] as $doc) {
                $this->addDocument($txnId, $doc, false);
            }
        }
        return $this->get($txnId);
    }

    public function submit(array $columns): array {
        $txnId = intval($columns['txnId'] ?? 0);
        if ($txnId > 0) {
            $this->updateDraft($txnId, $columns);
        } else {
            $created = $this->create($columns);
            $txnId = intval($created['txnId']);
        }
        return $this->finalise($txnId);
    }

    public function updateDraft(int $txnId, array $columns): array {
        $this->requireRecord();
        $current = DbMysql::select('wst_transaction', array('txnId' => $txnId), true);
        $this->assertSiteAccess(intval($current['siteId']));
        if ($current['txnStatus'] !== 'DRAFT') {
            throw new Exception('Only Draft records can be edited in place.', 31);
        }
        $mapped = $this->mapColumns($columns, false, $current);
        $mapped['txn_updated_by'] = $this->userId;
        unset($mapped['site_id']);
        DbMysql::update('wst_transaction', $mapped, array('txn_id' => $txnId));
        $this->writeHistory('TRANSACTION', $txnId, 'UPDATE', $current, $mapped, null, intval($current['siteId']), $mapped['event_date'] ?? $current['eventDate']);
        $this->saveAudit(self::AUDIT_UPDATE, 'Updated waste record ' . $current['txnRef']);
        return $this->get($txnId);
    }

    public function finalise(int $txnId): array {
        $this->requireRecord();
        $current = DbMysql::select('wst_transaction', array('txnId' => $txnId), true);
        $this->assertSiteAccess(intval($current['siteId']));
        if ($current['txnStatus'] === 'FINAL') {
            return $this->get($txnId);
        }
        if ($current['txnStatus'] !== 'DRAFT') {
            throw new Exception('Only a Draft record can be finalised.', 31);
        }
        $this->validateForFinal($current);
        $dup = $this->duplicateCheck(array(
            'siteId' => intval($current['siteId']),
            'eventDate' => $current['eventDate'],
            'type' => $current['txnType'],
            'swCodeId' => intval($current['swCodeId']),
            'qty' => $current['qty'],
            'extRef' => $current['externalRef'] ?? '',
            'excludeId' => $txnId
        ));
        $bal = new WasteBalance();
        $bal->adopt($this);
        $bal->validateChronological(intval($current['siteId']), intval($current['swCodeId']), array(
            'eventDate' => $current['eventDate'],
            'txnType' => $current['txnType'],
            'qtyKg' => floatval($current['qtyKg'])
        ), $txnId);
        DbMysql::update('wst_transaction', array(
            'txn_status' => 'FINAL',
            'finalised_by' => $this->userId,
            'finalised_at' => date('Y-m-d H:i:s'),
            'txn_updated_by' => $this->userId
        ), array('txn_id' => $txnId));
        $this->writeHistory('TRANSACTION', $txnId, 'FINALISE', array('txnStatus' => 'DRAFT'), array('txnStatus' => 'FINAL'), null, intval($current['siteId']), $current['eventDate']);
        $this->saveAudit(self::AUDIT_FINALISE, 'Finalised waste record ' . $current['txnRef']);
        $result = $this->get($txnId);
        $result['possibleDuplicate'] = $dup;
        $result['reportImpact'] = $this->affectedReports(intval($current['siteId']), $current['eventDate']);
        return $result;
    }

    public function cancel(int $txnId, array $columns): array {
        $this->requireRecord();
        $reason = trim(strval($columns['reason'] ?? ''));
        if ($reason === '') {
            throw new Exception('Enter the reason for cancelling this record.', 31);
        }
        $current = DbMysql::select('wst_transaction', array('txnId' => $txnId), true);
        $this->assertSiteAccess(intval($current['siteId']));
        if ($current['txnStatus'] !== 'DRAFT') {
            throw new Exception('Only a Draft record can be cancelled.', 31);
        }
        DbMysql::update('wst_transaction', array(
            'txn_status' => 'CANCELLED',
            'cancelled_by' => $this->userId,
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancel_reason' => $reason,
            'txn_updated_by' => $this->userId
        ), array('txn_id' => $txnId));
        $this->writeHistory('TRANSACTION', $txnId, 'CANCEL', array('txnStatus' => 'DRAFT'), array('txnStatus' => 'CANCELLED'), $reason, intval($current['siteId']), $current['eventDate']);
        $this->saveAudit(self::AUDIT_CANCEL, 'Cancelled waste record ' . $current['txnRef']);
        return $this->get($txnId);
    }

    public function amend(int $txnId, array $columns): array {
        $this->requireAmend();
        $reason = trim(strval($columns['reason'] ?? ''));
        if ($reason === '') {
            throw new Exception('Enter the reason for changing this Final record.', 31);
        }
        $current = DbMysql::select('wst_transaction', array('txnId' => $txnId), true);
        $this->assertSiteAccess(intval($current['siteId']));
        if ($current['txnStatus'] !== 'FINAL') {
            throw new Exception('Only a Final record can be amended.', 31);
        }
        $mapped = $this->mapColumns($columns, true, $current);
        $this->validateForFinal(array_merge($current, $this->camelizeRow($mapped)));
        $bal = new WasteBalance();
        $bal->adopt($this);
        $bal->validateChronological(intval($mapped['site_id'] ?? $current['siteId']), intval($mapped['sw_code_id'] ?? $current['swCodeId']), array(
            'eventDate' => $mapped['event_date'] ?? $current['eventDate'],
            'txnType' => $mapped['txn_type'] ?? $current['txnType'],
            'qtyKg' => floatval($mapped['qty_kg'] ?? $current['qtyKg'])
        ), $txnId);
        $mapped['txn_updated_by'] = $this->userId;
        unset($mapped['txn_status']);
        DbMysql::update('wst_transaction', $mapped, array('txn_id' => $txnId));
        $eventDate = $mapped['event_date'] ?? $current['eventDate'];
        $siteId = intval($mapped['site_id'] ?? $current['siteId']);
        $this->writeHistory('TRANSACTION', $txnId, 'AMEND', $current, $mapped, $reason, $siteId, $eventDate);
        $this->saveAudit(self::AUDIT_AMEND, 'Amended waste record ' . $current['txnRef']);
        $result = $this->get($txnId);
        $result['reportImpact'] = $this->affectedReports($siteId, $eventDate);
        return $result;
    }

    public function addDocument(int $txnId, array $doc, bool $requirePermission = true): array {
        if ($requirePermission) {
            $this->requireRecord();
        }
        $current = DbMysql::select('wst_transaction', array('txnId' => $txnId), true);
        $this->assertSiteAccess(intval($current['siteId']));
        if (empty($doc['fileUpload']) || !is_array($doc['fileUpload'])) {
            throw new Exception('Attach a supporting document file.', 31);
        }
        if (empty($doc['documentTypeId']) && empty($doc['documentType'])) {
            throw new Exception('Select a document type.', 31);
        }
        $uploadId = $this->saveUpload($doc['fileUpload'], self::DOC_SUPPORTING, 'waste/evidence', 'WST');
        $docId = DbMysql::insert('wst_transaction_document', array(
            'txn_id' => $txnId,
            'upload_id' => $uploadId,
            'document_type_id' => intval($doc['documentTypeId'] ?? 0) ?: null,
            'doc_ref' => trim(strval($doc['docRef'] ?? '')),
            'doc_date' => $this->normalizeDate($doc['docDate'] ?? ''),
            'doc_description' => trim(strval($doc['description'] ?? '')),
            'doc_status' => 1,
            'doc_created_by' => $this->userId
        ));
        if ($current['txnStatus'] === 'FINAL') {
            $this->writeHistory('DOCUMENT', $docId, 'DOC_ADD', null, array('txnId' => $txnId, 'uploadId' => $uploadId), $doc['reason'] ?? 'Added evidence', intval($current['siteId']), $current['eventDate']);
        }
        return $this->get($txnId);
    }

    public function removeDocument(int $txnId, int $docId): array {
        $this->requireRecord();
        $current = DbMysql::select('wst_transaction', array('txnId' => $txnId), true);
        $this->assertSiteAccess(intval($current['siteId']));
        $doc = DbMysql::select('wst_transaction_document', array('docId' => $docId, 'txnId' => $txnId), true);
        DbMysql::update('wst_transaction_document', array('doc_status' => 2), array('doc_id' => $docId));
        $this->writeHistory('DOCUMENT', $docId, 'DOC_REMOVE', $doc, array('docStatus' => 2), 'Removed evidence', intval($current['siteId']), $current['eventDate']);
        return $this->get($txnId);
    }

    public function duplicateCheck(array $filters): array {
        $siteId = $this->resolveSiteId($filters['siteId'] ?? null);
        $sql = "SELECT t.txn_id, t.txn_ref, t.event_date, t.txn_type, t.qty, t.unit, t.txn_status, t.external_ref
                FROM wst_transaction t
                WHERE t.site_id = :siteId AND t.txn_status IN ('DRAFT','FINAL')";
        $params = array('siteId' => $siteId);
        if (!empty($filters['eventDate'])) {
            $sql .= " AND t.event_date = :eventDate";
            $params['eventDate'] = $this->normalizeDate($filters['eventDate']);
        }
        if (!empty($filters['type'])) {
            $sql .= " AND t.txn_type = :txnType";
            $params['txnType'] = $filters['type'];
        }
        if (!empty($filters['swCodeId'])) {
            $sql .= " AND t.sw_code_id = :swCodeId";
            $params['swCodeId'] = intval($filters['swCodeId']);
        }
        if (isset($filters['qty']) && $filters['qty'] !== '') {
            $sql .= " AND t.qty = :qty";
            $params['qty'] = $filters['qty'];
        }
        if (!empty($filters['extRef'])) {
            $sql .= " AND t.external_ref = :extRef";
            $params['extRef'] = $filters['extRef'];
        }
        if (!empty($filters['excludeId'])) {
            $sql .= " AND t.txn_id <> :excludeId";
            $params['excludeId'] = intval($filters['excludeId']);
        }
        $sql .= " ORDER BY t.txn_id DESC LIMIT 10";
        return $this->queryAll($sql, $params);
    }

    private function listDocuments(int $txnId): array {
        $sql = "SELECT d.doc_id, d.txn_id, d.upload_id, d.document_type_id, d.doc_ref, d.doc_date,
                       d.doc_description, d.doc_created_by, d.doc_created_at, r.value_name AS document_type
                FROM wst_transaction_document d
                LEFT JOIN wst_ref_value r ON r.ref_value_id = d.document_type_id
                WHERE d.txn_id = :id AND d.doc_status = 1
                ORDER BY d.doc_id";
        $rows = $this->queryAll($sql, array('id' => $txnId));
        foreach ($rows as &$row) {
            $row['uploadedByName'] = $this->userDisplayName(intval($row['docCreatedBy'] ?? 0));
        }
        return $rows;
    }

    private function listHistory(int $txnId): array {
        $sql = "SELECT h.history_id, h.action, h.old_values, h.new_values, h.reason, h.user_id, h.created_at
                FROM wst_history h
                WHERE (h.entity_type = 'TRANSACTION' AND h.entity_id = :id)
                   OR (h.entity_type = 'DOCUMENT' AND h.entity_id IN (
                        SELECT d.doc_id FROM wst_transaction_document d WHERE d.txn_id = :id2
                   ))
                ORDER BY h.history_id DESC";
        $rows = $this->queryAll($sql, array('id' => $txnId, 'id2' => $txnId));
        foreach ($rows as &$row) {
            $row['userName'] = $this->userDisplayName(intval($row['userId'] ?? 0));
            if (!empty($row['oldValues']) && is_string($row['oldValues'])) {
                $decoded = json_decode($row['oldValues'], true);
                if (is_array($decoded)) {
                    $row['oldValues'] = $decoded;
                }
            }
            if (!empty($row['newValues']) && is_string($row['newValues'])) {
                $decoded = json_decode($row['newValues'], true);
                if (is_array($decoded)) {
                    $row['newValues'] = $decoded;
                }
            }
        }
        return $rows;
    }

    private function nextRef(int $siteId): string {
        $year = intval(date('Y'));
        $site = $this->getSiteRow($siteId);
        $code = preg_replace('/[^A-Za-z0-9]/', '', strval($site['siteCode'] ?? $siteId));
        $stmt = DbMysql::$DBH->prepare("SELECT last_no FROM wst_number_sequence WHERE site_id = :siteId AND seq_year = :year FOR UPDATE");
        $stmt->execute(array('siteId' => $siteId, 'year' => $year));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;
        if ($row) {
            $next = intval($row['last_no']) + 1;
            DbMysql::update('wst_number_sequence', array('last_no' => $next), array('site_id' => $siteId, 'seq_year' => $year));
        } else {
            $next = 1;
            DbMysql::insert('wst_number_sequence', array('site_id' => $siteId, 'seq_year' => $year, 'last_no' => $next));
        }
        return 'WST-' . $code . '-' . $year . '-' . str_pad(strval($next), 5, '0', STR_PAD_LEFT);
    }

    private function mapColumns(array $columns, bool $isAmend, array $current = array()): array {
        $siteId = $this->resolveSiteId($columns['siteId'] ?? ($current['siteId'] ?? null));
        $this->assertSiteAccess($siteId);
        $txnType = strtoupper(strval($columns['txnType'] ?? ($current['txnType'] ?? '')));
        if (!in_array($txnType, array('P', 'D'), true)) {
            throw new Exception('Record the waste as Produced or Disposed.', 31);
        }
        $eventDate = $this->normalizeDate($columns['eventDate'] ?? ($current['eventDate'] ?? ''));
        if (!$eventDate) {
            throw new Exception('Enter the event date.', 31);
        }
        $swCodeId = intval($columns['swCodeId'] ?? ($current['swCodeId'] ?? 0));
        if ($swCodeId <= 0) {
            throw new Exception('Select an approved Scheduled Waste code.', 31);
        }
        $sw = $this->getSwCode($swCodeId);
        $qty = floatval($columns['qty'] ?? ($current['qty'] ?? 0));
        $unit = strtoupper(strval($columns['unit'] ?? ($current['unit'] ?? 'KG')));
        if (!in_array($unit, array('KG', 'MT'), true)) {
            $unit = 'KG';
        }
        $qtyKg = $this->toKg($qty, $unit);
        $profile = DbMysql::select('wst_waste_profile', array('siteId' => $siteId, 'swCodeId' => $swCodeId));
        $mapped = array(
            'site_id' => $siteId,
            'txn_type' => $txnType,
            'event_date' => $eventDate,
            'sw_code_id' => intval($sw['swCodeId']),
            'profile_id' => !empty($profile) ? intval($profile['profileId']) : null,
            'source_activity' => trim(strval($columns['sourceActivity'] ?? ($current['sourceActivity'] ?? ''))),
            'wo_ref' => trim(strval($columns['woRef'] ?? ($current['woRef'] ?? ''))),
            'handling_method_id' => intval($columns['handlingMethodId'] ?? ($current['handlingMethodId'] ?? 0)) ?: null,
            'location_id' => intval($columns['locationId'] ?? ($current['locationId'] ?? 0)) ?: null,
            'location_text' => trim(strval($columns['locationText'] ?? ($current['locationText'] ?? ''))),
            'remarks' => trim(strval($columns['remarks'] ?? ($current['remarks'] ?? ''))),
            'qty' => $qty,
            'unit' => $unit,
            'qty_kg' => $qtyKg,
            'packaging_type_id' => intval($columns['packagingTypeId'] ?? ($current['packagingTypeId'] ?? 0)) ?: null,
            'package_count' => isset($columns['packageCount']) ? intval($columns['packageCount']) : ($current['packageCount'] ?? null),
            'transporter_id' => intval($columns['transporterId'] ?? ($current['transporterId'] ?? 0)) ?: null,
            'transporter_text' => trim(strval($columns['transporterText'] ?? ($current['transporterText'] ?? ''))),
            'receiver_id' => intval($columns['receiverId'] ?? ($current['receiverId'] ?? 0)) ?: null,
            'receiver_text' => trim(strval($columns['receiverText'] ?? ($current['receiverText'] ?? ''))),
            'vehicle_reg' => trim(strval($columns['vehicleReg'] ?? ($current['vehicleReg'] ?? ''))),
            'external_ref' => trim(strval($columns['externalRef'] ?? ($current['externalRef'] ?? '')))
        );
        if (!$isAmend && $qty <= 0) {
            // drafts may be incomplete; keep stored qty if provided
        }
        return $mapped;
    }

    private function validateForFinal(array $row): void {
        $siteId = intval($row['siteId'] ?? $row['site_id'] ?? 0);
        $txnType = $row['txnType'] ?? $row['txn_type'] ?? '';
        $eventDate = $row['eventDate'] ?? $row['event_date'] ?? '';
        $swCodeId = intval($row['swCodeId'] ?? $row['sw_code_id'] ?? 0);
        $qty = floatval($row['qty'] ?? 0);
        $locationId = intval($row['locationId'] ?? $row['location_id'] ?? 0);
        $locationText = trim(strval($row['locationText'] ?? $row['location_text'] ?? ''));
        // SIMPLE records come from the Waste Generation / Disposal screens, which
        // do not collect the Fifth Schedule handling and location details.
        $isSimple = strtoupper(strval($row['entryMode'] ?? $row['entry_mode'] ?? 'REGISTER')) === 'SIMPLE';
        if ($siteId <= 0) {
            throw new Exception('Select a premise.', 31);
        }
        if ($swCodeId <= 0) {
            throw new Exception('Select an approved Scheduled Waste code.', 31);
        }
        $sw = $this->getSwCode($swCodeId);
        if (intval($sw['swStatus']) !== 1) {
            throw new Exception('Select an approved Scheduled Waste code.', 31);
        }
        $profile = DbMysql::select('wst_waste_profile', array('siteId' => $siteId, 'swCodeId' => $swCodeId));
        if (!empty($profile) && intval($profile['profileStatus']) !== 1) {
            throw new Exception('This waste profile is inactive and cannot be used for a new Final record.', 31);
        }
        if ($qty <= 0) {
            throw new Exception('Enter a quantity greater than zero.', 31);
        }
        if (!$eventDate) {
            throw new Exception('Enter the event date.', 31);
        }
        if ($eventDate > date('Y-m-d')) {
            throw new Exception('A completed transaction cannot use a future event date.', 31);
        }
        if (!$isSimple && $locationId <= 0 && $locationText === '') {
            throw new Exception('Enter the storage or handling location.', 31);
        }
        if ($isSimple) {
            // Disposal evidence is enforced by WasteGeneration::dispose().
            return;
        }
        $premise = DbMysql::select('wst_premise', array('siteId' => $siteId));
        $needEvidence = false;
        if (!empty($premise)) {
            if ($txnType === 'P' && !empty($premise['evidenceRequiredProduced'])) {
                $needEvidence = true;
            }
            if ($txnType === 'D' && !empty($premise['evidenceRequiredDisposed'])) {
                $needEvidence = true;
            }
        }
        if ($needEvidence) {
            $txnId = intval($row['txnId'] ?? 0);
            $docs = $txnId ? DbMysql::count('wst_transaction_document', array('txnId' => $txnId, 'docStatus' => 1)) : 0;
            if ($docs < 1) {
                throw new Exception('Attach the required supporting document before finalising this record.', 31);
            }
        }
    }
}
