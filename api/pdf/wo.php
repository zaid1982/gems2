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

            // add a page
            $pdf->AddPage();

            $pdf->Image('pdf/images/logo.png', 15, 15, 50, 20, 'PNG', 'http://www.tcpdf.org', '', true, 150, '', false, false, 0, false, false, false);

            $arrSiteName = $this->fn_general->getSiteName();
            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrCategory = array('', 'External Complaint', 'Internal Complaint');
            $arrSeverity = array('', 'Non-Critical', 'Critical');
            $arrSla = array('', '4 hours', '2 hours');
            $arrDue = array('', '4', '2');

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            $userProfile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$woTask['wo_task_created_by'], 'user_profile_status'=>'1'), null, 1);

            $pdf->SetFont('helvetica', '', 11);
            $pdf->MultiCell(60, 20, '', 0, 'L', 0, 0, '', '');
            $pdf->MultiCell(120, 20, "\nWORK ORDER\n".strtoupper($arrSiteName[intval($woTask['site_id'])]), 1, 'C', 0, 0, '', '');
            $pdf->Ln();

            $pdf->SetFillColor(30, 0, 0, 0);
            $pdf->SetTextColor(0);
            $pdf->SetLineWidth(0.2);
            $pdf->Cell(180, 6, '', 0, 0, 'L', 0);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'A', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Complaint Details', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(30, 5, 'Reported By : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $arrUserFullName[intval($woTask['wo_task_created_by'])], 1, 0, 'L');
            $pdf->Cell(35, 5, 'Phone No : ', 1, 0, 'R');
            $pdf->Cell(55, 5, $this->fn_general->clear_null($userProfile['user_contact_no']), 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 5, 'Email : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $this->fn_general->clear_null($userProfile['user_email']), 1, 0, 'L');
            $pdf->Cell(35, 5, 'Reported Date/Time : ', 1, 0, 'R');
            $pdf->Cell(55, 5, $this->fn_general->convertDateToDisplay($woTask['wo_task_time_created']), 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 5, 'Category : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $arrCategory[intval($this->fn_general->clear_null($woTask['wo_task_type'], 0))], 1, 0, 'L');
            $pdf->Cell(35, 5, 'Severity : ', 1, 0, 'R');
            $pdf->Cell(55, 5, $arrSeverity[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))], 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 5, 'Work Order No : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $woTask['wo_task_no'], 1, 0, 'L');
            $pdf->Cell(35, 5, 'Location : ', 1, 0, 'R');
            $pdf->Cell(55, 5, $this->fn_general->clear_null($woTask['wo_task_location']), 1, 0, 'L');
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'B', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Description of Complaint', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $maxnocells = 0;
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $pdf->MultiCell(8,4,'',0,'L',0,0);
            $pdf->MultiCell(172,4, '',0,'L',0,0);
            $pdf->Ln();
            $cellcount = $pdf->MultiCell(8,4,'',0,'L',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(172,4, $this->fn_general->clear_null($woTask['wo_task_complaint']),0,'L',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $pdf->SetXY($startX,$startY);
            $pdf->MultiCell(8, ($maxnocells*4)+8, '', 1, 'L', 0, 0);
            $pdf->MultiCell(172, ($maxnocells*4)+8, '', 1, 'L', 0, 0);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'C', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Image of Complaint', 1, 0, 'L', 1);
            $pdf->Ln();

            $img_complaint = array();
            $img_before = array();
            $img_during = array();
            $img_after = array();
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

            $pdf->SetFont('helvetica', '', 9);
            if (!empty($img_complaint)) {
                foreach ($img_complaint as $key=>$img_display) {
                    if ($pdf->GetY() > 200) {
                        $pdf->AddPage();
                        $pdf->setPage($pdf->getPage());
                    }
                    $pdf->writeHTMLCell(8, 65, '', '', '', 1);
                    $pdf->writeHTMLCell(92, 65, '', '', '<br/><br/><img src="'.$img_display['upload_folder'].'/'.$img_display['upload_filename'].'.'.$img_display['upload_extension'].'" height="200" />', 1, '', '', '', 'C');
                    $pdf->writeHTMLCell(80, 65, '', '', "<br/><br/>Description : ".$this->fn_general->clear_null($img_display['wo_task_upload_desc']).
                        "<br/>Time Taken : ".$this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']).
                        "<br/>Longitude : ".$this->fn_general->clear_null($img_display['wo_task_upload_longitude']).
                        "<br/>Latitude : ".$this->fn_general->clear_null($img_display['wo_task_upload_latitude']), 1);
                    $pdf->Ln();
                }
            } else {
                $pdf->Cell(8, 12, '', 1, 0, 'C', 0);
                $pdf->Cell(172, 12, '', 1, 0, 'L', 0);
                $pdf->Ln();
            }

            if ($pdf->GetY() > 263) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'C', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Work Assessment & Technician Involved', 1, 0, 'L', 1);
            $pdf->Ln();

            $picName = '';
            $picEmail = '';
            $dueTime = '';
            $fixedTime = '';
            $duration = '';
            if (!empty($woTask['wo_task_assigned_to'])) {
                $picName = $arrUserFullName[intval($woTask['wo_task_assigned_to'])];
                $userProfileTech = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$woTask['wo_task_assigned_to'], 'user_profile_status'=>'1'), null, 1);
                $picEmail = $this->fn_general->clear_null($userProfileTech['user_email']);
                $createdTime = new DateTime($woTask['wo_task_time_created']);
                if (!empty($woTask['wo_task_severity'])) {
                    $dueTime = $createdTime->modify('+'.$arrDue[intval($woTask['wo_task_severity'])].' hour');
                }
                if (!empty($woTask['wo_task_time_executed'])) {
                    $assignedTime = new DateTime($woTask['wo_task_time_assigned']);
                    $executedTime = new DateTime($woTask['wo_task_time_executed']);
                    $fixedTime = $executedTime->format('j/n/Y g:i:sa');
                    $interval = $assignedTime->diff($executedTime);
                    $duration = $interval->format('%a days %H:%I:%S');
                } //else {
                    //date_default_timezone_set("Asia/Kuala_Lumpur");
                //}
            }

            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(30, 5, 'Person In Charged : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $picName, 1, 0, 'L');
            $pdf->Cell(35, 5, 'SLA : ', 1, 0, 'R');
            $pdf->Cell(55, 5, $arrSla[intval($this->fn_general->clear_null($woTask['wo_task_severity'], 0))], 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 5, 'Email : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $picEmail, 1, 0, 'L');
            $pdf->Cell(35, 5, 'Due Date/Time : ', 1, 0, 'R');
            $pdf->Cell(55, 5, !empty($dueTime)?$dueTime->format('j/n/Y g:i:sa'):'', 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 5, 'Work Completed : ', 1, 0, 'R');
            $pdf->Cell(60, 5, $fixedTime, 1, 0, 'L');
            $pdf->Cell(35, 5, 'Work Duration : ', 1, 0, 'R');
            $pdf->Cell(55, 5, $duration, 1, 0, 'L');
            $pdf->Ln();
            $pdf->Cell(30, 5, 'Rating : ', 1, 0, 'R');
            $pdf->Cell(60, 5, !empty($woTask['wo_task_rate'])?$woTask['wo_task_rate'].' / 5':'', 1, 0, 'L');
            $pdf->Cell(35, 5, '', 1, 0, 'R');
            $pdf->Cell(55, 5, '', 1, 0, 'L');
            $pdf->Ln();

            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'D', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' Description of Repair Work', 1, 0, 'L', 1);
            $pdf->Ln();
            $pdf->SetFont('helvetica', '', 9);
            $maxnocells = 0;
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $pdf->MultiCell(8,4,'',0,'L',0,0);
            $pdf->MultiCell(172,4, '',0,'L',0,0);
            $pdf->Ln();
            $cellcount = $pdf->MultiCell(8,4,'',0,'L',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $cellcount = $pdf->MultiCell(172,4, $this->fn_general->clear_null($woTask['wo_task_repair_desc']),0,'L',0,0);
            if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
            $pdf->SetXY($startX,$startY);
            $pdf->MultiCell(8, ($maxnocells*4)+8, '', 1, 'L', 0, 0);
            $pdf->MultiCell(172, ($maxnocells*4)+8, '', 1, 'L', 0, 0);
            $pdf->Ln();

            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(8, 6, 'E', 1, 0, 'C', 1);
            $pdf->Cell(172, 6, ' List of Assisted Technician', 1, 0, 'L', 1);
            $pdf->Ln();

            $pdf->SetFont('helvetica', '', 9);
            $woAssists = Class_db::getInstance()->db_select('wo_task_assist', array('wo_task_id'=>$this->woTaskId));
            for ($i=0; $i<count($woAssists);$i++) {
                $assistName = $arrUserFullName[intval($woAssists[$i]['user_id'])];
                $pdf->Cell(8, 5, ($i+1), 1, 0, 'R');
                $pdf->Cell(172, 5, $assistName, 1, 0, 'L');
                $pdf->Ln();
            }

            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $servicedBy = '';
            $verifyBy = '';
            if (!empty($woTask['wo_task_fixed_by'])) {
                $servicedBy = $arrUserFullName[intval($woTask['wo_task_fixed_by'])];
            }
            if (!empty($woTask['wo_task_verified_by'])) {
                $verifyBy = $arrUserFullName[intval($woTask['wo_task_verified_by'])];
            }

            $pdf->MultiCell(90, 18, "Service By\n\n\n....................................................................\nName : ".$servicedBy."\nDate : ".$this->fn_general->convertDateToDisplay($woTask['wo_task_time_executed']), 1, 'L', 0, 0);
            $pdf->MultiCell(90, 18, "Verified By\n\n\n....................................................................\nName : ".$verifyBy."\nDate : ".$this->fn_general->convertDateToDisplay($woTask['wo_task_time_verified']), 1, 'L', 0, 0);
            $pdf->Ln();

            $signService = false;
            $signVerified = false;
            foreach ($woUploads as $woUpload) {
                $uploadType = $woUpload['wo_task_upload_type'];
                if ($woUpload['upload_extension'] === 'png') {
                    $fileDir = $woUpload['upload_folder'].'/'.$woUpload['upload_filename'].'.'.$woUpload['upload_extension'];
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Sign : '.$fileDir);
                    if ($uploadType === '7' && $signService === false) {
                        $pdf->Image($fileDir, 20, $pdf->GetY()-30, 40, 30, 'PNG', '', '', false, 300);
                        $signService = true;
                    } else if ($uploadType === '8' && $signVerified === false) {
                        $pdf->Image($fileDir, 110, $pdf->GetY()-30, 40, 30, 'PNG', '', '', false, 300);
                        $signVerified = true;
                    }
                }
            }


            if (!empty($img_before) || !empty($img_during) || !empty($img_after)) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());

                $pdf->writeHTML("<br/><br/>", true, false, true, false);
                $pdf->SetFont('helvetica', '', 11);
                $pdf->writeHTMLCell(180, 6, '', '', ' Work Order Image : Before', 1, 0, 1);
                $pdf->Ln();

                if (!empty($img_before)) {
                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->writeHTMLCell(100, 65, '', '', '<br/><br/><img src="' . $img_before['upload_folder'] . '/' . $img_before['upload_filename'] . '.' . $img_before['upload_extension'] . '" height="200" />', 1, '', '', '', 'C');
                    $pdf->writeHTMLCell(80, 65, '', '', "<br/><br/>Description : " . $this->fn_general->clear_null($img_before['wo_task_upload_desc']) .
                        "<br/>Time Taken : " . $this->fn_general->convertDateToDisplay($img_before['wo_task_upload_timestamp']) .
                        "<br/>Longitude : " . $this->fn_general->clear_null($img_before['wo_task_upload_longitude']) .
                        "<br/>Latitude : " . $this->fn_general->clear_null($img_before['wo_task_upload_latitude']), 1);
                    $pdf->Ln();
                } else {
                    $pdf->writeHTMLCell(180, 12, '', '', '', 1, 0, 0);
                    $pdf->Ln();
                }

                $pdf->writeHTML("<br/><br/>", true, false, true, false);
                $pdf->SetFont('helvetica', '', 11);
                $pdf->writeHTMLCell(180, 6, '', '', ' Work Order Image : During', 1, 0, 1);
                $pdf->Ln();

                if (!empty($img_during)) {
                    $pdf->SetFont('helvetica', '', 9);
                    foreach ($img_during as $key => $img_display) {
                        $pdf->writeHTMLCell(100, 65, '', '', '<br/><br/><img src="' . $img_display['upload_folder'] . '/' . $img_display['upload_filename'] . '.' . $img_display['upload_extension'] . '" height="200" />', 1, '', '', '', 'C');
                        $pdf->writeHTMLCell(80, 65, '', '', "<br/><br/>Description : " . $this->fn_general->clear_null($img_display['wo_task_upload_desc']) .
                            "<br/>Time Taken : " . $this->fn_general->convertDateToDisplay($img_display['wo_task_upload_timestamp']) .
                            "<br/>Longitude : " . $this->fn_general->clear_null($img_display['wo_task_upload_longitude']) .
                            "<br/>Latitude : " . $this->fn_general->clear_null($img_display['wo_task_upload_latitude']), 1);
                        $pdf->Ln();

                        if ($pdf->GetY() > 200) {
                            $pdf->AddPage();
                            $pdf->setPage($pdf->getPage());
                        }
                    }
                } else {
                    $pdf->writeHTMLCell(180, 12, '', '', '', 1, 0, 0);
                    $pdf->Ln();
                }

                if ($pdf->GetY() > 200) {
                    $pdf->AddPage();
                    $pdf->setPage($pdf->getPage());
                }

                $pdf->writeHTML("<br/><br/>", true, false, true, false);
                $pdf->SetFont('helvetica', '', 11);
                $pdf->writeHTMLCell(180, 6, '', '', ' Work Order Image : After', 1, 0, 1);
                $pdf->Ln();

                if (!empty($img_after)) {
                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->writeHTMLCell(100, 65, '', '', '<br/><br/><img src="' . $img_after['upload_folder'] . '/' . $img_after['upload_filename'] . '.' . $img_after['upload_extension'] . '" height="200" />', 1, '', '', '', 'C');
                    $pdf->writeHTMLCell(80, 65, '', '', "<br/><br/>Description : " . $this->fn_general->clear_null($img_after['wo_task_upload_desc']) .
                        "<br/>Time Taken : " . $this->fn_general->convertDateToDisplay($img_after['wo_task_upload_timestamp']) .
                        "<br/>Longitude : " . $this->fn_general->clear_null($img_after['wo_task_upload_longitude']) .
                        "<br/>Latitude : " . $this->fn_general->clear_null($img_after['wo_task_upload_latitude']), 1);
                    $pdf->Ln();
                } else {
                    $pdf->writeHTMLCell(180, 12, '', '', '', 1, 0, 0);
                    $pdf->Ln();
                }
            }

            // close and output PDF document
            $folder_code = floor(intval($this->woTaskId)/1000);
            $folder = 'pdf/wo/'.$folder_code;

            $result = $this->fn_general->folderExist($folder);
            if (!$result) {
                mkdir ($folder,0777, true);
            }
            $filename = 'wo_'.substr((10000+intval($this->woTaskId)),1).'.pdf';
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
            //$filename_src = '\wo\\'.$folder_code.'\\'.$filename;
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
            Class_db::getInstance()->db_update('wo_task', array('pdf_id'=>$pdfId), array('wo_task_id'=>$this->woTaskId));

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