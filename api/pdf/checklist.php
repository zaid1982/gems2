<?php

class MYPDF_checklist extends TCPDF {
    private $fn_general;
    private $checklistId;
    private $checklistDocumentNo;
    private $checklistIssueNo;

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

	private function TaskQualEmpty() {
        $this->MultiCell(8, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(112, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, 4, '', 1, 'L', 0, 0);
        $this->Ln();
    }

    private function TaskQualSetHeight($maxnocells) {
        $this->MultiCell(8, $maxnocells * 4, '', 1, 'L', 0, 0);
        $this->MultiCell(112, $maxnocells * 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells * 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells * 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells * 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells * 4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells * 4, '', 1, 'L', 0, 0);
        $this->Ln();
    }

    private function TaskQuanEmpty() {
        $this->MultiCell(8, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(52, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(13, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(13, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(17, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(17, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, 4, '', 1, 'L', 0, 0);
        $this->Ln();
    }

    private function TaskQuanSetHeight($maxnocells) {
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(52, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(13, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(13, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(17, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(17, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();
    }

	public function ChecklistTable() {
        try {
            $frequencies = $this->fn_general->getPpmFrequencyCode();
            $checklist = Class_db::getInstance()->db_select_single('ppm_checklist', array('checklist_id'=>$this->checklistId), null, 1);
            $this->checklistDocumentNo = $checklist['checklist_document_no'];
            $this->checklistIssueNo = $checklist['checklist_issue_no'];

            $assetType = Class_db::getInstance()->db_select_single('ast_asset_type', array('asset_type_id'=>$checklist['asset_type_id']));
            $assetGroupName = '';
            $assetCategoryName = '';
            $assetTypeName = '';
            if (!empty($assetType)) {
                $assetTypeName = $assetType['asset_type_name'];
                $assetCategory = Class_db::getInstance()->db_select_single('ast_asset_category', array('asset_category_id'=>$assetType['asset_category_id']), null, 1);
                $assetCategoryName = $assetCategory['asset_category_name'];
                $assetGroupName = Class_db::getInstance()->db_select_col('ast_asset_group', array('asset_group_id'=>$assetCategory['asset_group_id']), 'asset_group_name', null, 1);
            }

            $this->SetFont('helvetica', '', 11);
            $this->MultiCell(60, 20, '', 0, 'L', 0, 0, '', '');
            $this->MultiCell(120, 20, "\nPREVENTIVE MAINTENANCE CHECKLIST\n[SITE NAME]", 1, 'C', 0, 0, '', '');
            $this->Ln();

            $this->SetFont('helvetica', '', 8);
            $this->SetFillColor(30, 0, 0, 0);
            $this->SetTextColor(0);
            //$this->SetDrawColor(128, 0, 0);
            $this->SetLineWidth(0.2);
            $this->Cell(180, 6, '', 0, 0, 'L', 0);
            $this->Ln();

            $this->SetFont('helvetica', '', 11);
            $this->Cell(8, 6, 'A', 1, 0, 'C', 1);
            $this->Cell(172, 6, ' Asset Details', 1, 0, 'L', 1);
            $this->Ln();

            $this->SetFont('helvetica', '', 9);
            $this->Cell(30, 5, 'Asset Group : ', 1, 0, 'R');
            $this->Cell(60, 5, $assetGroupName, 1, 0, 'L');
            $this->Cell(30, 5, 'Model : ', 1, 0, 'R');
            $this->Cell(60, 5, '', 1, 0, 'L');
            $this->Ln();
            $this->Cell(30, 5, 'Asset Category : ', 1, 0, 'R');
            $this->Cell(60, 5, $assetCategoryName, 1, 0, 'L');
            $this->Cell(30, 5, 'Capacity : ', 1, 0, 'R');
            $this->Cell(60, 5, '', 1, 0, 'L');
            $this->Ln();
            $this->Cell(30, 5, 'Asset Type : ', 1, 0, 'R');
            $this->Cell(60, 5, $assetTypeName, 1, 0, 'L');
            $this->Cell(30, 5, 'Location Code : ', 1, 0, 'R');
            $this->Cell(60, 5, '', 1, 0, 'L');
            $this->Ln();
            $this->Cell(30, 5, 'Task No : ', 1, 0, 'R');
            $this->Cell(60, 5, $this->checklistDocumentNo, 1, 0, 'L');
            $this->Cell(30, 5, 'PM Start Date : ', 1, 0, 'R');
            $this->Cell(60, 5, '', 1, 0, 'L');
            $this->Ln();
            $this->Cell(30, 5, 'Work Order No : ', 1, 0, 'R');
            $this->Cell(60, 5, '', 1, 0, 'L');
            $this->Cell(30, 5, '', 1, 0, 'R');
            $this->Cell(60, 5, '', 1, 0, 'L');
            $this->Ln();

            $this->SetFont('helvetica', '', 11);
            $this->Cell(8, 6, 'B', 1, 0, 'C', 1);
            $this->Cell(172, 6, ' Safety Precaution / General Guidelines prior to maintenance activity', 1, 0, 'L', 1);
            $this->Ln();

            $this->SetFont('helvetica', '', 9);
            $maxnocells = 0;
            $startX = $this->GetX();
            $startY = $this->GetY();
            $cellcount = $this->MultiCell(8,4,'',0,'L',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(172,4, $checklist['checklist_guideline'],0,'L',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $this->SetXY($startX,$startY);
            $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
            $this->MultiCell(172, $maxnocells*4, '', 1, 'L', 0, 0);
            $this->Ln();

            $this->SetFont('helvetica', '', 11);
            $this->Cell(8, 6, 'C', 1, 0, 'C', 1);
            $this->Cell(172, 6, ' Qualitative Tasks', 1, 0, 'L', 1);
            $this->Ln();

            $this->SetFont('helvetica', '', 9);
            $maxnocells = 0;
            $startX = $this->GetX();
            $startY = $this->GetY();
            $cellcount = $this->MultiCell(8, 4, '', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(112, 4, "Description", 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(10, 4, 'Freq', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(10, 4, 'Pass', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(10, 4, 'Fail', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(10, 4, 'N/A', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(20, 4, 'Action', 0, 'C', 0, 0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $this->SetXY($startX, $startY);
            $this->TaskQualSetHeight($maxnocells);

            $qualTasks = Class_db::getInstance()->db_select('ppm_checklist_qual', array('checklist_id'=>$this->checklistId), 'ABS(checklist_qual_numb)');
            if (!empty($qualTasks)) {
                for ($i = 0; $i<(count($qualTasks)<=2?3:count($qualTasks)+1); $i++) {
                    if ($i >= count($qualTasks)) {
                        $this->TaskQualEmpty();
                        continue;
                    }
                    $maxnocells = 0;
                    $startX = $this->GetX();
                    $startY = $this->GetY();
                    $frequencyId = $qualTasks[$i]['frequency_id'];
                    $cellcount = $this->MultiCell(8,4, $qualTasks[$i]['checklist_qual_numb'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(112,4, $qualTasks[$i]['checklist_qual_desc'],0,'L',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(10,4, $frequencies[intval($frequencyId)],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(20,4, '',0,'L',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $this->SetXY($startX,$startY);
                    $this->TaskQualSetHeight($maxnocells);
                }
            } else {
                for ($i = 0; $i<3; $i++) {
                    $this->TaskQualEmpty();
                }
            }

            if ($this->GetY() > 253) {
                $this->AddPage();
                $this->setPage($this->getPage());
            }

            $this->SetFont('helvetica', '', 11);
            $this->Cell(8, 6, 'D', 1, 0, 'C', 1);
            $this->Cell(172, 6, ' Quantitative Tasks', 1, 0, 'L', 1);
            $this->Ln();

            $this->SetFont('helvetica', '', 9);
            $maxnocells = 0;
            $startX = $this->GetX();
            $startY = $this->GetY();
            $cellcount = $this->MultiCell(8,4,'',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(52,4, "Description",0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(13,4, 'Units',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(13,4, 'Set Value',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(17,4, 'Measured Values',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(17,4, 'Limit / Tolerance',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(10,4, 'Freq',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(10,4, 'Pass',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(10,4, 'Fail',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(10,4, 'N/A',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $this->MultiCell(20,4, 'Action',0,'C',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $this->SetXY($startX,$startY);
            $this->TaskQuanSetHeight($maxnocells);

            $quanTasks = Class_db::getInstance()->db_select('ppm_checklist_quan', array('checklist_id'=>$this->checklistId), 'ABS(checklist_quan_numb)');
            if (!empty($quanTasks)) {
                for ($i = 0; $i<(count($quanTasks)<=2?3:count($quanTasks)+1); $i++) {
                    if ($i >= count($quanTasks)) {
                        $this->TaskQuanEmpty();
                        continue;
                    }
                    $maxnocells = 0;
                    $startX = $this->GetX();
                    $startY = $this->GetY();
                    $frequencyId = $quanTasks[$i]['frequency_id'];
                    $cellcount = $this->MultiCell(8,4,$quanTasks[$i]['checklist_quan_numb'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(52,4, $quanTasks[$i]['checklist_quan_desc'],0,'L',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(13,4, $quanTasks[$i]['checklist_quan_unit'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(13,4, $quanTasks[$i]['checklist_quan_set_values'],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(17,4, '',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(17,4, '',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(10,4, $frequencies[intval($frequencyId)],0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $cellcount = $this->MultiCell(20,4, '',0,'C',0,0);
                    if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
                    $this->SetXY($startX,$startY);
                    $this->TaskQuanSetHeight($maxnocells);
                }
            } else {
                for ($i = 0; $i<3; $i++) {
                    $this->TaskQuanEmpty();
                }
            }

            if ($this->GetY() > 250) {
                $this->AddPage();
                $this->setPage($this->getPage());
            }

            $this->SetFont('helvetica', '', 11);
            $this->Cell(8, 6, 'E', 1, 0, 'C', 1);
            $this->Cell(172, 6, ' Spare Parts / Material Used (if any)', 1, 0, 'L', 1);
            $this->Ln();

            $this->SetFont('helvetica', '', 9);
            $this->Cell(8, 16, '', 1, 0, 'C', 0);
            $this->Cell(172, 16, '', 1, 0, 'L', 0);
            $this->Ln();

            if ($this->GetY() > 261) {
                $this->AddPage();
                $this->setPage($this->getPage());
            }

            $this->SetFont('helvetica', '', 11);
            $this->Cell(8, 6, 'F', 1, 0, 'C', 1);
            $this->Cell(172, 6, ' Additional Report', 1, 0, 'L', 1);
            $this->Ln();

            $this->SetFont('helvetica', '', 9);
            $this->Cell(8, 10, '', 1, 0, 'C', 0);
            $this->SetFont('helvetica', 'B', 9);
            $this->MultiCell(32, 10, " Yes", 'B', 'L', 0, 0, '','','');
            $this->SetFont('helvetica', '', 9);
            $this->MultiCell(40, 10, "No", 'B', 'L', 0, 0, '','','');
            $this->Cell(100, 10, ' Refer to ...............................................', 1, 0, 'L', 0);
            $this->Ln();

            if ($this->GetY() > 250) {
                $this->AddPage();
                $this->setPage($this->getPage());
            }

            $this->SetFont('helvetica', '', 11);
            $this->Cell(8, 6, 'G', 1, 0, 'C', 1);
            $this->Cell(172, 6, ' Comments / Remarks', 1, 0, 'L', 1);
            $this->Ln();

            $this->SetFont('helvetica', '', 9);
            $this->Cell(8, 16, '', 1, 0, 'C', 0);
            $this->Cell(172, 16, '', 1, 0, 'L', 0);
            $this->Ln();

            if ($this->GetY() > 250) {
                $this->AddPage();
                $this->setPage($this->getPage());
            }

            $this->MultiCell(60, 18, "Service By\n\n.................................\nName :\nDate :", 1, 'L', 0, 0);
            $this->MultiCell(60, 18, "Checked By\n\n.................................\nName :\nDate :", 1, 'L', 0, 0);
            $this->MultiCell(60, 18, "Verified By\n\n.................................\nName :\nDate :", 1, 'L', 0, 0);

            // close and output PDF document
            $folder_code = floor(intval($this->checklistId)/1000);
            $folder = 'pdf/checklist/'.$folder_code;

            $result = $this->fn_general->folderExist($folder);
            if (!$result) {
                mkdir ($folder,0777, true);
            }
            $filename = 'checklist_'.substr((10000+intval($this->checklistId)),1).'.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : '.$filename);
            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'File : '.__FILE__);
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Environment : '.$environment);
            if ($environment == 'windows') {
                $filename_src = '\checklist\\' . $folder_code . '\\' . $filename;
            } else {
                $filename_src = '/checklist/' . $folder_code . '/' . $filename;
            }
            $this->Output(dirname(__FILE__). $filename_src, 'F');

            $pdfId = $checklist['pdf_id'];
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_insert('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'checklist', 'pdf_folder'=>$folder));
                Class_db::getInstance()->db_update('ppm_checklist', array('pdf_id'=>$pdfId), array('checklist_id'=>$this->checklistId));
            } else {
                Class_db::getInstance()->db_update('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'checklist', 'pdf_folder'=>$folder, 'pdf_timeCreated'=>'Now()'), array('pdf_id'=>$pdfId));
            }

            return $pdfId;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
	}

    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Line(PDF_MARGIN_LEFT, $this->y, $this->w - PDF_MARGIN_RIGHT, $this->y);
        $pageNo = 'Page '.strval($this->getAliasNumPage()).' of '.$this->getAliasNbPages();
        $this->Cell(85, 6, 'Document No : '.$this->checklistDocumentNo, 0, 0, 'L', 0);
        $this->Cell(50, 6, 'Issue No : '.$this->checklistIssueNo, 0, 0, 'L', 0);
        $this->Cell(55, 6, $pageNo, 0, 0, 'R', 0);
    }
}

class Class_pdf_checklist {
    private $fn_general;
    private $checklistId;

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

    public function create_pdf () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($this->checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId Empty');
            }

            // create new PDF document
            $pdf = new MYPDF_checklist(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Muhammad Zaid');
            $pdf->SetTitle('GEMS 2.0 Checklist');
            $pdf->SetSubject('GEMS 2.0 Checklist');

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

            $pdf->__set('fn_general', $this->fn_general);
            $pdf->__set('checklistId', $this->checklistId);
            return $pdf->ChecklistTable();
            //return '9';
            //$pdf->Output('example_011.pdf', 'I');
        } catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}

