<?php
/**
 * Class MYPDF_wo
 * Overrides the TCPDF footer so that each page prints "Page X of Y" at the bottom.
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
 * Builds a Work Request (WR) + Work Order (WO) report entirely via HTML tables
 * (with inline CSS) and hands it to TCPDF’s writeHTML() method. The gray header
 * color (#E6E6E6) exactly matches the original template’s section-header background.
 */
class Class_pdf_wo {
    private $fn_general;   // Utility object (clear_null, convertDateToDisplay, timeDiff, etc.)
    private $woTaskId;     // The ID of the WO task we’re generating a PDF for

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
     *   - Fetches all data (WR + WO) from database
     *   - Builds a single $html string containing all sections as <table>…</table>,
     *     using inline CSS with background‐color #E6E6E6 for each “section-header” row.
     *   - Calls TCPDF->writeHTML() to render it.
     *   - Saves the PDF file and updates sys_pdf + wo_task tables.
     *
     * Returns an array with: [ 'pdfId' => ..., 'woTaskNo' => ... ].
     */
    public function create_pdf() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            // 1) Validate that woTaskId was set
            if (empty($this->woTaskId)) {
                throw new Exception('[Line ' . __LINE__ . '] - Parameter woTaskId Empty');
            }

            // 2) Pull WO task record from database
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
            $arrSeverity = $this->fn_general->getSeverityName();       // [ severity_id => "Non-Critical", "Critical" ... ]

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

            // 5) Fetch all uploads, categorize them by type
            $woUploadsAll = Class_db::getInstance()->db_select(
                'mw_wo_upload',
                [
                    'wo_task_id' => $this->woTaskId,
                    'sys_upload.upload_status' => '1'
                ]
            );
            $imgComplaint = [];   // wo_task_upload_type = 1
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
                        // ignore all other types
                        break;
                }
            }

            // 6) Build the HTML template string (with inline CSS using #E6E6E6 for section headers)
            $html = '
<style>
    /* Use the same light-gray (#E6E6E6) for section-header background as in the Word template */
    body { font-family: helvetica, sans-serif; font-size: 10pt; }
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #000000; vertical-align: top; padding: 4px; }
    .section-header { background-color: #E6E6E6; font-weight: bold; }
    .no-border td { border: none !important; }
    .center-text { text-align: center; }
</style>

<!-- ========================= 
     HEADER: WR & WO TITLE 
     ========================= -->
<table class="no-border">
    <tr>
        <td colspan="2" class="center-text" style="font-size:16pt; font-weight:bold; border:none;">
            WORK REQUEST (WR) &amp;<br/>WORK ORDER (WO)
        </td>
    </tr>
    <!-- Thin line -->
    <tr>
        <td colspan="2" style="border-top:1px solid #000; border-left:none; border-right:none; border-bottom:none; height:8px;">
            &nbsp;
        </td>
    </tr>
</table>
<br/>

<!-- ========================= 
     SECTION A: Complaint Details 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">A</td>
        <td class="section-header">
            Complaint Details [User Details: Public &amp; Client for Complaints or Internal: for Self-Finding]
        </td>
    </tr>
    <!-- Row A1: Reported by / Phone No -->
    <tr>
        <td style="width:50%;"><strong>Reported by:</strong><br/>' . htmlspecialchars($arrUserFullName[intval($woTask['wo_task_created_by'])]) . '</td>
        <td><strong>Phone No:</strong><br/>' . htmlspecialchars($this->fn_general->clear_null($userProfile['user_contact_no'])) . '</td>
    </tr>
    <!-- Row A2: Email / Reported Date / Time -->
    <tr>
        <td><strong>Email:</strong><br/>' . htmlspecialchars($this->fn_general->clear_null($userProfile['user_email'])) . '</td>
        <td><strong>Reported Date / Time:</strong><br/>' . htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_created'])) . '</td>
    </tr>
    <!-- Row A3: Category / Severity -->
    <tr>
        <td><strong>Category:</strong><br/>' . htmlspecialchars($arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'],0))]) . '</td>
        <td><strong>Severity:</strong><br/>' . htmlspecialchars($arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'],0))]) . '</td>
    </tr>
    <!-- Row A4: Work Request No / Location Complaint -->
    <tr>
        <td><strong>Work Request No:</strong><br/>' . htmlspecialchars($woTask['wo_task_no']) . '</td>
        <td><strong>Location Complaint:</strong><br/>' . htmlspecialchars($this->fn_general->clear_null($woTask['wo_task_location'])) . '</td>
    </tr>
</table>
<br/>

<!-- ========================= 
     SECTION B1: Description of Complaint 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">B1</td>
        <td class="section-header">Description of Complaint [Manual Entry]</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td style="height:30mm;">&nbsp;</td>
    </tr>
</table>
<br/>

<!-- ========================= 
     SECTION B2: Complaint Images 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">B2</td>
        <td class="section-header">Complaint Images [Complain from User]</td>
    </tr>';

            // Insert each complaint image; if none, show one blank row
            if (!empty($imgComplaint)) {
                foreach ($imgComplaint as $img) {
                    $imgPath = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                    $desc    = htmlspecialchars($this->fn_general->clear_null($img['wo_task_upload_desc']));
                    $ts      = $this->fn_general->convertDateToDisplay($img['wo_task_upload_timestamp']);
                    $gps     = htmlspecialchars($img['wo_task_upload_longitude'] . ', ' . $img['wo_task_upload_latitude']);

                    $html .= '
    <tr>
        <td>&nbsp;</td>
        <td>
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:48%; text-align:center; border:1px solid #000; padding:4px;">
                        <img src="' . $imgPath . '" style="max-width:100%; height:auto;" />
                    </td>
                    <td style="width:52%; border:1px solid #000; padding:4px;">
                        <strong>Description:</strong> ' . $desc . '<br/>
                        <strong>Date / Time Taken:</strong> ' . $ts . '<br/>
                        <strong>GPS Coordinates:</strong> ' . $gps . '
                    </td>
                </tr>
            </table>
        </td>
    </tr>';
                }
            } else {
                // One blank row if no complaint images
                $html .= '
    <tr>
        <td>&nbsp;</td>
        <td style="height:30mm;">&nbsp;</td>
    </tr>';
            }

            $html .= '
</table>
<br/>

<!-- ========================= 
     SECTION C1: Work Assessment Details 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">C1</td>
        <td class="section-header">Work Assessment Details [Selected by P.I.C. to verify the complaint]</td>
    </tr>';

            // Calculate Work Assessment fields
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
                $createdDt = new DateTime($woTask['wo_task_time_created']);
                if (!empty($woTask['wo_task_severity'])) {
                    $dueDt = clone $createdDt;
                    $dueDt->modify('+' . $arrDue[intval($woTask['wo_task_severity'])] . ' hour');
                    $wrDueTime = $dueDt->format('j/n/Y g:i:sa');
                }
                if (!empty($woTask['wo_task_time_assigned'])) {
                    $assignedDt = new DateTime($woTask['wo_task_time_assigned']);
                    $assignTime = $assignedDt->format('j/n/Y g:i:sa');
                    $respondDuration = $this->fn_general->timeDiff(
                        $woTask['wo_task_time_created'],
                        $woTask['wo_task_time_assigned']
                    );
                    if ($assignedDt && !empty($wrDueTime)) {
                        $respondStatus = ($assignedDt <= new DateTime($wrDueTime) ? 'Within' : 'Exceed');
                    }
                }
            }

            // Two rows, four cells total
            $html .= '
    <tr>
        <td><strong>Person In Charge:</strong><br/>' . htmlspecialchars($picName) . '</td>
        <td><strong>SLA Respond Time:</strong><br/>' . htmlspecialchars($arrSla[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))]) . '</td>
    </tr>
    <tr>
        <td><strong>WR Due Date Time:</strong><br/>' . htmlspecialchars($wrDueTime) . '</td>
        <td><strong>Respond Status:</strong><br/>' . htmlspecialchars($respondStatus) . '</td>
    </tr>
</table>
<br/>

<!-- ========================= 
     SECTION C2: Response Images 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">C2</td>
        <td class="section-header">Response Images [P.I.C. verification of complaint]</td>
    </tr>';

            if (!empty($imgResponse)) {
                foreach ($imgResponse as $img) {
                    $imgPath = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                    $desc    = htmlspecialchars($this->fn_general->clear_null($img['wo_task_upload_desc']));
                    $ts      = $this->fn_general->convertDateToDisplay($img['wo_task_upload_timestamp']);
                    $gps     = htmlspecialchars($img['wo_task_upload_longitude'] . ', ' . $img['wo_task_upload_latitude']);

                    $html .= '
    <tr>
        <td>&nbsp;</td>
        <td>
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:48%; text-align:center; border:1px solid #000; padding:4px;">
                        <img src="' . $imgPath . '" style="max-width:100%; height:auto;" />
                    </td>
                    <td style="width:52%; border:1px solid #000; padding:4px;">
                        <strong>Description:</strong> ' . $desc . '<br/>
                        <strong>Date / Time Taken:</strong> ' . $ts . '<br/>
                        <strong>GPS Coordinates:</strong> ' . $gps . '
                    </td>
                </tr>
            </table>
        </td>
    </tr>';
                }
            } else {
                $html .= '
    <tr>
        <td>&nbsp;</td>
        <td style="height:30mm;">&nbsp;</td>
    </tr>';
            }

            $html .= '
</table>
<br/>

<!-- ========================= 
     SECTION D1: Validation Details 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">D1</td>
        <td class="section-header">Validation Details [Who issues / assigns the WR to P.I.C.]</td>
    </tr>
    <tr>
        <td><strong>Validation by:</strong><br/>[Select from System]</td>
        <td><strong>Designation:</strong><br/>[System Generated]</td>
    </tr>
    <tr>
        <td><strong>Verified Date:</strong><br/>[System Generated]</td>
        <td><strong>Work Request Status:</strong><br/>[Accept/Reject]</td>
    </tr>
</table>
<br/>

<!-- ========================= 
     SECTION D2: Remark Details 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">D2</td>
        <td class="section-header">Remark Details [Manual Entry]</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td style="height:30mm;">&nbsp;</td>
    </tr>
</table>
<br/>

<!-- ========================= 
     SECTION 8.A: Work Order Details 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">A</td>
        <td class="section-header">Work Order Details</td>
    </tr>';

            // Build Work Order “A” fields
            $woNumber = 'WO' . substr('GFMHQ' . date('ymd'), 0, 9) . str_pad($this->woTaskId, 5, '0', STR_PAD_LEFT);
            $woStatus = (!empty($woTask['wo_task_time_executed'])) ? 'Completed' : 'Open';

            $locName = $arrSiteName[intval($woTask['site_id'])];
            $locCode = '[Manual Entry]';  // adjust if you have a database field

            $assetName = '[Select from System or free text]';
            $assetCode = '[System Generated]';

            $woSeverity = $arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))];
            $woDueTime  = '';
            if (!empty($woTask['wo_task_time_assigned']) && !empty($woTask['wo_task_severity'])) {
                $assignedDt = new DateTime($woTask['wo_task_time_assigned']);
                $dueDt = clone $assignedDt;
                $dueDt->modify('+' . $arrDue[intval($woTask['wo_task_severity'])] . ' hour');
                $woDueTime = $dueDt->format('j/n/Y g:i:sa');
            }

            $html .= '
    <tr>
        <td><strong>Work Order No:</strong><br/>' . htmlspecialchars($woNumber) . '</td>
        <td><strong>Status:</strong><br/>' . htmlspecialchars($woStatus) . '</td>
    </tr>
    <tr>
        <td><strong>Work Request No:</strong><br/>' . htmlspecialchars($woTask['wo_task_no']) . '</td>
        <td><strong>Category:</strong><br/>' . htmlspecialchars($arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'],0))]) . '</td>
    </tr>
    <tr>
        <td><strong>Location Name:</strong><br/>' . htmlspecialchars($locName) . '</td>
        <td><strong>Location Code:</strong><br/>' . htmlspecialchars($locCode) . '</td>
    </tr>
    <tr>
        <td><strong>Asset Name:</strong><br/>' . htmlspecialchars($assetName) . '</td>
        <td><strong>Asset Code:</strong><br/>' . htmlspecialchars($assetCode) . '</td>
    </tr>
    <tr>
        <td><strong>Severity:</strong><br/>' . htmlspecialchars($woSeverity) . '</td>
        <td><strong>WO Due Date / Time:</strong><br/>' . htmlspecialchars($woDueTime) . '</td>
    </tr>
    <tr>
        <td colspan="2"><strong>Complaint Description:</strong><br/><div style="height:20mm;">&nbsp;</div></td>
    </tr>
</table>
<br/>

<!-- ========================= 
     SECTION 8.B1: Work Assignment Details 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">B1</td>
        <td class="section-header">Work Assignment Details [Details of task issuer and receiver]</td>
    </tr>
    <tr>
        <td><strong>Received By:</strong><br/>[System Generated]</td>
        <td><strong>Assigned To:</strong><br/>[Select from System]</td>
    </tr>
    <tr>
        <td><strong>Date Assigned:</strong><br/>[System Generated]</td>
        <td><strong>Phone No:</strong><br/>[System Generated]</td>
    </tr>
</table>
<br/>

<!-- ========================= 
     SECTION 8.B2: Support Personnel 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">B2</td>
        <td class="section-header">Support Personnel [Team members involved in execution]</td>
    </tr>
    <tr>
        <td style="width:8mm; text-align:center;"><strong>No.</strong></td>
        <td><strong>Name</strong></td>
    </tr>';

            // List support personnel or one blank row
            $woAssists = Class_db::getInstance()->db_select(
                'wo_task_assist',
                ['wo_task_id' => $this->woTaskId]
            );
            if (!empty($woAssists)) {
                foreach ($woAssists as $idx => $assist) {
                    $assistName = htmlspecialchars($arrUserFullName[intval($assist['user_id'])]);
                    $rowNo = $idx + 1;
                    $html .= '
    <tr>
        <td style="text-align:center;">' . $rowNo . '</td>
        <td>' . $assistName . '</td>
    </tr>';
                }
            } else {
                $html .= '
    <tr>
        <td style="text-align:center;">&nbsp;</td>
        <td>&nbsp;</td>
    </tr>';
            }

            $html .= '
</table>
<br/>

<!-- ========================= 
     SECTION 8.C: Material Details 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">C</td>
        <td class="section-header">Material Details [Parts or materials issued / returned]</td>
    </tr>
    <tr>
        <td style="width:25%;"><strong>Part No.</strong></td>
        <td style="width:40%;"><strong>Item Description</strong></td>
        <td style="width:10%;"><strong>Issue Type</strong></td>
        <td style="width:10%;"><strong>Unit</strong></td>
        <td style="width:7.5%;"><strong>Qty Taken</strong></td>
        <td style="width:7.5%;"><strong>Qty Return</strong></td>
    </tr>';

            // Draw 5 blank rows for manual entry
            for ($i = 0; $i < 5; $i++) {
                $html .= '
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>';
            }

            $html .= '
</table>
<br/>

<!-- ========================= 
     SECTION 8.D: Work Execution Details 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">D</td>
        <td class="section-header">Work Execution Details [Action duration, task notes, timeline]</td>
    </tr>';

            // Calculate execution details
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
                if (preg_match('/(\d+)\s*hour/i', $duration, $m)) {
                    $hoursTaken = intval($m[1]);
                } else {
                    $hoursTaken = 0;
                }
                $allowedHours = intval($arrDue[intval($woTask['wo_task_severity'])]);
                $statusWO = ($hoursTaken <= $allowedHours ? 'Within' : 'Exceed');
            }

            $html .= '
    <tr>
        <td><strong>Start Date &amp; Time:</strong><br/>' . htmlspecialchars($startDT) . '</td>
        <td colspan="2"><strong>End Date &amp; Time:</strong><br/>' . htmlspecialchars($endDT) . '</td>
    </tr>
    <tr>
        <td><strong>Duration:</strong><br/>' . htmlspecialchars($duration) . '</td>
        <td colspan="2"><strong>Status:</strong><br/>' . htmlspecialchars($statusWO) . '</td>
    </tr>
</table>
<br/>

<!-- ========================= 
     SECTION 8.E: Work Completion & Verification 
     ========================= -->
<table>
    <tr>
        <td class="section-header" style="width:8mm; text-align:center;">E</td>
        <td class="section-header">Work Completion &amp; Verification [Sign‐off &amp; rating]</td>
    </tr>
    <tr>
        <!-- Box 1: Serviced By -->
        <td style="width:33.33%; height:50mm; vertical-align: top;">
            <strong>Serviced By:</strong><br/><br/><br/>
            .........................................<br/>
            <strong>Name:</strong> ' . htmlspecialchars($arrUserFullName[intval($woTask['wo_task_fixed_by'] ?? 0)]) . '<br/>
            <strong>Date / Time:</strong> ' . ($woTask['wo_task_time_executed'] 
                ? htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed'])) 
                : '') . '
        </td>
        <!-- Box 2: Checked By -->
        <td style="width:33.33%; height:50mm; vertical-align: top;">
            <strong>Checked By:</strong><br/><br/><br/>
            .........................................<br/>
            <strong>Name:</strong> ' . htmlspecialchars($arrUserFullName[intval($woTask['wo_task_verified_by'] ?? 0)]) . '<br/>
            <strong>Date / Time:</strong> ' . ($woTask['wo_task_time_verified'] 
                ? htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified'])) 
                : '') . '
        </td>
        <!-- Box 3: Verified By -->
        <td style="width:33.33%; height:50mm; vertical-align: top;">
            <strong>Verified By:</strong><br/><br/><br/>
            .........................................<br/>
            <strong>Name:</strong> ' . htmlspecialchars($arrUserFullName[intval($woTask['wo_task_verified_by'] ?? 0)]) . '<br/>
            <strong>Date / Time:</strong> ' . ($woTask['wo_task_time_verified'] 
                ? htmlspecialchars($this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified'])) 
                : '') . '
        </td>
    </tr>
    <tr>
        <td colspan="3" style="padding:6px;">
            <strong>Satisfactory Level:</strong> [Choose 1–5: 1=Very Dissatisfied … 5=Very Satisfied] ' 
            . (!empty($woTask['wo_task_rate']) ? htmlspecialchars($woTask['wo_task_rate'] . ' / 5') : '') . '
        </td>
    </tr>
</table>
<br/>

<!-- ========================= 
     SECTION 8.J: Photo Documentation (Before / During / After) 
     ========================= -->

<!-- J1: Photo Documentation (Before) -->
<table>
    <tr>
        <td class="section-header" colspan="2">Photo Documentation (Before) [Visual proof for each repair stage]</td>
    </tr>';

            if (!empty($imgBefore)) {
                foreach ($imgBefore as $img) {
                    $imgPath = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                    $desc    = htmlspecialchars($this->fn_general->clear_null($img['wo_task_upload_desc']));
                    $ts      = $this->fn_general->convertDateToDisplay($img['wo_task_upload_timestamp']);
                    $gps     = htmlspecialchars($img['wo_task_upload_longitude'] . ', ' . $img['wo_task_upload_latitude']);

                    $html .= '
    <tr>
        <td style="width:48%; text-align:center; border:1px solid #000; padding:4px;">
            <img src="' . $imgPath . '" style="max-width:100%; height:auto;" />
        </td>
        <td style="width:52%; border:1px solid #000; padding:4px;">
            <strong>Description:</strong> ' . $desc . '<br/>
            <strong>Date / Time Taken:</strong> ' . $ts . '<br/>
            <strong>GPS Coordinates:</strong> ' . $gps . '
        </td>
    </tr>';
                }
            } else {
                $html .= '
    <tr>
        <td style="width:48%; height:30mm;">&nbsp;</td>
        <td style="width:52%; height:30mm;">&nbsp;</td>
    </tr>';
            }

            $html .= '
</table>
<br/>

<!-- J2: Photo Documentation (During) -->
<table>
    <tr>
        <td class="section-header" colspan="2">Photo Documentation (During) [Visual proof for each repair stage]</td>
    </tr>';

            if (!empty($imgDuring)) {
                foreach ($imgDuring as $img) {
                    $imgPath = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                    $desc    = htmlspecialchars($this->fn_general->clear_null($img['wo_task_upload_desc']));
                    $ts      = $this->fn_general->convertDateToDisplay($img['wo_task_upload_timestamp']);
                    $gps     = htmlspecialchars($img['wo_task_upload_longitude'] . ', ' . $img['wo_task_upload_latitude']);

                    $html .= '
    <tr>
        <td style="width:48%; text-align:center; border:1px solid #000; padding:4px;">
            <img src="' . $imgPath . '" style="max-width:100%; height:auto;" />
        </td>
        <td style="width:52%; border:1px solid #000; padding:4px;">
            <strong>Description:</strong> ' . $desc . '<br/>
            <strong>Date / Time Taken:</strong> ' . $ts . '<br/>
            <strong>GPS Coordinates:</strong> ' . $gps . '
        </td>
    </tr>';
                }
            } else {
                $html .= '
    <tr>
        <td style="width:48%; height:30mm;">&nbsp;</td>
        <td style="width:52%; height:30mm;">&nbsp;</td>
    </tr>';
            }

            $html .= '
</table>
<br/>

<!-- J3: Photo Documentation (After) -->
<table>
    <tr>
        <td class="section-header" colspan="2">Photo Documentation (After) [Visual proof for each repair stage]</td>
    </tr>';

            if (!empty($imgAfter)) {
                foreach ($imgAfter as $img) {
                    $imgPath = $img['upload_folder'] . '/' . $img['upload_filename'] . '.' . $img['upload_extension'];
                    $desc    = htmlspecialchars($this->fn_general->clear_null($img['wo_task_upload_desc']));
                    $ts      = $this->fn_general->convertDateToDisplay($img['wo_task_upload_timestamp']);
                    $gps     = htmlspecialchars($img['wo_task_upload_longitude'] . ', ' . $img['wo_task_upload_latitude']);

                    $html .= '
    <tr>
        <td style="width:48%; text-align:center; border:1px solid #000; padding:4px;">
            <img src="' . $imgPath . '" style="max-width:100%; height:auto;" />
        </td>
        <td style="width:52%; border:1px solid #000; padding:4px;">
            <strong>Description:</strong> ' . $desc . '<br/>
            <strong>Date / Time Taken:</strong> ' . $ts . '<br/>
            <strong>GPS Coordinates:</strong> ' . $gps . '
        </td>
    </tr>';
                }
            } else {
                $html .= '
    <tr>
        <td style="width:48%; height:30mm;">&nbsp;</td>
        <td style="width:52%; height:30mm;">&nbsp;</td>
    </tr>';
            }

            $html .= '
</table>
';  // End of HTML template

            // 7) Instantiate TCPDF and render the HTML
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
            // Write the full HTML at once—TCPDF will handle wrapping and page breaks automatically
            $pdf->writeHTML($html, true, false, true, false, '');

            // 8) Save the generated PDF file
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

            // 9) Insert/update sys_pdf record, then update wo_task
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
