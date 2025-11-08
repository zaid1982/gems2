<?php
/**
 * Schema Comparison Tool - Configuration Template
 * 
 * 1. Copy this file to: schema_compare_config.php
 * 2. Update the database credentials below
 * 3. Access via: http://localhost/gems2/maintenance/schema_compare.php
 */

return [
    'dev' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'gems2_dev',  // Your development database
        'label' => 'Development (Local)'
    ],
    'prod' => [
        'host' => 'production-server.com',  // CHANGE THIS
        'user' => 'prod_user',               // CHANGE THIS
        'pass' => 'prod_password',           // CHANGE THIS
        'name' => 'gems2_production',        // CHANGE THIS
        'label' => 'Production Server'
    ]
];
