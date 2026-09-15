<?php

require_once __DIR__ . '/../tcpdf/tcpdf.php';

class PdfWasteReport extends TCPDF {

    public function Header() {
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'GEMS Scheduled Waste Register — JKR Report', 0, 1, 'L');
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 5, 'Generated from Final waste records only', 0, 1, 'L');
        $this->Ln(2);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages() . '   |   Malaysia time   |   GEMS Waste Management', 0, 0, 'C');
    }

    public static function renderToFile(array $payload, string $filePath): void {
        $pdf = new self('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('GEMS');
        $pdf->SetAuthor('GEMS Waste Management');
        $pdf->SetTitle($payload['title'] ?? 'JKR Waste Report');
        $pdf->SetMargins(12, 22, 12);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();

        $header = $payload['header'];
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, $header['siteName'] . ' (' . $header['siteCode'] . ')', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 5, 'Address: ' . ($header['address'] ?: '-') . "\nContact: " . ($header['contact'] ?: '-'), 0, 'L');
        $pdf->Ln(1);
        $pdf->Cell(90, 5, 'Period: ' . $header['periodFrom'] . ' to ' . $header['periodTo'], 0, 0);
        $pdf->Cell(90, 5, 'As-at: ' . $header['asAt'], 0, 0);
        $pdf->Cell(0, 5, 'Version: ' . $header['versionNo'], 0, 1);
        $pdf->Cell(90, 5, 'Prepared by: ' . $header['generatedByName'], 0, 0);
        $pdf->Cell(0, 5, 'Generated: ' . $header['generatedAt'], 0, 1);
        if (!empty($header['submission'])) {
            $pdf->Cell(0, 5, 'Submission: ' . $header['submission'], 0, 1);
        }
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', 'B', 8);
        $w = array(22, 70, 28, 28, 28, 28, 22, 50);
        $heads = array('SW Code', 'Official Description', 'Opening (kg)', 'Produced (kg)', 'Disposed (kg)', 'Closing (kg)', 'Unit', 'Remarks');
        foreach ($heads as $i => $h) {
            $pdf->Cell($w[$i], 7, $h, 1, 0, 'C');
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 8);
        $lines = $payload['summary'];
        if (empty($lines)) {
            $pdf->Cell(array_sum($w), 8, 'No confirmed transactions — zero-activity report', 1, 1, 'C');
        }
        foreach ($lines as $line) {
            $pdf->Cell($w[0], 6, $line['swCode'], 1, 0);
            $pdf->Cell($w[1], 6, mb_substr(strval($line['swDescription']), 0, 42), 1, 0);
            $pdf->Cell($w[2], 6, number_format($line['openingKg'], 3), 1, 0, 'R');
            $pdf->Cell($w[3], 6, number_format($line['producedKg'], 3), 1, 0, 'R');
            $pdf->Cell($w[4], 6, number_format($line['disposedKg'], 3), 1, 0, 'R');
            $pdf->Cell($w[5], 6, number_format($line['closingKg'], 3), 1, 0, 'R');
            $pdf->Cell($w[6], 6, 'kg / MT', 1, 0, 'C');
            $pdf->Cell($w[7], 6, mb_substr(strval($line['remarks'] ?? ''), 0, 28), 1, 1);
        }
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 8);
        $tot = $payload['totals'];
        $pdf->Cell(92, 6, 'Totals', 1, 0);
        $pdf->Cell(28, 6, number_format($tot['openingKg'], 3), 1, 0, 'R');
        $pdf->Cell(28, 6, number_format($tot['producedKg'], 3), 1, 0, 'R');
        $pdf->Cell(28, 6, number_format($tot['disposedKg'], 3), 1, 0, 'R');
        $pdf->Cell(28, 6, number_format($tot['closingKg'], 3), 1, 1, 'R');
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 5, 'Reconciliation: Opening + Produced - Disposed = Closing. Display values aggregated in kg before rounding.', 0, 1);

        if (!empty($payload['detail'])) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 7, 'Transaction Appendix', 0, 1);
            $pdf->SetFont('helvetica', 'B', 7);
            $dw = array(28, 22, 18, 22, 18, 50, 22, 14, 28, 22, 28);
            $dheads = array('Reference', 'Event Date', 'Type', 'Premise', 'SW Code', 'Description', 'Qty', 'Unit', 'Location', 'Ext. Ref', 'Status');
            foreach ($dheads as $i => $h) {
                $pdf->Cell($dw[$i], 6, $h, 1, 0, 'C');
            }
            $pdf->Ln();
            $pdf->SetFont('helvetica', '', 7);
            foreach ($payload['detail'] as $row) {
                $vals = array(
                    $row['txnRef'], $row['eventDate'], $row['txnType'] === 'P' ? 'Produced' : 'Disposed',
                    $row['siteCode'], $row['swCode'], $row['swDescription'],
                    number_format(floatval($row['qty']), 3), $row['unit'],
                    $row['locationName'] ?: $row['locationText'], $row['externalRef'], $row['txnStatus']
                );
                foreach ($vals as $i => $val) {
                    $pdf->Cell($dw[$i], 5, mb_substr(strval($val), 0, 28), 1, 0);
                }
                $pdf->Ln();
            }
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdf->Output($filePath, 'F');
    }
}
