<?php

class Class_asset {

    private $constant;
    private $fn_general;
    private $assetId;

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
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value ) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property ) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property ) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $contractId
     * @return array
     * @throws Exception
     */
    public function get_asset_list ($contractId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ast_asset', array('contract_id'=>$contractId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['assetId'] = $dataLocal['asset_id'];
                $row_result['assetNo'] = $this->fn_general->clear_null($dataLocal['asset_no']);
                $row_result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
                $row_result['assetSerialNo'] = $this->fn_general->clear_null($dataLocal['asset_serial_no']);
                $row_result['assetDesc'] = $this->fn_general->clear_null($dataLocal['asset_desc']);
                $row_result['assetCapacity'] = $this->fn_general->clear_null($dataLocal['asset_capacity']);
                $row_result['assetLocationCode'] = $this->fn_general->clear_null($dataLocal['asset_location_code']);
                $row_result['assetLocationDesc'] = $this->fn_general->clear_null($dataLocal['asset_location_desc']);
                $row_result['assetGroupId'] = $this->fn_general->clear_null($dataLocal['asset_group_id']);
                $row_result['assetCategoryId'] = $this->fn_general->clear_null($dataLocal['asset_category_id']);
                $row_result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
                $row_result['assetBrandId'] = $this->fn_general->clear_null($dataLocal['asset_brand_id']);
                $row_result['assetModelId'] = $this->fn_general->clear_null($dataLocal['asset_model_id']);
                $row_result['contractId'] = $this->fn_general->clear_null($dataLocal['contract_id']);
                $row_result['ppmGroupId'] = $this->fn_general->clear_null($dataLocal['ppm_group_id']);
                $row_result['zoneId'] = $this->fn_general->clear_null($dataLocal['zone_id']);
                $row_result['assetBlock'] = $this->fn_general->clear_null($dataLocal['asset_block']);
                $row_result['assetLevel'] = $this->fn_general->clear_null($dataLocal['asset_level']);
                $row_result['assetManufacturer'] = $this->fn_general->clear_null($dataLocal['asset_manufacturer']);
                $row_result['assetSupplier'] = $this->fn_general->clear_null($dataLocal['asset_supplier']);
                $row_result['assetAgency'] = $this->fn_general->clear_null($dataLocal['asset_agency']);
                $row_result['assetDepartment'] = $this->fn_general->clear_null($dataLocal['asset_department']);
                $row_result['assetConstructionZone'] = $this->fn_general->clear_null($dataLocal['asset_construction_zone']);
                $row_result['assetOperationZone'] = $this->fn_general->clear_null($dataLocal['asset_operation_zone']);
                $row_result['assetRoom'] = $this->fn_general->clear_null($dataLocal['asset_room']);
                $row_result['assetCompartment'] = $this->fn_general->clear_null($dataLocal['asset_compartment']);
                $row_result['assetAuthEmployee'] = $this->fn_general->clear_null($dataLocal['asset_auth_employee']);
                $row_result['assetCriticality'] = $this->fn_general->clear_null($dataLocal['asset_criticality']);
                $row_result['assetContractor'] = $this->fn_general->clear_null($dataLocal['asset_contractor']);
                $row_result['assetWarranty'] = $this->fn_general->clear_null($dataLocal['asset_warranty']);
                $row_result['assetWarrantyExpDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_warranty_exp_date']));
                $row_result['assetLifeCycle'] = $this->fn_general->clear_null($dataLocal['asset_life_cycle']);
                $row_result['assetWarrantyNotes'] = $this->fn_general->clear_null($dataLocal['asset_warranty_notes']);
                $row_result['assetTechnicianNotes'] = $this->fn_general->clear_null($dataLocal['asset_technician_notes']);
                $row_result['assetPurchasePrice'] = $this->fn_general->clear_null($dataLocal['asset_purchase_price']);
                $row_result['assetCommissionedDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_commissioned_date']));
                $row_result['assetDisposedDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_disposed_date']));
                $row_result['assetCurrentValue'] = $this->fn_general->clear_null($dataLocal['asset_current_value']);
                $row_result['assetEstimatedLife'] = $this->fn_general->clear_null($dataLocal['asset_estimated_life']);
                $row_result['assetLifetimeDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_lifetime_date']));
                $row_result['assetTimeCreated'] = str_replace('-', '/', $dataLocal['asset_time_created']);
                $row_result['assetStatus'] = $dataLocal['asset_status'];
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $contractId
     * @param string $searchValue
     * @param array $filters
     * @return array{0: array, 1: array}
     */
    private function build_asset_list_where ($contractId, $searchValue, $filters = array()) {
        $baseWhere = array('contract_id' => $contractId);
        if (!empty($filters['assetGroupId'])) {
            $baseWhere['asset_group_id'] = $filters['assetGroupId'];
        }
        if (!empty($filters['assetCategoryId'])) {
            $baseWhere['asset_category_id'] = $filters['assetCategoryId'];
        }
        if (!empty($filters['assetTypeId'])) {
            $baseWhere['asset_type_id'] = $filters['assetTypeId'];
        }
        if (!empty($filters['assetStatus'])) {
            $baseWhere['asset_status'] = $filters['assetStatus'];
        }

        $whereWithSearch = $baseWhere;
        $searchValue = trim((string) $searchValue);
        if ($searchValue !== '') {
            $escaped = addslashes(str_replace(array('%', '_'), array('\\%', '\\_'), $searchValue));
            $like = "'%".$escaped."%'";
            $whereWithSearch['w1'] = '(asset_no LIKE '.$like
                .' OR asset_name LIKE '.$like
                .' OR asset_serial_no LIKE '.$like
                .' OR asset_location_code LIKE '.$like
                .' OR asset_group_name LIKE '.$like
                .' OR asset_category_name LIKE '.$like
                .' OR asset_type_name LIKE '.$like
                .' OR asset_brand_name LIKE '.$like
                .' OR asset_model_name LIKE '.$like
                .' OR ppm_group_name LIKE '.$like.')';
        }

        return array($baseWhere, $whereWithSearch);
    }

    /**
     * @param int|null $orderColumn
     * @param string $orderDir
     * @return string
     */
    private function build_asset_list_order ($orderColumn, $orderDir) {
        $orderableColumns = array(
            1 => 'asset_name',
            2 => 'asset_no',
            3 => 'asset_serial_no',
            4 => 'asset_group_name',
            5 => 'asset_category_name',
            6 => 'asset_type_name',
            7 => 'asset_brand_name',
            8 => 'asset_model_name',
            9 => 'ppm_group_name',
            10 => 'asset_location_code',
            11 => 'asset_status'
        );
        $orderDir = strtolower((string) $orderDir) === 'desc' ? 'DESC' : 'ASC';
        if (!is_null($orderColumn) && array_key_exists((int) $orderColumn, $orderableColumns)) {
            return $orderableColumns[(int) $orderColumn].' '.$orderDir;
        }
        return 'asset_no ASC';
    }

    /**
     * @param array $dataLocal
     * @return array
     */
    private function map_asset_datatable_row ($dataLocal) {
        return array(
            'assetId' => $dataLocal['asset_id'],
            'assetNo' => $this->fn_general->clear_null($dataLocal['asset_no']),
            'assetName' => $this->fn_general->clear_null($dataLocal['asset_name']),
            'assetSerialNo' => $this->fn_general->clear_null($dataLocal['asset_serial_no']),
            'assetLocationCode' => $this->fn_general->clear_null($dataLocal['asset_location_code']),
            'assetGroupId' => $this->fn_general->clear_null($dataLocal['asset_group_id']),
            'assetCategoryId' => $this->fn_general->clear_null($dataLocal['asset_category_id']),
            'assetTypeId' => $this->fn_general->clear_null($dataLocal['asset_type_id']),
            'assetBrandId' => $this->fn_general->clear_null($dataLocal['asset_brand_id']),
            'assetModelId' => $this->fn_general->clear_null($dataLocal['asset_model_id']),
            'ppmGroupId' => $this->fn_general->clear_null($dataLocal['ppm_group_id']),
            'assetStatus' => $dataLocal['asset_status'],
            'assetGroupName' => $this->fn_general->clear_null($dataLocal['asset_group_name']),
            'assetCategoryName' => $this->fn_general->clear_null($dataLocal['asset_category_name']),
            'assetTypeName' => $this->fn_general->clear_null($dataLocal['asset_type_name']),
            'assetBrandName' => $this->fn_general->clear_null($dataLocal['asset_brand_name']),
            'assetModelName' => $this->fn_general->clear_null($dataLocal['asset_model_name']),
            'ppmGroupName' => $this->fn_general->clear_null($dataLocal['ppm_group_name'])
        );
    }

    /**
     * Server-side page for Asset Management. Returns only the current page of slim rows.
     *
     * @param string $contractId
     * @param int $start
     * @param int $length
     * @param string $searchValue
     * @param int|null $orderColumn
     * @param string $orderDir
     * @param array $filters
     * @return array
     * @throws Exception
     */
    public function get_asset_datatable ($contractId, $start, $length, $searchValue, $orderColumn, $orderDir, $filters = array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractId)) {
                return array('recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array());
            }

            list($baseWhere, $whereWithSearch) = $this->build_asset_list_where($contractId, $searchValue, $filters);
            $orderSql = $this->build_asset_list_order($orderColumn, $orderDir);

            $start = max(0, (int) $start);
            $length = (int) $length;
            if ($length <= 0 || $length > 100) {
                $length = 25;
            }

            $totalRecords = Class_db::getInstance()->db_count('vg_asset_datatable', array('contract_id' => $contractId));
            $filteredRecords = ($whereWithSearch === $baseWhere && count($baseWhere) === 1)
                ? $totalRecords
                : Class_db::getInstance()->db_count('vg_asset_datatable', $whereWithSearch);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vg_asset_datatable', $whereWithSearch, $orderSql, $start.','.$length);
            foreach ($arr_dataLocal as $dataLocal) {
                $result[] = $this->map_asset_datatable_row($dataLocal);
            }

            return array(
                'recordsTotal' => intval($totalRecords),
                'recordsFiltered' => intval($filteredRecords),
                'data' => $result
            );
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Stream the filtered asset list as Excel or PDF. Uses the same filters as the table.
     *
     * @param string $contractId
     * @param string $searchValue
     * @param int|null $orderColumn
     * @param string $orderDir
     * @param array $filters
     * @param string $format xlsx|pdf
     * @return void
     * @throws Exception
     */
    public function stream_asset_export ($contractId, $searchValue, $orderColumn, $orderDir, $filters, $format) {
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

        if (empty($contractId)) {
            throw new Exception('[' . __LINE__ . '] - Please select a contract first', 31);
        }

        $format = strtolower(trim((string) $format));
        if ($format !== 'xlsx' && $format !== 'pdf') {
            throw new Exception('[' . __LINE__ . '] - Invalid export format', 31);
        }

        list($baseWhere, $whereWithSearch) = $this->build_asset_list_where($contractId, $searchValue, $filters);
        $orderSql = $this->build_asset_list_order($orderColumn, $orderDir);
        $filteredRecords = intval(Class_db::getInstance()->db_count('vg_asset_datatable', $whereWithSearch));
        if ($filteredRecords < 1) {
            throw new Exception('[' . __LINE__ . '] - No assets to export for the current filters', 31);
        }

        $maxRows = $format === 'pdf' ? 5000 : 20000;
        if ($filteredRecords > $maxRows) {
            $hint = $format === 'pdf'
                ? 'PDF is limited to 5,000 rows. Narrow the filters or export Excel instead.'
                : 'Excel is limited to 20,000 rows. Narrow the filters and try again.';
            throw new Exception('[' . __LINE__ . '] - Too many rows to export ('.$filteredRecords.'). '.$hint, 31);
        }

        $statusMap = array();
        try {
            $statusMap = $this->fn_general->getRefStatus();
        } catch (Exception $e) {
            $statusMap = array(1 => 'Active', 2 => 'Inactive', 5 => 'Archived');
        }

        $headers = array(
            '#', 'Asset Name', 'Asset No', 'Asset Serial No', 'Asset Group', 'Asset Category',
            'Asset Type', 'Brand', 'Model', 'PPM Group', 'Location Code', 'Status'
        );

        @set_time_limit(180);
        @ini_set('memory_limit', '512M');

        $excelRows = array();
        $chunk = 1000;
        $start = 0;
        $rowNo = 0;
        while ($start < $filteredRecords) {
            $arr_dataLocal = Class_db::getInstance()->db_select('vg_asset_datatable', $whereWithSearch, $orderSql, $start.','.$chunk);
            if (empty($arr_dataLocal)) {
                break;
            }
            foreach ($arr_dataLocal as $dataLocal) {
                $row = $this->map_asset_datatable_row($dataLocal);
                $rowNo++;
                $statusId = isset($row['assetStatus']) ? intval($row['assetStatus']) : 0;
                $statusText = isset($statusMap[$statusId]) ? $statusMap[$statusId] : (string) $row['assetStatus'];
                $excelRows[] = array(
                    $rowNo,
                    $row['assetName'],
                    $row['assetNo'],
                    $row['assetSerialNo'],
                    $row['assetGroupName'],
                    $row['assetCategoryName'],
                    $row['assetTypeName'],
                    $row['assetBrandName'],
                    $row['assetModelName'],
                    $row['ppmGroupName'],
                    $row['assetLocationCode'],
                    $statusText
                );
            }
            $start += $chunk;
        }

        $stamp = date('Ymd_His');
        if ($format === 'xlsx') {
            $this->stream_asset_export_xlsx($headers, $excelRows, 'GEMS_asset_list_'.$stamp.'.xlsx');
        } else {
            $this->stream_asset_export_pdf($headers, $excelRows, 'GEMS_asset_list_'.$stamp.'.pdf');
        }
    }

    /**
     * @param array $headers
     * @param array $rows
     * @param string $filename
     * @return void
     * @throws Exception
     */
    private function stream_asset_export_xlsx ($headers, $rows, $filename) {
        $vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        }
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new Exception('[' . __LINE__ . '] - PhpSpreadsheet is not installed. Run composer install.', 31);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Assets');
        $sheet->fromArray($headers, null, 'A1');
        $headerRange = 'A1:'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)).'1';
        $headerStyle = $sheet->getStyle($headerRange);
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('0055B8');
        $sheet->freezePane('A2');

        $rowNum = 2;
        foreach (array_chunk($rows, 1000) as $batch) {
            $sheet->fromArray($batch, null, 'A'.$rowNum);
            $rowNum += count($batch);
        }
        unset($rows);

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        if ($rowNum > 2) {
            $sheet->setAutoFilter('A1:'.$lastCol.($rowNum - 1));
        }
        foreach (range(1, count($headers)) as $colIndex) {
            $sheet->getColumnDimensionByColumn($colIndex)->setAutoSize(true);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: no-cache, must-revalidate');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);
    }

    /**
     * @param array $headers
     * @param array $rows
     * @param string $filename
     * @return void
     * @throws Exception
     */
    private function stream_asset_export_pdf ($headers, $rows, $filename) {
        $tcpdf = dirname(__DIR__) . '/tcpdf/tcpdf.php';
        if (!file_exists($tcpdf)) {
            throw new Exception('[' . __LINE__ . '] - PDF library is not available', 31);
        }
        require_once $tcpdf;

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('GEMS 2.0');
        $pdf->SetAuthor('GEMS 2.0');
        $pdf->SetTitle('GEMS 2.0 - Asset List');
        $pdf->SetMargins(8, 14, 8);
        $pdf->SetHeaderMargin(6);
        $pdf->SetFooterMargin(8);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'GEMS 2.0 - Asset List', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, 'Generated '.date('Y-m-d H:i').'  •  '.count($rows).' row(s)', 0, 1, 'L');
        $pdf->Ln(2);

        $widths = array(10, 36, 26, 24, 24, 24, 24, 22, 22, 24, 22, 19);
        $pdf->SetFillColor(0, 85, 184);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 7);
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetTextColor(36, 55, 70);
        $pdf->SetFont('helvetica', '', 7);
        $fill = false;
        foreach ($rows as $row) {
            if ($pdf->GetY() > 190) {
                $pdf->AddPage();
                $pdf->SetFillColor(0, 85, 184);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('helvetica', 'B', 7);
                foreach ($headers as $i => $header) {
                    $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetTextColor(36, 55, 70);
                $pdf->SetFont('helvetica', '', 7);
            }
            $pdf->SetFillColor(241, 245, 249);
            foreach ($row as $i => $value) {
                $align = $i === 0 ? 'C' : 'L';
                $pdf->Cell($widths[$i], 6, $this->pdf_cell_text((string) $value, $i === 0 ? 6 : 22), 1, 0, $align, $fill);
            }
            $pdf->Ln();
            $fill = !$fill;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $pdf->Output($filename, 'D');
    }

    /**
     * @param string $value
     * @param int $max
     * @return string
     */
    private function pdf_cell_text ($value, $max) {
        $value = trim(preg_replace('/\s+/', ' ', $value));
        if (function_exists('mb_strlen') && mb_strlen($value) > $max) {
            return mb_substr($value, 0, $max - 1).'…';
        }
        if (strlen($value) > $max) {
            return substr($value, 0, $max - 1).'...';
        }
        return $value;
    }

    /**
     * Contract-wide counts and chart buckets. Does not scan every asset row in PHP.
     *
     * @param string $contractId
     * @return array
     * @throws Exception
     */
    public function get_asset_summary ($contractId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractId)) {
                return array(
                    'counts' => array('total' => 0, '1' => 0, '2' => 0, '5' => 0),
                    'chart' => array()
                );
            }

            $counts = array('total' => 0, '1' => 0, '2' => 0, '5' => 0);
            $statusRows = Class_db::getInstance()->db_select('vg_asset_status_count', array('contract_id' => $contractId));
            foreach ($statusRows as $row) {
                $status = isset($row['asset_status']) ? (string) $row['asset_status'] : '';
                $n = isset($row['total']) ? intval($row['total']) : 0;
                $counts['total'] += $n;
                if (isset($counts[$status])) {
                    $counts[$status] = $n;
                }
            }

            $chart = array();
            $chartRows = Class_db::getInstance()->db_select('vg_asset_chart', array('contract_id' => $contractId));
            foreach ($chartRows as $row) {
                $chart[] = array(
                    'assetGroupId' => $this->fn_general->clear_null($row['asset_group_id']),
                    'assetCategoryId' => $this->fn_general->clear_null($row['asset_category_id']),
                    'assetTypeId' => $this->fn_general->clear_null($row['asset_type_id']),
                    'total' => intval($row['total'])
                );
            }

            return array('counts' => $counts, 'chart' => $chart);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @return array
     * @throws Exception
     */
    public function get_asset ($assetId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$assetId), null, 1);
            $result['assetId'] = $dataLocal['asset_id'];
            $result['assetNo'] = $this->fn_general->clear_null($dataLocal['asset_no']);
            $result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
            $result['assetSerialNo'] = $this->fn_general->clear_null($dataLocal['asset_serial_no']);
            $result['assetDesc'] = $this->fn_general->clear_null($dataLocal['asset_desc']);
            $result['assetCapacity'] = $this->fn_general->clear_null($dataLocal['asset_capacity']);
            $result['assetLocationCode'] = $this->fn_general->clear_null($dataLocal['asset_location_code']);
            $result['assetLocationDesc'] = $this->fn_general->clear_null($dataLocal['asset_location_desc']);
            $result['assetGroupId'] = $this->fn_general->clear_null($dataLocal['asset_group_id']);
            $result['assetCategoryId'] = $this->fn_general->clear_null($dataLocal['asset_category_id']);
            $result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
            $result['assetBrandId'] = $this->fn_general->clear_null($dataLocal['asset_brand_id']);
            $result['assetModelId'] = $this->fn_general->clear_null($dataLocal['asset_model_id']);
            $result['contractId'] = $this->fn_general->clear_null($dataLocal['contract_id']);
            $result['ppmGroupId'] = $this->fn_general->clear_null($dataLocal['ppm_group_id']);
            $result['zoneId'] = $this->fn_general->clear_null($dataLocal['zone_id']);
            $result['assetBlock'] = $this->fn_general->clear_null($dataLocal['asset_block']);
            $result['assetLevel'] = $this->fn_general->clear_null($dataLocal['asset_level']);
            $result['assetManufacturer'] = $this->fn_general->clear_null($dataLocal['asset_manufacturer']);
            $result['assetSupplier'] = $this->fn_general->clear_null($dataLocal['asset_supplier']);
            $result['assetAgency'] = $this->fn_general->clear_null($dataLocal['asset_agency']);
            $result['assetDepartment'] = $this->fn_general->clear_null($dataLocal['asset_department']);
            $result['assetConstructionZone'] = $this->fn_general->clear_null($dataLocal['asset_construction_zone']);
            $result['assetOperationZone'] = $this->fn_general->clear_null($dataLocal['asset_operation_zone']);
            $result['assetRoom'] = $this->fn_general->clear_null($dataLocal['asset_room']);
            $result['assetCompartment'] = $this->fn_general->clear_null($dataLocal['asset_compartment']);
            $result['assetAuthEmployee'] = $this->fn_general->clear_null($dataLocal['asset_auth_employee']);
            $result['assetCriticality'] = $this->fn_general->clear_null($dataLocal['asset_criticality']);
            $result['assetContractor'] = $this->fn_general->clear_null($dataLocal['asset_contractor']);
            $result['assetWarranty'] = $this->fn_general->clear_null($dataLocal['asset_warranty']);
            $result['assetWarrantyExpDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_warranty_exp_date']));
            $result['assetLifeCycle'] = $this->fn_general->clear_null($dataLocal['asset_life_cycle']);
            $result['assetWarrantyNotes'] = $this->fn_general->clear_null($dataLocal['asset_warranty_notes']);
            $result['assetTechnicianNotes'] = $this->fn_general->clear_null($dataLocal['asset_technician_notes']);
            $result['assetPurchasePrice'] = $this->fn_general->clear_null($dataLocal['asset_purchase_price']);
            $result['assetCommissionedDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_commissioned_date']));
            $result['assetDisposedDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_disposed_date']));
            $result['assetCurrentValue'] = $this->fn_general->clear_null($dataLocal['asset_current_value']);
            $result['assetEstimatedLife'] = $this->fn_general->clear_null($dataLocal['asset_estimated_life']);
            $result['assetLifetimeDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_lifetime_date']));
            $result['assetTimeRegistered'] = str_replace('-', '/', $dataLocal['asset_time_registered']);
            $result['assetTimeCreated'] = str_replace('-', '/', $dataLocal['asset_time_created']);
            $result['assetRegisteredBy'] = $this->fn_general->clear_null($dataLocal['asset_registered_by']);
            $result['assetStatus'] = $dataLocal['asset_status'];

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function create_asset () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            return Class_db::getInstance()->db_insert('ast_asset', array('asset_status'=>'5'));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Insert a fully-validated Active asset (used by Excel import).
     *
     * @param array $params Resolved field map (IDs already looked up)
     * @param string $userId
     * @return mixed
     * @throws Exception
     */
    public function import_registered_asset ($params, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($params) || empty($params['contractId']) || empty($params['assetNo']) || empty($params['assetName'])) {
                throw new Exception('[' . __LINE__ . '] - Required import parameters empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $contractId = $params['contractId'];
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_no'=>$params['assetNo'], 'contract_id'=>$contractId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR, 31);
            }
            if (!empty($params['assetSerialNo'])
                && Class_db::getInstance()->db_count('ast_asset', array('asset_serial_no'=>$params['assetSerialNo'], 'contract_id'=>$contractId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR_SERIAL_NO, 31);
            }

            $insert = array(
                'contract_id' => $contractId,
                'asset_name' => $params['assetName'],
                'asset_no' => $params['assetNo'],
                'asset_location_code' => $params['assetLocationCode'],
                'asset_group_id' => $params['assetGroupId'],
                'asset_category_id' => $params['assetCategoryId'],
                'asset_type_id' => $params['assetTypeId'],
                'asset_status' => '1',
                'asset_registered_by' => $userId,
                'asset_time_registered' => 'Now()'
            );

            $optionalMap = array(
                'assetSerialNo' => 'asset_serial_no',
                'assetDesc' => 'asset_desc',
                'assetBrandId' => 'asset_brand_id',
                'assetModelId' => 'asset_model_id',
                'ppmGroupId' => 'ppm_group_id',
                'zoneId' => 'zone_id',
                'assetCapacity' => 'asset_capacity',
                'assetLocationDesc' => 'asset_location_desc',
                'assetBlock' => 'asset_block',
                'assetLevel' => 'asset_level',
                'assetManufacturer' => 'asset_manufacturer',
                'assetSupplier' => 'asset_supplier',
                'assetAgency' => 'asset_agency',
                'assetDepartment' => 'asset_department',
                'assetConstructionZone' => 'asset_construction_zone',
                'assetOperationZone' => 'asset_operation_zone',
                'assetRoom' => 'asset_room',
                'assetCompartment' => 'asset_compartment',
                'assetAuthEmployee' => 'asset_auth_employee',
                'assetCriticality' => 'asset_criticality',
                'assetContractor' => 'asset_contractor',
                'assetWarranty' => 'asset_warranty',
                'assetWarrantyExpDate' => 'asset_warranty_exp_date',
                'assetWarrantyNotes' => 'asset_warranty_notes',
                'assetTechnicianNotes' => 'asset_technician_notes'
            );
            foreach ($optionalMap as $src => $column) {
                if (isset($params[$src]) && $params[$src] !== '' && $params[$src] !== null) {
                    $insert[$column] = $params[$src];
                }
            }

            return Class_db::getInstance()->db_insert('ast_asset', $insert);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @throws Exception
     */
    public function submit_asset ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($this->assetId)) { throw new Exception('[' . __LINE__ . '] - Parameter assetId empty'); }
            if (empty($userId)) { throw new Exception('[' . __LINE__ . '] - Parameter userId empty'); }

            $assetStatus = Class_db::getInstance()->db_select_col('ast_asset', array('asset_id'=>$this->assetId), 'asset_status', null, 1);
            if ($assetStatus !== '5') { throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SUBMITTED, 31); }

            $updateArr = array(
                'asset_registered_by'=>$userId,
                'asset_time_registered'=>'Now()',
                'asset_status'=>'1'
            );
            Class_db::getInstance()->db_update('ast_asset', $updateArr, array('asset_id'=>$this->assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $putVars
     * @throws Exception
     */
    public function update_asset ($putVars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($this->assetId)) { throw new Exception('[' . __LINE__ . '] - Parameter assetId empty', 31); }
            if (empty($putVars)) { throw new Exception('[' . __LINE__ . '] - Array putVars empty'); }

            $contractId = Class_db::getInstance()->db_select_col('ast_asset', array('asset_id'=>$this->assetId), 'contract_id', null, 1);
            $params = array();
            if (isset($putVars['assetNo'])) {                   $params['asset_no'] = $putVars['assetNo']; }
            if (isset($putVars['assetSerialNo'])) {             $params['asset_serial_no'] = $putVars['assetSerialNo']; }
            if (isset($putVars['contractId'])) {                $params['contract_id'] = $putVars['contractId']; }
            if (isset($putVars['assetName'])) {                 $params['asset_name'] = $putVars['assetName']; }
            if (isset($putVars['assetDesc'])) {                 $params['asset_desc'] = $putVars['assetDesc']; }
            if (isset($putVars['assetGroupId'])) {              $params['asset_group_id'] = $putVars['assetGroupId']; }
            if (isset($putVars['assetCategoryId'])) {           $params['asset_category_id'] = $putVars['assetCategoryId']; }
            if (isset($putVars['assetTypeId'])) {               $params['asset_type_id'] = $putVars['assetTypeId']; }
            if (isset($putVars['assetBrandId'])) {              $params['asset_brand_id'] = $putVars['assetBrandId']; }
            if (isset($putVars['assetModelId'])) {              $params['asset_model_id'] = $putVars['assetModelId']; }
            if (isset($putVars['zoneId'])) {                    $params['zone_id'] = $putVars['zoneId']; }
            if (isset($putVars['assetLocationCode'])) {         $params['asset_location_code'] = $putVars['assetLocationCode']; }
            if (isset($putVars['assetLocationDesc'])) {         $params['asset_location_desc'] = $putVars['assetLocationDesc']; }
            if (isset($putVars['ppmGroupId'])) {                $params['ppm_group_id'] = $putVars['ppmGroupId']; }
            if (isset($putVars['assetCapacity'])) {             $params['asset_capacity'] = $putVars['assetCapacity']; }
            if (isset($putVars['assetBlock'])) {                $params['asset_block'] = $putVars['assetBlock']; }
            if (isset($putVars['assetLevel'])) {                $params['asset_level'] = $putVars['assetLevel']; }
            if (isset($putVars['assetManufacturer'])) {         $params['asset_manufacturer'] = $putVars['assetManufacturer']; }
            if (isset($putVars['assetSupplier'])) {             $params['asset_supplier'] = $putVars['assetSupplier']; }
            if (isset($putVars['assetAgency'])) {               $params['asset_agency'] = $putVars['assetAgency']; }
            if (isset($putVars['assetDepartment'])) {           $params['asset_department'] = $putVars['assetDepartment']; }
            if (isset($putVars['assetConstructionZone'])) {     $params['asset_construction_zone'] = $putVars['assetConstructionZone']; }
            if (isset($putVars['assetOperationZone'])) {        $params['asset_operation_zone'] = $putVars['assetOperationZone']; }
            if (isset($putVars['assetRoom'])) {                 $params['asset_room'] = $putVars['assetRoom']; }
            if (isset($putVars['assetCompartment'])) {          $params['asset_compartment'] = $putVars['assetCompartment']; }
            if (isset($putVars['assetAuthEmployee'])) {         $params['asset_auth_employee'] = $putVars['assetAuthEmployee']; }
            if (isset($putVars['assetCriticality'])) {          $params['asset_criticality'] = $putVars['assetCriticality']; }
            if (isset($putVars['assetContractor'])) {           $params['asset_contractor'] = $putVars['assetContractor']; }
            if (isset($putVars['assetWarranty'])) {             $params['asset_warranty'] = $putVars['assetWarranty']; }
            if (isset($putVars['assetWarrantyExpDate'])) {      $params['asset_warranty_exp_date'] = $putVars['assetWarrantyExpDate']; }
            if (isset($putVars['assetLifeCycle'])) {            $params['asset_life_cycle'] = $putVars['assetLifeCycle']; }
            if (isset($putVars['assetWarrantyNotes'])) {        $params['asset_warranty_notes'] = $putVars['assetWarrantyNotes']; }
            if (isset($putVars['assetTechnicianNotes'])) {      $params['asset_technician_notes'] = $putVars['assetTechnicianNotes']; }
            if (isset($putVars['assetPurchasePrice'])) {        $params['asset_purchase_price'] = $putVars['assetPurchasePrice']; }
            if (isset($putVars['assetCommissionedDate'])) {     $params['asset_commissioned_date'] = $putVars['assetCommissionedDate']; }
            if (isset($putVars['assetDisposedDate'])) {         $params['asset_disposed_date'] = $putVars['assetDisposedDate']; }
            if (isset($putVars['assetCurrentValue'])) {         $params['asset_current_value'] = $putVars['assetCurrentValue']; }
            if (isset($putVars['assetEstimatedLife'])) {        $params['asset_estimated_life'] = $putVars['assetEstimatedLife']; }
            if (isset($putVars['assetLifetimeDate'])) {         $params['asset_lifetime_date'] = $putVars['assetLifetimeDate']; }

            if (isset($params['asset_no']) && !empty($params['asset_no'])
                && Class_db::getInstance()->db_count('ast_asset', array('asset_no'=>$params['asset_no'], 'contract_id'=>$contractId, 'asset_id'=>'<>'.$this->assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR, 31);
            }
            if (isset($params['asset_serial_no']) && !empty($params['asset_serial_no'])
                && Class_db::getInstance()->db_count('ast_asset', array('asset_serial_no'=>$params['asset_serial_no'], 'contract_id'=>$contractId, 'asset_id'=>'<>'.$this->assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR_SERIAL_NO, 31);
            }
            Class_db::getInstance()->db_update('ast_asset', $params, array('asset_id'=>$this->assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @throws Exception
     */
    public function deactivate_asset ($assetId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_id'=>$assetId, 'asset_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset', array('asset_status'=>'2'), array('asset_id'=>$assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @throws Exception
     */
    public function activate_asset ($assetId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_id'=>$assetId, 'asset_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset', array('asset_status'=>'1'), array('asset_id'=>$assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @throws Exception
     */
    public function delete_asset ($assetId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_id'=>$assetId, 'asset_status'=>'5')) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset data not exist');
            }
            if (Class_db::getInstance()->db_count('ppm', array('asset_id'=>$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_DELETE_PPM, 31);
            }

            Class_db::getInstance()->db_delete('ast_asset', array('asset_id'=>$assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $siteId
     * @return
     * @throws Exception
     */
    public function get_total_asset ($clientId='', $siteId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }

            if (empty($siteId)) {
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                $siteIdStr = '('.implode(',', $siteIds).')';
            } else {
                $siteIdStr = $siteId;
            }
            $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id'=>$siteIdStr, 'contract_status'=>'1'), 'contract_id');
            if (!empty($contractIds)) {
                $contractId = '('.implode(',', $contractIds).')';
                return Class_db::getInstance()->db_select_col('vw_count_asset', array('contract_id'=>$contractId), 'total');
            }
            return '';
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
