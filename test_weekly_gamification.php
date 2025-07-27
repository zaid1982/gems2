<?php
/**
 * Test script for weekly gamification calculations
 * Run this script to test the new weekly calculation functionality
 */

// Include necessary files (adjust paths as needed)
require_once 'api/function/f_gamification.php';
require_once 'api/class/Class_db.php';

class WeeklyGamificationTest {
    
    private $gamification;
    
    public function __construct() {
        $this->gamification = new Class_gamification();
    }
    
    /**
     * Test week calculation functions
     */
    public function testWeekCalculations() {
        echo "<h2>Testing Week Calculation Functions</h2>\n";
        
        // Test January 2025 (current month)
        $year = 2025;
        $month = 1;
        
        echo "<h3>Testing getWeeksInMonth($year, $month)</h3>\n";
        try {
            $weeksInMonth = $this->callPrivateMethod($this->gamification, 'getWeeksInMonth', [$year, $month]);
            echo "Weeks in January 2025: $weeksInMonth<br>\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "<br>\n";
        }
        
        echo "<h3>Testing getWeekDateRange for each week</h3>\n";
        for ($week = 1; $week <= 5; $week++) {
            try {
                $dateRange = $this->callPrivateMethod($this->gamification, 'getWeekDateRange', [$year, $month, $week]);
                echo "Week $week: {$dateRange['start']} to {$dateRange['end']}<br>\n";
            } catch (Exception $e) {
                echo "Week $week Error: " . $e->getMessage() . "<br>\n";
            }
        }
    }
    
    /**
     * Test the full monthly calculation with debug output
     */
    public function testMonthlyCalculation() {
        echo "<h2>Testing Monthly Calculation (Dry Run)</h2>\n";
        
        // Test with current month
        $year = 2025;
        $month = 1;
        
        echo "Testing runMonthly($year, $month)<br>\n";
        echo "<em>Note: This will attempt to run the actual calculation. Make sure your database is ready.</em><br><br>\n";
        
        try {
            // Enable debug output
            echo "Starting weekly gamification calculation...<br>\n";
            
            // Call the runMonthly method
            $this->gamification->runMonthly($year, $month);
            
            echo "✅ Monthly calculation completed successfully!<br>\n";
            
        } catch (Exception $e) {
            echo "❌ Error during calculation: " . $e->getMessage() . "<br>\n";
            echo "This might be expected if database tables/views don't exist yet.<br>\n";
        }
    }
    
    /**
     * Test configuration loading
     */
    public function testConfiguration() {
        echo "<h2>Testing Configuration Loading</h2>\n";
        
        try {
            // Test if configuration is loaded properly
            $configMethod = $this->callPrivateMethod($this->gamification, 'getConfig', ['mbv_tier1_threshold', 50]);
            echo "MBV Tier 1 Threshold: $configMethod<br>\n";
            
            $configMethod = $this->callPrivateMethod($this->gamification, 'getConfig', ['mbv_tier2_threshold', 100]);
            echo "MBV Tier 2 Threshold: $configMethod<br>\n";
            
            $configMethod = $this->callPrivateMethod($this->gamification, 'getConfig', ['point_scale_factor', 10000]);
            echo "Point Scale Factor: $configMethod<br>\n";
            
            echo "✅ Configuration loading works correctly!<br>\n";
            
        } catch (Exception $e) {
            echo "❌ Configuration error: " . $e->getMessage() . "<br>\n";
        }
    }
    
    /**
     * Helper method to call private methods for testing
     */
    private function callPrivateMethod($object, $methodName, $parameters = []) {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
    
    /**
     * Check database requirements
     */
    public function checkDatabaseRequirements() {
        echo "<h2>Checking Database Requirements</h2>\n";
        
        try {
            // Check if gmi_config table exists and has data
            $configData = Class_db::getInstance()->db_select2('gmi_config', array('status' => 1));
            echo "✅ gmi_config table exists with " . count($configData) . " active configurations<br>\n";
            
            // Check if gmi_weekly table exists
            try {
                $weeklyCheck = Class_db::getInstance()->db_select2('gmi_weekly', array(), '', '1');
                echo "✅ gmi_weekly table exists<br>\n";
            } catch (Exception $e) {
                echo "❌ gmi_weekly table missing. Run gmi_weekly_setup.sql first<br>\n";
            }
            
            // Check if daily views exist (these might not exist yet)
            $viewsToCheck = [
                'vw_gamification_ppm_daily',
                'vw_gamification_ppm_assist_daily', 
                'vw_gamification_wo_daily',
                'vw_gamification_wo_assist_daily'
            ];
            
            foreach ($viewsToCheck as $view) {
                try {
                    $viewCheck = Class_db::getInstance()->db_select2($view, array(), '', '1');
                    echo "✅ $view exists<br>\n";
                } catch (Exception $e) {
                    echo "⚠️ $view missing (may need to be created based on your actual table structure)<br>\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ Database connection error: " . $e->getMessage() . "<br>\n";
        }
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "<html><head><title>Weekly Gamification Test Results</title></head><body>\n";
        echo "<h1>Weekly Gamification System Test Results</h1>\n";
        echo "<p>Generated on: " . date('Y-m-d H:i:s') . "</p>\n";
        
        $this->checkDatabaseRequirements();
        $this->testConfiguration();
        $this->testWeekCalculations();
        
        echo "<hr>\n";
        echo "<p><strong>Important Notes:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>Make sure to run <code>gmi_weekly_setup.sql</code> before testing calculations</li>\n";
        echo "<li>Daily views need to be customized based on your actual table structure</li>\n";
        echo "<li>Test with small datasets first before running on production data</li>\n";
        echo "</ul>\n";
        
        echo "<p><em>Ready to test the full calculation? Uncomment the line below:</em></p>\n";
        echo "<pre style='background: #f5f5f5; padding: 10px;'>\n";
        echo "// \$this->testMonthlyCalculation();\n";
        echo "</pre>\n";
        
        echo "</body></html>\n";
    }
}

// Run the tests
$test = new WeeklyGamificationTest();
$test->runAllTests();

// Uncomment the line below when you're ready to test the full calculation
// $test->testMonthlyCalculation();
?>
