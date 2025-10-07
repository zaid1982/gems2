<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Zone extends General {

    public $zoneId = 0;
    public $zoneName = '';

    private static $tableName = 'cli_zone';
    private static $idName = 'zoneId';

    function __construct(int $userId = 0, bool $isLogged = false)
    {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRef(): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            return DbMysql::selectAll($this::$tableName, array(), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getList(): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            return DbMysql::selectAll($this::$tableName);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getList2(): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $returnArr = array();
            $zoneList = DbMysql::selectAll($this::$tableName, array('siteId'=>$this->userSite, 'zoneStatus'=>1), 0, false, 'zoneType');
            foreach ($zoneList as $zone) {
                $type = $zone['zoneType'];
                $returnArr[$type][] = $zone;
            }
            return $returnArr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $zoneId
     * @return array
     * @throws Exception
     */
    public function get(int $zoneId=0): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->zoneId = !empty($zoneId) ? $zoneId : $this->zoneId;
            return DbMysql::select($this::$tableName, array($this::$idName=>$this->zoneId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $zoneId
     * @throws Exception
     */
    public function set (int $zoneId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($zoneId, $this::$idName);
            $this->zoneId = $zoneId;
            $this->zoneName = DbMysql::selectColumn($this::$tableName, array($this::$idName=>$zoneId),'zoneName', true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function insert (array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkMandatoryArray($columns, array('siteId', 'zoneCode', 'zoneName', 'zoneStatus'), true);
            if (DbMysql::count($this::$tableName, parent::arraySpliceAssoc($columns, array('siteId', 'zoneCode'))) > 0) {
                throw new Exception(str_replace('__', $columns['zoneCode'], Constant::$zone['errAlreadyExist']), 31);
            }
            $this->set(DbMysql::insert($this::$tableName, $columns));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $zoneId
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function update (int $zoneId, array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($zoneId, $this::$idName);
            parent::checkMandatoryArray($columns, array('siteId', 'zoneCode', 'zoneName', 'zoneStatus'), true);
            if (DbMysql::count($this::$tableName, array_merge(parent::arraySpliceAssoc($columns, array('siteId', 'zoneCode')), array($this::$idName=>'<>|'.$zoneId))) > 0) {
                throw new Exception(str_replace('__', $columns['zoneCode'], Constant::$zone['errAlreadyExist']), 31);
            }
            DbMysql::update($this::$tableName, $columns, array($this::$idName=>$zoneId));
            $this->set($zoneId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Output Excel template for bulk zone creation.
     *
     * @return void
     * @throws Exception
     */
    public function downloadTemplate(): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                throw new Exception('Excel template generation requires PhpSpreadsheet library. Please install dependencies.');
            }

            $sites = DbMysql::selectAll('cli_site', array('siteStatus' => 1), 0, false, 'siteName');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Zones');

            $headers = array('Site (ID - Name)', 'Zone Type', 'Zone Code', 'Zone Name', 'Zone Status');
            foreach ($headers as $idx => $header) {
                $column = Coordinate::stringFromColumnIndex($idx + 1);
                $sheet->setCellValue($column . '1', $header);
            }

            $sheet->getStyle('A1:E1')->applyFromArray(array(
                'font' => array('bold' => true, 'color' => array('rgb' => 'FFFFFF')),
                'fill' => array('fillType' => Fill::FILL_SOLID, 'startColor' => array('rgb' => '007bff')),
                'alignment' => array('horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER),
                'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => '000000')))
            ));

            $sheet->getRowDimension(1)->setRowHeight(22);
            $sheet->freezePane('A2');

            $sheet->getColumnDimension('A')->setWidth(38);
            $sheet->getColumnDimension('B')->setWidth(22);
            $sheet->getColumnDimension('C')->setWidth(18);
            $sheet->getColumnDimension('D')->setWidth(28);
            $sheet->getColumnDimension('E')->setWidth(16);

            if (!empty($sites)) {
                $sampleSiteId = $sites[0]['siteId'] ?? ($sites[0]['site_id'] ?? '');
                $sampleSiteName = $sites[0]['siteName'] ?? ($sites[0]['site_name'] ?? '');
                $sheet->setCellValue('A2', trim($sampleSiteId . ' - ' . $sampleSiteName));
            } else {
                $sheet->setCellValue('A2', 'Select site from dropdown');
            }
            $sheet->setCellValue('B2', 'Office');
            $sheet->setCellValue('C2', 'ZONE-001');
            $sheet->setCellValue('D2', 'Main Lobby');
            $sheet->setCellValue('E2', 'Active');

            $lookupSheet = $spreadsheet->createSheet();
            $lookupSheet->setTitle('Lookups');
            $lookupSheet->setCellValue('A1', 'Site (ID - Name)');
            $lookupSheet->setCellValue('B1', 'Site Name');
            $lookupSheet->setCellValue('C1', 'Site ID');

            $lookupRow = 2;
            foreach ($sites as $site) {
                $siteId = $site['siteId'] ?? ($site['site_id'] ?? '');
                $siteName = $site['siteName'] ?? ($site['site_name'] ?? '');
                $display = trim($siteId . ' - ' . $siteName);
                $lookupSheet->setCellValue('A' . $lookupRow, $display);
                $lookupSheet->setCellValue('B' . $lookupRow, $siteName);
                $lookupSheet->setCellValueExplicit('C' . $lookupRow, $siteId, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $lookupRow++;
            }
            $lastSiteRow = max($lookupRow - 1, 2);

            $lookupSheet->setCellValue('E1', 'Zone Status');
            $lookupSheet->setCellValue('F1', 'Code');
            $lookupSheet->setCellValue('E2', 'Active');
            $lookupSheet->setCellValue('F2', 1);
            $lookupSheet->setCellValue('E3', 'Disabled');
            $lookupSheet->setCellValue('F3', 2);

            $lookupSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

            $siteFormula = "'Lookups'!\$A\$2:\$A\$" . $lastSiteRow;
            $statusFormula = "'Lookups'!\$E\$2:\$E\$3";

            for ($row = 2; $row <= 501; $row++) {
                $siteValidation = $sheet->getCell('A' . $row)->getDataValidation();
                $siteValidation->setType(DataValidation::TYPE_LIST);
                $siteValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $siteValidation->setAllowBlank(false);
                $siteValidation->setShowDropDown(true);
                $siteValidation->setFormula1($siteFormula);
                $siteValidation->setErrorTitle('Invalid Site');
                $siteValidation->setError('Please select a site from the dropdown list.');
                $siteValidation->setPromptTitle('Site Selection');
                $siteValidation->setPrompt('Pick a site (format: ID - Name).');

                $statusValidation = $sheet->getCell('E' . $row)->getDataValidation();
                $statusValidation->setType(DataValidation::TYPE_LIST);
                $statusValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $statusValidation->setAllowBlank(false);
                $statusValidation->setShowDropDown(true);
                $statusValidation->setFormula1($statusFormula);
                $statusValidation->setErrorTitle('Invalid Status');
                $statusValidation->setError('Valid options: Active or Disabled.');
                $statusValidation->setPromptTitle('Zone Status');
                $statusValidation->setPrompt('Choose the status (Active or Disabled).');
            }

            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions');
            $instructionSheet->setCellValue('A1', 'Zone Template Usage');
            $instructionSheet->setCellValue('A3', '• Fill one row per zone.');
            $instructionSheet->setCellValue('A4', '• Site column uses dropdown (format: ID - Name). Do not type arbitrary values.');
            $instructionSheet->setCellValue('A5', '• Zone Status column accepts only "Active" or "Disabled".');
            $instructionSheet->setCellValue('A6', '• Zone Code must be unique per site.');
            $instructionSheet->setCellValue('A7', '• Remove sample row before upload.');
            $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $instructionSheet->getColumnDimension('A')->setWidth(120);

            $spreadsheet->setActiveSheetIndex(0);

            DbMysql::close();

            // Clean any output buffers to prevent corruption
            if (ob_get_length()) {
                ob_end_clean();
            }

            $writer = new Xlsx($spreadsheet);
            $fileName = 'zone_template_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            $writer->save('php://output');
            exit;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Import zones from Excel template
     * @param array $fileInfo File upload info from $_FILES
     * @return array Statistics about the import
     * @throws Exception
     */
    public function importFromExcel(array $fileInfo): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (!isset($fileInfo['tmp_name']) || !file_exists($fileInfo['tmp_name'])) {
                throw new Exception('No file uploaded', 31);
            }

            // Load the spreadsheet
            $spreadsheet = IOFactory::load($fileInfo['tmp_name']);
            $sheet = $spreadsheet->getSheet(0); // Main sheet
            $highestRow = $sheet->getHighestRow();

            $stats = [
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'skipped' => 0,
                'errors' => []
            ];

            // Start from row 3 (skip header and sample)
            for ($row = 3; $row <= $highestRow; $row++) {
                $siteCell = trim($sheet->getCell('A' . $row)->getValue() ?? '');
                $zoneType = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                $zoneCode = trim($sheet->getCell('C' . $row)->getValue() ?? '');
                $zoneName = trim($sheet->getCell('D' . $row)->getValue() ?? '');
                $zoneStatus = trim($sheet->getCell('E' . $row)->getValue() ?? '');

                // Skip empty rows
                if (empty($siteCell) && empty($zoneType) && empty($zoneCode) && empty($zoneName)) {
                    $stats['skipped']++;
                    continue;
                }

                $stats['total']++;

                try {
                    // Parse site ID from "ID - Name" format
                    if (!preg_match('/^(\d+)\s*-/', $siteCell, $matches)) {
                        throw new Exception("Invalid site format in row $row");
                    }
                    $siteId = intval($matches[1]);

                    // Map status text to value
                    $statusValue = ($zoneStatus === 'Active') ? 1 : 2;

                    // Validate required fields
                    if (empty($zoneType)) {
                        throw new Exception("Zone type is required in row $row");
                    }
                    if (empty($zoneCode)) {
                        throw new Exception("Zone code is required in row $row");
                    }
                    if (empty($zoneName)) {
                        throw new Exception("Zone name is required in row $row");
                    }

                    // Prepare insert data
                    $params = [
                        'siteId' => $siteId,
                        'zoneType' => $zoneType,
                        'zoneCode' => $zoneCode,
                        'zoneName' => $zoneName,
                        'zoneStatus' => $statusValue
                    ];

                    // Insert using the existing insert method
                    $this->insert($params);
                    $stats['success']++;

                } catch (Exception $e) {
                    $stats['failed']++;
                    $stats['errors'][] = "Row $row: " . $e->getMessage();
                    parent::logError(__CLASS__, __FUNCTION__, __LINE__, "Import error row $row: " . $e->getMessage());
                }
            }

            return $stats;

        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function delete (): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->zoneId, $this::$idName);
            if (DbMysql::count($this::$tableName, array($this::$idName=>$this->zoneId)) > 0) {
                throw new Exception(str_replace('__', $this->zoneName, Constant::$zone['errStillExist']), 31);
            }
            DbMysql::delete($this::$tableName, array($this::$idName=>$this->zoneId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}