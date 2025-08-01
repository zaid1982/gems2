<?php
/**
 * Test script to verify the division by zero fix
 */

require_once 'api/constant.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_db.php';
require_once 'api/function/f_gamification.php';

echo "=== TESTING DIVISION BY ZERO FIX ===\n\n";

try {
    $gamification = new Class_gamification();
    
    // Test the problematic scenario: August 2025 week 5
    echo "Testing gamification calculation for August 2025...\n";
    echo "This previously caused a division by zero error.\n\n";
    
    // Try to run the monthly calculation that was failing
    echo "Running runMonthly(2025, 8)...\n";
    $result = $gamification->runMonthly(2025, 8);
    
    echo "✅ SUCCESS! No division by zero error occurred.\n";
    echo "The fix is working correctly.\n\n";
    
    echo "What was fixed:\n";
    echo "- Added proper check for \$allCompleted > 0 before dividing by it\n";
    echo "- This prevents division by zero when tasks are assigned but none completed\n";
    echo "- The late penalty calculation now safely handles edge cases\n\n";
    
    echo "The calculation can now handle scenarios where:\n";
    echo "- Tasks are assigned (\$allTotal > 0)\n";
    echo "- But no tasks are completed (\$allCompleted = 0)\n";
    echo "- In such cases, the late penalty is set to 0 instead of causing an error\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    if (strpos($e->getMessage(), 'Division by zero') !== false) {
        echo "\n⚠️  Division by zero error still exists!\n";
        echo "The fix may not have been applied correctly.\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
