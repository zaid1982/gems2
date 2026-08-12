<?php

require_once __DIR__.'/arahan_siasatan_pdf.php';

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
        }
        return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."]";
    }

    private function pdf_debug_enabled() {
        static $enabled = null;
        if ($enabled === null) {
            $configPath = dirname(__DIR__).'/library/config.ini';
            $config = is_file($configPath) ? parse_ini_file($configPath, true) : array();
            $enabled = !empty($config['pdf']['debug']);
        }
        return $enabled;
    }

    private function format_pdf_error(Throwable $ex) {
        $detail = $ex->getMessage();
        $detail .= ' ['.basename($ex->getFile()).':'.$ex->getLine().']';
        if ($ex->getPrevious() instanceof Throwable) {
            $prev = $ex->getPrevious();
            $detail .= ' | caused by: '.$prev->getMessage().' ['.basename($prev->getFile()).':'.$prev->getLine().']';
        }
        return $detail;
    }

    private function get_upload_file_path ($upload) {
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

    private function clear_val($value, $default = '') {
        return $this->fn_general->clear_null($value, $default);
    }

    private function format_pdf_datetime($date) {
        $date = $this->clear_val($date);
        if ($date === '' || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '';
        }
        return (new DateTime($date))->format('j M Y H:i');
    }

    private function format_pdf_timestamp($date) {
        $date = $this->clear_val($date);
        if ($date === '' || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '';
        }
        return (new DateTime($date))->format('Y-m-d H:i:s');
    }

    private function cap_label($name, $role) {
        $name = trim($name);
        return $name !== '' ? '['.$name.' - '.$role.']' : '';
    }

    private function user_name($arrUserFullName, $userId) {
        if (empty($userId)) {
            return '';
        }
        $key = intval($userId);
        return isset($arrUserFullName[$key]) ? $arrUserFullName[$key] : '';
    }

    private function get_location_data($woTask) {
        $locationName = $this->clear_val($woTask['wo_task_location']);
        if (!empty($woTask['zone_id'])) {
            $zone = Class_db::getInstance()->db_select_single('cli_zone', array('zone_id'=>$woTask['zone_id']), null, 0);
            if (!empty($zone)) {
                $locationName = trim($this->clear_val($zone['zone_code']).' '.$this->clear_val($zone['zone_name']));
            }
        }
        return $locationName;
    }

    private function get_asset_data($woTask) {
        if (empty($woTask['asset_id'])) {
            return array('name' => '', 'code' => '');
        }
        $asset = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$woTask['asset_id']), null, 0);
        return array(
            'name' => $this->clear_val($asset['asset_name']),
            'code' => $this->clear_val($asset['asset_no'])
        );
    }

    private function get_trade_category($woTask) {
        if (empty($woTask['ppm_group_id'])) {
            return '';
        }
        $group = Class_db::getInstance()->db_select_single('ppm_group', array('ppm_group_id'=>$woTask['ppm_group_id']), null, 0);
        return !empty($group) ? $this->clear_val($group['ppm_group_name']) : '';
    }

    private function get_materials($woTaskId) {
        try {
            $woRequestId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$woTaskId), 'wo_task_request_id', 'wo_task_request_id DESC');
            if (empty($woRequestId)) {
                return array();
            }
            return Class_db::getInstance()->db_select2('vw_wo_task_parts_mobile', array('a.wo_task_request_id'=>$woRequestId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            return array();
        }
    }

    private function first_signature_path($uploads, $type) {
        if (empty($uploads[$type])) {
            return null;
        }
        foreach ($uploads[$type] as $upload) {
            $path = $this->get_upload_file_path($upload);
            if ($path !== '') {
                return $path;
            }
        }
        return null;
    }

    private function collect_uploads($woTaskId) {
        $grouped = array('1'=>array(), '2'=>array(), '3'=>array(), '4'=>array(), '5'=>array(), '7'=>array(), '8'=>array(), '12'=>array());
        $woUploads = Class_db::getInstance()->db_select('mw_wo_upload', array('wo_task_id'=>$woTaskId, 'sys_upload.upload_status'=>'1'));
        foreach ($woUploads as $woUpload) {
            $uploadType = $woUpload['wo_task_upload_type'];
            if (!isset($grouped[$uploadType])) {
                $grouped[$uploadType] = array();
            }
            $grouped[$uploadType][] = $woUpload;
        }
        return $grouped;
    }

    private function upload_to_photo_item($upload) {
        return array(
            'image' => $this->get_upload_file_path($upload),
            'keterangan' => $this->clear_val($upload['wo_task_upload_desc']),
            'masa' => $this->format_pdf_timestamp($upload['wo_task_upload_timestamp']),
            'longitude' => $this->clear_val($upload['wo_task_upload_longitude']),
            'latitude' => $this->clear_val($upload['wo_task_upload_latitude']),
        );
    }

    private function build_photo_groups($uploads) {
        $groups = array(
            array('title' => 'Gambar Aduan', 'type' => '1'),
            array('title' => 'Gambar Pembaikan : Sebelum', 'type' => '2'),
            array('title' => 'Gambar Pembaikan : Semasa', 'type' => '3'),
            array('title' => 'Gambar Pembaikan : Selepas', 'type' => '4'),
        );

        $photos = array();
        foreach ($groups as $group) {
            if (empty($uploads[$group['type']])) {
                continue;
            }
            $items = array();
            foreach ($uploads[$group['type']] as $upload) {
                $items[] = $this->upload_to_photo_item($upload);
            }
            $photos[] = array(
                'title' => $group['title'],
                'items' => $items,
            );
        }
        return $photos;
    }

    private function build_parts_rows($materials) {
        if (empty($materials)) {
            return array(array());
        }

        $rows = array();
        foreach ($materials as $material) {
            $rows[] = array(
                'no' => $this->clear_val(isset($material['part_id']) ? $material['part_id'] : ''),
                'keterangan' => $this->clear_val(isset($material['item_description']) ? $material['item_description'] : ''),
                'jenis' => 'I',
                'unit' => '',
                'digunakan' => $this->clear_val(isset($material['wo_task_parts_quantity']) ? $material['wo_task_parts_quantity'] : ''),
                'dikembalikan' => '',
            );
        }
        return $rows;
    }

    private function build_pdf_data($woTask) {
        $arrUserFullName = $this->fn_general->getUserFullName();
        $arrCategory = array('', 'Complaint', 'Finding', 'Request', 'Breakdown', 'Defect', 'Public Complaint');
        $arrSeverity = $this->fn_general->getSeverityName();
        $arrStatus = $this->fn_general->getRefStatus();

        $reporterProfile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$woTask['wo_task_created_by'], 'user_profile_status'=>'1'), null, 0);
        $picProfile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$woTask['wo_task_assigned_to'], 'user_profile_status'=>'1'), null, 0);
        if (!is_array($reporterProfile)) {
            $reporterProfile = array();
        }
        if (!is_array($picProfile)) {
            $picProfile = array();
        }

        $locationDisplay = $this->get_location_data($woTask);
        $asset = $this->get_asset_data($woTask);
        $uploads = $this->collect_uploads($this->woTaskId);
        $materials = $this->get_materials($this->woTaskId);

        $categoryId = intval($this->clear_val($woTask['wo_task_type'], 0));
        $severityId = intval($this->clear_val($woTask['wo_task_severity'], 0));
        $statusId = intval($this->clear_val($woTask['wo_task_status'], 0));

        $reporterName = $this->user_name($arrUserFullName, $woTask['wo_task_created_by']);
        $assignedName = $this->user_name($arrUserFullName, $woTask['wo_task_assigned_to']);
        $receivedBy = $this->user_name($arrUserFullName, !empty($woTask['wo_task_wr_verified_by']) ? $woTask['wo_task_wr_verified_by'] : $woTask['wo_task_created_by']);
        $workerName = $this->user_name($arrUserFullName, !empty($woTask['wo_task_fixed_by']) ? $woTask['wo_task_fixed_by'] : $woTask['wo_task_assigned_to']);
        $verifiedName = $this->user_name($arrUserFullName, $woTask['wo_task_verified_by']);
        $checkName = $this->user_name($arrUserFullName, $woTask['wo_task_wr_checked_by']);

        $createdTime = $this->clear_val($woTask['wo_task_time_created']);
        $assignedTime = $this->clear_val($woTask['wo_task_time_assigned']);
        $wrVerifiedTime = $this->clear_val($woTask['wo_task_time_wr_verified']);
        $executedTime = $this->clear_val($woTask['wo_task_time_executed']);
        $verifiedTime = $this->clear_val($woTask['wo_task_time_verified']);
        $checkedTime = $this->clear_val($woTask['wo_task_time_wr_checked']);

        $workStart = $wrVerifiedTime !== '' ? $wrVerifiedTime : $assignedTime;
        $workDuration = $this->fn_general->timeDiff($workStart, $executedTime);
        $totalExecTime = Class_db::getInstance()->db_select_col('mw_wo_execute_duration', array(), 'duration', null, 0, array('transaction_id'=>$woTask['transaction_id']));
        if (!empty($totalExecTime)) {
            $workDuration = $totalExecTime;
        }

        $arahanTime = $assignedTime !== '' ? $assignedTime : $createdTime;

        return array(
            'no_ruj' => $woTask['wo_task_no'],
            'status' => $this->clear_val($arrStatus[$statusId]),
            'nama_pengadu' => $reporterName,
            'jenis_kerja' => $this->clear_val($arrCategory[$categoryId]),
            'tarikh_aduan' => $this->format_pdf_datetime($createdTime),
            'kategori_kerja' => $this->get_trade_category($woTask),
            'no_telefon' => $this->clear_val($reporterProfile['user_contact_no']),
            'keutamaan_kerja' => $this->clear_val($arrSeverity[$severityId]),
            'keterangan_aduan' => $this->clear_val($woTask['wo_task_complaint']),
            'lokasi' => $locationDisplay,
            'no_aset' => $asset['code'],
            'nama_aset' => $asset['name'],
            'diterima_oleh' => $receivedBy,
            'ditugaskan_kepada' => $assignedName,
            'tarikh_arahan' => $this->format_pdf_datetime($arahanTime),
            'no_dihubungi' => $this->clear_val($picProfile['user_contact_no']),
            'keterangan_arahan' => $this->clear_val($woTask['wo_task_complaint']),
            'tarikh_tandatangan_pengadu' => $this->format_pdf_datetime($createdTime),
            'sign_pengadu' => $this->first_signature_path($uploads, '5'),
            'cap_pengadu' => $this->cap_label($reporterName, 'Client'),
            'parts' => $this->build_parts_rows($materials),
            'nama_pekerja' => $workerName,
            'no_pekerja' => '',
            'kerja_mula' => $this->format_pdf_timestamp($workStart),
            'kerja_tamat' => $this->format_pdf_timestamp($executedTime),
            'tempoh_kerja' => $workDuration,
            'tindakan' => $this->clear_val($woTask['wo_task_repair_desc']),
            'tindakan_mula' => $this->format_pdf_datetime($workStart),
            'tindakan_siap' => $this->format_pdf_datetime($executedTime),
            'tempoh_tindakan' => $workDuration,
            'sign_technician' => $this->first_signature_path($uploads, '7'),
            'cap_technician' => $this->cap_label($assignedName, 'Technician'),
            'sign_pegawai_penyelia' => $this->first_signature_path($uploads, '8'),
            'sign_pengadu_kerja_siap' => $this->first_signature_path($uploads, '5'),
            'cap_pegawai_penyelia' => $this->cap_label($verifiedName !== '' ? $verifiedName : $reporterName, 'Client'),
            'cap_pengadu_kerja_siap' => $this->cap_label($reporterName, 'Client'),
            'tarikh_pegawai_penyelia' => $this->format_pdf_datetime($verifiedTime),
            'tarikh_pengadu_kerja_siap' => $this->format_pdf_datetime($createdTime),
            'sign_operasi_fasiliti' => $this->first_signature_path($uploads, '12'),
            'cap_operasi_fasiliti' => $this->cap_label($checkName, 'Operasi Fasiliti'),
            'tarikh_operasi_fasiliti' => $this->format_pdf_datetime($checkedTime),
            'photos' => $this->build_photo_groups($uploads),
        );
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
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
        }
        throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
    }

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

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            $pdfData = $this->build_pdf_data($woTask);

            $pdf = new ArahanSiasatanPdf();
            $pdf->render($pdfData);

            $folder_code = floor(intval($this->woTaskId)/1000);
            $folder = 'pdf/wo/'.$folder_code;
            $folderPath = __DIR__.'/wo/'.$folder_code;

            if (!is_dir($folderPath) && !mkdir($folderPath, 0777, true)) {
                throw new Exception('[' . __LINE__ . '] - Unable to create PDF folder '.$folderPath);
            }
            if (!is_writable($folderPath)) {
                throw new Exception('[' . __LINE__ . '] - PDF folder not writable '.$folderPath);
            }

            // Use WO number so browser Save As suggests the business number, not internal task id
            $displayNo = $this->clear_val(isset($woTask['wo_task_no']) ? $woTask['wo_task_no'] : '');
            if ($displayNo === '' || $displayNo === '-') {
                $displayNo = 'wo_'.substr((10000000+intval($this->woTaskId)),1);
            }
            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $displayNo).'.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : '.$filename);
            $pdf->Output($folderPath.'/'.$filename, 'F');

            $pdfId = $woTask['pdf_id'];
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_select_col('sys_pdf', array('pdf_filename'=>$filename, 'pdf_status'=>'1'), 'pdf_id');
            }
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_insert('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'wo', 'pdf_folder'=>$folder));
            } else {
                Class_db::getInstance()->db_update('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'wo', 'pdf_folder'=>$folder, 'pdf_timeCreated'=>'Now()'), array('pdf_id'=>$pdfId));
            }
            Class_db::getInstance()->db_update('wo_task', array('pdf_id'=>$pdfId, 'wo_task_is_pdf'=>'0'), array('wo_task_id'=>$this->woTaskId));

            return array(
                'pdfId'=>$pdfId,
                'woTaskNo'=>$woTask['wo_task_no']
            );
        } catch (Throwable $ex) {
            $detail = $this->format_pdf_error($ex);
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $detail);
            $errorCode = $this->pdf_debug_enabled() ? 31 : $ex->getCode();
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $detail), $errorCode);
        }
    }
}

if (!class_exists('Class_pdf_wo_jkr')) {
    class Class_pdf_wo_jkr extends Class_pdf_wo {
        public function create_pdf() {
            return parent::create_pdf();
        }
    }
}
