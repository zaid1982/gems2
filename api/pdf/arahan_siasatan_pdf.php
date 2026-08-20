<?php

if (!class_exists('TCPDF')) {
    require_once __DIR__.'/tcpdf_include.php';
}

class ArahanSiasatanPdf extends TCPDF
{
    protected $x0 = 14.7;
    protected $w0 = 180.4;
    protected $right = 195.1;

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);

        $this->setPrintHeader(false);
        $this->setPrintFooter(true);

        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false, 0);
        $this->SetCellPadding(1.0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.12);

        $this->SetCreator('GEMS 2.0');
        $this->SetAuthor('GEMS 2.0');
        $this->SetTitle('Borang Arahan Siasatan & Penyenggaraan Pembaikan');
    }

    public function Footer()
    {
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.25);
        $this->Line(14.7, 282.5, 195.1, 282.5);

        $this->SetFont('helvetica', '', 8.5);
        $this->SetXY(150, 283.5);
        $this->Cell(45, 4, 'Halaman '.$this->getAliasNumPage().' / '.$this->getAliasNbPages(), 0, 0, 'R');
    }

    private function drawCell($x, $y, $w, $h, $text = '', $border = 1, $align = 'L', $style = '', $size = 8.2, $valign = 'M')
    {
        $this->SetFont('helvetica', $style, $size);
        $this->SetXY($x, $y);

        $this->MultiCell(
            $w,
            $h,
            $text,
            $border,
            $align,
            false,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $h,
            $valign
        );
    }

    private function drawText($x, $y, $w, $h, $text = '', $align = 'L', $style = '', $size = 8.2, $valign = 'M')
    {
        $this->SetFont('helvetica', $style, $size);
        $this->SetXY($x, $y);

        $this->MultiCell(
            $w,
            $h,
            $text,
            0,
            $align,
            false,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $h,
            $valign,
            true
        );
    }

    private function drawGrid($x, $y, array $colWidths, array $rowHeights, $lineWidth = 0.12, array $skipOuter = array())
    {
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth($lineWidth);

        $totalW = array_sum($colWidths);
        $totalH = array_sum($rowHeights);
        $x2 = $x + $totalW;
        $y2 = $y + $totalH;

        if (empty($skipOuter)) {
            $this->Rect($x, $y, $totalW, $totalH);
        } else {
            if (empty($skipOuter['top'])) {
                $this->Line($x, $y, $x2, $y);
            }
            if (empty($skipOuter['bottom'])) {
                $this->Line($x, $y2, $x2, $y2);
            }
            if (empty($skipOuter['left'])) {
                $this->Line($x, $y, $x, $y2);
            }
            if (empty($skipOuter['right'])) {
                $this->Line($x2, $y, $x2, $y2);
            }
        }

        $currentX = $x;
        foreach ($colWidths as $i => $cw) {
            $currentX += $cw;
            if ($i < count($colWidths) - 1) {
                $this->Line($currentX, $y, $currentX, $y + $totalH);
            }
        }

        $currentY = $y;
        foreach ($rowHeights as $i => $rh) {
            $currentY += $rh;
            if ($i < count($rowHeights) - 1) {
                $this->Line($x, $currentY, $x + $totalW, $currentY);
            }
        }
    }

    private function section($y, $title, $skipBottom = false, $skipTop = false)
    {
        $skipOuter = array();
        if ($skipBottom) {
            $skipOuter['bottom'] = true;
        }
        if ($skipTop) {
            $skipOuter['top'] = true;
        }
        $this->SetLineWidth(0.18);
        $this->drawGrid($this->x0, $y, array($this->w0), array(6.4), 0.18, $skipOuter);
        $this->drawText($this->x0, $y, $this->w0, 6.4, $title, 'L', 'B', 8.7);
        $this->SetLineWidth(0.12);
    }

    private function drawFormHeader(array $d)
    {
        $x = $this->x0;
        $w = $this->w0;
        $y0 = 11.7;

        $row1H = 10.1;
        $row2H = 4.5;
        $row3H = 9.1;
        $leftW = 125.1;
        $labelW = 20.1;
        $valueW = 35.2;
        $refRowH = 4.55;

        $y2 = $y0 + $row1H;
        $y3 = $y2 + $row2H;
        $xSplit = $x + $leftW;
        $xValue = $xSplit + $labelW;
        $yMid = $y3 + $refRowH;

        $this->SetLineWidth(0.12);
        $this->Rect($x, $y0, $w, $row1H + $row2H + $row3H);
        $this->Line($x, $y2, $x + $w, $y2);
        $this->Line($x, $y3, $x + $w, $y3);
        $this->Line($xSplit, $y3, $xSplit, $y3 + $row3H);
        $this->Line($xValue, $y3, $xValue, $y3 + $row3H);
        $this->Line($xSplit, $yMid, $x + $w, $yMid);

        $this->drawText($x, $y0, $w, $row1H, 'JKR.PATA.F7/4', 'R', 'B');
        $this->drawText($x, $y2, $w, $row2H, 'BORANG ARAHAN SIASATAN & PENYENGGARAAN PEMBAIKAN', 'C', 'B');
        $this->drawText($x, $y3, $leftW, $row3H, 'Format Arahan Siasatan dan Penyenggaraan Pembaikan', 'C');
        $this->drawText($xSplit, $y3, $labelW, $refRowH, 'No. Ruj.', 'L', 'B');
        $this->drawText($xValue, $y3, $valueW, $refRowH, $d['no_ruj'] ?? '', 'L');
        $this->drawText($xSplit, $yMid, $labelW, $refRowH, 'Status', 'L', 'B');
        $this->drawText($xValue, $yMid, $valueW, $refRowH, $d['status'] ?? '', 'L');
    }

    private function drawSectionB(array $d)
    {
        $x = $this->x0;
        $w = $this->w0;
        $y0 = 71.1;
        $y1 = 79.1;
        $y2 = 83.7;
        $y3 = 88.3;
        $y4 = 97.3;
        $y5 = 101.8;

        $this->SetLineWidth(0.12);
        $this->Rect($x, $y0, $w, $y5 - $y0);
        $this->Line($x, $y1, $x + $w, $y1);
        $this->Line($x, $y2, $x + $w, $y2);
        $this->Line($x, $y3, $x + $w, $y3);
        $this->Line($x, $y4, $x + $w, $y4);

        $this->Line(37.6, $y0, 37.6, $y1);
        $this->Line(72.7, $y0, 72.7, $y2);
        $this->Line(101.8, $y0, 101.8, $y2);
        $this->Line(136.8, $y0, 136.8, $y2);
        $this->Line(159.9, $y0, 159.9, $y1);
        $this->Line(37.6, $y2, 37.6, $y3);
        $this->Line(108.7, $y3, 108.7, $y5);
        $this->Line(142.9, $y3, 142.9, $y5);
        $this->Line(37.6, $y3, 37.6, $y4);

        $this->drawText(14.7, $y0, 22.9, 8.0, 'Diterima Oleh', 'L', 'B');
        $this->drawText(37.6, $y0, 35.1, 8.0, $d['diterima_oleh'] ?? '', 'L');
        $this->drawText(72.7, $y0, 29.1, 8.0, 'Ditugaskan Kepada', 'L', 'B');
        $this->drawText(101.8, $y0, 35.0, 8.0, $d['ditugaskan_kepada'] ?? '', 'L');
        $this->drawText(136.8, $y0, 23.1, 8.0, 'Tarikh / Masa', 'L', 'B');
        $this->drawText(159.9, $y0, 35.2, 8.0, $d['tarikh_arahan'] ?? '', 'L');

        $this->drawText(72.7, $y1, 29.1, 4.6, 'No. Utk Dihubungi', 'L', 'B');
        $this->drawText(101.8, $y1, 35.0, 4.6, $d['no_dihubungi'] ?? '', 'L');

        $this->drawText(14.7, $y2, 22.9, 4.6, 'Keterangan', 'L', 'B');
        $this->drawText(37.6, $y2, 157.5, 4.6, $d['keterangan_arahan'] ?? '', 'L');

        $this->drawText(14.7, $y3, 22.9, 9.0, 'Tarikh & Masa', 'L', 'B');
        $this->drawText(37.6, $y3, 71.1, 9.0, $d['tarikh_tandatangan_pengadu'] ?? '', 'L');
        $this->drawText(108.7, $y3, 34.2, 9.0, 'Tandatangan Pengadu', 'L', 'B');
        $this->fitImage($d['sign_pengadu'] ?? null, 150, $y3 + 0.4, 30, 8);

        $this->drawText(108.7, $y4, 34.2, 4.5, 'Cap Nama & Jawatan', 'L', 'B');
        $this->drawText(142.9, $y4, 52.2, 4.5, $d['cap_pengadu'] ?? '', 'C', 'I', 7.7);
    }

    private function drawSectionE(array $d)
    {
        $x = $this->x0;
        $w = $this->w0;
        $y0 = 147.6;
        $y1 = 152.1;
        $y2 = 156.6;
        $y3 = 165.6;
        $y4 = 173.7;

        $this->SetLineWidth(0.12);
        $this->Rect($x, $y0, $w, $y4 - $y0);
        $this->Line($x, $y1, $x + $w, $y1);
        $this->Line($x, $y2, $x + $w, $y2);
        $this->Line($x, $y3, $x + $w, $y3);

        $this->Line(41.7, $y0, 41.7, $y1);
        $this->Line(42.7, $y1, 42.7, $y2);
        $this->Line(108.7, $y1, 108.7, $y4);
        $this->Line(142.9, $y1, 142.9, $y4);
        $this->Line(168.9, $y1, 168.9, $y2);
        $this->Line(182.8, $y1, 182.8, $y2);

        $this->drawText(14.7, $y0, 27.0, 4.5, 'Tindakan', 'L', 'B');
        $this->drawText(41.7, $y0, 153.4, 4.5, $d['tindakan'] ?? '', 'L');

        $this->drawText(14.7, $y1, 28.0, 4.5, 'Tarikh & Masa Mula', 'L', 'B');
        $this->drawText(42.7, $y1, 66.0, 4.5, $d['tindakan_mula'] ?? '', 'L');
        $this->drawText(108.7, $y1, 34.2, 4.5, 'Tarikh & Masa Siap', 'L', 'B');
        $this->drawText(142.9, $y1, 26.0, 4.5, $d['tindakan_siap'] ?? '', 'L');
        $this->drawText(168.9, $y1, 13.9, 4.5, 'Tempoh', 'L', 'B');
        $this->drawText(182.8, $y1, 12.3, 4.5, $d['tempoh_tindakan'] ?? '', 'C');

        $this->drawText(108.7, $y2, 34.2, 9.0, 'Tandatangan', 'L', 'B');
        $this->fitImage($d['sign_technician'] ?? null, 150, $y2 + 0.4, 28, 8);

        $this->drawText(108.7, $y3, 34.2, 8.1, 'Cap Nama & Jawatan', 'L', 'B');
        $this->drawText(142.9, $y3, 52.2, 8.1, $d['cap_technician'] ?? '', 'C', 'I', 7.5);
    }

    private function drawSectionG(array $d)
    {
        $x = $this->x0;
        $w = $this->w0;
        $xRight = $x + $w;
        $xMid = 104.9;
        $xLeftVal = 59.7;
        $xRightVal = 149.9;
        $labelW = 45.0;
        $valueW = 45.2;

        $y0 = 204.9;
        $y1 = 214.0;
        $y2 = 222.0;
        $y3 = 230.0;
        $y4 = 234.6;
        $y5 = 242.6;
        $y6 = 250.6;
        $y7 = 258.6;
        $y8 = 264.3;

        $this->SetLineWidth(0.12);
        $this->Rect($x, $y0, $w, $y8 - $y0);
        $this->Line($x, $y1, $xRight, $y1);
        $this->Line($x, $y2, $xRight, $y2);
        $this->Line($x, $y3, $xRight, $y3);
        $this->Line($x, $y4, $xRight, $y4);
        $this->Line($xMid, $y5, $xRight, $y5);
        $this->Line($xMid, $y6, $xRight, $y6);
        $this->Line($xMid, $y7, $xRight, $y7);
        $this->Line($xLeftVal, $y0, $xLeftVal, $y4);
        $this->Line($xMid, $y0, $xMid, $y8);
        $this->Line($xRightVal, $y0, $xRightVal, $y8);

        $this->drawText($x, $y0, $labelW, 9.1, 'Tandatangan', 'L', 'B');
        $this->fitImage($d['sign_pegawai_penyelia'] ?? null, 65, $y0 + 0.3, 30, 8);

        $this->drawText($xMid, $y0, $labelW, 9.1, 'Tandatangan', 'L', 'B');
        $this->fitImage($d['sign_pengadu_kerja_siap'] ?? null, 158, $y0 + 0.3, 28, 8);

        $this->drawText($xLeftVal, $y1, $valueW, 8.0, "Pengesahan Oleh Pegawai\nPenyelia", 'C', 'B', 7.8);
        $this->drawText($xRightVal, $y1, $valueW, 8.0, "Pengesahan Oleh Pihak\nPengadu (Jika Berkenaan)", 'C', 'B', 7.8);

        $this->drawText($x, $y2, $labelW, 8.0, 'Cap Nama & Jawatan', 'L', 'B');
        $this->drawText($xLeftVal, $y2, $valueW, 8.0, $d['cap_pegawai_penyelia'] ?? '', 'C', 'I', 7.5);
        $this->drawText($xMid, $y2, $labelW, 8.0, 'Cap Nama & Jawatan', 'L', 'B');
        $this->drawText($xRightVal, $y2, $valueW, 8.0, $d['cap_pengadu_kerja_siap'] ?? '', 'C', 'I', 7.5);

        $this->drawText($x, $y3, $labelW, 4.6, 'Tarikh & Masa', 'L', 'B');
        $this->drawText($xLeftVal, $y3, $valueW, 4.6, $d['tarikh_pegawai_penyelia'] ?? '', 'C');
        $this->drawText($xMid, $y3, $labelW, 4.6, 'Tarikh & Masa', 'L', 'B');
        $this->drawText($xRightVal, $y3, $valueW, 4.6, $d['tarikh_pengadu_kerja_siap'] ?? '', 'C');

        $this->drawText($xMid, $y4, $labelW, 8.0, 'Tandatangan', 'L', 'B');
        $this->fitImage($d['sign_operasi_fasiliti'] ?? null, 158, $y4 + 0.4, 28, 8);

        $this->drawText($xRightVal, $y5, $valueW, 8.0, "Pengesahan Oleh Pegawai\nOperasi Fasiliti", 'C', 'B', 7.8);
        $this->drawText($xMid, $y6, $labelW, 8.0, 'Cap Nama & Jawatan', 'L', 'B');
        $this->drawText($xRightVal, $y6, $valueW, 8.0, $d['cap_operasi_fasiliti'] ?? '', 'C', 'I', 7.5);
        $this->drawText($xMid, $y7, $labelW, 5.7, 'Tarikh & Masa', 'L', 'B');
        $this->drawText($xRightVal, $y7, $valueW, 5.7, $d['tarikh_operasi_fasiliti'] ?? '', 'C');
    }

    private function fitImage($path, $x, $y, $w, $h)
    {
        if (!$path || !is_file($path) || !is_readable($path)) {
            return;
        }

        $info = @getimagesize($path);
        if (!$info || empty($info[0]) || empty($info[1])) {
            return;
        }

        [$iw, $ih] = $info;
        if ($iw <= 0 || $ih <= 0) {
            return;
        }

        $ratio = min($w / $iw, $h / $ih);
        $nw = $iw * $ratio;
        $nh = $ih * $ratio;

        $ix = $x + (($w - $nw) / 2);
        $iy = $y + (($h - $nh) / 2);

        try {
            $this->Image($path, $ix, $iy, $nw, $nh, '', '', '', true, 300);
        } catch (Exception $e) {
            return;
        }
    }

    public function render(array $data)
    {
        $noRuj = trim((string) ($data['no_ruj'] ?? ''));
        if ($noRuj !== '' && $noRuj !== '-') {
            $this->SetTitle($noRuj);
        }

        $this->drawPageOne($data);
        $this->drawPhotoPages($data['photos'] ?? []);

        return $this;
    }

    private function drawPageOne(array $d)
    {
        $this->AddPage();

        $x = $this->x0;
        $w = $this->w0;

        $this->drawFormHeader($d);

        $this->section(35.4, 'A. ADUAN', false, true);

        $ay = 41.8;
        $rh = 4.55;
        $colWidthsA = array(28.0, 62.2, 27.9, 62.3);
        $rowHeightsA = array($rh, $rh, $rh, $rh, $rh);

        $rowsA = array(
            array('Nama Pengadu', $d['nama_pengadu'] ?? '', 'Jenis Kerja', $d['jenis_kerja'] ?? ''),
            array('Tarikh & Masa', $d['tarikh_aduan'] ?? '', 'Kategori Kerja', $d['kategori_kerja'] ?? ''),
            array('No. Telefon', $d['no_telefon'] ?? '', 'Keutamaan Kerja', $d['keutamaan_kerja'] ?? ''),
            array('Keterangan', $d['keterangan_aduan'] ?? '', 'Lokasi', $d['lokasi'] ?? ''),
            array('No. Aset', $d['no_aset'] ?? '', 'Nama Aset', $d['nama_aset'] ?? ''),
        );

        $this->drawGrid(14.7, $ay, $colWidthsA, $rowHeightsA, 0.12, array('top' => true));
        foreach ($rowsA as $i => $r) {
            $y = $ay + ($i * $rh);
            $this->drawText(14.7, $y, 28.0, $rh, $r[0], 'L', 'B');
            $this->drawText(42.7, $y, 62.2, $rh, $r[1], 'L');
            $this->drawText(104.9, $y, 27.9, $rh, $r[2], 'L', 'B');
            $this->drawText(132.8, $y, 62.3, $rh, $r[3], 'L');
        }

        $this->section(64.5, 'B. ARAHAN SIASATAN');
        $this->drawSectionB($d);

        $this->section(101.8, 'C. BUTIRAN ALAT GANTI');

        $partsY = 108.3;
        $partsRowH = 4.6;
        $colWidthsC = array(24.0, 70.0, 20.2, 20.0, 23.0, 23.2);
        $rowHeightsC = array(8.0, $partsRowH);

        $this->drawGrid(14.7, $partsY, $colWidthsC, $rowHeightsC);

        $this->drawText(14.7, $partsY, 24.0, 8.0, 'No. Alat Ganti', 'C', 'B');
        $this->drawText(38.7, $partsY, 70.0, 8.0, 'Keterangan', 'C', 'B');
        $this->drawText(108.7, $partsY, 20.2, 8.0, "Jenis Isu /\n(D/I)", 'C', 'B');
        $this->drawText(128.9, $partsY, 20.0, 8.0, 'Unit Bahan', 'C', 'B');
        $this->drawText(148.9, $partsY, 23.0, 8.0, "Kuantiti\nDigunakan", 'C', 'B');
        $this->drawText(171.9, $partsY, 23.2, 8.0, "Kuantiti\nDikembalikan", 'C', 'B');

        $partsRows = $d['parts'] ?? array(array());
        if (empty($partsRows)) {
            $partsRows = array(array());
        }
        $partLimit = min(count($partsRows), 1);
        for ($i = 0; $i < $partLimit; $i++) {
            $part = $partsRows[$i];
            $y = $partsY + 8.0 + ($i * $partsRowH);
            if ($i > 0) {
                $this->drawGrid(14.7, $y, $colWidthsC, array($partsRowH));
            }
            $this->drawText(14.7, $y, 24.0, $partsRowH, $part['no'] ?? '', 'C');
            $this->drawText(38.7, $y, 70.0, $partsRowH, $part['keterangan'] ?? '', 'L');
            $this->drawText(108.7, $y, 20.2, $partsRowH, $part['jenis'] ?? '', 'C');
            $this->drawText(128.9, $y, 20.0, $partsRowH, $part['unit'] ?? '', 'C');
            $this->drawText(148.9, $y, 23.0, $partsRowH, $part['digunakan'] ?? '', 'C');
            $this->drawText(171.9, $y, 23.2, $partsRowH, $part['dikembalikan'] ?? '', 'C');
        }

        $footnoteY = 120.9;
        $this->drawGrid(14.7, $footnoteY, array(180.4), array(4.5));
        $this->drawText(14.7, $footnoteY, 180.4, 4.5, '** D = Direct Issue, I = Inventory', 'L', 'I');

        $this->section(125.4, 'D. BUTIRAN KERJA');

        $dy = 132.0;
        $colWidthsD = array(60.0, 30.2, 34.9, 35.2, 20.1);
        $rowHeightsD = array(4.5, 4.6);

        $this->drawGrid(14.7, $dy, $colWidthsD, $rowHeightsD);

        $this->drawText(14.7, $dy, 60.0, 4.5, 'Nama Pekerja', 'C', 'B');
        $this->drawText(74.7, $dy, 30.2, 4.5, 'No. Pekerja', 'C', 'B');
        $this->drawText(104.9, $dy, 34.9, 4.5, 'Tarikh & Masa Mula', 'C', 'B');
        $this->drawText(139.8, $dy, 35.2, 4.5, 'Tarikh & Masa Tamat', 'C', 'B');
        $this->drawText(175.0, $dy, 20.1, 4.5, 'Tempoh', 'C', 'B');

        $this->drawText(14.7, 136.5, 60.0, 4.6, $d['nama_pekerja'] ?? '', 'L');
        $this->drawText(74.7, 136.5, 30.2, 4.6, $d['no_pekerja'] ?? '', 'C');
        $this->drawText(104.9, 136.5, 34.9, 4.6, $d['kerja_mula'] ?? '', 'C');
        $this->drawText(139.8, 136.5, 35.2, 4.6, $d['kerja_tamat'] ?? '', 'C');
        $this->drawText(175.0, 136.5, 20.1, 4.6, $d['tempoh_kerja'] ?? '', 'C');

        $this->section(141.1, 'E. TINDAKAN PEMBAIKAN / PENCEGAHAN');
        $this->drawSectionE($d);

        $this->section(173.7, 'F. JIKA LUAR SKOP KERJA / TEMPOH TANGGUNGAN KECACATAN');

        $fRows = array(
            array('Nama Kontraktor', $d['nama_kontraktor'] ?? ''),
            array('Lantikan Mula Kerja', $d['lantikan_mula_kerja'] ?? ''),
            array('Tarikh Siap Kerja', $d['tarikh_siap_kerja'] ?? ''),
            array('Kos Akhir', $d['kos_akhir'] ?? ''),
        );

        $fy = 180.2;
        $rhF = 4.55;
        $colWidthsF = array(28.0, 152.4);
        $rowHeightsF = array($rhF, $rhF, $rhF, $rhF);

        $this->drawGrid(14.7, $fy, $colWidthsF, $rowHeightsF);
        foreach ($fRows as $i => $r) {
            $y = $fy + ($i * $rhF);
            $this->drawText(14.7, $y, 28.0, $rhF, $r[0], 'L', 'B');
            $this->drawText(42.7, $y, 152.4, $rhF, $r[1], 'L');
        }

        $this->section(198.4, 'G. PERAKUAN KERJA SIAP');
        $this->drawSectionG($d);
    }

    private function drawPhotoPages(array $photos)
    {
        if (empty($photos)) {
            return;
        }

        $flatItems = [];
        foreach ($photos as $group) {
            $title = $group['title'] ?? '';
            $items = $group['items'] ?? [];
            foreach ($items as $itemIndex => $item) {
                $item['_group_title'] = $title;
                $flatItems[] = [
                    'title' => $itemIndex === 0 ? $title : '',
                    'item' => $item,
                ];
            }
        }

        if (empty($flatItems)) {
            return;
        }

        $x = 14.7;
        $w = 180.4;
        $leftW = 95.0;
        $rightW = $w - $leftW;

        $pageTwoSlots = [
            ['titleY' => 19.9,  'rowY' => 26.0,  'rowH' => 65.6],
            ['titleY' => 100.1, 'rowY' => 106.1, 'rowH' => 65.5],
            ['titleY' => 180.2, 'rowY' => 186.2, 'rowH' => 65.5],
        ];

        $pageThreeSlots = [
            ['titleY' => null,  'rowY' => 11.7,  'rowH' => 65.1],
            ['titleY' => null,  'rowY' => 76.8,  'rowH' => 65.7],
            ['titleY' => 150.9, 'rowY' => 156.9, 'rowH' => 65.7],
        ];

        $this->AddPage();
        $slotIndex = 0;
        $slots = $pageTwoSlots;

        foreach ($flatItems as $entry) {
            if ($slotIndex >= count($slots)) {
                $this->AddPage();
                $slots = ($slots === $pageTwoSlots) ? $pageThreeSlots : $pageTwoSlots;
                $slotIndex = 0;
            }

            $slot = $slots[$slotIndex];
            $titleH = 6.1;
            $hasTitle = !empty($entry['title']) && $slot['titleY'] !== null;

            if ($hasTitle) {
                $this->drawPhotoBlock(
                    $entry['title'],
                    $entry['item'],
                    $x,
                    $slot['titleY'],
                    $w,
                    $leftW,
                    $rightW,
                    $titleH,
                    $slot['rowH']
                );
            } else {
                $this->drawPhotoRow(
                    $entry['item'],
                    $x,
                    $slot['rowY'],
                    $leftW,
                    $rightW,
                    $slot['rowH']
                );
            }

            $slotIndex++;
        }
    }

    private function drawPhotoBlock($title, array $item, $x, $y, $w, $leftW, $rightW, $titleH, $rowH)
    {
        $this->SetLineWidth(0.12);
        $totalH = $titleH + $rowH;
        $ySplit = $y + $titleH;

        $this->Rect($x, $y, $w, $totalH);
        $this->Line($x, $ySplit, $x + $w, $ySplit);
        $this->Line($x + $leftW, $ySplit, $x + $leftW, $y + $totalH);

        $this->drawText($x, $y, $w, $titleH, $title, 'L', '', 11.5);

        $this->fitImage(
            $item['image'] ?? null,
            $x + 20,
            $ySplit + 4,
            55,
            $rowH - 8
        );

        $text = 'Keterangan : '.($item['keterangan'] ?? '')."\n"
            .'Masa Diambil : '.($item['masa'] ?? '')."\n"
            .'Longitude : '.($item['longitude'] ?? '')."\n"
            .'Latitude : '.($item['latitude'] ?? '');

        $this->drawText($x + $leftW + 2, $ySplit + 5, $rightW - 4, 25, $text, 'L', '', 9.5, 'T');
    }

    private function drawPhotoRow(array $item, $x, $y, $leftW, $rightW, $rowH)
    {
        $this->drawGrid($x, $y, array($leftW, $rightW), array($rowH));

        $this->fitImage(
            $item['image'] ?? null,
            $x + 20,
            $y + 4,
            55,
            $rowH - 8
        );

        $groupTitle = trim((string) ($item['_group_title'] ?? ''));
        $text = ($groupTitle !== '' ? $groupTitle."\n" : '')
            .'Keterangan : '.($item['keterangan'] ?? '')."\n"
            .'Masa Diambil : '.($item['masa'] ?? '')."\n"
            .'Longitude : '.($item['longitude'] ?? '')."\n"
            .'Latitude : '.($item['latitude'] ?? '');

        $this->drawText($x + $leftW + 2, $y + 5, $rightW - 4, 25, $text, 'L', '', 9.5, 'T');
    }
}
