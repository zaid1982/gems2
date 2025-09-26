<?php
// Quick test to verify PHP mail() -> msmtp -> Exchange pipeline.
// Run: php /Applications/XAMPP/xamppfiles/htdocs/gems2/developer/test_exchange_mail.php

$to = getenv('TEST_TO') ?: 'your.email@yourdomain.com';
$from = getenv('TEST_FROM') ?: 'service_mailbox@yourdomain.com';
$subject = 'GEMS2 Exchange SMTP test (msmtp)';
$body = "Hello,\n\nThis is a test message sent via PHP mail() using msmtp to Exchange.\nTime: " . date('c') . "\n\nRegards,\nGEMS2";
$headers = [
    'From' => $from,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
];

// Format headers
$headerStr = '';
foreach ($headers as $k => $v) {
    $headerStr .= $k . ': ' . $v . "\r\n";
}

// Some MTAs want an envelope sender; PHP allows 5th param for that. msmtp respects -f.
$additionalParams = '-f' . $from;

$ok = mail($to, $subject, $body, $headerStr, $additionalParams);

if ($ok) {
    echo "mail() returned true. Check your inbox and ~/.msmtp.log\n";
    exit(0);
} else {
    echo "mail() returned false. Check Apache/PHP error log and ~/.msmtp.log\n";
    exit(1);
}
