<?php

class MYPDF_ppm extends TCPDF {
    private $fn_general;
    private $ppmTaskId;
    private $ppmDocumentNo;
    private $ppmIssueNo;

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value ) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg,'-');
            if ($pos !== false) {
                $msg = substr($msg, $pos+2);
            }
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."] - ".$msg;
        } else {
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."]";
        }
    }

    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Line(PDF_MARGIN_LEFT, $this->y, $this->w - PDF_MARGIN_RIGHT, $this->y);
        $pageNo = 'Page '.strval($this->getAliasNumPage()).' of '.$this->getAliasNbPages();
        $this->Cell(65, 6, 'Document No : '.$this->ppmDocumentNo, 0, 0, 'L', 0);
        $this->Cell(70, 6, 'Issue No : '.$this->ppmIssueNo, 0, 0, 'L', 0);
        $this->Cell(55, 6, $pageNo, 0, 0, 'R', 0);
    }
}

class Class_pdf_ppm {
    private $fn_general;
    private $ppmTaskId;

    function __construct() {
    }

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg,'-');
            if ($pos !== false) {
                $msg = substr($msg, $pos+2);
            }
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."] - ".$msg;
        } else {
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."]";
        }
    }

    /**
     * @param $property
     * @return mixed
     * @throws Exception
     */
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    private function TaskQualEmpty($pdf) {
        $pdf->MultiCell(8, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(112, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(20, 4, '', 1, 'L', 0, 0);
        $pdf->Ln();
    }

    private function TaskQualSetHeight($pdf, $maxnocells) {
        $pdf->MultiCell(8, $maxnocells * 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(112, $maxnocells * 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, $maxnocells * 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, $maxnocells * 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, $maxnocells * 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, $maxnocells * 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(20, $maxnocells * 4, '', 1, 'L', 0, 0);
        $pdf->Ln();
    }

    private function TaskQuanEmpty($pdf) {
        $pdf->MultiCell(8, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(52, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(13, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(13, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(17, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(17, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(20, 4, '', 1, 'L', 0, 0);
        $pdf->Ln();
    }

    private function TaskQuanSetHeight($pdf, $maxnocells) {
        $pdf->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(52, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(13, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(13, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(17, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(17, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $pdf->Ln();
    }

    public function create_pdf () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($this->ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId Empty');
            }

            // create new PDF document
            $pdf = new MYPDF_ppm(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Muhammad Zaid');
            $pdf->SetTitle('GEMS 2.0 PPM');
            $pdf->SetSubject('GEMS 2.0 PPM');

            // set default header data
            //$pdf->SetHeaderData('', PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 011', PDF_HEADER_STRING);

            // remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(true);

            // set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            // set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // add a page
            $pdf->AddPage();

            $pdf->Image('pdf/images/logo.png', 15, 15, 50, 20, 'PNG', 'http://www.tcpdf.org', '', true, 150, '', false, false, 0, false, false, false);

            //$pdf->__set('fn_general', $this->fn_general);
            //$pdf->__set('ppmTaskId', $this->ppmTaskId);
            //$pdf->PpmTable();


            $frequencies = $this->fn_general->getPpmFrequencyCode();
            $locationCodes = $this->fn_general->getLocationCode();
            $ppmTask = Class_db::getInstance()->db_select_single('ppm_task', array('ppm_task_id'=>$this->ppmTaskId), null, 1);
            $ppm = Class_db::getInstance()->db_select_single('ppm', array('ppm_id'=>$ppmTask['ppm_id']), null, 1);
            $asset = Class_db::getInstance()->db_select_single('mw_ppm_section_a', array('ppm_task_id'=>$this->ppmTaskId), null, 1);
            $pdf->__set('ppmDocumentNo', $ppm['ppm_task_no']);
            $pdf->__set('ppmIssueNo', $ppm['ppm_issue_no']);

            $pdf->SetFont('helvetica', '', 11);
            $pdf->MultiCell(60, 20, '', 0, 'L', 0, 0, '', '');
            $pdf->MultiCell(120, 20, "\nPREVENTIVE MAINTENANCE CHECKLIST\n".strtoupper($asset['site_name']), 1, 'C', 0, 0, '', '');
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(30, 0, 0, 0);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(128, 0, 0);
            $pdf->SetLineWidth(0.2);
            $pdf->Cell(180, 6, '', 0, 0, 'L', 0);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'A', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Asset Details', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(30, 5, 'Asset Group : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $asset['asset_group_name'], 1, 0, 'L');
            $pdf->Cell(30, 5, 'Model : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $asset['asset_model_name'], 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 5, 'Asset Category : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $asset['asset_category_name'], 1, 0, 'L');
            $pdf->Cell(30, 5, 'Capacity : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $asset['asset_capacity'], 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 5, 'Asset Type : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $asset['asset_type_name'], 1, 0, 'L');
            $pdf->Cell(30, 5, 'Location Code : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $locationCodes[intval($asset['location_code_id'])], 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 5, 'Task No : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $ppm['ppm_task_no'], 1, 0, 'L');
            $pdf->Cell(30, 5, 'PM Start Date : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $this->fn_general->convertDateToDisplay($ppmTask['ppm_task_time_start']), 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 5, 'Work Order No : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $ppmTask['ppm_task_no'], 1, 0, 'L');
            $pdf->Cell(30, 5, '', 1, 0, 'R');
            $pdf->Cell(60, 5, '', 1, 0, 'L');
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'B', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Safety Precaution / General Guidelines prior to maintenance activity', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $maxnocells = 0;
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $cellcount = $pdf->MultiCell(8,4,'',0,'L',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(172,4, $ppmTask['ppm_task_guideline'],0,'L',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $pdf->SetXY($startX,$startY);
            $pdf->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
            $pdf->MultiCell(172, $maxnocells*4, '', 1, 'L', 0, 0);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'C', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Qualitative Tasks', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $maxnocells = 0;
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $cellcount = $pdf->MultiCell(8, 4, '', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(112, 4, "Description", 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(10, 4, 'Freq', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(10, 4, 'Pass', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(10, 4, 'Fail', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(10, 4, 'N/A', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(20, 4, 'Action', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $pdf->SetXY($startX, $startY);
            $this->TaskQualSetHeight($pdf, $maxnocells);

            $qualTasks = Class_db::getInstance()->db_select('ppm_task_qual', array('ppm_task_id'=>$this->ppmTaskId), 'ABS(ppm_task_qual_numb)');
            if (!empty($qualTasks)) {
                for ($i = 0; $i<(count($qualTasks)<=2?3:count($qualTasks)+1); $i++) {
                    if ($i >= count($qualTasks)) {
                        $this->TaskQualEmpty($pdf);
                        continue;
                    }
                    $maxnocells = 0;
                    $startX = $pdf->GetX();
                    $startY = $pdf->GetY();
                    $frequencyId = $qualTasks[$i]['frequency_id'];
                    $qualResult = $qualTasks[$i]['ppm_task_qual_result'];
                    $cellcount = $pdf->MultiCell(8,4, $qualTasks[$i]['ppm_task_qual_numb'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(112,4, $qualTasks[$i]['ppm_task_qual_desc'],0,'L',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(10,4, $frequencies[intval($frequencyId)],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(10,4, $qualResult==='1'?'X':'',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(10,4, $qualResult==='0'?'X':'',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(10,4, $qualResult==='2'?'X':'',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(20,4, $qualTasks[$i]['ppm_task_qual_remark'],0,'L',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $pdf->SetXY($startX,$startY);
                    $this->TaskQualSetHeight($pdf, $maxnocells);
                }
            } else {
                for ($i = 0; $i<3; $i++) {
                    $this->TaskQualEmpty($pdf);
                }
            }

            if ($pdf->GetY() > 253) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'D', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Quantitative Tasks', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $maxnocells = 0;
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $cellcount = $pdf->MultiCell(8,4,'',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(52,4, "Description",0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(13,4, 'Units',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(13,4, 'Set Value',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(17,4, 'Measured Values',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(17,4, 'Limit / Tolerance',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(10,4, 'Freq',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(10,4, 'Pass',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(10,4, 'Fail',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(10,4, 'N/A',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(20,4, 'Action',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $pdf->SetXY($startX,$startY);
            $this->TaskQuanSetHeight($pdf, $maxnocells);

            $quanTasks = Class_db::getInstance()->db_select('ppm_task_quan', array('ppm_task_id'=>$this->ppmTaskId), 'ABS(ppm_task_quan_numb)');
            if (!empty($quanTasks)) {
                for ($i = 0; $i<(count($quanTasks)<=2?3:count($quanTasks)+1); $i++) {
                    if ($i >= count($quanTasks)) {
                        $this->TaskQuanEmpty($pdf);
                        continue;
                    }
                    $maxnocells = 0;
                    $startX = $pdf->GetX();
                    $startY = $pdf->GetY();
                    $frequencyId = $quanTasks[$i]['frequency_id'];
                    $quanResult = $quanTasks[$i]['ppm_task_quan_result'];
                    $cellcount = $pdf->MultiCell(8,4,$quanTasks[$i]['ppm_task_quan_numb'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(52,4, $quanTasks[$i]['ppm_task_quan_desc'],0,'L',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(13,4, $quanTasks[$i]['ppm_task_quan_unit'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(13,4, $quanTasks[$i]['ppm_task_quan_set_values'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(17,4, $quanTasks[$i]['ppm_task_quan_measured_values'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(17,4, $quanTasks[$i]['ppm_task_quan_limit'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(10,4, $frequencies[intval($frequencyId)],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(10,4, $quanResult==='1'?'X':'',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(10,4, $quanResult==='0'?'X':'',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(10,4, $quanResult==='2'?'X':'',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $pdf->MultiCell(20,4, $quanTasks[$i]['ppm_task_quan_remark'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $pdf->SetXY($startX,$startY);
                    $this->TaskQuanSetHeight($pdf, $maxnocells);
                }
            } else {
                for ($i = 0; $i<3; $i++) {
                    $this->TaskQuanEmpty($pdf);
                }
            }

            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'E', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Spare Parts / Material Used (if any)', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(8,4,'','RL','C',0,0);
            $pdf->MultiCell(8,4,'',0,'C',0,0);
            $pdf->MultiCell(164,4,'','R','L',0,0);
            $pdf->Ln();

            $ppmParts = Class_db::getInstance()->db_select('ppm_task_parts', array('ppm_task_id'=>$this->ppmTaskId));
            if (!empty($ppmParts)) {
                for ($i = 0; $i<count($ppmParts); $i++) {
                    if ($pdf->GetY() > 272) {
                        $pdf->AddPage();
                        $pdf->setPage($pdf->getPage());
                    }

                    $pdf->MultiCell(8, 4, '', 'RL', 'C', 0, 0);
                    $pdf->MultiCell(8, 4, ($i+1).'.', 0, 'C', 0, 0);
                    $pdf->MultiCell(164, 4, $ppmParts[$i]['ppm_task_parts_desc'], 'R', 'L', 0, 0);
                    $pdf->Ln();
                }
            }

            $pdf->MultiCell(8,4,'','RLB','C',0,0);
            $pdf->MultiCell(8,4,'','B','C',0,0);
            $pdf->MultiCell(164,4,'','RB','L',0,0);
            $pdf->Ln();

            if ($pdf->GetY() > 261) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'F', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Additional Report', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(8, 10, '', 1, 0, 'C', 0);
            if ($ppmTask['ppm_task_is_additional_report'] === '1') {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->MultiCell(32, 10, " Yes", 'B', 'L', 0, 0, '','','');
                $pdf->SetFont('helvetica', '', 9);
                $pdf->MultiCell(40, 10, "No", 'B', 'L', 0, 0, '','','');
            } else {
                $pdf->MultiCell(32, 10, " Yes", 'B', 'L', 0, 0, '','','');
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->MultiCell(40, 10, "No", 'B', 'L', 0, 0, '','','');
                $pdf->SetFont('helvetica', '', 9);
            }
            $pdf->Cell(100, 10, ' Refer to ...............................................', 1, 0, 'L', 0);
            $pdf->Ln();

            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $pdf->GetY());
            if ($pdf->GetY() > 256) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'G', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Comments / Remarks', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(8,4,'','RL','C',0,0);
            $pdf->MultiCell(172,4,'','R','L',0,0);
            $pdf->Ln();
            $pdf->Cell(8, 4, '', 'RL', 0, 'C', 0);
            $pdf->MultiCell(172,4, ' '.$ppmTask['ppm_task_remark'], 'R', 'L', 0, 0);
            $pdf->Ln();
            $pdf->MultiCell(8,4,'','RLB','C',0,0);
            $pdf->MultiCell(172,4,'','RB','L',0,0);
            $pdf->Ln();

            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $servicedBy = '';
            $checkedBy = '';
            $verifyBy = '';
            if (!empty($ppmTask['ppm_task_serviced_by'])) {
                $user = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$ppmTask['ppm_task_serviced_by']), null, 1);
                $servicedBy = $user['user_first_name'].' '.$user['user_last_name'];
            }
            if (!empty($ppmTask['ppm_task_checked_by'])) {
                $user = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$ppmTask['ppm_task_checked_by']), null, 1);
                $checkedBy = $user['user_first_name'].' '.$user['user_last_name'];
            }
            if (!empty($ppmTask['ppm_task_verified_by'])) {
                $user = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$ppmTask['ppm_task_verified_by']), null, 1);
                $verifyBy = $user['user_first_name'].' '.$user['user_last_name'];
            }

            $pdf->MultiCell(60, 18, "Service By\n\n\n........................................................\nName : ".$servicedBy."\nDate : ".$this->fn_general->convertDateToDisplay($ppmTask['ppm_task_time_serviced']), 1, 'L', 0, 0);
            $pdf->MultiCell(60, 18, "Checked By\n\n\n........................................................\nName : ".$checkedBy."\nDate : ".$this->fn_general->convertDateToDisplay($ppmTask['ppm_task_time_checked']), 1, 'L', 0, 0);
            $pdf->MultiCell(60, 18, "Verified By\n\n\n........................................................\nName : ".$verifyBy."\nDate : ".$this->fn_general->convertDateToDisplay($ppmTask['ppm_task_time_verified']), 1, 'L', 0, 0);
            $pdf->Ln();

            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $pdf->GetY());
            //$pdf->Image('pdf/images/logo.png', 20, $pdf->GetY()-22, 40, 20, 'PNG', '', '', false, 300);
            //$pdf->Image('upload/6/0/f_10018.jpg', 80, $pdf->GetY()-20, 30, 10, 'JPG', '', '', true, 150, '', false, false, 0);
            //$pdf->Image('upload/6/0/f_10018.jpg', 140, $pdf->GetY()-20, 30, 10, 'JPG', '', '', true, 150, '', false, false, 0);

            // close and output PDF document
            $folder_code = floor(intval($this->ppmTaskId)/1000);
            $folder = 'pdf/ppm/'.$folder_code;

            $result = $this->fn_general->folderExist($folder);
            if (!$result) {
                mkdir ($folder,0777, true);
            }
            $filename = 'ppm_'.substr((10000+intval($this->ppmTaskId)),1).'.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : '.$filename);

            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'File : '.__FILE__);
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Environment : '.$environment);
            if ($environment == 'windows') {
                $filename_src = '\ppm\\' . $folder_code . '\\' . $filename;
            } else {
                $filename_src = '/ppm/' . $folder_code . '/' . $filename;
            }
            //$filename_src = '\ppm\\'.$folder_code.'\\'.$filename;
            $pdf->Output(dirname(__FILE__). $filename_src, 'F');

            $pdfId = $ppmTask['pdf_id'];
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_insert('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'ppm', 'pdf_folder'=>$folder));
                Class_db::getInstance()->db_update('ppm_task', array('pdf_id'=>$pdfId), array('ppm_task_id'=>$this->ppmTaskId));
            } else {
                Class_db::getInstance()->db_update('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'ppm', 'pdf_folder'=>$folder, 'pdf_timeCreated'=>'Now()'), array('pdf_id'=>$pdfId));
            }

            return $pdfId;
        } catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}