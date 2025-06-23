<?php

class MYPDF_wo extends TCPDF {
    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Line(PDF_MARGIN_LEFT, $this->y, $this->w - PDF_MARGIN_RIGHT, $this->y);
        $pageNo = 'Page '.strval($this->getAliasNumPage()).' of '.$this->getAliasNbPages();
        $this->Cell(180, 6, $pageNo, 0, 0, 'R', 0);
    }
}

class Class_pdf_wo {
    private $fn_general;
    private $woTaskId;

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

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId Empty');
            }

            // create new PDF document
            $pdf = new MYPDF_wo(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Muhammad Zaid');
            $pdf->SetTitle('GEMS 2.0 WO');
            $pdf->SetSubject('GEMS 2.0 WO');

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

            // ---------------------------------------------------------
            // Data Fetching (Keep existing logic)
            $arrSiteName = $this->fn_general->getSiteName();
            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrCategory = array('', 'Complaint', 'Finding', 'Request', 'Breakdown', 'Defect', 'Public Complaint');
            $arrSeverity = $this->fn_general->getSeverityName();
            //$arrSeverity = array('', 'Non-Critical', 'Critical'); // This line was commented out in original, keep as is
            $arrSla = array('', '4 hours', '2 hours');
            $arrDue = array('', '4', '2');

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            $userProfile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$woTask['wo_task_created_by'], 'user_profile_status'=>'1'), null, 1);
            $clientId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$woTask['site_id']), 'client_id', null, 1);

            $arrClientSeverity = Class_db::getInstance()->db_select('cli_client_severity', array('client_id'=>$clientId));
            foreach ($arrClientSeverity as $clientSeverity) {
                $severityKey = intval($clientSeverity['severity_id']);
                $arrSla[$severityKey] = $clientSeverity['client_severity_hour'].' hours';
                $arrDue[$severityKey] = $clientSeverity['client_severity_hour'];
            }

            // Image arrays (keep existing logic)
            $img_complaint = array();
            $img_before = null; // Changed to null as it seems to be a single image for before/after
            $img_during = array();
            $img_after = null;  // Changed to null as it seems to be a single image for before/after
            $woUploads = Class_db::getInstance()->db_select('mw_wo_upload', array('wo_task_id'=>$this->woTaskId, 'sys_upload.upload_status'=>'1'));
            foreach ($woUploads as $woUpload) {
                $uploadType = $woUpload['wo_task_upload_type'];
                if ($uploadType === '1') {
                    array_push($img_complaint, $woUpload);
                } else if ($uploadType === '2') {
                    $img_before = $woUpload;
                } else if ($uploadType === '3') {
                    array_push($img_during, $woUpload);
                } else if ($uploadType === '4') {
                    $img_after = $woUpload;
                }
            }

            // ---------------------------------------------------------

            // add a page
            $pdf->AddPage();

            // Set default font and colors for template
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetFillColor(230, 230, 230); // Light grey for section headers
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetLineWidth(0.2); // Reset line width if needed

            // --- Header / Title Area ---
            // Logo
            $pdf->Image('pdf/images/logo_'.$clientId.'.png', 15, 15, 50, 20, 'PNG', '', '', false, 150, '', false, false, 0, false, false, false);

            // Title
            $pdf->SetFont('helvetica', 'B', 16);
            // Calculate a centered position for the title after the logo
            $logo_x = 15;
            $logo_width = 50;
            $title_width = 100; // Adjusted width to leave room
            $title_x = $logo_x + $logo_width + 10; // Start title after logo with a gap
            if ($title_x + $title_width > $pdf->GetPageWidth() - PDF_MARGIN_RIGHT) {
                $title_x = ($pdf->GetPageWidth() - $title_width) / 2; // Fallback to center if it overflows
            }

            $pdf->SetXY($title_x, 15);
            $pdf->Cell($title_width, 10, 'WORK ORDER', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetX($title_x); // Align description under title
            $pdf->Cell($title_width, 8, strtoupper($arrSiteName[intval($woTask['site_id'])]), 0, 1, 'C');
            $pdf->Ln(5); // Add some space after the title block

            // --- WORK ORDER (WO) SECTIONS ---

            // Section A: Work Order Details
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(8, 7, 'A', 1, 0, 'C', 1);
            $pdf->Cell(172, 7, ' Work Order Details', 1, 1, 'L', 1);
            $pdf->SetFont('helvetica', '', 9);

            // Column widths for key-value pairs
            $col_width_label = 35; // Label width
            $col_width_value = 55; // Value width
            $total_row_width = 180; // (35+55) * 2 = 180

            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->Cell($col_width_label, 6, 'Work Order No:', 'LR', 0, 'R');
            $pdf->Cell($col_width_value, 6, $this->fn_general->clear_null($woTask['wo_task_no']), 'R', 0, 'L');
            $pdf->Cell($col_width_label, 6, 'Status:', 'R', 0, 'R');
            $pdf->Cell($col_width_value, 6, $this->fn_general->clear_null($woTask['wo_task_status']), 'R', 1, 'L');

            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->Cell($col_width_label, 6, 'Work Request No:', 'LR', 0, 'R');
            $pdf->Cell($col_width_value, 6, $this->fn_general->clear_null($woTask['wo_task_ref_no']), 'R', 0, 'L'); // Assuming this holds WR No
            $pdf->Cell($col_width_label, 6, 'Category:', 'R', 0, 'R');
            $pdf->Cell($col_width_value, 6, $arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'], 0))], 'R', 1, 'L');

            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->Cell($col_width_label, 6, 'Location Name:', 'LR', 0, 'R');
            $pdf->Cell($col_width_value, 6, $this->fn_general->clear_null($woTask['wo_task_location']), 'R', 0, 'L');
            $pdf->Cell($col_width_label, 6, 'Location Code:', 'R', 0, 'R'); // Assuming a location code exists in woTask
            $pdf->Cell($col_width_value, 6, $this->fn_general->clear_null($woTask['site_code']), 'R', 1, 'L'); // Placeholder, adjust field name if different

            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->Cell($col_width_label, 6, 'Asset Name:', 'LR', 0, 'R');
            $pdf->Cell($col_width_value, 6, $this->fn_general->clear_null($woTask['asset_name']), 'R', 0, 'L'); // Placeholder, adjust field name if different
            $pdf->Cell($col_width_label, 6, 'Asset Code:', 'R', 0, 'R'); // Placeholder
            $pdf->Cell($col_width_value, 6, $this->fn_general->clear_null($woTask['asset_code']), 'R', 1, 'L'); // Placeholder
            $pdf->Cell(0, 0, '', 'T', 1); // Bottom border for this sub-section

            $pdf->Ln(2);
            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($col_width_label * 2, 6, 'Complaint Description:', 'LR', 0, 'R'); // Spanning two columns for label
            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(0, 6, $this->fn_general->clear_null($woTask['wo_task_complaint']), 'R', 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->Cell(0, 0, '', 'B', 1); // Bottom border for description row
            $pdf->Ln(2);


            // Section B1: Work Assignment Details
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(8, 7, 'B', 1, 0, 'C', 1); // Changed from B1 to B as per template
            $pdf->Cell(172, 7, ' Work Assignment Details [Details of task issuer and receiver]', 1, 1, 'L', 1);
            $pdf->SetFont('helvetica', '', 9);

            $picName = $this->fn_general->clear_null($arrUserFullName[intval($woTask['wo_task_assigned_to'])]);
            $userProfileTech = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$woTask['wo_task_assigned_to'], 'user_profile_status'=>'1'), null, 1);
            $picEmail = $this->fn_general->clear_null($userProfileTech['user_email']);

            $assignedToName = $this->fn_general->clear_null($arrUserFullName[intval($woTask['wo_task_assigned_to'])]); // Assuming this is assigned_to
            $assignedToPhone = $this->fn_general->clear_null($userProfileTech['user_contact_no']); // Fetch phone from assigned_to user profile
            $assignTime = !empty($woTask['wo_task_time_assigned']) ? $this->fn_general->convertDateToDisplay($woTask['wo_task_time_assigned']) : '';


            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->Cell($col_width_label, 6, 'Received By:', 'LR', 0, 'R');
            $pdf->Cell($col_width_value, 6, $picName, 'R', 0, 'L'); // Assuming "Person in Charge" is "Received By"
            $pdf->Cell($col_width_label, 6, 'Assigned To:', 'R', 0, 'R');
            $pdf->Cell($col_width_value, 6, $assignedToName, 'R', 1, 'L');

            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->Cell($col_width_label, 6, 'Date Assigned:', 'LR', 0, 'R');
            $pdf->Cell($col_width_value, 6, $assignTime, 'R', 0, 'L');
            $pdf->Cell($col_width_label, 6, 'Phone No:', 'R', 0, 'R');
            $pdf->Cell($col_width_value, 6, $assignedToPhone, 'R', 1, 'L');
            $pdf->Cell(0, 0, '', 'T', 1); // Bottom border for this section
            $pdf->Ln(2);


            // Section B2: Support Personnel (Now Part of C as per your previous design)
            // This section was 'E' in your wo.php. Moved here to reflect template order.
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(8, 7, 'C', 1, 0, 'C', 1); // Adjusted section label
            $pdf->Cell(172, 7, ' Support Personnel [Team members involved in execution]', 1, 1, 'L', 1);
            $pdf->SetFont('helvetica', '', 9);

            $woAssists = Class_db::getInstance()->db_select('wo_task_assist', array('wo_task_id'=>$this->woTaskId));

            $pdf->Cell(15, 6, 'No.', 1, 0, 'C');
            $pdf->Cell(0, 6, 'Name', 1, 1, 'C');

            $max_support_rows = 5; // To match the template's typical empty rows
            $current_support_rows = 0;

            foreach ($woAssists as $person) {
                if ($current_support_rows < $max_support_rows) {
                    $assistName = $this->fn_general->clear_null($arrUserFullName[intval($person['user_id'])]);
                    $pdf->Cell(15, 6, ($current_support_rows + 1), 'LRB', 0, 'C');
                    $pdf->Cell(0, 6, $assistName, 'RB', 1, 'L');
                    $current_support_rows++;
                }
            }
            // Add empty rows for more personnel if needed
            for ($i = $current_support_rows; $i < $max_support_rows; $i++) {
                $pdf->Cell(15, 6, ($i + 1), 'LRB', 0, 'C');
                $pdf->Cell(0, 6, '', 'RB', 1, 'L');
            }
            $pdf->Ln(2);

            // Section C: Material Details (Now D)
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(8, 7, 'D', 1, 0, 'C', 1); // Adjusted section label
            $pdf->Cell(172, 7, ' Material Details [Parts or materials issued, returned, and tracked]', 1, 1, 'L', 1);
            $pdf->SetFont('helvetica', '', 9);

            $pdf->Cell(25, 6, 'Part No.', 1, 0, 'C');
            $pdf->Cell(50, 6, 'Item Description', 1, 0, 'C');
            $pdf->Cell(25, 6, 'Issue Type', 1, 0, 'C');
            $pdf->Cell(15, 6, '(D/I)', 1, 0, 'C');
            $pdf->Cell(20, 6, 'Unit', 1, 0, 'C');
            $pdf->Cell(30, 6, 'Quantity Taken', 1, 0, 'C');
            $pdf->Cell(0, 6, 'Quantity Return', 1, 1, 'C');

            // Example empty rows for material details - you'd populate this with real data
            for ($i = 0; $i < 4; $i++) {
                $pdf->Cell(25, 6, '', 'LRB', 0, 'C');
                $pdf->Cell(50, 6, '', 'RB', 0, 'L');
                $pdf->Cell(25, 6, '', 'RB', 0, 'C');
                $pdf->Cell(15, 6, '', 'RB', 0, 'C');
                $pdf->Cell(20, 6, '', 'RB', 0, 'C');
                $pdf->Cell(30, 6, '', 'RB', 0, 'C');
                $pdf->Cell(0, 6, '', 'RB', 1, 'C');
            }
            $pdf->Cell(0, 6, '**D = Direct Issue, I = Inventory', 0, 1, 'L');
            $pdf->Ln(2);

            // Section D: Work Execution Details (Now E)
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(8, 7, 'E', 1, 0, 'C', 1); // Adjusted section label
            $pdf->Cell(172, 7, ' Work Execution Details [Action duration, task notes, and work timeline]', 1, 1, 'L', 1);
            $pdf->SetFont('helvetica', '', 9);

            $pdf->MultiCell(0, 15, $this->fn_general->clear_null($woTask['wo_task_repair_desc']), 'LRB', 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->Ln(2);

            // Re-use your existing date/time calculation logic
            $createdTime = new DateTime($woTask['wo_task_time_created']);
            $dueTime = null;
            if (!empty($woTask['wo_task_severity'])) {
                $dueTime = clone $createdTime; // Clone to avoid modifying original createdTime
                $dueTime->modify('+'.$arrDue[intval($woTask['wo_task_severity'])].' hour');
            }
            $assignedTime = null;
            $assignTime = '';
            if (!empty($woTask['wo_task_time_assigned'])) {
                $assignedTime = new DateTime($woTask['wo_task_time_assigned']);
                $assignTime = $assignedTime->format('j/n/Y g:i:sa');
            }
            $executedTime = null;
            $fixedTime = '';
            if (!empty($woTask['wo_task_time_executed'])) {
                $executedTime = new DateTime($woTask['wo_task_time_executed']);
                $fixedTime = $executedTime->format('j/n/Y g:i:sa');
            }

            $totalExecTime = Class_db::getInstance()->db_select_col('mw_wo_execute_duration', array(), 'duration', null, 0, array('transaction_id'=>$woTask['transaction_id']));
            $duration = !empty($totalExecTime) ? $totalExecTime : ''; // This seems to be for execution duration

            // The template uses Start/End Date & Time, and a single Duration field.
            // Map your values to these.
            $startDate = !empty($assignedTime) ? $assignedTime->format('j/n/Y g:i:sa') : '[System Generated based on WO start]';
            $endDate = !empty($executedTime) ? $executedTime->format('j/n/Y g:i:sa') : '[System Generated based on WO task end]';
            $workStatus = ($woTask['wo_task_status'] === 'Completed') ? 'Within SLA/Exceed' : 'Pending/InProgress'; // Placeholder logic, refine as needed

            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->Cell($col_width_label, 6, 'Start Date & Time:', 'LR', 0, 'R');
            $pdf->Cell($col_width_value, 6, $startDate, 'R', 0, 'L');
            $pdf->Cell($col_width_label, 6, 'End Date & Time:', 'R', 0, 'R');
            $pdf->Cell($col_width_value, 6, $endDate, 'R', 1, 'L');

            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->Cell($col_width_label, 6, 'Duration:', 'LR', 0, 'R');
            $pdf->Cell($col_width_value, 6, $duration, 'R', 0, 'L');
            $pdf->Cell($col_width_label, 6, 'Status:', 'R', 0, 'R');
            $pdf->Cell($col_width_value, 6, $workStatus, 'R', 1, 'L');
            $pdf->Cell(0, 0, '', 'T', 1);
            $pdf->Ln(2);


            // Section E: Work Completion & Verification (Now F)
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(8, 7, 'F', 1, 0, 'C', 1); // Adjusted section label
            $pdf->Cell(172, 7, ' Work Completion & Verification [Sign-off and satisfaction rating]', 1, 1, 'L', 1);
            $pdf->SetFont('helvetica', '', 9);

            // Signature area variables
            $servicedBy = $this->fn_general->clear_null($arrUserFullName[intval($woTask['wo_task_fixed_by'])]);
            $verifiedBy = $this->fn_general->clear_null($arrUserFullName[intval($woTask['wo_task_verified_by'])]);
            $servicedDate = !empty($woTask['wo_task_time_executed']) ? $this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed']) : '';
            $verifiedDate = !empty($woTask['wo_task_time_verified']) ? $this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified']) : '';

            // Placeholder for Checked By
            $checkedByName = '';
            $checkedByDesignation = '';
            $checkedByDate = '';
            if ($woTask['wo_task_is_wr'] === '0') { // Assuming 'Checked By' is for self-finding and not required for WR
                 $checkedByName = 'Not Required';
                 $checkedByDesignation = '';
                 $checkedByDate = '';
            }
            // For actual implementation, you'd fetch the 'checked by' person here

            $signature_area_height = 25; // Height for the signature boxes
            $signature_label_width = (210 - (PDF_MARGIN_LEFT + PDF_MARGIN_RIGHT) - (5 * 2)) / 3; // Approx width for 3 cols (180 - 10)/3 = 56.66

            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->Cell($signature_label_width, 6, 'Serviced By:', 1, 0, 'C');
            $pdf->Cell(5, 6, '', 0, 0); // Gap
            $pdf->Cell($signature_label_width, 6, 'Checked By:', 1, 0, 'C');
            $pdf->Cell(5, 6, '', 0, 0); // Gap
            $pdf->Cell($signature_label_width, 6, 'Verified By:', 1, 1, 'C');

            $y_before_signatures = $pdf->GetY();
            // Serviced By box (using Multicell for placeholder text to wrap)
            $pdf->MultiCell($signature_label_width, $signature_area_height, '', 1, 'C', 0, 0, $pdf->GetX(), $y_before_signatures, true, 0, false, true, $signature_area_height, 'M', false);
            $pdf->Cell(5, $signature_area_height, '', 0, 0);
            // Checked By box
            $pdf->MultiCell($signature_label_width, $signature_area_height, '', 1, 'C', 0, 0, $pdf->GetX(), $y_before_signatures, true, 0, false, true, $signature_area_height, 'M', false);
            $pdf->Cell(5, $signature_area_height, '', 0, 0);
            // Verified By box
            $pdf->MultiCell($signature_label_width, $signature_area_height, '', 1, 'C', 0, 1, $pdf->GetX(), $y_before_signatures, true, 0, false, true, $signature_area_height, 'M', false);
            $pdf->Ln(1); // Small space

            // Move cursor after the multicells for names/designations
            $pdf->SetY($y_before_signatures + $signature_area_height + 1);
            $pdf->SetX(PDF_MARGIN_LEFT);

            // Names
            $pdf->MultiCell($signature_label_width, 6, "Name: " . $servicedBy, 'LR', 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->Cell(5, 6, '', 0, 0);
            $pdf->MultiCell($signature_label_width, 6, "Name: " . $checkedByName, 'R', 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->Cell(5, 6, '', 0, 0);
            $pdf->MultiCell($signature_label_width, 6, "Name: " . $verifiedBy, 'R', 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);

            // Designation & Date
            $pdf->SetX(PDF_MARGIN_LEFT);
            $pdf->MultiCell($signature_label_width, 10, "Date: " . $servicedDate, 'LRB', 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->Cell(5, 10, '', 0, 0);
            $pdf->MultiCell($signature_label_width, 10, "Date: " . $checkedByDate, 'RB', 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->Cell(5, 10, '', 0, 0);
            $pdf->MultiCell($signature_label_width, 10, "Date: " . $verifiedDate, 'RB', 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->Ln(2);

            // Add the dynamic signature images
            $signService = false;
            $signVerified = false;
            // Loop through all uploads again to place signatures
            foreach ($woUploads as $woUpload) {
                $uploadType = $woUpload['wo_task_upload_type'];
                if ($woUpload['upload_extension'] === 'png' || $woUpload['upload_extension'] === 'jpg') { // Added jpg for robustness
                    $fileDir = $woUpload['upload_folder'].'/'.$woUpload['upload_filename'].'.'.$woUpload['upload_extension'];
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Sign : '.$fileDir);
                    
                    // Construct the absolute path for the image
                    // Assuming upload_folder is relative to your web root, or adjust as needed.
                    // If upload_folder is like 'upload/15/2080', then it's often DOCUMENT_ROOT/upload/...
                    // Or, if it's an absolute path already provided by upload_folder, use as is.
                    $abs_image_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $fileDir; // Common scenario
                    // You might need to adjust this depending on your server setup for 'upload_folder'
                    // Example: if upload_folder already contains the full server path, then just $fileDir.
                    // Example 2: if upload folder is relative to API directory: dirname(dirname(__DIR__)) . '/' . $fileDir;


                    if ($uploadType === '7' && $signService === false) { // Service By signature
                        // Check if file exists before trying to embed
                        if (file_exists($abs_image_path)) {
                            $pdf->Image($abs_image_path, PDF_MARGIN_LEFT + ($signature_label_width / 2) - 20, $y_before_signatures + 5, 40, 20, '', '', '', false, 300);
                            $signService = true;
                        } else {
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Signature file not found: ' . $abs_image_path);
                        }
                    } else if ($uploadType === '8' && $signVerified === false) { // Verified By signature
                        // Check if file exists before trying to embed
                        if (file_exists($abs_image_path)) {
                            $pdf->Image($abs_image_path, PDF_MARGIN_LEFT + $signature_label_width + 5 + ($signature_label_width / 2) - 20, $y_before_signatures + 5, 40, 20, '', '', '', false, 300);
                            $signVerified = true;
                        } else {
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Signature file not found: ' . $abs_image_path);
                        }
                    }
                }
            }
            $pdf->Ln(2); // Ensure cursor is below signature section


            // Satisfaction Rating
            $pdf->MultiCell(0, 5, '**Notes: For self-finding cases, part-level checks do not require a signature. Final verification must be signed by the immediate superior.', 0, 'L', 0, 1);
            $pdf->Ln(2);

            $currentRate = !empty($woTask['wo_task_rate']) ? intval($woTask['wo_task_rate']) : 0;
            $rating_options = [
                1 => 'Very Dissatisfied',
                2 => 'Dissatisfied',
                3 => 'Neutral',
                4 => 'Satisfied',
                5 => 'Very Satisfied'
            ];

            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(40, 6, 'Satisfactory Level: [Choose]', 0, 0, 'L');
            $rating_cell_width_num = 10;
            $rating_cell_width_desc = 28; // Adjusted for better fit

            foreach ($rating_options as $num => $desc) {
                $fill = ($currentRate === $num) ? true : false;
                $pdf->SetFillColor(($currentRate === $num) ? 200 : 255, ($currentRate === $num) ? 200 : 255, ($currentRate === $num) ? 200 : 255); // Highlight selected
                $pdf->Cell($rating_cell_width_num, 6, $num, 1, 0, 'C', $fill);
                $pdf->Cell($rating_cell_width_desc, 6, $desc, 1, 0, 'L', $fill);
                $pdf->SetFillColor(255, 255, 255); // Reset fill color
            }
            $pdf->Ln(2);


            // Section J: Photo Documentation (Before)
            // Added page break logic here
            if ($pdf->GetY() > 240) { // Check if enough space for images + next section
                $pdf->AddPage();
            }
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 7, 'G. Photo Documentation (Before) [Visual proof for each repair stage]', 1, 1, 'L', 1); // Adjusted section label
            $pdf->SetFont('helvetica', '', 9);

            $image_width_doc = 90; // Larger width for images as they are 1-2 per row
            $image_height_doc = 60;
            $image_spacing_doc = 5;

            if (!empty($img_before)) {
                $y_before_image_doc = $pdf->GetY();
                $pdf->Image($img_before['upload_folder'] . '/' . $img_before['upload_filename'] . '.' . $img_before['upload_extension'],
                            PDF_MARGIN_LEFT, $y_before_image_doc, $image_width_doc, $image_height_doc, '', '', '', false, 300, '', false, false, 1, false, false, false);
                $pdf->SetX(PDF_MARGIN_LEFT + $image_width_doc + $image_spacing_doc);
                $pdf->MultiCell(80, $image_height_doc,
                    "Description : " . $this->fn_general->clear_null($img_before['wo_task_upload_desc']) .
                    "\nTime Taken : " . $this->fn_general->convertDateToDisplay($img_before['wo_task_upload_timestamp']) .
                    "\nLongitude : " . $this->fn_general->clear_null($img_before['wo_task_upload_longitude']) .
                    "\nLatitude : " . $this->fn_general->clear_null($img_before['wo_task_upload_latitude']),
                    1, 'L', 0, 1, '', '', true, 0, false, true, $image_height_doc, 'T', false);
                $pdf->Ln(2);
            } else {
                $pdf->Cell(0, $image_height_doc + 10, 'No "Before" image available.', 1, 1, 'C', 0); // Placeholder box
                $pdf->Ln(2);
            }

            // Section J: Photo Documentation (During)
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 7, 'H. Photo Documentation (During) [Visual proof for each repair stage]', 1, 1, 'L', 1); // Adjusted section label
            $pdf->SetFont('helvetica', '', 9);

            if (!empty($img_during)) {
                $count = 0;
                foreach ($img_during as $img_display) {
                    if ($pdf->GetY() + $image_height_doc + 10 > $pdf->getPageHeight() - PDF_MARGIN_BOTTOM) {
                        $pdf->AddPage();
                    }
                    $y_before_image_doc = $pdf->GetY();
                    $pdf->Image($img_display['upload_folder'] . '/' . $img_display['upload_filename'] . '.' . $img_display['upload_extension'],
                                PDF_MARGIN_LEFT, $y_before_image_doc, $image_width_doc, $image_height_doc, '', '', '', false, 300, '', false, false, 1, false, false, false);
                    $pdf->SetX(PDF_MARGIN_LEFT + $image_width_doc + $image_spacing_doc);
                    $pdf->MultiCell(80, $image_height_doc,
                        "Description : " . $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                        "\nTime Taken : " . $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                        "\nLongitude : " . $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) .
                        "\nLatitude : " . $this->fn_general->clear_null($img_display['wo_task_upload_latitude']),
                        1, 'L', 0, 1, '', '', true, 0, false, true, $image_height_doc, 'T', false);
                    $pdf->Ln(2);
                    $count++;
                    if ($count % 1 === 0 && count($img_during) > $count) { // Forces new line if more images
                        $pdf->Ln(2); // Additional small gap
                    }
                }
            } else {
                $pdf->Cell(0, $image_height_doc + 10, 'No "During" images available.', 1, 1, 'C', 0); // Placeholder box
                $pdf->Ln(2);
            }

            // Section J: Photo Documentation (After)
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 7, 'I. Photo Documentation (After) [Visual proof for each repair stage]', 1, 1, 'L', 1); // Adjusted section label
            $pdf->SetFont('helvetica', '', 9);

            if (!empty($img_after)) {
                $y_before_image_doc = $pdf->GetY();
                $pdf->Image($img_after['upload_folder'] . '/' . $img_after['upload_filename'] . '.' . $img_after['upload_extension'],
                            PDF_MARGIN_LEFT, $y_before_image_doc, $image_width_doc, $image_height_doc, '', '', '', false, 300, '', false, false, 1, false, false, false);
                $pdf->SetX(PDF_MARGIN_LEFT + $image_width_doc + $image_spacing_doc);
                $pdf->MultiCell(80, $image_height_doc,
                    "Description : " . $this->fn_general->clear_null($img_after['wo_task_upload_desc']) .
                    "\nTime Taken : " . $this->fn_general->convertDateToDisplay($img_after['wo_task_upload_timestamp']) .
                    "\nLongitude : " . $this->fn_general->clear_null($img_after['wo_task_upload_longitude']) .
                    "\nLatitude : " . $this->fn_general->clear_null($img_after['wo_task_upload_latitude']),
                    1, 'L', 0, 1, '', '', true, 0, false, true, $image_height_doc, 'T', false);
                $pdf->Ln(2);
            } else {
                $pdf->Cell(0, $image_height_doc + 10, 'No "After" image available.', 1, 1, 'C', 0); // Placeholder box
                $pdf->Ln(2);
            }


            // Final Notes (as per original template text)
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 6, 'Notes:', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(0, 5, 'Upon completion, the work request will be closed, and the user will receive a notification email for receipt acknowledgment.', 0, 'L', 0, 1);
            $pdf->MultiCell(0, 5, 'If the Work Request (WR) is rejected, and you wish to submit a new complaint or request, please initiate a new Work Request submission.', 0, 'L', 0, 1);
            $pdf->MultiCell(0, 5, '[Note: At least one image is required for each stage. Additional images are optional as needed].', 0, 'L', 0, 1);
            $pdf->Ln(2);


            // ---------------------------------------------------------
            // Output PDF document (keep existing logic)
            $folder_code = floor(intval($this->woTaskId)/1000);
            $folder = 'pdf/wo/'.$folder_code;

            $result = $this->fn_general->folderExist($folder);
            if (!$result) {
                mkdir ($folder,0777, true);
            }
            $filename = 'wo_'.substr((10000000+intval($this->woTaskId)),1).'.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : '.$filename);

            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'File : '.__FILE__);
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Environment : '.$environment);
            if ($environment == 'windows') {
                $filename_src = '\wo\\' . $folder_code . '\\' . $filename;
            } else {
                $filename_src = '/wo/' . $folder_code . '/' . $filename;
            }
            $pdf->Output(dirname(__FILE__). $filename_src, 'F');

            $pdfId = $woTask['pdf_id'];
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_select_col('sys_pdf', array('pdf_filename'=>$filename, 'pdf_status'=>'1'), 'pdf_id');
            }
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_insert('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'wo', 'pdf_folder'=>$folder));
            } else {
                Class_db::getInstance()->db_update('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'wo', 'pdf_folder'=>$folder, 'pdf_timeCreated'=>'Now()'), array('pdf_id'=>$pdfId));
            }
            Class_db::getInstance()->db_update('wo_task', array('pdf_id'=>$pdfId, 'wo_task_is_pdf'=>'0'), array('wo_task_id'=>$this->woTaskId));

            return array(
                'pdfId'=>$pdfId,
                'woTaskNo'=>$woTask['wo_task_no']
            );
        } catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}