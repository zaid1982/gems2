<?php

/**
 * Write a one-page TCPDF document that surfaces an exception for debugging.
 * Returns true on success.
 */
function gems_pdf_write_error_page($absolutePath, $title, array $lines) {
    if (!class_exists('TCPDF')) {
        throw new RuntimeException('TCPDF class not loaded; cannot write error PDF.');
    }

    $dir = dirname($absolutePath);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create PDF folder '.$dir);
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('PDF folder not writable '.$dir);
    }

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('GEMS');
    $pdf->SetTitle($title);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(160, 0, 0);
    $pdf->MultiCell(0, 8, $title, 0, 'L', false, 1);
    $pdf->Ln(2);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->MultiCell(0, 5, 'Generated: '.date('Y-m-d H:i:s'), 0, 'L', false, 1);
    $pdf->Ln(2);

    foreach ($lines as $label => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->MultiCell(0, 5, (string) $label, 0, 'L', false, 1);
        $pdf->SetFont('courier', '', 8);
        $pdf->MultiCell(0, 4, (string) $value, 1, 'L', true, 1);
        $pdf->Ln(2);
    }

    $pdf->Output($absolutePath, 'F');
    return true;
}

/**
 * Build standard diagnostic lines from a Throwable.
 */
function gems_pdf_error_lines(Throwable $ex, array $extra = array()) {
    $lines = array(
        'Message' => $ex->getMessage(),
        'File' => $ex->getFile().':'.$ex->getLine(),
        'Type' => get_class($ex),
    );
    if ($ex->getPrevious() instanceof Throwable) {
        $prev = $ex->getPrevious();
        $lines['Caused by'] = $prev->getMessage().' ['.basename($prev->getFile()).':'.$prev->getLine().']';
    }
    foreach ($extra as $key => $value) {
        $lines[$key] = $value;
    }
    $lines['Trace'] = $ex->getTraceAsString();
    return $lines;
}
