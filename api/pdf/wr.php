<?php

class MYPDF_wr extends TCPDF {
    public function Header() {
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 0, $this->getPageWidth(), $this->getPageHeight(), 'F');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'This form is system-generated and does not require a signature.', 0, 0, 'L', 0);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, 0, 'R', 0);
    }
}

class Class_pdf_wr {
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

    private function clear($value, $default='') {
        return $this->fn_general->clear_null($value, $default);
    }

    private function array_get($array, $key, $default='') {
        return is_array($array) && isset($array[$key]) ? $array[$key] : $default;
    }

    private function user_name($arrUserFullName, $userId) {
        if (empty($userId)) {
            return '';
        }
        $key = intval($userId);
        return isset($arrUserFullName[$key]) ? $arrUserFullName[$key] : '';
    }

    private function user_profile($userId) {
        if (empty($userId)) {
            return array();
        }
        return Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$userId, 'user_profile_status'=>'1'), null, 0);
    }

    private function date_text($date) {
        $date = $this->clear($date);
        if ($date === '' || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '';
        }
        try {
            return $this->fn_general->convertDateToDisplay($date);
        } catch (Exception $ex) {
            return '';
        }
    }

    private function add_time_text($date, $amount, $unit) {
        $date = $this->clear($date);
        if ($date === '' || !is_numeric($amount)) {
            return '';
        }
        try {
            $dateTime = new DateTime($date);
            $dateTime->modify('+'.intval($amount).' '.$unit);
            return $dateTime->format('j/n/Y g:i:sa');
        } catch (Exception $ex) {
            return '';
        }
    }

    private function add_time_mysql($date, $amount, $unit) {
        $date = $this->clear($date);
        if ($date === '' || !is_numeric($amount)) {
            return '';
        }
        try {
            $dateTime = new DateTime($date);
            $dateTime->modify('+'.intval($amount).' '.$unit);
            return $dateTime->format('Y-m-d H:i:s');
        } catch (Exception $ex) {
            return '';
        }
    }

    private function duration_text($startDate, $endDate) {
        if ($this->clear($startDate) === '' || $this->clear($endDate) === '') {
            return '';
        }
        return $this->fn_general->timeDiff($startDate, $endDate);
    }

    private function sla_status($dueDate, $actualDate) {
        if ($this->clear($dueDate) === '' || $this->clear($actualDate) === '') {
            return '';
        }
        try {
            $due = new DateTime($dueDate);
            $actual = new DateTime($actualDate);
            return $actual <= $due ? 'Within SLA' : 'Exceed SLA';
        } catch (Exception $ex) {
            return '';
        }
    }

    private function get_upload_file_path($upload) {
        if (empty($upload['upload_folder']) || empty($upload['upload_filename']) || empty($upload['upload_extension'])) {
            return '';
        }
        $relativePath = $upload['upload_folder'].'/'.$upload['upload_filename'].'.'.$upload['upload_extension'];
        $paths = array(
            $relativePath,
            dirname(__DIR__, 2).'/'.$relativePath
        );
        foreach ($paths as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }
        return '';
    }

    private function collect_uploads($woTaskId) {
        $grouped = array('1'=>array(), '9'=>array(), '10'=>array(), '11'=>array());
        $woUploads = Class_db::getInstance()->db_select('mw_wo_upload', array('wo_task_id'=>$woTaskId, 'sys_upload.upload_status'=>'1'));
        foreach ($woUploads as $woUpload) {
            $uploadType = $woUpload['wo_task_upload_type'];
            if (!isset($grouped[$uploadType])) {
                $grouped[$uploadType] = array();
            }
            array_push($grouped[$uploadType], $woUpload);
        }
        return $grouped;
    }

    private function first_upload_path($uploads) {
        if (empty($uploads)) {
            return '';
        }
        foreach ($uploads as $upload) {
            $imagePath = $this->get_upload_file_path($upload);
            if ($imagePath !== '') {
                return $imagePath;
            }
        }
        return '';
    }

    private function ensure_space($pdf, $height) {
        if ($pdf->GetY() + $height > 275) {
            $pdf->AddPage();
            $pdf->setPage($pdf->getPage());
        }
    }

    private function document_title($pdf, $title) {
        $this->ensure_space($pdf, 14);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->MultiCell(0, 8, $title, 1, 'C', 1, 1, '', '', true, 0, false, true, 0, 'M', false);
    }

    private function section_header($pdf, $title) {
        $this->ensure_space($pdf, 16);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->MultiCell(0, 7, $title, 1, 'L', 1, 1, '', '', true, 0, false, true, 0, 'M', false);
    }

    private function pair_rows($pdf, $rows) {
        $pdf->SetFont('helvetica', '', 9);
        foreach ($rows as $row) {
            $this->ensure_space($pdf, 8);
            $pdf->Cell(40, 6, $row[0].':', 1, 0, 'L', 0, '', 1);
            $pdf->Cell(55, 6, $this->clear($row[1]), 1, 0, 'L', 0, '', 1);
            $pdf->Cell(40, 6, $this->array_get($row, 2) !== '' ? $row[2].':' : '', 1, 0, 'L', 0, '', 1);
            $pdf->Cell(45, 6, $this->clear($this->array_get($row, 3)), 1, 1, 'L', 0, '', 1);
        }
        $pdf->Ln(2);
    }

    private function text_block($pdf, $title, $text, $height=16) {
        $this->section_header($pdf, $title);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, $height, $this->clear($text), 'LRB', 'L', 0, 1, '', '', true, 0, false, true, $height, 'T', true);
        $pdf->Ln(2);
    }

    private function image_section($pdf, $title, $uploads, $columns=3) {
        $this->section_header($pdf, $title);
        $pdf->SetFont('helvetica', '', 9);

        if (empty($uploads)) {
            $pdf->MultiCell(0, 18, '[No image captured]', 1, 'C', 0, 1, '', '', true, 0, false, true, 18, 'M', false);
            $pdf->Ln(2);
            return;
        }

        $tableWidth = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
        $cellWidth = $tableWidth / $columns;
        $imageHeight = 36;
        $metaHeight = 16;
        $gpsHeight = 8;
        $chunks = array_chunk(array_values($uploads), $columns);

        foreach ($chunks as $chunk) {
            $this->ensure_space($pdf, 72);
            $pdf->SetX(PDF_MARGIN_LEFT);
            for ($i = 0; $i < $columns; $i++) {
                $pdf->Cell($cellWidth, 6, isset($chunk[$i]) ? 'Image '.($i + 1) : '', 1, 0, 'C');
            }
            $pdf->Ln();

            $y = $pdf->GetY();
            for ($i = 0; $i < $columns; $i++) {
                $x = PDF_MARGIN_LEFT + ($i * $cellWidth);
                $pdf->MultiCell($cellWidth, $imageHeight, '', 1, 'C', 0, 0, $x, $y, true, 0, false, true, $imageHeight, 'M', false);
                if (isset($chunk[$i])) {
                    $imagePath = $this->get_upload_file_path($chunk[$i]);
                    if ($imagePath !== '') {
                        $pdf->Image($imagePath, $x + 2, $y + 2, $cellWidth - 4, $imageHeight - 4, '', '', '', true, 150, '', false, false, 0, true, false, false);
                    } else {
                        $pdf->MultiCell($cellWidth, $imageHeight, '[Image file not available]', 0, 'C', 0, 0, $x, $y, true, 0, false, true, $imageHeight, 'M', true);
                    }
                }
            }
            $pdf->SetY($y + $imageHeight);

            $pdf->SetX(PDF_MARGIN_LEFT);
            for ($i = 0; $i < $columns; $i++) {
                $upload = isset($chunk[$i]) ? $chunk[$i] : array();
                $text = isset($chunk[$i]) ? 'Description: '.$this->clear($this->array_get($upload, 'wo_task_upload_desc'))."\n".'Date / Time Taken: '.$this->date_text($this->array_get($upload, 'wo_task_upload_timestamp')) : '';
                $pdf->MultiCell($cellWidth, $metaHeight, $text, 1, 'L', 0, 0, '', '', true, 0, false, true, $metaHeight, 'T', true);
            }
            $pdf->Ln();

            $pdf->SetX(PDF_MARGIN_LEFT);
            for ($i = 0; $i < $columns; $i++) {
                $upload = isset($chunk[$i]) ? $chunk[$i] : array();
                $text = isset($chunk[$i]) ? 'Longitude/Latitude: '.$this->clear($this->array_get($upload, 'wo_task_upload_longitude')).' / '.$this->clear($this->array_get($upload, 'wo_task_upload_latitude')) : '';
                $pdf->MultiCell($cellWidth, $gpsHeight, $text, 1, 'L', 0, 0, '', '', true, 0, false, true, $gpsHeight, 'T', true);
            }
            $pdf->Ln(10);
        }
    }

    private function signature_section($pdf, $columns) {
        $this->ensure_space($pdf, 70);
        $this->section_header($pdf, 'D3. Work Request Sign-Off [Validation and verification signatures]');
        $tableWidth = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
        $width = $tableWidth / count($columns);
        $bodyHeight = 25;

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetX(PDF_MARGIN_LEFT);
        foreach ($columns as $i => $column) {
            $pdf->Cell($width, 6, $column['title'], 1, 0, 'C', 0, '', 1);
        }
        $pdf->Ln();

        $y = $pdf->GetY();
        foreach ($columns as $i => $column) {
            $x = PDF_MARGIN_LEFT + ($i * $width);
            $pdf->MultiCell($width, $bodyHeight, '', 1, 'C', 0, 0, $x, $y, true, 0, false, true, $bodyHeight, 'M', false);
            if (!empty($column['signature'])) {
                $pdf->Image($column['signature'], $x + 12, $y + 2, $width - 24, $bodyHeight - 4, '', '', '', true, 150, '', false, false, 0, true, false, false);
            }
        }
        $pdf->SetY($y + $bodyHeight);

        $pdf->SetX(PDF_MARGIN_LEFT);
        foreach ($columns as $i => $column) {
            $pdf->MultiCell($width, 14, 'Name: '.$this->clear($column['name'])."\n".'Date / Time: '.$this->clear($column['date']), 1, 'L', 0, 0, '', '', true, 0, false, true, 14, 'T', true);
        }
        $pdf->Ln(16);
    }

    private function get_location_data($woTask) {
        $locationName = $this->clear($this->array_get($woTask, 'wo_task_location'));
        if (!empty($woTask['zone_id'])) {
            $zone = Class_db::getInstance()->db_select_single('cli_zone', array('zone_id'=>$woTask['zone_id']), null, 0);
            if (!empty($zone)) {
                $locationName = trim($this->clear($this->array_get($zone, 'zone_code')).' '.$this->clear($this->array_get($zone, 'zone_name')));
            }
        }
        return $locationName;
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

            $pdf = new MYPDF_wr(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('GEMS 2.0');
            $pdf->SetTitle('Work Request');
            $pdf->SetSubject('Work Request Details');
            $pdf->setPrintHeader(true);
            $pdf->setPrintFooter(true);
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->AddPage();

            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrCategory = array('', 'Complaint', 'Finding', 'Request', 'Breakdown', 'Defect', 'Public Complaint');
            $arrSeverity = $this->fn_general->getSeverityName();
            $arrStatus = $this->fn_general->getRefStatus();
            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            $clientId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$woTask['site_id']), 'client_id', null, 1);
            $userProfile = $this->user_profile($woTask['wo_task_created_by']);
            $picProfile = $this->user_profile($this->array_get($woTask, 'wo_task_assigned_to'));
            $uploads = $this->collect_uploads($this->woTaskId);

            $severityId = intval($this->clear($this->array_get($woTask, 'wo_task_severity'), 0));
            $categoryId = intval($this->clear($this->array_get($woTask, 'wo_task_type'), 0));
            $statusId = intval($this->clear($this->array_get($woTask, 'wo_task_status'), 0));
            $arrRespondSla = array('', '4 hours', '2 hours');
            $arrRespondDueMinutes = array('', '240', '120');
            $arrClientSeverity = Class_db::getInstance()->db_select('cli_client_severity', array('client_id'=>$clientId));
            foreach ($arrClientSeverity as $clientSeverity) {
                $key = intval($clientSeverity['severity_id']);
                if (isset($clientSeverity['client_severity_respond_time'])) {
                    $arrRespondSla[$key] = $clientSeverity['client_severity_respond_time'].' minutes';
                    $arrRespondDueMinutes[$key] = $clientSeverity['client_severity_respond_time'];
                }
            }

            $createdTime = $this->array_get($woTask, 'wo_task_time_created');
            $assignedTime = $this->array_get($woTask, 'wo_task_time_assigned');
            $wrCheckedTime = $this->array_get($woTask, 'wo_task_time_wr_checked');
            $wrVerifiedTime = $this->array_get($woTask, 'wo_task_time_wr_verified');
            $respondActual = $wrCheckedTime !== '' ? $wrCheckedTime : $assignedTime;
            $respondDueText = $this->add_time_text($createdTime, $this->array_get($arrRespondDueMinutes, $severityId), 'minute');
            $respondDueMysql = $this->add_time_mysql($createdTime, $this->array_get($arrRespondDueMinutes, $severityId), 'minute');
            $respondDuration = $this->duration_text($createdTime, $respondActual);
            $respondStatus = $this->sla_status($respondDueMysql, $respondActual);

            $this->document_title($pdf, 'WORK REQUEST (WR)');
            $this->section_header($pdf, 'A. Complaint Details [User Details: Public & Client for Complaints or Internal: for Self-Finding]');
            $this->pair_rows($pdf, array(
                array('Reported by', $this->user_name($arrUserFullName, $woTask['wo_task_created_by']), 'Phone No', $this->array_get($userProfile, 'user_contact_no')),
                array('Email', $this->array_get($userProfile, 'user_email'), 'Reported Date / Time', $this->date_text($createdTime)),
                array('Category', $this->array_get($arrCategory, $categoryId), 'Severity', $this->array_get($arrSeverity, $severityId)),
                array('Work Request No', $this->clear($this->array_get($woTask, 'wo_task_request_no'), $woTask['wo_task_no']), 'Location Complaint', $this->get_location_data($woTask))
            ));
            $this->text_block($pdf, 'B1. Description of Complaint [Manual Entry]', $this->array_get($woTask, 'wo_task_complaint'));
            $this->image_section($pdf, 'B2. Complaint Images [Complain from User]', $uploads['1']);
            $this->section_header($pdf, 'C1. Work Assessment Details [Selected by P.I.C. to verify the complaint]');
            $this->pair_rows($pdf, array(
                array('Person in Charge', $this->user_name($arrUserFullName, $this->array_get($woTask, 'wo_task_assigned_to')), 'SLA Respond Time', $this->array_get($arrRespondSla, $severityId)),
                array('Email', $this->array_get($picProfile, 'user_email'), 'WR Due Date Time', $respondDueText),
                array('Respond Date / Duration', trim($this->date_text($respondActual).' '.$respondDuration), 'Respond Status', $respondStatus)
            ));
            $this->image_section($pdf, 'C2. Response Images [P.I.C. verification of the complaint]', $uploads['11']);
            $this->section_header($pdf, 'D1. Validation Details [Who issue/assigned the WR to the P.I.C.]');
            $this->pair_rows($pdf, array(
                array('Validation by', $this->user_name($arrUserFullName, $this->array_get($woTask, 'wo_task_wr_checked_by')), 'Designation', ''),
                array('Verified Date', $this->date_text($wrVerifiedTime), 'Work Request Status', $this->array_get($arrStatus, $statusId))
            ));
            $this->text_block($pdf, 'D2. Remark Details [Remarks before selecting WR Status; ensure a note is added if rejected] [Manual Entry]', $this->array_get($woTask, 'wo_task_wr_check'));
            $this->signature_section($pdf, array(
                array('title'=>'Checked By', 'signature'=>$this->first_upload_path($uploads['9']), 'name'=>$this->user_name($arrUserFullName, $this->array_get($woTask, 'wo_task_wr_checked_by')), 'date'=>$this->date_text($wrCheckedTime)),
                array('title'=>'Verified By', 'signature'=>$this->first_upload_path($uploads['10']), 'name'=>$this->user_name($arrUserFullName, $this->array_get($woTask, 'wo_task_wr_verified_by')), 'date'=>$this->date_text($wrVerifiedTime))
            ));

            $folder_code = floor(intval($this->woTaskId)/1000);
            $folder = 'pdf/wr/'.$folder_code;
            $folderPath = __DIR__.'/wr/'.$folder_code;

            if (!is_dir($folderPath) && !mkdir($folderPath, 0777, true)) {
                throw new Exception('[' . __LINE__ . '] - Unable to create PDF folder '.$folderPath);
            }
            if (!is_writable($folderPath)) {
                throw new Exception('[' . __LINE__ . '] - PDF folder not writable '.$folderPath);
            }
            // Use WR/WO number so browser Save As suggests the business number, not internal task id
            $displayNo = $this->clear($this->array_get($woTask, 'wo_task_request_no'), $this->array_get($woTask, 'wo_task_no'));
            if ($displayNo === '' || $displayNo === '-') {
                $displayNo = 'wr_'.substr((10000000+intval($this->woTaskId)),1);
            }
            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $displayNo).'.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : '.$filename);
            $pdf->SetTitle($displayNo);
            $pdf->Output($folderPath.'/'.$filename, 'F');

            $pdfId = $woTask['pdf_id_wr'];
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_select_col('sys_pdf', array('pdf_filename'=>$filename, 'pdf_status'=>'1'), 'pdf_id');
            }
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_insert('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'wr', 'pdf_folder'=>$folder));
            } else {
                Class_db::getInstance()->db_update('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'wr', 'pdf_folder'=>$folder, 'pdf_timeCreated'=>'Now()'), array('pdf_id'=>$pdfId));
            }
            Class_db::getInstance()->db_update('wo_task', array('pdf_id_wr'=>$pdfId), array('wo_task_id'=>$this->woTaskId));

            return array(
                'pdfId'=>$pdfId,
                'woTaskNo'=>$this->clear($this->array_get($woTask, 'wo_task_request_no'), $woTask['wo_task_no'])
            );
        } catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}