<?php

class MYPDF_ptw extends TCPDF {
    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Line(PDF_MARGIN_LEFT, $this->y, $this->w - PDF_MARGIN_RIGHT, $this->y);
        $pageNo = 'Page '.strval($this->getAliasNumPage()).' of '.$this->getAliasNbPages();
        $this->Cell(180, 6, $pageNo, 0, 0, 'R', 0);
    }
}

class Class_pdf_ptw {
    private $fn_general;
    private $ptwId;

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

    /**
     * Render checkbox with checked/unchecked status
     */
    private function renderCheckbox($pdf, $x, $y, $isChecked = false, $size = 3) {
        $pdf->Rect($x, $y, $size, $size, 'D');
        if ($isChecked) {
            $pdf->Text($x + 0.5, $y + 2.5, '✓');
        }
    }

    /**
     * Render radio button with selected status
     */
    private function renderRadioButton($pdf, $x, $y, $isSelected = false, $size = 3) {
        $pdf->Circle($x + ($size/2), $y + ($size/2), $size/2, 0, 360, 'D');
        if ($isSelected) {
            $pdf->Circle($x + ($size/2), $y + ($size/2), $size/4, 0, 360, 'F');
        }
    }

    public function create_pdf() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($this->ptwId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ptwId Empty');
            }

            // create new PDF document
            $pdf = new MYPDF_ptw(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('GEMS 2.0');
            $pdf->SetTitle('Permit To Work');
            $pdf->SetSubject('PTW Permit');

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

            // Get site and user data
            $arrSiteName = $this->fn_general->getSiteName();
            $arrUserFullName = $this->fn_general->getUserFullName();

            // Get PTW data from database (new table name ptw_permit; fallback to legacy ptw_permits)
            $ptwData = null;
            try {
                $ptwData = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id'=>$this->ptwId), null, 1);
            } catch (Exception $e) { /* ignore */ }
            if (empty($ptwData)) {
                $ptwData = Class_db::getInstance()->db_select_single('ptw_permits', array('ptw_id'=>$this->ptwId), null, 1);
            }
            
            if (empty($ptwData)) {
                throw new Exception('[' . __LINE__ . '] - PTW data not found for ID: ' . $this->ptwId);
            }

            // Get client ID for logo
            $clientId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$ptwData['site_id']), 'client_id', null, 1);

            // Parse JSON data
            $workers = !empty($ptwData['ptw_workers']) ? json_decode($ptwData['ptw_workers'], true) : [];
            $workTypes = !empty($ptwData['ptw_work_types']) ? json_decode($ptwData['ptw_work_types'], true) : [];
            $hazardChecklist = !empty($ptwData['ptw_hazard_checklist']) ? json_decode($ptwData['ptw_hazard_checklist'], true) : [];
            $coldWorkChecklist = !empty($ptwData['ptw_checklist_cold_work']) ? json_decode($ptwData['ptw_checklist_cold_work'], true) : [];
            $hotWorkChecklist = !empty($ptwData['ptw_checklist_hot_work']) ? json_decode($ptwData['ptw_checklist_hot_work'], true) : [];
            $confinedSpaceChecklist = !empty($ptwData['ptw_checklist_confined_space']) ? json_decode($ptwData['ptw_checklist_confined_space'], true) : [];
            $declarationChecklist = !empty($ptwData['ptw_declaration_checklist']) ? json_decode($ptwData['ptw_declaration_checklist'], true) : [];
            $supportingDocs = !empty($ptwData['ptw_supporting_docs_checklist']) ? json_decode($ptwData['ptw_supporting_docs_checklist'], true) : [];
            $certificateNumbers = !empty($ptwData['ptw_certificate_numbers']) ? json_decode($ptwData['ptw_certificate_numbers'], true) : [];

            // Header with logo and title
            $pdf->Image('pdf/images/logo_'.$clientId.'.png', 15, 15, 50, 20, 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);

            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->MultiCell(60, 20, '', 0, 'L', 0, 0, '', '');
            $pdf->MultiCell(120, 20, "\nPERMIT TO WORK\n".strtoupper($arrSiteName[intval($ptwData['site_id'] ?? 0)]), 1, 'C', 0, 0, '', '');
            $pdf->Ln();

            // Add spacing
            $pdf->Cell(180, 10, '', 0, 0, 'L', 0);
            $pdf->Ln();

            // SECTION 1: Basic Information
            $this->renderSection1($pdf, $ptwData);

            // SECTION 2: Work Description & Risk Assessment
            $this->renderSection2($pdf, $ptwData);

            // SECTION 3: Work Types & Hazardous Activities
            $this->renderSection3($pdf, $workTypes, $hazardChecklist, $coldWorkChecklist, $hotWorkChecklist, $confinedSpaceChecklist);

            // SECTION 4: PPE Requirements
            $this->renderSection4($pdf, $hazardChecklist);

            // SECTION 5: Workers Information
            $this->renderSection5($pdf, $workers);

            // SECTION 6: Supporting Documents & Certificates
            $this->renderSection6($pdf, $supportingDocs, $certificateNumbers);

            // SECTION 7: Contractor Declaration
            $this->renderSection7($pdf, $ptwData, $declarationChecklist);

            // SECTION 8: Approval Signatures
            $this->renderSection8($pdf, $ptwData, $arrUserFullName);

            // Save PDF file
            return $this->savePdfFile($pdf, $ptwData);

        } catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Create a minimal PDF with basic text for quick end-to-end testing.
     * Does not touch database; only writes a file at api/pdf/ptw/<folder>/<filename>.
     */
    public function create_basic_pdf() {
        try {
            if (empty($this->ptwId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ptwId Empty');
            }

            // Try to load PTW data (new schema first, then legacy)
            $ptwData = null;
            try {
                $ptwData = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id'=>$this->ptwId), null, 1);
            } catch (Exception $e) { /* ignore */ }
            if (empty($ptwData)) {
                try {
                    $ptwData = Class_db::getInstance()->db_select_single('ptw_permits', array('ptw_id'=>$this->ptwId), null, 1);
                } catch (Exception $e) { /* ignore */ }
            }

            // Minimal PDF
            $pdf = new MYPDF_ptw(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 18);
            $pdf->Cell(0, 12, 'PERMIT TO WORK', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 11);
            $displayNo = '';
            if (!empty($ptwData)) {
                $displayNo = !empty($ptwData['ptw_permit_number']) ? $ptwData['ptw_permit_number'] : ($ptwData['ptw_request_number'] ?? '');
            }
            $pdf->Cell(0, 8, 'Permit/Request No: ' . ($displayNo ?: '-'), 0, 1, 'C');

            $pdf->Ln(4);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Basic Information', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);

            // Helper closures for safe access
            $safe = function($key) use ($ptwData) { return isset($ptwData[$key]) ? $ptwData[$key] : null; };
            $disp = function($val) { return ($val === null || $val === '') ? '-' : $val; };
            $toDate = function($val) { return ($val ? $this->fn_general->convertDateToDisplay($val) : '-'); };

            // Two-column simple grid
            $pdf->Cell(50, 7, 'Work Area:', 0, 0, 'L');
            $pdf->Cell(0, 7, $this->fn_general->clear_null($safe('ptw_work_area') ?: '-'), 0, 1, 'L');

            $pdf->Cell(50, 7, 'Valid From:', 0, 0, 'L');
            $pdf->Cell(0, 7, $toDate($safe('ptw_valid_from')), 0, 1, 'L');

            $pdf->Cell(50, 7, 'Valid To:', 0, 0, 'L');
            $pdf->Cell(0, 7, $toDate($safe('ptw_valid_to')), 0, 1, 'L');

            $pdf->Cell(50, 7, 'Risk Level:', 0, 0, 'L');
            $pdf->Cell(0, 7, $this->fn_general->clear_null($safe('ptw_risk_level') ?: '-'), 0, 1, 'L');

            $pdf->Cell(50, 7, 'Applicant Name:', 0, 0, 'L');
            $pdf->Cell(0, 7, $this->fn_general->clear_null($safe('ptw_applicant_name') ?: '-'), 0, 1, 'L');

            $pdf->Cell(50, 7, 'Applicant Contact:', 0, 0, 'L');
            $pdf->Cell(0, 7, $this->fn_general->clear_null($safe('ptw_applicant_contact') ?: '-'), 0, 1, 'L');

            $pdf->Cell(50, 7, 'Contractor Company:', 0, 0, 'L');
            $pdf->Cell(0, 7, $this->fn_general->clear_null($safe('ptw_contractor_company') ?: '-'), 0, 1, 'L');

            $pdf->Cell(50, 7, 'Contractor Supervisor:', 0, 0, 'L');
            $pdf->Cell(0, 7, $this->fn_general->clear_null($safe('ptw_contractor_supervisor') ?: '-'), 0, 1, 'L');

            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Work Description', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $desc = $this->fn_general->clear_null($safe('ptw_permit_description') ?: '-');
            $pdf->MultiCell(0, 6, $desc, 0, 'L');

            $pdf->Ln(6);
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->Cell(0, 6, 'Generated on ' . date('Y-m-d H:i:s'), 0, 1, 'R');

            // Optional: Work Types & PPE Summary
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Work Types & PPE', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);

            // Work Types
            $workTypes = [];
            if (!empty($ptwData)) {
                if (!empty($ptwData['ptw_work_types'])) {
                    $tmp = json_decode($ptwData['ptw_work_types'], true);
                    if (is_array($tmp)) { $workTypes = $tmp; }
                }
                if (empty($workTypes) && !empty($ptwData['ptw_work_type'])) {
                    $workTypes = [$ptwData['ptw_work_type']];
                }
            }
            $workTypesDisp = (!empty($workTypes) && is_array($workTypes)) ? implode(', ', array_map(function($x){ return is_string($x) ? ucwords(str_replace('_',' ', strtolower($x))) : $x; }, $workTypes)) : '-';
            $pdf->Cell(50, 7, 'Work Types:', 0, 0, 'L');
            $pdf->Cell(0, 7, $this->fn_general->clear_null($workTypesDisp), 0, 1, 'L');

            // PPE from hazard checklist
            $ppeList = [];
            if (!empty($ptwData['ptw_hazard_checklist'])) {
                $haz = json_decode($ptwData['ptw_hazard_checklist'], true);
                if (is_array($haz)) {
                    $ppeLabels = [
                        'ppe_safety_helmet' => 'Safety Helmet',
                        'ppe_safety_shoes' => 'Safety Shoes',
                        'ppe_high_vis_vest' => 'High Visibility Vest',
                        'ppe_dust_mask' => 'Dust Mask',
                        'ppe_respirator' => 'Respirator',
                        'ppe_scba' => 'SCBA',
                        'ppe_safety_glasses' => 'Safety Glasses',
                        'ppe_face_shield' => 'Face Shield',
                        'ppe_coveralls' => 'Coveralls',
                        'ppe_chemical_suit' => 'Chemical Suit',
                        'ppe_work_gloves' => 'Work Gloves',
                        'ppe_chemical_gloves' => 'Chemical Gloves',
                        'ppe_cut_resistant' => 'Cut Resistant Gloves',
                        'ppe_ear_plugs' => 'Ear Plugs',
                        'ppe_ear_muffs' => 'Ear Muffs'
                    ];
                    foreach ($ppeLabels as $key => $label) {
                        if (isset($haz[$key]) && $haz[$key]) { $ppeList[] = $label; }
                    }
                    if (!empty($haz['ppe_others'])) {
                        $ppeList[] = 'Others: ' . $haz['ppe_others'];
                    }
                }
            }
            $ppeDisp = !empty($ppeList) ? implode(', ', $ppeList) : '-';
            $pdf->Cell(50, 7, 'PPE:', 0, 0, 'L');
            $pdf->Cell(0, 7, $this->fn_general->clear_null($ppeDisp), 0, 1, 'L');

            // Approvals Summary
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Approvals', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);

            $arrUserFullName = $this->fn_general->getUserFullName();
            $row = function($label, $statusKey, $userKey, $dateKey) use ($pdf, $ptwData, $arrUserFullName) {
                $status = isset($ptwData[$statusKey]) ? $ptwData[$statusKey] : 'PENDING';
                $name = '';
                $date = '';
                if (!empty($ptwData[$userKey])) {
                    $uid = intval($ptwData[$userKey]);
                    $name = isset($arrUserFullName[$uid]) ? $arrUserFullName[$uid] : ('User #'.$uid);
                }
                if (!empty($ptwData[$dateKey])) {
                    $date = $this->fn_general->convertDateToDisplay($ptwData[$dateKey]);
                }
                $pdf->Cell(40, 7, $label.':', 0, 0, 'L');
                $pdf->Cell(35, 7, 'Status: ' . $status, 0, 0, 'L');
                $pdf->Cell(75, 7, 'Name: ' . ($name ?: '-'), 0, 0, 'L');
                $pdf->Cell(0, 7, 'Date: ' . ($date ?: '-'), 0, 1, 'L');
            };
            $row('Supervisor', 'ptw_supervisor_approval', 'approved_supervisor_by', 'approved_supervisor_date');
            $row('SHE', 'ptw_she_approval', 'approved_she_by', 'approved_she_date');
            $row('Facility Manager', 'ptw_fm_approval', 'approved_fm_by', 'approved_fm_date');

            // Write file with deterministic name
            $folder_code = floor(intval($this->ptwId)/1000);
            $folder_rel = '../../upload/ptw/pdf/' . $folder_code; // relative to api/pdf
            $folder_abs = dirname(__FILE__) . '/' . $folder_rel;
            if (!is_dir($folder_abs)) {
                @mkdir($folder_abs, 0777, true);
            }
            $filename = 'ptw_' . substr((10000000+intval($this->ptwId)), 1) . '.pdf';
            $pdf->Output($folder_abs . '/' . $filename, 'F');

            return array(
                'filename' => $filename,
                'folder' => 'upload/ptw/pdf/' . $folder_code
            );
        } catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0099', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Create a styled PDF approximating the provided PTW sample layout (header + Section 1 skeleton).
     * Uses TCPDF primitives for reliable rendering; saves to /upload/ptw/pdf.
     */
    public function create_template_pdf() {
        try {
            if (empty($this->ptwId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ptwId Empty');
            }

            // Load PTW (new first, legacy fallback)
            $ptw = null;
            try { $ptw = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id'=>$this->ptwId), null, 1); } catch (Exception $e) {}
            if (empty($ptw)) {
                try { $ptw = Class_db::getInstance()->db_select_single('ptw_permits', array('ptw_id'=>$this->ptwId), null, 1); } catch (Exception $e) {}
            }

            $pdf = new MYPDF_ptw(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(12, 12, 12);
            $pdf->AddPage();

            // Header with logo + title
            $logoPath = dirname(__FILE__) . '/../../img/icon/gfm-logo-transparent.png';
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, 12, 12, 32, 0, 'PNG');
            }
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 8, 'PERMIT TO WORK', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(220, 53, 69); // red accent
            $pdf->Cell(0, 6, 'WORK FAST, THINK SMART, STAY SAFE', 0, 1, 'C');
            $pdf->SetTextColor(0);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(0, 5, 'BPM 9.1/F/013/25:1', 0, 0, 'C');
            $pdf->SetXY(160, 12);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'PTW NO : ' . (!empty($ptw['ptw_permit_number']) ? $ptw['ptw_permit_number'] : '-'), 0, 1, 'R');

            // Section 1 banner
            $pdf->Ln(5);
            $pdf->SetFillColor(0, 123, 255); // blue
            $pdf->SetTextColor(255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 7, 'SECTION 1 : REQUISITION (To be filled-up by Applicant)', 0, 1, 'L', 1);
            $pdf->SetTextColor(0);

            // DETAILS OF JOB APPLICATION header
            $pdf->SetFillColor(235, 235, 235);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 6, 'DETAILS OF JOB APPLICATION', 1, 1, 'L', 1);
            $pdf->SetFont('helvetica', '', 9);

            // Two-column grid rows
            $row = function($labelL, $valL, $labelR, $valR) use ($pdf) {
                $pdf->Cell(35, 6, $labelL, 1, 0, 'L');
                $pdf->Cell(70, 6, $valL, 1, 0, 'L');
                $pdf->Cell(35, 6, $labelR, 1, 0, 'L');
                $pdf->Cell(0, 6, $valR, 1, 1, 'L');
            };

            $row('Applicant Name :', $this->fn_general->clear_null($ptw['ptw_applicant_name'] ?? ''), 'Staff No. / NRIC No :', $this->fn_general->clear_null($ptw['ptw_staff_nric'] ?? ''));
            $row('Contractor Supervisor in Charge :', $this->fn_general->clear_null($ptw['ptw_contractor_supervisor'] ?? ''), 'Contact No :', $this->fn_general->clear_null($ptw['ptw_supervisor_contact'] ?? ''));
            $row('Company / Department :', $this->fn_general->clear_null($ptw['ptw_applicant_company_dept'] ?? ''), 'Identification No :', $this->fn_general->clear_null($ptw['ptw_identification_no'] ?? ''));
            $row('Duration of Work - From: Date/Time', '', 'To: Date/Time', '');
            $row('Work Area :', $this->fn_general->clear_null($ptw['ptw_work_area'] ?? ''), 'Level :', $this->fn_general->clear_null($ptw['ptw_level'] ?? ''));

            // Description of Work (multi-line)
            $pdf->Cell(35, 8, 'Description of Work :', 1, 0, 'L');
            $x = $pdf->GetX(); $y = $pdf->GetY();
            $pdf->MultiCell(0, 8, $this->fn_general->clear_null($ptw['ptw_permit_description'] ?? ''), 1, 'L');

            // List of Workers table header
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 6, 'List of Workers', 1, 1, 'L', 1);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell(10, 6, 'No.', 1, 0, 'C');
            $pdf->Cell(80, 6, 'Name', 1, 0, 'L');
            $pdf->Cell(45, 6, 'Designation', 1, 0, 'L');
            $pdf->Cell(0, 6, 'Identification No. (IC/CIDB Card/Passport)', 1, 1, 'L');
            $pdf->SetFont('helvetica', '', 8);

            $workers = [];
            if (!empty($ptw['ptw_workers'])) {
                $tmp = json_decode($ptw['ptw_workers'], true);
                if (is_array($tmp)) { $workers = $tmp; }
            }
            for ($i=1; $i<=10; $i++) {
                $w = $workers[$i-1] ?? [];
                $pdf->Cell(10, 6, strval($i), 1, 0, 'C');
                $pdf->Cell(80, 6, $this->fn_general->clear_null($w['name'] ?? ''), 1, 0, 'L');
                $pdf->Cell(45, 6, $this->fn_general->clear_null($w['designation'] ?? ''), 1, 0, 'L');
                $pdf->Cell(0, 6, $this->fn_general->clear_null($w['identification'] ?? ''), 1, 1, 'L');
            }

            // Save file
            $folder_code = floor(intval($this->ptwId)/1000);
            $folder_abs = dirname(__FILE__) . '/../../upload/ptw/pdf/' . $folder_code;
            if (!is_dir($folder_abs)) { @mkdir($folder_abs, 0777, true); }
            $filename = 'ptw_' . substr((10000000+intval($this->ptwId)), 1) . '.pdf';
            $pdf->Output($folder_abs . '/' . $filename, 'F');

            return array('filename'=>$filename, 'folder'=>'upload/ptw/pdf/'.$folder_code);

        } catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0101', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Render PDF from an HTML template using TCPDF's writeHTML.
     * Resolves local assets (logo) to absolute filesystem paths.
     */
    public function create_pdf_from_html_template($templateFilename = 'ptw_form_html_replica.html') {
        try {
            if (empty($this->ptwId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ptwId Empty');
            }

            $templatePath = dirname(__FILE__) . '/templates/' . $templateFilename;
            if (!file_exists($templatePath)) {
                throw new Exception('Template not found: ' . $templatePath);
            }

            $html = file_get_contents($templatePath);

            // Resolve logo path and common img directory
            $base = dirname(__FILE__) . '/../../'; // points to gems2/
            $absLogo = $base . 'img/icon/gfm-logo-transparent.png';
            $html = str_replace('img/icon/gfm-logo-transparent.png', $absLogo, $html);
            // Best-effort replace for other relative images if any
            $html = preg_replace('#src\s*=\s*[\"\']img/#i', 'src="' . rtrim(str_replace('\\', '/', $base), '/') . '/img/', $html);

            // Strip script tags (not supported/needed in PDF)
            $html = preg_replace('#<script[\s\S]*?</script>#i', '', $html);

            $pdf = new MYPDF_ptw(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(8, 8, 8);
            $pdf->AddPage();

            $pdf->writeHTML($html, true, false, true, false, '');

            // Save to upload path
            $folder_code = floor(intval($this->ptwId)/1000);
            $folder_abs = dirname(__FILE__) . '/../../upload/ptw/pdf/' . $folder_code;
            if (!is_dir($folder_abs)) { @mkdir($folder_abs, 0777, true); }
            $filename = 'ptw_' . substr((10000000+intval($this->ptwId)), 1) . '.pdf';
            $pdf->Output($folder_abs . '/' . $filename, 'F');

            return array('filename'=>$filename, 'folder'=>'upload/ptw/pdf/'.$folder_code);

        } catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0102', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

        /**
         * Create PDF using the two-page TCPDF HTML design provided (incorporated from attachment).
         * Keeps it static for now (placeholders), saves to /upload/ptw/pdf like other generators.
         */
        public function create_pdf_from_attachment_design() {
                try {
                        if (empty($this->ptwId)) {
                                throw new Exception('[' . __LINE__ . '] - Parameter ptwId Empty');
                        }

                        // Base path for resolving local images
                        $base = dirname(__FILE__) . '/../../'; // gems2/
                        $absLogo = $base . 'img/icon/gfm-logo-transparent.png';

                        // Shared styles from the provided attachment
                        $styles = <<<CSS
<style>
    .title      { font-size: 15px; font-weight: 800; letter-spacing: .2px; }
    .subtitle   { font-size: 9px; color: #555; }
    .muted      { color: #666; font-size: 8px; }
    .small      { font-size: 8px; }
    .sec-h      { background-color: #f3f5f8; font-weight: 700; border: 0.3mm solid #d8dde5; padding: 6px; }
    .section    { border: 0.3mm solid #d8dde5; }
    .p-6        { padding: 6px; }
    .mt-3       { margin-top: 3px; }
    .mb-2       { margin-bottom: 2px; }
    .mb-4       { margin-bottom: 4px; }
    .mb-6       { margin-bottom: 6px; }
    .tb         { border-collapse: collapse; width: 100%; }
    .tb th, .tb td { border: 0.3mm solid #d8dde5; padding: 5px; vertical-align: top; }
    .tb thead th { background-color: #fafbff; font-weight: bold; }
    .box        { border: 0.3mm dashed #d8dde5; padding: 4px; }
    .pill       { border: 0.3mm solid #d8dde5; border-radius: 99px; padding: 3px 8px; display: inline-block; margin: 2px 3px 2px 0; }
    .hstack     { width: 100%; }
    .lh         { line-height: 1.35; }
    .hline      { border-bottom: 0.3mm solid #d8dde5; height: 12px; display:inline-block; min-width:20mm; }
    .checkbox   { font-family: dejavusans; }
    .pad-cell   { padding: 3px 5px; }
    .two-cell   { width: 50%; }
</style>
CSS;

                        // Page 1 HTML (header + sections 0-4)
                        $page1 = <<<HTML
{$styles}
<table class="hstack" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:70%; vertical-align: middle;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width:12mm; height:12mm; border:0.3mm solid #d8dde5; background-color:#f3f5f8; text-align:center;">
                        <!-- logo box -->
                        <img src="{$absLogo}" height="12" />
                    </td>
                    <td style="padding-left:6px;">
                        <div class="title">PERMIT TO WORK</div>
                        <div class="subtitle">Work Fast • Think Smart • Stay Safe</div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width:30%; text-align:right; font-size:9px;">
            <b>Form Code:</b> BPM 9.1/F/013/25:1<br/>
            <b>PTW No:</b> <span style="display:inline-block; min-width:28mm; border-bottom:0.3mm solid #d8dde5;">&nbsp;</span>
        </td>
    </tr>
</table>

<!-- Permit Metadata -->
<table class="section mt-3" cellpadding="0" cellspacing="0">
    <tr><td class="sec-h">Permit Metadata <span class="small" style="color:#5b6371;">(For admin tracking)</span></td></tr>
    <tr><td class="p-6">
        <table class="tb">
            <tr>
                <td style="width:33%"><b>Building / Site</b><br/><div class="hline"></div></td>
                <td style="width:33%"><b>Level</b><br/><div class="hline"></div></td>
                <td style="width:34%"><b>Work Area</b><br/><div class="hline"></div></td>
            </tr>
            <tr>
                <td><b>Valid From</b><br/>Date: <span class="hline"></span> Time: <span class="hline"></span></td>
                <td><b>Valid To</b><br/>Date: <span class="hline"></span> Time: <span class="hline"></span></td>
                <td><b>Company / Dept.</b><br/><div class="hline"></div></td>
            </tr>
        </table>
    </td></tr>
</table>

<!-- Section 1: Requisition -->
<table class="section mt-3" cellpadding="0" cellspacing="0">
    <tr><td class="sec-h">Section 1 — Requisition <span class="small" style="color:#5b6371;">(To be filled by applicant)</span></td></tr>
    <tr><td class="p-6">
        <table class="tb">
            <tr>
                <td><b>Applicant Name</b><br/><div class="hline"></div></td>
                <td><b>Contact No</b><br/><div class="hline"></div></td>
                <td><b>Staff / NRIC No</b><br/><div class="hline"></div></td>
            </tr>
            <tr>
                <td><b>Contractor Supervisor</b><br/><div class="hline"></div></td>
                <td><b>Contact No</b><br/><div class="hline"></div></td>
                <td><b>Identification No</b><br/><div class="hline"></div></td>
            </tr>
        </table>

        <div class="mb-4"></div>
        <b>List of Workers</b>
        <table class="tb mt-3">
            <thead>
                <tr>
                    <th style="width:8%">#</th>
                    <th>Identification No. (IC / CIDB / Passport)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>1</td><td></td></tr>
                <tr><td>2</td><td></td></tr>
                <tr><td>3</td><td></td></tr>
                <tr><td>4</td><td></td></tr>
                <tr><td>5</td><td></td></tr>
                <tr><td>6</td><td></td></tr>
                <tr><td>7</td><td></td></tr>
                <tr><td>8</td><td></td></tr>
                <tr><td>9</td><td></td></tr>
                <tr><td>10</td><td></td></tr>
            </tbody>
        </table>
        <div class="muted mt-3">Provide additional list if space is insufficient.</div>

        <div class="mb-4"></div>
        <b>Description of Work</b>
        <table class="tb mt-3">
            <tr><td style="height:28mm;"></td></tr>
        </table>
    </td></tr>
</table>

<!-- Section 2: Cold Work / Other Works -->
<table class="section mt-3" cellpadding="0" cellspacing="0">
    <tr><td class="sec-h">Section 2 — Cold Work / Other Works <span class="small" style="color:#5b6371;">(Conditions to be implemented by Supervisor)</span></td></tr>
    <tr><td class="p-6">
        <table class="tb">
            <tr>
                <td class="pad-cell"><span class="checkbox">☐</span> Electrical work</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Working at height</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Excavation work</td>
            </tr>
            <tr>
                <td class="pad-cell"><span class="checkbox">☐</span> Working under load</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Lifting work</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Chemical handling</td>
            </tr>
            <tr>
                <td class="pad-cell"><span class="checkbox">☐</span> Circuit isolation</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Lock-out / Tag-out</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Fire extinguisher</td>
            </tr>
            <tr>
                <td class="pad-cell"><span class="checkbox">☐</span> Main supply cut-off</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Abseiling work</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Scaffolding</td>
            </tr>
            <tr>
                <td class="pad-cell"><span class="checkbox">☐</span> Gondola</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Rooftop access</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Use of ladder</td>
            </tr>
            <tr>
                <td class="pad-cell"><span class="checkbox">☐</span> Depth &lt; 1.2 m</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Depth &gt; 1.2 m (confined space)</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Safe access &amp; egress</td>
            </tr>
            <tr>
                <td class="pad-cell"><span class="checkbox">☐</span> Protection from falling material</td>
                <td class="pad-cell"><span class="checkbox">☐</span> Protection from engulfment</td>
                <td class="pad-cell">Others: <span class="hline" style="display:inline-block; width:34mm;"></span></td>
            </tr>
        </table>
        <div class="muted mt-3">Approval is valid provided all listed conditions/precautions are complied with.</div>
    </td></tr>
</table>

<!-- Section 3: Hot Work -->
<table class="section mt-3" cellpadding="0" cellspacing="0">
    <tr><td class="sec-h">Section 3 — Hot Work <span class="small" style="color:#5b6371;">(Conditions to be implemented by Supervisor)</span></td></tr>
    <tr><td class="p-6">
        <table class="tb">
            <tr>
                <td style="width:50%; vertical-align:top;">
                    <b>Type of Hot Work</b><br/><br/>
                    <span class="pill"><span class="checkbox">☐</span> Welding</span>
                    <span class="pill"><span class="checkbox">☐</span> Flame cutting</span>
                    <span class="pill"><span class="checkbox">☐</span> Open flame</span>
                    <span class="pill"><span class="checkbox">☐</span> Grinding</span>
                    <span class="pill"><span class="checkbox">☐</span> Blasting</span>
                    <span class="pill"><span class="checkbox">☐</span> Power brushing</span>
                    <span class="pill"><span class="checkbox">☐</span> Gouging</span>
                    <span class="pill"><span class="checkbox">☐</span> PWHT</span>
                    <span class="pill"><span class="checkbox">☐</span> Pyrophoric material</span>
                    <span class="pill"><span class="checkbox">☐</span> Others</span>
                </td>
                <td style="width:50%; vertical-align:top;">
                    <b>Controls Required</b><br/><br/>
                    <table class="tb">
                        <tr><td>Gas monitoring — <span class="checkbox">☐</span> Continuous &nbsp; <span class="checkbox">☐</span> Every <span class="hline" style="display:inline-block; width:10mm;"></span> hour(s)</td></tr>
                        <tr><td>Firewatch name: <span class="hline" style="display:inline-block; width:50mm;"></span></td></tr>
                        <tr><td>Fire extinguisher (Type/Qty): <span class="hline" style="display:inline-block; width:36mm;"></span></td></tr>
                        <tr><td><span class="checkbox">☐</span> Fire retardant screen</td></tr>
                        <tr><td><span class="checkbox">☐</span> Cover flammable sewer/drain/sump</td></tr>
                        <tr><td><span class="checkbox">☐</span> Relocate spark-producing equipment</td></tr>
                        <tr><td><span class="checkbox">☐</span> Remove flammable/combustible materials</td></tr>
                        <tr><td><span class="checkbox">☐</span> Fire blanket</td></tr>
                        <tr><td>Others: <span class="hline" style="display:inline-block; width:50mm;"></span></td></tr>
                    </table>
                </td>
            </tr>
        </table>
        <div class="muted mt-3">Approval is valid provided all listed conditions/precautions are complied with.</div>
    </td></tr>
</table>

<!-- Section 4: Confined Space Entry -->
<table class="section mt-3" cellpadding="0" cellspacing="0">
    <tr><td class="sec-h">Section 4 — Confined Space Entry <span class="small" style="color:#5b6371;">(Conditions to be implemented by Entry Supervisor)</span></td></tr>
    <tr><td class="p-6">
        <table class="tb">
            <tr>
                <td style="width:50%; vertical-align:top;">
                    <b>Entry Condition</b><br/><br/>
                    <table class="tb">
                        <tr><td><span class="checkbox">☐</span> Respirable atmosphere</td></tr>
                        <tr><td><span class="checkbox">☐</span> Irrespirable atmosphere</td></tr>
                        <tr><td><span class="checkbox">☐</span> First entry using SCBA/air line</td></tr>
                        <tr><td><span class="checkbox">☐</span> Entrants are CSE certified</td></tr>
                        <tr><td><span class="checkbox">☐</span> Entrants briefed by supervisor</td></tr>
                        <tr><td><span class="checkbox">☐</span> Entry status board/log</td></tr>
                        <tr><td><span class="checkbox">☐</span> Max persons allowed:</td></tr>
                        <tr><td><span class="checkbox">☐</span> Continuous ventilation</td></tr>
                        <tr><td><span class="checkbox">☐</span> Check every ____ hour(s)</td></tr>
                        <tr><td><span class="checkbox">☐</span> CSE checklist attached</td></tr>
                    </table>
                </td>
                <td style="width:50%; vertical-align:top;">
                    <b>Controls Required</b><br/><br/>
                    <table class="tb">
                        <tr><td>Type of communication used: <span class="hline" style="display:inline-block; width:50mm;"></span></td></tr>
                        <tr><td><span class="checkbox">☐</span> Standby person</td></tr>
                        <tr><td><span class="checkbox">☐</span> Rope signals</td></tr>
                        <tr><td>Name: <span class="hline" style="display:inline-block; width:50mm;"></span></td></tr>
                        <tr><td><span class="checkbox">☐</span> ELV lighting (≤50V)</td></tr>
                        <tr><td><span class="checkbox">☐</span> Rescue equipment</td></tr>
                        <tr><td><span class="checkbox">☐</span> Complete CSE checklist</td></tr>
                        <tr><td><span class="checkbox">☐</span> Walkie talkie</td></tr>
                        <tr><td><span class="checkbox">☐</span> Whistle / horn</td></tr>
                        <tr><td><span class="checkbox">☐</span> Keep mobile equipment away from manhole</td></tr>
                        <tr><td>Others: <span class="hline" style="display:inline-block; width:50mm;"></span></td></tr>
                    </table>
                    <div class="muted mt-3 lh">I have personally checked all control measures are in place to prevent release of hazard and meet certificate requirements.</div>
                    <table class="tb mt-3">
                        <tr>
                            <td><b>Name</b><br/><div class="hline"></div></td>
                            <td><b>Designation</b><br/><div class="hline"></div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td></tr>
</table>
HTML;

                        // Page 2 HTML (sections 5-9)
                        $page2 = <<<HTML
{$styles}
<table class="hstack" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:70%; vertical-align: middle;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width:12mm; height:12mm; border:0.3mm solid #d8dde5; background-color:#f3f5f8; text-align:center;"></td>
                    <td style="padding-left:6px;">
                        <div class="title">PERMIT TO WORK</div>
                        <div class="subtitle">Work Fast • Think Smart • Stay Safe</div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width:30%; text-align:right; font-size:9px;">
            <b>Form:</b> BPM 9.1
        </td>
    </tr>
</table>

<!-- Section 5: Supporting Documents -->
<table class="section mt-3" cellpadding="0" cellspacing="0">
    <tr><td class="sec-h">Section 5 — Supporting Documents</td></tr>
    <tr><td class="p-6">
        <table class="tb">
            <thead>
                <tr><th style="width:55%">Descriptions</th><th style="width:18%">Status</th><th>Remarks / No.</th></tr>
            </thead>
            <tbody>
                <tr><td>CIDB Green Card</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>AESP Card</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Competency Person Certificate</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Contractor Checklist</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Safety Data Sheet</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Lifting Plan</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Job Method Statement</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Job Hazard Analysis</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>HIRARC / EAIA</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Medical Check Up</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Calibration Certificate</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Load Chart</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Toolbox Talk Record</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Excavation Plan</td><td>☐ Yes ☐ No</td><td>Excavation Cert No.:</td></tr>
                <tr><td>Confined Space Cert</td><td>☐ Yes ☐ No</td><td>Cert No.:</td></tr>
                <tr><td>Lifting Checklist</td><td>☐ Yes ☐ No</td><td>Lifting Cert No.:</td></tr>
                <tr><td>Physical Isolation Certificate</td><td>☐ Yes ☐ No</td><td>No.:</td></tr>
                <tr><td>Electrical Isolation Certificate</td><td>☐ Yes ☐ No</td><td>No.:</td></tr>
                <tr><td>Traffic Management Plan</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Certificate of Fitness (PMA/PMT/PMD)</td><td>☐ Yes ☐ No</td><td></td></tr>
                <tr><td>Others (please specify)</td><td></td><td></td></tr>
            </tbody>
        </table>
    </td></tr>
</table>

<!-- Section 6: Hazardous Activities -->
<table class="section mt-3" cellpadding="0" cellspacing="0">
    <tr><td class="sec-h">Section 6 — Hazardous Activities</td></tr>
    <tr><td class="p-6">
        <table class="tb">
            <tr>
                <td class="pad-cell">☐ Slippery floor</td>
                <td class="pad-cell">☐ Sharp objects/edges</td>
                <td class="pad-cell">☐ Rotating parts</td>
            </tr>
            <tr>
                <td class="pad-cell">☐ Dropped objects</td>
                <td class="pad-cell">☐ Uneven ground/soil</td>
                <td class="pad-cell">☐ Extreme weather</td>
            </tr>
            <tr>
                <td class="pad-cell">☐ Pinch point</td>
                <td class="pad-cell">☐ Hot surface</td>
                <td class="pad-cell">☐ Fall from height</td>
            </tr>
            <tr>
                <td class="pad-cell">☐ Flying debris</td>
                <td class="pad-cell">☐ Moving vehicle/machinery</td>
                <td class="pad-cell">☐ Electrical</td>
            </tr>
            <tr>
                <td class="pad-cell">☐ Wild animals/insects</td>
                <td class="pad-cell">☐ Volatile liquid</td>
                <td class="pad-cell">☐ Illumination deficiency</td>
            </tr>
            <tr>
                <td class="pad-cell">☐ Gas/fumes</td>
                <td class="pad-cell">☐ Simultaneous operation</td>
                <td class="pad-cell">☐ Chemical</td>
            </tr>
            <tr>
                <td class="pad-cell">☐ Oxygen deficiency</td>
                <td class="pad-cell">☐ Poisonous substance</td>
                <td class="pad-cell">☐ Infectious substance</td>
            </tr>
            <tr>
                <td class="pad-cell">☐ Excessive noise</td>
                <td class="pad-cell">☐ Ionizing radiation</td>
                <td class="pad-cell">☐ Ergonomic/fatigue</td>
            </tr>
            <tr>
                <td class="pad-cell">☐ Pressurised equipment</td>
                <td class="pad-cell">☐ Engulfment</td>
                <td class="pad-cell">☐ Molds</td>
            </tr>
            <tr>
                <td colspan="3" class="pad-cell">☐ Others: _______________________________________________</td>
            </tr>
        </table>
    </td></tr>
</table>

<!-- Section 7: Contractor Declaration & PPE -->
<table class="section mt-3" cellpadding="0" cellspacing="0">
    <tr><td class="sec-h">Section 7 — Contractor Declaration & PPE</td></tr>
    <tr><td class="p-6">
        <ol class="lh" style="margin:0 0 6px 14px;">
            <li>All assigned workers are briefed on relevant SHE procedures; only activities stated in JHA are permitted.</li>
            <li>All documentation provided are valid during PTW validity.</li>
            <li>Ensure all appointed workers are briefed on job steps and safety before start work.</li>
            <li>GFM is not liable for incidents on site if PTW terms are violated.</li>
            <li>PTW must be displayed at the work site at all times.</li>
            <li>All safety equipment has been checked and deemed safe.</li>
        </ol>

        <table class="tb mt-3">
            <tr>
                <td style="width:33%; vertical-align:top;">
                    <b>Mandatory</b><br/><br/>
                    <span class="pill">☐ Safety helmet</span>
                    <span class="pill">☐ Safety shoes</span>
                    <span class="pill">☐ Life jacket/vest</span>
                </td>
                <td style="width:33%; vertical-align:top;">
                    <b>Respiratory Protection</b><br/><br/>
                    <span class="pill">☐ Half mask</span>
                    <span class="pill">☐ Full face (SCBA)</span>
                    <span class="pill">☐ Air line set</span>
                </td>
                <td style="width:34%; vertical-align:top;">
                    <b>Eye, Face & Body</b><br/><br/>
                    <span class="pill">☐ Goggles</span>
                    <span class="pill">☐ Face shield</span>
                    <span class="pill">☐ Welding mask</span>
                    <span class="pill">☐ Hood</span>
                    <span class="pill">☐ Disposable suit</span>
                    <span class="pill">☐ Chemical suit</span>
                </td>
            </tr>
            <tr>
                <td style="vertical-align:top;">
                    <b>Hand Protection</b><br/><br/>
                    <span class="pill">☐ Leather gloves</span>
                    <span class="pill">☐ Rubber gloves</span>
                    <span class="pill">☐ Chemical gloves</span>
                    <span class="pill">☐ Cotton gloves</span>
                </td>
                <td style="vertical-align:top;">
                    <b>Hearing</b><br/><br/>
                    <span class="pill">☐ Ear muff</span>
                    <span class="pill">☐ Ear plug</span>
                </td>
                <td style="vertical-align:top;">
                    <b>Footwear</b><br/><br/>
                    <span class="pill">☐ Rubber boots</span>
                </td>
            </tr>
        </table>

        <table class="tb mt-3">
            <tr>
                <td><b>Name</b><br/><div class="hline"></div></td>
                <td><b>Designation</b><br/><div class="hline"></div></td>
                <td><b>Signature</b><br/><div class="hline"></div></td>
            </tr>
        </table>

        <div class="muted mt-3">Acknowledgement by Supervisor/Contractor Supervisor: We have read and understood the terms and agree to comply with all requirements set by the building owners and GFM.</div>
    </td></tr>
</table>

<!-- Section 8: GFM Verification & Approval -->
<table class="section mt-3" cellpadding="0" cellspacing="0">
    <tr><td class="sec-h">Section 8 — GFM Verification & Approval</td></tr>
    <tr><td class="p-6">
        <ol class="lh" style="margin:0 0 4px 14px;">
            <li>PTW application must be submitted and approved by authorised personnel before work begins.</li>
            <li>PTW is required for all types of works defined in SHE Induction Briefing.</li>
            <li>PTW validity: up to 7 days for Other Works; up to 8 hours/day for Confined Space Entry from issuance date.</li>
        </ol>

        <table class="tb mt-3">
            <tr>
                <td style="width:33%; vertical-align:top;">
                    <b>Supervised By</b><br/><br/>
                    Name: <span class="hline" style="display:inline-block; width:45mm;"></span><br/>
                    Designation: <span class="hline" style="display:inline-block; width:38mm;"></span><br/>
                    Signature: <span class="hline" style="display:inline-block; width:48mm;"></span><br/>
                    Date: <span class="hline" style="display:inline-block; width:28mm;"></span>
                </td>
                <td style="width:33%; vertical-align:top;">
                    <b>SHE Authorizing Person</b><br/><br/>
                    ☐ Approved ☐ Not Approved<br/><br/>
                    Name: <span class="hline" style="display:inline-block; width:45mm;"></span><br/>
                    Designation: <span class="hline" style="display:inline-block; width:38mm;"></span><br/>
                    Signature: <span class="hline" style="display:inline-block; width:48mm;"></span><br/>
                    Date: <span class="hline" style="display:inline-block; width:28mm;"></span>
                </td>
                <td style="width:34%; vertical-align:top;">
                    <b>Facility Engineer / Manager / Client</b><br/><br/>
                    ☐ Approved ☐ Not Approved<br/><br/>
                    Name: <span class="hline" style="display:inline-block; width:45mm;"></span><br/>
                    Designation: <span class="hline" style="display:inline-block; width:38mm;"></span><br/>
                    Signature: <span class="hline" style="display:inline-block; width:48mm;"></span><br/>
                    Date: <span class="hline" style="display:inline-block; width:28mm;"></span>
                </td>
            </tr>
        </table>
    </td></tr>
</table>

<!-- Section 9: Hand Back / Status -->
<table class="section mt-3" cellpadding="0" cellspacing="0">
    <tr><td class="sec-h">Section 9 — Hand Back / Status</td></tr>
    <tr><td class="p-6">
        <table class="tb">
            <tr>
                <td style="width:50%; vertical-align:top;">
                    <b>Completion</b><br/>
                    <div class="muted">All work completed; site cleaned and ready for normal operations.</div><br/>
                    From: Date <span class="hline" style="display:inline-block; width:20mm;"></span> Time <span class="hline" style="display:inline-block; width:18mm;"></span><br/>
                    To: &nbsp;&nbsp;&nbsp;&nbsp;Date <span class="hline" style="display:inline-block; width:20mm;"></span> Time <span class="hline" style="display:inline-block; width:18mm;"></span>
                </td>
                <td style="width:50%; vertical-align:top;">
                    <b>Site Ready</b><br/>
                    <div class="muted">All precautions removed; worksite ready to resume.</div>
                </td>
            </tr>
        </table>

        <table class="tb mt-3">
            <tr>
                <td style="width:50%; vertical-align:top;">
                    <b>Suspended</b><br/>
                    <div class="muted">Permit suspended (attach NCR/CAR if required).</div><br/>
                    Name: <span class="hline" style="display:inline-block; width:50mm;"></span><br/>
                    Signature: <span class="hline" style="display:inline-block; width:50mm;"></span><br/>
                    Date: <span class="hline" style="display:inline-block; width:30mm;"></span>
                </td>
                <td style="width:50%; vertical-align:top;">
                    <b>Cancelled</b><br/>
                    <div class="muted">Permit cancelled; work will not be carried out.</div><br/>
                    Name: <span class="hline" style="display:inline-block; width:50mm;"></span><br/>
                    Signature: <span class="hline" style="display:inline-block; width:50mm;"></span><br/>
                    Date: <span class="hline" style="display:inline-block; width:30mm;"></span>
                </td>
            </tr>
        </table>

        <table class="tb mt-3">
            <tr>
                <td style="width:50%; vertical-align:top;">
                    <b>Extension</b><br/>
                    <div class="muted">I hereby confirm the extension of this permit.</div><br/>
                    Name: <span class="hline" style="display:inline-block; width:50mm;"></span><br/>
                    Signature: <span class="hline" style="display:inline-block; width:50mm;"></span><br/>
                    Date: <span class="hline" style="display:inline-block; width:30mm;"></span>
                </td>
                <td style="width:50%; vertical-align:top;">
                    <b>PTW Status</b><br/><br/>
                    <span class="pill">☐ Extended</span>
                    <span class="pill">☐ Closed</span>
                    <span class="pill">☐ Cancelled</span>
                    <span class="pill">☐ Suspended</span>
                </td>
            </tr>
        </table>
    </td></tr>
</table>
HTML;

                        // Create and write pages
                        $pdf = new MYPDF_ptw(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
                        $pdf->setPrintHeader(false);
                        $pdf->setPrintFooter(false);
                        $pdf->SetMargins(12, 12, 12);

                        $pdf->AddPage();
                        $pdf->writeHTML($page1, true, false, true, false, '');

                        $pdf->AddPage();
                        $pdf->writeHTML($page2, true, false, true, false, '');

                        // Save to upload path (deterministic)
                        $folder_code = floor(intval($this->ptwId)/1000);
                        $folder_abs = dirname(__FILE__) . '/../../upload/ptw/pdf/' . $folder_code;
                        if (!is_dir($folder_abs)) { @mkdir($folder_abs, 0777, true); }
                        $filename = 'ptw_' . substr((10000000+intval($this->ptwId)), 1) . '.pdf';
                        $pdf->Output($folder_abs . '/' . $filename, 'F');

                        return array('filename'=>$filename, 'folder'=>'upload/ptw/pdf/'.$folder_code);

                } catch(Exception $ex) {
                        $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
                        throw new Exception($this->get_exception('0103', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
                }
        }

    private function renderSection1($pdf, $ptwData) {
        // Section 1 Header
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(8, 6, '1', 1, 0, 'C', 1);
        $pdf->Cell(172, 6, ' BASIC INFORMATION', 1, 0, 'L', 1);
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);

    // Row 1: Permit/Request Number and Work Area
    $displayNo = !empty($ptwData['ptw_permit_number']) ? $ptwData['ptw_permit_number'] : ($ptwData['ptw_request_number'] ?? '');
    $label = !empty($ptwData['ptw_permit_number']) ? 'Permit Number:' : 'Request Number:';
    $pdf->Cell(45, 6, $label, 1, 0, 'L');
    $pdf->Cell(45, 6, $this->fn_general->clear_null($displayNo), 1, 0, 'L');
        $pdf->Cell(45, 6, 'Work Area:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->clear_null($ptwData['ptw_work_area']), 1, 0, 'L');
        $pdf->Ln();

        // Row 2: Valid From and Valid To
        $pdf->Cell(45, 6, 'Valid From:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->convertDateToDisplay($ptwData['ptw_valid_from']), 1, 0, 'L');
        $pdf->Cell(45, 6, 'Valid To:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->convertDateToDisplay($ptwData['ptw_valid_to']), 1, 0, 'L');
        $pdf->Ln();

        // Row 3: Risk Level and Level
        $pdf->Cell(45, 6, 'Risk Level:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->clear_null($ptwData['ptw_risk_level']), 1, 0, 'L');
        $pdf->Cell(45, 6, 'Level:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->clear_null($ptwData['ptw_level']), 1, 0, 'L');
        $pdf->Ln();

        // Row 4: Applicant Information
        $pdf->Cell(45, 6, 'Applicant Name:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->clear_null($ptwData['ptw_applicant_name']), 1, 0, 'L');
        $pdf->Cell(45, 6, 'Contact:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->clear_null($ptwData['ptw_applicant_contact']), 1, 0, 'L');
        $pdf->Ln();

        // Row 5: Contractor Information
        $pdf->Cell(45, 6, 'Contractor Company:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->clear_null($ptwData['ptw_contractor_company']), 1, 0, 'L');
        $pdf->Cell(45, 6, 'Supervisor:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->clear_null($ptwData['ptw_contractor_supervisor']), 1, 0, 'L');
        $pdf->Ln();

        $pdf->Ln(3);
    }

    private function renderSection2($pdf, $ptwData) {
        // Section 2 Header
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(8, 6, '2', 1, 0, 'C', 1);
        $pdf->Cell(172, 6, ' WORK DESCRIPTION & RISK ASSESSMENT', 1, 0, 'L', 1);
        $pdf->Ln();

        // Work Description
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();
        
        $pdf->MultiCell(8, 4, '', 0, 'L', 0, 0);
        $workDescription = $this->fn_general->clear_null($ptwData['ptw_permit_description']);
        $cellcount = $pdf->MultiCell(172, 4, $workDescription, 0, 'L', 0, 0);
        
        $pdf->SetXY($startX, $startY);
        $pdf->MultiCell(8, $cellcount * 4, '', 1, 'L', 0, 0);
        $pdf->MultiCell(172, $cellcount * 4, '', 1, 'L', 0, 0);
        $pdf->Ln();

        // Hazards
        if (!empty($ptwData['ptw_hazards'])) {
            $pdf->Cell(45, 6, 'Identified Hazards:', 1, 0, 'L');
            $pdf->Cell(135, 6, $this->fn_general->clear_null($ptwData['ptw_hazards']), 1, 0, 'L');
            $pdf->Ln();
        }

        // Control Measures
        if (!empty($ptwData['ptw_control_measures'])) {
            $pdf->Cell(45, 6, 'Control Measures:', 1, 0, 'L');
            $pdf->Cell(135, 6, $this->fn_general->clear_null($ptwData['ptw_control_measures']), 1, 0, 'L');
            $pdf->Ln();
        }

        $pdf->Ln(3);
    }

    private function renderSection3($pdf, $workTypes, $hazardChecklist, $coldWorkChecklist, $hotWorkChecklist, $confinedSpaceChecklist) {
        // Section 3 Header
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(8, 6, '3', 1, 0, 'C', 1);
        $pdf->Cell(172, 6, ' WORK TYPES & HAZARDOUS ACTIVITIES', 1, 0, 'L', 1);
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);

        // Work Types
        $pdf->Cell(180, 4, 'Work Types:', 0, 0, 'L');
        $pdf->Ln();

        $coldWorkChecked = in_array('COLD_WORK', $workTypes) || in_array('cold_work', $workTypes);
        $hotWorkChecked = in_array('HOT_WORK', $workTypes) || in_array('hot_work', $workTypes);
        $confinedSpaceChecked = in_array('CONFINED_SPACE', $workTypes) || in_array('confined_space', $workTypes);

        $currentY = $pdf->GetY();
        $this->renderCheckbox($pdf, 15, $currentY, $coldWorkChecked);
        $pdf->Cell(8, 4, '', 0, 0, 'L');
        $pdf->Cell(50, 4, 'Cold Work', 0, 0, 'L');
        
        $this->renderCheckbox($pdf, 75, $currentY, $hotWorkChecked);
        $pdf->Cell(8, 4, '', 0, 0, 'L');
        $pdf->Cell(50, 4, 'Hot Work', 0, 0, 'L');
        
        $this->renderCheckbox($pdf, 135, $currentY, $confinedSpaceChecked);
        $pdf->Cell(8, 4, '', 0, 0, 'L');
        $pdf->Cell(50, 4, 'Confined Space', 0, 0, 'L');
        $pdf->Ln();

        // Hazardous Activities Checklist
        if (!empty($hazardChecklist)) {
            $pdf->Ln(2);
            $pdf->Cell(180, 4, 'Hazardous Activities:', 0, 0, 'L');
            $pdf->Ln();
            
            $hazardItems = [
                'hazSlipperyFloor' => 'Slippery Floor',
                'hazUnguardedOpening' => 'Unguarded Opening',
                'hazWorkingAtHeight' => 'Working at Height',
                'hazDroppedObjects' => 'Dropped Objects',
                'hazOverheadWork' => 'Overhead Work',
                'hazElectricalHazard' => 'Electrical Hazard',
                'hazChemicalExposure' => 'Chemical Exposure',
                'hazNoiseVibration' => 'Noise/Vibration'
            ];

            $currentY = $pdf->GetY();
            $col = 0;
            foreach ($hazardItems as $key => $label) {
                $isChecked = isset($hazardChecklist[$key]) && $hazardChecklist[$key] === true;
                $x = 15 + ($col * 90);
                
                $this->renderCheckbox($pdf, $x, $currentY, $isChecked);
                $pdf->SetXY($x + 8, $currentY);
                $pdf->Cell(80, 4, $label, 0, 0, 'L');
                
                $col++;
                if ($col >= 2) {
                    $col = 0;
                    $currentY += 6;
                }
            }
            if ($col > 0) {
                $pdf->SetY($currentY + 6);
            }
        }

        $pdf->Ln(3);
    }

    private function renderSection4($pdf, $hazardChecklist) {
        // Section 4 Header
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(8, 6, '4', 1, 0, 'C', 1);
        $pdf->Cell(172, 6, ' PERSONAL PROTECTIVE EQUIPMENT (PPE)', 1, 0, 'L', 1);
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);

        // PPE Requirements
        $ppeItems = [
            'ppe_safety_helmet' => 'Safety Helmet',
            'ppe_safety_shoes' => 'Safety Shoes',
            'ppe_high_vis_vest' => 'High Visibility Vest',
            'ppe_dust_mask' => 'Dust Mask',
            'ppe_respirator' => 'Respirator',
            'ppe_scba' => 'SCBA',
            'ppe_safety_glasses' => 'Safety Glasses',
            'ppe_face_shield' => 'Face Shield',
            'ppe_coveralls' => 'Coveralls',
            'ppe_chemical_suit' => 'Chemical Suit',
            'ppe_work_gloves' => 'Work Gloves',
            'ppe_chemical_gloves' => 'Chemical Gloves',
            'ppe_cut_resistant' => 'Cut Resistant Gloves',
            'ppe_ear_plugs' => 'Ear Plugs',
            'ppe_ear_muffs' => 'Ear Muffs'
        ];

        $currentY = $pdf->GetY();
        $col = 0;
        foreach ($ppeItems as $key => $label) {
            $isChecked = isset($hazardChecklist[$key]) && $hazardChecklist[$key] === true;
            $x = 15 + ($col * 60);
            
            $this->renderCheckbox($pdf, $x, $currentY, $isChecked);
            $pdf->SetXY($x + 8, $currentY);
            $pdf->Cell(50, 4, $label, 0, 0, 'L');
            
            $col++;
            if ($col >= 3) {
                $col = 0;
                $currentY += 6;
            }
        }
        if ($col > 0) {
            $pdf->SetY($currentY + 6);
        }

        // PPE Others
        if (isset($hazardChecklist['ppe_others']) && !empty($hazardChecklist['ppe_others'])) {
            $pdf->Ln(2);
            $pdf->Cell(30, 4, 'Others (specify):', 0, 0, 'L');
            $pdf->Cell(150, 4, $hazardChecklist['ppe_others'], 1, 0, 'L');
            $pdf->Ln();
        }

        $pdf->Ln(3);
    }

    private function renderSection5($pdf, $workers) {
        // Section 5 Header
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(8, 6, '5', 1, 0, 'C', 1);
        $pdf->Cell(172, 6, ' WORKERS INFORMATION', 1, 0, 'L', 1);
        $pdf->Ln();

        // Table headers
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(10, 6, 'No', 1, 0, 'C', 1);
        $pdf->Cell(50, 6, 'Name', 1, 0, 'C', 1);
        $pdf->Cell(40, 6, 'Designation', 1, 0, 'C', 1);
        $pdf->Cell(40, 6, 'Identification', 1, 0, 'C', 1);
        $pdf->Cell(20, 6, 'Certified', 1, 0, 'C', 1);
        $pdf->Cell(20, 6, 'Contact', 1, 0, 'C', 1);
        $pdf->Ln();

        // Worker rows
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);
        
        if (!empty($workers) && is_array($workers)) {
            foreach ($workers as $index => $worker) {
                $pdf->Cell(10, 6, $index + 1, 1, 0, 'C');
                $pdf->Cell(50, 6, $this->fn_general->clear_null($worker['name'] ?? ''), 1, 0, 'L');
                $pdf->Cell(40, 6, $this->fn_general->clear_null($worker['designation'] ?? ''), 1, 0, 'L');
                $pdf->Cell(40, 6, $this->fn_general->clear_null($worker['identification'] ?? ''), 1, 0, 'L');
                $pdf->Cell(20, 6, isset($worker['is_certified']) && $worker['is_certified'] ? 'Yes' : 'No', 1, 0, 'C');
                $pdf->Cell(20, 6, $this->fn_general->clear_null($worker['contact_number'] ?? ''), 1, 0, 'L');
                $pdf->Ln();
            }
        } else {
            // Empty rows
            for ($i = 0; $i < 3; $i++) {
                $pdf->Cell(10, 6, '', 1, 0, 'C');
                $pdf->Cell(50, 6, '', 1, 0, 'L');
                $pdf->Cell(40, 6, '', 1, 0, 'L');
                $pdf->Cell(40, 6, '', 1, 0, 'L');
                $pdf->Cell(20, 6, '', 1, 0, 'C');
                $pdf->Cell(20, 6, '', 1, 0, 'L');
                $pdf->Ln();
            }
        }

        $pdf->Ln(3);
    }

    private function renderSection6($pdf, $supportingDocs, $certificateNumbers) {
        // Section 6 Header
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(8, 6, '6', 1, 0, 'C', 1);
        $pdf->Cell(172, 6, ' SUPPORTING DOCUMENTS & CERTIFICATES', 1, 0, 'L', 1);
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);

        // Supporting Documents
        $docItems = [
            'calibration_certificate' => 'Calibration Certificate',
            'load_chart' => 'Load Chart',
            'toolbox_talk_record' => 'Toolbox Talk Record',
            'safety_data_sheet' => 'Safety Data Sheet',
            'job_method_statement' => 'Job Method Statement',
            'job_hazard_analysis' => 'Job Hazard Analysis',
            'certificate_of_fitness' => 'Certificate of Fitness',
            'cidb_green_card' => 'CIDB Green Card',
            'aesp_card' => 'AESP Card',
            'competency_person_certificate' => 'Competency Person Certificate'
        ];

        $currentY = $pdf->GetY();
        $col = 0;
        foreach ($docItems as $key => $label) {
            $isChecked = in_array($key, $supportingDocs);
            $x = 15 + ($col * 90);
            
            $this->renderCheckbox($pdf, $x, $currentY, $isChecked);
            $pdf->SetXY($x + 8, $currentY);
            $pdf->Cell(80, 4, $label, 0, 0, 'L');
            
            $col++;
            if ($col >= 2) {
                $col = 0;
                $currentY += 6;
            }
        }
        if ($col > 0) {
            $pdf->SetY($currentY + 6);
        }

        // Certificate Numbers
        if (!empty($certificateNumbers)) {
            $pdf->Ln(2);
            $pdf->Cell(180, 4, 'Certificate Numbers:', 0, 0, 'L');
            $pdf->Ln();

            $certItems = [
                'excavationCert' => 'Excavation',
                'liftingCert' => 'Lifting',
                'physicalIsolation' => 'Physical Isolation',
                'electricalIsolation' => 'Electrical Isolation',
                'confinedSpaceCert' => 'Confined Space',
                'othersCert' => 'Others'
            ];

            foreach ($certItems as $key => $label) {
                if (isset($certificateNumbers[$key]) && !empty($certificateNumbers[$key]['value'])) {
                    $pdf->Cell(40, 4, $label . ':', 0, 0, 'L');
                    $pdf->Cell(60, 4, $certificateNumbers[$key]['value'], 1, 0, 'L');
                    $pdf->Ln();
                }
            }
        }

        $pdf->Ln(3);
    }

    private function renderSection7($pdf, $ptwData, $declarationChecklist) {
        // Section 7 Header
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(8, 6, '7', 1, 0, 'C', 1);
        $pdf->Cell(172, 6, ' CONTRACTOR DECLARATION', 1, 0, 'L', 1);
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);

        // Declaration statements
        $declarations = [
            'declaration1' => 'I have received and understood the safety briefing',
            'declaration2' => 'All workers are competent and properly trained',
            'declaration3' => 'Required PPE will be used at all times',
            'declaration4' => 'Work will be carried out according to safety procedures',
            'declaration5' => 'Emergency procedures have been understood',
            'declaration6' => 'I accept responsibility for safe work execution'
        ];

        foreach ($declarations as $key => $statement) {
            $yesSelected = isset($declarationChecklist[$key]) && $declarationChecklist[$key] === 'yes';
            $noSelected = isset($declarationChecklist[$key]) && $declarationChecklist[$key] === 'no';
            
            $currentY = $pdf->GetY();
            $pdf->Cell(120, 6, $statement, 1, 0, 'L');
            
            // Yes radio button
            $this->renderRadioButton($pdf, 125, $currentY + 1, $yesSelected);
            $pdf->SetXY(132, $currentY);
            $pdf->Cell(20, 6, 'Yes', 0, 0, 'L');
            
            // No radio button
            $this->renderRadioButton($pdf, 150, $currentY + 1, $noSelected);
            $pdf->SetXY(157, $currentY);
            $pdf->Cell(20, 6, 'No', 1, 0, 'L');
            $pdf->Ln();
        }

        // Contractor Acknowledgment
        $pdf->Ln(2);
        $pdf->Cell(45, 6, 'Contractor Name:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->clear_null($ptwData['ptw_contractor_name'] ?? ''), 1, 0, 'L');
        $pdf->Cell(45, 6, 'Designation:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->clear_null($ptwData['ptw_contractor_designation'] ?? ''), 1, 0, 'L');
        $pdf->Ln();

        $pdf->Cell(45, 6, 'Date:', 1, 0, 'L');
        $pdf->Cell(45, 6, $this->fn_general->clear_null($ptwData['ptw_contractor_date'] ?? ''), 1, 0, 'L');
        $pdf->Cell(90, 6, 'Signature: ................................', 1, 0, 'L');
        $pdf->Ln();

        // Final confirmation
        $confirmationChecked = isset($declarationChecklist['contractorConfirmation']) && $declarationChecklist['contractorConfirmation'] === true;
        $currentY = $pdf->GetY();
        $this->renderCheckbox($pdf, 15, $currentY, $confirmationChecked);
        $pdf->Cell(8, 6, '', 0, 0, 'L');
        $pdf->Cell(172, 6, 'I confirm compliance with all safety requirements and regulations', 0, 0, 'L');
        $pdf->Ln();

        $pdf->Ln(3);
    }

    private function renderSection8($pdf, $ptwData, $arrUserFullName) {
        // Section 8 Header
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(8, 6, '8', 1, 0, 'C', 1);
        $pdf->Cell(172, 6, ' APPROVAL SIGNATURES', 1, 0, 'L', 1);
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);

        // Supervisor Approval
        $supervisorName = '';
        $supervisorDate = '';
        $supervisorStatus = $this->fn_general->clear_null($ptwData['ptw_supervisor_approval'] ?? 'PENDING');
        
        if (!empty($ptwData['approved_supervisor_by'])) {
            $supervisorName = $arrUserFullName[intval($ptwData['approved_supervisor_by'])] ?? '';
            $supervisorDate = $this->fn_general->convertDateToDisplay($ptwData['approved_supervisor_date']);
        }

        $pdf->Cell(60, 18, "SUPERVISOR APPROVAL\n\nStatus: " . $supervisorStatus . "\nName: " . $supervisorName . "\nDate: " . $supervisorDate . "\n\nSignature: ................................", 1, 0, 'L');

        // SHE Approval
        $sheName = '';
        $sheDate = '';
        $sheStatus = $this->fn_general->clear_null($ptwData['ptw_she_approval'] ?? 'PENDING');
        
        if (!empty($ptwData['approved_she_by'])) {
            $sheName = $arrUserFullName[intval($ptwData['approved_she_by'])] ?? '';
            $sheDate = $this->fn_general->convertDateToDisplay($ptwData['approved_she_date']);
        }

        $pdf->Cell(60, 18, "SHE APPROVAL\n\nStatus: " . $sheStatus . "\nName: " . $sheName . "\nDate: " . $sheDate . "\n\nSignature: ................................", 1, 0, 'L');

        // Facility Manager Approval
        $fmName = '';
        $fmDate = '';
        $fmStatus = $this->fn_general->clear_null($ptwData['ptw_fm_approval'] ?? 'PENDING');
        
        if (!empty($ptwData['approved_fm_by'])) {
            $fmName = $arrUserFullName[intval($ptwData['approved_fm_by'])] ?? '';
            $fmDate = $this->fn_general->convertDateToDisplay($ptwData['approved_fm_date']);
        }

        $pdf->Cell(60, 18, "FACILITY MANAGER APPROVAL\n\nStatus: " . $fmStatus . "\nName: " . $fmName . "\nDate: " . $fmDate . "\n\nSignature: ................................", 1, 0, 'L');
        $pdf->Ln();

        $pdf->Ln(3);
    }

    private function savePdfFile($pdf, $ptwData) {
        // Create folder structure under /upload/ptw/pdf/<folder_code>
        $folder_code = floor(intval($this->ptwId)/1000);
        $folder = '../../upload/ptw/pdf/'.$folder_code; // relative to api/pdf

        $result = $this->fn_general->folderExist($folder);
        $absFolder = dirname(__FILE__).'/'.$folder; // resolves to gems2/upload/ptw/pdf/<folder_code>
        if (!$result && !is_dir($absFolder)) {
            @mkdir($absFolder, 0777, true);
        }

        $filename = 'ptw_'.substr((10000000+intval($this->ptwId)), 1).'.pdf';
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : '.$filename);

        $config = parse_ini_file('library/config.ini');
        $environment = $config['environment'];
        
    // Write directly to absolute folder (independent of environment)
    $pdf->Output($absFolder . '/' . $filename, 'F');

        // Update database
        $pdfId = $ptwData['pdf_id'] ?? null;
        if (empty($pdfId)) {
            $pdfId = Class_db::getInstance()->db_select_col('sys_pdf', array('pdf_filename'=>$filename, 'pdf_status'=>'1'), 'pdf_id');
        }
        
        if (empty($pdfId)) {
            $pdfId = Class_db::getInstance()->db_insert('sys_pdf', array(
                'pdf_filename'=>$filename, 
                'pdf_type'=>'ptw', 
                'pdf_folder'=>'upload/ptw/pdf/'.$folder_code
            ));
        } else {
            Class_db::getInstance()->db_update('sys_pdf', array(
                'pdf_filename'=>$filename, 
                'pdf_type'=>'ptw', 
                'pdf_folder'=>'upload/ptw/pdf/'.$folder_code, 
                'pdf_timeCreated'=>'Now()'
            ), array('pdf_id'=>$pdfId));
        }

        // Update new table first; fallback to legacy
        try {
            Class_db::getInstance()->db_update('ptw_permit', array(
                'pdf_id'=>$pdfId, 
                'ptw_is_pdf'=>'1'
            ), array('ptw_permit_id'=>$this->ptwId));
        } catch (Exception $e) {
            // Legacy table update
            Class_db::getInstance()->db_update('ptw_permits', array(
                'pdf_id'=>$pdfId, 
                'ptw_is_pdf'=>'1'
            ), array('ptw_id'=>$this->ptwId));
        }

        return array(
            'pdfId'=>$pdfId,
            'ptwPermitNumber'=>$ptwData['ptw_permit_number']
        );
    }
}
