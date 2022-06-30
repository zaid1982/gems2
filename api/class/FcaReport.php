<?php

class FcaReport extends General {
    
    public $fcaReportId = 0;
    public $fcaReportName = '';

    function __construct(int $userId = 0, bool $isLogged = false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getList(): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            return DbMysql::selectAll('fca_report', array('fcaReportStatus'=>1));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $fcaReportId
     * @throws Exception
     */
    public function set (int $fcaReportId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($fcaReportId, 'fcaReportId');
            $this->fcaReportId = $fcaReportId;
            $this->fcaReportName = DbMysql::selectColumn('fca_report', array('fcaReportId'=>$fcaReportId),'fcaReportName', true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function insert (array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkMandatoryArray($columns, array('fcaReportName', 'fcaReportDateFrom', 'fcaReportDateTo', 'siteId', 'fcaReportExcludeList', 'fcaReportSortBy', 'pdfId'), true);
            $columns['fcaReportCreatedBy'] = $this->userId;
            $this->set(DbMysql::insert('fca_report', $columns));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function delete (): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->fcaReportId, 'fcaReportId');
            DbMysql::update('fca_report', array('fcaReportStatus'=>2), array('fcaReportId'=>$this->fcaReportId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param string $imageFile
     * @param float $boxHeight
     * @return void
     * @throws Exception
     */
    private function getImageDimension (string $imageFile, float $boxHeight): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyString($imageFile, 'imageFile1');
            parent::checkEmptyFloat($boxHeight, 'boxHeight');
            $imageSize = getimagesize($imageFile);
            $imageWidthDivider = $imageSize[0] / 44;
            $imageHeight = $imageSize[1] / $imageWidthDivider;
            $imageWidth = 44;
            if ($imageHeight > $boxHeight - 3) {
                $imageHeight = $boxHeight - 3;
                $imageHeightDivider = $imageSize[1] / $imageHeight;
                $imageWidth = $imageSize[0] / $imageHeightDivider;
            }
            return array($imageWidth, $imageHeight);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $params
     * @return array
     * @throws Exception
     */
    public function createPdf ($params): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, '$params '.json_encode($params));
            parent::checkMandatoryArray($params, array('fcaReportDateFrom', 'fcaReportDateTo', 'siteId', 'fcaReportExcludeList', 'fcaReportSortBy'), true);
            $sqlVal = array($params['fcaReportDateFrom'], $params['fcaReportDateTo'], intval($params['siteId']));

            $sql = /** @lang text */
                "SELECT 
                    f.*, a.asset_group_name, d.fca_defect_category_name, z.fca_zone_name,
                    CONCAT(u1.upload_folder, '/', u1.upload_filename, '.', u1.upload_extension) AS image_file_1,
                    CONCAT(u2.upload_folder, '/', u2.upload_filename, '.', u2.upload_extension) AS image_file_2
                FROM fca_task f
                LEFT JOIN ast_asset_group a ON a.asset_group_id = f.asset_group_id
                LEFT JOIN fca_defect_category d ON d.fca_defect_category_id = f.fca_defect_category_id
                LEFT JOIN fca_zone z ON z.fca_zone_id = f.fca_zone_id
                LEFT JOIN sys_upload u1 ON u1.upload_id = f.fca_task_image_1
                LEFT JOIN sys_upload u2 ON u2.upload_id = f.fca_task_image_2
                WHERE fca_task_status = 19 AND (fca_task_time_created BETWEEN ? AND ?) AND f.site_id = ?";
            if (intval($params['fcaReportExcludeList']) === 1) {
                $sql .= " AND fca_task_exclude_report = 0";
            }
            if (!empty($params['assetGroupId'])) {
                $sql .= " AND asset_group_id = ?";
                $sqlVal[] = intval($params['assetGroupId']);
            }
            $sql .= " ORDER BY ".$params['fcaReportSortBy'];
            $fcaTaskArr = DbMysql::selectSqlAll($sql, $sqlVal);
            $params['fcaReportTotal'] = count($fcaTaskArr);
            if (empty($fcaTaskArr)) {
                return $params;
            }

            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetTitle('GEMS 2.0 - FCA Report');
            $pdf->SetSubject('GEMS 2.0 - FCA Report');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(true);
            $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, 25, PDF_MARGIN_RIGHT);
            $pdf->SetFooterMargin(20);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->setCellPaddings(1, 1, 1, 1);
            $titleHeight = 8.173612;
            $cellHeight = 5.527778;
            $cntNextPage = 0;

            $pdf->AddPage();
            $i = 0;
            while ($i < count($fcaTaskArr)) {
                if ($cntNextPage > 0 && $i === $cntNextPage) {
                    $pdf->AddPage();
                }
                $fcaTask = $fcaTaskArr[$i];

                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->SetFillColor(0, 4, 16, 0);
                $pdf->setCellPaddings(1, 2.2, 1, 1);
                $pdf->MultiCell(7, $titleHeight, 'BIL', 1, 'C', 1, 0);
                $pdf->MultiCell(94, $titleHeight, 'GAMBAR', 1, 'C', 1, 0);
                $pdf->MultiCell(47, $titleHeight, 'LOKASI', 1, 'C', 1, 0);
                $pdf->setCellPaddings(1, 1, 1, 1);
                $pdf->MultiCell(32, $titleHeight, 'KATEGORI KEROSAKAN / KECACATAN', 1, 'C', 1, 0);
                $pdf->Ln();

                $y1 = $pdf->GetY();
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->MultiCell(20, '', 'Tarikh :', 1, 'L', 0, 0, 15 + 7 + 94);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->MultiCell(27, '', parent::dateDisplay($fcaTask['fcaTaskTimeCreated']), 1, 'L', 0, 0);
                $pdf->MultiCell(15, '', ($fcaTask['fcaTaskConditionScale'] === 5 ? 'X' : ''), 1, 'C', 0, 0);
                $pdf->SetFillColor(0, 100, 100, 0);
                $pdf->MultiCell(17, '', 'A', 1, 'C', 1, 0);
                $pdf->Ln();

                $yTemp = $pdf->getY();
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->MultiCell(20, $cellHeight, 'Zon :', 0, 'L', 0, 0, 15 + 7 + 94);
                $pdf->SetFont('helvetica', '', 8);
                $totalLine = $pdf->MultiCell(27, $cellHeight, $fcaTask['fcaZoneName'], 0, 'L', 0, 0);
                $pdf->MultiCell(15, $cellHeight, ($fcaTask['fcaTaskConditionScale']===4 || $fcaTask['fcaTaskConditionScale']===3 ? 'X' : ''), 0, 'C', 0, 0);
                $pdf->MultiCell(17, $cellHeight, 'B', 0, 'C', 1, 0);
                $pdf->Ln();
                $pdf->setY($yTemp);
                $yActual = ($totalLine * ($cellHeight - 2)) + 2;
                $pdf->SetFont('helvetica', '', 8);
                $pdf->MultiCell(20, $yActual, '', 1, 'L', 0, 0, 15 + 7 + 94);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->MultiCell(27, $yActual, '', 1, 'L', 0, 0);
                $pdf->MultiCell(15, $yActual, '', 1, 'C', 0, 0);
                $pdf->SetFillColor(0, 50, 100, 0);
                $pdf->MultiCell(17, $yActual, '', 1, 'C', 1, 0);
                $pdf->Ln();

                $yTemp = $pdf->getY();
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->MultiCell(20, $cellHeight, 'Kawasan :', 0, 'L', 0, 0, 15 + 7 + 94);
                $pdf->SetFont('helvetica', '', 8);
                $totalLine = $pdf->MultiCell(27, $cellHeight, $fcaTask['fcaTaskArea'], 0, 'L', 0, 0);
                $pdf->MultiCell(15, $cellHeight, ($fcaTask['fcaTaskConditionScale']===2 || $fcaTask['fcaTaskConditionScale']===1 ? 'X' : ''), 0, 'C', 0, 0);
                $pdf->MultiCell(17, $cellHeight, 'C', 0, 'C', 1, 0);
                $pdf->Ln();
                $pdf->setY($yTemp);
                $yActual = ($totalLine * ($cellHeight - 2)) + 2;
                $pdf->SetFont('helvetica', '', 8);
                $pdf->MultiCell(20, $yActual, '', 1, 'L', 0, 0, 15 + 7 + 94);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->MultiCell(27, $yActual, '', 1, 'L', 0, 0);
                $pdf->MultiCell(15, $yActual, '', 1, 'C', 0, 0);
                $pdf->SetFillColor(0, 0, 100, 0);
                $pdf->MultiCell(17, $yActual, '', 1, 'C', 1, 0);
                $pdf->Ln();

                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->setCellPaddings(1, 2.2, 1, 1);
                $pdf->SetFillColor(0, 4, 16, 0);
                $pdf->MultiCell(20 + 27 + 32, $titleHeight, 'ULASAN', 1, 'C', 1, 0, 15 + 7 + 94);
                $pdf->setCellPaddings(1, 1, 1, 1);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->Ln();

                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->MultiCell(20, '', 'Skop Kerja :', 1, 'L', 0, 0, 15 + 7 + 94);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->MultiCell(27 + 32, '', $fcaTask['assetGroupName'], 1, 'L', 0, 0);
                $pdf->Ln();

                $pdf->setCellPaddings(1, 1, 1, 0);
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->MultiCell(20 + 27 + 32, '', 'Keterangan Kerosakan / Penemuan :', 'RLT', 'L', 0, 0, 15 + 7 + 94);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->Ln();

                $pdf->setCellPaddings(1, 1, 1, 2);
                $pdf->MultiCell(20 + 27 + 32, '', $fcaTask['fcaTaskObservation'], 'RLB', 'L', 0, 0, 15 + 7 + 94);
                $pdf->Ln();

                $y2 = $pdf->GetY();
                parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'current Y = ' .$i.' - '.$cntNextPage. ' - '. $y2);
                if ($y2 > 273) {
                    $j = $i;
                    $i = $cntNextPage;
                    $cntNextPage = $j;
                    $pdf->AddPage();
                    $pdf->deletePage($pdf->getPage());
                    $pdf->deletePage($pdf->getPage());
                    $pdf->AddPage();
                    continue;
                }

                $pdf->setY($y1);
                $yMainRowHeight = $y2 - $y1;
                $pdf->MultiCell(7, $yMainRowHeight, strval($i + 1), 1, 'C', 0, 0);
                $pdf->MultiCell(47, $yMainRowHeight, '', 1, 'C', 0, 0);
                $pdf->MultiCell(47, $yMainRowHeight, '', 1, 'C', 0, 0);
                $pdf->Ln();

                if (file_exists($fcaTask['imageFile1'])) {
                    $image1Dimension = $this->getImageDimension($fcaTask['imageFile1'], $yMainRowHeight);
                    $xPoint = $image1Dimension[0] < 44 ? 15 + 7 + 1.5 + ((44 - $image1Dimension[0]) / 2) : 15 + 7 + 1.5;
                    $pdf->Image($fcaTask['imageFile1'], $xPoint, $y1 + 1.5, $image1Dimension[0], $image1Dimension[1], '', '', '', true, 300);
                }
                if (file_exists($fcaTask['imageFile2'])) {
                    $image2Dimension = $this->getImageDimension($fcaTask['imageFile2'], $yMainRowHeight);
                    $xPoint2 = $image2Dimension[0] < 44 ? 15 + 7 + 47 + 1.5 + ((44 - $image2Dimension[0]) / 2) : 15 + 47 + 7 + 1.5;
                    $pdf->Image($fcaTask['imageFile2'], $xPoint2, $y1 + 1.5, $image2Dimension[0], $image2Dimension[1], '', '', '', true, 300);
                }
                $i++;
            }

            $curDates = new DateTime();
            $siteId = 1;
            $folder = 'pdf/fca/'.$siteId;
            if (!parent::folderExist($folder)) {
                mkdir ($folder,0777, true);
            }
            $filename = 'fca_'.$curDates->format("ymdHis").'.pdf';
            $filenameSrc = trim(dirname(__FILE__), 'class').'pdf\fca\\' . $siteId . '\\' . $filename;
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'dirname = '.$filenameSrc);
            $pdf->Output($filenameSrc, 'F');
            $params['pdfId'] = DbMysql::insert('sys_pdf', array('pdfType'=>'fca', 'pdfFolder'=>$folder, 'pdfFilename'=>$filename));
            return $params;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}