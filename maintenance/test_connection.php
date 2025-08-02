<?php
// Simple connection test for the advanced database tool
require_once('../api/class/Constant.php');

header('Content-Type: application/json');

try {
    $dsn = "mysql:host=" . Constant::$dbHost . ";dbname=" . Constant::$dbName . ";charset=utf8mb4";
    $pdo = new PDO($dsn, Constant::$dbUserName, Constant::$dbUserPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Test query
    $stmt = $pdo->prepare("SELECT COUNT(*) as table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?");
    $stmt->execute([Constant::$dbName]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Connection successful',
        'host' => Constant::$dbHost,
        'database' => Constant::$dbName,
        'table_count' => $result['table_count']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'host' => Constant::$dbHost ?? 'Unknown',
        'database' => Constant::$dbName ?? 'Unknown'
    ]);
}
?>
