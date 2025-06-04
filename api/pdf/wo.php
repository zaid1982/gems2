<?php
class MYPDF_wo extends TCPDF {
    // Page footer (same as before)
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

    // Magic getters/setters (same as before)...
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
     *   - Generates one PDF file containing both the WR (Work Request) and WO (Work Order)
     *     sections, laid out exactly as per the attached DOCX template.
     *   - All the “Manual Entry” spots are left blank or commented so you can fill in later.
     */
    public function create_pdf () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId Empty');
            }

            // fetch data for this WO task (same as before)
            $woTask = Class_db::getInstance()->db_select_single(
                'wo_task', 
                array('wo_task_id' => $this->woTaskId), 
                null, 1
            );
            if (!$woTask) {
                throw new Exception('WO Task ID not found');
            }

            // fetch the “creator” user profile (for WR “Reported by”)
            $userProfile = Class_db::getInstance()->db_select_single(
                'sys_user_profile', 
                array(
                    'user_id' => $woTask['wo_task_created_by'], 
                    'user_profile_status' => '1'
                ), 
                null, 1
            );
            $arrUserFullName = $this->fn_general->getUserFullName(); // array[user_id] => “Full Name”

            // fetch severity mapping & site mapping & category mapping (same as before)
            $arrSiteName = $this->fn_general->getSiteName();        // array[site_id] => “Site Name”
            $arrCategory  = array('', 'Complaint', 'Finding', 'Request', 'Breakdown', 'Defect', 'Public Complaint');
            $arrSeverity  = $this->fn_general->getSeverityName(); // array[severity_id] => “Non-Critical” etc.

            // SLA & due arrays (same logic as before, but now also used in WR lineup)
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
                array('client_id'=>$clientId)
            );
            foreach ($arrClientSeverity as $clientSeverity) {
                $sevKey = intval($clientSeverity['severity_id']);
                $arrSla[$sevKey] = $clientSeverity['client_severity_hour'].' hours';
                $arrDue[$sevKey] = $clientSeverity['client_severity_hour'];
            }

            // ============================
            // 1) START: Instantiate PDF
            // ============================
            $pdf = new MYPDF_wo(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // document info
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Muhammad Zaid');
            $pdf->SetTitle('GEMS 2.0 WR & WO');
            $pdf->SetSubject('GEMS 2.0 WR & WO');

            // no default header; yes default footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(true);

            // fonts for header/footer (even though header is disabled)
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // margins & auto page break & image scale
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // add first page
            $pdf->AddPage();

            // -----------------------------------------------
            // HEADER: “WORK REQUEST (WR) & WORK ORDER (WO)”
            // -----------------------------------------------
            $pdf->SetFont('helvetica', 'B', 18);
            $pdf->Cell(0, 10, 'WORK REQUEST (WR) &', 0, 1, 'C');
            $pdf->Cell(0, 10, 'WORK ORDER (WO)', 0, 1, 'C');
            $pdf->Ln(4);

            // draw a thin line under header
            $pdf->SetLineWidth(0.2);
            $pdf->Line(PDF_MARGIN_LEFT, $pdf->GetY(), $pdf->getPageWidth() - PDF_MARGIN_RIGHT, $pdf->GetY());
            $pdf->Ln(6);

            // ============================================================
            // 2) WORK REQUEST (WR) SECTION
            // ------------------------------------------------------------
            //   A. Complaint Details
            //   B1. Description of Complaint
            //   B2. Complaint Images
            //   C1. Work Assessment Details
            //   C2. Response Images
            //   D1. Validation Details
            //   D2. Remark Details
            // ============================================================

            // -------------------------------------------------------------------
            // WR – A) “Complaint Details”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->SetTextColor(0);

            // Section A header row
            $pdf->Cell(8, 8, 'A', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Complaint Details [User Details: Public & Client for Complaints or Internal: for Self-Finding]', 1, 0, 'L', 1);
            $pdf->Ln();

            // Section A – row 1: “Reported by / Phone No”
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(30, 6, 'Reported by:', 1, 0, 'R');
            // Actual “Reported by” from DB or free‐form if self‐finding
            $pdf->Cell(60, 6, 
                $arrUserFullName[intval($woTask['wo_task_created_by'])] 
                // If manual entry override, leave as blank string 
                // or replace above with '[Manual Entry]' 
            , 1, 0, 'L');
            $pdf->Cell(35, 6, 'Phone No:', 1, 0, 'R');
            $pdf->Cell(55, 6, 
                // either real phone or placeholder
                $this->fn_general->clear_null($userProfile['user_contact_no']) 
                // if manual: '[Manual Entry]' 
            , 1, 0, 'L');
            $pdf->Ln();

            // Section A – row 2: “Email / Reported Date/Time”
            $pdf->Cell(30, 6, 'Email:', 1, 0, 'R');
            $pdf->Cell(60, 6, 
                $this->fn_general->clear_null($userProfile['user_email']) 
                // if manual: '[Manual Entry]'
            , 1, 0, 'L');
            $pdf->Cell(35, 6, 'Reported Date / Time:', 1, 0, 'R');
            $pdf->Cell(55, 6, 
                $this->fn_general->convertDateToDisplay($woTask['wo_task_time_created']) 
                // if you want a different format, adjust here
            , 1, 0, 'L');
            $pdf->Ln();

            // Section A – row 3: “Category / Severity”
            $pdf->Cell(30, 6, 'Category:', 1, 0, 'R');
            $pdf->Cell(60, 6, 
                $arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'], 0))] 
                // if manual: '[Select from System]'
            , 1, 0, 'L');
            $pdf->Cell(35, 6, 'Severity:', 1, 0, 'R');
            $pdf->Cell(55, 6, 
                $arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))] 
                // or '[Select from System]'
            , 1, 0, 'L');
            $pdf->Ln();

            // Section A – row 4: “Work Request No / Location Complaint”
            $pdf->Cell(30, 6, 'Work Request No:', 1, 0, 'R');
            $pdf->Cell(60, 6, 
                // You’ll have to generate WR number yourself (not stored in wo_task).
                // For now: placeholder or map from wo_task_no
                'WR' . substr('GFMHQ'.date('ymd'), 0, 9) . str_pad($this->woTaskId, 5, '0', STR_PAD_LEFT)
                // e.g. 'WRGFMHQ25042900001'
            , 1, 0, 'L');
            $pdf->Cell(35, 6, 'Location Complaint:', 1, 0, 'R');
            $pdf->Cell(55, 6, 
                $this->fn_general->clear_null($woTask['wo_task_location']) 
                // or '[Select from System]'
            , 1, 0, 'L');
            $pdf->Ln(12);

            // -------------------------------------------------------------------
            // WR – B1) “Description of Complaint”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'B1', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Description of Complaint [Manual Entry]', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            // Dynamically compute height of multi‐line complaint
            $maxnocells = 0;
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $pdf->MultiCell(8, 4, '', 0, 'L', 0, 0);
            $pdf->MultiCell(172, 4, '', 0, 'L', 0, 0);
            $pdf->Ln();
            // measure
            $cellcount = $pdf->MultiCell(8, 4, '', 0, 'L', 0, 0);
            if ($cellcount > $maxnocells) {
                $maxnocells = $cellcount;
            }
            $cellcount = $pdf->MultiCell(172, 4, 
                // If you want DB‐driven complaint, use:
                // $this->fn_general->clear_null($woTask['wo_task_complaint'])
                // Otherwise leave blank for manual entry.
                '', 0, 'L', 0, 0
            );
            if ($cellcount > $maxnocells) {
                $maxnocells = $cellcount;
            }
            // draw bordered box of appropriate height
            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell(8, ($maxnocells * 4) + 8, '', 1, 'L', 0, 0);
            $pdf->MultiCell(172, ($maxnocells * 4) + 8, '', 1, 'L', 0, 0);
            $pdf->Ln(12);

            // -------------------------------------------------------------------
            // WR – B2) “Complaint Images”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'B2', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Complaint Images [Complain from User]', 1, 0, 'L', 1);
            $pdf->Ln();

            // fetch complaint‐type uploads (upload_type = 1) from mw_wo_upload
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
                foreach ($img_complaint as $key => $img_display) {
                    // if near bottom, add new page
                    if ($pdf->GetY() > 200) {
                        $pdf->AddPage();
                    }
                    // left placeholder
                    $pdf->writeHTMLCell(8, 65, '', '', '', 1, 0, 0, false, '', false);
                    // image cell (92×65)
                    $imgPath = $img_display['upload_folder'].'/'.
                               $img_display['upload_filename'].'.'.
                               $img_display['upload_extension'];
                    $pdf->writeHTMLCell(
                        92, 65, '', '', 
                        '<br/><br/><img src="' . $imgPath . '" height="200" />', 
                        1, 0, 0, true, 'C', false
                    );
                    // description cell (80×65)
                    $descHtml  = '<br/><br/>Description: ' . 
                                 $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                                 '<br/>Date / Time Taken: ' . 
                                 $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                                 '<br/>GPS Coordinates: ' . 
                                 $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) . ', ' .
                                 $this->fn_general->clear_null($img_display['wo_task_upload_latitude']);
                    $pdf->writeHTMLCell(80, 65, '', '', $descHtml, 1, 0, 0, true, 'L', false);
                    $pdf->Ln();
                }
            } else {
                // no complaint images: draw one empty row
                $pdf->Cell(8, 12, '', 1, 0, 'C', 0);
                $pdf->Cell(172, 12, '', 1, 0, 'L', 0);
                $pdf->Ln();
            }
            $pdf->Ln(4);

            // -------------------------------------------------------------------
            // WR – C1) “Work Assessment Details”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'C1', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Work Assessment Details [Selected by P.I.C. to verify the complaint]', 1, 0, 'L', 1);
            $pdf->Ln();

            $picName = '';
            $picEmail = '';
            $dueTime = '';
            $assignTime = '';
            $wrVerifyTime = '';
            $fixedTime = '';
            if (!empty($woTask['wo_task_assigned_to'])) {
                $picName = $arrUserFullName[intval($woTask['wo_task_assigned_to'])];
                $userProfileTech = Class_db::getInstance()->db_select_single(
                    'sys_user_profile', 
                    array('user_id'=>$woTask['wo_task_assigned_to'], 'user_profile_status'=>'1'), 
                    null, 1
                );
                $picEmail = $this->fn_general->clear_null($userProfileTech['user_email']);

                // compute dueTime = created + SLA hours
                $createdTime = new DateTime($woTask['wo_task_time_created']);
                if (!empty($woTask['wo_task_severity'])) {
                    $dueTimeDt = clone $createdTime;
                    $dueTimeDt->modify('+'.$arrDue[intval($woTask['wo_task_severity'])].' hour');
                    $dueTime = $dueTimeDt->format('j/n/Y g:i:sa');
                }

                // assignTime
                if (!empty($woTask['wo_task_time_assigned'])) {
                    $assignedDt = new DateTime($woTask['wo_task_time_assigned']);
                    $assignTime = $assignedDt->format('j/n/Y g:i:sa');
                }
                // WR verify time
                if (!empty($woTask['wo_task_time_wr_verified'])) {
                    $wrVerDt = new DateTime($woTask['wo_task_time_wr_verified']);
                    $wrVerifyTime = $wrVerDt->format('j/n/Y g:i:sa');
                }
                // fixed time (when task executed)
                if (!empty($woTask['wo_task_time_executed'])) {
                    $execDt = new DateTime($woTask['wo_task_time_executed']);
                    $fixedTime = $execDt->format('j/n/Y g:i:sa');
                }
            }

            // Work Assessment grid rows
            $pdf->SetFont('helvetica', '', 9);
            // Row 1: “Person in Charge / SLA Respond Time”
            $pdf->Cell(30, 5, 'Person in Charge:', 1, 0, 'R');
            $pdf->Cell(60, 5, ($picName ? $picName : '[Select from System]'), 1, 0, 'L');
            $pdf->Cell(35, 5, 'SLA Respond Time:', 1, 0, 'R');
            $pdf->Cell(55, 5, ($picName && $dueTime ? $arrSla[intval($woTask['wo_task_severity'])] : '[Select from System]'), 1, 0, 'L');
            $pdf->Ln();

            // Row 2: “Email / WR Due Date Time”
            $pdf->Cell(30, 5, 'Email:', 1, 0, 'R');
            $pdf->Cell(60, 5, ($picName ? $picEmail : ''), 1, 0, 'L');
            $pdf->Cell(35, 5, 'WR Due Date Time:', 1, 0, 'R');
            $pdf->Cell(55, 5, ($dueTime ? $dueTime : ''), 1, 0, 'L');
            $pdf->Ln();

            // Row 3: “Respond Date / Duration / Respond Status”
            if (!empty($woTask['wo_task_is_wr']) && $woTask['wo_task_is_wr'] === '1') {
                // compute respond duration text
                $respondDuration = '';
                if (!empty($woTask['wo_task_time_created']) && !empty($woTask['wo_task_time_assigned'])) {
                    $respondDuration = $this->fn_general->timeDiff(
                        $woTask['wo_task_time_created'], 
                        $woTask['wo_task_time_assigned']
                    );
                }
                // compute respond status
                $statusText = '';
                if ($assignTime && $dueTime) {
                    $dueDt = new DateTime($dueTime);
                    $assignDt = new DateTime($assignTime);
                    if ($assignDt <= $dueDt) {
                        $statusText = 'Within';
                    } else {
                        $statusText = 'Exceed';
                    }
                }
                $pdf->Cell(30, 5, 'Respond Date / Duration:', 1, 0, 'R');
                $pdf->Cell(60, 5, ($assignTime ? $assignTime . ', ' . $respondDuration : ''), 1, 0, 'L');
                $pdf->Cell(35, 5, 'Respond Status:', 1, 0, 'R');
                $pdf->Cell(55, 5, ($statusText ? $statusText : ''), 1, 0, 'L');
                $pdf->Ln();
            }

            $pdf->Ln(4);

            // -------------------------------------------------------------------
            // WR – C2) “Response Images”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'C2', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Response Images [P.I.C. verification of complaint]', 1, 0, 'L', 1);
            $pdf->Ln();

            // fetch response‐type uploads (upload_type = 2)
            $img_response = array();
            foreach ($woUploads as $woUpload) {
                if ($woUpload['wo_task_upload_type'] === '2') {
                    $img_response[] = $woUpload;
                }
            }
            $pdf->SetFont('helvetica', '', 9);
            if (!empty($img_response)) {
                foreach ($img_response as $img_display) {
                    if ($pdf->GetY() > 200) {
                        $pdf->AddPage();
                    }
                    // left placeholder
                    $pdf->writeHTMLCell(8, 65, '', '', '', 1, 0, 0, false, '', false);
                    // image
                    $imgPath = $img_display['upload_folder'].'/'.
                               $img_display['upload_filename'].'.'.
                               $img_display['upload_extension'];
                    $pdf->writeHTMLCell(
                        92, 65, '', '', 
                        '<br/><br/><img src="' . $imgPath . '" height="200" />', 
                        1, 0, 0, true, 'C', false
                    );
                    // description
                    $descHtml  = '<br/><br/>Description: ' . 
                                 $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                                 '<br/>Date / Time Taken: ' . 
                                 $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                                 '<br/>Longitude / Latitude: ' . 
                                 $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) . ', ' .
                                 $this->fn_general->clear_null($img_display['wo_task_upload_latitude']);
                    $pdf->writeHTMLCell(80, 65, '', '', $descHtml, 1, 0, 0, true, 'L', false);
                    $pdf->Ln();
                }
            } else {
                // empty placeholder row
                $pdf->Cell(8, 12, '', 1, 0, 'C', 0);
                $pdf->Cell(172, 12, '', 1, 0, 'L', 0);
                $pdf->Ln();
            }
            $pdf->Ln(4);

            // -------------------------------------------------------------------
            // WR – D1) “Validation Details”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'D1', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Validation Details [Who issues / assigns the WR to P.I.C.]', 1, 0, 'L', 1);
            $pdf->Ln();

            // If you have “validation” data in your DB, fetch it/match it here.
            // For now: fill placeholders. e.g. “Validation by: Azlan Bin Tuah” etc.
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

            // -------------------------------------------------------------------
            // WR – D2) “Remark Details”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'D2', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Remark Details [Manual Entry]', 1, 0, 'L', 1);
            $pdf->Ln();

            // dynamic height “remark” box
            $pdf->SetFont('helvetica', '', 9);
            $maxnocells = 0;
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $pdf->MultiCell(8, 4, '', 0, 'L', 0, 0);
            $pdf->MultiCell(172, 4, '', 0, 'L', 0, 0);
            $pdf->Ln();
            $cellcount = $pdf->MultiCell(8, 4, '', 0, 'L', 0, 0);
            if ($cellcount > $maxnocells) {
                $maxnocells = $cellcount;
            }
            $cellcount = $pdf->MultiCell(172, 4, '', 0, 'L', 0, 0);
            if ($cellcount > $maxnocells) {
                $maxnocells = $cellcount;
            }
            $pdf->SetXY($startX, $startY);
            $pdf->MultiCell(8, ($maxnocells * 4) + 8, '', 1, 'L', 0, 0);
            $pdf->MultiCell(172, ($maxnocells * 4) + 8, '', 1, 'L', 0, 0);
            $pdf->Ln(12);

            // ======================================
            // 3) WORK ORDER (WO) SECTION (starts here)
            // ------------------------------------------------------------------------------------------------
            //   A) Work Order Details
            //   B1) Work Assignment Details
            //   B2) Support Personnel
            //   C) Material Details
            //   D) Work Execution Details
            //   E) Work Completion & Verification
            //   J) Photo Documentation (Before/During/After)
            // ======================================

            // -------------------------------------------------------------------
            // WO – A) “Work Order Details”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'A', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Work Order Details', 1, 0, 'L', 1);
            $pdf->Ln();

            // Row 1: “Work Order No / Status”
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(30, 6, 'Work Order No:', 1, 0, 'R');
            $pdf->Cell(60, 6, 
                'WO' . substr('GFMHQ'.date('ymd'), 0, 9) . str_pad($this->woTaskId, 5, '0', STR_PAD_LEFT)
                // e.g. 'WOGFMHQ25042900001'
            , 1, 0, 'L');
            $pdf->Cell(35, 6, 'Status:', 1, 0, 'R');
            $pdf->Cell(55, 6, 
                // if executed date exists => “Completed” else blank or “Open”
                (!empty($woTask['wo_task_time_executed']) ? 'Completed' : 'Open') 
            , 1, 0, 'L');
            $pdf->Ln();

            // Row 2: “Work Request No / Category”
            $pdf->Cell(30, 6, 'Work Request No:', 1, 0, 'R');
            $pdf->Cell(60, 6, 
                // same WR number as above
                'WR' . substr('GFMHQ'.date('ymd'), 0, 9) . str_pad($this->woTaskId, 5, '0', STR_PAD_LEFT)
            , 1, 0, 'L');
            $pdf->Cell(35, 6, 'Category:', 1, 0, 'R');
            $pdf->Cell(55, 6, 
                $arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'], 0))] 
            , 1, 0, 'L');
            $pdf->Ln();

            // Row 3: “Location Name / Location Code”
            $pdf->Cell(30, 6, 'Location Name:', 1, 0, 'R');
            $pdf->Cell(60, 6, 
                $arrSiteName[intval($woTask['site_id'])] 
            , 1, 0, 'L');
            $pdf->Cell(35, 6, 'Location Code:', 1, 0, 'R');
            $pdf->Cell(55, 6, 
                // if you store a separate code, put it here; else blank
                ''
            , 1, 0, 'L');
            $pdf->Ln();

            // Row 4: “Asset Name / Asset Code”
            $pdf->Cell(30, 6, 'Asset Name:', 1, 0, 'R');
            $pdf->Cell(60, 6, '[Select from System or free text]', 1, 0, 'L');
            $pdf->Cell(35, 6, 'Asset Code:', 1, 0, 'R');
            $pdf->Cell(55, 6, '[System Generated]', 1, 0, 'L');
            $pdf->Ln();

            // Row 5: “Severity / WO Due Date/Time”
            $pdf->Cell(30, 6, 'Severity:', 1, 0, 'R');
            $pdf->Cell(60, 6, 
                $arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))] 
            , 1, 0, 'L');
            $pdf->Cell(35, 6, 'WO Due Date / Time:', 1, 0, 'R');
            $pdf->Cell(55, 6, 
                // if you recalc dueTime differently for WO, override here
                ($dueTime ? $dueTime : '') 
            , 1, 0, 'L');
            $pdf->Ln();

            // Row 6: “Complaint Description”
            $pdf->Cell(30, 6, 'Complaint Description:', 1, 0, 'R');
            $pdf->Cell(150, 6, 
                // from WR complaint field
                '', 1, 0, 'L'
            );
            $pdf->Ln(12);

            // -------------------------------------------------------------------
            // WO – B1) “Work Assignment Details”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'B1', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Work Assignment Details [Details of task issuer and receiver]', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            // Row 1: “Received By / Assigned To / Date Assigned / Phone No”
            $pdf->Cell(30, 6, 'Received By:', 1, 0, 'R');
            $pdf->Cell(60, 6, '[System Generated]', 1, 0, 'L'); // e.g. who submitted the WR
            $pdf->Cell(30, 6, 'Assigned To:', 1, 0, 'R');
            $pdf->Cell(50, 6, '[Select from System]', 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 6, 'Date Assigned:', 1, 0, 'R');
            $pdf->Cell(60, 6, '[System Generated]', 1, 0, 'L');
            $pdf->Cell(30, 6, 'Phone No:', 1, 0, 'R');
            $pdf->Cell(50, 6, '[System Generated]', 1, 0, 'L');
            $pdf->Ln(12);

            // -------------------------------------------------------------------
            // WO – B2) “Support Personnel”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'B2', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Support Personnel [Team members involved in execution]', 1, 0, 'L', 1);
            $pdf->Ln();

            // fetch assisted technicians (same as before, but now put into Support Personnel)
            $woAssists = Class_db::getInstance()->db_select(
                'wo_task_assist', 
                array('wo_task_id' => $this->woTaskId)
            );
            $pdf->SetFont('helvetica', '', 9);
            // draw header row for the Support Personnel table
            $pdf->Cell(8, 6, 'No.', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, 'Name', 1, 0, 'C', 1);
            $pdf->Ln();
            for ($i = 0; $i < count($woAssists); $i++) {
                $assistName = $arrUserFullName[intval($woAssists[$i]['user_id'])];
                $pdf->Cell(8, 6, ($i + 1), 1, 0, 'C', 0);
                $pdf->Cell(172, 6, $assistName, 1, 0, 'L', 0);
                $pdf->Ln();
            }
            // If fewer rows than a fixed size are desired, add blank lines here.
            $pdf->Ln(4);

            // -------------------------------------------------------------------
            // WO – C) “Material Details”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'C', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Material Details [Parts or materials issued / returned]', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            // draw a header row for the materials table
            $pdf->Cell(30, 6, 'Part No.', 1, 0, 'C', 1);
            $pdf->Cell(70, 6, 'Item Description', 1, 0, 'C', 1);
            $pdf->Cell(20, 6, 'Issue Type', 1, 0, 'C', 1);
            $pdf->Cell(15, 6, 'Unit', 1, 0, 'C', 1);
            $pdf->Cell(25, 6, 'Qty Taken', 1, 0, 'C', 1);
            $pdf->Cell(25, 6, 'Qty Return', 1, 0, 'C', 1);
            $pdf->Ln();
            // leave blank rows for manual entry or integrate with inventory DB
            for ($row=0; $row<5; $row++) {
                $pdf->Cell(30, 6, '', 1, 0, 'L', 0);
                $pdf->Cell(70, 6, '', 1, 0, 'L', 0);
                $pdf->Cell(20, 6, '', 1, 0, 'C', 0);
                $pdf->Cell(15, 6, '', 1, 0, 'C', 0);
                $pdf->Cell(25, 6, '', 1, 0, 'C', 0);
                $pdf->Cell(25, 6, '', 1, 0, 'C', 0);
                $pdf->Ln();
            }
            $pdf->Ln(4);

            // -------------------------------------------------------------------
            // WO – D) “Work Execution Details”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'D', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Work Execution Details [Action duration, task notes, timeline]', 1, 0, 'L', 1);
            $pdf->Ln();

            // Row 1: “Start Date/Time / End Date/Time / Duration / Status”
            $pdf->SetFont('helvetica', '', 9);
            // compute duration if possible
            $durationText = '';
            if (!empty($woTask['wo_task_time_assigned']) && !empty($woTask['wo_task_time_executed'])) {
                $durationText = $this->fn_general->timeDiff(
                    $woTask['wo_task_time_assigned'], 
                    $woTask['wo_task_time_executed']
                );
            }
            $pdf->Cell(40, 6, 'Start Date & Time:', 1, 0, 'R');
            $pdf->Cell(60, 6, 
                ($woTask['wo_task_time_assigned'] ? 
                    $this->fn_general->convertDateToDisplay($woTask['wo_task_time_assigned']) 
                    : '[System Generated]'
                ), 1, 0, 'L');
            $pdf->Cell(30, 6, 'End Date & Time:', 1, 0, 'R');
            $pdf->Cell(50, 6, 
                ($woTask['wo_task_time_executed'] ? 
                    $this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed']) 
                    : '[System Generated]'
                ), 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(40, 6, 'Duration:', 1, 0, 'R');
            $pdf->Cell(60, 6, ($durationText ? $durationText : ''), 1, 0, 'L');
            // compute status within SLA?
            $statusWO = '';
            if ($durationText && !empty($woTask['wo_task_severity'])) {
                // if durationText is hours:minutes or “13 Hours”…we’ll approximate 
                // (adjust logic if you store numeric hours in DB)
                $hoursTaken = 0;
                if (preg_match('/(\d+)\s*Hour/i', $durationText, $m)) {
                    $hoursTaken = intval($m[1]);
                }
                $allowedHours = intval($arrDue[intval($woTask['wo_task_severity'])]);
                $statusWO = ($hoursTaken <= $allowedHours ? 'Within' : 'Exceed');
            }
            $pdf->Cell(30, 6, 'Status:', 1, 0, 'R');
            $pdf->Cell(50, 6, ($statusWO ? $statusWO : ''), 1, 0, 'L');
            $pdf->Ln(12);

            // -------------------------------------------------------------------
            // WO – E) “Work Completion & Verification”
            // -------------------------------------------------------------------
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 8, 'E', 1, 0, 'C', 1);
            $pdf->Cell(172, 8, ' Work Completion & Verification [Sign‐off & rating]', 1, 0, 'L', 1);
            $pdf->Ln();

            // draw three signature columns: “Serviced By / Checked By / Verified By”
            $servicedBy = '';
            $checkedBy = '';
            $verifiedBy = '';
            // For simplicity, use the same “assigned_to” or map to real roles:
            if (!empty($woTask['wo_task_fixed_by'])) {
                $servicedBy = $arrUserFullName[intval($woTask['wo_task_fixed_by'])];
            }
            if (!empty($woTask['wo_task_verified_by'])) {
                $verifiedBy = $arrUserFullName[intval($woTask['wo_task_verified_by'])];
            }
            // “Checked By” you can map from another DB column if exists. For now, leave blank.

            // width of each signature cell = 60 (so three cells fit in 180 total)
            $pdf->MultiCell(60, 18,
                "Serviced By:\n\n\n" .
                "................................................\n" .
                "Name: " . ($servicedBy ? $servicedBy : '') . "\n" .
                "Date / Time: " . 
                ($woTask['wo_task_time_executed'] ? 
                    $this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed']) 
                    : ''
                ), 
            1, 'L', 0, 0);

            $pdf->MultiCell(60, 18,
                "Checked By:\n\n\n" .
                "................................................\n" .
                "Name: " . '', 
            1, 'L', 0, 0);

            $pdf->MultiCell(60, 18,
                "Verified By:\n\n\n" .
                "................................................\n" .
                "Name: " . ($verifiedBy ? $verifiedBy : '') . "\n" .
                "Date / Time: " . 
                ($woTask['wo_task_time_verified'] ? 
                    $this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified']) 
                    : ''
                ), 
            1, 'L', 0, 0);

            $pdf->Ln(16);

            // Compute rating placeholder or real rating from DB
            $ratingText = '';
            if (!empty($woTask['wo_task_rate'])) {
                $ratingText = $woTask['wo_task_rate'] . ' / 5';
            }
            // below the signature row, show “Satisfactory Level” selection lines
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6, 'Satisfactory Level: [Choose 1–5: 1=Very Dissatisfied … 5=Very Satisfied] ' . $ratingText, 0, 1, 'L');
            $pdf->Ln(4);

            // -------------------------------------------------------------------
            // WO – J) “Photo Documentation (Before / During / After)”
            // -------------------------------------------------------------------
            // gather images of types 3 (before), 4 (during), 5 (after). 
            // In your original code they were: 2=during? Actually in your first code: 
            // upload_type=2 ➔ ‘before’? upload_type=3 ➔ ‘during’? upload_type=4 ➔ ‘after’? 
            // Let’s assume:
            //    type=2 => “before”
            //    type=3 => “during”
            //    type=4 => “after”
            $img_before = array();
            $img_during = array();
            $img_after = array();
            foreach ($woUploads as $woUpload) {
                $t = $woUpload['wo_task_upload_type'];
                if ($t === '2') {
                    $img_before[] = $woUpload;
                } else if ($t === '3') {
                    $img_during[] = $woUpload;
                } else if ($t === '4') {
                    $img_after[] = $woUpload;
                }
            }

            // Add a new page if we’re too near bottom
            if ($pdf->GetY() > 220) {
                $pdf->AddPage();
            }

            // J1: Photo Documentation (Before)
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, 'Photo Documentation (Before) [Visual proof for each repair stage]', 1, 1, 'L', 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($img_before)) {
                foreach ($img_before as $img_display) {
                    if ($pdf->GetY() > 200) {
                        $pdf->AddPage();
                    }
                    // left placeholder cell (image)
                    $pdf->writeHTMLCell(60, 60, '', '', 
                        '<br/><br/><img src="' . 
                        ($img_display['upload_folder'].'/'.$img_display['upload_filename'].'.'.$img_display['upload_extension']) . 
                        '" height="200" />', 
                    1, 0, false, true, 'C');
                    // right cell (desc + timestamp + coords)
                    $descHtml  = '<br/><br/>Description: ' . 
                                 $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                                 '<br/>Date / Time Taken: ' . 
                                 $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                                 '<br/>GPS Coordinates: ' . 
                                 $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) . ', ' .
                                 $this->fn_general->clear_null($img_display['wo_task_upload_latitude']);
                    $pdf->writeHTMLCell(120, 60, '', '', $descHtml, 1, 1, false, true, 'L');
                    $pdf->Ln(2);
                }
            } else {
                // blank placeholder row
                $pdf->Cell(60, 12, '', 1, 0, 'C', 0);
                $pdf->Cell(120, 12, '', 1, 1, 'L', 0);
            }
            $pdf->Ln(4);

            // J2: Photo Documentation (During)
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, 'Photo Documentation (During) [Visual proof for each repair stage]', 1, 1, 'L', 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($img_during)) {
                foreach ($img_during as $img_display) {
                    if ($pdf->GetY() > 200) {
                        $pdf->AddPage();
                    }
                    $pdf->writeHTMLCell(60, 60, '', '', 
                        '<br/><br/><img src="' . 
                        ($img_display['upload_folder'].'/'.$img_display['upload_filename'].'.'.$img_display['upload_extension']) . 
                        '" height="200" />', 
                    1, 0, false, true, 'C');
                    $descHtml  = '<br/><br/>Description: ' . 
                                 $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                                 '<br/>Date / Time Taken: ' . 
                                 $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                                 '<br/>GPS Coordinates: ' . 
                                 $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) . ', ' .
                                 $this->fn_general->clear_null($img_display['wo_task_upload_latitude']);
                    $pdf->writeHTMLCell(120, 60, '', '', $descHtml, 1, 1, false, true, 'L');
                    $pdf->Ln(2);
                }
            } else {
                $pdf->Cell(60, 12, '', 1, 0, 'C', 0);
                $pdf->Cell(120, 12, '', 1, 1, 'L', 0);
            }
            $pdf->Ln(4);

            // J3: Photo Documentation (After)
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, 'Photo Documentation (After) [Visual proof for each repair stage]', 1, 1, 'L', 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($img_after)) {
                foreach ($img_after as $img_display) {
                    if ($pdf->GetY() > 200) {
                        $pdf->AddPage();
                    }
                    $pdf->writeHTMLCell(60, 60, '', '', 
                        '<br/><br/><img src="' . 
                        ($img_display['upload_folder'].'/'.$img_display['upload_filename'].'.'.$img_display['upload_extension']) . 
                        '" height="200" />', 
                    1, 0, false, true, 'C');
                    $descHtml  = '<br/><br/>Description: ' . 
                                 $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                                 '<br/>Date / Time Taken: ' . 
                                 $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                                 '<br/>GPS Coordinates: ' . 
                                 $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) . ', ' .
                                 $this->fn_general->clear_null($img_display['wo_task_upload_latitude']);
                    $pdf->writeHTMLCell(120, 60, '', '', $descHtml, 1, 1, false, true, 'L');
                    $pdf->Ln(2);
                }
            } else {
                $pdf->Cell(60, 12, '', 1, 0, 'C', 0);
                $pdf->Cell(120, 12, '', 1, 1, 'L', 0);
            }
            $pdf->Ln(4);

            // ============================
            // 4) SAVE & RECORD PDF
            // ============================
            $folder_code = floor(intval($this->woTaskId)/1000);
            $folder = 'pdf/wo/' . $folder_code;
            $result = $this->fn_general->folderExist($folder);
            if (!$result) {
                mkdir($folder, 0777, true);
            }
            $filename = 'wo_' . substr((10000000 + intval($this->woTaskId)), 1) . '.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : ' . $filename);

            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Environment : '.$environment);

            if ($environment == 'windows') {
                $filename_src = '\wo\\' . $folder_code . '\\' . $filename;
            } else {
                $filename_src = '/wo/' . $folder_code . '/' . $filename;
            }

            // write PDF to disk
            $pdf->Output(dirname(__FILE__) . $filename_src, 'F');

            // insert/update sys_pdf record (same logic as before)
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
                        'pdf_filename'   => $filename, 
                        'pdf_type'       => 'wo', 
                        'pdf_folder'     => $folder, 
                        'pdf_timeCreated'=> 'Now()'
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
