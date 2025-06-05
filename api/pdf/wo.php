<?php
class MYPDF_wo extends TCPDF {
    // Page footer: replaced $this->w → $this->getPageWidth()
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        // draw a horizontal line across the full printable width
        $this->Line(
            PDF_MARGIN_LEFT,
            $this->y,
            $this->getPageWidth() - PDF_MARGIN_RIGHT,
            $this->y
        );
        $pageNo = 'Page ' . strval($this->getAliasNumPage()) . ' of ' . $this->getAliasNbPages();
        $this->Cell(180, 6, $pageNo, 0, 0, 'R', 0);
    }
}

class Class_pdf_wo {
    private $fn_general;
    private $woTaskId;

    function __construct() {
    }

    // Magic getters/setters (unchanged)...
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
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }
    public function __isset($property) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }
    public function __unset($property) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * create_pdf():
     *   - Generates a single PDF containing both WR and WO sections,
     *     with all MultiCell widths summing to the printable page width
     *     and all dynamic MultiCells properly resetting X/Y.
     */
    public function create_pdf() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId Empty');
            }

            // *** 1) Fetch data exactly as before ***
            $woTask = Class_db::getInstance()->db_select_single(
                'wo_task',
                array('wo_task_id' => $this->woTaskId),
                null, 1
            );
            if (!$woTask) {
                throw new Exception('WO Task ID not found');
            }

            $userProfile = Class_db::getInstance()->db_select_single(
                'sys_user_profile',
                array(
                    'user_id' => $woTask['wo_task_created_by'],
                    'user_profile_status' => '1'
                ),
                null, 1
            );
            $arrUserFullName = $this->fn_general->getUserFullName(); // [user_id] => “Full Name”
            $arrSiteName     = $this->fn_general->getSiteName();     // [site_id] => “Site Name”
            $arrCategory     = array('', 'Complaint', 'Finding', 'Request', 'Breakdown', 'Defect', 'Public Complaint');
            $arrSeverity     = $this->fn_general->getSeverityName(); // [severity_id] => “Non-Critical” etc.

            // build SLA & due arrays exactly as before
            $clientId = Class_db::getInstance()->db_select_col(
                'cli_site',
                array('site_id' => $woTask['site_id']),
                'client_id',
                null, 1
            );
            $arrSla = array('', '4 hours', '2 hours');
            $arrDue = array('', '4', '2');
            $arrClientSeverity = Class_db::getInstance()->db_select(
                'cli_client_severity',
                array('client_id' => $clientId)
            );
            foreach ($arrClientSeverity as $clientSeverity) {
                $sevKey = intval($clientSeverity['severity_id']);
                $arrSla[$sevKey] = $clientSeverity['client_severity_hour'] . ' hours';
                $arrDue[$sevKey] = $clientSeverity['client_severity_hour'];
            }

            // *** 2) Instantiate TCPDF and set up margins/auto‐page breaks ***
            $pdf = new MYPDF_wo(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Muhammad Zaid');
            $pdf->SetTitle('GEMS 2.0 WR & WO');
            $pdf->SetSubject('GEMS 2.0 WR & WO');

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(true);

            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            $pdf->AddPage();

            // Compute **printable width/height** once, for use in all subsequent column calculations:
            $printableW = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
            $printableH = $pdf->getPageHeight() - PDF_MARGIN_TOP - PDF_MARGIN_BOTTOM;

            // --------------------
            // HEADER: WR & WO Title
            // --------------------
            $pdf->SetFont('helvetica', 'B', 18);
            $pdf->Cell(0, 10, 'WORK REQUEST (WR) &', 0, 1, 'C');
            $pdf->Cell(0, 10, 'WORK ORDER (WO)', 0, 1, 'C');
            $pdf->Ln(4);

            // A thin line across the full printable width
            $pdf->SetLineWidth(0.2);
            $pdf->Line(
                PDF_MARGIN_LEFT,
                $pdf->GetY(),
                $pdf->getPageWidth() - PDF_MARGIN_RIGHT,
                $pdf->GetY()
            );
            $pdf->Ln(6);

            // =================================================================================
            // 3) WR SECTION
            //    A: Complaint Details
            //    B1: Description of Complaint
            //    B2: Complaint Images
            //    C1: Work Assessment Details
            //    C2: Response Images
            //    D1: Validation Details
            //    D2: Remark Details
            // =================================================================================

            // ----------------------------------------
            // WR – A) Complaint Details
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->SetTextColor(0);
            // “A” cell is 8mm wide; the remainder is  printableW - 8
            $pdf->Cell(8, 8, 'A', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8,
                ' Complaint Details [User Details: Public & Client for Complaints or Internal: for Self-Finding]',
                1, 0, 'L', 1
            );
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            // Row 1: Reported by / Phone No
            $pdf->Cell(30, 6, 'Reported by:', 1, 0, 'R');
            $pdf->Cell(60, 6,
                $arrUserFullName[intval($woTask['wo_task_created_by'])]
                , 1, 0, 'L');
            $pdf->Cell(35, 6, 'Phone No:', 1, 0, 'R');
            $pdf->Cell(55, 6,
                $this->fn_general->clear_null($userProfile['user_contact_no'])
                , 1, 0, 'L'
            );
            $pdf->Ln();

            // Row 2: Email / Reported Date/Time
            $pdf->Cell(30, 6, 'Email:', 1, 0, 'R');
            $pdf->Cell(60, 6,
                $this->fn_general->clear_null($userProfile['user_email'])
                , 1, 0, 'L'
            );
            $pdf->Cell(35, 6, 'Reported Date / Time:', 1, 0, 'R');
            $pdf->Cell(55, 6,
                $this->fn_general->convertDateToDisplay($woTask['wo_task_time_created'])
                , 1, 0, 'L'
            );
            $pdf->Ln();

            // Row 3: Category / Severity
            $pdf->Cell(30, 6, 'Category:', 1, 0, 'R');
            $pdf->Cell(60, 6,
                $arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'], 0))]
                , 1, 0, 'L'
            );
            $pdf->Cell(35, 6, 'Severity:', 1, 0, 'R');
            $pdf->Cell(55, 6,
                $arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))]
                , 1, 0, 'L'
            );
            $pdf->Ln();

            // Row 4: Work Request No / Location Complaint
            $pdf->Cell(30, 6, 'Work Request No:', 1, 0, 'R');
            $pdf->Cell(60, 6,
                // Generate WR number or leave blank for manual
                'WR' . substr('GFMHQ' . date('ymd'), 0, 9) . str_pad($this->woTaskId, 5, '0', STR_PAD_LEFT)
                , 1, 0, 'L'
            );
            $pdf->Cell(35, 6, 'Location Complaint:', 1, 0, 'R');
            $pdf->Cell(55, 6,
                $this->fn_general->clear_null($woTask['wo_task_location'])
                , 1, 0, 'L'
            );
            $pdf->Ln(12);

            // ----------------------------------------
            // WR – B1) Description of Complaint (dynamic height)
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'B1', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8, ' Description of Complaint [Manual Entry]', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            // 1) capture start X/Y
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();

            // 2) measure two MultiCells (both border=0, linebreak=0)
            $cellcount1 = $pdf->MultiCell(8, 4, '', 0, 'L', 0, 0);
            $cellcount2 = $pdf->MultiCell($printableW - 8, 4, '', 0, 'L', 0, 0);
            $maxnocells = max($cellcount1, $cellcount2);

            // 3) reset X/Y, draw the final bordered cells
            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell(8, ($maxnocells * 4) + 8, '', 1, 'L', 0, 0);
            $pdf->MultiCell($printableW - 8, ($maxnocells * 4) + 8, '', 1, 'L', 0, 0);

            // 4) one Ln() to move to the next line
            $pdf->Ln(12);

            // ----------------------------------------
            // WR – B2) Complaint Images
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'B2', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8, ' Complaint Images [Complain from User]', 1, 0, 'L', 1);
            $pdf->Ln();

            // fetch complaint‐type uploads (type=1)
            $img_complaint = array();
            $woUploads = Class_db::getInstance()->db_select(
                'mw_wo_upload',
                array(
                    'wo_task_id' => $this->woTaskId,
                    'sys_upload.upload_status' => '1'
                )
            );
            foreach ($woUploads as $woUpload) {
                if ($woUpload['wo_task_upload_type'] === '1') {
                    $img_complaint[] = $woUpload;
                }
            }

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($img_complaint)) {
                // we want 3 columns: 8mm, IMAGE_COL, DESC_COL, where IMAGE_COL+DESC_COL = printableW−8
                $col1 = 8;
                $remaining = $printableW - $col1;
                // give roughly 48% of (remaining) to the image column
                $col2 = round($remaining * 0.48);
                $col3 = $remaining - $col2;

                foreach ($img_complaint as $img_display) {
                    // if there isn’t enough vertical space for a 65mm‐tall row, page‐break
                    $buffer = 65 + 4; // 65 height + a little extra
                    if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - $buffer)) {
                        $pdf->AddPage();
                    }
                    // Column 1: placeholder
                    $pdf->writeHTMLCell($col1, 65, '', '', '', 1, 0, false, true, 'C');
                    // Column 2: image itself
                    $imgPath = $img_display['upload_folder'] . '/' .
                               $img_display['upload_filename'] . '.' .
                               $img_display['upload_extension'];
                    $pdf->writeHTMLCell(
                        $col2, 65, '', '',
                        '<br/><br/><img src="' . $imgPath . '" height="200" />',
                        1, 0, false, true, 'C'
                    );
                    // Column 3: description, timestamp, coordinates
                    $descHtml = '<br/><br/>Description: ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                                '<br/>Date / Time Taken: ' .
                                $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                                '<br/>GPS Coordinates: ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) . ', ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_latitude']);
                    $pdf->writeHTMLCell($col3, 65, '', '', $descHtml, 1, 0, false, true, 'L');
                    $pdf->Ln();
                }
            } else {
                // if no complaint images, draw a single empty row of height 12
                $pdf->Cell(8, 12, '', 1, 0, 'C', 0);
                $pdf->Cell($printableW - 8, 12, '', 1, 0, 'L', 0);
                $pdf->Ln();
            }
            $pdf->Ln(4);

            // ----------------------------------------
            // WR – C1) Work Assessment Details
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'C1', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8,
                ' Work Assessment Details [Selected by P.I.C. to verify the complaint]',
                1, 0, 'L', 1
            );
            $pdf->Ln();

            $picName    = '';
            $picEmail   = '';
            $dueTime    = '';
            $assignTime = '';
            $wrVerifyTime = '';
            $fixedTime  = '';
            if (!empty($woTask['wo_task_assigned_to'])) {
                $picName = $arrUserFullName[intval($woTask['wo_task_assigned_to'])];
                $userProfileTech = Class_db::getInstance()->db_select_single(
                    'sys_user_profile',
                    array(
                        'user_id' => $woTask['wo_task_assigned_to'],
                        'user_profile_status' => '1'
                    ), null, 1
                );
                $picEmail = $this->fn_general->clear_null($userProfileTech['user_email']);

                // compute WR due time = created + (arrDue) hours
                $createdTime = new DateTime($woTask['wo_task_time_created']);
                if (!empty($woTask['wo_task_severity'])) {
                    $dueTimeDt = clone $createdTime;
                    $dueTimeDt->modify('+' . $arrDue[intval($woTask['wo_task_severity'])] . ' hour');
                    $dueTime = $dueTimeDt->format('j/n/Y g:i:sa');
                }

                if (!empty($woTask['wo_task_time_assigned'])) {
                    $assignedDt = new DateTime($woTask['wo_task_time_assigned']);
                    $assignTime = $assignedDt->format('j/n/Y g:i:sa');
                }
                if (!empty($woTask['wo_task_time_wr_verified'])) {
                    $wrVerDt = new DateTime($woTask['wo_task_time_wr_verified']);
                    $wrVerifyTime = $wrVerDt->format('j/n/Y g:i:sa');
                }
                if (!empty($woTask['wo_task_time_executed'])) {
                    $execDt = new DateTime($woTask['wo_task_time_executed']);
                    $fixedTime = $execDt->format('j/n/Y g:i:sa');
                }
            }

            $pdf->SetFont('helvetica', '', 9);
            // Row 1: Person in Charge / SLA Respond Time
            $pdf->Cell(30, 5, 'Person in Charge:', 1, 0, 'R');
            $pdf->Cell(60, 5, ($picName ? $picName : '[Select from System]'), 1, 0, 'L');
            $pdf->Cell(35, 5, 'SLA Respond Time:', 1, 0, 'R');
            $pdf->Cell(55, 5,
                ($picName && $dueTime ? $arrSla[intval($woTask['wo_task_severity'])] : '[Select from System]'),
                1, 0, 'L'
            );
            $pdf->Ln();

            // Row 2: Email / WR Due Date Time
            $pdf->Cell(30, 5, 'Email:', 1, 0, 'R');
            $pdf->Cell(60, 5, ($picName ? $picEmail : ''), 1, 0, 'L');
            $pdf->Cell(35, 5, 'WR Due Date Time:', 1, 0, 'R');
            $pdf->Cell(55, 5, ($dueTime ? $dueTime : ''), 1, 0, 'L');
            $pdf->Ln();

            // Row 3 (if is_wr): Respond Date / Duration / Respond Status
            if (!empty($woTask['wo_task_is_wr']) && $woTask['wo_task_is_wr'] === '1') {
                $respondDuration = '';
                if (!empty($woTask['wo_task_time_created']) && !empty($woTask['wo_task_time_assigned'])) {
                    $respondDuration = $this->fn_general->timeDiff(
                        $woTask['wo_task_time_created'],
                        $woTask['wo_task_time_assigned']
                    );
                }
                $statusText = '';
                if ($assignTime && $dueTime) {
                    $dueDt = new DateTime($dueTime);
                    $assignDt = new DateTime($assignTime);
                    $statusText = ($assignDt <= $dueDt ? 'Within' : 'Exceed');
                }
                $pdf->Cell(30, 5, 'Respond Date / Duration:', 1, 0, 'R');
                $pdf->Cell(60, 5, ($assignTime ? $assignTime . ', ' . $respondDuration : ''), 1, 0, 'L');
                $pdf->Cell(35, 5, 'Respond Status:', 1, 0, 'R');
                $pdf->Cell(55, 5, ($statusText ? $statusText : ''), 1, 0, 'L');
                $pdf->Ln();
            }

            $pdf->Ln(4);

            // ----------------------------------------
            // WR – C2) Response Images
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'C2', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8, ' Response Images [P.I.C. verification of complaint]', 1, 0, 'L', 1);
            $pdf->Ln();

            // fetch response‐type uploads (type=2)
            $img_response = array();
            foreach ($woUploads as $woUpload) {
                if ($woUpload['wo_task_upload_type'] === '2') {
                    $img_response[] = $woUpload;
                }
            }

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($img_response)) {
                // reuse the same column widths as B2
                $col1 = 8;
                $remaining = $printableW - $col1;
                $col2 = round($remaining * 0.48);
                $col3 = $remaining - $col2;

                foreach ($img_response as $img_display) {
                    $buffer = 65 + 4;
                    if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - $buffer)) {
                        $pdf->AddPage();
                    }
                    $pdf->writeHTMLCell($col1, 65, '', '', '', 1, 0, false, true, 'C');
                    $imgPath = $img_display['upload_folder'] . '/' .
                               $img_display['upload_filename'] . '.' .
                               $img_display['upload_extension'];
                    $pdf->writeHTMLCell(
                        $col2, 65, '', '',
                        '<br/><br/><img src="' . $imgPath . '" height="200" />',
                        1, 0, false, true, 'C'
                    );
                    $descHtml = '<br/><br/>Description: ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                                '<br/>Date / Time Taken: ' .
                                $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                                '<br/>Longitude / Latitude: ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) . ', ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_latitude']);
                    $pdf->writeHTMLCell($col3, 65, '', '', $descHtml, 1, 0, false, true, 'L');
                    $pdf->Ln();
                }
            } else {
                $pdf->Cell(8, 12, '', 1, 0, 'C', 0);
                $pdf->Cell($printableW - 8, 12, '', 1, 0, 'L', 0);
                $pdf->Ln();
            }
            $pdf->Ln(4);

            // ----------------------------------------
            // WR – D1) Validation Details
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'D1', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8,
                ' Validation Details [Who issues / assigns the WR to P.I.C.]',
                1, 0, 'L', 1
            );
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(30, 6, 'Validation by:', 1, 0, 'R');
            $pdf->Cell(60, 6, '[Select from System]', 1, 0, 'L');
            $pdf->Cell(35, 6, 'Designation:', 1, 0, 'R');
            $pdf->Cell(55, 6, '[System Generated]', 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 6, 'Verified Date:', 1, 0, 'R');
            $pdf->Cell(60, 6, '[System Generated]', 1, 0, 'L');
            $pdf->Cell(35, 6, 'Work Request Status:', 1, 0, 'R');
            $pdf->Cell(55, 6, '[Accept/Reject]', 1, 0, 'L');
            $pdf->Ln(12);

            // ----------------------------------------
            // WR – D2) Remark Details (dynamic height)
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'D2', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8, ' Remark Details [Manual Entry]', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $cellcount1 = $pdf->MultiCell(8, 4, '', 0, 'L', 0, 0);
            $cellcount2 = $pdf->MultiCell($printableW - 8, 4, '', 0, 'L', 0, 0);
            $maxnocells = max($cellcount1, $cellcount2);

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell(8, ($maxnocells * 4) + 8, '', 1, 'L', 0, 0);
            $pdf->MultiCell($printableW - 8, ($maxnocells * 4) + 8, '', 1, 'L', 0, 0);
            $pdf->Ln(12);

            // =================================================================================
            // 4) WO SECTION (starts here)
            //    A) Work Order Details
            //    B1) Work Assignment Details
            //    B2) Support Personnel
            //    C) Material Details
            //    D) Work Execution Details
            //    E) Work Completion & Verification
            //    J) Photo Documentation (Before / During / After)
            // =================================================================================

            // ----------------------------------------
            // WO – A) Work Order Details
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'A', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8, ' Work Order Details', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            // Row 1: Work Order No / Status
            $pdf->Cell(30, 6, 'Work Order No:', 1, 0, 'R');
            $pdf->Cell(60, 6,
                'WO' . substr('GFMHQ' . date('ymd'), 0, 9) . str_pad($this->woTaskId, 5, '0', STR_PAD_LEFT)
                , 1, 0, 'L'
            );
            $pdf->Cell(35, 6, 'Status:', 1, 0, 'R');
            $pdf->Cell(55, 6,
                (!empty($woTask['wo_task_time_executed']) ? 'Completed' : 'Open')
                , 1, 0, 'L'
            );
            $pdf->Ln();

            // Row 2: Work Request No / Category
            $pdf->Cell(30, 6, 'Work Request No:', 1, 0, 'R');
            $pdf->Cell(60, 6,
                'WR' . substr('GFMHQ' . date('ymd'), 0, 9) . str_pad($this->woTaskId, 5, '0', STR_PAD_LEFT)
                , 1, 0, 'L'
            );
            $pdf->Cell(35, 6, 'Category:', 1, 0, 'R');
            $pdf->Cell(55, 6,
                $arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'], 0))]
                , 1, 0, 'L'
            );
            $pdf->Ln();

            // Row 3: Location Name / Location Code
            $pdf->Cell(30, 6, 'Location Name:', 1, 0, 'R');
            $pdf->Cell(60, 6,
                $arrSiteName[intval($woTask['site_id'])]
                , 1, 0, 'L'
            );
            $pdf->Cell(35, 6, 'Location Code:', 1, 0, 'R');
            $pdf->Cell(55, 6, '', 1, 0, 'L'); // manual or blank
            $pdf->Ln();

            // Row 4: Asset Name / Asset Code
            $pdf->Cell(30, 6, 'Asset Name:', 1, 0, 'R');
            $pdf->Cell(60, 6, '[Select from System or free text]', 1, 0, 'L');
            $pdf->Cell(35, 6, 'Asset Code:', 1, 0, 'R');
            $pdf->Cell(55, 6, '[System Generated]', 1, 0, 'L');
            $pdf->Ln();

            // Row 5: Severity / WO Due Date Time
            $pdf->Cell(30, 6, 'Severity:', 1, 0, 'R');
            $pdf->Cell(60, 6,
                $arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))]
                , 1, 0, 'L'
            );
            $pdf->Cell(35, 6, 'WO Due Date / Time:', 1, 0, 'R');
            $pdf->Cell(55, 6,
                ($dueTime ? $dueTime : '')
                , 1, 0, 'L'
            );
            $pdf->Ln();

            // Row 6: Complaint Description
            $pdf->Cell(30, 6, 'Complaint Description:', 1, 0, 'R');
            $pdf->Cell($printableW - 30, 6, '', 1, 0, 'L'); // blank or from WR
            $pdf->Ln(12);

            // ----------------------------------------
            // WO – B1) Work Assignment Details
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'B1', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8,
                ' Work Assignment Details [Details of task issuer and receiver]',
                1, 0, 'L', 1
            );
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            // Row 1: Received By / Assigned To / Date Assigned / Phone No
            $pdf->Cell(30, 6, 'Received By:', 1, 0, 'R');
            $pdf->Cell(60, 6, '[System Generated]', 1, 0, 'L');
            $pdf->Cell(30, 6, 'Assigned To:', 1, 0, 'R');
            $pdf->Cell(50, 6, '[Select from System]', 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 6, 'Date Assigned:', 1, 0, 'R');
            $pdf->Cell(60, 6, '[System Generated]', 1, 0, 'L');
            $pdf->Cell(30, 6, 'Phone No:', 1, 0, 'R');
            $pdf->Cell(50, 6, '[System Generated]', 1, 0, 'L');
            $pdf->Ln(12);

            // ----------------------------------------
            // WO – B2) Support Personnel
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'B2', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8, ' Support Personnel [Team members involved in execution]', 1, 0, 'L', 1);
            $pdf->Ln();

            $woAssists = Class_db::getInstance()->db_select(
                'wo_task_assist',
                array('wo_task_id' => $this->woTaskId)
            );
            $pdf->SetFont('helvetica', '', 9);
            // header row
            $pdf->Cell(8, 6, 'No.', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 6, 'Name', 1, 0, 'C', 1);
            $pdf->Ln();

            foreach ($woAssists as $index => $assist) {
                $assistName = $arrUserFullName[intval($assist['user_id'])];
                $pdf->Cell(8, 6, $index + 1, 1, 0, 'C', 0);
                $pdf->Cell($printableW - 8, 6, $assistName, 1, 0, 'L', 0);
                $pdf->Ln();
            }
            $pdf->Ln(4);

            // ----------------------------------------
            // WO – C) Material Details
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'C', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8, ' Material Details [Parts or materials issued / returned]', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            // header row for materials table: [30 + 70 + 20 + 15 + 25 + 25 = 185], printableW presumably = 190
            // we’ll spread to exactly printableW:
            $c1 = 30; 
            $c2 = 70; 
            $c3 = 20; 
            $c4 = 15; 
            $c5 = 25; 
            $c6 = $printableW - ($c1+$c2+$c3+$c4+$c5); // ensures total = printableW
            $pdf->Cell($c1, 6, 'Part No.', 1, 0, 'C', 1);
            $pdf->Cell($c2, 6, 'Item Description', 1, 0, 'C', 1);
            $pdf->Cell($c3, 6, 'Issue Type', 1, 0, 'C', 1);
            $pdf->Cell($c4, 6, 'Unit', 1, 0, 'C', 1);
            $pdf->Cell($c5, 6, 'Qty Taken', 1, 0, 'C', 1);
            $pdf->Cell($c6, 6, 'Qty Return', 1, 0, 'C', 1);
            $pdf->Ln();

            // five blank rows for manual entry
            for ($row = 0; $row < 5; $row++) {
                $pdf->Cell($c1, 6, '', 1, 0, 'L', 0);
                $pdf->Cell($c2, 6, '', 1, 0, 'L', 0);
                $pdf->Cell($c3, 6, '', 1, 0, 'C', 0);
                $pdf->Cell($c4, 6, '', 1, 0, 'C', 0);
                $pdf->Cell($c5, 6, '', 1, 0, 'C', 0);
                $pdf->Cell($c6, 6, '', 1, 0, 'C', 0);
                $pdf->Ln();
            }
            $pdf->Ln(4);

            // ----------------------------------------
            // WO – D) Work Execution Details
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'D', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8, ' Work Execution Details [Action duration, task notes, timeline]', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $durationText = '';
            if (!empty($woTask['wo_task_time_assigned']) && !empty($woTask['wo_task_time_executed'])) {
                $durationText = $this->fn_general->timeDiff(
                    $woTask['wo_task_time_assigned'],
                    $woTask['wo_task_time_executed']
                );
            }
            // split printableW into four columns: 40 + 60 + 30 + (printableW−130)
            $wd1 = 40;
            $wd2 = 60;
            $wd3 = 30;
            $wd4 = $printableW - ($wd1 + $wd2 + $wd3);
            $pdf->Cell($wd1, 6, 'Start Date & Time:', 1, 0, 'R');
            $pdf->Cell($wd2, 6,
                ($woTask['wo_task_time_assigned'] ?
                    $this->fn_general->convertDateToDisplay($woTask['wo_task_time_assigned'])
                    : '[System Generated]'
                ), 1, 0, 'L'
            );
            $pdf->Cell($wd3, 6, 'End Date & Time:', 1, 0, 'R');
            $pdf->Cell($wd4, 6,
                ($woTask['wo_task_time_executed'] ?
                    $this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed'])
                    : '[System Generated]'
                ), 1, 0, 'L'
            );
            $pdf->Ln();
            $pdf->Cell($wd1, 6, 'Duration:', 1, 0, 'R');
            $pdf->Cell($wd2, 6, ($durationText ? $durationText : ''), 1, 0, 'L');

            // compute Status (Within/Exceed)
            $statusWO = '';
            if ($durationText && !empty($woTask['wo_task_severity'])) {
                $hoursTaken = 0;
                if (preg_match('/(\d+)\s*Hour/i', $durationText, $m)) {
                    $hoursTaken = intval($m[1]);
                }
                $allowedHours = intval($arrDue[intval($woTask['wo_task_severity'])]);
                $statusWO = ($hoursTaken <= $allowedHours ? 'Within' : 'Exceed');
            }
            $pdf->Cell($wd3, 6, 'Status:', 1, 0, 'R');
            $pdf->Cell($wd4, 6, ($statusWO ? $statusWO : ''), 1, 0, 'L');
            $pdf->Ln(12);

            // ----------------------------------------
            // WO – E) Work Completion & Verification
            // ----------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'E', 1, 0, 'C', 1);
            $pdf->Cell($printableW - 8, 8, ' Work Completion & Verification [Sign‐off & rating]', 1, 0, 'L', 1);
            $pdf->Ln();

            $servicedBy = '';
            $checkedBy  = '';
            $verifiedBy = '';
            if (!empty($woTask['wo_task_fixed_by'])) {
                $servicedBy = $arrUserFullName[intval($woTask['wo_task_fixed_by'])];
            }
            if (!empty($woTask['wo_task_verified_by'])) {
                $verifiedBy = $arrUserFullName[intval($woTask['wo_task_verified_by'])];
            }
            // three equal columns: each = printableW / 3
            $sigW = round($printableW / 3);
            $pdf->MultiCell($sigW, 18,
                "Serviced By:\n\n\n" .
                "................................................\n" .
                "Name: " . ($servicedBy ? $servicedBy : '') . "\n" .
                "Date / Time: " .
                ($woTask['wo_task_time_executed'] ?
                    $this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed'])
                    : ''
                ),
                1, 'L', 0, 0
            );
            $pdf->MultiCell($sigW, 18,
                "Checked By:\n\n\n" .
                "................................................\n" .
                "Name: " . '', // blank or from DB if available
                1, 'L', 0, 0
            );
            $pdf->MultiCell($sigW, 18,
                "Verified By:\n\n\n" .
                "................................................\n" .
                "Name: " . ($verifiedBy ? $verifiedBy : '') . "\n" .
                "Date / Time: " .
                ($woTask['wo_task_time_verified'] ?
                    $this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified'])
                    : ''
                ),
                1, 'L', 0, 0
            );
            $pdf->Ln(16);

            // Satisfactory Level line
            $ratingText = '';
            if (!empty($woTask['wo_task_rate'])) {
                $ratingText = $woTask['wo_task_rate'] . ' / 5';
            }
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6,
                'Satisfactory Level: [Choose 1–5: 1=Very Dissatisfied … 5=Very Satisfied] ' . $ratingText,
                0, 1, 'L'
            );
            $pdf->Ln(4);

            // ----------------------------------------
            // WO – J) Photo Documentation (Before/During/After)
            // ----------------------------------------
            // categorize uploads by type:
            //   type=2 → before, type=3 → during, type=4 → after
            $img_before = array();
            $img_during = array();
            $img_after  = array();
            foreach ($woUploads as $woUpload) {
                $t = $woUpload['wo_task_upload_type'];
                if ($t === '2') {
                    $img_before[] = $woUpload;
                } elseif ($t === '3') {
                    $img_during[] = $woUpload;
                } elseif ($t === '4') {
                    $img_after[] = $woUpload;
                }
            }

            // If not enough vertical space for “Before” header + 60mm row, page‐break
            $needed = 8 + 60 + 4; // header height + one image row + little buffer
            if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - $needed)) {
                $pdf->AddPage();
            }

            // J1: Photo Documentation (Before)
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, 'Photo Documentation (Before) [Visual proof for each repair stage]', 1, 1, 'L', 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($img_before)) {
                foreach ($img_before as $img_display) {
                    // if not enough vertical space for a 60-high row, page‐break
                    $rowHeight = 60 + 4;
                    if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - $rowHeight)) {
                        $pdf->AddPage();
                    }
                    // left column (60mm) for the image
                    $pdf->writeHTMLCell(60, 60, '', '',
                        '<br/><br/><img src="' .
                        ($img_display['upload_folder'] . '/' . $img_display['upload_filename'] . '.' . $img_display['upload_extension']) .
                        '" height="200" />',
                        1, 0, false, true, 'C'
                    );
                    // right column (printableW - 60) for description
                    $descHtml = '<br/><br/>Description: ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                                '<br/>Date / Time Taken: ' .
                                $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                                '<br/>GPS Coordinates: ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) . ', ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_latitude']);
                    $pdf->writeHTMLCell($printableW - 60, 60, '', '', $descHtml, 1, 1, false, true, 'L');
                    $pdf->Ln(2);
                }
            } else {
                // blank row
                $pdf->Cell(60, 12, '', 1, 0, 'C', 0);
                $pdf->Cell($printableW - 60, 12, '', 1, 1, 'L', 0);
            }
            $pdf->Ln(4);

            // J2: Photo Documentation (During)
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, 'Photo Documentation (During) [Visual proof for each repair stage]', 1, 1, 'L', 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($img_during)) {
                foreach ($img_during as $img_display) {
                    $rowHeight = 60 + 4;
                    if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - $rowHeight)) {
                        $pdf->AddPage();
                    }
                    $pdf->writeHTMLCell(60, 60, '', '',
                        '<br/><br/><img src="' .
                        ($img_display['upload_folder'] . '/' . $img_display['upload_filename'] . '.' . $img_display['upload_extension']) .
                        '" height="200" />',
                        1, 0, false, true, 'C'
                    );
                    $descHtml = '<br/><br/>Description: ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                                '<br/>Date / Time Taken: ' .
                                $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                                '<br/>GPS Coordinates: ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) . ', ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_latitude']);
                    $pdf->writeHTMLCell($printableW - 60, 60, '', '', $descHtml, 1, 1, false, true, 'L');
                    $pdf->Ln(2);
                }
            } else {
                $pdf->Cell(60, 12, '', 1, 0, 'C', 0);
                $pdf->Cell($printableW - 60, 12, '', 1, 1, 'L', 0);
            }
            $pdf->Ln(4);

            // J3: Photo Documentation (After)
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, 'Photo Documentation (After) [Visual proof for each repair stage]', 1, 1, 'L', 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($img_after)) {
                foreach ($img_after as $img_display) {
                    $rowHeight = 60 + 4;
                    if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - $rowHeight)) {
                        $pdf->AddPage();
                    }
                    $pdf->writeHTMLCell(60, 60, '', '',
                        '<br/><br/><img src="' .
                        ($img_display['upload_folder'] . '/' . $img_display['upload_filename'] . '.' . $img_display['upload_extension']) .
                        '" height="200" />',
                        1, 0, false, true, 'C'
                    );
                    $descHtml = '<br/><br/>Description: ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                                '<br/>Date / Time Taken: ' .
                                $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                                '<br/>GPS Coordinates: ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) . ', ' .
                                $this->fn_general->clear_null($img_display['wo_task_upload_latitude']);
                    $pdf->writeHTMLCell($printableW - 60, 60, '', '', $descHtml, 1, 1, false, true, 'L');
                    $pdf->Ln(2);
                }
            } else {
                $pdf->Cell(60, 12, '', 1, 0, 'C', 0);
                $pdf->Cell($printableW - 60, 12, '', 1, 1, 'L', 0);
            }
            $pdf->Ln(4);

            // =================================================================================
            // 5) SAVE & RECORD PDF
            // =================================================================================
            $folder_code = floor(intval($this->woTaskId) / 1000);
            $folder = 'pdf/wo/' . $folder_code;
            if (!$this->fn_general->folderExist($folder)) {
                mkdir($folder, 0777, true);
            }
            $filename = 'wo_' . substr((10000000 + intval($this->woTaskId)), 1) . '.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : ' . $filename);

            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];
            if ($environment == 'windows') {
                $filename_src = '\wo\\' . $folder_code . '\\' . $filename;
            } else {
                $filename_src = '/wo/' . $folder_code . '/' . $filename;
            }

            $pdf->Output(dirname(__FILE__) . $filename_src, 'F');

            $pdfId = $woTask['pdf_id'];
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_select_col(
                    'sys_pdf',
                    array('pdf_filename' => $filename, 'pdf_status' => '1'),
                    'pdf_id'
                );
            }
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_insert(
                    'sys_pdf',
                    array('pdf_filename' => $filename, 'pdf_type' => 'wo', 'pdf_folder' => $folder)
                );
            } else {
                Class_db::getInstance()->db_update(
                    'sys_pdf',
                    array(
                        'pdf_filename'    => $filename,
                        'pdf_type'        => 'wo',
                        'pdf_folder'      => $folder,
                        'pdf_timeCreated' => 'Now()'
                    ),
                    array('pdf_id' => $pdfId)
                );
            }
            Class_db::getInstance()->db_update(
                'wo_task',
                array('pdf_id' => $pdfId, 'wo_task_is_pdf' => '0'),
                array('wo_task_id' => $this->woTaskId)
            );

            return array(
                'pdfId'    => $pdfId,
                'woTaskNo' => $woTask['wo_task_no']
            );
        } catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
