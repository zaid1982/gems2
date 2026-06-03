<?php
/**
 * Excel/CSV File Parser for Work Order Import
 * Handles CSV, XLS, and XLSX files
 */

// Include composer autoloader for PhpSpreadsheet when available.
$vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Add PhpSpreadsheet imports
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class WoImportFileParser {
    
    private $supportedFormats = ['csv', 'xlsx', 'xls'];
    private $knownHeaderNames = [
        'external_wo_number', 'description', 'location', 'wo_type', 'severity', 'assigned_to_email', 'created_date', 'assigned_date', 'completed_date', 'verified_date',
        'created_by_email', 'verified_by_email', 'repair_description', 'longitude', 'latitude', 'rating', 'asset_number', 'zone_id', 'external_reference',
        'date', 'request no', 'request no.', 'work order no', 'work order no.', 'site', 'complaint type', 'complainant', 'complaint description', 'trade',
        'pic', 'pic name', 'technician', 'assigned technician', 'executor', 'fixed by', 'assigned by', 'verified by', 'asset no', 'asset no.', 'asset number', 'asset code',
        'complaint time', 'assigned time', 'executed time', 'execution time', 'completed time', 'completed date', 'verified time', 'respond duration', 'assistants'
    ];
    
    /**
     * Parse uploaded file and return data array
     * @param array $file Uploaded file array
     * @return array Parsed data
     * @throws Exception
     */
    public function parseFile($file) {
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $this->supportedFormats)) {
            throw new Exception('Unsupported file format: ' . $fileExtension);
        }
        
        switch ($fileExtension) {
            case 'csv':
                return $this->parseCsv($file['tmp_name']);
            case 'xlsx':
            case 'xls':
                return $this->parseExcel($file['tmp_name'], $fileExtension);
            default:
                throw new Exception('Invalid file format');
        }
    }
    
    /**
     * Parse CSV file
     * @param string $filePath Path to CSV file
     * @return array Parsed data
     */
    private function parseCsv($filePath) {
        $rows = [];
        
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 0, ',')) !== FALSE) {
                $rows[] = array_map(function ($value) {
                    return trim((string) $value);
                }, $row);
            }
            fclose($handle);
        } else {
            throw new Exception('Could not open CSV file');
        }
        
        $mapped = $this->mapRowsWithDetectedHeader($rows);
        return $mapped['data'];
    }
    
    /**
     * Parse Excel file (requires PhpSpreadsheet or similar library)
     * For now, this is a placeholder that suggests CSV conversion
     * @param string $filePath Path to Excel file
     * @param string $extension File extension
     * @return array Parsed data
     * @throws Exception
     */
    private function parseExcel($filePath, $extension) {
        // Check if PhpSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new Exception('Excel support requires PhpSpreadsheet library. Please install it with Composer or convert your file to CSV format.');
        }

        if ($extension === 'xlsx' && !class_exists('ZipArchive')) {
            throw new Exception('Excel .xlsx import requires the PHP Zip extension (ZipArchive). Please enable php-zip on the server or upload CSV format.');
        }
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $bestResult = ['score' => -1, 'data' => []];
            
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $rows = $this->getWorksheetRows($worksheet);
                $mapped = $this->mapRowsWithDetectedHeader($rows, false);

                if ($mapped['score'] > $bestResult['score']) {
                    $bestResult = $mapped;
                }
            }

            if ($bestResult['score'] <= 0) {
                throw new Exception('Could not detect the WO import header row. Please use the downloaded template or keep the data headers visible in the first sheet.');
            }
            
            return $bestResult['data'];
            
        } catch (Exception $e) {
            throw new Exception('Failed to parse Excel file: ' . $e->getMessage());
        }
    }

    private function getWorksheetRows($worksheet) {
        $rows = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $value = $cell->getCalculatedValue();

                if (class_exists('\\PhpOffice\\PhpSpreadsheet\\Shared\\Date') && \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell) && is_numeric($value)) {
                    $value = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
                } else if ($value instanceof DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                } else if (is_object($value)) {
                    $value = method_exists($value, '__toString') ? (string) $value : '';
                }

                $rowData[] = trim((string) $value);
            }

            $rows[] = $rowData;
        }

        return $rows;
    }

    private function mapRowsWithDetectedHeader($rows, $throwIfMissing = true) {
        $headerInfo = $this->detectHeaderRow($rows);

        if ($headerInfo === null) {
            if ($throwIfMissing) {
                throw new Exception('Could not detect the WO import header row. Please keep headers such as Date, Request No., Location, Complaint Description, Severity, and PIC Name in the file.');
            }
            return ['score' => 0, 'data' => []];
        }

        $headers = $this->sanitizeHeaders($rows[$headerInfo['index']]);
        $data = [];

        for ($rowIndex = $headerInfo['index'] + 1; $rowIndex < count($rows); $rowIndex++) {
            if ($this->isEmptyRow($rows[$rowIndex])) {
                continue;
            }

            $mappedRow = [];
            foreach ($headers as $columnIndex => $header) {
                if ($header === null) {
                    continue;
                }

                $value = isset($rows[$rowIndex][$columnIndex]) ? trim((string) $rows[$rowIndex][$columnIndex]) : '';
                if (isset($mappedRow[$header]) && $mappedRow[$header] !== '' && $value === '') {
                    continue;
                }
                $mappedRow[$header] = $value;
            }

            if (!empty($mappedRow)) {
                $data[] = $mappedRow;
            }
        }

        return ['score' => $headerInfo['score'], 'data' => $data];
    }

    private function detectHeaderRow($rows) {
        $bestIndex = null;
        $bestScore = 0;

        foreach ($rows as $rowIndex => $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $score = $this->scoreHeaderRow($row);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $rowIndex;
            }
        }

        if ($bestIndex === null || $bestScore < 2) {
            return null;
        }

        return ['index' => $bestIndex, 'score' => $bestScore];
    }

    private function scoreHeaderRow($row) {
        $knownHeaders = array_flip(array_map([$this, 'normalizeHeaderName'], $this->knownHeaderNames));
        $score = 0;

        foreach ($row as $value) {
            $normalized = $this->normalizeHeaderName($value);
            if ($normalized !== '' && isset($knownHeaders[$normalized])) {
                $score++;
            }
        }

        return $score;
    }

    private function sanitizeHeaders($row) {
        $headers = [];
        $seen = [];

        foreach ($row as $columnIndex => $header) {
            $header = trim((string) $header);
            if ($header === '') {
                $headers[$columnIndex] = null;
                continue;
            }

            if (!isset($seen[$header])) {
                $seen[$header] = 0;
                $headers[$columnIndex] = $header;
                continue;
            }

            $seen[$header]++;
            $headers[$columnIndex] = $header . ' ' . $seen[$header];
        }

        return $headers;
    }

    private function isEmptyRow($row) {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function normalizeHeaderName($value) {
        return preg_replace('/[^a-z0-9_]+/', '', strtolower(trim((string) $value)));
    }
    
    /**
     * Validate file before parsing
     * @param array $file Uploaded file array
     * @return array Validation result
     */
    public function validateFile($file) {
        $errors = [];
        
        // Check file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file was uploaded';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size (10MB limit)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size too large. Maximum size is 10MB';
        }
        
        // Check file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $this->supportedFormats)) {
            $errors[] = 'Invalid file format. Supported formats: ' . implode(', ', $this->supportedFormats);
        }
        
        // Check MIME type for security
        $allowedMimes = [
            'csv' => ['text/csv', 'text/plain', 'application/csv'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'xls' => ['application/vnd.ms-excel']
        ];
        
        $fileMime = mime_content_type($file['tmp_name']);
        if (isset($allowedMimes[$fileExtension]) && !in_array($fileMime, $allowedMimes[$fileExtension])) {
            // Some servers may not detect MIME correctly, so this is a warning rather than error
            // $errors[] = 'File MIME type does not match extension';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_info' => [
                'name' => $file['name'],
                'size' => $file['size'],
                'type' => $fileExtension,
                'mime' => $fileMime
            ]
        ];
    }
    
    /**
     * Generate CSV template
     * @return string CSV content
     */
    public function generateCsvTemplate() {
        $headers = [
            'external_wo_number',
            'description',
            'location', 
            'wo_type',
            'severity',
            'assigned_to_email',
            'created_date',
            'assigned_date',
            'completed_date',
            'verified_date',
            'created_by_email',
            'verified_by_email', 
            'repair_description',
            'longitude',
            'latitude',
            'rating',
            'asset_number',
            'zone_id',
            'external_reference'
        ];
        
        $sampleRow = [
            'EXT-WO-2024-001',
            'Aircon not working in office area',
            'Level 2, Office Block A',
            '4',
            '2',
            'technician@company.com',
            '2024-01-15',
            '2024-01-15',
            '2024-01-16',
            '2024-01-16',
            'supervisor@company.com',
            'manager@company.com',
            'Replaced faulty compressor unit',
            '103.8198',
            '1.3521',
            '5',
            'AC-001',
            '1',
            'EXT-REF-001'
        ];
        
        return implode(',', $headers) . "\n" . implode(',', $sampleRow);
    }
    
    /**
     * Get supported file formats
     * @return array Supported formats
     */
    public function getSupportedFormats() {
        return $this->supportedFormats;
    }
}
?>
