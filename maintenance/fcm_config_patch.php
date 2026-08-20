<?php

declare(strict_types=1);

require_once __DIR__ . '/_require_auth.php';

header('Content-Type: application/json; charset=utf-8');

const FCM_PROJECT_ID = 'gems-eace5';
const FCM_PROJECT_NUMBER = '899105022758';
const FCM_NETWORK_SOURCE = 'gemsPlus';

function fcmRespond(bool $success, array $data = [], ?string $error = null): void
{
    echo json_encode(['success' => $success, 'error' => $error] + $data, JSON_PRETTY_PRINT);
    exit;
}

function fcmProjectRoot(): string
{
    $root = realpath(dirname(__DIR__));
    if ($root === false) {
        throw new RuntimeException('Unable to resolve project root');
    }
    return $root;
}

function fcmCandidateSecretDirs(string $root): array
{
    $dirs = [];
    $parent = dirname($root);
    $grand = dirname($parent);
    foreach ([$grand, $parent] as $base) {
        if ($base !== '' && $base !== '/' && $base !== '\\') {
            $dirs[] = $base . DIRECTORY_SEPARATOR . 'gfm-secrets';
        }
    }
    $dirs[] = 'C:\\xampp\\gfm-secrets';
    $dirs[] = $root . DIRECTORY_SEPARATOR . 'maintenance' . DIRECTORY_SEPARATOR . '.secrets';
    return array_values(array_unique($dirs));
}

function fcmEnsureDir(string $dir): bool
{
    if (is_dir($dir)) {
        return is_writable($dir);
    }
    if (!@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return false;
    }
    return is_writable($dir);
}

function fcmDenyWebAccess(string $dir): void
{
    $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
    }
    $webConfig = $dir . DIRECTORY_SEPARATOR . 'web.config';
    if (!is_file($webConfig)) {
        @file_put_contents($webConfig, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <security>
      <authorization>
        <remove users="*" roles="" verbs="" />
        <add accessType="Deny" users="*" />
      </authorization>
    </security>
  </system.webServer>
</configuration>
XML);
    }
}

function fcmAppendMissingSections(string $configPath): array
{
    $raw = file_get_contents($configPath);
    if ($raw === false) {
        throw new RuntimeException('Unable to read config.ini');
    }

    $parsed = @parse_ini_file($configPath, true, INI_SCANNER_RAW);
    if (!is_array($parsed)) {
        throw new RuntimeException('Unable to parse config.ini');
    }

    $added = [];
    $appendix = '';

    if (!isset($parsed['app']) || !is_array($parsed['app'])) {
        $appendix .= "\n; ------------------------------------------------------------\n";
        $appendix .= "; App / deployment identity (used by mobile push deep-links)\n";
        $appendix .= "; gemsPlus = JKR (gems.jkr.gov.my)\n";
        $appendix .= "[app]\n";
        $appendix .= 'network_source = ' . FCM_NETWORK_SOURCE . "\n";
        $added[] = 'app';
    } elseif (trim((string) ($parsed['app']['network_source'] ?? '')) === '') {
        $raw .= (str_ends_with($raw, "\n") ? '' : "\n") . "network_source = " . FCM_NETWORK_SOURCE . "\n";
        $added[] = 'app.network_source';
    }

    if (!isset($parsed['fcm']) || !is_array($parsed['fcm'])) {
        $appendix .= "\n; ------------------------------------------------------------\n";
        $appendix .= "; Firebase Cloud Messaging (FCM HTTP v1)\n";
        $appendix .= "[fcm]\n";
        $appendix .= 'project_id = ' . FCM_PROJECT_ID . "\n";
        $appendix .= 'project_number = ' . FCM_PROJECT_NUMBER . "\n";
        $appendix .= "service_account_path =\n";
        $appendix .= "legacy_server_key =\n";
        $appendix .= "cafile =\n";
        $added[] = 'fcm';
    }

    if ($appendix !== '') {
        $raw .= (str_ends_with($raw, "\n") ? '' : "\n") . $appendix;
    }

    $backup = $configPath . '.pre-fcm-' . date('Ymd_His') . '.ini';
    if (!@copy($configPath, $backup)) {
        throw new RuntimeException('Unable to backup config.ini');
    }
    if (@file_put_contents($configPath, $raw, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write config.ini. IIS/SYSTEM needs write access.');
    }

    return ['added' => $added, 'backup_created' => true];
}

function fcmSetKey(string $configPath, string $section, string $key, string $value): void
{
    $raw = file_get_contents($configPath);
    if ($raw === false) {
        throw new RuntimeException('Unable to read config.ini');
    }
    $safeValue = str_replace('\\', '/', $value);
    $line = $key . ' = "' . str_replace('"', '', $safeValue) . '"';
    $pattern = '/^\s*' . preg_quote($key, '/') . '\s*=.*$/m';
    if (preg_match($pattern, $raw)) {
        $raw = preg_replace($pattern, $line, $raw, 1);
    } else {
        $raw .= (str_ends_with($raw, "\n") ? '' : "\n") . $line . "\n";
    }
    if (@file_put_contents($configPath, $raw, LOCK_EX) === false) {
        throw new RuntimeException('Unable to update ' . $key);
    }
}

try {
    $root = fcmProjectRoot();
    $configPath = $root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'config.ini';
    if (!is_file($configPath)) {
        fcmRespond(false, [], 'config.ini not found');
    }
    if (!is_writable($configPath) || !is_writable(dirname($configPath))) {
        fcmRespond(false, [
            'config_writable' => false,
            'web_user' => function_exists('posix_geteuid') ? (string) posix_geteuid() : get_current_user(),
        ], 'config.ini is not writable by the web server user');
    }

    $result = fcmAppendMissingSections($configPath);

    $placedPath = '';
    $secretDirUsed = '';
    $existingCandidates = [];
    foreach (fcmCandidateSecretDirs($root) as $dir) {
        $existingCandidates[] = $dir . DIRECTORY_SEPARATOR . 'gems-eace5-firebase-adminsdk.json';
    }
    foreach ($existingCandidates as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            $placedPath = $candidate;
            $secretDirUsed = dirname($candidate);
            fcmSetKey($configPath, 'fcm', 'service_account_path', $placedPath);
            break;
        }
    }
    if ($placedPath === '' && !empty($_FILES['service_account']['tmp_name']) && is_uploaded_file($_FILES['service_account']['tmp_name'])) {
        $tmp = $_FILES['service_account']['tmp_name'];
        $decoded = json_decode((string) file_get_contents($tmp), true);
        if (!is_array($decoded) || empty($decoded['private_key']) || empty($decoded['client_email'])) {
            fcmRespond(false, $result, 'Uploaded file is not a valid Firebase service account JSON');
        }
        foreach (fcmCandidateSecretDirs($root) as $dir) {
            if (fcmEnsureDir($dir)) {
                fcmDenyWebAccess($dir);
                $target = $dir . DIRECTORY_SEPARATOR . 'gems-eace5-firebase-adminsdk.json';
                if (@move_uploaded_file($tmp, $target) || @copy($tmp, $target)) {
                    @chmod($target, 0600);
                    $placedPath = $target;
                    $secretDirUsed = $dir;
                    break;
                }
            }
        }
        if ($placedPath === '') {
            fcmRespond(false, $result, 'Could not store service account JSON in a writable secrets directory');
        }
        fcmSetKey($configPath, 'fcm', 'service_account_path', $placedPath);
    }

    $config = @parse_ini_file($configPath, true, INI_SCANNER_RAW);
    $fcm = (is_array($config) && isset($config['fcm']) && is_array($config['fcm'])) ? $config['fcm'] : [];
    $app = (is_array($config) && isset($config['app']) && is_array($config['app'])) ? $config['app'] : [];
    $serviceAccountPath = trim((string) ($fcm['service_account_path'] ?? ''));

    fcmRespond(true, [
        'added' => $result['added'],
        'backup_created' => true,
        'sections' => is_array($config) ? array_keys($config) : [],
        'app' => [
            'section_present' => $app !== [],
            'network_source' => trim((string) ($app['network_source'] ?? '')) ?: null,
        ],
        'fcm' => [
            'section_present' => $fcm !== [],
            'keys_present' => array_keys($fcm),
            'project_id_set' => trim((string) ($fcm['project_id'] ?? '')) !== '',
            'project_number_set' => trim((string) ($fcm['project_number'] ?? '')) !== '',
            'service_account_path_set' => $serviceAccountPath !== '',
            'service_account_file_exists' => $serviceAccountPath !== '' && is_file($serviceAccountPath),
            'legacy_server_key_set' => trim((string) ($fcm['legacy_server_key'] ?? '')) !== '',
            'cafile_set' => trim((string) ($fcm['cafile'] ?? '')) !== '',
        ],
        'secret_dir_used' => $secretDirUsed !== '' ? basename($secretDirUsed) : null,
    ]);
} catch (Throwable $e) {
    fcmRespond(false, [], $e->getMessage());
}
