<?php

class WasteReport extends WasteBase {

    public function list(?int $siteId = null): array {
        $sql = "SELECT r.report_id, r.site_id, r.period_start, r.period_end, r.as_at_date, r.version_no,
                       r.include_detail, r.pdf_upload_id, r.excel_upload_id, r.generated_by, r.generated_at,
                       s.site_name, s.site_code,
                       (SELECT COUNT(*) FROM wst_report_submission sub WHERE sub.report_id = r.report_id) AS submission_count
                FROM wst_report r
                INNER JOIN cli_site s ON s.site_id = r.site_id
                WHERE 1 = 1";
        $params = array();
        if (!$this->isAdministrator()) {
            $sql .= " AND r.site_id = :siteId";
            $params['siteId'] = $this->resolveSiteId();
        } else if ($siteId) {
            $sql .= " AND r.site_id = :siteId";
            $params['siteId'] = intval($siteId);
        }
        $sql .= " ORDER BY r.period_start DESC, r.version_no DESC";
        $rows = $this->queryAll($sql, $params);
        foreach ($rows as &$row) {
            $row['generatedByName'] = $this->userDisplayName(intval($row['generatedBy'] ?? 0));
            $row['changedSinceGenerated'] = $this->changedSince($row);
        }
        return $rows;
    }

    public function get(int $reportId): array {
        $sql = "SELECT r.*, s.site_name, s.site_code, s.site_desc
                FROM wst_report r
                INNER JOIN cli_site s ON s.site_id = r.site_id
                WHERE r.report_id = :id";
        $row = $this->queryOne($sql, array('id' => $reportId));
        if (empty($row)) {
            throw new Exception('JKR report not found.', 31);
        }
        $this->assertSiteAccess(intval($row['siteId']));
        $row['summary'] = json_decode($row['summaryJson'] ?? '[]', true) ?: array();
        $row['detail'] = json_decode($row['detailJson'] ?? '[]', true) ?: array();
        $row['generatedByName'] = $this->userDisplayName(intval($row['generatedBy'] ?? 0));
        $row['changedSinceGenerated'] = $this->changedSince($row);
        $row['submissions'] = $this->queryAll(
            "SELECT sub.submission_id, sub.submitted_at, sub.recipient, sub.channel_id, sub.submission_ref,
                    sub.evidence_upload_id, sub.notes, sub.created_by, v.value_name AS channel_name
             FROM wst_report_submission sub
             LEFT JOIN wst_ref_value v ON v.ref_value_id = sub.channel_id
             WHERE sub.report_id = :id
             ORDER BY sub.submission_id DESC",
            array('id' => $reportId)
        );
        return $row;
    }

    public function preview(array $columns): array {
        $this->requireReport();
        return $this->buildSnapshot($columns);
    }

    public function generate(array $columns): array {
        $this->requireReport();
        $snap = $this->buildSnapshot($columns);
        $siteId = intval($snap['header']['siteId']);
        $max = $this->queryOne(
            "SELECT COALESCE(MAX(version_no),0) AS max_ver
             FROM wst_report
             WHERE site_id = :siteId AND period_start = :ps AND period_end = :pe",
            array('siteId' => $siteId, 'ps' => $snap['header']['periodFrom'], 'pe' => $snap['header']['periodTo'])
        );
        $version = intval($max['maxVer'] ?? 0) + 1;
        $snap['header']['versionNo'] = $version;
        $snap['header']['generatedAt'] = date('Y-m-d H:i:s');
        $snap['header']['generatedByName'] = $this->userDisplayName($this->userId);

        $folder = 'upload/waste/report';
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $stamp = date('YmdHis') . '_WST_' . $siteId . '_v' . $version;
        $pdfPath = $folder . '/' . $stamp . '.pdf';
        $xlsxPath = $folder . '/' . $stamp . '.xlsx';

        require_once __DIR__ . '/../pdf/waste_report.php';
        PdfWasteReport::renderToFile($snap, $pdfPath);
        $this->writeExcel($snap, $xlsxPath);

        $pdfUploadId = $this->registerFile($pdfPath, $stamp, 'pdf', 'application/pdf', self::DOC_REPORT);
        $excelUploadId = $this->registerFile($xlsxPath, $stamp, 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', self::DOC_REPORT);

        $reportId = DbMysql::insert('wst_report', array(
            'site_id' => $siteId,
            'period_start' => $snap['header']['periodFrom'],
            'period_end' => $snap['header']['periodTo'],
            'as_at_date' => $snap['header']['asAt'],
            'version_no' => $version,
            'include_detail' => !empty($columns['includeDetail']) ? 1 : 0,
            'include_zero' => !empty($columns['includeZero']) ? 1 : 0,
            'summary_json' => json_encode($snap['summary']),
            'detail_json' => json_encode($snap['detail']),
            'pdf_upload_id' => $pdfUploadId,
            'excel_upload_id' => $excelUploadId,
            'generated_by' => $this->userId,
            'remarks' => trim(strval($columns['remarks'] ?? ''))
        ));
        $this->saveAudit(self::AUDIT_REPORT, 'Generated JKR waste report v' . $version . ' for site ' . $siteId);
        return $this->get($reportId);
    }

    public function recordSubmission(int $reportId, array $columns): array {
        $this->requireReport();
        $report = $this->get($reportId);
        parent::checkMandatoryArray($columns, array('submittedAt', 'recipient'));
        $evidenceId = null;
        if (!empty($columns['fileUpload']) && is_array($columns['fileUpload'])) {
            $evidenceId = $this->saveUpload($columns['fileUpload'], self::DOC_SUBMISSION, 'waste/submission', 'WJS');
        }
        DbMysql::insert('wst_report_submission', array(
            'report_id' => $reportId,
            'submitted_at' => $this->normalizeDate($columns['submittedAt']) . ' ' . date('H:i:s'),
            'recipient' => trim(strval($columns['recipient'])),
            'channel_id' => intval($columns['channelId'] ?? 0) ?: null,
            'submission_ref' => trim(strval($columns['submissionRef'] ?? '')),
            'evidence_upload_id' => $evidenceId,
            'notes' => trim(strval($columns['notes'] ?? '')),
            'created_by' => $this->userId
        ));
        $this->saveAudit(self::AUDIT_SUBMIT, 'Recorded JKR submission for report ' . $reportId);
        return $this->get($reportId);
    }

    private function buildSnapshot(array $columns): array {
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $periodFrom = $this->normalizeDate($columns['periodFrom'] ?? '') ?: date('Y-m-01');
        $periodTo = $this->normalizeDate($columns['periodTo'] ?? '') ?: date('Y-m-t');
        $asAt = $this->normalizeDate($columns['asAt'] ?? '') ?: $periodTo;
        $includeDetail = !isset($columns['includeDetail']) || !empty($columns['includeDetail']);
        $includeZero = !empty($columns['includeZero']);

        $premise = (new WasteReference());
        $premise->adopt($this);
        $premiseRow = $premise->getPremise($siteId);

        $bal = new WasteBalance();
        $bal->adopt($this);
        $summary = $bal->summarise(array($siteId), array(), $periodFrom, $periodTo, $asAt);
        $lines = $summary['lines'];
        if ($includeZero) {
            $profiles = $premise->listProfiles($siteId);
            $have = array();
            foreach ($lines as $line) {
                $have[intval($line['swCodeId'])] = true;
            }
            foreach ($profiles as $profile) {
                $id = intval($profile['swCodeId']);
                if (isset($have[$id])) {
                    continue;
                }
                $lines[] = array(
                    'siteId' => $siteId,
                    'swCodeId' => $id,
                    'swCode' => $profile['swCode'],
                    'swDescription' => $profile['swDescription'],
                    'openingKg' => 0,
                    'producedKg' => 0,
                    'disposedKg' => 0,
                    'closingKg' => 0,
                    'remarks' => 'Zero activity'
                );
            }
        }
        if (empty($lines)) {
            $lines[] = array(
                'siteId' => $siteId,
                'swCodeId' => 0,
                'swCode' => '-',
                'swDescription' => 'No confirmed transactions',
                'openingKg' => 0,
                'producedKg' => 0,
                'disposedKg' => 0,
                'closingKg' => 0,
                'remarks' => 'Zero-activity report'
            );
        }

        $detail = array();
        if ($includeDetail) {
            $txn = new WasteTransaction();
            $txn->adopt($this);
            $detail = $txn->list(array(
                'siteId' => $siteId,
                'from' => $periodFrom,
                'to' => $periodTo,
                'status' => 'FINAL'
            ));
        }

        return array(
            'title' => 'JKR Scheduled Waste Report',
            'header' => array(
                'siteId' => $siteId,
                'siteName' => $premiseRow['siteName'] ?? '',
                'siteCode' => $premiseRow['siteCode'] ?? '',
                'address' => $premiseRow['premiseAddress'] ?? ($premiseRow['siteDesc'] ?? ''),
                'contact' => $premiseRow['premiseContactNo'] ?? '',
                'periodFrom' => $periodFrom,
                'periodTo' => $periodTo,
                'asAt' => $asAt,
                'versionNo' => 'Preview',
                'generatedByName' => $this->userDisplayName($this->userId),
                'generatedAt' => date('Y-m-d H:i:s')
            ),
            'summary' => $lines,
            'detail' => $detail,
            'totals' => array(
                'openingKg' => $summary['openingKg'],
                'producedKg' => $summary['producedKg'],
                'disposedKg' => $summary['disposedKg'],
                'closingKg' => $summary['closingKg']
            )
        );
    }

    private function changedSince(array $report): bool {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS cnt
             FROM wst_history
             WHERE site_id = :siteId
               AND created_at > :generatedAt
               AND event_date IS NOT NULL
               AND event_date BETWEEN :ps AND :pe
               AND action IN ('FINALISE','AMEND','CREATE')",
            array(
                'siteId' => intval($report['siteId']),
                'generatedAt' => $report['generatedAt'],
                'ps' => $report['periodStart'],
                'pe' => $report['periodEnd']
            )
        );
        return intval($row['cnt'] ?? 0) > 0;
    }

    private function registerFile(string $path, string $filename, string $ext, string $mime, int $documentId): int {
        return DbMysql::insert('sys_upload', array(
            'document_id' => $documentId,
            'upload_name' => 'JKR Waste Report',
            'upload_uplname' => $filename . '.' . $ext,
            'upload_filename' => $filename,
            'upload_extension' => $ext,
            'upload_folder' => dirname($path),
            'upload_filesize' => file_exists($path) ? filesize($path) : 0,
            'upload_blob_type' => $mime,
            'upload_created_by' => $this->userId,
            'upload_status' => 1
        ));
    }

    private function writeExcel(array $snap, string $filePath): void {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception('PhpSpreadsheet is not installed. Run composer install.', 31);
        }
        require_once $autoload;
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Summary');
        $h = $snap['header'];
        $sheet->setCellValue('A1', 'GEMS Scheduled Waste Register — JKR Report');
        $sheet->setCellValue('A2', $h['siteName'] . ' (' . $h['siteCode'] . ')');
        $sheet->setCellValue('A3', 'Period ' . $h['periodFrom'] . ' to ' . $h['periodTo'] . '  |  As-at ' . $h['asAt'] . '  |  Version ' . $h['versionNo']);
        $sheet->setCellValue('A4', 'Generated ' . $h['generatedAt'] . ' by ' . $h['generatedByName']);
        $headers = array('SW Code', 'Official Description', 'Opening (kg)', 'Produced (kg)', 'Disposed (kg)', 'Closing (kg)', 'Opening (MT)', 'Produced (MT)', 'Disposed (MT)', 'Closing (MT)', 'Remarks');
        $sheet->fromArray($headers, null, 'A6');
        $r = 7;
        foreach ($snap['summary'] as $line) {
            $sheet->fromArray(array(
                $line['swCode'], $line['swDescription'],
                $line['openingKg'], $line['producedKg'], $line['disposedKg'], $line['closingKg'],
                isset($line['openingMt']) ? $line['openingMt'] : round($line['openingKg'] / 1000, 6),
                isset($line['producedMt']) ? $line['producedMt'] : round($line['producedKg'] / 1000, 6),
                isset($line['disposedMt']) ? $line['disposedMt'] : round($line['disposedKg'] / 1000, 6),
                isset($line['closingMt']) ? $line['closingMt'] : round($line['closingKg'] / 1000, 6),
                $line['remarks'] ?? ''
            ), null, 'A' . $r);
            $r++;
        }
        $sheet->setCellValue('A' . ($r + 1), 'Opening + Produced - Disposed = Closing. Full precision aggregated in kg.');

        if (!empty($snap['detail'])) {
            $detail = $spreadsheet->createSheet();
            $detail->setTitle('Appendix');
            $detail->fromArray(array(
                'Reference', 'Event Date', 'Type', 'Premise', 'SW Code', 'Description',
                'Quantity', 'Unit', 'Qty (kg)', 'Location', 'External Ref', 'Status', 'Has Evidence'
            ), null, 'A1');
            $d = 2;
            foreach ($snap['detail'] as $row) {
                $detail->fromArray(array(
                    $row['txnRef'], $row['eventDate'], $row['txnType'] === 'P' ? 'Produced' : 'Disposed',
                    $row['siteCode'] ?? '', $row['swCode'], $row['swDescription'] ?? '',
                    $row['qty'], $row['unit'], $row['qtyKg'],
                    $row['locationName'] ?? ($row['locationText'] ?? ''),
                    $row['externalRef'] ?? '', $row['txnStatus'],
                    !empty($row['hasEvidence']) ? 'Yes' : 'No'
                ), null, 'A' . $d);
                $d++;
            }
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}
