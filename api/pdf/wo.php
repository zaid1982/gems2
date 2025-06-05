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
        // Assume Class_general is properly initialized or autoloaded
        $this->fn_general = new Class_general();
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
     * - Queries all WR + WO data from DB.
     * - Builds a native‐TCPDF table layout (Cells/MultiCells) with the correct
     * colors, fonts, borders, and column widths.
     * - Saves the PDF to disk and updates sys_pdf + wo_task table.
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

            // Get the base path for uploads, assuming it's configured
            // This might need adjustment based on your actual file structure and configuration
            $config = parse_ini_file('library/config.ini');
            $uploadBasePath = $config['upload_base_path'] ?? ''; // Adjust as per your config

            foreach ($woUploadsAll as $upl) {
                $full_path = $uploadBasePath . $upl['upload_folder'] . '/' . $upl['upload_filename'] . '.' . $upl['upload_extension'];
                if (!file_exists($full_path)) {
                    // Log or handle missing file, e.g., use a placeholder
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Missing upload file: ' . $full_path);
                    continue; // Skip this upload if file does not exist
                }

                switch ($upl['wo_task_upload_type']) {
                    case '1':
                        $upl['full_path'] = $full_path;
                        $imgComplaint[] = $upl;
                        break;
                    case '2':
                        $upl['full_path'] = $full_path;
                        $imgBefore[] = $upl;
                        break;
                    case '3':
                        $upl['full_path'] = $full_path;
                        $imgDuring[] = $upl;
                        break;
                    case '4':
                        $upl['full_path'] = $full_path;
                        $imgAfter[] = $upl;
                        break;
                    case '5':
                        $upl['full_path'] = $full_path;
                        $imgResponse[] = $upl;
                        break;
                    case '7':
                        if (!$signService && $upl['upload_extension'] === 'png') {
                            $signService = $full_path;
                        }
                        break;
                    case '8':
                        if (!$signVerify && $upl['upload_extension'] === 'png') {
                            $signVerify = $full_path;
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

            // 7) Prepare C2 (Response Images) descriptions - using the prepared $imgResponse array
            // The actual data extraction will now happen directly when rendering as we iterate over $imgResponse
            // This preparation step might be less critical now that we're passing the full array directly
            // to the rendering logic. Keeping it for consistency if needed elsewhere.


            // 8) Prepare WO‐A fields
            $woNumber   = 'WO' . str_pad($this->woTaskId, 12, '0', STR_PAD_LEFT);
            $woStatus   = (!empty($woTask['wo_task_time_executed'])) ? 'Completed' : 'Open';
            $locName    = htmlspecialchars($arrSiteName[intval($woTask['site_id'])]);
            $locCode    = "[System Generated]"; // As per template, this is static
            $assetName  = "[Select from System]"; // As per template, this is static
            $assetCode  = "[System Generated]"; // As per template, this is static
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
                // This logic is simplified; timeDiff should return consistent format or actual hours
                // Assuming timeDiff returns "X hours Y min" or similar. Need to parse hours correctly.
                preg_match('/(\d+)\s*hour/i', $duration_WO, $m_hours);
                $hoursTaken = isset($m_hours[1]) ? intval($m_hours[1]) : 0;

                // Also parse minutes if needed for more precise check
                preg_match('/(\d+)\s*min/i', $duration_WO, $m_min);
                $minutesTaken = isset($m_min[1]) ? intval($m_min[1]) : 0;
                $totalMinutesTaken = $hoursTaken * 60 + $minutesTaken;

                $allowedHours = intval($arrDue[intval($woTask['wo_task_severity'])]);
                $allowedMinutes = $allowedHours * 60;

                $statusWO = ($totalMinutesTaken <= $allowedMinutes) ? 'Within SLA' : 'Exceed SLA';
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
            $verifiedByName = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_verified_by'] ?? 0)]); // Assuming verified by is same as checked by for simplicity
            $verifiedAt     = $checkedAt; // same timestamp for simplicity
            $ratingTxt      = '';
            $rating_value = intval($woTask['wo_task_rate'] ?? 0);
            if (!empty($rating_value)) {
                $ratingTxt = htmlspecialchars($rating_value . ' / 5');
            }


            // 14) Prepare J1, J2, J3 (Photo Documentation) - using the prepared arrays

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
            $pdf->Cell($threeW, 8, '', 1, 0, 'C', 1); // Empty header for Image 2
            $pdf->Cell($threeW, 8, '', 1, 1, 'C', 1); // Empty header for Image 3

            // Next row: column headers "Image 1 / Image 2 / Image 3"
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Image 1', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 7, 'Image 2', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 7, 'Image 3', 1, 1, 'C', 1);

            // Image placeholders (50mm high) and descriptions below
            $imageHeight = 50;
            $descLineHeight = 7; // Height for each description line

            // Store starting Y for this row of images
            $y_start_images = $pdf->GetY();

            // Set current X for the first image column (after labelW)
            $x_current_col = PDF_MARGIN_LEFT + $labelW;

            // Draw and potentially place image for Column 1
            $pdf->Rect($x_current_col, $y_start_images, $threeW, $imageHeight); // Draw image box
            if (!empty($imgComplaint[0]['full_path']) && file_exists($imgComplaint[0]['full_path'])) {
                $pdf->Image($imgComplaint[0]['full_path'], $x_current_col + 1, $y_start_images + 1, $threeW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Draw and potentially place image for Column 2
            $x_current_col += $threeW;
            $pdf->Rect($x_current_col, $y_start_images, $threeW, $imageHeight); // Draw image box
            if (!empty($imgComplaint[1]['full_path']) && file_exists($imgComplaint[1]['full_path'])) {
                $pdf->Image($imgComplaint[1]['full_path'], $x_current_col + 1, $y_start_images + 1, $threeW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Draw and potentially place image for Column 3
            $x_current_col += $threeW;
            $pdf->Rect($x_current_col, $y_start_images, $threeW, $imageHeight); // Draw image box
            if (!empty($imgComplaint[2]['full_path']) && file_exists($imgComplaint[2]['full_path'])) {
                $pdf->Image($imgComplaint[2]['full_path'], $x_current_col + 1, $y_start_images + 1, $threeW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Now, position for the descriptions below the image boxes
            $pdf->SetY($y_start_images + $imageHeight);
            $pdf->SetFont('helvetica', '', 8); // Set font for image descriptions

            // Description row 1 (Description)
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0); // Empty cell under 'B2'
            $pdf->Cell($threeW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgComplaint[0]['wo_task_upload_desc'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgComplaint[1]['wo_task_upload_desc'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgComplaint[2]['wo_task_upload_desc'] ?? '')), 1, 1, 'L', 0);

            // Description row 2 (Date/Time)
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgComplaint[0]['wo_task_upload_timestamp'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgComplaint[1]['wo_task_upload_timestamp'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgComplaint[2]['wo_task_upload_timestamp'] ?? '')), 1, 1, 'L', 0);

            // Description row 3 (GPS)
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, $descLineHeight, 'GPS Coordinates: ' . htmlspecialchars(($imgComplaint[0]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgComplaint[0]['wo_task_upload_latitude'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'GPS Coordinates: ' . htmlspecialchars(($imgComplaint[1]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgComplaint[1]['wo_task_upload_latitude'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'GPS Coordinates: ' . htmlspecialchars(($imgComplaint[2]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgComplaint[2]['wo_task_upload_latitude'] ?? '')), 1, 1, 'L', 0);


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
            $pdf->Cell($threeW, 8, '', 1, 0, 'C', 1); // Empty header for Image 2
            $pdf->Cell($threeW, 8, '', 1, 1, 'C', 1); // Empty header for Image 3

            // Headers: Image 1/2/3
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, 7, 'Image 1', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 7, 'Image 2', 1, 0, 'C', 1);
            $pdf->Cell($threeW, 7, 'Image 3', 1, 1, 'C', 1);

            // Image placeholders (50mm tall) and descriptions below
            $imageHeight = 50;
            $descLineHeight = 7;

            $y_start_images = $pdf->GetY();

            // Set current X for the first image column (after labelW)
            $x_current_col = PDF_MARGIN_LEFT + $labelW;

            // Draw and potentially place image for Column 1
            $pdf->Rect($x_current_col, $y_start_images, $threeW, $imageHeight);
            if (!empty($imgResponse[0]['full_path']) && file_exists($imgResponse[0]['full_path'])) {
                $pdf->Image($imgResponse[0]['full_path'], $x_current_col + 1, $y_start_images + 1, $threeW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Draw and potentially place image for Column 2
            $x_current_col += $threeW;
            $pdf->Rect($x_current_col, $y_start_images, $threeW, $imageHeight);
            if (!empty($imgResponse[1]['full_path']) && file_exists($imgResponse[1]['full_path'])) {
                $pdf->Image($imgResponse[1]['full_path'], $x_current_col + 1, $y_start_images + 1, $threeW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Draw and potentially place image for Column 3
            $x_current_col += $threeW;
            $pdf->Rect($x_current_col, $y_start_images, $threeW, $imageHeight);
            if (!empty($imgResponse[2]['full_path']) && file_exists($imgResponse[2]['full_path'])) {
                $pdf->Image($imgResponse[2]['full_path'], $x_current_col + 1, $y_start_images + 1, $threeW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Now, position for the descriptions below the image boxes
            $pdf->SetY($y_start_images + $imageHeight);
            $pdf->SetFont('helvetica', '', 8);

            // Description row 1 (Description)
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgResponse[0]['wo_task_upload_desc'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgResponse[1]['wo_task_upload_desc'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgResponse[2]['wo_task_upload_desc'] ?? '')), 1, 1, 'L', 0);

            // Description row 2 (Date/Time)
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgResponse[0]['wo_task_upload_timestamp'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgResponse[1]['wo_task_upload_timestamp'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgResponse[2]['wo_task_upload_timestamp'] ?? '')), 1, 1, 'L', 0);

            // Description row 3 (GPS)
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Longitude/ Latitude: ' . htmlspecialchars(($imgResponse[0]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgResponse[0]['wo_task_upload_latitude'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Longitude/ Latitude: ' . htmlspecialchars(($imgResponse[1]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgResponse[1]['wo_task_upload_latitude'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Longitude/ Latitude: ' . htmlspecialchars(($imgResponse[2]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgResponse[2]['wo_task_upload_latitude'] ?? '')), 1, 1, 'L', 0);


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
            $pdf->MultiCell($restW, 40, htmlspecialchars($this->fn_general->clear_null($woTask['wo_task_remarks'])), 1, 'L', 0, 1, '', '', true);


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
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]); // Fill for label
            $pdf->Cell($restW, 7, 'Complaint Description:', 1, 1, 'L', 1);

            $pdf->SetFillColor(255, 255, 255); // Reset fill for content
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
            // This cell should span the restW width, not sixW
            $pdf->Cell($restW, 8, 'Material Details [Parts or materials issued / returned]', 1, 1, 'L', 1);


            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(0, 0, 0);

            // Header row: Part No / Item Description / Issue Type (D/I) / Unit / Qty Taken / Qty Return
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0); // Empty cell under 'C'
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
            $pdf->SetFillColor(255, 255, 255); // White background for manual entry box
            $pdf->SetTextColor(0, 0, 0);

            // Blank notes row (20mm high)
            $pdf->Cell($labelW, 20, '', 1, 0, 'C', 0);
            // Replace static '[Manual Entry]' with the actual notes if available, or keep blank if needed
            $pdf->MultiCell($restW, 20, htmlspecialchars($this->fn_general->clear_null($woTask['wo_task_notes'])), 1, 'L', 0, 1, '', '', true);


            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]); // Gray background for labels
            // Row: Start Date & Time / End Date & Time / Duration / Status
            // We'll split the restW into two label columns and two data columns.
            // (LabelW) (Label) (Data) (Label) (Data)
            $halfRestW = round($restW / 2, 1);
            $thirdRestW = round($restW / 3, 1); // Using 3 columns as per template visual.

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0); // Empty cell under D
            $pdf->Cell($thirdRestW, 7, 'Start Date & Time:', 1, 0, 'L', 1);
            $pdf->Cell($thirdRestW, 7, $startDT_WO, 1, 0, 'L', 0);
            $pdf->Cell($thirdRestW, 7, 'End Date & Time:', 1, 0, 'L', 1);
            $pdf->Cell($thirdRestW, 7, $endDT_WO, 1, 1, 'L', 0); // End of row

            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0); // Empty cell under D
            $pdf->Cell($thirdRestW, 7, 'Duration:', 1, 0, 'L', 1);
            $pdf->Cell($thirdRestW, 7, $duration_WO, 1, 0, 'L', 0);
            $pdf->Cell($thirdRestW, 7, 'Status:', 1, 0, 'L', 1);
            $pdf->Cell($thirdRestW, 7, $statusWO, 1, 1, 'L', 0); // End of row

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
            $sigW = round($restW / 3, 1);
            $sigBoxHeight = 50;
            $textLineHeight = 5; // Smaller height for text lines inside signature boxes

            $y_start_signatures = $pdf->GetY(); // Capture the starting Y for the signature row

            // Draw the outer cells for layout
            $pdf->Cell($labelW, $sigBoxHeight, '', 0, 0, 'C', 0); // empty cell under "E"
            $pdf->Cell($sigW, $sigBoxHeight, '', 1, 0, 'L', 0); // Serviced By Box
            $pdf->Cell($sigW, $sigBoxHeight, '', 1, 0, 'L', 0); // Checked By Box
            $pdf->Cell($sigW, $sigBoxHeight, '', 1, 1, 'L', 0); // Verified By Box

            // Now put the labels, names, dates, and signatures inside each box using SetXY
            $pdf->SetFont('helvetica', '', 8);

            // Serviced By
            $x_serviced = PDF_MARGIN_LEFT + $labelW;
            $pdf->SetXY($x_serviced, $y_start_signatures + 2);
            $pdf->Cell($sigW, $textLineHeight, 'Serviced By:', 0, 1, 'L', 0);
            $pdf->SetX($x_serviced); // Maintain X for next line
            $pdf->Cell($sigW, $textLineHeight, 'Name: ' . $servicedByName, 0, 1, 'L', 0);
            $pdf->SetX($x_serviced); // Maintain X
            $pdf->Cell($sigW, $textLineHeight, 'Date / Time: ' . $servicedAt, 0, 1, 'L', 0);
            // Place Serviced By signature image if exists
            if (!empty($signService) && file_exists($signService)) {
                $img_width = $sigW * 0.8; // 80% of cell width
                $img_height = $sigBoxHeight - ($textLineHeight * 3) - 4; // Remaining space minus padding
                if ($img_height > 0) { // Ensure height is positive
                    $img_x = $x_serviced + ($sigW - $img_width) / 2;
                    $img_y = $y_start_signatures + ($textLineHeight * 3) + ($sigBoxHeight - ($textLineHeight * 3) - $img_height) / 2;
                    $pdf->Image($signService, $img_x, $img_y, $img_width, $img_height, 'PNG', '', '', false, 300, '', false, false, 0, true, false, false);
                }
            }


            // Checked By
            $x_checked = PDF_MARGIN_LEFT + $labelW + $sigW;
            $pdf->SetXY($x_checked, $y_start_signatures + 2);
            $pdf->Cell($sigW, $textLineHeight, 'Checked By:', 0, 1, 'L', 0);
            $pdf->SetX($x_checked);
            $pdf->Cell($sigW, $textLineHeight, 'Name: ' . $checkedByName, 0, 1, 'L', 0);
            $pdf->SetX($x_checked);
            $pdf->Cell($sigW, $textLineHeight, 'Date / Time: ' . $checkedAt, 0, 1, 'L', 0);


            // Verified By
            $x_verified = PDF_MARGIN_LEFT + $labelW + 2 * $sigW;
            $pdf->SetXY($x_verified, $y_start_signatures + 2);
            $pdf->Cell($sigW, $textLineHeight, 'Verified By:', 0, 1, 'L', 0);
            $pdf->SetX($x_verified);
            $pdf->Cell($sigW, $textLineHeight, 'Name: ' . $verifiedByName, 0, 1, 'L', 0);
            $pdf->SetX($x_verified);
            $pdf->Cell($sigW, $textLineHeight, 'Date / Time: ' . $verifiedAt, 0, 1, 'L', 0);
            // Place Verified By signature image if exists
            if (!empty($signVerify) && file_exists($signVerify)) {
                $img_width = $sigW * 0.8;
                $img_height = $sigBoxHeight - ($textLineHeight * 3) - 4;
                if ($img_height > 0) {
                    $img_x = $x_verified + ($sigW - $img_width) / 2;
                    $img_y = $y_start_signatures + ($textLineHeight * 3) + ($sigBoxHeight - ($textLineHeight * 3) - $img_height) / 2;
                    $pdf->Image($signVerify, $img_x, $img_y, $img_width, $img_height, 'PNG', '', '', false, 300, '', false, false, 0, true, false, false);
                }
            }


            // After placing all signature texts and images, move the cursor to the end of the signature boxes
            $pdf->SetY($y_start_signatures + $sigBoxHeight);

            // Satisfactory rating row
            $pdf->Ln(2); // Small line break before rating
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]); // Gray background for label
            $pdf->Cell($restW, 7, 'Satisfactory Level: [Choose]', 1, 1, 'L', 1);

            // Now draw the tick‐boxes 1…5
            $boxW = round($restW / 5, 1);
            $pdf->Cell($labelW, 7, '', 0, 0, 'C', 0);
            $pdf->SetFillColor(255, 255, 255); // White background for checkboxes
            for ($i = 1; $i <= 5; $i++) {
                $checkbox_label = $i . ' [ ]';
                if ($rating_value === $i) {
                    $checkbox_label = $i . ' [X]'; // Mark selected rating
                }
                $pdf->Cell($boxW, 7, $checkbox_label, 1, 0, 'C', 0);
            }
            $pdf->Ln(7); // Move to the next line properly after the rating boxes


            //
            // === WO – SECTION J1: Photo Documentation (Before) ===
            //
            $pdf->Ln(2); // Small break for new section
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

            // Image placeholder (height=50) and descriptions below
            $imageHeight = 50;
            $descLineHeight = 7;

            $y_start_single_image = $pdf->GetY();

            $x_single_img = PDF_MARGIN_LEFT + $labelW;
            $pdf->Rect($x_single_img, $y_start_single_image, $restW, $imageHeight); // Draw image box
            if (!empty($imgBefore[0]['full_path']) && file_exists($imgBefore[0]['full_path'])) {
                $pdf->Image($imgBefore[0]['full_path'], $x_single_img + 1, $y_start_single_image + 1, $restW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Now, position for the descriptions below the image box
            $pdf->SetY($y_start_single_image + $imageHeight);
            $pdf->SetFont('helvetica', '', 8);

            // Description rows
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgBefore[0]['wo_task_upload_desc'] ?? '')), 1, 1, 'L', 0);

            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgBefore[0]['wo_task_upload_timestamp'] ?? '')), 1, 1, 'L', 0);

            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, $descLineHeight, 'Longitude/ Latitude: ' . htmlspecialchars(($imgBefore[0]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgBefore[0]['wo_task_upload_latitude'] ?? '')), 1, 1, 'L', 0);


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

            // Images row (50mm tall) and descriptions below
            $imageHeight = 50;
            $descLineHeight = 7;

            $y_start_images = $pdf->GetY();

            // Set current X for the first image column (after labelW)
            $x_current_col = PDF_MARGIN_LEFT + $labelW;

            // Image 1
            $pdf->Rect($x_current_col, $y_start_images, $threeW, $imageHeight);
            if (!empty($imgDuring[0]['full_path']) && file_exists($imgDuring[0]['full_path'])) {
                $pdf->Image($imgDuring[0]['full_path'], $x_current_col + 1, $y_start_images + 1, $threeW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Image 2
            $x_current_col += $threeW;
            $pdf->Rect($x_current_col, $y_start_images, $threeW, $imageHeight);
            if (!empty($imgDuring[1]['full_path']) && file_exists($imgDuring[1]['full_path'])) {
                $pdf->Image($imgDuring[1]['full_path'], $x_current_col + 1, $y_start_images + 1, $threeW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Image 3
            $x_current_col += $threeW;
            $pdf->Rect($x_current_col, $y_start_images, $threeW, $imageHeight);
            if (!empty($imgDuring[2]['full_path']) && file_exists($imgDuring[2]['full_path'])) {
                $pdf->Image($imgDuring[2]['full_path'], $x_current_col + 1, $y_start_images + 1, $threeW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Now, position for the descriptions below the image boxes
            $pdf->SetY($y_start_images + $imageHeight);
            $pdf->SetFont('helvetica', '', 8);

            // Description row 1
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgDuring[0]['wo_task_upload_desc'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgDuring[1]['wo_task_upload_desc'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgDuring[2]['wo_task_upload_desc'] ?? '')), 1, 1, 'L', 0);

            // Description row 2 (Date/Time)
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgDuring[0]['wo_task_upload_timestamp'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgDuring[1]['wo_task_upload_timestamp'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgDuring[2]['wo_task_upload_timestamp'] ?? '')), 1, 1, 'L', 0);

            // Description row 3 (GPS)
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Longitude/ Latitude: ' . htmlspecialchars(($imgDuring[0]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgDuring[0]['wo_task_upload_latitude'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Longitude/ Latitude: ' . htmlspecialchars(($imgDuring[1]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgDuring[1]['wo_task_upload_latitude'] ?? '')), 1, 0, 'L', 0);
            $pdf->Cell($threeW, $descLineHeight, 'Longitude/ Latitude: ' . htmlspecialchars(($imgDuring[2]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgDuring[2]['wo_task_upload_latitude'] ?? '')), 1, 1, 'L', 0);


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

            // Image placeholder (50mm tall) and descriptions below
            $imageHeight = 50;
            $descLineHeight = 7;

            $y_start_single_image = $pdf->GetY();

            $x_single_img = PDF_MARGIN_LEFT + $labelW;
            $pdf->Rect($x_single_img, $y_start_single_image, $restW, $imageHeight); // Draw image box
            if (!empty($imgAfter[0]['full_path']) && file_exists($imgAfter[0]['full_path'])) {
                $pdf->Image($imgAfter[0]['full_path'], $x_single_img + 1, $y_start_single_image + 1, $restW - 2, $imageHeight - 2, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }

            // Now, position for the descriptions below the image box
            $pdf->SetY($y_start_single_image + $imageHeight);
            $pdf->SetFont('helvetica', '', 8);

            // Description rows
            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, $descLineHeight, 'Description: ' . htmlspecialchars($this->fn_general->clear_null($imgAfter[0]['wo_task_upload_desc'] ?? '')), 1, 1, 'L', 0);

            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, $descLineHeight, 'Date / Time Taken: ' . htmlspecialchars($this->fn_general->convertDateToDisplay($imgAfter[0]['wo_task_upload_timestamp'] ?? '')), 1, 1, 'L', 0);

            $pdf->Cell($labelW, $descLineHeight, '', 0, 0, 'C', 0);
            $pdf->Cell($restW, $descLineHeight, 'Longitude/ Latitude: ' . htmlspecialchars(($imgAfter[0]['wo_task_upload_longitude'] ?? '') . ', ' . ($imgAfter[0]['wo_task_upload_latitude'] ?? '')), 1, 1, 'L', 0);

            $pdf->Ln(5);

            // 16) Save the PDF file to disk
            $folder_code = floor(intval($this->woTaskId) / 1000);
            $folder = 'pdf/wo/' . $folder_code;
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            $filename = 'wo_' . str_pad($this->woTaskId, 12, '0', STR_PAD_LEFT) . '.pdf';

            // Ensure the path is correct for output
            $outputDir = dirname(__FILE__) . '/../' . $folder; // Adjust this path if your 'pdf' directory is elsewhere relative to the script
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }
            $outputFilePath = $outputDir . '/' . $filename;

            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Saving PDF as: ' . $outputFilePath);


            $pdf->Output($outputFilePath, 'F');

            // 17) Update sys_pdf and wo_task tables
            // Path for database storage
            $filename_src = '/wo/' . $folder_code . '/' . $filename; // Ensure this is the correct path for database storage

            $pdfId = $woTask['pdf_id'];
            if (empty($pdfId)) {
                // Check if a record exists with the filename, assuming it might exist from a previous failed attempt
                $existingPdf = Class_db::getInstance()->db_select_single(
                    'sys_pdf',
                    [
                        'pdf_filename' => $filename,
                        'pdf_type'   => 'wo', // Added type for more specific check
                        'pdf_status'   => '1'
                    ],
                    'pdf_id',
                    1
                );
                if ($existingPdf) {
                    $pdfId = $existingPdf['pdf_id'];
                }
            }

            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_insert(
                    'sys_pdf',
                    [
                        'pdf_filename' => $filename,
                        'pdf_type'     => 'wo',
                        'pdf_folder'   => $folder_code, // Store just the folder code, not full path
                        'pdf_path'     => $filename_src // Store the full relative path for retrieval
                    ]
                );
            } else {
                Class_db::getInstance()->db_update(
                    'sys_pdf',
                    [
                        'pdf_filename'    => $filename,
                        'pdf_type'        => 'wo',
                        'pdf_folder'      => $folder_code,
                        'pdf_path'        => $filename_src,
                        'pdf_timeCreated' => 'Now()'
                    ],
                    ['pdf_id' => $pdfId]
                );
            }
            Class_db::getInstance()->db_update(
                'wo_task',
                ['pdf_id' => $pdfId, 'wo_task_is_pdf' => '1'], // Set to 1 upon successful PDF generation
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