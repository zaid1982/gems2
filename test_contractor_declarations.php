<?php
require_once 'api/class/Constant.php';

// Test script to check contractor declarations in database
try {
    $pdo = new PDO(
        "mysql:host=" . Constant::$dbHost . ";port=3306;dbname=" . Constant::$dbName . ";charset=utf8",
        Constant::$dbUserName,
        Constant::$dbUserPassword,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    echo "<h2>Contractor Declarations Test</h2>";
    
    // Get the latest PTW records with declaration data
    $sql = "SELECT 
                ptw_permit_id, 
                ptw_permit_number,
                ptw_applicant_name,
                ptw_declaration_checklist,
                created_date
            FROM ptw_permit 
            WHERE ptw_declaration_checklist IS NOT NULL 
                AND ptw_declaration_checklist != '[]'
                AND ptw_declaration_checklist != '{}'
            ORDER BY created_date DESC 
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "<p style='color: orange;'>No PTW records found with contractor declaration data.</p>";
        
        // Check total PTW records
        $totalSql = "SELECT COUNT(*) as total FROM ptw_permit";
        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute();
        $total = $totalStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total PTW records in database: " . $total['total'] . "</p>";
        
        // Show latest 5 records regardless of declaration data
        echo "<h3>Latest 5 PTW Records (for reference):</h3>";
        $latestSql = "SELECT ptw_permit_id, ptw_permit_number, ptw_applicant_name, ptw_declaration_checklist, created_date 
                     FROM ptw_permit ORDER BY created_date DESC LIMIT 5";
        $latestStmt = $pdo->prepare($latestSql);
        $latestStmt->execute();
        $latestRecords = $latestStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>PTW ID</th><th>Permit Number</th><th>Applicant</th><th>Declaration Data</th><th>Created Date</th></tr>";
        foreach ($latestRecords as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['ptw_permit_id']) . "</td>";
            echo "<td>" . htmlspecialchars($record['ptw_permit_number']) . "</td>";
            echo "<td>" . htmlspecialchars($record['ptw_applicant_name']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($record['ptw_declaration_checklist'], 0, 100)) . "...</td>";
            echo "<td>" . htmlspecialchars($record['created_date']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: green;'>Found " . count($records) . " PTW records with contractor declaration data:</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>PTW ID</th><th>Permit Number</th><th>Applicant</th><th>Declaration Data</th><th>Created Date</th></tr>";
        
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['ptw_permit_id']) . "</td>";
            echo "<td>" . htmlspecialchars($record['ptw_permit_number']) . "</td>";
            echo "<td>" . htmlspecialchars($record['ptw_applicant_name']) . "</td>";
            echo "<td style='max-width: 300px; word-wrap: break-word;'>";
            
            // Try to decode and format the JSON data
            $declarationData = json_decode($record['ptw_declaration_checklist'], true);
            if ($declarationData) {
                echo "<pre style='font-size: 10px; margin: 0;'>";
                echo htmlspecialchars(json_encode($declarationData, JSON_PRETTY_PRINT));
                echo "</pre>";
            } else {
                echo htmlspecialchars($record['ptw_declaration_checklist']);
            }
            echo "</td>";
            echo "<td>" . htmlspecialchars($record['created_date']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h3>Instructions for Testing:</h3>";
    echo "<ol>";
    echo "<li>Go to the PTW form: <a href='ptw_form.html' target='_blank'>ptw_form.html</a></li>";
    echo "<li>Fill out the form and make sure to select Yes/No for all contractor declarations</li>";
    echo "<li>Submit the form</li>";
    echo "<li>Refresh this page to see if the declaration data was stored</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
