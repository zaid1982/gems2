<?php
/**
 * Class MYPDF_wo
 * Override the TCPDF footer so that it prints "Page X of Y" on every page.
 */
class MYPDF_wo extends TCPDF {
    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Line(PDF_MARGIN_LEFT, $this->y, $this->getPageWidth() - PDF_MARGIN_RIGHT, $this->y);
        $pageNo = 'Page ' . strval($this->getAliasNumPage()) . ' of ' . $this->getAliasNbPages();
        $this->Cell(0, 6, $pageNo, 0, 0, 'R', 0);
    }
}

/**
 * Class Class_pdf_wo
 * 
 * Generates a PDF containing both:
 *  - Work Request (WR) section
 *  - Work Order   (WO) section
 * 
 * This version fixes:
 * 1) Text wrapping (no overflow; MultiCell measurement + drawing at computed height).
 * 2) Signature boxes aligned and same height.
 * 3) Uniform table row heights.
 * 4) Uses getPageWidth()/getPageHeight() rather than $pdf->w or $pdf->h.
 */
class Class_pdf_wo {
    private $fn_general;
    private $woTaskId;

    function __construct() {
    }

    // Magic getters/setters (same as before)
    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false) {
                $msg = substr($msg, $pos + 1);
            }
            return "(ErrCode:{$codes}) [" . __CLASS__ . ":{$function}:{$line}] - " . $msg;
        } else {
            return "(ErrCode:{$codes}) [" . __CLASS__ . ":{$function}:{$line}]";
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
     *   - Builds a single PDF with WR (Work Request) & WO (Work Order) sections.
     *   - Uses dynamic MultiCell measurement for wrapping and uniform row heights.
     *   - Signature blocks are aligned and consistent.
     *   - Returns an array with 'pdfId' and 'woTaskNo'.
     */
    public function create_pdf() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            // 1) Validate woTaskId
            if (empty($this->woTaskId)) {
                throw new Exception('[Line ' . __LINE__ . '] - Parameter woTaskId Empty');
            }

            // 2) Fetch WO task record
            $woTask = Class_db::getInstance()->db_select_single(
                'wo_task',
                ['wo_task_id' => $this->woTaskId],
                null,
                1
            );
            if (!$woTask) {
                throw new Exception('WO Task ID not found');
            }

            // 3) Fetch related data (user profiles, site, severity, category, etc.)
            $arrUserFullName = $this->fn_general->getUserFullName();      // array[ user_id => fullName ]
            $userProfile = Class_db::getInstance()->db_select_single(
                'sys_user_profile',
                [
                    'user_id' => $woTask['wo_task_created_by'],
                    'user_profile_status' => '1'
                ],
                null,
                1
            );

            $arrSiteName = $this->fn_general->getSiteName();              // array[ site_id => "Site Name" ]
            $arrCategory = ['', 'Complaint', 'Finding', 'Request', 'Breakdown', 'Defect', 'Public Complaint'];
            $arrSeverity = $this->fn_general->getSeverityName();          // array[ severity_id => "Non-Critical", "Critical", ... ]

            // 4) SLA / Due arrays based on client severity
            $clientId = Class_db::getInstance()->db_select_col(
                'cli_site',
                ['site_id' => $woTask['site_id']],
                'client_id',
                null,
                1
            );
            $arrSla = ['', '4 hours', '2 hours'];  // default
            $arrDue = ['', '4', '2'];              // default (hours)
            $arrClientSeverity = Class_db::getInstance()->db_select(
                'cli_client_severity',
                ['client_id' => $clientId]
            );
            foreach ($arrClientSeverity as $cs) {
                $key = intval($cs['severity_id']);
                $arrSla[$key] = $cs['client_severity_hour'] . ' hours';
                $arrDue[$key] = $cs['client_severity_hour'];
            }

            // 5) Fetch all uploads for this WO (we’ll separate them by type: 1➔Complaint, 2➔Before, 3➔During, 4➔After, 5➔Response, 7➔ServiceSign, 8➔VerifySign)
            $woUploadsAll = Class_db::getInstance()->db_select(
                'mw_wo_upload',
                [
                    'wo_task_id' => $this->woTaskId,
                    'sys_upload.upload_status' => '1'
                ]
            );
            // Categorize:
            $imgComplaint = [];
            $imgBefore = [];
            $imgDuring = [];
            $imgAfter = [];
            $imgResponse = [];
            $signService = null;
            $signVerify = null;
            foreach ($woUploadsAll as $upl) {
                switch ($upl['wo_task_upload_type']) {
                    case '1': // Complaint images
                        $imgComplaint[] = $upl;
                        break;
                    case '2': // Before repair
                        $imgBefore[] = $upl;
                        break;
                    case '3': // During repair
                        $imgDuring[] = $upl;
                        break;
                    case '4': // After repair
                        $imgAfter[] = $upl;
                        break;
                    case '5': // Response images (WR C2)
                        $imgResponse[] = $upl;
                        break;
                    case '7': // Service signature
                        if ($upl['upload_extension'] === 'png' && !$signService) {
                            $signService = $upl['upload_folder'] . '/' . $upl['upload_filename'] . '.' . $upl['upload_extension'];
                        }
                        break;
                    case '8': // Verify signature
                        if ($upl['upload_extension'] === 'png' && !$signVerify) {
                            $signVerify = $upl['upload_folder'] . '/' . $upl['upload_filename'] . '.' . $upl['upload_extension'];
                        }
                        break;
                    default:
                        // ignore other types
                        break;
                }
            }

            // 6) Start building the PDF
            $pdf = new MYPDF_wo(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Document metadata
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Muhammad Zaid');
            $pdf->SetTitle('GEMS 2.0 WR & WO');
            $pdf->SetSubject('GEMS 2.0 WR & WO');

            // No default header / Use custom footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(true);

            // Fonts for header/footer (even though header is disabled)
            $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
            $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

            // Margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // Auto page breaks
            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

            // Image scale ratio
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // Add first page
            $pdf->AddPage();

            // Recompute printable width/height
            $printableW = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
            $printableH = $pdf->getPageHeight() - PDF_MARGIN_TOP - PDF_MARGIN_BOTTOM;

            // === HEADER: “WORK REQUEST (WR) & WORK ORDER (WO)” ===
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'WORK REQUEST (WR) &', 0, 1, 'C');
            $pdf->Cell(0, 10, 'WORK ORDER (WO)', 0, 1, 'C');
            $pdf->Ln(2);

            // Draw a thin line
            $pdf->SetLineWidth(0.2);
            $pdf->Line(
                PDF_MARGIN_LEFT,
                $pdf->GetY(),
                $pdf->getPageWidth() - PDF_MARGIN_RIGHT,
                $pdf->GetY()
            );
            $pdf->Ln(6);

            // ---------------------------
            // 7) WORK REQUEST (WR) SECTION
            // ---------------------------
            //   A) Complaint Details
            //   B1) Description of Complaint
            //   B2) Complaint Images
            //   C1) Work Assessment Details
            //   C2) Response Images
            //   D1) Validation Details
            //   D2) Remark Details
            // ---------------------------

            // 7.A) Section A – “Complaint Details”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->SetTextColor(0);

            // A: Header row spanning full width, single row, height=8
            $pdf->Cell(8, 8, 'A', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Complaint Details [User Details: Public & Client for Complaints or Internal: for Self-Finding]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Now draw four rows inside Section A: each must wrap and use uniform height

            // Row A.1: “Reported by / Phone No”
            $lineH = 6;
            $colA1 = 30;
            $colA2 = ($printableW - 30) * 0.5; // half of the remaining width
            $colA3 = $printableW - $colA1 - $colA2; // the other half

            $textA1_1 = 'Reported by:';
            $textA1_2 = $arrUserFullName[intval($woTask['wo_task_created_by'])];
            $textA1_3 = 'Phone No:';
            $textA1_4 = $this->fn_general->clear_null($userProfile['user_contact_no']);

            // We will combine them into two “cells” for simplicity:
            //   [ (colA1 + colA2) ]   |   [colA3]
            // Left: “Reported by: <name>”
            // Right: “Phone No: <phone>”

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            // Measure left side
            $linesL = $pdf->MultiCell(
                $colA1 + $colA2,
                $lineH,
                $textA1_1 . ' ' . $textA1_2,
                0,
                'L',
                0,
                0
            );
            // Measure right side
            $linesR = $pdf->MultiCell(
                $colA3,
                $lineH,
                $textA1_3 . ' ' . $textA1_4,
                0,
                'L',
                0,
                0
            );
            $maxLn = max($linesL, $linesR);
            $reqH = ($maxLn * $lineH) + 2; // +2 padding

            // Draw final bordered cells
            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell(
                $colA1 + $colA2,
                $reqH,
                $textA1_1 . ' ' . $textA1_2,
                1,
                'L',
                0,
                0
            );
            $pdf->MultiCell(
                $colA3,
                $reqH,
                $textA1_3 . ' ' . $textA1_4,
                1,
                'L',
                0,
                0
            );
            $pdf->Ln();

            // Row A.2: “Email / Reported Date / Time”
            $textA2_1 = 'Email:';
            $textA2_2 = $this->fn_general->clear_null($userProfile['user_email']);
            $textA2_3 = 'Reported Date / Time:';
            $textA2_4 = $this->fn_general->convertDateToDisplay($woTask['wo_task_time_created']);

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();

            $linesL = $pdf->MultiCell(
                $colA1 + $colA2,
                $lineH,
                $textA2_1 . ' ' . $textA2_2,
                0,
                'L',
                0,
                0
            );
            $linesR = $pdf->MultiCell(
                $colA3,
                $lineH,
                $textA2_3 . ' ' . $textA2_4,
                0,
                'L',
                0,
                0
            );
            $maxLn = max($linesL, $linesR);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell(
                $colA1 + $colA2,
                $reqH,
                $textA2_1 . ' ' . $textA2_2,
                1,
                'L',
                0,
                0
            );
            $pdf->MultiCell(
                $colA3,
                $reqH,
                $textA2_3 . ' ' . $textA2_4,
                1,
                'L',
                0,
                0
            );
            $pdf->Ln();

            // Row A.3: “Category / Severity”
            $textA3_1 = 'Category:';
            $textA3_2 = $arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'], 0))];
            $textA3_3 = 'Severity:';
            $textA3_4 = $arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))];

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();

            $linesL = $pdf->MultiCell(
                $colA1 + $colA2,
                $lineH,
                $textA3_1 . ' ' . $textA3_2,
                0,
                'L',
                0,
                0
            );
            $linesR = $pdf->MultiCell(
                $colA3,
                $lineH,
                $textA3_3 . ' ' . $textA3_4,
                0,
                'L',
                0,
                0
            );
            $maxLn = max($linesL, $linesR);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell(
                $colA1 + $colA2,
                $reqH,
                $textA3_1 . ' ' . $textA3_2,
                1,
                'L',
                0,
                0
            );
            $pdf->MultiCell(
                $colA3,
                $reqH,
                $textA3_3 . ' ' . $textA3_4,
                1,
                'L',
                0,
                0
            );
            $pdf->Ln();

            // Row A.4: “Work Request No / Location Complaint”
            // We will build two columns: left= “Work Request No: WRGF…”, right= “Location Complaint: <lat,lon>”
            $wrNumber = 'WR' . substr('GFMHQ' . date('ymd'), 0, 9) . str_pad($this->woTaskId, 5, '0', STR_PAD_LEFT);
            $locText = $this->fn_general->clear_null($woTask['wo_task_location']);
            // If you store GPS coords separately, fetch them here. Using placeholder if blank:
            if (empty($locText)) {
                $locText = '[Manual Entry]';
            }

            $textA4_1 = 'Work Request No: ' . $wrNumber;
            $textA4_2 = 'Location Complaint: ' . $locText;

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();

            $linesL = $pdf->MultiCell(
                $colA1 + $colA2,
                $lineH,
                $textA4_1,
                0,
                'L',
                0,
                0
            );
            $linesR = $pdf->MultiCell(
                $colA3,
                $lineH,
                $textA4_2,
                0,
                'L',
                0,
                0
            );
            $maxLn = max($linesL, $linesR);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell(
                $colA1 + $colA2,
                $reqH,
                $textA4_1,
                1,
                'L',
                0,
                0
            );
            $pdf->MultiCell(
                $colA3,
                $reqH,
                $textA4_2,
                1,
                'L',
                0,
                0
            );
            $pdf->Ln(10);


            // 7.B1) Section B1 – “Description of Complaint [Manual Entry]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'B1', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Description of Complaint [Manual Entry]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Now draw one full-width dynamic‐height box (two columns: 8mm blank on left, rest on right)
            $pdf->SetFont('helvetica', '', 9);
            $lineH = 5;
            $colB1_left = 8;
            $colB1_right = $printableW - $colB1_left;

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();

            // We leave the content blank for manual entry
            $textB1_1 = '';
            $textB1_2 = '';

            $linesL = $pdf->MultiCell($colB1_left, $lineH, $textB1_1, 0, 'L', 0, 0);
            $linesR = $pdf->MultiCell($colB1_right, $lineH, $textB1_2, 0, 'L', 0, 0);
            $maxLn = max($linesL, $linesR);
            $reqH = ($maxLn * $lineH) + 8; // +8 for extra padding

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colB1_left, $reqH, $textB1_1, 1, 'L', 0, 0);
            $pdf->MultiCell($colB1_right, $reqH, $textB1_2, 1, 'L', 0, 0);
            $pdf->Ln(10);


            // 7.B2) Section B2 – “Complaint Images [Complain from User]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'B2', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Complaint Images [Complain from User]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // If there are complaint images, draw each in a row; otherwise draw one blank row
            $pdf->SetFont('helvetica', '', 9);
            $lineH = 5;
            $colC2_1 = 8;
            $colC2_2 = ($printableW - $colC2_1) * 0.48;   // ~48% for image
            $colC2_3 = ($printableW - $colC2_1) - $colC2_2; // remaining ~52% for description

            if (!empty($imgComplaint)) {
                foreach ($imgComplaint as $img) {
                    // Page break if near bottom
                    if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - 70)) {
                        $pdf->AddPage();
                    }

                    $imgPath = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                    $descHtml = "Description: " . $this->fn_general->clear_null($img['wo_task_upload_desc']) . "<br/>" .
                                "Date / Time Taken: " . $this->fn_general->convertDateToDisplay($img['wo_task_upload_timestamp']) . "<br/>" .
                                "GPS Coordinates: " . $this->fn_general->clear_null($img['wo_task_upload_longitude']) . ", " .
                                                      $this->fn_general->clear_null($img['wo_task_upload_latitude']);

                    // Capture X,Y
                    $startX = $pdf->GetX();
                    $startY = $pdf->GetY();

                    // Measure how many lines the description needs in a width=$colC2_3 at lineHeight=5
                    $lines1 = $pdf->MultiCell($colC2_1, $lineH, '', 0, 'L', 0, 0);
                    $lines2 = $pdf->MultiCell($colC2_2, $lineH, '<br/><br/><img src="' . $imgPath . '" height="200"/>', 0, 'C', 0, 0);
                    $lines3 = $pdf->MultiCell($colC2_3, $lineH, $descHtml, 0, 'L', 0, 0);

                    $maxLn = max($lines1, $lines2, $lines3);
                    $reqH = ($maxLn * $lineH) + 8;

                    // Reset to start of row
                    $pdf->SetXY($startX, $startY);
                    $pdf->MultiCell($colC2_1, $reqH, '', 1, 'L', 0, 0);
                    $pdf->MultiCell($colC2_2, $reqH, '<br/><br/><img src="' . $imgPath . '" height="200"/>', 1, 'C', 0, 0, false, true, '', true);
                    $pdf->MultiCell($colC2_3, $reqH, $descHtml, 1, 'L', 0, 0, false, true, '', true);

                    $pdf->Ln();
                }
            } else {
                // One blank row if no images
                $pdf->Cell(8, 20, '', 1, 0, 'C', 0);
                $pdf->Cell($printableW - 8, 20, '', 1, 0, 'L', 0);
                $pdf->Ln(10);
            }


            // 7.C1) Section C1 – “Work Assessment Details [Selected by P.I.C. to verify the complaint]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'C1', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Work Assessment Details [Selected by P.I.C. to verify the complaint]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Build four small fields in one row:
            // Person In Charge | SLA Respond Time | WR Due Date Time | Respond Status

            $picName = '';
            $picEmail = '';
            $wrDueTime = '';
            $assignTime = '';
            $respondDuration = '';
            $respondStatus = '';

            if (!empty($woTask['wo_task_assigned_to'])) {
                $picName = $arrUserFullName[intval($woTask['wo_task_assigned_to'])];
                $userProfileTech = Class_db::getInstance()->db_select_single(
                    'sys_user_profile',
                    [
                        'user_id' => $woTask['wo_task_assigned_to'],
                        'user_profile_status' => '1'
                    ],
                    null,
                    1
                );
                $picEmail = $this->fn_general->clear_null($userProfileTech['user_email']);

                // SLA due: createdTime + $arrDue[severity] hours
                $createdDt = new DateTime($woTask['wo_task_time_created']);
                if (!empty($woTask['wo_task_severity'])) {
                    $dueDt = clone $createdDt;
                    $dueDt->modify('+' . $arrDue[intval($woTask['wo_task_severity'])] . ' hour');
                    $wrDueTime = $dueDt->format('j/n/Y g:i:sa');
                }
                // assignTime
                if (!empty($woTask['wo_task_time_assigned'])) {
                    $assignedDt = new DateTime($woTask['wo_task_time_assigned']);
                    $assignTime = $assignedDt->format('j/n/Y g:i:sa');
                }
                // respondDuration
                if (!empty($woTask['wo_task_time_assigned'])) {
                    $respondDuration = $this->fn_general->timeDiff(
                        $woTask['wo_task_time_created'],
                        $woTask['wo_task_time_assigned']
                    );
                }
                // compute respondStatus: Within or Exceed
                if ($assignTime && $wrDueTime) {
                    $ad = new DateTime($assignTime);
                    $wd = new DateTime($wrDueTime);
                    $respondStatus = ($ad <= $wd ? 'Within' : 'Exceed');
                }
            }

            // Now draw two rows: first row = “Person In Charge / SLA Respond Time / WR Due Date Time / Respond Status”
            // We’ll carve the printable width into FOUR columns:
            $colC1_1 =  ($printableW * 0.25); // 25%
            $colC1_2 =  ($printableW * 0.25); // 25%
            $colC1_3 =  ($printableW * 0.25); // 25%
            $colC1_4 =  $printableW - $colC1_1 - $colC1_2 - $colC1_3; // remaining 25%

            $lineH = 6;
            $textC1_1 = 'Person In Charge: ' . $picName;
            $textC1_2 = 'SLA Respond Time: ' . $arrSla[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))];
            $textC1_3 = 'WR Due Date Time: ' . $wrDueTime;
            $textC1_4 = 'Respond Status: ' . $respondStatus;

            // Measure each of the four columns
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $lines1 = $pdf->MultiCell($colC1_1, $lineH, $textC1_1, 0, 'L', 0, 0);
            $lines2 = $pdf->MultiCell($colC1_2, $lineH, $textC1_2, 0, 'L', 0, 0);
            $lines3 = $pdf->MultiCell($colC1_3, $lineH, $textC1_3, 0, 'L', 0, 0);
            $lines4 = $pdf->MultiCell($colC1_4, $lineH, $textC1_4, 0, 'L', 0, 0);

            $maxLn = max($lines1, $lines2, $lines3, $lines4);
            $reqH = ($maxLn * $lineH) + 2;

            // Draw final bordered row
            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colC1_1, $reqH, $textC1_1, 1, 'L', 0, 0);
            $pdf->MultiCell($colC1_2, $reqH, $textC1_2, 1, 'L', 0, 0);
            $pdf->MultiCell($colC1_3, $reqH, $textC1_3, 1, 'L', 0, 0);
            $pdf->MultiCell($colC1_4, $reqH, $textC1_4, 1, 'L', 0, 0);
            $pdf->Ln();

            // 7.C2) Section C2 – “Response Images [P.I.C. verification of complaint]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'C2', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Response Images [P.I.C. verification of complaint]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Now draw each response image in a row (same pattern as complaint images)
            $pdf->SetFont('helvetica', '', 9);
            $lineH = 5;
            $colC2a = 8;
            $colC2b = ($printableW - $colC2a) * 0.48;
            $colC2c = ($printableW - $colC2a) - $colC2b;

            if (!empty($imgResponse)) {
                foreach ($imgResponse as $img) {
                    if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - 70)) {
                        $pdf->AddPage();
                    }
                    $imgPath = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                    $descHtml = "Description: " . $this->fn_general->clear_null($img['wo_task_upload_desc']) . "<br/>" .
                                "Date / Time Taken: " . $this->fn_general->convertDateToDisplay($img['wo_task_upload_timestamp']) . "<br/>" .
                                "GPS Coordinates: " . $this->fn_general->clear_null($img['wo_task_upload_longitude']) . ", " .
                                                      $this->fn_general->clear_null($img['wo_task_upload_latitude']);

                    $startX = $pdf->GetX();
                    $startY = $pdf->GetY();

                    $lines1 = $pdf->MultiCell($colC2a, $lineH, '', 0, 'L', 0, 0);
                    $lines2 = $pdf->MultiCell($colC2b, $lineH, '<br/><br/><img src="' . $imgPath . '" height="200"/>', 0, 'C', 0, 0);
                    $lines3 = $pdf->MultiCell($colC2c, $lineH, $descHtml, 0, 'L', 0, 0);

                    $maxLn = max($lines1, $lines2, $lines3);
                    $reqH = ($maxLn * $lineH) + 8;

                    $pdf->SetXY($startX, $startY);
                    $pdf->MultiCell($colC2a, $reqH, '', 1, 'L', 0, 0);
                    $pdf->MultiCell($colC2b, $reqH, '<br/><br/><img src="' . $imgPath . '" height="200"/>', 1, 'C', 0, 0, false, true, '', true);
                    $pdf->MultiCell($colC2c, $reqH, $descHtml, 1, 'L', 0, 0, false, true, '', true);

                    $pdf->Ln();
                }
            } else {
                $pdf->Cell(8, 20, '', 1, 0, 'C', 0);
                $pdf->Cell($printableW - 8, 20, '', 1, 0, 'L', 0);
                $pdf->Ln(10);
            }


            // 7.D1) Section D1 – “Validation Details [Who issues / assigns the WR to P.I.C.]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'D1', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Validation Details [Who issues / assigns the WR to P.I.C.]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Four small fields: Validation by / Designation / Verified Date / Work Request Status
            $colD1_1 = $printableW * 0.25;
            $colD1_2 = $printableW * 0.25;
            $colD1_3 = $printableW * 0.25;
            $colD1_4 = $printableW - ($colD1_1 + $colD1_2 + $colD1_3);

            // Placeholder values
            $textD1_1 = 'Validation by: [Select from System]';
            $textD1_2 = 'Designation: [System Generated]';
            $textD1_3 = 'Verified Date: [System Generated]';
            $textD1_4 = 'Work Request Status: [Accept/Reject]';

            $lineH = 6;
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l1 = $pdf->MultiCell($colD1_1, $lineH, $textD1_1, 0, 'L', 0, 0);
            $l2 = $pdf->MultiCell($colD1_2, $lineH, $textD1_2, 0, 'L', 0, 0);
            $l3 = $pdf->MultiCell($colD1_3, $lineH, $textD1_3, 0, 'L', 0, 0);
            $l4 = $pdf->MultiCell($colD1_4, $lineH, $textD1_4, 0, 'L', 0, 0);

            $maxLn = max($l1, $l2, $l3, $l4);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colD1_1, $reqH, $textD1_1, 1, 'L', 0, 0);
            $pdf->MultiCell($colD1_2, $reqH, $textD1_2, 1, 'L', 0, 0);
            $pdf->MultiCell($colD1_3, $reqH, $textD1_3, 1, 'L', 0, 0);
            $pdf->MultiCell($colD1_4, $reqH, $textD1_4, 1, 'L', 0, 0);
            $pdf->Ln(10);


            // 7.D2) Section D2 – “Remark Details [Manual Entry]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'D2', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Remark Details [Manual Entry]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Draw dynamic‐height blank for manual remark
            $pdf->SetFont('helvetica', '', 9);
            $lineH = 5;
            $colD2_left = 8;
            $colD2_right = $printableW - $colD2_left;

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();

            $linesL = $pdf->MultiCell($colD2_left, $lineH, '', 0, 'L', 0, 0);
            $linesR = $pdf->MultiCell($colD2_right, $lineH, '', 0, 'L', 0, 0);
            $maxLn = max($linesL, $linesR);
            $reqH = ($maxLn * $lineH) + 8;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colD2_left, $reqH, '', 1, 'L', 0, 0);
            $pdf->MultiCell($colD2_right, $reqH, '', 1, 'L', 0, 0);
            $pdf->Ln(10);


            // ================================
            // 8) WORK ORDER (WO) SECTION BEGINS
            // ================================
            //   A) Work Order Details
            //   B1) Work Assignment Details
            //   B2) Support Personnel
            //   C) Material Details
            //   D) Work Execution Details
            //   E) Work Completion & Verification
            //   J) Photo Documentation (Before / During / After)
            // ================================

            // 8.A) Section A – “Work Order Details”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'A', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Work Order Details',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Now draw six rows inside Section A, each with two columns (label/value),
            // wrapping text as needed and ensuring uniform row height.

            // Row A1: “Work Order No / Status”
            $woNumber = 'WO' . substr('GFMHQ' . date('ymd'), 0, 9) . str_pad($this->woTaskId, 5, '0', STR_PAD_LEFT);
            $woStatus = (!empty($woTask['wo_task_time_executed'])) ? 'Completed' : 'Open';

            $label1 = 'Work Order No: ' . $woNumber;
            $label2 = 'Status: ' . $woStatus;

            $colA_1 = $printableW * 0.5;
            $colA_2 = $printableW - $colA_1;
            $lineH = 6;

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l1 = $pdf->MultiCell($colA_1, $lineH, $label1, 0, 'L', 0, 0);
            $l2 = $pdf->MultiCell($colA_2, $lineH, $label2, 0, 'L', 0, 0);
            $maxLn = max($l1, $l2);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colA_1, $reqH, $label1, 1, 'L', 0, 0);
            $pdf->MultiCell($colA_2, $reqH, $label2, 1, 'L', 0, 0);
            $pdf->Ln();

            // Row A2: “Work Request No / Category”
            $label3 = 'Work Request No: ' . $wrNumber;
            $label4 = 'Category: ' . $arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'], 0))];

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l3 = $pdf->MultiCell($colA_1, $lineH, $label3, 0, 'L', 0, 0);
            $l4 = $pdf->MultiCell($colA_2, $lineH, $label4, 0, 'L', 0, 0);
            $maxLn = max($l3, $l4);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colA_1, $reqH, $label3, 1, 'L', 0, 0);
            $pdf->MultiCell($colA_2, $reqH, $label4, 1, 'L', 0, 0);
            $pdf->Ln();

            // Row A3: “Location Name / Location Code”
            $locName = $arrSiteName[intval($woTask['site_id'])];
            $locCode = '[Manual Entry]'; // Or pull from your DB if available

            $label5 = 'Location Name: ' . $locName;
            $label6 = 'Location Code: ' . $locCode;

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l5 = $pdf->MultiCell($colA_1, $lineH, $label5, 0, 'L', 0, 0);
            $l6 = $pdf->MultiCell($colA_2, $lineH, $label6, 0, 'L', 0, 0);
            $maxLn = max($l5, $l6);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colA_1, $reqH, $label5, 1, 'L', 0, 0);
            $pdf->MultiCell($colA_2, $reqH, $label6, 1, 'L', 0, 0);
            $pdf->Ln();

            // Row A4: “Asset Name / Asset Code”
            $assetName = '[Select from System or free text]';
            $assetCode = '[System Generated]';

            $label7 = 'Asset Name: ' . $assetName;
            $label8 = 'Asset Code: ' . $assetCode;

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l7 = $pdf->MultiCell($colA_1, $lineH, $label7, 0, 'L', 0, 0);
            $l8 = $pdf->MultiCell($colA_2, $lineH, $label8, 0, 'L', 0, 0);
            $maxLn = max($l7, $l8);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colA_1, $reqH, $label7, 1, 'L', 0, 0);
            $pdf->MultiCell($colA_2, $reqH, $label8, 1, 'L', 0, 0);
            $pdf->Ln();

            // Row A5: “Severity / WO Due Date / Time”
            $sevText = $arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))];
            // For WO due time, reuse $wrDueTime or recalc with actual SLA if needed:
            $woDueTime = $wrDueTime; // or recalc differently for actual WO SLA

            $label9  = 'Severity: ' . $sevText;
            $label10 = 'WO Due Date / Time: ' . $woDueTime;

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l9  = $pdf->MultiCell($colA_1, $lineH, $label9, 0, 'L', 0, 0);
            $l10 = $pdf->MultiCell($colA_2, $lineH, $label10, 0, 'L', 0, 0);
            $maxLn = max($l9, $l10);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colA_1, $reqH, $label9, 1, 'L', 0, 0);
            $pdf->MultiCell($colA_2, $reqH, $label10, 1, 'L', 0, 0);
            $pdf->Ln();

            // Row A6: “Complaint Description: [Manual Entry]” (full width)
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 6, 'Complaint Description:', 1, 1, 'L', 0);
            $pdf->SetFont('helvetica', '', 9);

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();

            // The user can type the complaint description here manually, so we leave it blank:
            $textA6 = '';

            $lines = $pdf->MultiCell($printableW, $lineH, $textA6, 0, 'L', 0, 0);
            $reqH  = ($lines * $lineH) + 8;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($printableW, $reqH, $textA6, 1, 'L', 0, 0);
            $pdf->Ln(10);


            // 8.B1) Section B1 – “Work Assignment Details [Details of task issuer and receiver]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'B1', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Work Assignment Details [Details of task issuer and receiver]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Four fields: Received By / Assigned To / Date Assigned / Phone No
            $colB1_1 = $printableW * 0.25;
            $colB1_2 = $printableW * 0.25;
            $colB1_3 = $printableW * 0.25;
            $colB1_4 = $printableW - ($colB1_1 + $colB1_2 + $colB1_3);

            $textB1_1 = 'Received By: [System Generated]';
            $textB1_2 = 'Assigned To: [Select from System]';
            $textB1_3 = 'Date Assigned: [System Generated]';
            $textB1_4 = 'Phone No: [System Generated]';

            $lineH = 6;
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l1 = $pdf->MultiCell($colB1_1, $lineH, $textB1_1, 0, 'L', 0, 0);
            $l2 = $pdf->MultiCell($colB1_2, $lineH, $textB1_2, 0, 'L', 0, 0);
            $l3 = $pdf->MultiCell($colB1_3, $lineH, $textB1_3, 0, 'L', 0, 0);
            $l4 = $pdf->MultiCell($colB1_4, $lineH, $textB1_4, 0, 'L', 0, 0);

            $maxLn = max($l1, $l2, $l3, $l4);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colB1_1, $reqH, $textB1_1, 1, 'L', 0, 0);
            $pdf->MultiCell($colB1_2, $reqH, $textB1_2, 1, 'L', 0, 0);
            $pdf->MultiCell($colB1_3, $reqH, $textB1_3, 1, 'L', 0, 0);
            $pdf->MultiCell($colB1_4, $reqH, $textB1_4, 1, 'L', 0, 0);
            $pdf->Ln(10);


            // 8.B2) Section B2 – “Support Personnel [Team members involved in execution]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'B2', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Support Personnel [Team members involved in execution]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Header row: No. | Name
            $colB2_1 = 8;
            $colB2_2 = $printableW - $colB2_1;
            $lineH = 6;

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l1 = $pdf->MultiCell($colB2_1, $lineH, 'No.', 0, 'C', 0, 0);
            $l2 = $pdf->MultiCell($colB2_2, $lineH, 'Name', 0, 'C', 0, 0);
            $maxLn = max($l1, $l2);
            $reqH = ($maxLn * $lineH) + 2;
            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colB2_1, $reqH, 'No.', 1, 'C', 0, 0);
            $pdf->MultiCell($colB2_2, $reqH, 'Name', 1, 'C', 0, 0);
            $pdf->Ln();

            // Fetch support personnel (wo_task_assist)
            $woAssists = Class_db::getInstance()->db_select(
                'wo_task_assist',
                ['wo_task_id' => $this->woTaskId]
            );
            $pdf->SetFont('helvetica', '', 9);
            foreach ($woAssists as $idx => $assist) {
                $assistName = $arrUserFullName[intval($assist['user_id'])];
                $rowNo = ($idx + 1);

                $startX = $pdf->GetX();
                $startY = $pdf->GetY();
                $lines1 = $pdf->MultiCell($colB2_1, $lineH, $rowNo, 0, 'C', 0, 0);
                $lines2 = $pdf->MultiCell($colB2_2, $lineH, $assistName, 0, 'L', 0, 0);
                $maxLn = max($lines1, $lines2);
                $reqH  = ($maxLn * $lineH) + 2;

                $pdf->SetXY($startX, $startY);
                $pdf->MultiCell($colB2_1, $reqH, $rowNo, 1, 'C', 0, 0);
                $pdf->MultiCell($colB2_2, $reqH, $assistName, 1, 'L', 0, 0);
                $pdf->Ln();
            }
            // If no support personnel, draw one blank row
            if (empty($woAssists)) {
                $pdf->Cell($colB2_1, 12, '', 1, 0, 'C', 0);
                $pdf->Cell($colB2_2, 12, '', 1, 0, 'L', 0);
                $pdf->Ln();
            }
            $pdf->Ln(6);


            // 8.C) Section C – “Material Details [Parts or materials issued / returned]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'C', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Material Details [Parts or materials issued / returned]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Column widths for Material Details (6 columns)
            // Part No. (25%), Item Description (40%), Issue Type (10%), Unit (10%), Qty Taken (7.5%), Qty Return (7.5%)
            $colC_1 = $printableW * 0.25;
            $colC_2 = $printableW * 0.40;
            $colC_3 = $printableW * 0.10;
            $colC_4 = $printableW * 0.10;
            $colC_5 = $printableW * 0.075;
            $colC_6 = $printableW - ($colC_1 + $colC_2 + $colC_3 + $colC_4 + $colC_5);

            // Draw header row
            $lineH = 6;
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l1 = $pdf->MultiCell($colC_1, $lineH, 'Part No.', 0, 'C', 0, 0);
            $l2 = $pdf->MultiCell($colC_2, $lineH, 'Item Description', 0, 'C', 0, 0);
            $l3 = $pdf->MultiCell($colC_3, $lineH, 'Issue Type', 0, 'C', 0, 0);
            $l4 = $pdf->MultiCell($colC_4, $lineH, 'Unit', 0, 'C', 0, 0);
            $l5 = $pdf->MultiCell($colC_5, $lineH, 'Qty Taken', 0, 'C', 0, 0);
            $l6 = $pdf->MultiCell($colC_6, $lineH, 'Qty Return', 0, 'C', 0, 0);

            $maxLn = max($l1, $l2, $l3, $l4, $l5, $l6);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colC_1, $reqH, 'Part No.', 1, 'C', 0, 0);
            $pdf->MultiCell($colC_2, $reqH, 'Item Description', 1, 'C', 0, 0);
            $pdf->MultiCell($colC_3, $reqH, 'Issue Type', 1, 'C', 0, 0);
            $pdf->MultiCell($colC_4, $reqH, 'Unit', 1, 'C', 0, 0);
            $pdf->MultiCell($colC_5, $reqH, 'Qty Taken', 1, 'C', 0, 0);
            $pdf->MultiCell($colC_6, $reqH, 'Qty Return', 1, 'C', 0, 0);
            $pdf->Ln();

            // Draw 5 blank rows for manual entry (or loop through actual material records if you have them)
            for ($i = 0; $i < 5; $i++) {
                $pdf->Cell($colC_1, 8, '', 1, 0, 'L', 0);
                $pdf->Cell($colC_2, 8, '', 1, 0, 'L', 0);
                $pdf->Cell($colC_3, 8, '', 1, 0, 'C', 0);
                $pdf->Cell($colC_4, 8, '', 1, 0, 'C', 0);
                $pdf->Cell($colC_5, 8, '', 1, 0, 'C', 0);
                $pdf->Cell($colC_6, 8, '', 1, 0, 'C', 0);
                $pdf->Ln();
            }
            $pdf->Ln(6);


            // 8.D) Section D – “Work Execution Details [Action duration, task notes, timeline]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'D', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Work Execution Details [Action duration, task notes, timeline]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Two rows: “Start Date & Time / End Date & Time” and “Duration / Status”
            $lineH = 6;

            // Compute actual values:
            $startDT = $woTask['wo_task_time_assigned']
                     ? $this->fn_general->convertDateToDisplay($woTask['wo_task_time_assigned'])
                     : '[System Generated]';
            $endDT   = $woTask['wo_task_time_executed']
                     ? $this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed'])
                     : '[System Generated]';
            $duration = '';
            if (!empty($woTask['wo_task_time_assigned']) && !empty($woTask['wo_task_time_executed'])) {
                $duration = $this->fn_general->timeDiff(
                    $woTask['wo_task_time_assigned'],
                    $woTask['wo_task_time_executed']
                );
            }
            $statusWO = '';
            if (!empty($woTask['wo_task_time_assigned']) && !empty($woTask['wo_task_time_executed']) && !empty($woTask['wo_task_severity'])) {
                // Extract hours from duration string if formatted like “X hours Y minutes”
                if (preg_match('/(\d+)\s*hour/i', $duration, $m)) {
                    $hoursTaken = intval($m[1]);
                } else {
                    $hoursTaken = 0;
                }
                $allowedHours = intval($arrDue[intval($woTask['wo_task_severity'])]);
                $statusWO = ($hoursTaken <= $allowedHours ? 'Within' : 'Exceed');
            }

            // First row: “Start Date & Time / End Date & Time”
            $labelD1 = 'Start Date & Time: ' . $startDT;
            $labelD2 = 'End Date & Time: ' . $endDT;
            $colD_1  = $printableW * 0.5;
            $colD_2  = $printableW - $colD_1;

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l1 = $pdf->MultiCell($colD_1, $lineH, $labelD1, 0, 'L', 0, 0);
            $l2 = $pdf->MultiCell($colD_2, $lineH, $labelD2, 0, 'L', 0, 0);
            $maxLn = max($l1, $l2);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colD_1, $reqH, $labelD1, 1, 'L', 0, 0);
            $pdf->MultiCell($colD_2, $reqH, $labelD2, 1, 'L', 0, 0);
            $pdf->Ln();

            // Second row: “Duration / Status”
            $labelD3 = 'Duration: ' . $duration;
            $labelD4 = 'Status: ' . $statusWO;

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $l3 = $pdf->MultiCell($colD_1, $lineH, $labelD3, 0, 'L', 0, 0);
            $l4 = $pdf->MultiCell($colD_2, $lineH, $labelD4, 0, 'L', 0, 0);
            $maxLn = max($l3, $l4);
            $reqH = ($maxLn * $lineH) + 2;

            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell($colD_1, $reqH, $labelD3, 1, 'L', 0, 0);
            $pdf->MultiCell($colD_2, $reqH, $labelD4, 1, 'L', 0, 0);
            $pdf->Ln(10);


            // 8.E) Section E – “Work Completion & Verification [Sign‐off & rating]”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(8, 8, 'E', 1, 0, 'C', 1);
            $pdf->Cell(
                $printableW - 8,
                8,
                ' Work Completion & Verification [Sign‐off & rating]',
                1,
                0,
                'L',
                1
            );
            $pdf->Ln();

            // Three signature boxes: Serviced By / Checked By / Verified By
            $servicedByName = '';
            if (!empty($woTask['wo_task_fixed_by'])) {
                $servicedByName = $arrUserFullName[intval($woTask['wo_task_fixed_by'])];
            }
            $checkedByName = ''; // fill if you have a DB field for “checked_by”
            $verifiedByName = '';
            if (!empty($woTask['wo_task_verified_by'])) {
                $verifiedByName = $arrUserFullName[intval($woTask['wo_task_verified_by'])];
            }

            $servicedDt = $woTask['wo_task_time_executed']
                          ? $this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed'])
                          : '';
            $checkedDt = ''; // if you store a “checked time” use it
            $verifiedDt = $woTask['wo_task_time_verified']
                          ? $this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified'])
                          : '';

            // Satisfactory level (rating)
            $ratingText = '';
            if (!empty($woTask['wo_task_rate'])) {
                $ratingText = $woTask['wo_task_rate'] . ' / 5';
            }

            // Compute width for each of the three boxes (evenly split)
            $boxW = floor($printableW / 3);
            $boxH = 45; // fixed height for each signature box

            // Serviced By box
            $pdf->MultiCell(
                $boxW,
                $boxH,
                "Serviced By:\n\n\n" .
                "................................................\n" .
                "Name: " . $servicedByName . "\n" .
                "Date / Time: " . $servicedDt,
                1,
                'L',
                0,
                0
            );
            // Checked By box
            $pdf->MultiCell(
                $boxW,
                $boxH,
                "Checked By:\n\n\n" .
                "................................................\n" .
                "Name: " . $checkedByName . "\n" .
                "Date / Time: " . $checkedDt,
                1,
                'L',
                0,
                0
            );
            // Verified By box
            $pdf->MultiCell(
                $boxW,
                $boxH,
                "Verified By:\n\n\n" .
                "................................................\n" .
                "Name: " . $verifiedByName . "\n" .
                "Date / Time: " . $verifiedDt,
                1,
                'L',
                0,
                0
            );
            $pdf->Ln(6);

            // Satisfactory Level line (full width)
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(
                0,
                6,
                'Satisfactory Level: [Choose 1–5: 1=Very Dissatisfied … 5=Very Satisfied] ' . $ratingText,
                0,
                1,
                'L',
                0
            );
            $pdf->Ln(8);


            // 8.J) Section J – “Photo Documentation (Before / During / After)”
            // We break this into three subsections (Before, During, After), each with a header + dynamic image rows.

            // J1: “Photo Documentation (Before)”
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(
                0,
                8,
                'Photo Documentation (Before) [Visual proof for each repair stage]',
                1,
                1,
                'L',
                1
            );
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            $colJ1_1 = ($printableW * 0.35); // 35% for image
            $colJ1_2 = $printableW - $colJ1_1;
            $lineH = 5;

            if (!empty($imgBefore)) {
                foreach ($imgBefore as $img) {
                    if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - 70)) {
                        $pdf->AddPage();
                    }
                    $imgPath = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                    $descHtml = "Description: " . $this->fn_general->clear_null($img['wo_task_upload_desc']) . "<br/>" .
                                "Date / Time Taken: " . $this->fn_general->convertDateToDisplay($img['wo_task_upload_timestamp']) . "<br/>" .
                                "GPS Coordinates: " . $this->fn_general->clear_null($img['wo_task_upload_longitude']) . ", " .
                                                      $this->fn_general->clear_null($img['wo_task_upload_latitude']);

                    $startX = $pdf->GetX();
                    $startY = $pdf->GetY();
                    $l1 = $pdf->MultiCell($colJ1_1, $lineH, '<br/><br/><img src="' . $imgPath . '" height="200"/>', 0, 'C', 0, 0);
                    $l2 = $pdf->MultiCell($colJ1_2, $lineH, $descHtml, 0, 'L', 0, 0);
                    $maxLn = max($l1, $l2);
                    $reqH = ($maxLn * $lineH) + 8;
                    $pdf->SetXY($startX, $startY);
                    $pdf->MultiCell($colJ1_1, $reqH, '<br/><br/><img src="' . $imgPath . '" height="200"/>', 1, 'C', 0, 0, false, true, '', true);
                    $pdf->MultiCell($colJ1_2, $reqH, $descHtml, 1, 'L', 0, 0, false, true, '', true);
                    $pdf->Ln();
                }
            } else {
                $pdf->Cell($colJ1_1, 20, '', 1, 0, 'C', 0);
                $pdf->Cell($colJ1_2, 20, '', 1, 0, 'L', 0);
                $pdf->Ln(10);
            }

            // J2: “Photo Documentation (During)”
            if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - 80)) {
                $pdf->AddPage();
            }
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(
                0,
                8,
                'Photo Documentation (During) [Visual proof for each repair stage]',
                1,
                1,
                'L',
                1
            );
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($imgDuring)) {
                foreach ($imgDuring as $img) {
                    if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - 70)) {
                        $pdf->AddPage();
                    }
                    $imgPath = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                    $descHtml = "Description: " . $this->fn_general->clear_null($img['wo_task_upload_desc']) . "<br/>" .
                                "Date / Time Taken: " . $this->fn_general->convertDateToDisplay($img['wo_task_upload_timestamp']) . "<br/>" .
                                "GPS Coordinates: " . $this->fn_general->clear_null($img['wo_task_upload_longitude']) . ", " .
                                                      $this->fn_general->clear_null($img['wo_task_upload_latitude']);

                    $startX = $pdf->GetX();
                    $startY = $pdf->GetY();
                    $l1 = $pdf->MultiCell($colJ1_1, $lineH, '<br/><br/><img src="' . $imgPath . '" height="200"/>', 0, 'C', 0, 0);
                    $l2 = $pdf->MultiCell($colJ1_2, $lineH, $descHtml, 0, 'L', 0, 0);
                    $maxLn = max($l1, $l2);
                    $reqH = ($maxLn * $lineH) + 8;
                    $pdf->SetXY($startX, $startY);
                    $pdf->MultiCell($colJ1_1, $reqH, '<br/><br/><img src="' . $imgPath . '" height="200"/>', 1, 'C', 0, 0, false, true, '', true);
                    $pdf->MultiCell($colJ1_2, $reqH, $descHtml, 1, 'L', 0, 0, false, true, '', true);
                    $pdf->Ln();
                }
            } else {
                $pdf->Cell($colJ1_1, 20, '', 1, 0, 'C', 0);
                $pdf->Cell($colJ1_2, 20, '', 1, 0, 'L', 0);
                $pdf->Ln(10);
            }

            // J3: “Photo Documentation (After)”
            if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - 80)) {
                $pdf->AddPage();
            }
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(
                0,
                8,
                'Photo Documentation (After) [Visual proof for each repair stage]',
                1,
                1,
                'L',
                1
            );
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($imgAfter)) {
                foreach ($imgAfter as $img) {
                    if ($pdf->GetY() > ($pdf->getPageHeight() - PDF_MARGIN_BOTTOM - 70)) {
                        $pdf->AddPage();
                    }
                    $imgPath = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                    $descHtml = "Description: " . $this->fn_general->clear_null($img['wo_task_upload_desc']) . "<br/>" .
                                "Date / Time Taken: " . $this->fn_general->convertDateToDisplay($img['wo_task_upload_timestamp']) . "<br/>" .
                                "GPS Coordinates: " . $this->fn_general->clear_null($img['wo_task_upload_longitude']) . ", " .
                                                      $this->fn_general->clear_null($img['wo_task_upload_latitude']);

                    $startX = $pdf->GetX();
                    $startY = $pdf->GetY();
                    $l1 = $pdf->MultiCell($colJ1_1, $lineH, '<br/><br/><img src="' . $imgPath . '" height="200"/>', 0, 'C', 0, 0);
                    $l2 = $pdf->MultiCell($colJ1_2, $lineH, $descHtml, 0, 'L', 0, 0);
                    $maxLn = max($l1, $l2);
                    $reqH = ($maxLn * $lineH) + 8;
                    $pdf->SetXY($startX, $startY);
                    $pdf->MultiCell($colJ1_1, $reqH, '<br/><br/><img src="' . $imgPath . '" height="200"/>', 1, 'C', 0, 0, false, true, '', true);
                    $pdf->MultiCell($colJ1_2, $reqH, $descHtml, 1, 'L', 0, 0, false, true, '', true);
                    $pdf->Ln();
                }
            } else {
                $pdf->Cell($colJ1_1, 20, '', 1, 0, 'C', 0);
                $pdf->Cell($colJ1_2, 20, '', 1, 0, 'L', 0);
                $pdf->Ln(10);
            }

            // ============================
            // 9) FINISH & SAVE PDF
            // ============================
            $folder_code = floor(intval($this->woTaskId) / 1000);
            $folder = 'pdf/wo/' . $folder_code;
            $exists = $this->fn_general->folderExist($folder);
            if (!$exists) {
                mkdir($folder, 0777, true);
            }
            $filename = 'wo_' . substr((10000000 + intval($this->woTaskId)), 1) . '.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : ' . $filename);

            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Environment : ' . $environment);

            if ($environment === 'windows') {
                $filename_src = '\wo\\' . $folder_code . '\\' . $filename;
            } else {
                $filename_src = '/wo/' . $folder_code . '/' . $filename;
            }

            $pdf->Output(dirname(__FILE__) . $filename_src, 'F');

            // Insert/update sys_pdf record
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
                        'pdf_filename'   => $filename,
                        'pdf_type'       => 'wo',
                        'pdf_folder'     => $folder,
                        'pdf_timeCreated'=> 'Now()'
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
