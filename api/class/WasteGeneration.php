<?php

/**
 * Waste Generation -> Pending Collection -> Disposal lifecycle.
 *
 * Records live in wst_transaction so the balance calculator and the JKR report
 * keep working unchanged:
 *   - generation  = FINAL Produced, entry_mode SIMPLE, collection_status PENDING
 *   - disposal    = FINAL Disposed linked back through parent_txn_id
 * The registered weight stays on the Produced row; the actual disposed weight is
 * the official quantity of the Disposed row.
 */
class WasteGeneration extends WasteBase {

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_DISPOSED = 'DISPOSED';

    private const DOC_DURING = 'Disposal Image - During';
    private const DOC_AFTER = 'Disposal Image - After';
    private const DOC_NOTE = 'Consignment Note';
    private const DOC_RECEIPT = 'Consignment Receipt';

    /**
     * Pending queue / generation history.
     */
    public function list(array $filters): array {
        $sql = "SELECT t.txn_id, t.txn_ref, t.site_id, t.event_date, t.sw_code_id, t.qty, t.unit, t.qty_kg,
                       t.txn_status, t.collection_status, t.disposal_txn_id, t.remarks,
                       t.txn_created_by, t.txn_created_at,
                       s.site_name, s.site_code, c.sw_code, c.sw_description,
                       p.profile_alias,
                       d.txn_ref AS disposal_ref, d.event_date AS disposal_date, d.qty_kg AS disposed_kg,
                       d.consignment_note_ref, d.consignment_receipt_ref
                FROM wst_transaction t
                INNER JOIN cli_site s ON s.site_id = t.site_id
                INNER JOIN ref_sw_code c ON c.sw_code_id = t.sw_code_id
                LEFT JOIN wst_waste_profile p ON p.profile_id = t.profile_id
                LEFT JOIN wst_transaction d ON d.txn_id = t.disposal_txn_id
                WHERE t.txn_type = 'P' AND t.collection_status IS NOT NULL
                  AND t.txn_status = 'FINAL'";
        $params = array();
        if (!$this->isAdministrator()) {
            $sql .= " AND t.site_id = :ownSite";
            $params['ownSite'] = $this->resolveSiteId();
        } else if (!empty($filters['siteId'])) {
            $sql .= " AND t.site_id = :siteId";
            $params['siteId'] = intval($filters['siteId']);
        }
        $status = strtoupper(trim(strval($filters['status'] ?? '')));
        if (in_array($status, array(self::STATUS_PENDING, self::STATUS_DISPOSED), true)) {
            $sql .= " AND t.collection_status = :collectionStatus";
            $params['collectionStatus'] = $status;
        }
        if (!empty($filters['swCodeId'])) {
            $sql .= " AND t.sw_code_id = :swCodeId";
            $params['swCodeId'] = intval($filters['swCodeId']);
        }
        if (!empty($filters['from'])) {
            $sql .= " AND t.event_date >= :fromDate";
            $params['fromDate'] = $this->normalizeDate($filters['from']);
        }
        if (!empty($filters['to'])) {
            $sql .= " AND t.event_date <= :toDate";
            $params['toDate'] = $this->normalizeDate($filters['to']);
        }
        if (!empty($filters['ref'])) {
            $sql .= " AND t.txn_ref LIKE :ref";
            $params['ref'] = '%' . $filters['ref'] . '%';
        }
        $sql .= " ORDER BY t.event_date DESC, t.txn_id DESC";
        $rows = $this->queryAll($sql, $params);
        foreach ($rows as &$row) {
            $row['wasteType'] = $row['swCode'] . ($row['profileAlias'] ? ' — ' . $row['profileAlias'] : '');
            $row['registeredKg'] = floatval($row['qtyKg']);
            $row['disposedKg'] = $row['disposedKg'] === null ? null : floatval($row['disposedKg']);
            $row['canDispose'] = $row['collectionStatus'] === self::STATUS_PENDING;
            $row['recordedByName'] = $this->userDisplayName(intval($row['txnCreatedBy'] ?? 0));
        }
        return $rows;
    }

    /**
     * Pending kilograms grouped by waste type.
     */
    public function pendingSummary(array $filters): array {
        $sql = "SELECT c.sw_code, c.sw_description, SUM(t.qty_kg) AS pending_kg, COUNT(*) AS record_count
                FROM wst_transaction t
                INNER JOIN ref_sw_code c ON c.sw_code_id = t.sw_code_id
                WHERE t.txn_type = 'P' AND t.txn_status = 'FINAL'
                  AND t.collection_status = 'PENDING'";
        $params = array();
        if (!$this->isAdministrator()) {
            $sql .= " AND t.site_id = :ownSite";
            $params['ownSite'] = $this->resolveSiteId();
        } else if (!empty($filters['siteId'])) {
            $sql .= " AND t.site_id = :siteId";
            $params['siteId'] = intval($filters['siteId']);
        }
        $sql .= " GROUP BY c.sw_code, c.sw_description ORDER BY c.sw_code";
        $rows = $this->queryAll($sql, $params);
        foreach ($rows as &$row) {
            $row['pendingKg'] = floatval($row['pendingKg']);
            $row['recordCount'] = intval($row['recordCount']);
        }
        return $rows;
    }

    public function get(int $txnId): array {
        $sql = "SELECT t.*, s.site_name, s.site_code, c.sw_code, c.sw_description, c.sw_group, p.profile_alias
                FROM wst_transaction t
                INNER JOIN cli_site s ON s.site_id = t.site_id
                INNER JOIN ref_sw_code c ON c.sw_code_id = t.sw_code_id
                LEFT JOIN wst_waste_profile p ON p.profile_id = t.profile_id
                WHERE t.txn_id = :id";
        $row = $this->queryOne($sql, array('id' => $txnId));
        if (empty($row)) {
            throw new Exception('Waste record not found.', 31);
        }
        $this->assertSiteAccess(intval($row['siteId']));
        $row['registeredKg'] = floatval($row['qtyKg']);
        $row['recordedByName'] = $this->userDisplayName(intval($row['txnCreatedBy'] ?? 0));
        $row['documents'] = $this->documents($txnId);
        $row['history'] = $this->history($txnId);
        $row['disposal'] = array();
        if (!empty($row['disposalTxnId'])) {
            $disposal = $this->queryOne($sql, array('id' => intval($row['disposalTxnId'])));
            if (!empty($disposal)) {
                $disposal['documents'] = $this->documents(intval($row['disposalTxnId']));
                $disposal['recordedByName'] = $this->userDisplayName(intval($disposal['txnCreatedBy'] ?? 0));
                $row['disposal'] = $disposal;
            }
        }
        return $row;
    }

    /**
     * Create a generation record. Saved straight to FINAL so the official
     * balance and the JKR register include it immediately.
     */
    public function create(array $columns): array {
        $this->requireRecord();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $swCodeId = intval($columns['swCodeId'] ?? 0);
        $qtyKg = $this->readWeight($columns);
        $eventDate = $this->readEventDate($columns);
        $sw = $this->validateWasteType($siteId, $swCodeId);
        $premise = DbMysql::select('wst_premise', array('siteId' => $siteId));
        $profile = DbMysql::select('wst_waste_profile', array('siteId' => $siteId, 'swCodeId' => $swCodeId));

        $data = array(
            'txn_ref' => $this->nextRef($siteId),
            'site_id' => $siteId,
            'txn_type' => 'P',
            'entry_mode' => 'SIMPLE',
            'collection_status' => self::STATUS_PENDING,
            'event_date' => $eventDate,
            'sw_code_id' => $swCodeId,
            'profile_id' => !empty($profile) ? intval($profile['profileId']) : null,
            'handling_method_id' => !empty($premise['defaultHandlingMethodId']) ? intval($premise['defaultHandlingMethodId']) : null,
            'location_id' => !empty($premise['defaultLocationId']) ? intval($premise['defaultLocationId']) : null,
            'remarks' => $this->sanitizeText($columns['remarks'] ?? ''),
            'qty' => $qtyKg,
            'unit' => 'KG',
            'qty_kg' => $qtyKg,
            'txn_status' => 'FINAL',
            'finalised_by' => $this->userId,
            'finalised_at' => date('Y-m-d H:i:s'),
            'txn_created_by' => $this->userId
        );
        $txnId = DbMysql::insert('wst_transaction', $data);
        $this->writeHistory('TRANSACTION', $txnId, 'GENERATE', null, $data, null, $siteId, $eventDate);
        $this->saveAudit(self::AUDIT_GENERATE, 'Generated waste ' . $data['txn_ref'] . ' (' . $sw['swCode'] . ', ' . $qtyKg . ' kg)');
        return $this->get($txnId);
    }

    /**
     * Correct a pending record: weight, date or waste type.
     */
    public function update(int $txnId, array $columns): array {
        $this->requireRecord();
        $current = $this->requirePending($txnId);
        $siteId = intval($current['siteId']);
        $swCodeId = intval($columns['swCodeId'] ?? $current['swCodeId']);
        $qtyKg = isset($columns['qtyKg']) || isset($columns['qty']) ? $this->readWeight($columns) : floatval($current['qtyKg']);
        $eventDate = isset($columns['eventDate']) ? $this->readEventDate($columns) : $current['eventDate'];
        $this->validateWasteType($siteId, $swCodeId);
        $profile = DbMysql::select('wst_waste_profile', array('siteId' => $siteId, 'swCodeId' => $swCodeId));

        $data = array(
            'sw_code_id' => $swCodeId,
            'profile_id' => !empty($profile) ? intval($profile['profileId']) : null,
            'event_date' => $eventDate,
            'qty' => $qtyKg,
            'unit' => 'KG',
            'qty_kg' => $qtyKg,
            'remarks' => isset($columns['remarks']) ? $this->sanitizeText($columns['remarks']) : $this->sanitizeText($current['remarks'] ?? ''),
            'txn_updated_by' => $this->userId
        );
        DbMysql::update('wst_transaction', $data, array('txn_id' => $txnId));
        $this->writeHistory('TRANSACTION', $txnId, 'UPDATE', $current, $data, $columns['reason'] ?? null, $siteId, $eventDate);
        $this->saveAudit(self::AUDIT_GENERATE_EDIT, 'Edited pending waste ' . $current['txnRef']);
        return $this->get($txnId);
    }

    /**
     * Delete a pending record. The row is cancelled, never physically removed.
     */
    public function delete(int $txnId, array $columns): array {
        $this->requireRecord();
        $current = $this->requirePending($txnId);
        $reason = $this->sanitizeText($columns['reason'] ?? '', 500);
        if ($reason === '') {
            throw new Exception('Enter the reason for deleting this pending waste record.', 31);
        }
        DbMysql::update('wst_transaction', array(
            'txn_status' => 'CANCELLED',
            'collection_status' => 'NULL',
            'cancelled_by' => $this->userId,
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancel_reason' => $reason,
            'txn_updated_by' => $this->userId
        ), array('txn_id' => $txnId));
        $this->writeHistory('TRANSACTION', $txnId, 'DELETE', $current, array('txnStatus' => 'CANCELLED'), $reason, intval($current['siteId']), $current['eventDate']);
        $this->saveAudit(self::AUDIT_GENERATE_DELETE, 'Deleted pending waste ' . $current['txnRef']);
        return array('txnId' => $txnId, 'txnRef' => $current['txnRef'], 'txnStatus' => 'CANCELLED');
    }

    /**
     * Execute the disposal for a pending record.
     */
    public function dispose(int $txnId, array $columns): array {
        $this->requireRecord();
        $current = $this->requirePending($txnId);
        $siteId = intval($current['siteId']);
        $swCodeId = intval($current['swCodeId']);

        $actualKg = round(floatval($columns['actualQtyKg'] ?? 0), 3);
        if ($actualKg <= 0) {
            throw new Exception('Enter the actual disposed weight in kilograms.', 31);
        }
        $disposalDate = $this->normalizeDate($columns['disposalDate'] ?? '');
        if (!$disposalDate) {
            throw new Exception('Enter the disposal date.', 31);
        }
        if ($disposalDate > date('Y-m-d')) {
            throw new Exception('The disposal date cannot be in the future.', 31);
        }
        if ($disposalDate < $current['eventDate']) {
            throw new Exception('The disposal date cannot be earlier than the generation date.', 31);
        }
        $during = $this->requireImage($columns, array('duringImage', 'during'), 'during disposal');
        $after = $this->requireImage($columns, array('afterImage', 'after'), 'after disposal');

        // The disposal cannot take more than the premise holds for this waste code.
        $bal = new WasteBalance();
        $bal->adopt($this);
        $bal->validateChronological($siteId, $swCodeId, array(
            'eventDate' => $disposalDate,
            'txnType' => 'D',
            'qtyKg' => $actualKg
        ));

        $noteRef = $this->sanitizeText($columns['consignmentNoteRef'] ?? '', 80);
        $receiptRef = $this->sanitizeText($columns['consignmentReceiptRef'] ?? '', 80);
        $data = array(
            'txn_ref' => $this->nextRef($siteId),
            'site_id' => $siteId,
            'txn_type' => 'D',
            'entry_mode' => 'SIMPLE',
            'parent_txn_id' => $txnId,
            'event_date' => $disposalDate,
            'sw_code_id' => $swCodeId,
            'profile_id' => !empty($current['profileId']) ? intval($current['profileId']) : null,
            'handling_method_id' => !empty($current['handlingMethodId']) ? intval($current['handlingMethodId']) : null,
            'location_id' => !empty($current['locationId']) ? intval($current['locationId']) : null,
            'qty' => $actualKg,
            'unit' => 'KG',
            'qty_kg' => $actualKg,
            'consignment_note_ref' => $noteRef,
            'consignment_receipt_ref' => $receiptRef,
            'disposal_remarks' => $this->sanitizeText($columns['remarks'] ?? '', 500),
            'txn_status' => 'FINAL',
            'finalised_by' => $this->userId,
            'finalised_at' => date('Y-m-d H:i:s'),
            'txn_created_by' => $this->userId
        );
        $disposalId = DbMysql::insert('wst_transaction', $data);

        $this->attachTyped($disposalId, self::DOC_DURING, $during, $disposalDate);
        $this->attachTyped($disposalId, self::DOC_AFTER, $after, $disposalDate);
        $note = $this->readImage($columns, array('consignmentNote', 'note'));
        if ($note) {
            $this->attachTyped($disposalId, self::DOC_NOTE, $note, $disposalDate, $noteRef);
        }
        $receipt = $this->readImage($columns, array('consignmentReceipt', 'receipt'));
        if ($receipt) {
            $this->attachTyped($disposalId, self::DOC_RECEIPT, $receipt, $disposalDate, $receiptRef);
        }

        DbMysql::update('wst_transaction', array(
            'collection_status' => self::STATUS_DISPOSED,
            'disposal_txn_id' => $disposalId,
            'txn_updated_by' => $this->userId
        ), array('txn_id' => $txnId));

        $this->writeHistory('TRANSACTION', $disposalId, 'DISPOSE', null, $data, null, $siteId, $disposalDate);
        $this->writeHistory('TRANSACTION', $txnId, 'DISPOSED', array('collectionStatus' => self::STATUS_PENDING), array('collectionStatus' => self::STATUS_DISPOSED, 'disposalTxnId' => $disposalId), null, $siteId, $disposalDate);
        $this->saveAudit(self::AUDIT_DISPOSE, 'Disposed waste ' . $current['txnRef'] . ' as ' . $data['txn_ref'] . ' (' . $actualKg . ' kg)');
        return $this->get($txnId);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * DbMysql treats a value starting with "|" as raw SQL, so free text must
     * never be written with a leading pipe.
     */
    private function sanitizeText($value, int $maxLength = 0): string {
        $text = ltrim(trim(strval($value ?? '')), '|');
        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }
        return $text;
    }

    private function requirePending(int $txnId): array {
        $current = DbMysql::select('wst_transaction', array('txnId' => $txnId), true);
        $this->assertSiteAccess(intval($current['siteId']));
        if ($current['txnType'] !== 'P') {
            throw new Exception('This is not a waste generation record.', 31);
        }
        if ($current['txnStatus'] !== 'FINAL') {
            throw new Exception('This waste record is no longer active.', 31);
        }
        if ($current['collectionStatus'] !== self::STATUS_PENDING) {
            throw new Exception('This waste record is already disposed and can no longer be changed.', 31);
        }
        return $current;
    }

    private function readWeight(array $columns): float {
        $raw = $columns['qtyKg'] ?? ($columns['qty'] ?? null);
        $qtyKg = round(floatval($raw), 3);
        if ($qtyKg <= 0) {
            throw new Exception('Enter a waste weight greater than zero.', 31);
        }
        return $qtyKg;
    }

    private function readEventDate(array $columns): string {
        $eventDate = $this->normalizeDate($columns['eventDate'] ?? ($columns['date'] ?? ''));
        if (!$eventDate) {
            throw new Exception('Enter the waste generation date.', 31);
        }
        if ($eventDate > date('Y-m-d')) {
            throw new Exception('The waste generation date cannot be in the future.', 31);
        }
        return $eventDate;
    }

    private function validateWasteType(int $siteId, int $swCodeId): array {
        if ($swCodeId <= 0) {
            throw new Exception('Select a waste type.', 31);
        }
        $sw = $this->getSwCode($swCodeId);
        if (empty($sw) || intval($sw['swStatus']) !== 1) {
            throw new Exception('Select an active waste type.', 31);
        }
        $profile = DbMysql::select('wst_waste_profile', array('siteId' => $siteId, 'swCodeId' => $swCodeId));
        if (!empty($profile) && intval($profile['profileStatus']) !== 1) {
            throw new Exception('This waste type is inactive for the premise.', 31);
        }
        return $sw;
    }

    /**
     * @return array|null the fileUpload payload, or null when nothing was sent
     */
    private function readImage(array $columns, array $keys): ?array {
        foreach ($keys as $key) {
            if (empty($columns[$key])) {
                continue;
            }
            $value = $columns[$key];
            if (isset($value['fileUpload']) && is_array($value['fileUpload'])) {
                return $value['fileUpload'];
            }
            if (isset($value['data'])) {
                return $value;
            }
        }
        return null;
    }

    private function requireImage(array $columns, array $keys, string $label): array {
        $image = $this->readImage($columns, $keys);
        if ($image === null) {
            throw new Exception('Attach the ' . $label . ' image before saving.', 31);
        }
        return $image;
    }

    private function attachTyped(int $txnId, string $typeName, array $fileUpload, ?string $docDate = null, string $docRef = ''): void {
        $uploadId = $this->saveUpload($fileUpload, self::DOC_SUPPORTING, 'waste/disposal', 'WSD');
        DbMysql::insert('wst_transaction_document', array(
            'txn_id' => $txnId,
            'upload_id' => $uploadId,
            'document_type_id' => $this->documentTypeId($typeName),
            'doc_ref' => $docRef,
            'doc_date' => $docDate,
            'doc_description' => $typeName,
            'doc_status' => 1,
            'doc_created_by' => $this->userId
        ));
    }

    private function documentTypeId(string $typeName): ?int {
        $row = $this->queryOne(
            "SELECT ref_value_id FROM wst_ref_value
             WHERE value_type = 'DOCUMENT_TYPE' AND value_name = :name AND value_status = 1
             ORDER BY site_id IS NULL DESC LIMIT 1",
            array('name' => $typeName)
        );
        return empty($row) ? null : intval($row['refValueId']);
    }

    private function documents(int $txnId): array {
        $rows = $this->queryAll(
            "SELECT d.doc_id, d.txn_id, d.upload_id, d.document_type_id, d.doc_ref, d.doc_date,
                    d.doc_description, d.doc_created_by, d.doc_created_at, r.value_name AS document_type
             FROM wst_transaction_document d
             LEFT JOIN wst_ref_value r ON r.ref_value_id = d.document_type_id
             WHERE d.txn_id = :id AND d.doc_status = 1
             ORDER BY d.doc_id",
            array('id' => $txnId)
        );
        foreach ($rows as &$row) {
            $row['uploadedByName'] = $this->userDisplayName(intval($row['docCreatedBy'] ?? 0));
        }
        return $rows;
    }

    private function history(int $txnId): array {
        $rows = $this->queryAll(
            "SELECT h.history_id, h.action, h.old_values, h.new_values, h.reason, h.user_id, h.created_at
             FROM wst_history h
             WHERE h.entity_type = 'TRANSACTION' AND h.entity_id = :id
             ORDER BY h.history_id DESC",
            array('id' => $txnId)
        );
        foreach ($rows as &$row) {
            $row['userName'] = $this->userDisplayName(intval($row['userId'] ?? 0));
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
}
