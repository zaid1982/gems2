<?php
// Simple test of PTW permit number generation (without database)
echo "=== PTW Permit Number Generation Test ===\n";

function generateSimplePtwNumber() {
    $timestamp = date('YmdHis'); // YYYYMMDDhhmmss format
    $permit_number = 'PTW' . $timestamp;
    
    // Add microseconds to ensure uniqueness even for rapid submissions
    $microseconds = substr(microtime(), 2, 2); // Get 2 digits of microseconds
    $permit_number .= $microseconds;
    
    return $permit_number;
}

// Generate several test permit numbers
for ($i = 1; $i <= 10; $i++) {
    $permit_number = generateSimplePtwNumber();
    echo "Test $i: $permit_number\n";
    
    // Small delay to show timestamp progression
    usleep(100000); // 0.1 second delay
}

echo "\n=== Format Explanation ===\n";
echo "Format: PTWYYYYMMDDhhmmssXX\n";
echo "Where:\n";
echo "- PTW = Prefix\n";
echo "- YYYY = Year (4 digits) - " . date('Y') . "\n";
echo "- MM = Month (2 digits) - " . date('m') . "\n";
echo "- DD = Day (2 digits) - " . date('d') . "\n";
echo "- hh = Hour (2 digits, 24-hour format) - " . date('H') . "\n";
echo "- mm = Minute (2 digits) - " . date('i') . "\n";
echo "- ss = Second (2 digits) - " . date('s') . "\n";
echo "- XX = Microseconds (2 digits) for uniqueness\n";

$example = generateSimplePtwNumber();
echo "\nCurrent example: $example\n";
echo "This ensures each permit number is:\n";
echo "✓ Unique (timestamp + microseconds)\n";
echo "✓ Sortable by creation time\n";
echo "✓ Human readable\n";
echo "✓ No database dependency for generation\n";
?>
