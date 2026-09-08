<?php

class Class_asset_import {

    const MAX_ROWS = 1000;
    const MAX_FILE_BYTES = 10485760;

    public $constant;
    public $fn_general;
    public $fn_asset;

    private $headerAliases = array(
        'asset name' => 'assetName',
        'asset no' => 'assetNo',
        'asset no.' => 'assetNo',
        'asset number' => 'assetNo',
        'asset serial no' => 'assetSerialNo',
        'asset serial no.' => 'assetSerialNo',
        'serial no' => 'assetSerialNo',
        'serial no.' => 'assetSerialNo',
        'description' => 'assetDesc',
        'asset description' => 'assetDesc',
        'asset group' => 'assetGroup',
        'asset category' => 'assetCategory',
        'asset type' => 'assetType',
        'brand' => 'assetBrand',
        'asset brand' => 'assetBrand',
        'model' => 'assetModel',
        'asset model' => 'assetModel',
        'capacity' => 'assetCapacity',
        'ppm group' => 'ppmGroup',
        'zone code' => 'zoneCode',
        'zone' => 'zoneCode',
        'location code' => 'assetLocationCode',
        'location description' => 'assetLocationDesc',
        'block' => 'assetBlock',
        'asset block' => 'assetBlock',
        'level' => 'assetLevel',
        'asset level' => 'assetLevel',
        'manufacturer' => 'assetManufacturer',
        'supplier' => 'assetSupplier',
        'agency' => 'assetAgency',
        'department' => 'assetDepartment',
        'construction zone' => 'assetConstructionZone',
        'operation zone' => 'assetOperationZone',
        'room' => 'assetRoom',
        'compartment' => 'assetCompartment',
        'authentication employee' => 'assetAuthEmployee',
        'auth employee' => 'assetAuthEmployee',
        'asset criticality' => 'assetCriticality',
        'criticality' => 'assetCriticality',
        'contractor' => 'assetContractor',
        'warranty' => 'assetWarranty',
        'warranty / contract' => 'assetWarranty',
        'warranty exp date' => 'assetWarrantyExpDate',
        'warranty expired date' => 'assetWarrantyExpDate',
        'warranty / contract notes' => 'assetWarrantyNotes',
        'warranty notes' => 'assetWarrantyNotes',
        'note to technician' => 'assetTechnicianNotes',
        'technician notes' => 'assetTechnicianNotes'
    );

    private $requiredKeys = array('assetName', 'assetNo', 'assetLocationCode', 'assetGroup', 'assetCategory', 'assetType');

    private $maxLengths = array(
        'assetName' => 150,
        'assetNo' => 100,
        'assetSerialNo' => 100,
        'assetDesc' => 1000,
        'assetCapacity' => 30,
        'assetLocationCode' => 100,
        'assetLocationDesc' => 255,
        'assetBlock' => 30,
        'assetLevel' => 30,
        'assetManufacturer' => 100,
        'assetSupplier' => 100,
        'assetAgency' => 100,
        'assetDepartment' => 100,
        'assetConstructionZone' => 50,
        'assetOperationZone' => 50,
        'assetRoom' => 50,
        'assetCompartment' => 50,
        'assetAuthEmployee' => 150,
        'assetCriticality' => 50,
        'assetContractor' => 100,
        'assetWarranty' => 50,
        'assetWarrantyNotes' => 1000,
        'assetTechnicianNotes' => 1000
    );

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false) {
                $msg = substr($msg, $pos + 2);
            }
            return '(ErrCode:'.$codes.') ['.__CLASS__.':'.$function.':'.$line.'] - '.$msg;
        }
        return '(ErrCode:'.$codes.') ['.__CLASS__.':'.$function.':'.$line.']';
    }

    private function norm($value) {
        return strtolower(trim((string)$value));
    }

    private function str_len($value) {
        return function_exists('mb_strlen') ? mb_strlen((string)$value) : strlen((string)$value);
    }

    private function cell_string($value) {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value)) {
            return (string)$value;
        }
        if (is_float($value)) {
            if (floor($value) == $value) {
                return (string)(int)$value;
            }
            return trim(rtrim(rtrim(sprintf('%.8F', $value), '0'), '.'));
        }
        return trim((string)$value);
    }

    private function require_phpspreadsheet() {
        $vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        }
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new Exception('[' . __LINE__ . '] - PhpSpreadsheet is not installed. Run composer install or convert the file to a supported format.');
        }
    }

    /**
     * @param string $contractId
     * @param string $userSite
     * @param bool $isAdministrator
     * @return array
     * @throws Exception
     */
    public function get_contract_context($contractId, $userSite, $isAdministrator) {
        if (empty($contractId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
        }
        $contract = Class_db::getInstance()->db_select_single('cli_contract', array('contract_id'=>$contractId), null, 1);
        if (!$isAdministrator && !empty($userSite) && (string)$contract['site_id'] !== (string)$userSite) {
            throw new Exception('[' . __LINE__ . '] - Access denied to contract from a different site');
        }
        $site = Class_db::getInstance()->db_select_single('cli_site', array('site_id'=>$contract['site_id']), null, 1);
        $client = array();
        if (!empty($site['client_id'])) {
            $client = Class_db::getInstance()->db_select_single('cli_client', array('client_id'=>$site['client_id']), null, 0);
        }
        return array(
            'contractId' => $contract['contract_id'],
            'contractName' => $contract['contract_name'],
            'siteId' => $contract['site_id'],
            'siteName' => isset($site['site_name']) ? $site['site_name'] : '',
            'clientId' => isset($site['client_id']) ? $site['client_id'] : '',
            'clientName' => (!empty($client) && isset($client['client_name'])) ? $client['client_name'] : ''
        );
    }

    /**
     * @param string $siteId
     * @return array
     */
    private function load_lookups($siteId) {
        $lookups = array(
            'groups' => array(),
            'categories' => array(),
            'types' => array(),
            'brands' => array(),
            'brandTypes' => array(),
            'models' => array(),
            'zones' => array(),
            'ppmGroups' => array(),
            'refGroups' => array(),
            'refCategories' => array(),
            'refTypes' => array(),
            'refBrands' => array(),
            'refModels' => array(),
            'refZones' => array(),
            'refPpmGroups' => array()
        );

        foreach (Class_db::getInstance()->db_select('ast_asset_group') as $row) {
            $lookups['groups'][$this->norm($row['asset_group_name'])] = $row;
            if ((string)$row['asset_group_status'] === '1') {
                $lookups['refGroups'][] = $row['asset_group_name'];
            }
        }
        foreach (Class_db::getInstance()->db_select('ast_asset_category') as $row) {
            $groupId = $row['asset_group_id'];
            if (!isset($lookups['categories'][$groupId])) {
                $lookups['categories'][$groupId] = array();
            }
            $lookups['categories'][$groupId][$this->norm($row['asset_category_name'])] = $row;
            if ((string)$row['asset_category_status'] === '1') {
                $lookups['refCategories'][] = $row;
            }
        }
        foreach (Class_db::getInstance()->db_select('ast_asset_type') as $row) {
            $categoryId = $row['asset_category_id'];
            if (!isset($lookups['types'][$categoryId])) {
                $lookups['types'][$categoryId] = array();
            }
            $lookups['types'][$categoryId][$this->norm($row['asset_type_name'])] = $row;
            if ((string)$row['asset_type_status'] === '1') {
                $lookups['refTypes'][] = $row;
            }
        }
        foreach (Class_db::getInstance()->db_select('ast_asset_brand') as $row) {
            $lookups['brands'][$this->norm($row['asset_brand_name'])] = $row;
        }
        foreach (Class_db::getInstance()->db_select('vw_asset_brand_group') as $row) {
            if (empty($row['asset_brand_id']) || empty($row['asset_type_id'])) {
                continue;
            }
            $lookups['brandTypes'][$row['asset_brand_id']][$row['asset_type_id']] = $row;
            if ((string)$row['asset_brand_status'] === '1') {
                $lookups['refBrands'][] = $row;
            }
        }
        foreach (Class_db::getInstance()->db_select('ast_asset_model') as $row) {
            $brandId = $row['asset_brand_id'];
            $typeId = $row['asset_type_id'];
            if (!isset($lookups['models'][$brandId])) {
                $lookups['models'][$brandId] = array();
            }
            if (!isset($lookups['models'][$brandId][$typeId])) {
                $lookups['models'][$brandId][$typeId] = array();
            }
            $lookups['models'][$brandId][$typeId][$this->norm($row['asset_model_name'])] = $row;
            if ((string)$row['asset_model_status'] === '1') {
                $lookups['refModels'][] = $row;
            }
        }
        foreach (Class_db::getInstance()->db_select('cli_zone', array('site_id'=>$siteId)) as $row) {
            $lookups['zones'][$this->norm($row['zone_code'])] = $row;
            if ((string)$row['zone_status'] === '1') {
                $lookups['refZones'][] = $row;
            }
        }
        foreach (Class_db::getInstance()->db_select('ppm_group', array('site_id'=>$siteId, 'role_id'=>'5')) as $row) {
            $lookups['ppmGroups'][$this->norm($row['ppm_group_name'])] = $row;
            if ((string)$row['ppm_group_status'] === '1') {
                $lookups['refPpmGroups'][] = $row['ppm_group_name'];
            }
        }

        $lookups['groupNameById'] = array();
        foreach ($lookups['groups'] as $group) {
            $lookups['groupNameById'][$group['asset_group_id']] = $group['asset_group_name'];
        }
        $lookups['categoryNameById'] = array();
        $lookups['categoryGroupById'] = array();
        foreach ($lookups['categories'] as $groupId => $cats) {
            foreach ($cats as $cat) {
                $lookups['categoryNameById'][$cat['asset_category_id']] = $cat['asset_category_name'];
                $lookups['categoryGroupById'][$cat['asset_category_id']] = $groupId;
            }
        }
        $lookups['typeNameById'] = array();
        foreach ($lookups['types'] as $types) {
            foreach ($types as $type) {
                $lookups['typeNameById'][$type['asset_type_id']] = $type['asset_type_name'];
            }
        }
        $lookups['brandNameById'] = array();
        foreach ($lookups['brands'] as $brand) {
            $lookups['brandNameById'][$brand['asset_brand_id']] = $brand['asset_brand_name'];
        }

        return $lookups;
    }

    private function load_existing_keys($contractId) {
        $assetNos = array();
        $serialNos = array();
        foreach (Class_db::getInstance()->db_select('ast_asset', array('contract_id'=>$contractId)) as $row) {
            $no = $this->norm($row['asset_no']);
            if ($no !== '') {
                $assetNos[$no] = true;
            }
            $serial = $this->norm($this->fn_general->clear_null($row['asset_serial_no']));
            if ($serial !== '') {
                $serialNos[$serial] = true;
            }
        }
        return array($assetNos, $serialNos);
    }

    /**
     * @param string $contractId
     * @param string $userSite
     * @param bool $isAdministrator
     * @throws Exception
     */
    public function download_template($contractId, $userSite, $isAdministrator) {
        $this->require_phpspreadsheet();
        $context = $this->get_contract_context($contractId, $userSite, $isAdministrator);
        $lookups = $this->load_lookups($context['siteId']);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $infoSheet = $spreadsheet->getActiveSheet();
        $infoSheet->setTitle('Instructions');
        $infoRows = array(
            array('Asset Import Template'),
            array(''),
            array('Target Contract', $context['contractName']),
            array('Site', $context['siteName']),
            array('Client', $context['clientName']),
            array(''),
            array('All rows are created as Active assets under this contract. Do not add Client or Contract columns.'),
            array('Names must match existing active records on the Reference sheet. New Group / Category / Type / Brand / Model will not be created.'),
            array('Maximum '.$this::MAX_ROWS.' data rows per file.'),
            array('Warranty Exp Date accepts YYYY-MM-DD or DD/MM/YYYY.'),
            array(''),
            array('Required columns', 'Asset Name, Asset No, Location Code, Asset Group, Asset Category, Asset Type')
        );
        $infoSheet->fromArray($infoRows, null, 'A1');
        $infoSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $infoSheet->getColumnDimension('A')->setWidth(28);
        $infoSheet->getColumnDimension('B')->setWidth(80);

        $assetSheet = $spreadsheet->createSheet();
        $assetSheet->setTitle('Assets');
        $headers = array(
            'Asset Name', 'Asset No', 'Asset Serial No', 'Description', 'Asset Group', 'Asset Category', 'Asset Type',
            'Brand', 'Model', 'Capacity', 'PPM Group', 'Zone Code', 'Location Code', 'Location Description',
            'Block', 'Level', 'Manufacturer', 'Supplier', 'Agency', 'Department', 'Construction Zone',
            'Operation Zone', 'Room', 'Compartment', 'Auth Employee', 'Criticality', 'Contractor',
            'Warranty', 'Warranty Exp Date', 'Warranty Notes', 'Technician Notes'
        );
        $assetSheet->fromArray($headers, null, 'A1');
        $assetSheet->fromArray(array(
            'Chiller Unit 1', 'CH-L1-01', '', 'Main plant chiller', 'Mechanical', 'HVAC', 'Chiller',
            '', '', '', '', '', 'PLANT-01', 'Level 1 Plant Room'
        ), null, 'A2');
        $headerRange = 'A1:'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)).'1';
        $assetSheet->getStyle($headerRange)->getFont()->setBold(true);
        $assetSheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('0055B8');
        $assetSheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');
        $assetSheet->freezePane('A2');
        foreach (range(1, count($headers)) as $col) {
            $assetSheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Reference');
        $refSheet->fromArray(array(
            'Asset Group', '', 'Asset Group', 'Asset Category', '', 'Asset Group', 'Asset Category', 'Asset Type',
            '', 'Asset Type', 'Brand', '', 'Asset Type', 'Brand', 'Model', '', 'Zone Code', 'Zone Name', '', 'PPM Group'
        ), null, 'A1');
        $refSheet->getStyle('A1:T1')->getFont()->setBold(true);

        $maxRows = max(
            count($lookups['refGroups']),
            count($lookups['refCategories']),
            count($lookups['refTypes']),
            count($lookups['refBrands']),
            count($lookups['refModels']),
            count($lookups['refZones']),
            count($lookups['refPpmGroups']),
            1
        );
        for ($i = 0; $i < $maxRows; $i++) {
            $rowNum = $i + 2;
            if (isset($lookups['refGroups'][$i])) {
                $refSheet->setCellValue('A'.$rowNum, $lookups['refGroups'][$i]);
            }
            if (isset($lookups['refCategories'][$i])) {
                $cat = $lookups['refCategories'][$i];
                $groupName = isset($lookups['groupNameById'][$cat['asset_group_id']]) ? $lookups['groupNameById'][$cat['asset_group_id']] : '';
                $refSheet->setCellValue('C'.$rowNum, $groupName);
                $refSheet->setCellValue('D'.$rowNum, $cat['asset_category_name']);
            }
            if (isset($lookups['refTypes'][$i])) {
                $type = $lookups['refTypes'][$i];
                $catName = isset($lookups['categoryNameById'][$type['asset_category_id']]) ? $lookups['categoryNameById'][$type['asset_category_id']] : '';
                $groupId = isset($lookups['categoryGroupById'][$type['asset_category_id']]) ? $lookups['categoryGroupById'][$type['asset_category_id']] : '';
                $groupName = isset($lookups['groupNameById'][$groupId]) ? $lookups['groupNameById'][$groupId] : '';
                $refSheet->setCellValue('F'.$rowNum, $groupName);
                $refSheet->setCellValue('G'.$rowNum, $catName);
                $refSheet->setCellValue('H'.$rowNum, $type['asset_type_name']);
            }
            if (isset($lookups['refBrands'][$i])) {
                $brand = $lookups['refBrands'][$i];
                $typeName = isset($lookups['typeNameById'][$brand['asset_type_id']]) ? $lookups['typeNameById'][$brand['asset_type_id']] : '';
                $refSheet->setCellValue('J'.$rowNum, $typeName);
                $refSheet->setCellValue('K'.$rowNum, $brand['asset_brand_name']);
            }
            if (isset($lookups['refModels'][$i])) {
                $model = $lookups['refModels'][$i];
                $typeName = isset($lookups['typeNameById'][$model['asset_type_id']]) ? $lookups['typeNameById'][$model['asset_type_id']] : '';
                $brandName = isset($lookups['brandNameById'][$model['asset_brand_id']]) ? $lookups['brandNameById'][$model['asset_brand_id']] : '';
                $refSheet->setCellValue('M'.$rowNum, $typeName);
                $refSheet->setCellValue('N'.$rowNum, $brandName);
                $refSheet->setCellValue('O'.$rowNum, $model['asset_model_name']);
            }
            if (isset($lookups['refZones'][$i])) {
                $refSheet->setCellValue('Q'.$rowNum, $lookups['refZones'][$i]['zone_code']);
                $refSheet->setCellValue('R'.$rowNum, $lookups['refZones'][$i]['zone_name']);
            }
            if (isset($lookups['refPpmGroups'][$i])) {
                $refSheet->setCellValue('T'.$rowNum, $lookups['refPpmGroups'][$i]);
            }
        }
        foreach (range('A', 'T') as $col) {
            $refSheet->getColumnDimension($col)->setAutoSize(true);
        }
        $refSheet->freezePane('A2');

        $spreadsheet->setActiveSheetIndex(1);
        $filename = 'asset_import_'.$this->safe_filename($context['contractName']).'.xlsx';

        Class_db::getInstance()->db_close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);
        exit;
    }

    private function safe_filename($name) {
        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string)$name);
        $safe = trim($safe, '_');
        return $safe !== '' ? $safe : 'contract';
    }

    /**
     * @param array $file
     * @return array
     * @throws Exception
     */
    public function parse_excel($file) {
        $this->require_phpspreadsheet();
        if (empty($file) || !isset($file['tmp_name'])) {
            throw new Exception('[' . __LINE__ . '] - No file uploaded');
        }
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('[' . __LINE__ . '] - File upload failed');
        }
        if (isset($file['size']) && $file['size'] > $this::MAX_FILE_BYTES) {
            throw new Exception('[' . __LINE__ . '] - File size too large. Maximum size is 10MB.');
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, array('xlsx', 'xls'), true)) {
            throw new Exception('[' . __LINE__ . '] - Only Excel files (.xlsx, .xls) are allowed');
        }
        if ($extension === 'xlsx' && !class_exists('ZipArchive')) {
            throw new Exception('[' . __LINE__ . '] - Excel .xlsx import requires the PHP Zip extension.');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        $worksheet = null;
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if ($this->norm($sheet->getTitle()) === 'assets') {
                $worksheet = $sheet;
                break;
            }
        }
        if ($worksheet === null) {
            $worksheet = $spreadsheet->getActiveSheet();
            if (in_array($this->norm($worksheet->getTitle()), array('instructions', 'reference'), true)) {
                foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                    if (!in_array($this->norm($sheet->getTitle()), array('instructions', 'reference'), true)) {
                        $worksheet = $sheet;
                        break;
                    }
                }
            }
        }

        $sheetData = $worksheet->toArray(null, true, true, true);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (empty($sheetData)) {
            throw new Exception('[' . __LINE__ . '] - Excel file is empty');
        }

        $headerRow = reset($sheetData);
        $headerRowNumber = key($sheetData);
        $columnMap = array();
        foreach ($headerRow as $colKey => $headerValue) {
            $alias = $this->norm($headerValue);
            if ($alias === '' || !isset($this->headerAliases[$alias])) {
                continue;
            }
            $columnMap[$colKey] = $this->headerAliases[$alias];
        }

        $missing = array();
        $mappedKeys = array_values($columnMap);
        foreach ($this->requiredKeys as $requiredKey) {
            if (!in_array($requiredKey, $mappedKeys, true)) {
                $missing[] = $requiredKey;
            }
        }
        if (!empty($missing)) {
            throw new Exception('[' . __LINE__ . '] - Missing required columns: '.implode(', ', $missing).'. Download the template and keep the header row.');
        }

        $rows = array();
        foreach ($sheetData as $rowNumber => $rowData) {
            if ((string)$rowNumber === (string)$headerRowNumber) {
                continue;
            }
            $mapped = array();
            $hasData = false;
            foreach ($columnMap as $colKey => $fieldKey) {
                $value = isset($rowData[$colKey]) ? $this->cell_string($rowData[$colKey]) : '';
                $mapped[$fieldKey] = $value;
                if ($value !== '') {
                    $hasData = true;
                }
            }
            if (!$hasData) {
                continue;
            }
            $mapped['row_number'] = (int)$rowNumber;
            $rows[] = $mapped;
        }

        if (count($rows) > $this::MAX_ROWS) {
            throw new Exception('[' . __LINE__ . '] - Too many rows ('.count($rows).'). Maximum is '.$this::MAX_ROWS.' per file.');
        }

        return $rows;
    }

    private function parse_date($value) {
        $value = $this->cell_string($value);
        if ($value === '') {
            return '';
        }
        if (is_numeric($value) && (float)$value > 20000) {
            $unix = ((int)$value - 25569) * 86400;
            return gmdate('Y-m-d', $unix);
        }
        $formats = array('Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'Y-m-d H:i:s', 'd/m/Y H:i:s', 'd/m/Y H:i', 'Y/m/d H:i:s');
        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $value);
            if ($dt instanceof DateTime) {
                $errors = DateTime::getLastErrors();
                if ($errors === false || ((int)$errors['warning_count'] === 0 && (int)$errors['error_count'] === 0)) {
                    return $dt->format('Y-m-d');
                }
            }
        }
        $ts = strtotime($value);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
        return false;
    }

    /**
     * @param array $rows
     * @param array $context
     * @return array
     */
    public function validate_rows($rows, $context) {
        $lookups = $this->load_lookups($context['siteId']);
        list($existingNos, $existingSerials) = $this->load_existing_keys($context['contractId']);
        $fileNos = array();
        $fileSerials = array();
        $previewRows = array();
        $validRows = array();

        foreach ($rows as $row) {
            $errors = array();
            $params = array(
                'contractId' => $context['contractId']
            );

            foreach ($this->requiredKeys as $requiredKey) {
                if (!isset($row[$requiredKey]) || $this->cell_string($row[$requiredKey]) === '') {
                    $errors[] = $this->label_for_key($requiredKey).' is required';
                }
            }

            foreach ($this->maxLengths as $fieldKey => $max) {
                if (!empty($row[$fieldKey]) && $this->str_len($row[$fieldKey]) > $max) {
                    $errors[] = $this->label_for_key($fieldKey).' exceeds '.$max.' characters';
                }
            }

            $assetNo = isset($row['assetNo']) ? $this->cell_string($row['assetNo']) : '';
            $serialNo = isset($row['assetSerialNo']) ? $this->cell_string($row['assetSerialNo']) : '';
            $assetNoKey = $this->norm($assetNo);
            $serialKey = $this->norm($serialNo);

            if ($assetNoKey !== '') {
                if (isset($existingNos[$assetNoKey])) {
                    $errors[] = 'Asset No already exists in this contract';
                }
                if (isset($fileNos[$assetNoKey])) {
                    $errors[] = 'Asset No is duplicated in this file (also on row '.$fileNos[$assetNoKey].')';
                } else {
                    $fileNos[$assetNoKey] = $row['row_number'];
                }
            }
            if ($serialKey !== '') {
                if (isset($existingSerials[$serialKey])) {
                    $errors[] = 'Asset Serial No already exists in this contract';
                }
                if (isset($fileSerials[$serialKey])) {
                    $errors[] = 'Asset Serial No is duplicated in this file (also on row '.$fileSerials[$serialKey].')';
                } else {
                    $fileSerials[$serialKey] = $row['row_number'];
                }
            }

            $group = null;
            $category = null;
            $type = null;
            $groupName = isset($row['assetGroup']) ? $this->cell_string($row['assetGroup']) : '';
            $categoryName = isset($row['assetCategory']) ? $this->cell_string($row['assetCategory']) : '';
            $typeName = isset($row['assetType']) ? $this->cell_string($row['assetType']) : '';

            if ($groupName !== '') {
                $groupKey = $this->norm($groupName);
                if (!isset($lookups['groups'][$groupKey])) {
                    $errors[] = 'Asset Group not found';
                } else if ((string)$lookups['groups'][$groupKey]['asset_group_status'] !== '1') {
                    $errors[] = 'Asset Group is not active';
                } else {
                    $group = $lookups['groups'][$groupKey];
                }
            }
            if ($categoryName !== '' && $group !== null) {
                $categoryKey = $this->norm($categoryName);
                if (!isset($lookups['categories'][$group['asset_group_id']][$categoryKey])) {
                    $errors[] = 'Asset Category not found under the specified Asset Group';
                } else if ((string)$lookups['categories'][$group['asset_group_id']][$categoryKey]['asset_category_status'] !== '1') {
                    $errors[] = 'Asset Category is not active';
                } else {
                    $category = $lookups['categories'][$group['asset_group_id']][$categoryKey];
                }
            } else if ($categoryName !== '' && $group === null && $groupName !== '') {
                $errors[] = 'Asset Category cannot be resolved because Asset Group is invalid';
            }
            if ($typeName !== '' && $category !== null) {
                $typeKey = $this->norm($typeName);
                if (!isset($lookups['types'][$category['asset_category_id']][$typeKey])) {
                    $errors[] = 'Asset Type not found under the specified Asset Category';
                } else if ((string)$lookups['types'][$category['asset_category_id']][$typeKey]['asset_type_status'] !== '1') {
                    $errors[] = 'Asset Type is not active';
                } else {
                    $type = $lookups['types'][$category['asset_category_id']][$typeKey];
                }
            } else if ($typeName !== '' && $category === null && $categoryName !== '') {
                $errors[] = 'Asset Type cannot be resolved because Asset Category is invalid';
            }

            $brandName = isset($row['assetBrand']) ? $this->cell_string($row['assetBrand']) : '';
            $modelName = isset($row['assetModel']) ? $this->cell_string($row['assetModel']) : '';
            $brand = null;
            if ($modelName !== '' && $brandName === '') {
                $errors[] = 'Brand is required when Model is provided';
            }
            if ($brandName !== '') {
                $brandKey = $this->norm($brandName);
                if (!isset($lookups['brands'][$brandKey])) {
                    $errors[] = 'Asset Brand not found';
                } else if ((string)$lookups['brands'][$brandKey]['asset_brand_status'] !== '1') {
                    $errors[] = 'Asset Brand is not active';
                } else if ($type === null) {
                    $errors[] = 'Asset Brand cannot be resolved because Asset Type is invalid';
                } else if (!isset($lookups['brandTypes'][$lookups['brands'][$brandKey]['asset_brand_id']][$type['asset_type_id']])) {
                    $errors[] = 'Asset Brand is not linked to the specified Asset Type';
                } else {
                    $brand = $lookups['brands'][$brandKey];
                }
            }
            if ($modelName !== '' && $brand !== null && $type !== null) {
                $modelKey = $this->norm($modelName);
                if (!isset($lookups['models'][$brand['asset_brand_id']][$type['asset_type_id']][$modelKey])) {
                    $errors[] = 'Asset Model not found for the specified Brand and Type';
                } else if ((string)$lookups['models'][$brand['asset_brand_id']][$type['asset_type_id']][$modelKey]['asset_model_status'] !== '1') {
                    $errors[] = 'Asset Model is not active';
                } else {
                    $params['assetModelId'] = $lookups['models'][$brand['asset_brand_id']][$type['asset_type_id']][$modelKey]['asset_model_id'];
                }
            }

            $ppmGroupName = isset($row['ppmGroup']) ? $this->cell_string($row['ppmGroup']) : '';
            if ($ppmGroupName !== '') {
                $ppmKey = $this->norm($ppmGroupName);
                if (!isset($lookups['ppmGroups'][$ppmKey])) {
                    $errors[] = 'PPM Group not found for this site';
                } else if ((string)$lookups['ppmGroups'][$ppmKey]['ppm_group_status'] !== '1') {
                    $errors[] = 'PPM Group is not active';
                } else {
                    $params['ppmGroupId'] = $lookups['ppmGroups'][$ppmKey]['ppm_group_id'];
                }
            }

            $zoneCode = isset($row['zoneCode']) ? $this->cell_string($row['zoneCode']) : '';
            if ($zoneCode !== '') {
                $zoneKey = $this->norm($zoneCode);
                if (!isset($lookups['zones'][$zoneKey])) {
                    $errors[] = 'Zone Code not found for this site';
                } else if ((string)$lookups['zones'][$zoneKey]['zone_status'] !== '1') {
                    $errors[] = 'Zone is not active';
                } else {
                    $params['zoneId'] = $lookups['zones'][$zoneKey]['zone_id'];
                }
            }

            $warrantyDateRaw = isset($row['assetWarrantyExpDate']) ? $row['assetWarrantyExpDate'] : '';
            if ($this->cell_string($warrantyDateRaw) !== '') {
                $parsedDate = $this->parse_date($warrantyDateRaw);
                if ($parsedDate === false) {
                    $errors[] = 'Warranty Exp Date is invalid';
                } else {
                    $params['assetWarrantyExpDate'] = $parsedDate;
                }
            }

            $params['assetName'] = isset($row['assetName']) ? $this->cell_string($row['assetName']) : '';
            $params['assetNo'] = $assetNo;
            $params['assetLocationCode'] = isset($row['assetLocationCode']) ? $this->cell_string($row['assetLocationCode']) : '';
            if ($group !== null) {
                $params['assetGroupId'] = $group['asset_group_id'];
            }
            if ($category !== null) {
                $params['assetCategoryId'] = $category['asset_category_id'];
            }
            if ($type !== null) {
                $params['assetTypeId'] = $type['asset_type_id'];
            }
            if ($brand !== null) {
                $params['assetBrandId'] = $brand['asset_brand_id'];
            }

            $optionalText = array(
                'assetSerialNo', 'assetDesc', 'assetCapacity', 'assetLocationDesc', 'assetBlock', 'assetLevel',
                'assetManufacturer', 'assetSupplier', 'assetAgency', 'assetDepartment', 'assetConstructionZone',
                'assetOperationZone', 'assetRoom', 'assetCompartment', 'assetAuthEmployee', 'assetCriticality',
                'assetContractor', 'assetWarranty', 'assetWarrantyNotes', 'assetTechnicianNotes'
            );
            foreach ($optionalText as $fieldKey) {
                if (!empty($row[$fieldKey])) {
                    $params[$fieldKey] = $this->cell_string($row[$fieldKey]);
                }
            }

            $preview = array(
                'row_number' => $row['row_number'],
                'valid' => empty($errors),
                'errors' => $errors,
                'assetName' => $params['assetName'],
                'assetNo' => $params['assetNo'],
                'assetGroup' => $groupName,
                'assetCategory' => $categoryName,
                'assetType' => $typeName,
                'locationCode' => $params['assetLocationCode']
            );
            $previewRows[] = $preview;
            if (empty($errors)) {
                $validRows[] = array(
                    'row_number' => $row['row_number'],
                    'params' => $params,
                    'preview' => $preview
                );
            }
        }

        $invalidCount = count($previewRows) - count($validRows);
        return array(
            'contractId' => $context['contractId'],
            'contractName' => $context['contractName'],
            'siteName' => $context['siteName'],
            'clientName' => $context['clientName'],
            'total_rows' => count($previewRows),
            'valid_rows' => count($validRows),
            'invalid_rows' => $invalidCount,
            'can_proceed' => count($validRows) > 0,
            'rows' => $previewRows,
            'valid' => $validRows
        );
    }

    private function label_for_key($key) {
        $labels = array(
            'assetName' => 'Asset Name',
            'assetNo' => 'Asset No',
            'assetSerialNo' => 'Asset Serial No',
            'assetDesc' => 'Description',
            'assetGroup' => 'Asset Group',
            'assetCategory' => 'Asset Category',
            'assetType' => 'Asset Type',
            'assetLocationCode' => 'Location Code',
            'assetLocationDesc' => 'Location Description',
            'assetCapacity' => 'Capacity',
            'assetBlock' => 'Block',
            'assetLevel' => 'Level',
            'assetManufacturer' => 'Manufacturer',
            'assetSupplier' => 'Supplier',
            'assetAgency' => 'Agency',
            'assetDepartment' => 'Department',
            'assetConstructionZone' => 'Construction Zone',
            'assetOperationZone' => 'Operation Zone',
            'assetRoom' => 'Room',
            'assetCompartment' => 'Compartment',
            'assetAuthEmployee' => 'Auth Employee',
            'assetCriticality' => 'Criticality',
            'assetContractor' => 'Contractor',
            'assetWarranty' => 'Warranty',
            'assetWarrantyNotes' => 'Warranty Notes',
            'assetTechnicianNotes' => 'Technician Notes'
        );
        return isset($labels[$key]) ? $labels[$key] : $key;
    }

    /**
     * @param array $file
     * @param string $contractId
     * @param string $userSite
     * @param bool $isAdministrator
     * @return array
     * @throws Exception
     */
    public function preview_import($file, $contractId, $userSite, $isAdministrator) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $context = $this->get_contract_context($contractId, $userSite, $isAdministrator);
            $rows = $this->parse_excel($file);
            if (empty($rows)) {
                throw new Exception('[' . __LINE__ . '] - No data rows found in the Excel file');
            }
            $result = $this->validate_rows($rows, $context);
            unset($result['valid']);
            return $result;
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $file
     * @param string $contractId
     * @param string $userId
     * @param string $userSite
     * @param bool $isAdministrator
     * @return array
     * @throws Exception
     */
    public function execute_import($file, $contractId, $userId, $userSite, $isAdministrator) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            $context = $this->get_contract_context($contractId, $userSite, $isAdministrator);
            $rows = $this->parse_excel($file);
            if (empty($rows)) {
                throw new Exception('[' . __LINE__ . '] - No data rows found in the Excel file');
            }
            $validated = $this->validate_rows($rows, $context);
            if (empty($validated['valid'])) {
                throw new Exception('[' . __LINE__ . '] - No valid rows to import');
            }

            $inserted = array();
            $skipped = array();
            foreach ($validated['rows'] as $previewRow) {
                if (empty($previewRow['valid'])) {
                    $skipped[] = array(
                        'row_number' => $previewRow['row_number'],
                        'assetNo' => $previewRow['assetNo'],
                        'assetName' => $previewRow['assetName'],
                        'errors' => $previewRow['errors']
                    );
                }
            }

            foreach ($validated['valid'] as $validRow) {
                try {
                    $assetId = $this->fn_asset->import_registered_asset($validRow['params'], $userId);
                    $inserted[] = array(
                        'row_number' => $validRow['row_number'],
                        'assetId' => $assetId,
                        'assetNo' => $validRow['params']['assetNo'],
                        'assetName' => $validRow['params']['assetName']
                    );
                } catch (Exception $ex) {
                    $message = $ex->getMessage();
                    $pos = strpos($message, '] - ');
                    $skipped[] = array(
                        'row_number' => $validRow['row_number'],
                        'assetNo' => $validRow['params']['assetNo'],
                        'assetName' => $validRow['params']['assetName'],
                        'errors' => array($pos !== false ? substr($message, $pos + 4) : $message)
                    );
                }
            }

            return array(
                'contractId' => $context['contractId'],
                'contractName' => $context['contractName'],
                'inserted' => count($inserted),
                'skipped' => count($skipped),
                'inserted_assets' => $inserted,
                'skipped_rows' => $skipped
            );
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
