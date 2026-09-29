<?php

/**
 * Work-order PDF layout from api/library/config.ini:
 *   [pdf]
 *   wo_form = gfm
 *   wo_form = jkr
 *
 * gfm = Global FM work order. This is the default.
 * jkr = JKR Arahan Siasatan. Set wo_form = jkr on the JKR server.
 */
function gems_wo_form(): string
{
    $form = '';
    $configPath = dirname(__DIR__) . '/library/config.ini';
    if (is_file($configPath)) {
        $config = parse_ini_file($configPath, true, INI_SCANNER_RAW);
        if (is_array($config)) {
            $form = strtolower(trim((string) ($config['pdf']['wo_form'] ?? '')));
        }
    }

    return $form === 'jkr' ? 'jkr' : 'gfm';
}

function gems_new_wo_pdf()
{
    if (!class_exists('TCPDF', false)) {
        require_once __DIR__ . '/tcpdf_include.php';
    }

    if (gems_wo_form() === 'gfm') {
        require_once __DIR__ . '/wo_lama.php';
        return new Class_pdf_wo();
    }

    require_once __DIR__ . '/wo_jkr.php';
    return new Class_pdf_wo_jkr();
}
