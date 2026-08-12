<?php

date_default_timezone_set("Asia/Kuala_Lumpur");
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$apiBasePath = dirname(__DIR__);
chdir($apiBasePath);

require_once $apiBasePath.'/library/constant.php';
require_once $apiBasePath.'/function/db.php';
require_once $apiBasePath.'/function/f_general.php';
require_once $apiBasePath.'/function/f_login.php';
require_once $apiBasePath.'/function/f_wo.php';
require_once $apiBasePath.'/function/f_task.php';
require_once $apiBasePath.'/function/f_email.php';
require_once __DIR__.'/tcpdf_include.php';
require_once __DIR__.'/wo.php';
require_once __DIR__.'/wr.php';

if (!class_exists('Class_pdf_wo_jkr')) {
class Class_pdf_wo_jkr extends Class_pdf_wo {
    private function get_jkr_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false) {
                $msg = substr($msg, $pos + 2);
            }
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."] - ".$msg;
        }
        return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."]";
    }

    private function fn_general() {
        return $this->__get('fn_general');
    }

    private function wo_task_id() {
        return $this->__get('woTaskId');
    }

    private function clear($value, $default='') {
        return $this->fn_general()->clear_null($value, $default);
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
        return $this->fn_general()->convertDateToDisplay($date);
    }

    private function add_time_text($date, $amount, $unit) {
        $date = $this->clear($date);
        if ($date === '' || !is_numeric($amount)) {
            return '';
        }
        $dateTime = new DateTime($date);
        $dateTime->modify('+'.intval($amount).' '.$unit);
        return $dateTime->format('j/n/Y g:i:sa');
    }

    private function duration_text($startDate, $endDate) {
        if ($this->clear($startDate) === '' || $this->clear($endDate) === '') {
            return '';
        }
        return $this->fn_general()->timeDiff($startDate, $endDate);
    }

    private function sla_status($dueDate, $actualDate) {
        if ($this->clear($dueDate) === '' || $this->clear($actualDate) === '') {
            return '';
        }
        $due = new DateTime($dueDate);
        $actual = new DateTime($actualDate);
        return $actual <= $due ? 'Within SLA' : 'Exceed SLA';
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
        $grouped = array('1'=>array(), '2'=>array(), '3'=>array(), '4'=>array(), '7'=>array(), '8'=>array(), '9'=>array(), '10'=>array(), '11'=>array(), '12'=>array());
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

    private function signature_section($pdf, $columns) {
        $this->ensure_space($pdf, 70);
        $this->section_header($pdf, 'E. Work Completion & Verification [Sign-off and satisfaction rating]');
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
                $pdf->Image($column['signature'], $x + 8, $y + 2, $width - 16, $bodyHeight - 4, '', '', '', true, 150, '', false, false, 0, true, false, false);
            }
        }
        $pdf->SetY($y + $bodyHeight);

        $pdf->SetX(PDF_MARGIN_LEFT);
        foreach ($columns as $i => $column) {
            $pdf->MultiCell($width, 10, 'Name: '.$this->clear($column['name']), 1, 'L', 0, 0, '', '', true, 0, false, true, 10, 'T', true);
        }
        $pdf->Ln();

        $pdf->SetX(PDF_MARGIN_LEFT);
        foreach ($columns as $i => $column) {
            $pdf->MultiCell($width, 12, 'Designation: '.$this->clear($column['designation'])."\n".'Date / Time: '.$this->clear($column['date']), 1, 'L', 0, 0, '', '', true, 0, false, true, 12, 'T', true);
        }
        $pdf->Ln(14);
    }

    private function rating_section($pdf, $rating) {
        $this->ensure_space($pdf, 16);
        $labels = array('1'=>'Very Dissatisfied', '2'=>'Dissatisfied', '3'=>'Neutral', '4'=>'Satisfied', '5'=>'Very Satisfied');
        $tableWidth = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
        $optionWidth = $tableWidth / count($labels);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Satisfactory Level:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 8);
        foreach ($labels as $score => $label) {
            $text = ($rating == $score ? 'X' : $score).' '.$label;
            $pdf->MultiCell($optionWidth, 6, $text, 1, 'C', 0, 0, '', '', true, 0, false, true, 6, 'M', true);
        }
        $pdf->Ln(8);
    }

    private function get_location_data($woTask) {
        $locationName = $this->clear($this->array_get($woTask, 'wo_task_location'));
        $locationCode = '';
        if (!empty($woTask['zone_id'])) {
            $zone = Class_db::getInstance()->db_select_single('cli_zone', array('zone_id'=>$woTask['zone_id']), null, 0);
            if (!empty($zone)) {
                $locationCode = $this->clear($this->array_get($zone, 'zone_code'));
                $locationName = trim($locationCode.' '.$this->clear($this->array_get($zone, 'zone_name')));
            }
        }
        return array('name'=>$locationName, 'code'=>$locationCode);
    }

    private function get_asset_data($woTask) {
        if (empty($woTask['asset_id'])) {
            return array('name'=>'', 'code'=>'');
        }
        $asset = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$woTask['asset_id']), null, 0);
        return array(
            'name'=>$this->clear($this->array_get($asset, 'asset_name')),
            'code'=>$this->clear($this->array_get($asset, 'asset_no'))
        );
    }

    private function get_materials($woTaskId) {
        try {
            $woRequestId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$woTaskId), 'wo_task_request_id', 'wo_task_request_id DESC');
            if (empty($woRequestId)) {
                return array();
            }
            return Class_db::getInstance()->db_select2('vw_wo_task_parts_mobile', array('a.wo_task_request_id'=>$woRequestId));
        } catch (Exception $ex) {
            $this->fn_general()->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            return array();
        }
    }

    private function material_section($pdf, $materials) {
        $this->section_header($pdf, 'C. Material Details [Parts or materials issued, returned, and tracked based on inventory records in the GEMS module]');
        $pdf->SetFont('helvetica', '', 8);
        $widths = array(22, 52, 25, 13, 18, 25, 25);
        $headers = array('Part No.', 'Item Description', 'Issue Type', 'D/I', 'Unit', 'Quantity Taken', 'Quantity Return');
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 6, $header, 1, 0, 'C', 0, '', 1);
        }
        $pdf->Ln();

        if (empty($materials)) {
            $materials = array();
        }
        $rows = max(count($materials), 4);
        for ($i = 0; $i < $rows; $i++) {
            $material = isset($materials[$i]) ? $materials[$i] : array();
            $values = array(
                $this->clear($this->array_get($material, 'part_id')),
                $this->clear($this->array_get($material, 'item_description')),
                $this->clear($this->array_get($material, 'status_desc')),
                empty($material) ? '' : 'I',
                '',
                $this->clear($this->array_get($material, 'wo_task_parts_quantity')),
                ''
            );
            foreach ($values as $j => $value) {
                $pdf->Cell($widths[$j], 6, $value, 1, 0, $j === 1 ? 'L' : 'C', 0, '', 1);
            }
            $pdf->Ln();
        }
        $pdf->Cell(0, 6, '**D = Direct Issue, I = Inventory', 0, 1, 'L');
        $pdf->Ln(2);
    }

    private function save_pdf($pdf, $woTask, $woTaskId) {
        $folderCode = floor(intval($woTaskId) / 1000);
        $folder = 'pdf/wo/'.$folderCode;
        $folderPath = __DIR__.'/wo/'.$folderCode;

        if (!is_dir($folderPath) && !mkdir($folderPath, 0777, true)) {
            throw new Exception('[' . __LINE__ . '] - Unable to create PDF folder '.$folderPath);
        }
        if (!is_writable($folderPath)) {
            throw new Exception('[' . __LINE__ . '] - PDF folder not writable '.$folderPath);
        }

        // Use WO number so browser Save As suggests the business number, not internal task id
        $displayNo = $this->clear($this->array_get($woTask, 'wo_task_no'));
        if ($displayNo === '' || $displayNo === '-') {
            $displayNo = 'wo_'.substr((10000000 + intval($woTaskId)), 1);
        }
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $displayNo).'.pdf';
        $this->fn_general()->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : '.$filename);
        $pdf->Output($folderPath.'/'.$filename, 'F');

        $pdfId = $this->array_get($woTask, 'pdf_id');
        if (empty($pdfId)) {
            $pdfId = Class_db::getInstance()->db_select_col('sys_pdf', array('pdf_filename'=>$filename, 'pdf_status'=>'1'), 'pdf_id');
        }
        if (empty($pdfId)) {
            $pdfId = Class_db::getInstance()->db_insert('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'wo', 'pdf_folder'=>$folder));
        } else {
            Class_db::getInstance()->db_update('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'wo', 'pdf_folder'=>$folder, 'pdf_timeCreated'=>'Now()'), array('pdf_id'=>$pdfId));
        }
        Class_db::getInstance()->db_update('wo_task', array('pdf_id'=>$pdfId, 'wo_task_is_pdf'=>'0'), array('wo_task_id'=>$woTaskId));

        return array(
            'pdfId'=>$pdfId,
            'woTaskNo'=>$woTask['wo_task_no']
        );
    }

    public function create_pdf() {
        return parent::create_pdf();
    }
}
}
$isDirectRequest = isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__);
if (!$isDirectRequest) {
    return;
}

$api_name = 'api_wo';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_wo = new Class_wo();
$fn_task = new Class_task();
$fn_pdf_wo = new Class_pdf_wo_jkr();
$fn_pdf_wr = new Class_pdf_wr();
$fn_email = new Class_email();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_wo->__set('constant', $constant);
    $fn_wo->__set('fn_general', $fn_general);
    $fn_pdf_wo->__set('fn_general', $fn_general);
    $fn_pdf_wr->__set('fn_general', $fn_general);
    $fn_task->__set('constant', $constant);
    $fn_task->__set('fn_general', $fn_general);
    $fn_email->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $type = filter_input(INPUT_GET, 'type');
        $woTaskId = filter_input(INPUT_GET, 'woTaskId');
        if (!is_null($type)) {
            if ($type === 'dashboard_list') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $kpiType = filter_input(INPUT_GET, 'kpiType');
                $result = $fn_wo->get_wo_task_dashboard_list($clientId, $siteId, $year, $month, '', $kpiType);
            }
            else if ($type === 'total_by_site_status') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_site_status($clientId, $year, $month);
            }
            else if ($type === 'total_by_site_type') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_site_type($clientId, $year, $month);
            }
            else if ($type === 'total_by_type') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_type($clientId, $siteId, $year, $month);
            }
            else if ($type === 'total_by_status') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_status($clientId, $siteId, $year, $month);
            }
            else if ($type === 'total_by_group') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_group($clientId, $siteId, $year, $month);
            }
            else if ($type === 'top5_execute') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_top5_execute($clientId, $siteId, $year, $month);
            }
            else if ($type === 'bottom5_execute') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_bottom5_execute($clientId, $siteId, $year, $month);
            }
            else if ($type === 'average_execute_by_trade') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_average_execute_by_trade($clientId, $siteId, $year, $month);
            }
            else if ($type === 'report_wo_summary') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_report_wo_summary($clientId, $year, $month);
            }
            else if ($type === 'report_wo_pending_list') {
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_task_dashboard_list('', $siteId, $year, $month, true);
            }
            else if ($type === 'report_wo_total') {
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_report_wo_total($year, $month);
            }
            else if ($type === 'report_wo_daily') {
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $isManual = filter_input(INPUT_GET, 'isManual');
                $result = $fn_wo->get_report_wo_daily($siteId, $isManual, $year, $month);
            }
            else if ($type === 'wo_by_transaction') {
                $transactionId = filter_input(INPUT_GET, 'transactionId');
                $result = $fn_wo->get_wo_task($transactionId);
            }
            else if ($type === 'helpdesk_list') {
                $isPending = filter_input(INPUT_GET, 'isPending');
                $fn_wo->__set('userId', $jwt_data->userId);
                $result = $fn_wo->get_helpdesk_list($isPending);
            }
            else if ($type === 'severity_list_by_site') {
                $siteId = filter_input(INPUT_GET, 'siteId');
                $result = $fn_wo->get_severity_list_by_site($siteId);
            }
            else if ($type === 'ppm_group_user_list') {
                $ppmGroupId = filter_input(INPUT_GET, 'ppmGroupId');
                $result = $fn_wo->get_ppm_group_user_list($ppmGroupId);
            }
            else if ($type === 'technician_current_task') {
                $userTechId = filter_input(INPUT_GET, 'userId');
                $result = $fn_wo->get_technician_current_task($userTechId);
            }
            else {
                throw new Exception('[' . __LINE__ . '] - Parameter get invalid');
            }
        } else if (!is_null($woTaskId)) {
            $result = $fn_wo->get_wo_task();
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter get invalid');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $action = filter_input(INPUT_POST, 'action');
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'insert_site_manual') {
            $siteId = filter_input(INPUT_POST, 'siteId');
            $siteName = filter_input(INPUT_POST, 'siteName');
            $selectedDate = filter_input(INPUT_POST, 'selectedDate');
            $selectedMonth = filter_input(INPUT_POST, 'selectedMonth');
            $selectedYear = filter_input(INPUT_POST, 'selectedYear');
            $open0 = filter_input(INPUT_POST, 'open0');
            $closed0 = filter_input(INPUT_POST, 'closed0');
            $open1 = filter_input(INPUT_POST, 'open1');
            $closed1 = filter_input(INPUT_POST, 'closed1');
            $open2 = filter_input(INPUT_POST, 'open2');
            $closed2 = filter_input(INPUT_POST, 'closed2');
            $open3 = filter_input(INPUT_POST, 'open3');
            $closed3 = filter_input(INPUT_POST, 'closed3');
            $open4 = filter_input(INPUT_POST, 'open4');
            $closed4 = filter_input(INPUT_POST, 'closed4');
            $open5 = filter_input(INPUT_POST, 'open5');
            $closed5 = filter_input(INPUT_POST, 'closed5');

            $params = array(
                'siteId'=>$siteId,
                'selectedDate'=>$selectedDate,
                'selectedMonth'=>$selectedMonth,
                'selectedYear'=>$selectedYear,
                'open0'=>$open0,
                'closed0'=>$closed0,
                'open1'=>$open1,
                'closed1'=>$closed1,
                'open2'=>$open2,
                'closed2'=>$closed2,
                'open3'=>$open3,
                'closed3'=>$closed3,
                'open4'=>$open4,
                'closed4'=>$closed4,
                'open5'=>$open5,
                'closed5'=>$closed5
            );

            $result = $fn_wo->add_siteManual($params);
            $fn_general->save_audit('125', $jwt_data->userId, 'Site = '.$siteName.', date = '.$selectedDate.'/'.$selectedMonth.'/'.$selectedYear);
            $form_data['errmsg'] = $constant::SUC_WO_MANUAL_REPORT_ADD;
        }
        else if ($action === 'submit_helpdesk_complaint') {
            $siteId = filter_input(INPUT_POST, 'siteId');
            $createdBy = filter_input(INPUT_POST, 'createdBy');
            $locationCodeId = filter_input(INPUT_POST, 'locationCodeId');
            $locationDetails = filter_input(INPUT_POST, 'locationDetails');
            $complaint = filter_input(INPUT_POST, 'complaint');
            $taskType = filter_input(INPUT_POST, 'taskType');
            $severity = filter_input(INPUT_POST, 'severity');
            $ppmGroupId = filter_input(INPUT_POST, 'ppmGroupId');
            $assignedTo = filter_input(INPUT_POST, 'assignedTo');
            $taskAssist = filter_input(INPUT_POST, 'taskAssist', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

            $complaintImageUploads = array();
            $complaintImages = filter_input(INPUT_POST, 'complaintImages', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            if (!empty($complaintImages)) {
                foreach ($complaintImages as $complaintImage) {
                    $uploadId = $fn_general->uploadDocument($complaintImage, 9, $jwt_data->userId);
                    $complaintImageUpload = array('uploadId' => $uploadId, 'description' => $complaintImage['description'], 'longitude' => '', 'latitude' => '');
                    array_push($complaintImageUploads, $complaintImageUpload);
                }
            }

            $groupId = $fn_task->get_group_id_from_user($createdBy, '6');
            $fn_wo->__set('userId', $createdBy);
            $woTaskNo = $fn_wo->create_wo_no($groupId, false);
            $taskId = $fn_task->create_new_task('2', $createdBy, '6', $groupId, $woTaskNo, '', '11');
            $isWr = $fn_wo->get_wo_is_wr();
            if ($isWr === '1') {
                $newTaskId = $fn_task->submit_task($taskId, $createdBy, '9', '', '1', '', $groupId);
            } else {
                $newTaskId = $fn_task->submit_task($taskId, $createdBy, '9', '', '', '', $groupId);
            }
            $woTaskId = $fn_wo->submit_new_complaint($taskId, $woTaskNo, $locationCodeId, $locationDetails, $complaint, $complaintImageUploads, '', '', '1');
            $fn_wo->__set('woTaskId', $woTaskId);
            $fn_wo->save_respond_time_m();
            $fn_wo->save_assigned_technician_m($ppmGroupId, $assignedTo, $severity, $taskAssist, $taskType);

            $currentTask = $fn_wo->get_current_task('24', '12', '26', '17', '29');
            $newTaskId = $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '10', '', '', '', '', $assignedTo);
            $returnVal = $fn_wo->submit_assign($currentTask['transactionId']);
            $auditLabel = $isWr === '1' ? 'Work Request no. = ' : 'Work Order no. = ';
            $emailTemplateId = $isWr === '1' ? 11 : 5;
            $notiTextId = $isWr === '1' ? 12 : 6;
            $fn_general->save_audit('136', $jwt_data->userId, $auditLabel.$returnVal);
            $fn_email->setup_email($assignedTo, $emailTemplateId, array('task_no' => $returnVal));
            $fn_email->setup_mobile_notification($assignedTo, $notiTextId, array('task_no' => $returnVal));
            $form_data['errmsg'] = $constant::SUC_SUBMITTED;
        }
        else if ($action === 'generate_pdf') {
            $woTaskId = filter_input(INPUT_POST, 'woTaskId');
            $fn_pdf_wo->__set('woTaskId', $woTaskId);
            $result = $fn_pdf_wo->create_pdf();
        }
        else if ($action === 'generate_pdf_wr') {
            $woTaskId = filter_input(INPUT_POST, 'woTaskId');
            $fn_pdf_wr->__set('woTaskId', $woTaskId);
            $result = $fn_pdf_wr->create_pdf();
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update_site_manual') {
            $siteManualId = filter_input(INPUT_GET, 'siteManualId');
            $fn_wo->update_siteManual($siteManualId, $put_vars);
            $fn_general->save_audit('126', $jwt_data->userId, 'Site = '.$put_vars['siteName'].', date = '.$put_vars['selectedDate'].'/'.$put_vars['selectedMonth'].'/'.$put_vars['selectedYear']);
            $form_data['errmsg'] = $constant::SUC_WO_MANUAL_REPORT_EDIT;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $woTaskId = filter_input(INPUT_GET, 'woTaskId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $woTaskNo = $fn_wo->delete_wo($woTaskId);
        $fn_general->save_audit('124', $jwt_data->userId, 'WO Task No. = ' . $woTaskNo);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_WO_DELETE;
        $form_data['success'] = true;
    } else {
        throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
    }
    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    if ($is_transaction) {
        Class_db::getInstance()->db_rollback();
    }
    Class_db::getInstance()->db_close();
    $form_data['error'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    if ($ex->getCode() === 31) {
        $form_data['errmsg'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    } else {
        $form_data['errmsg'] = $constant::ERR_DEFAULT;
    }
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}

echo json_encode($form_data);