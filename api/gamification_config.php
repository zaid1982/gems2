<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Initialize required classes
$constant = new Class_constant();
$fn_general = new Class_general();
$fn_general->__set('constant', $constant);

try {
    Class_db::getInstance()->db_connect();
    
    switch ($action) {
        case 'get_all':
            echo json_encode(getAllConfiguration());
            break;
            
        case 'save_all':
            $config = json_decode($_POST['config'], true);
            echo json_encode(saveAllConfiguration($config));
            break;
            
        case 'get_single':
            $key = $_GET['key'] ?? '';
            echo json_encode(getSingleConfiguration($key));
            break;
            
        case 'update_single':
            $key = $_POST['key'] ?? '';
            $value = $_POST['value'] ?? '';
            echo json_encode(updateSingleConfiguration($key, $value));
            break;
            
        case 'reset_defaults':
            echo json_encode(resetToDefaults());
            break;
            
        case 'debug_raw':
            echo json_encode(debugRawData());
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action specified'
            ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

function getAllConfiguration() {
    try {
        $result = Class_db::getInstance()->db_select2('gmi_config', array('status' => '1'), 'config_key ASC');
        $config = [];
        if ($result && count($result) > 0) {
            foreach ($result as $row) {
                // Skip rows with empty config_key
                if (empty($row['configKey'])) {
                    continue;
                }
                
                $value = $row['configValue'];
                
                // Convert value based on data type (with fallback)
                $dataType = isset($row['dataType']) ? $row['dataType'] : 'string';
                switch ($dataType) {
                    case 'int':
                        $value = (int)$value;
                        break;
                    case 'float':
                        $value = (float)$value;
                        break;
                    case 'string':
                    default:
                        $value = (string)$value;
                        break;
                }
                
                $config[$row['configKey']] = $value;
            }
        }
        
        // If no valid configurations found, return helpful message
        if (empty($config)) {
            return [
                'success' => false,
                'message' => 'No valid configuration data found. Check if gmi_config table has data with proper config_key values.',
                'total_rows' => count($result)
            ];
        }
        
        return [
            'success' => true,
            'data' => $config,
            'message' => 'Configuration loaded successfully'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to load configuration: ' . $e->getMessage()
        ];
    }
}

function saveAllConfiguration($config) {
    try {
        // Define expected configurations with their data types
        $expectedConfigs = [
            'tier_medalist_threshold' => 'int',
            'tier_finisher_threshold' => 'int',
            'mbv_tier1_threshold' => 'int',
            'mbv_tier2_threshold' => 'int',
            'mbv_tier1_multiplier' => 'float',
            'mbv_tier2_multiplier' => 'float',
            'mbv_tier3_multiplier' => 'float',
            'weight_completed' => 'float',
            'weight_ontime' => 'float',
            'weight_late_penalty' => 'float',
            'self_finding_points' => 'int',
            'point_scale_factor' => 'int',
            'productivity_base' => 'int',
            'wo_ontime_multiplier' => 'float'
        ];
        
        $updatedCount = 0;
        $errors = [];
        
        foreach ($config as $key => $value) {
            if (!isset($expectedConfigs[$key])) {
                $errors[] = "Unknown configuration key: $key";
                continue;
            }
            
            // Validate and convert value based on data type
            $dataType = $expectedConfigs[$key];
            switch ($dataType) {
                case 'int':
                    if (!is_numeric($value) || (int)$value != $value) {
                        $errors[] = "Invalid integer value for $key: $value";
                        continue 2;
                    }
                    $value = (int)$value;
                    break;
                    
                case 'float':
                    if (!is_numeric($value)) {
                        $errors[] = "Invalid numeric value for $key: $value";
                        continue 2;
                    }
                    $value = (float)$value;
                    break;
                    
                case 'string':
                default:
                    $value = (string)$value;
                    break;
            }
            
            // Check if configuration exists
            $existingRecord = Class_db::getInstance()->db_select_single2('gmi_config', array('config_key' => $key, 'status' => '1'));
            
            if ($existingRecord && count($existingRecord) > 0) {
                // Update existing record
                $updateData = array('config_value' => $value, 'last_updated_at' => date('Y-m-d H:i:s'));
                $updateResult = Class_db::getInstance()->db_update('gmi_config', $updateData, array('config_key' => $key, 'status' => '1'));
                
                if ($updateResult !== false) {
                    $updatedCount++;
                } else {
                    $errors[] = "Failed to update $key";
                }
            } else {
                // Insert new record (should not happen if defaults are properly set)
                $insertData = array(
                    'config_key' => $key,
                    'config_value' => $value,
                    'data_type' => $dataType,
                    'description' => ucwords(str_replace('_', ' ', $key)),
                    'last_updated_by' => 'system',
                    'last_updated_at' => date('Y-m-d H:i:s'),
                    'status' => '1'
                );
                $insertResult = Class_db::getInstance()->db_insert('gmi_config', $insertData);
                
                if ($insertResult !== false) {
                    $updatedCount++;
                } else {
                    $errors[] = "Failed to insert $key";
                }
            }
        }
        
        if (count($errors) > 0) {
            return [
                'success' => false,
                'message' => 'Some configurations could not be saved: ' . implode(', ', $errors),
                'updated_count' => $updatedCount
            ];
        }
        
        return [
            'success' => true,
            'message' => "Successfully updated $updatedCount configuration(s)",
            'updated_count' => $updatedCount
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to save configuration: ' . $e->getMessage()
        ];
    }
}

function getSingleConfiguration($key) {
    try {
        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'Configuration key is required'
            ];
        }
        
        $result = Class_db::getInstance()->db_select_single2('gmi_config', array('config_key' => $key, 'status' => '1'));
        
        if ($result && count($result) > 0) {
            $value = $result['config_value'];
            
            // Convert value based on data type
            switch ($result['data_type']) {
                case 'int':
                    $value = (int)$value;
                    break;
                case 'float':
                    $value = (float)$value;
                    break;
                case 'string':
                default:
                    $value = (string)$value;
                    break;
            }
            
            return [
                'success' => true,
                'data' => [
                    'key' => $result['config_key'],
                    'value' => $value,
                    'data_type' => $result['data_type'],
                    'description' => $result['description']
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Configuration not found'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to get configuration: ' . $e->getMessage()
        ];
    }
}

function updateSingleConfiguration($key, $value) {
    try {
        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'Configuration key is required'
            ];
        }
        
        // Get current configuration to validate data type
        $checkResult = Class_db::getInstance()->db_select_single2('gmi_config', array('config_key' => $key, 'status' => '1'));
        
        if (!$checkResult || count($checkResult) == 0) {
            return [
                'success' => false,
                'message' => 'Configuration key not found'
            ];
        }
        
        $dataType = $checkResult['data_type'];
        
        // Validate and convert value based on data type
        switch ($dataType) {
            case 'int':
                if (!is_numeric($value) || (int)$value != $value) {
                    return [
                        'success' => false,
                        'message' => 'Invalid integer value'
                    ];
                }
                $value = (int)$value;
                break;
                
            case 'float':
                if (!is_numeric($value)) {
                    return [
                        'success' => false,
                        'message' => 'Invalid numeric value'
                    ];
                }
                $value = (float)$value;
                break;
                
            case 'string':
            default:
                $value = (string)$value;
                break;
        }
        
        // Update configuration
        $updateData = array('config_value' => $value, 'last_updated_at' => date('Y-m-d H:i:s'));
        $updateResult = Class_db::getInstance()->db_update('gmi_config', $updateData, array('config_key' => $key, 'status' => '1'));
        
        if ($updateResult !== false) {
            return [
                'success' => true,
                'message' => 'Configuration updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update configuration'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to update configuration: ' . $e->getMessage()
        ];
    }
}

function resetToDefaults() {
    try {
        // Default configurations
        $defaults = [
            ['tier_medalist_threshold', '150', 'int', 'Minimum completed tasks required to achieve Medalist tier'],
            ['tier_finisher_threshold', '80', 'int', 'Minimum completed tasks required to achieve Finisher tier'],
            ['mbv_tier1_threshold', '50', 'int', 'MBV threshold for tier 1 multiplier (lowest performance)'],
            ['mbv_tier2_threshold', '100', 'int', 'MBV threshold for tier 2 multiplier (medium performance)'],
            ['mbv_tier1_multiplier', '1', 'float', 'Point multiplier for tier 1 (MBV ≤ 50)'],
            ['mbv_tier2_multiplier', '3', 'float', 'Point multiplier for tier 2 (51 ≤ MBV ≤ 100)'],
            ['mbv_tier3_multiplier', '5', 'float', 'Point multiplier for tier 3 (MBV > 100)'],
            ['weight_completed', '0.3', 'float', 'Weight percentage for completion points in scoring calculation'],
            ['weight_ontime', '0.7', 'float', 'Weight percentage for on-time points in scoring calculation'],
            ['weight_late_penalty', '0.15', 'float', 'Weight percentage for late penalty in scoring calculation'],
            ['self_finding_points', '5', 'int', 'Points awarded per self-finding work order'],
            ['point_scale_factor', '10000', 'int', 'Scaling factor for all point calculations'],
            ['productivity_base', '90', 'int', 'Base productivity percentage for calculations'],
            ['wo_ontime_multiplier', '2', 'float', 'Multiplier for work order on-time calculations']
        ];
        
        $updatedCount = 0;
        
        foreach ($defaults as $config) {
            list($key, $value, $dataType, $description) = $config;
            
            // Check if configuration exists
            $existingRecord = Class_db::getInstance()->db_select_single2('gmi_config', array('config_key' => $key, 'status' => '1'));
            
            if ($existingRecord && count($existingRecord) > 0) {
                // Update existing record
                $updateData = array('config_value' => $value, 'last_updated_at' => date('Y-m-d H:i:s'));
                $updateResult = Class_db::getInstance()->db_update('gmi_config', $updateData, array('config_key' => $key, 'status' => '1'));
                
                if ($updateResult !== false) {
                    $updatedCount++;
                }
            } else {
                // Insert new record
                $insertData = array(
                    'config_key' => $key,
                    'config_value' => $value,
                    'data_type' => $dataType,
                    'description' => $description,
                    'last_updated_by' => 'system',
                    'last_updated_at' => date('Y-m-d H:i:s'),
                    'status' => '1'
                );
                $insertResult = Class_db::getInstance()->db_insert('gmi_config', $insertData);
                
                if ($insertResult !== false) {
                    $updatedCount++;
                }
            }
        }
        
        return [
            'success' => true,
            'message' => "Successfully reset $updatedCount configuration(s) to defaults",
            'updated_count' => $updatedCount
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to reset to defaults: ' . $e->getMessage()
        ];
    }
}

function debugRawData() {
    try {
        // Get the exact same query that getAllConfiguration uses
        $result = Class_db::getInstance()->db_select2('gmi_config', array('status' => '1'), 'config_key ASC');
        
        return [
            'success' => true,
            'raw_result' => $result,
            'result_count' => count($result),
            'first_row_keys' => $result && count($result) > 0 ? array_keys($result[0]) : [],
            'first_row_data' => $result && count($result) > 0 ? $result[0] : null,
            'message' => 'Raw debug data'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Debug failed: ' . $e->getMessage()
        ];
    }
}
?>
