<?php
use TCPDF;

class MYPDF_wo extends TCPDF {
    // Override footer to print "Page X of Y" at bottom
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        // Draw a thin line across page
        $this->Line(PDF_MARGIN_LEFT, $this->y, $this->getPageWidth() - PDF_MARGIN_RIGHT, $this->y);
        // Right‐aligned "Page X of Y"
        $pageNo = 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
        $this->Cell(0, 6, $pageNo, 0, 0, 'R');
    }
}

class Class_pdf_wo {
    private $fn_general;   // utility functions (clear_null, convertDateToDisplay, timeDiff, etc.)
    private $woTaskId;

    public function __construct() {
    }

    // Magic methods (as before)
    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false) {
                $msg = substr($msg, $pos + 1);
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
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Set Property not exist [' . $property . ']'));
        }
    }
    public function __isset($property) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Isset Property not exist [' . $property . ']'));
        }
    }
    public function __unset($property) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Unset Property not exist [' . $property . ']'));
        }
    }

    /**
     * create_pdf()
     *   - Queries all WR + WO data from DB.
     *   - Builds a native‐TCPDF table layout (Cells/MultiCells) with the correct
     *     colors, fonts, borders, and column widths.
     *   - Saves the PDF to disk and updates sys_pdf + wo_task table.
     */
    public function create_pdf() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($this->woTaskId)) {
                throw new Exception('[Line ' . __LINE__ . '] - Parameter woTaskId Empty');
            }

            // 1) Fetch WO task record
            $woTask = Class_db::getInstance()->db_select_single(
                'wo_task',
                ['wo_task_id' => $this->woTaskId],
                null,
                1
            );
            if (!$woTask) {
                throw new Exception('WO Task ID not found');
            }

            // 2) Fetch user/site/config arrays
            $arrUserFullName = $this->fn_general->getUserFullName(); // [ user_id => "Full Name" ]
            $userProfile = Class_db::getInstance()->db_select_single(
                'sys_user_profile',
                [
                    'user_id' => $woTask['wo_task_created_by'],
                    'user_profile_status' => '1'
                ],
                null,
                1
            );
            $arrSiteName = $this->fn_general->getSiteName();
            $arrCategory = ['', 'Complaint', 'Finding', 'Request', 'Breakdown', 'Defect', 'Public Complaint'];
            $arrSeverity = $this->fn_general->getSeverityName();

            // 3) Build SLA / Due arrays by client
            $clientId = Class_db::getInstance()->db_select_col(
                'cli_site',
                ['site_id' => $woTask['site_id']],
                'client_id',
                null,
                1
            );
            $arrSla = ['', '4 hours', '2 hours'];
            $arrDue = ['', '4', '2'];
            $arrClientSeverity = Class_db::getInstance()->db_select(
                'cli_client_severity',
                ['client_id' => $clientId]
            );
            foreach ($arrClientSeverity as $cs) {
                $key = intval($cs['severity_id']);
                $arrSla[$key] = $cs['client_severity_hour'] . ' hours';
                $arrDue[$key] = $cs['client_severity_hour'];
            }

            // 4) Fetch all uploads and bucket them by type
            $woUploadsAll = Class_db::getInstance()->db_select(
                'mw_wo_upload',
                [
                    'wo_task_id' => $this->woTaskId,
                    'sys_upload.upload_status' => '1'
                ]
            );
            $imgComplaint = [];
            $imgBefore = [];
            $imgDuring = [];
            $imgAfter = [];
            $imgResponse = [];
            $signService = '';
            $signVerify = '';

            foreach ($woUploadsAll as $upl) {
                switch ($upl['wo_task_upload_type']) {
                    case '1':
                        $imgComplaint[] = $upl; 
                        break;
                    case '2':
                        $imgBefore[] = $upl;
                        break;
                    case '3':
                        $imgDuring[] = $upl;
                        break;
                    case '4':
                        $imgAfter[] = $upl;
                        break;
                    case '5':
                        $imgResponse[] = $upl;
                        break;
                    case '7':
                        if (!$signService && $upl['upload_extension'] === 'png') {
                            $signService = $upl['upload_folder'] . '/' . $upl['upload_filename'] . '.' . $upl['upload_extension'];
                        }
                        break;
                    case '8':
                        if (!$signVerify && $upl['upload_extension'] === 'png') {
                            $signVerify = $upl['upload_folder'] . '/' . $upl['upload_filename'] . '.' . $upl['upload_extension'];
                        }
                        break;
                    default:
                        // ignore others
                        break;
                }
            }

            // 5) Prepare all textual fields (escape HTML entities)
            $wr_no         = htmlspecialchars($woTask['wo_task_no']);
            $reportedBy    = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_created_by'])]);
            $reportedPhone = htmlspecialchars($this->fn_general->clear_null($userProfile['user_contact_no']));
            $reportedEmail = htmlspecialchars($this->fn_general->clear_null($userProfile['user_email']));
            $reportedDtTxt = htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_created']));
            $categoryTxt   = htmlspecialchars($arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'], 0))]);
            $severityTxt   = htmlspecialchars($arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))]);
            $locationTxt   = htmlspecialchars($this->fn_general->clear_null($woTask['wo_task_location']));
            $complaintTxt  = htmlspecialchars($this->fn_general->clear_null($woTask['wo_task_complaint']));

            // 6) Prepare C1 (Work Assessment) fields
            $picName       = '';
            $picEmail      = '';
            $wrDueTime     = '';
            $assignTime    = '';
            $respondDuration = '';
            $respondStatus = '';

            if (!empty($woTask['wo_task_assigned_to'])) {
                $picName = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_assigned_to'])]);
                $userProfileTech = Class_db::getInstance()->db_select_single(
                    'sys_user_profile',
                    [
                        'user_id' => $woTask['wo_task_assigned_to'],
                        'user_profile_status' => '1'
                    ],
                    null,
                    1
                );
                $picEmail = htmlspecialchars($this->fn_general->clear_null($userProfileTech['user_email']));

                // WR Due Date
                if (!empty($woTask['wo_task_severity'])) {
                    $createdDt = new DateTime($woTask['wo_task_time_created']);
                    $dueDt = clone $createdDt;
                    $dueDt->modify('+' . $arrDue[intval($woTask['wo_task_severity'])] . ' hour');
                    $wrDueTime = htmlspecialchars($dueDt->format('d/m/Y g:i A'));
                }
                // Respond Date / Duration / Status
                if (!empty($woTask['wo_task_time_assigned'])) {
                    $assignedDt = new DateTime($woTask['wo_task_time_assigned']);
                    $assignTime = htmlspecialchars($assignedDt->format('d/m/Y g:i A'));
                    $respondDuration = htmlspecialchars(
                        $this->fn_general->timeDiff(
                            $woTask['wo_task_time_created'],
                            $woTask['wo_task_time_assigned']
                        )
                    );
                    if (!empty($wrDueTime)) {
                        $dueCheck = new DateTime($wrDueTime);
                        $respondStatus = ($assignedDt <= $dueCheck) ? 'Within' : 'Exceed';
                    }
                }
            }

            // 7) Prepare C2 (Response Images) descriptions
            $r1_img = $r1_desc = $r1_ts = $r1_gps = '';
            $r2_img = $r2_desc = $r2_ts = $r2_gps = '';
            $r3_img = $r3_desc = $r3_ts = $r3_gps = '';

            if (!empty($imgResponse[0])) {
                $i = $imgResponse[0];
                $r1_img = $i['upload_folder'] . '/' . $i['upload_filename'] . '.' . $i['upload_extension'];
                $r1_desc = htmlspecialchars($this->fn_general->clear_null($i['wo_task_upload_desc']));
                $r1_ts   = htmlspecialchars($this->fn_general->convertDateToDisplay($i['wo_task_upload_timestamp']));
                $r1_gps  = htmlspecialchars($i['wo_task_upload_longitude'] . ', ' . $i['wo_task_upload_latitude']);
            }
            if (!empty($imgResponse[1])) {
                $i = $imgResponse[1];
                $r2_img = $i['upload_folder'] . '/' . $i['upload_filename'] . '.' . $i['upload_extension'];
                $r2_desc = htmlspecialchars($this->fn_general->clear_null($i['wo_task_upload_desc']));
                $r2_ts   = htmlspecialchars($this->fn_general->convertDateToDisplay($i['wo_task_upload_timestamp']));
                $r2_gps  = htmlspecialchars($i['wo_task_upload_longitude'] . ', ' . $i['wo_task_upload_latitude']);
            }
            if (!empty($imgResponse[2])) {
                $i = $imgResponse[2];
                $r3_img = $i['upload_folder'] . '/' . $i['upload_filename'] . '.' . $i['upload_extension'];
                $r3_desc = htmlspecialchars($this->fn_general->clear_null($i['wo_task_upload_desc']));
                $r3_ts   = htmlspecialchars($this->fn_general->convertDateToDisplay($i['wo_task_upload_timestamp']));
                $r3_gps  = htmlspecialchars($i['wo_task_upload_longitude'] . ', ' . $i['wo_task_upload_latitude']);
            }

            // 8) Prepare WO‐A fields
            $woNumber   = 'WO' . str_pad($this->woTaskId, 12, '0', STR_PAD_LEFT);
            $woStatus   = (!empty($woTask['wo_task_time_executed'])) ? 'Completed' : 'Open';
            $locName    = htmlspecialchars($arrSiteName[intval($woTask['site_id'])]);
            $locCode    = "[System Generated]";
            $assetName  = "[Select from System]";
            $assetCode  = "[System Generated]";
            $woSeverity = htmlspecialchars($arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))]);
            $woDueTime  = '';
            if (!empty($woTask['wo_task_time_assigned']) && !empty($woTask['wo_task_severity'])) {
                $assignedDt = new DateTime($woTask['wo_task_time_assigned']);
                $dueDt = clone $assignedDt;
                $dueDt->modify('+' . $arrDue[intval($woTask['wo_task_severity'])] . ' hour');
                $woDueTime = htmlspecialchars($dueDt->format('d/m/Y g:i A'));
            }
            $complaintFromWR = htmlspecialchars($woTask['wo_task_complaint']); // complaint from WR

            // 9) Prepare WO‐B1 (Work Assignment)
            $receivedBy   = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_verified_by'] ?? 0)]);
            $assignedTo   = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_assigned_to'] ?? 0)]);
            $dateAssigned = '';
            if (!empty($woTask['wo_task_time_assigned'])) {
                $dateAssigned = htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_assigned']));
            }
            $issuerPhone  = htmlspecialchars($this->fn_general->clear_null($userProfile['user_contact_no']));

            // 10) Prepare WO‐B2 (Support Personnel)
            $woAssists = Class_db::getInstance()->db_select(
                'wo_task_assist',
                ['wo_task_id' => $this->woTaskId]
            );
            // We'll prepare up to 10 blank lines or actual names, whichever is greater
            $assistRows = [];
            if (!empty($woAssists)) {
                foreach ($woAssists as $idx => $assist) {
                    $assistRows[] = [
                        'no'   => $idx + 1,
                        'name' => htmlspecialchars($arrUserFullName[intval($assist['user_id'])])
                    ];
                }
            }
            // Fill up to 8 blank lines if fewer than 8 exist
            $maxAssistRows = 8;
            for ($i = count($assistRows); $i < $maxAssistRows; $i++) {
                $assistRows[] = ['no' => '', 'name' => ''];
            }

            // 11) Prepare WO‐C (Material Details)
            // We'll leave 5 blank rows for manual entry
            $materialRows = [];
            for ($i = 0; $i < 5; $i++) {
                $materialRows[] = [
                    'part'    => '',
                    'desc'    => '',
                    'issue'   => '',
                    'unit'    => '',
                    'qtyTake' => '',
                    'qtyRet'  => ''
                ];
            }

            // 12) Prepare WO‐D (Work Execution Details)
            $startDT_WO  = $woTask['wo_task_time_assigned']
                        ? htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_assigned']))
                        : "[System Generated]";
            $endDT_WO    = $woTask['wo_task_time_executed']
                        ? htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed']))
                        : "[System Generated]";
            $duration_WO = '';
            if (!empty($woTask['wo_task_time_assigned']) && !empty($woTask['wo_task_time_executed'])) {
                $duration_WO = htmlspecialchars(
                    $this->fn_general->timeDiff(
                        $woTask['wo_task_time_assigned'],
                        $woTask['wo_task_time_executed']
                    )
                );
            }
            $statusWO = '';
            if ($duration_WO !== '') {
                // Check if within SLA
                if (preg_match('/(\d+)\s*hour/i', $duration_WO, $m)) {
                    $hoursTaken = intval($m[1]);
                } else {
                    $hoursTaken = 0;
                }
                $allowedHours = intval($arrDue[intval($woTask['wo_task_severity'])]);
                $statusWO = ($hoursTaken <= $allowedHours) ? 'Within SLA' : 'Exceed SLA';
            }

            // 13) Prepare WO‐E (Work Completion & Verification)
            $servicedByName = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_fixed_by'] ?? 0)]);
            $servicedAt     = '';
            if (!empty($woTask['wo_task_time_executed'])) {
                $servicedAt = htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed']));
            }
            $checkedByName = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_verified_by'] ?? 0)]);
            $checkedAt     = '';
            if (!empty($woTask['wo_task_time_verified'])) {
                $checkedAt = htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified']));
            }
            $verifiedByName = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_verified_by'] ?? 0)]);
            $verifiedAt     = $checkedAt; // same timestamp for simplicity
            $ratingTxt      = '';
            if (!empty($woTask['wo_task_rate'])) {
                $ratingTxt = htmlspecialchars($woTask['wo_task_rate'] . ' / 5');
            }

            // 14) Prepare J1, J2, J3 (Photo Documentation)
            // We will show up to 1 image for Before/After, up to 3 for During
            // If fewer, leave blank cell. We also show description, date/time, GPS below each image cell.

            // Helper function: draw up to N images in a row
            // returns an array of cell arrays: [ [ 'img'=>'path', 'desc'=>'', 'ts'=>'', 'gps'=>'' ], ... ]
            function prepareStageImages($arr, $slots) {
                $out = [];
                for ($i = 0; $i < $slots; $i++) {
                    if (!empty($arr[$i])) {
                        $img  = $arr[$i];
                        $path = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                        $desc = htmlspecialchars((new Class_general())->clear_null($img['wo_task_upload_desc']));
                        $ts   = htmlspecialchars((new Class_general())->convertDateToDisplay($img['wo_task_upload_timestamp']));
                        $gps  = htmlspecialchars($img['wo_task_upload_longitude'] . ', ' . $img['wo_task_upload_latitude']);
                        $out[] = ['img'=>$path, 'desc'=>$desc, 'ts'=>$ts, 'gps'=>$gps];
                    } else {
                        $out[] = ['img'=>'', 'desc'=>'', 'ts'=>'', 'gps'=>''];
                    }
                }
                return $out;
            }

            $beforeImgs = prepareStageImages($imgBefore, 1);
            $duringImgs = prepareStageImages($imgDuring, 3);
            $afterImgs  = prepareStageImages($imgAfter, 1);


            //
            // === 15) START TCPDF ===
            //
            $pdf = new MYPDF_wo(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Generated by Class_pdf_wo');
            $pdf->SetTitle('GEMS 2.0 WR & WO');
            $pdf->SetSubject('GEMS 2.0 WR & WO');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(true);
            // Set fonts
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetFont('helvetica', '', 10);
            // Margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            $pdf->AddPage();

            // Define some color shortcuts
            $darkBlue = [0, 82, 155];
            $teal     = [0, 150, 136];
            $lightGray = [224, 224, 224];

            // Calculate content width (page width minus margins)
            $pageWidth = $pdf->getPageWidth();
            $margL = PDF_MARGIN_LEFT;
            $margR = PDF_MARGIN_RIGHT;
            $contentWidth = $pageWidth - $margL - $margR; // e.g. ~180mm

            // Define column widths (all in mm)
            // we'll use a 5% label column = contentWidth*0.05 ≈ 9mm if contentWidth=180
            // Round to nearest .1mm for convenience
            $labelW = round($contentWidth * 0.05, 1);
            $restW  = $contentWidth - $labelW; // ~171mm

            // For 2‐column rows: each cell = restW/2
            $twoW   = round($restW / 2, 1);

            // For 3‐column rows: each cell = restW/3
            $threeW = round($restW / 3, 1);

            // For 6‐column rows (material): each = restW/6
            $sixW = round($restW / 6, 1);

            //
            // --- HEADER BAR: "WORK REQUEST (WR) & WORK ORDER (WO)" centered on dark blue ---
            //
            $pdf->SetFillColor($darkBlue[0], $darkBlue[1], $darkBlue[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 12);
            // One big cell across contentWidth
            $pdf->Cell($contentWidth, 8, 'WORK REQUEST (WR) & WORK ORDER (WO)', 0, 1, 'C', 1);
            $pdf->Ln(2);

            // Reset text color for next cells
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 10);

            //
            // === WR – SECTION A: Complaint Details ===
            //
            // 1st row: teal background with "A" label + section title
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            // "A" label
            $pdf->Cell($labelW, 8, 'A', 1, 0, 'C', 1);
            // Section title (restW wide)
            $pdf->Cell($restW, 8, 'Complaint Details [User Details: Public & Client for Complaints or Internal: for Self-Finding]', 1, 1, 'L', 1);

            // 2nd row: "Reported by" / "Phone No"
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 9);
            // "Reported by:"
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0); // empty cell under "A"
            $pdf->Cell($twoW, 7, 'Reported by:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $reportedBy, 1, 0, 'L', 0);

            $pdf->Cell($twoW, 7, 'Phone No:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $reportedPhone, 1, 1, 'L', 0);

            // 3rd row: "Email" / "Reported Date / Time"
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0); // empty under "A"
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->Cell($twoW, 7, 'Email:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $reportedEmail, 1, 0, 'L', 0);

            $pdf->Cell($twoW, 7, 'Reported Date / Time:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $reportedDtTxt, 1, 1, 'L', 0);

            // 4th row: "Category" / "Severity"
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Category:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $categoryTxt, 1, 0, 'L', 0);

            $pdf->Cell($twoW, 7, 'Severity:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $severityTxt, 1, 1, 'L', 0);

            // 5th row: "Work Request No" / "Location Complaint"
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Work Request No:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $wr_no, 1, 0, 'L', 0);

            $pdf->Cell($twoW, 7, 'Location Complaint:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $locationTxt, 1, 1, 'L', 0);

            //
            // === WR – SECTION B1: Description of Complaint ===
            //
            $pdf->Ln(2);
            // Teal header B1
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'B1', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Description of Complaint [Manual Entry]', 1, 1, 'L', 1);

            // The big blank description box (40 mm high)
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell($labelW, 40, '', 1, 0, 'C', 0);
            $pdf->MultiCell($restW, 40, $complaintTxt, 1, 'L', 0, 1, '', '', true);

            //
            // === WR – SECTION B2: Complaint Images ===
            //
            $pdf->Ln(2);
            // Teal header B2
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'B2', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 8, 'Complaint Images [Complain from User]', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 8, '', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 8, '', 1, 1, 'C', 1);

            // Next row: column headers "Image 1 / Image 2 / Image 3"
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Image 1', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 7, 'Image 2', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 7, 'Image 3', 1, 1, 'C', 1);

            // Next row: image placeholders (50mm high)
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Cell($labelW, 50, '', 1, 0, 'C', 0);
            // Image 1 placeholder
            $pdf->Cell($threeW, 50, '', 1, 0, 'C', 0);
            // Image 2 placeholder
            $pdf->Cell($threeW, 50, '', 1, 0, 'C', 0);
            // Image 3 placeholder
            $pdf->Cell($threeW, 50, '', 1, 1, 'C', 0);

            // Finally, descriptions under images
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);

            // Image 1 desc
            $pdf->Cell($threeW, 7, 'Description: [Manual Entry]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Description: [Manual Entry]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Description: [Manual Entry]', 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Date / Time Taken: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Date / Time Taken: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Date / Time Taken: [System Generated]', 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'GPS Coordinates: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'GPS Coordinates: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'GPS Coordinates: [System Generated]', 1, 1, 'L', 0);

            //
            // === WR – SECTION C1: Work Assessment Details ===
            //
            $pdf->Ln(2);
            // Teal header C1
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'C1', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Work Assessment Details [Selected by P.I.C. to verify the complaint]', 1, 1, 'L', 1);

            // Data rows: Person in Charge / SLA Respond Time / WR Due Date /Time /Respond Status
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Person in Charge:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $picName, 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'SLA Respond Time:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $arrSla[intval($woTask['wo_task_severity'])], 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Email:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $picEmail, 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'WR Due Date / Time:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $wrDueTime, 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Respond Date / Duration:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $assignTime . ', ' . $respondDuration, 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'Respond Status:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $respondStatus, 1, 1, 'L', 0);

            //
            // === WR – SECTION C2: Response Images ===
            //
            $pdf->Ln(2);
            // Teal header C2
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'C2', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 8, 'Response Images [P.I.C. verification of the complaint]', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 8, '', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 8, '', 1, 1, 'C', 1);

            // Headers: Image 1/2/3
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Image 1', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 7, 'Image 2', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 7, 'Image 3', 1, 1, 'C', 1);

            // Images row (50mm tall)
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Cell($labelW, 50, '', 1, 0, 'C', 0);
            $pdf->Cell($threeW, 50, '', 1, 0, 'C', 0);
            $pdf->Cell($threeW, 50, '', 1, 0, 'C', 0);
            $pdf->Cell($threeW, 50, '', 1, 1, 'C', 0);

            // Description row
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Description: [Manual Entry]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Description: [Manual Entry]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Description: [Manual Entry]', 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Date / Time Taken: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Date / Time Taken: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Date / Time Taken: [System Generated]', 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Longitude/ Latitude: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Longitude/ Latitude: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Longitude/ Latitude: [System Generated]', 1, 1, 'L', 0);

            //
            // === WR – SECTION D1: Validation Details ===
            //
            $pdf->Ln(2);
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'D1', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Validation Details [Who issues/ assigns the WR to P.I.C.]', 1, 1, 'L', 1);

            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);

            // Row: Validation by / Designation
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Validation by:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $arrUserFullName[intval($woTask['wo_task_verified_by'] ?? 0)], 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'Designation:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, 'Head of Department [System Generated]', 1, 1, 'L', 0);

            // Row: Verified Date / Work Request Status
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Verified Date:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified'])), 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'Work Request Status:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, 'Accept/Reject [System Generated]', 1, 1, 'L', 0);

            //
            // === WR – SECTION D2: Remark Details ===
            //
            $pdf->Ln(2);
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'D2', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Remark Details [Manual Entry]', 1, 1, 'L', 1);

            // Blank 40mm high for remarks
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell($labelW, 40, '', 1, 0, 'C', 0);
            $pdf->Cell($restW, 40, '', 1, 1, 'L', 0);

            //
            // === WO HEADER BAR ===
            //
            $pdf->Ln(2);
            $pdf->SetFillColor($darkBlue[0], $darkBlue[1], $darkBlue[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell($contentWidth, 8, 'WORK ORDER (WO)', 0, 1, 'C', 1);
            $pdf->Ln(2);

            //
            // === WO – SECTION A: Work Order Details ===
            //
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'A', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Work Order Details', 1, 1, 'L', 1);

            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 9);

            // Row 1: Work Order No / Status
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Work Order No:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $woNumber, 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'Status:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $woStatus, 1, 1, 'L', 0);

            // Row 2: Work Request No / Category
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Work Request No:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $wr_no, 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'Category:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $categoryTxt, 1, 1, 'L', 0);

            // Row 3: Location Name / Location Code
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Location Name:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $locName, 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'Location Code:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $locCode, 1, 1, 'L', 0);

            // Row 4: Asset Name / Asset Code
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Asset Name:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $assetName, 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'Asset Code:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $assetCode, 1, 1, 'L', 0);

            // Row 5: Severity / WO Due Date/Time
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Severity:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $woSeverity, 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'WO Due Date/Time:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $woDueTime, 1, 1, 'L', 0);

            // Row 6: Complaint Description (full width)
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, 7, 'Complaint Description:', 1, 1, 'L', 1);

            $pdf->Cell($labelW, 20, '', 1, 0, 'C', 0);
            $pdf->MultiCell($restW, 20, $complaintFromWR, 1, 'L', 0, 1, '', '', true);

            //
            // === WO – SECTION B1: Work Assignment Details ===
            //
            $pdf->Ln(2);
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'B1', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Work Assignment Details [Details of task issuer and receiver]', 1, 1, 'L', 1);

            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);

            // Row: Received By / Assigned To
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Received By:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $receivedBy, 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'Assigned To:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $assignedTo, 1, 1, 'L', 0);

            // Row: Date Assigned / Phone No
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($twoW, 7, 'Date Assigned:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $dateAssigned, 1, 0, 'L', 0);
            $pdf->Cell($twoW, 7, 'Phone No:', 1, 0, 'L', 1);
            $pdf->Cell($twoW, 7, $issuerPhone, 1, 1, 'L', 0);

            //
            // === WO – SECTION B2: Support Personnel ===
            //
            $pdf->Ln(2);
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'B2', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Support Personnel [Team members involved in execution]', 1, 1, 'L', 1);

            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);

            // Header row: No. / Name
            $pdf->Cell($labelW, 7, 'No.', 1, 0, 'C', 1);
            $pdf->Cell($restW, 7, 'Name', 1, 1, 'C', 1);

            // Each assist row
            foreach ($assistRows as $ar) {
                $pdf->Cell($labelW, 7, $ar['no'], 1, 0, 'C', 0);
                $pdf->Cell($restW, 7, $ar['name'], 1, 1, 'L', 0);
            }

            //
            // === WO – SECTION C: Material Details ===
            //
            $pdf->Ln(2);
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'C', 1, 0, 'C', 1);
            $pdf->Cell($sixW, 8, 'Material Details [Parts or materials issued / returned]', 1, 1, 'L', 1);

            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);

            // Header row: Part No / Item Description / Issue Type (D/I) / Unit / Qty Taken / Qty Return
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($sixW, 7, 'Part No.', 1, 0, 'C', 1);
            $pdf->Cell($sixW, 7, 'Item Description', 1, 0, 'C', 1);
            $pdf->Cell($sixW, 7, 'Issue Type (D/I)', 1, 0, 'C', 1);
            $pdf->Cell($sixW, 7, 'Unit', 1, 0, 'C', 1);
            $pdf->Cell($sixW, 7, 'Quantity Taken', 1, 0, 'C', 1);
            $pdf->Cell($sixW, 7, 'Quantity Return', 1, 1, 'C', 1);

            // Blank material rows
            foreach ($materialRows as $mr) {
                $pdf->Cell($labelW, 7, '', 1, 0, 'C', 0);
                $pdf->Cell($sixW, 7, $mr['part'],    1, 0, 'L', 0);
                $pdf->Cell($sixW, 7, $mr['desc'],    1, 0, 'L', 0);
                $pdf->Cell($sixW, 7, $mr['issue'],   1, 0, 'L', 0);
                $pdf->Cell($sixW, 7, $mr['unit'],    1, 0, 'L', 0);
                $pdf->Cell($sixW, 7, $mr['qtyTake'], 1, 0, 'C', 0);
                $pdf->Cell($sixW, 7, $mr['qtyRet'],  1, 1, 'C', 0);
            }

            //
            // === WO – SECTION D: Work Execution Details ===
            //
            $pdf->Ln(2);
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'D', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Work Execution Details [Action duration, task notes, work timeline]', 1, 1, 'L', 1);

            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);

            // Blank notes row (20mm high)
            $pdf->Cell($labelW, 20, '', 1, 0, 'C', 0);
            $pdf->Cell($restW, 20, '[Manual Entry]', 1, 1, 'L', 0);

            // Row: Start Date & Time / End Date & Time / Duration / Status
            // We'll split the restW into three equal columns: restW/3 each.
            $thirdW = round($restW / 3, 1);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($thirdW, 7, 'Start Date & Time:', 1, 0, 'L', 1);
            $pdf->Cell($thirdW, 7, $startDT_WO, 1, 0, 'L', 0);
            $pdf->Cell($thirdW, 7, 'End Date & Time:', 1, 0, 'L', 1);
            $pdf->Cell($thirdW, 7, $endDT_WO, 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($thirdW, 7, 'Duration:', 1, 0, 'L', 1);
            $pdf->Cell($thirdW, 7, $duration_WO, 1, 0, 'L', 0);
            $pdf->Cell($thirdW, 7, 'Status:', 1, 0, 'L', 1);
            $pdf->Cell($thirdW, 7, $statusWO, 1, 1, 'L', 0);

            //
            // === WO – SECTION E: Work Completion & Verification ===
            //
            $pdf->Ln(2);
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'E', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Work Completion & Verification [Sign‐off & satisfaction rating]', 1, 1, 'L', 1);

            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);

            // Three side‐by‐side signature boxes (each restW/3 wide, height=50)
            $pdf->Cell($labelW, 50, '', 0, 0, 'C', 0); // under "E"
            $sigW = round($restW / 3, 1);

            // Serviced By
            $pdf->Cell($sigW, 50, '', 1, 0, 'L', 0);
            // Checked By
            $pdf->Cell($sigW, 50, '', 1, 0, 'L', 0);
            // Verified By
            $pdf->Cell($sigW, 50, '', 1, 1, 'L', 0);

            // Now put the labels and names underneath each box
            // We'll write over the blank boxes by re‐positioning the cursor manually

            // Serviced By text
            $y = $pdf->GetY() - 50; // top of the signature boxes
            $x = PDF_MARGIN_LEFT + $labelW;
            $pdf->SetXY($x, $y + 2);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($sigW, 5, 'Serviced By:', 0, 1, 'L', 0);
            $pdf->Cell($sigW, 5, 'Name: ' . $servicedByName, 0, 1, 'L', 0);
            $pdf->Cell($sigW, 5, 'Date / Time: ' . $servicedAt, 0, 1, 'L', 0);

            // Checked By text
            $x = PDF_MARGIN_LEFT + $labelW + $sigW;
            $pdf->SetXY($x, $y + 2);
            $pdf->Cell($sigW, 5, 'Checked By:', 0, 1, 'L', 0);
            $pdf->Cell($sigW, 5, 'Name: ' . $checkedByName, 0, 1, 'L', 0);
            $pdf->Cell($sigW, 5, 'Date / Time: ' . $checkedAt, 0, 1, 'L', 0);

            // Verified By text
            $x = PDF_MARGIN_LEFT + $labelW + 2*$sigW;
            $pdf->SetXY($x, $y + 2);
            $pdf->Cell($sigW, 5, 'Verified By:', 0, 1, 'L', 0);
            $pdf->Cell($sigW, 5, 'Name: ' . $verifiedByName, 0, 1, 'L', 0);
            $pdf->Cell($sigW, 5, 'Date / Time: ' . $verifiedAt, 0, 1, 'L', 0);

            // Satisfactory rating row (one row spanning all three signature columns)
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, 7, 'Satisfactory Level: [Choose]', 1, 1, 'L', 0);

            // Now draw the tick‐boxes 1…5
            $boxW = round($restW / 5, 1);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            for ($i = 1; $i <= 5; $i++) {
                $pdf->Cell($boxW, 7, $i . ' [ ]', 1, 0, 'C', 0);
            }
            $pdf->Ln(10);

            //
            // === WO – SECTION J1: Photo Documentation (Before) ===
            //
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'J', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Photo Documentation (Before) [Visual proof for each repair stage]', 1, 1, 'L', 1);

            // Header row: "Image 1"
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, 7, 'Image 1', 1, 1, 'C', 1);

            // Image placeholder (height=50)
            $pdf->Cell($labelW, 50, '', 1, 0, 'C', 0);
            $pdf->Cell($restW, 50, '', 1, 1, 'C', 0);

            // Description row
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, 7, 'Description: [Manual Entry]', 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, 7, 'Date / Time Taken: [System Generated]', 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, 7, 'Longitude/ Latitude: [System Generated]', 1, 1, 'L', 0);

            //
            // === WO – SECTION J2: Photo Documentation (During) ===
            //
            $pdf->Ln(2);
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'J', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Photo Documentation (During) [Visual proof for each repair stage]', 1, 1, 'L', 1);

            // Headers: Image 1 / Image 2 / Image 3
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Image 1', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 7, 'Image 2', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 7, 'Image 3', 1, 1, 'C', 1);

            // Images row (50mm tall)
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Cell($labelW, 50, '', 1, 0, 'C', 0);
            $pdf->Cell($threeW, 50, '', 1, 0, 'C', 0);
            $pdf->Cell($threeW, 50, '', 1, 0, 'C', 0);
            $pdf->Cell($threeW, 50, '', 1, 1, 'C', 0);

            // Description row
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Description: [Manual Entry]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Description: [Manual Entry]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Description: [Manual Entry]', 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Date / Time Taken: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Date / Time Taken: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Date / Time Taken: [System Generated]', 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Longitude/ Latitude: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Longitude/ Latitude: [System Generated]', 1, 0, 'L', 0);
            $pdf->Cell($threeW, 7, 'Longitude/ Latitude: [System Generated]', 1, 1, 'L', 0);

            //
            // === WO – SECTION J3: Photo Documentation (After) ===
            //
            $pdf->Ln(2);
            $pdf->SetFillColor($teal[0], $teal[1], $teal[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell($labelW, 8, 'J', 1, 0, 'C', 1);
            $pdf->Cell($restW, 8, 'Photo Documentation (After) [Visual proof for each repair stage]', 1, 1, 'L', 1);

            // Headers: "Image 1"
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, 7, 'Image 1', 1, 1, 'C', 1);

            // Image placeholder (50mm tall)
            $pdf->Cell($labelW, 50, '', 1, 0, 'C', 0);
            $pdf->Cell($restW, 50, '', 1, 1, 'C', 0);

            // Description rows
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, 7, 'Description: [Manual Entry]', 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, 7, 'Date / Time Taken: [System Generated]', 1, 1, 'L', 0);

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, 7, 'Longitude/ Latitude: [System Generated]', 1, 1, 'L', 0);

            $pdf->Ln(5);

            // 16) Save the PDF file to disk
            $folder_code = floor(intval($this->woTaskId) / 1000);
            $folder = 'pdf/wo/' . $folder_code;
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            $filename = 'wo_' . str_pad($this->woTaskId, 12, '0', STR_PAD_LEFT) . '.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Saving PDF as: ' . $filename);

            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];

            if ($environment === 'windows') {
                $filename_src = '\wo\\' . $folder_code . '\\' . $filename;
            } else {
                $filename_src = '/wo/' . $folder_code . '/' . $filename;
            }

            $pdf->Output(dirname(__FILE__) . $filename_src, 'F');

            // 17) Update sys_pdf and wo_task tables
            $pdfId = $woTask['pdf_id'];
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_select_col(
                    'sys_pdf',
                    [
                        'pdf_filename' => $filename,
                        'pdf_status'   => '1'
                    ],
                    'pdf_id'
                );
            }
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_insert(
                    'sys_pdf',
                    [
                        'pdf_filename' => $filename,
                        'pdf_type'     => 'wo',
                        'pdf_folder'   => $folder
                    ]
                );
            } else {
                Class_db::getInstance()->db_update(
                    'sys_pdf',
                    [
                        'pdf_filename'    => $filename,
                        'pdf_type'        => 'wo',
                        'pdf_folder'      => $folder,
                        'pdf_timeCreated' => 'Now()'
                    ],
                    ['pdf_id' => $pdfId]
                );
            }
            Class_db::getInstance()->db_update(
                'wo_task',
                ['pdf_id' => $pdfId, 'wo_task_is_pdf' => '0'],
                ['wo_task_id' => $this->woTaskId]
            );

            return [
                'pdfId'    => $pdfId,
                'woTaskNo' => $woTask['wo_task_no']
            ];
        } 
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
