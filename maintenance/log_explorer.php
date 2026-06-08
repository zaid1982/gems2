<?php
require_once __DIR__ . '/_require_auth.php';

// Log Explorer Backend API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Read config.ini to get log directory
function getLogDirectory() {
    $configFile = '../api/library/config.ini';
    if (file_exists($configFile)) {
        $config = parse_ini_file($configFile, true);
        $logDir = $config['database']['log_dir'] ?? '/Applications/XAMPP/logs/gems';
        
        // If it's a relative path starting with ./
        if (strpos($logDir, './') === 0) {
            $logDir = __DIR__ . '/' . substr($logDir, 2);
        }
        // Handle other relative paths by resolving them from the current script location
        elseif (!file_exists($logDir) && !preg_match('/^[A-Za-z]:/', $logDir) && strpos($logDir, '/') !== 0) {
            // Try resolving relative path from current directory
            $resolvedPath = realpath(__DIR__ . '/' . $logDir);
            if ($resolvedPath && file_exists($resolvedPath)) {
                return $resolvedPath;
            }
            
            // Try resolving from project root
            $projectRoot = dirname(__DIR__);
            $resolvedPath = realpath($projectRoot . '/' . $logDir);
            if ($resolvedPath && file_exists($resolvedPath)) {
                return $resolvedPath;
            }
        }
        
        return $logDir;
    }
    return '/Applications/XAMPP/logs/gems';
}

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list_directories':
            listDirectories();
            break;
        
        case 'list_files':
            listFiles();
            break;
        
        case 'download_file':
            downloadFile();
            break;
        
        case 'view_file':
            viewFile();
            break;
        
        case 'tail_file':
            tailFile();
            break;
        
        case 'search_logs':
            searchLogs();
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function listDirectories() {
    $logDir = getLogDirectory();
    
    if (!is_dir($logDir)) {
        throw new Exception("Log directory not found: $logDir");
    }
    
    $directories = [];
    $items = scandir($logDir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.DS_Store') continue;
        
        $fullPath = $logDir . '/' . $item;
        if (is_dir($fullPath)) {
            $fileCount = count(glob($fullPath . '/*')) - count(glob($fullPath . '/.*'));
            $directories[] = [
                'name' => $item,
                'path' => $fullPath,
                'file_count' => $fileCount,
                'modified' => filemtime($fullPath),
                'modified_formatted' => date('Y-m-d H:i:s', filemtime($fullPath))
            ];
        }
    }
    
    // Sort by modified time, newest first
    usort($directories, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    echo json_encode([
        'success' => true,
        'directories' => $directories,
        'log_dir' => $logDir
    ]);
}

function listFiles() {
    $directory = $_GET['directory'] ?? '';
    $logDir = getLogDirectory();
    
    if (empty($directory)) {
        throw new Exception('Directory parameter required');
    }
    
    $fullPath = $logDir . '/' . $directory;
    
    if (!is_dir($fullPath)) {
        throw new Exception("Directory not found: $fullPath");
    }
    
    $files = [];
    $items = scandir($fullPath);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.DS_Store') continue;
        
        $filePath = $fullPath . '/' . $item;
        if (is_file($filePath)) {
            $files[] = [
                'name' => $item,
                'path' => $filePath,
                'size' => filesize($filePath),
                'size_formatted' => formatBytes(filesize($filePath)),
                'modified' => filemtime($filePath),
                'modified_formatted' => date('Y-m-d H:i:s', filemtime($filePath)),
                'extension' => pathinfo($item, PATHINFO_EXTENSION)
            ];
        }
    }
    
    // Sort by modified time, newest first
    usort($files, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    echo json_encode([
        'success' => true,
        'files' => $files,
        'directory' => $directory,
        'full_path' => $fullPath
    ]);
}

function downloadFile() {
    $directory = $_GET['directory'] ?? '';
    $filename = $_GET['filename'] ?? '';
    
    if (empty($directory) || empty($filename)) {
        throw new Exception('Directory and filename parameters required');
    }
    
    $logDir = getLogDirectory();
    $filePath = $logDir . '/' . $directory . '/' . $filename;
    
    if (!file_exists($filePath) || !is_file($filePath)) {
        throw new Exception('File not found');
    }
    
    // Security check - ensure file is within log directory
    $realLogDir = realpath($logDir);
    $realFilePath = realpath($filePath);
    
    if (strpos($realFilePath, $realLogDir) !== 0) {
        throw new Exception('Access denied');
    }
    
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    
    // Output file
    readfile($filePath);
    exit;
}

function viewFile() {
    $directory = $_GET['directory'] ?? '';
    $filename = $_GET['filename'] ?? '';
    $lines = (int)($_GET['lines'] ?? 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    if (empty($directory) || empty($filename)) {
        throw new Exception('Directory and filename parameters required');
    }
    
    $logDir = getLogDirectory();
    $filePath = $logDir . '/' . $directory . '/' . $filename;
    
    if (!file_exists($filePath) || !is_file($filePath)) {
        throw new Exception('File not found');
    }
    
    // Security check
    $realLogDir = realpath($logDir);
    $realFilePath = realpath($filePath);
    
    if (strpos($realFilePath, $realLogDir) !== 0) {
        throw new Exception('Access denied');
    }
    
    $fileContent = file($filePath, FILE_IGNORE_NEW_LINES);
    $totalLines = count($fileContent);
    
    $startLine = max(0, $offset);
    $endLine = min($totalLines, $startLine + $lines);
    
    $content = array_slice($fileContent, $startLine, $lines);
    
    echo json_encode([
        'success' => true,
        'content' => $content,
        'total_lines' => $totalLines,
        'start_line' => $startLine,
        'end_line' => $endLine,
        'file_info' => [
            'name' => $filename,
            'size' => filesize($filePath),
            'size_formatted' => formatBytes(filesize($filePath)),
            'modified' => filemtime($filePath),
            'modified_formatted' => date('Y-m-d H:i:s', filemtime($filePath))
        ]
    ]);
}

function tailFile() {
    $directory = $_GET['directory'] ?? '';
    $filename = $_GET['filename'] ?? '';
    $lines = (int)($_GET['lines'] ?? 50);
    
    if (empty($directory) || empty($filename)) {
        throw new Exception('Directory and filename parameters required');
    }
    
    $logDir = getLogDirectory();
    $filePath = $logDir . '/' . $directory . '/' . $filename;
    
    if (!file_exists($filePath) || !is_file($filePath)) {
        throw new Exception('File not found');
    }
    
    // Security check
    $realLogDir = realpath($logDir);
    $realFilePath = realpath($filePath);
    
    if (strpos($realFilePath, $realLogDir) !== 0) {
        throw new Exception('Access denied');
    }
    
    // Get last N lines using tail command
    $command = "tail -n $lines " . escapeshellarg($filePath);
    $output = shell_exec($command);
    
    $content = $output ? explode("\n", trim($output)) : [];
    
    echo json_encode([
        'success' => true,
        'content' => $content,
        'lines_requested' => $lines,
        'file_info' => [
            'name' => $filename,
            'size' => filesize($filePath),
            'size_formatted' => formatBytes(filesize($filePath)),
            'modified' => filemtime($filePath),
            'modified_formatted' => date('Y-m-d H:i:s', filemtime($filePath))
        ]
    ]);
}

function searchLogs() {
    $directory = $_GET['directory'] ?? '';
    $filename = $_GET['filename'] ?? '';
    $search = $_GET['search'] ?? '';
    $case_sensitive = ($_GET['case_sensitive'] ?? 'false') === 'true';
    $max_results = (int)($_GET['max_results'] ?? 100);
    
    if (empty($directory) || empty($filename) || empty($search)) {
        throw new Exception('Directory, filename, and search parameters required');
    }
    
    $logDir = getLogDirectory();
    $filePath = $logDir . '/' . $directory . '/' . $filename;
    
    if (!file_exists($filePath) || !is_file($filePath)) {
        throw new Exception('File not found');
    }
    
    // Security check
    $realLogDir = realpath($logDir);
    $realFilePath = realpath($filePath);
    
    if (strpos($realFilePath, $realLogDir) !== 0) {
        throw new Exception('Access denied');
    }
    
    $fileContent = file($filePath, FILE_IGNORE_NEW_LINES);
    $results = [];
    $lineNumber = 1;
    
    foreach ($fileContent as $line) {
        $searchIn = $case_sensitive ? $line : strtolower($line);
        $searchFor = $case_sensitive ? $search : strtolower($search);
        
        if (strpos($searchIn, $searchFor) !== false) {
            $results[] = [
                'line_number' => $lineNumber,
                'content' => $line,
                'highlighted' => highlightSearch($line, $search, $case_sensitive)
            ];
            
            if (count($results) >= $max_results) {
                break;
            }
        }
        $lineNumber++;
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total_found' => count($results),
        'search_term' => $search,
        'case_sensitive' => $case_sensitive,
        'max_results' => $max_results
    ]);
}

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

function highlightSearch($text, $search, $caseSensitive = false) {
    $flags = $caseSensitive ? '' : 'i';
    return preg_replace('/(' . preg_quote($search, '/') . ')/' . $flags, '<mark>$1</mark>', $text);
}
?>
