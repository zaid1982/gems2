<?php

declare(strict_types=1);

require_once __DIR__ . '/_require_auth.php';

header('Content-Type: application/json; charset=utf-8');

$configPath = dirname(__DIR__) . '/api/library/config.ini';
$config = is_file($configPath) ? @parse_ini_file($configPath, true, INI_SCANNER_RAW) : false;

$fcm = (is_array($config) && isset($config['fcm']) && is_array($config['fcm'])) ? $config['fcm'] : null;
$app = (is_array($config) && isset($config['app']) && is_array($config['app'])) ? $config['app'] : null;

$serviceAccountPath = is_array($fcm) ? trim((string) ($fcm['service_account_path'] ?? '')) : '';
$cafile = is_array($fcm) ? trim((string) ($fcm['cafile'] ?? '')) : '';
$legacy = is_array($fcm) ? trim((string) ($fcm['legacy_server_key'] ?? '')) : '';
$networkSource = is_array($app) ? trim((string) ($app['network_source'] ?? '')) : '';

$bundledCa = dirname(__DIR__) . '/api/library/certs/cacert.pem';
$windowsCandidates = [
    'C:\\xampp\\php\\extras\\ssl\\cacert.pem',
    'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt',
];

$resolvedCa = '';
foreach (array_merge($cafile !== '' ? [$cafile] : [], [$bundledCa], $windowsCandidates) as $candidate) {
    if ($candidate !== '' && is_file($candidate) && is_readable($candidate)) {
        $resolvedCa = $candidate;
        break;
    }
}

echo json_encode([
    'success' => true,
    'config_file_exists' => is_file($configPath),
    'sections' => is_array($config) ? array_keys($config) : [],
    'app' => [
        'section_present' => is_array($app),
        'network_source_set' => $networkSource !== '',
        'network_source' => $networkSource !== '' ? $networkSource : null,
    ],
    'fcm' => [
        'section_present' => is_array($fcm),
        'keys_present' => is_array($fcm) ? array_keys($fcm) : [],
        'project_id_set' => is_array($fcm) && trim((string) ($fcm['project_id'] ?? '')) !== '',
        'project_number_set' => is_array($fcm) && trim((string) ($fcm['project_number'] ?? '')) !== '',
        'service_account_path_set' => $serviceAccountPath !== '',
        'service_account_file_exists' => $serviceAccountPath !== '' && is_file($serviceAccountPath),
        'service_account_readable' => $serviceAccountPath !== '' && is_file($serviceAccountPath) && is_readable($serviceAccountPath),
        'legacy_server_key_set' => $legacy !== '',
        'cafile_set' => $cafile !== '',
        'cafile_exists' => $cafile !== '' && is_file($cafile),
        'resolved_ca_available' => $resolvedCa !== '',
        'bundled_cacert_exists' => is_file($bundledCa),
    ],
], JSON_PRETTY_PRINT);