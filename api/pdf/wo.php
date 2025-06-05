<?php
/**
 * Class MYPDF_wo
 * Overrides the TCPDF footer so each page prints "Page X of Y" at the bottom.
 */
class MYPDF_wo extends TCPDF {
    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        // Draw a thin line from left margin to right margin
        $this->Line(PDF_MARGIN_LEFT, $this->y, $this->getPageWidth() - PDF_MARGIN_RIGHT, $this->y);
        // Print page number ("Page X of Y") at the right
        $pageNo = 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
        $this->Cell(0, 6, $pageNo, 0, 0, 'R', 0);
    }
}

/**
 * Class Class_pdf_wo
 *
 * Generates a Work Request (WR) + Work Order (WO) PDF by constructing an HTML
 * template that uses 100%‐width tables and percentage‐based <td> widths.
 * Once verified in the browser, TCPDF->writeHTML() will render it exactly as seen.
 */
class Class_pdf_wo {
    private $fn_general;   // Utility object providing clear_null(), convertDateToDisplay(), timeDiff(), etc.
    private $woTaskId;     // The ID of the WO task

    function __construct() {
    }

    // Magic property getters/setters/isset/unset (unchanged)
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
     *   - Fetches all WR + WO data from the database.
     *   - Builds a single HEREDOC HTML string containing all sections,
     *     using percentage‐based <td width="…%"> so that TCPDF renders
     *     exactly as the browser preview.
     *   - Calls TCPDF->writeHTML() to render it.
     *   - Saves the PDF file and updates sys_pdf + wo_task tables.
     *
     * Returns an array with: [ 'pdfId' => ..., 'woTaskNo' => ... ].
     */
    public function create_pdf() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            // 1) Validate woTaskId
            if (empty($this->woTaskId)) {
                throw new Exception('[Line ' . __LINE__ . '] - Parameter woTaskId Empty');
            }

            // 2) Fetch the WO task record
            $woTask = Class_db::getInstance()->db_select_single(
                'wo_task',
                ['wo_task_id' => $this->woTaskId],
                null,
                1
            );
            if (!$woTask) {
                throw new Exception('WO Task ID not found in database');
            }

            // 3) Fetch related data arrays
            $arrUserFullName = $this->fn_general->getUserFullName();   // [ user_id => "Full Name" ]
            $userProfile = Class_db::getInstance()->db_select_single(
                'sys_user_profile',
                [
                    'user_id' => $woTask['wo_task_created_by'],
                    'user_profile_status' => '1'
                ],
                null,
                1
            );
            $arrSiteName = $this->fn_general->getSiteName();           // [ site_id => "Site Name" ]
            $arrCategory = ['', 'Complaint', 'Finding', 'Request', 'Breakdown', 'Defect', 'Public Complaint'];
            $arrSeverity = $this->fn_general->getSeverityName();       // [ severity_id => "Normal", "Critical", ... ]

            // 4) Build SLA / Due arrays based on client severity
            $clientId = Class_db::getInstance()->db_select_col(
                'cli_site',
                ['site_id' => $woTask['site_id']],
                'client_id',
                null,
                1
            );
            $arrSla = ['', '4 hours', '2 hours']; // defaults
            $arrDue = ['', '4', '2'];             // defaults (hours)
            $arrClientSeverity = Class_db::getInstance()->db_select(
                'cli_client_severity',
                ['client_id' => $clientId]
            );
            foreach ($arrClientSeverity as $cs) {
                $key = intval($cs['severity_id']);
                $arrSla[$key] = $cs['client_severity_hour'] . ' hours';
                $arrDue[$key] = $cs['client_severity_hour'];
            }

            // 5) Fetch all uploads and categorize them by type
            $woUploadsAll = Class_db::getInstance()->db_select(
                'mw_wo_upload',
                [
                    'wo_task_id' => $this->woTaskId,
                    'sys_upload.upload_status' => '1'
                ]
            );
            $imgComplaint = [];   // type = 1
            $imgBefore = [];      // type = 2
            $imgDuring = [];      // type = 3
            $imgAfter = [];       // type = 4
            $imgResponse = [];    // type = 5
            $signService = null;  // type = 7
            $signVerify = null;   // type = 8
            foreach ($woUploadsAll as $upl) {
                switch ($upl['wo_task_upload_type']) {
                    case '1':
                        $imgComplaint[] = $upl; break;
                    case '2':
                        $imgBefore[] = $upl; break;
                    case '3':
                        $imgDuring[] = $upl; break;
                    case '4':
                        $imgAfter[] = $upl; break;
                    case '5':
                        $imgResponse[] = $upl; break;
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
                        // ignore other types
                        break;
                }
            }

            // 6) Build the HTML template string using HEREDOC with percentage widths
            //
            //    Each <table width="100%"> forces the table to use full width.  
            //    Label columns use width="5%", leaving 95% for data columns.  
            //    When splitting data into 2 columns, each is 47.5%.  
            //    Splitting into 3 columns → 31.66% each.  
            //    Splitting into 6 columns → approx. 15.83% each.
            //
            $wr_no         = htmlspecialchars($woTask['wo_task_no']);
            $reportedBy    = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_created_by'])]);
            $reportedPhone = htmlspecialchars($this->fn_general->clear_null($userProfile['user_contact_no']));
            $reportedEmail = htmlspecialchars($this->fn_general->clear_null($userProfile['user_email']));
            $reportedDtTxt = htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_created']));
            $categoryTxt   = htmlspecialchars($arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'], 0))]);
            $severityTxt   = htmlspecialchars($arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))]);
            $locationTxt   = htmlspecialchars($this->fn_general->clear_null($woTask['wo_task_location']));
            $complaintTxt  = htmlspecialchars($this->fn_general->clear_null($woTask['wo_task_complaint']));

            // C1 fields
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

                // Calculate WR Due Date
                if (!empty($woTask['wo_task_severity'])) {
                    $createdDt = new DateTime($woTask['wo_task_time_created']);
                    $dueDt = clone $createdDt;
                    $dueDt->modify('+' . $arrDue[intval($woTask['wo_task_severity'])] . ' hour');
                    $wrDueTime = htmlspecialchars($dueDt->format('d/m/Y g:i A'));
                }
                // Calculate Respond Date / Duration / Status
                if (!empty($woTask['wo_task_time_assigned'])) {
                    $assignedDt = new DateTime($woTask['wo_task_time_assigned']);
                    $assignTime = htmlspecialchars($assignedDt->format('d/m/Y g:i A'));
                    $respondDuration = htmlspecialchars(
                        $this->fn_general->timeDiff(
                            $woTask['wo_task_time_created'],
                            $woTask['wo_task_time_assigned']
                        )
                    );
                    if ($assignedDt && !empty($wrDueTime)) {
                        $respondStatus = ($assignedDt <= new DateTime($wrDueTime)) ? 'Within' : 'Exceed';
                    }
                }
            }

            // C2 fields (images)
            // We will insert up to 3 response images; if fewer, leave blank
            $r1_img = '';
            $r1_desc = $r1_ts = $r1_gps = '';
            $r2_img = '';
            $r2_desc = $r2_ts = $r2_gps = '';
            $r3_img = '';
            $r3_desc = $r3_ts = $r3_gps = '';
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

            // WO‐A fields
            // Generate a pseudo WO Number (for demonstration)
            $woNumber = 'WO' . str_pad($this->woTaskId, 10, '0', STR_PAD_LEFT);
            $woStatus = (!empty($woTask['wo_task_time_executed'])) ? 'Completed' : 'Open';

            $locName = htmlspecialchars($arrSiteName[intval($woTask['site_id'])]);
            $locCode = htmlspecialchars('[System Generated]');
            $assetName = htmlspecialchars('[Select from System]');
            $assetCode = htmlspecialchars('[System Generated]');
            $woSeverity = htmlspecialchars($arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))]);

            $woDueTime = '';
            if (!empty($woTask['wo_task_time_assigned']) && !empty($woTask['wo_task_severity'])) {
                $assignedDt = new DateTime($woTask['wo_task_time_assigned']);
                $dueDt = clone $assignedDt;
                $dueDt->modify('+' . $arrDue[intval($woTask['wo_task_severity'])] . ' hour');
                $woDueTime = htmlspecialchars($dueDt->format('d/m/Y g:i A'));
            }

            // WO‐B1
            $receivedBy = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_verified_by'] ?? 0)]);
            $assignedTo = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_assigned_to'] ?? 0)]);
            $dateAssigned = '';
            if (!empty($woTask['wo_task_time_assigned'])) {
                $dateAssigned = htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_assigned']));
            }
            $issuerPhone = htmlspecialchars($this->fn_general->clear_null($userProfile['user_contact_no']));

            // WO‐B2 (Support Personnel)
            $woAssists = Class_db::getInstance()->db_select(
                'wo_task_assist',
                ['wo_task_id' => $this->woTaskId]
            );
            $assistRowsHtml = '';
            if (!empty($woAssists)) {
                foreach ($woAssists as $idx => $assist) {
                    $assistName = htmlspecialchars($arrUserFullName[intval($assist['user_id'])]);
                    $rowNo = $idx + 1;
                    $assistRowsHtml .= "
    <tr>
      <td width=\"5%\" style=\"text-align:center;\">{$rowNo}</td>
      <td width=\"95%\">{$assistName}</td>
    </tr>";
                }
            } else {
                // Single blank row
                $assistRowsHtml = "
    <tr>
      <td width=\"5%\">&nbsp;</td>
      <td width=\"95%\">&nbsp;</td>
    </tr>";
            }

            // WO‐C: Material Details — we prepare 5 blank rows (no dynamic data)
            $materialBlankRows = '';
            for ($i = 0; $i < 5; $i++) {
                $materialBlankRows .= "
    <tr>
      <td width=\"5%\">&nbsp;</td>
      <td width=\"15.83%\">&nbsp;</td>
      <td width=\"15.83%\">&nbsp;</td>
      <td width=\"15.83%\">&nbsp;</td>
      <td width=\"15.83%\">&nbsp;</td>
      <td width=\"15.83%\">&nbsp;</td>
      <td width=\"15.83%\">&nbsp;</td>
    </tr>";
            }

            // WO‐D: Work Execution Details
            $startDT = $woTask['wo_task_time_assigned']
                     ? htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_assigned']))
                     : '[System Generated]';
            $endDT   = $woTask['wo_task_time_executed']
                     ? htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed']))
                     : '[System Generated]';

            $duration = '';
            if (!empty($woTask['wo_task_time_assigned']) && !empty($woTask['wo_task_time_executed'])) {
                $duration = htmlspecialchars($this->fn_general->timeDiff(
                    $woTask['wo_task_time_assigned'],
                    $woTask['wo_task_time_executed']
                ));
            }

            $statusWO = '';
            if (!empty($woTask['wo_task_time_assigned']) && !empty($woTask['wo_task_time_executed']) && !empty($woTask['wo_task_severity'])) {
                if (preg_match('/(\d+)\s*hour/i', $duration, $m)) {
                    $hoursTaken = intval($m[1]);
                } else {
                    $hoursTaken = 0;
                }
                $allowedHours = intval($arrDue[intval($woTask['wo_task_severity'])]);
                $statusWO = ($hoursTaken <= $allowedHours) ? 'Within SLA' : 'Exceed SLA';
            }

            // WO‐E: Work Completion & Verification
            $servicedByName = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_fixed_by'] ?? 0)]);
            $servicedAt = '';
            if (!empty($woTask['wo_task_time_executed'])) {
                $servicedAt = htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed']));
            }
            $checkedByName = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_verified_by'] ?? 0)]);
            $checkedAt = '';
            if (!empty($woTask['wo_task_time_verified'])) {
                $checkedAt = htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified']));
            }
            $verifiedByName = htmlspecialchars($arrUserFullName[intval($woTask['wo_task_verified_by'] ?? 0)]);
            $verifiedAt = '';
            if (!empty($woTask['wo_task_time_verified'])) {
                $verifiedAt = htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified']));
            }
            $ratingTxt = '';
            if (!empty($woTask['wo_task_rate'])) {
                $ratingTxt = htmlspecialchars($woTask['wo_task_rate'] . ' / 5');
            }

            // J1, J2, J3 (Photo Documentation Before / During / After)
            // Prepare up to three images for each stage
            function prepareImageHtml($imgArray) {
                $html = '';
                for ($i = 0; $i < 3; $i++) {
                    if (!empty($imgArray[$i])) {
                        $img = $imgArray[$i];
                        $path = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                        $desc = htmlspecialchars((new Class_general())->clear_null($img['wo_task_upload_desc']));
                        $ts   = htmlspecialchars((new Class_general())->convertDateToDisplay($img['wo_task_upload_timestamp']));
                        $gps  = htmlspecialchars($img['wo_task_upload_longitude'] . ', ' . $img['wo_task_upload_latitude']);
                        $html .= "
    <tr>
      <td width=\"5%\">&nbsp;</td>
      <td width=\"31.66%\" style=\"text-align:center; border:1px solid #000; padding:4px;\">
        <img src=\"{$path}\" style=\"max-width:100%; height:auto;\" /><br/><br/>
        <strong>Description:</strong> {$desc}<br/>
        <strong>Date / Time Taken:</strong> {$ts}<br/>
        <strong>Longitude / Latitude:</strong> {$gps}
      </td>";
                    } else {
                        $html .= "
    <tr>
      <td width=\"5%\">&nbsp;</td>
      <td width=\"31.66%\" style=\"height:50mm;\">&nbsp;</td>";
                    }
                }
                $html .= "
    </tr>";
                return $html;
            }

            $beforeHtml = prepareImageHtml($imgBefore);
            $duringHtml = prepareImageHtml($imgDuring);
            $afterHtml  = prepareImageHtml($imgAfter);

            // ========================= BUILD THE HEREDOC HTML STRING =========================
            $html = <<<'HTML'
<style>
  /* ======== Global styles ======== */
  body {
    font-family: Helvetica, Arial, sans-serif;
    font-size: 10pt;
    margin: 0;
  }
  table {
    border-collapse: collapse;
    margin-bottom: 20px;
  }
  td, th {
    border: 1px solid #000;
    vertical-align: top;
    padding: 4px;
  }
  .section-header {
    background-color: #E6E6E6;
    font-weight: bold;
  }
  .center-text {
    text-align: center;
  }
  .placeholder {
    color: #555;
    font-style: italic;
  }
  .no-border td {
    border: none !important;
  }
</style>

<!-- ========================= HEADER ========================= -->
<table width="100%" class="no-border">
  <tr>
    <td colspan="2" class="center-text" style="font-size:16pt; font-weight:bold; border:none;">
      WORK REQUEST (WR) &amp;<br/>WORK ORDER (WO)
    </td>
  </tr>
  <tr>
    <td colspan="2" style="border-top:1px solid #000; border-left:none; border-right:none; border-bottom:none; height:8px;">
      &nbsp;
    </td>
  </tr>
</table>

<!-- ========================= WR – SECTION A: Complaint Details ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">A</td>
    <td class="section-header" width="95%">Complaint Details [User Details: Public &amp; Client for Complaints or Internal: for Self-Finding]</td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Reported by:</strong><br/>__REPORTED_BY__<br/>
      <span class="placeholder">[Manual Entry]</span>
    </td>
    <td width="47.5%">
      <strong>Phone No:</strong><br/>__REPORTED_PHONE__<br/>
      <span class="placeholder">[Manual Entry]</span>
    </td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Email:</strong><br/>__REPORTED_EMAIL__<br/>
      <span class="placeholder">[Manual Entry]</span>
    </td>
    <td width="47.5%">
      <strong>Reported Date / Time:</strong><br/>__REPORTED_DT__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Category:</strong><br/>__CATEGORY__<br/>
      <span class="placeholder">[Select from System]</span>
    </td>
    <td width="47.5%">
      <strong>Severity:</strong><br/>__SEVERITY__<br/>
      <span class="placeholder">[Select from System]</span>
    </td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Work Request No:</strong><br/>__WR_NO__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
    <td width="47.5%">
      <strong>Location Complaint:</strong><br/>__LOCATION__<br/>
      <span class="placeholder">[Select from System]</span>
    </td>
  </tr>
</table>

<!-- ========================= WR – SECTION B1: Description of Complaint ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">B1</td>
    <td class="section-header" width="95%">Description of Complaint [Manual Entry]</td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
    <td width="95%" style="height:40mm;">&nbsp;</td>
  </tr>
</table>

<!-- ========================= WR – SECTION B2: Complaint Images ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">B2</td>
    <td class="section-header" colspan="3" width="95%">Complaint Images [Complain from User]</td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
    <td class="center-text" width="31.66%"><strong>Image 1</strong></td>
    <td class="center-text" width="31.66%"><strong>Image 2</strong></td>
    <td class="center-text" width="31.66%"><strong>Image 3</strong></td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
    <td width="31.66%" style="height:50mm;">&nbsp;</td>
    <td width="31.66%" style="height:50mm;">&nbsp;</td>
    <td width="31.66%" style="height:50mm;">&nbsp;</td>
  </tr>
</table>

<!-- ========================= WR – SECTION C1: Work Assessment Details ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">C1</td>
    <td class="section-header" colspan="3" width="95%">Work Assessment Details [Selected by P.I.C. to verify the complaint]</td>
  </tr>
  <tr>
    <td width="5%"><strong>Person in Charge:</strong><br/><span class="placeholder">[Select from System]</span></td>
    <td width="31.66%"><strong>SLA Respond Time:</strong><br/><span class="placeholder">[System Generated]</span></td>
    <td width="31.66%"><strong>WR Due Date / Time:</strong><br/><span class="placeholder">[System Generated]</span></td>
    <td width="31.66%"><strong>Respond Status:</strong><br/><span class="placeholder">[System Generated]</span></td>
  </tr>
  <tr>
    <td width="5%"><strong>Email:</strong><br/><span class="placeholder">[System Generated]</span></td>
    <td colspan="3" width="95%"><strong>Respond Date / Duration:</strong><br/><span class="placeholder">[System Generated]</span></td>
  </tr>
</table>

<!-- ========================= WR – SECTION C2: Response Images ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">C2</td>
    <td class="section-header" colspan="3" width="95%">Response Images [P.I.C. verification of the complaint]</td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
    <td class="center-text" width="31.66%"><strong>Image 1</strong></td>
    <td class="center-text" width="31.66%"><strong>Image 2</strong></td>
    <td class="center-text" width="31.66%"><strong>Image 3</strong></td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
    <td width="31.66%" style="height:50mm;">&nbsp;</td>
    <td width="31.66%" style="height:50mm;">&nbsp;</td>
    <td width="31.66%" style="height:50mm;">&nbsp;</td>
  </tr>
</table>

<!-- ========================= WR – SECTION D1: Validation Details ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">D1</td>
    <td class="section-header" width="95%">Validation Details [Who issues / assigns the WR to P.I.C.]</td>
  </tr>
  <tr>
    <td width="5%"><strong>Validation by:</strong><br/>[Select from System]</td>
    <td width="95%"><strong>Designation:</strong><br/>[System Generated]</td>
  </tr>
  <tr>
    <td width="5%"><strong>Verified Date:</strong><br/>[System Generated]</td>
    <td width="95%"><strong>Work Request Status:</strong><br/>[Accept/Reject]</td>
  </tr>
</table>

<!-- ========================= WR – SECTION D2: Remark Details ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">D2</td>
    <td class="section-header" width="95%">Remark Details [Manual Entry]</td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
    <td width="95%" style="height:40mm;">&nbsp;</td>
  </tr>
</table>

<!-- ========================= WO HEADER ========================= -->
<table width="100%" class="no-border">
  <tr>
    <td colspan="2" class="center-text" style="font-size:16pt; font-weight:bold; border:none;">
      WORK ORDER (WO)
    </td>
  </tr>
  <tr>
    <td colspan="2" style="border-top:1px solid #000; border-left:none; border-right:none; border-bottom:none; height:8px;">
      &nbsp;
    </td>
  </tr>
</table>

<!-- ========================= WO – SECTION A: Work Order Details ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">A</td>
    <td class="section-header" width="95%">Work Order Details</td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Work Order No:</strong><br/>__WO_NO__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
    <td width="47.5%">
      <strong>Status:</strong><br/>__WO_STATUS__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Work Request No:</strong><br/>__WR_NO__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
    <td width="47.5%">
      <strong>Category:</strong><br/>__CATEGORY__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Location Name:</strong><br/>__LOC_NAME__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
    <td width="47.5%">
      <strong>Location Code:</strong><br/>__LOC_CODE__
    </td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Asset Name:</strong><br/>__ASSET_NAME__<br/>
      <span class="placeholder">[Select from System]</span>
    </td>
    <td width="47.5%">
      <strong>Asset Code:</strong><br/>__ASSET_CODE__
    </td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Severity:</strong><br/>__WO_SEVERITY__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
    <td width="47.5%">
      <strong>WO Due Date / Time:</strong><br/>__WO_DUE_TIME__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
  </tr>
  <tr>
    <td colspan="2" width="100%">
      <strong>Complaint Description:</strong><br/>
      <div style="height:20mm;">__COMPLAINT_TEXT__</div>
    </td>
  </tr>
</table>

<!-- ========================= WO – SECTION B1: Work Assignment Details ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">B1</td>
    <td class="section-header" width="95%">Work Assignment Details [Details of task issuer and receiver]</td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Received By:</strong><br/>__RECEIVED_BY__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
    <td width="47.5%">
      <strong>Assigned To:</strong><br/>__ASSIGNED_TO__<br/>
      <span class="placeholder">[Select from System]</span>
    </td>
  </tr>
  <tr>
    <td width="47.5%">
      <strong>Date Assigned:</strong><br/>__DATE_ASSIGNED__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
    <td width="47.5%">
      <strong>Phone No:</strong><br/>__ISSUER_PHONE__<br/>
      <span class="placeholder">[System Generated]</span>
    </td>
  </tr>
</table>

<!-- ========================= WO – SECTION B2: Support Personnel ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">B2</td>
    <td class="section-header" width="95%">Support Personnel [Team members involved in execution]</td>
  </tr>
  <tr>
    <td style="text-align:center;" width="5%"><strong>No.</strong></td>
    <td width="95%"><strong>Name</strong></td>
  </tr>
  __ASSIST_ROWS__
</table>

<!-- ========================= WO – SECTION C: Material Details ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">C</td>
    <td class="section-header" colspan="6" width="95%">Material Details [Parts or materials issued / returned]</td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
    <td width="15.83%"><strong>Part No.</strong></td>
    <td width="15.83%"><strong>Item Description</strong></td>
    <td width="15.83%"><strong>Issue Type (D/I)</strong></td>
    <td width="15.83%"><strong>Unit</strong></td>
    <td width="15.83%"><strong>Qty Taken</strong></td>
    <td width="15.83%"><strong>Qty Return</strong></td>
  </tr>
  <!-- Example data row (remove or replace if not needed) -->
  <tr>
    <td>&nbsp;</td>
    <td>ABC-123</td>
    <td>Replacement Filter</td>
    <td>D</td>
    <td>Each</td>
    <td>1</td>
    <td>0</td>
  </tr>
  __MATERIAL_BLANK_ROWS__
</table>

<!-- ========================= WO – SECTION D: Work Execution Details ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">D</td>
    <td class="section-header" colspan="2" width="95%">Work Execution Details [Action duration, task notes, timeline]</td>
  </tr>
  <tr>
    <td width="5%"><strong>Start Date &amp; Time:</strong><br/>__START_DT__</td>
    <td width="47.5%"><strong>End Date &amp; Time:</strong><br/>__END_DT__</td>
    <td width="47.5%"><strong>Duration:</strong><br/>__DURATION__</td>
  </tr>
  <tr>
    <td colspan="1" width="5%">&nbsp;</td>
    <td width="47.5%"><strong>Status:</strong><br/>__STATUS_WO__</td>
    <td width="47.5%">&nbsp;</td>
  </tr>
</table>

<!-- ========================= WO – SECTION E: Work Completion & Verification ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">E</td>
    <td class="section-header" colspan="2" width="95%">Work Completion &amp; Verification [Sign‐off &amp; rating]</td>
  </tr>
  <tr>
    <td width="31.666%" style="height:50mm; vertical-align: top;">
      <strong>Serviced By:</strong><br/><br/><br/>
      .........................................<br/>
      <strong>Name:</strong> __SERVICED_BY__<br/>
      <strong>Date / Time:</strong> __SERVICED_AT__
    </td>
    <td width="31.666%" style="height:50mm; vertical-align: top;">
      <strong>Checked By:</strong><br/><br/><br/>
      .........................................<br/>
      <strong>Name:</strong> __CHECKED_BY__<br/>
      <strong>Date / Time:</strong> __CHECKED_AT__
    </td>
    <td width="31.666%" style="height:50mm; vertical-align: top;">
      <strong>Verified By:</strong><br/><br/><br/>
      .........................................<br/>
      <strong>Name:</strong> __VERIFIED_BY__<br/>
      <strong>Date / Time:</strong> __VERIFIED_AT__
    </td>
  </tr>
  <tr>
    <td colspan="3" width="100%" style="padding:6px;">
      <strong>Satisfactory Level:</strong> [Choose 1–5: 1=Very Dissatisfied … 5=Very Satisfied]
      <span class="placeholder">__RATING__</span>
    </td>
  </tr>
</table>

<!-- ========================= WO – SECTION J1: Photo Documentation (Before) ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">J1</td>
    <td class="section-header" colspan="3" width="95%">Photo Documentation (Before) [Visual proof for each repair stage]</td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
    <td class="center-text" width="31.66%"><strong>Image 1</strong></td>
    <td class="center-text" width="31.66%"><strong>Image 2</strong></td>
    <td class="center-text" width="31.66%"><strong>Image 3</strong></td>
  </tr>
  __BEFORE_ROWS__
</table>

<!-- ========================= WO – SECTION J2: Photo Documentation (During) ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">J2</td>
    <td class="section-header" colspan="3" width="95%">Photo Documentation (During) [Visual proof for each repair stage]</td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
    <td class="center-text" width="31.66%"><strong>Image 1</strong></td>
    <td class="center-text" width="31.66%"><strong>Image 2</strong></td>
    <td class="center-text" width="31.66%"><strong>Image 3</strong></td>
  </tr>
  __DURING_ROWS__
</table>

<!-- ========================= WO – SECTION J3: Photo Documentation (After) ========================= -->
<table width="100%">
  <tr>
    <td class="section-header center-text" width="5%">J3</td>
    <td class="section-header" colspan="3" width="95%">Photo Documentation (After) [Visual proof for each repair stage]</td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
    <td class="center-text" width="31.66%"><strong>Image 1</strong></td>
    <td class="center-text" width="31.66%"><strong>Image 2</strong></td>
    <td class="center-text" width="31.66%"><strong>Image 3</strong></td>
  </tr>
  __AFTER_ROWS__
</table>
HTML;

            // 7) Now replace each placeholder token with its PHP variable
            $search = [
                '__REPORTED_BY__',
                '__REPORTED_PHONE__',
                '__REPORTED_EMAIL__',
                '__REPORTED_DT__',
                '__CATEGORY__',
                '__SEVERITY__',
                '__WR_NO__',
                '__LOCATION__',
                '__COMPLAINT_TEXT__',

                '__START_DT__',
                '__END_DT__',
                '__DURATION__',
                '__STATUS_WO__',

                '__WO_NO__',
                '__WO_STATUS__',
                '__LOC_NAME__',
                '__LOC_CODE__',
                '__ASSET_NAME__',
                '__ASSET_CODE__',
                '__WO_SEVERITY__',
                '__WO_DUE_TIME__',

                '__RECEIVED_BY__',
                '__ASSIGNED_TO__',
                '__DATE_ASSIGNED__',
                '__ISSUER_PHONE__',

                '__SERVICED_BY__',
                '__SERVICED_AT__',
                '__CHECKED_BY__',
                '__CHECKED_AT__',
                '__VERIFIED_BY__',
                '__VERIFIED_AT__',
                '__RATING__',

                '__ASSIST_ROWS__',
                '__MATERIAL_BLANK_ROWS__',
                '__BEFORE_ROWS__',
                '__DURING_ROWS__',
                '__AFTER_ROWS__'
            ];

            $replace = [
                $reportedBy,
                $reportedPhone,
                $reportedEmail,
                $reportedDtTxt,
                $categoryTxt,
                $severityTxt,
                $wr_no,
                $locationTxt,
                $complaintTxt,

                $startDT,
                $endDT,
                $duration,
                $statusWO,

                $woNumber,
                $woStatus,
                $locName,
                $locCode,
                $assetName,
                $assetCode,
                $woSeverity,
                $woDueTime,

                $receivedBy,
                $assignedTo,
                $dateAssigned,
                $issuerPhone,

                $servicedByName,
                $servicedAt,
                $checkedByName,
                $checkedAt,
                $verifiedByName,
                $verifiedAt,
                $ratingTxt,

                $assistRowsHtml,
                $materialBlankRows,
                $beforeHtml,
                $duringHtml,
                $afterHtml
            ];

            // Perform the placeholder replacements
            $html = str_replace($search, $replace, $html);

            // 8) Instantiate TCPDF and render the HTML
            $pdf = new MYPDF_wo(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Generated by Class_pdf_wo');
            $pdf->SetTitle('GEMS 2.0 WR & WO');
            $pdf->SetSubject('GEMS 2.0 WR & WO');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(true);
            $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
            $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            $pdf->AddPage();
            // Write the full HTML—TCPDF will interpret the percentage widths correctly
            $pdf->writeHTML($html, true, false, true, false, '');

            // 9) Save the generated PDF file
            $folder_code = floor(intval($this->woTaskId) / 1000);
            $folder = 'pdf/wo/' . $folder_code;
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            $filename = 'wo_' . str_pad($this->woTaskId, 10, '0', STR_PAD_LEFT) . '.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Saving PDF as: ' . $filename);

            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Environment: ' . $environment);

            if ($environment === 'windows') {
                $filename_src = '\wo\\' . $folder_code . '\\' . $filename;
            } else {
                $filename_src = '/wo/' . $folder_code . '/' . $filename;
            }
            $pdf->Output(dirname(__FILE__) . $filename_src, 'F');

            // 10) Insert/update sys_pdf record, then update wo_task
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
