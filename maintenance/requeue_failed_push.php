<?php
/**
 * Requeue failed push notifications from noti_log back into noti_send.
 *
 * Usage:
 *   php maintenance/requeue_failed_push.php
 *   php maintenance/requeue_failed_push.php --since="2026-08-13 00:00:00"
 *   php maintenance/requeue_failed_push.php --dry-run
 *
 * - Dedupes by user_id + noti_text_id + body + data
 * - Refreshes FCM token from sys_user
 * - Strips [FAIL: ...] suffixes from titles
 * - Resets retry_count to 0 so scheduler will pick them up immediately
 */

$options = getopt('', array('since:', 'dry-run', 'help'));
if (isset($options['help'])) {
    echo "Usage: php maintenance/requeue_failed_push.php [--since=\"YYYY-MM-DD HH:MM:SS\"] [--dry-run]\n";
    exit(0);
}

$since = isset($options['since']) ? trim($options['since']) : date('Y-m-d H:i:s', strtotime('-2 days'));
$dryRun = isset($options['dry-run']);

$config = parse_ini_file(__DIR__ . '/../api/library/config.ini', true);
$db = $config['database'];
$pdo = new PDO(
    'mysql:host='.$db['dbhost'].';dbname='.$db['dbname'].';charset=utf8',
    $db['username'],
    $db['password'],
    array(PDO::ATTR_TIMEOUT => 30, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$col = $pdo->query("SHOW COLUMNS FROM noti_send LIKE 'noti_retry_count'")->fetch();
if (empty($col)) {
    fwrite(STDERR, "Missing retry columns. Run maintenance/add_noti_retry_columns.sql first.\n");
    exit(1);
}

echo "DB=".$pdo->query('SELECT DATABASE()')->fetchColumn()."\n";
echo "since=$since dry_run=".($dryRun ? 'yes' : 'no')."\n";
echo "noti_send before=".$pdo->query('SELECT COUNT(*) FROM noti_send')->fetchColumn()."\n";

$selectSql = <<<SQL
SELECT
  x.noti_text_id,
  u.user_token AS noti_to,
  TRIM(REGEXP_REPLACE(x.noti_title, '\\\\s*\\\\[FAIL:.*$', '')) AS noti_title,
  x.noti_html,
  x.noti_data,
  x.user_id
FROM (
  SELECT n.user_id, n.noti_text_id, n.noti_html, n.noti_data, n.noti_title, n.noti_log_id,
         ROW_NUMBER() OVER (
           PARTITION BY n.user_id, n.noti_text_id, n.noti_html, IFNULL(n.noti_data, '')
           ORDER BY n.noti_log_id DESC
         ) AS rn
  FROM noti_log n
  WHERE n.noti_log_status = 23
    AND n.noti_log_time_sent >= :since
    AND n.user_id IS NOT NULL
) x
INNER JOIN sys_user u ON u.user_id = x.user_id
WHERE x.rn = 1
  AND u.user_status = 1
  AND u.user_token IS NOT NULL
  AND u.user_token != ''
SQL;

if ($dryRun) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ('.$selectSql.') q');
    $stmt->execute(array(':since' => $since));
    echo "would_queue=".$stmt->fetchColumn()."\n";
    exit(0);
}

$insertSql = 'INSERT INTO noti_send (noti_text_id, noti_to, noti_title, noti_html, noti_data, user_id, noti_retry_count, noti_next_retry_at, noti_last_error)
SELECT q.noti_text_id, q.noti_to,
       IF(q.noti_title = \'\' OR q.noti_title IS NULL, \'GEMS\', q.noti_title),
       q.noti_html, q.noti_data, q.user_id, 0, NULL, NULL
FROM ('.$selectSql.') q';

$stmt = $pdo->prepare($insertSql);
$t = microtime(true);
$stmt->execute(array(':since' => $since));
echo 'inserted='.$stmt->rowCount().' in '.round(microtime(true) - $t, 2)."s\n";
echo 'noti_send after='.$pdo->query('SELECT COUNT(*) FROM noti_send')->fetchColumn()."\n";
echo "Done. Run: php api/scheduler_push_notification.php\n";
