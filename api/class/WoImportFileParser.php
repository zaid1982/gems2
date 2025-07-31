<?php
/**
 * Excel/CSV File Parser for Work Order Import
 * Handles CSV, XLS, and XLSX files
 */

// Add PhpSpreadsheet imports
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class WoImportFileParser {
    
    private $supportedFormats = ['csv', 'xlsx', 'xls'];
    
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
        $data = [];
        $headers = null;
        
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 0, ',')) !== FALSE) {
                if ($headers === null) {
                    // First row is headers
                    $headers = array_map('trim', $row);
                } else {
                    // Map data to headers
                    $rowData = [];
                    for ($i = 0; $i < count($headers); $i++) {
                        $rowData[$headers[$i]] = isset($row[$i]) ? trim($row[$i]) : '';
                    }
                    $data[] = $rowData;
                }
            }
            fclose($handle);
        } else {
            throw new Exception('Could not open CSV file');
        }
        
        return $data;
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
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = [];
            $headers = null;
            
            foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getCalculatedValue();
                }
                
                if ($headers === null) {
                    // First row is headers
                    $headers = array_map('trim', $rowData);
                } else {
                    // Map data to headers
                    $mappedRow = [];
                    for ($i = 0; $i < count($headers); $i++) {
                        $mappedRow[$headers[$i]] = isset($rowData[$i]) ? trim($rowData[$i]) : '';
                    }
                    $data[] = $mappedRow;
                }
            }
            
            return $data;
            
        } catch (Exception $e) {
            throw new Exception('Failed to parse Excel file: ' . $e->getMessage());
        }
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
