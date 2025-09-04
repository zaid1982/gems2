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
        // Create folder structure under api/pdf/ptw/<folder_code>
        $folder_code = floor(intval($this->ptwId)/1000);
        $folder = 'ptw/'.$folder_code; // relative to api/pdf

        $result = $this->fn_general->folderExist($folder);
        $absFolder = dirname(__FILE__).'/'.$folder;
        if (!$result && !is_dir($absFolder)) {
            @mkdir($absFolder, 0777, true);
        }

        $filename = 'ptw_'.substr((10000000+intval($this->ptwId)), 1).'.pdf';
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : '.$filename);

        $config = parse_ini_file('library/config.ini');
        $environment = $config['environment'];
        
        if ($environment == 'windows') {
            $filename_src = '\ptw\\' . $folder_code . '\\' . $filename;
        } else {
            $filename_src = '/ptw/' . $folder_code . '/' . $filename;
        }

        $pdf->Output(dirname(__FILE__). $filename_src, 'F');

        // Update database
        $pdfId = $ptwData['pdf_id'] ?? null;
        if (empty($pdfId)) {
            $pdfId = Class_db::getInstance()->db_select_col('sys_pdf', array('pdf_filename'=>$filename, 'pdf_status'=>'1'), 'pdf_id');
        }
        
        if (empty($pdfId)) {
            $pdfId = Class_db::getInstance()->db_insert('sys_pdf', array(
                'pdf_filename'=>$filename, 
                'pdf_type'=>'ptw', 
                'pdf_folder'=>$folder
            ));
        } else {
            Class_db::getInstance()->db_update('sys_pdf', array(
                'pdf_filename'=>$filename, 
                'pdf_type'=>'ptw', 
                'pdf_folder'=>$folder, 
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
