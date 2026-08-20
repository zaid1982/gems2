<?php

declare(strict_types=1);

require_once __DIR__ . '/_require_auth.php';

header('Content-Type: application/json; charset=utf-8');

function caRespond(bool $success, array $data = [], ?string $error = null): void
{
    echo json_encode(['success' => $success, 'error' => $error] + $data, JSON_PRETTY_PRINT);
    exit;
}

function caSetIniKey(string $configPath, string $key, string $value): void
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
        throw new RuntimeException('Unable to update cafile in config.ini');
    }
}

try {
    $root = realpath(dirname(__DIR__));
    if ($root === false) {
        caRespond(false, [], 'Unable to resolve project root');
    }

    if (empty($_FILES['cafile']['tmp_name']) || !is_uploaded_file($_FILES['cafile']['tmp_name'])) {
        caRespond(false, [], 'Upload cafile as multipart field "cafile"');
    }

    $tmp = $_FILES['cafile']['tmp_name'];
    $size = (int) ($_FILES['cafile']['size'] ?? 0);
    if ($size < 1000 || $size > 2_000_000) {
        caRespond(false, [], 'CA bundle size is outside the expected range');
    }

    $contents = file_get_contents($tmp);
    if ($contents === false || !str_contains($contents, 'BEGIN CERTIFICATE')) {
        caRespond(false, [], 'Uploaded file is not a PEM certificate bundle');
    }

    $certsDir = $root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'certs';
    if (!is_dir($certsDir) && !@mkdir($certsDir, 0755, true) && !is_dir($certsDir)) {
        caRespond(false, [], 'Unable to create api/library/certs');
    }
    if (!is_writable($certsDir)) {
        caRespond(false, [], 'api/library/certs is not writable');
    }

    $target = $certsDir . DIRECTORY_SEPARATOR . 'cacert.pem';
    if (@move_uploaded_file($tmp, $target) === false && @file_put_contents($target, $contents) === false) {
        caRespond(false, [], 'Unable to write cacert.pem');
    }

    $configPath = $root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'config.ini';
    if (!is_file($configPath) || !is_writable($configPath)) {
        caRespond(false, [
            'bundled_cacert_exists' => is_file($target),
        ], 'cacert.pem saved, but config.ini is not writable');
    }

    caSetIniKey($configPath, 'cafile', $target);

    $config = @parse_ini_file($configPath, true, INI_SCANNER_RAW);
    $fcm = (is_array($config) && isset($config['fcm']) && is_array($config['fcm'])) ? $config['fcm'] : [];
    $cafile = trim((string) ($fcm['cafile'] ?? ''));

    caRespond(true, [
        'bundled_cacert_exists' => is_file($target),
        'bundled_cacert_bytes' => is_file($target) ? filesize($target) : 0,
        'cafile_set' => $cafile !== '',
        'cafile_exists' => $cafile !== '' && is_file($cafile),
        'cafile_readable' => $cafile !== '' && is_file($cafile) && is_readable($cafile),
    ]);
} catch (Throwable $e) {
    caRespond(false, [], $e->getMessage());
}
