<?php
/**
 * Developer helper: send a test email immediately using direct Exchange SMTP (send_email_365_direct) WITHOUT using the DB queue.
 *
 * Usage:
 *   php developer/run_send_email_365_test.php recipient@example.com
 *   TEST_TO=recipient@example.com php developer/run_send_email_365_test.php
 *
 * Optional env vars:
 *   TEST_TO          Target recipient (overridden by first CLI arg if provided)
 *   TEST_SUBJECT     Custom subject (default auto-generated)
 *   TEST_BODY        Custom HTML body (default basic template)
 *
 * Flow:
 *   1. Build an ad-hoc MIME message (HTML) with optional custom subject/body.
 *   2. Invoke Class_email::send_email_365_direct() to transmit via SMTP immediately.
 *   3. Exit with 0 on success, non-zero on failure.
 *
 * Notes:
 *   - Requires [smtp] and [mail] sections configured in api/library/config.ini.
 *   - Does not depend on an email_template placeholder set; template id is set to 0.
 *   - Safe for repeated runs; subject includes timestamp + random token to avoid collisions.
 */

$baseDir = dirname(__DIR__);
require_once $baseDir . '/api/library/constant.php';
// DB not strictly required for direct send, but keep general utilities (logging)
// If you want zero DB dependency, you can remove db.php and any Class_db calls.
require_once $baseDir . '/api/function/db.php';
require_once $baseDir . '/api/function/f_general.php';
require_once $baseDir . '/api/function/f_email.php';

$fn_general = new Class_general();
$email      = new Class_email();
$email->__set('fn_general', $fn_general);

$recipient = $argv[1] ?? getenv('TEST_TO') ?? '';
if (empty($recipient)) {
    fwrite(STDERR, "Usage: php developer/run_send_email_365_test.php recipient@example.com\n" .
        "Or set TEST_TO env var.\n");
    exit(2);
}

$subject = getenv('TEST_SUBJECT');
if (empty($subject)) {
    $subject = 'GEMS2 send_email_365 smoke ' . date('Y-m-d H:i:s') . ' #' . substr(bin2hex(random_bytes(3)),0,6);
}

$body = getenv('TEST_BODY');
if (empty($body)) {
    $body = '<html><body style="font-family:Arial,sans-serif">'
        . '<h2>GEMS2 Direct SMTP Test</h2>'
        . '<p>This is a test message sent via <code>send_email_365</code> at <strong>' . date('c') . '</strong>.</p>'
        . '<ul>'
        . '<li>Recipient: ' . htmlspecialchars($recipient, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
        . '<li>Subject: ' . htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
        . '</ul>'
        . '<p>If you received this, direct SMTP (Exchange) delivery is working.</p>'
        . '<hr><small>Automated test email – safe to ignore.</small>'
        . '</body></html>';
}

try {
    // Direct immediate send (no DB queue interaction)
    $ok = $email->send_email_365_direct($recipient, $subject, $body);
    if ($ok) {
        echo "SUCCESS: Direct SMTP email sent to {$recipient}\n";
        exit(0);
    }
    echo "FAIL: send_email_365_direct returned false (unexpected).\n";
    exit(1);
} catch (Exception $ex) {
    $fn_general->log_error('DEV', 'run_send_email_365_test', __LINE__, $ex->getMessage());
    fwrite(STDERR, "Exception: " . $ex->getMessage() . "\n");
    exit(99);
}
