<?php
// Safe PTW data cleaner: purges PTW tables and uploaded files.
// Usage (CLI or browser):
//   tools/ptw_clear_data.php?dry_run=1               # default: preview only
//   tools/ptw_clear_data.php?confirm=1&dry_run=0     # execute purge
//   Optional: &site_id=19                             # restrict to site
//   Optional: &delete_files=1|0                       # also delete upload files (default 1 on confirm)
//   Optional: &dangerously_allow_production=1         # required if environment != development

require_once __DIR__ . '/../api/library/constant.php';
require_once __DIR__ . '/../api/function/db.php';
require_once __DIR__ . '/../api/function/f_general.php';

header('Content-Type: text/plain');
date_default_timezone_set('Asia/Kuala_Lumpur');

function echo_line($msg) { echo $msg . "\n"; }

try {
    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_general->__set('constant', $constant);
    Class_db::getInstance()->db_connect();
} catch (Exception $e) {
    http_response_code(500);
    echo_line('Init failed: ' . $e->getMessage());
    exit;
}

// Read environment for guardrails
$config = @parse_ini_file(__DIR__ . '/../api/library/config.ini');
$env = $config['environment'] ?? 'development';
$allowProd = isset($_GET['dangerously_allow_production']) && $_GET['dangerously_allow_production'] == '1';

$dryRun = !isset($_GET['dry_run']) ? 1 : intval($_GET['dry_run']);
$confirm = isset($_GET['confirm']) ? intval($_GET['confirm']) : 0;
$siteId = isset($_GET['site_id']) && $_GET['site_id'] !== '' ? trim($_GET['site_id']) : null;
$deleteFilesParam = isset($_GET['delete_files']) ? intval($_GET['delete_files']) : null; // null = auto

// If not explicitly set, default delete_files to 1 when confirm=1, else 0 on dry-run
$deleteFiles = ($deleteFilesParam !== null) ? $deleteFilesParam : ($confirm ? 1 : 0);

if ($env !== 'development' && !$allowProd) {
    echo_line('Refusing to run: environment is ' . $env . '. Pass ?dangerously_allow_production=1 to override.');
    exit;
}

if (!$confirm) {
    echo_line('Mode: DRY RUN (no data will be modified). Add ?confirm=1&dry_run=0 to execute.');
} else if ($dryRun) {
    echo_line('Note: confirm=1 is ignored while dry_run=1. Set dry_run=0 to execute.');
}

$db = Class_db::getInstance();

// Helpers
function count_sql($sql, $params = []) {
    $rows = Class_db::getInstance()->db_raw_select_colm_prepared($sql, $params, 'c', 0);
    return intval($rows[0] ?? 0);
}

function make_in_clause_params($prefix, $values) {
    $params = [];
    $placeholders = [];
    $i = 0;
    foreach ($values as $v) {
        $key = ':' . $prefix . $i;
        $params[$key] = strval($v);
        $placeholders[] = $key;
        $i++;
    }
    return [$placeholders, $params];
}

function rrmdir($dir) {
    if (!is_dir($dir)) return 0;
    $count = 0;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $count += rrmdir($path);
            @rmdir($path);
        } else {
            if (@unlink($path)) { $count++; }
        }
    }
    return $count;
}

// Determine scope
$scoped = $siteId !== null;

// Gather permit IDs in scope
$permitIds = [];
try {
    if ($scoped) {
        $permits = $db->db_select('ptw_permit', array('site_id' => strval($siteId)), 'ptw_permit_id');
    } else {
        $permits = $db->db_select('ptw_permit', array(), 'ptw_permit_id');
    }
    foreach ($permits as $p) { $permitIds[] = $p['ptw_permit_id']; }
} catch (Exception $e) {
    // Table may not exist on some environments
    $permits = [];
}

// Compute counts (DB)
$counts = [
    'ptw_permit' => 0,
    'ptw_worker' => 0,
    'ptw_document' => 0,
    'ptw_status_history' => 0,
    'ptw_approval_log' => 0,
    'ptw_number_sequence' => 0,
];

try {
    if ($scoped) {
        $counts['ptw_permit'] = count_sql('SELECT COUNT(*) AS c FROM ptw_permit WHERE site_id = :sid', [':sid' => strval($siteId)]);
        if (!empty($permitIds)) {
            list($ph, $params) = make_in_clause_params('pid', $permitIds);
            $in = implode(',', $ph);
            $counts['ptw_worker'] = count_sql("SELECT COUNT(*) AS c FROM ptw_worker WHERE ptw_permit_id IN ($in)", $params);
            $counts['ptw_document'] = count_sql("SELECT COUNT(*) AS c FROM ptw_document WHERE ptw_permit_id IN ($in)", $params);
            $counts['ptw_status_history'] = count_sql("SELECT COUNT(*) AS c FROM ptw_status_history WHERE ptw_permit_id IN ($in)", $params);
            $counts['ptw_approval_log'] = count_sql("SELECT COUNT(*) AS c FROM ptw_approval_log WHERE ptw_permit_id IN ($in)", $params);
        }
        $counts['ptw_number_sequence'] = count_sql('SELECT COUNT(*) AS c FROM ptw_number_sequence WHERE site_id = :sid', [':sid' => strval($siteId)]);
    } else {
        $counts['ptw_permit'] = count_sql('SELECT COUNT(*) AS c FROM ptw_permit', []);
        $counts['ptw_worker'] = count_sql('SELECT COUNT(*) AS c FROM ptw_worker', []);
        $counts['ptw_document'] = count_sql('SELECT COUNT(*) AS c FROM ptw_document', []);
        $counts['ptw_status_history'] = count_sql('SELECT COUNT(*) AS c FROM ptw_status_history', []);
        $counts['ptw_approval_log'] = count_sql('SELECT COUNT(*) AS c FROM ptw_approval_log', []);
        $counts['ptw_number_sequence'] = count_sql('SELECT COUNT(*) AS c FROM ptw_number_sequence', []);
    }
} catch (Exception $e) {
    echo_line('[Warning] Count query failed: ' . $e->getMessage());
}

echo_line('PTW Data Cleaner');
echo_line('Environment: ' . $env);
echo_line('Scope: ' . ($scoped ? ('site_id=' . $siteId) : 'ALL SITES'));
echo_line('Will delete files: ' . ($deleteFiles ? 'YES' : 'NO'));
echo_line('--- Current row counts (scope):');
foreach ($counts as $tbl => $c) {
    echo_line(sprintf('  %-20s %10d', $tbl, $c));
}

// Files summary
$uploadBase = realpath(__DIR__ . '/../upload/ptw');
if ($uploadBase && is_dir($uploadBase)) {
    if ($scoped) {
        $target = $uploadBase . DIRECTORY_SEPARATOR . basename($siteId);
        echo_line('Upload path (scoped): ' . $target . (is_dir($target) ? '' : ' (missing)'));
    } else {
        echo_line('Upload base: ' . $uploadBase);
        // list top-level site directories
        $sites = array_values(array_filter(scandir($uploadBase), function($x) use ($uploadBase) {
            return $x !== '.' && $x !== '..' && is_dir($uploadBase . DIRECTORY_SEPARATOR . $x);
        }));
        echo_line('Found site folders: ' . (empty($sites) ? '(none)' : implode(', ', $sites)));
    }
} else {
    echo_line('Upload base not found: ' . (__DIR__ . '/../upload/ptw'));
}

if (!$confirm || $dryRun) {
    echo_line('--- DRY RUN complete. No changes made.');
    exit;
}

// Execute purge
try {
    $db->db_beginTransaction();

    if ($scoped) {
        if (!empty($permitIds)) {
            list($ph, $params) = make_in_clause_params('pid', $permitIds);
            $in = implode(',', $ph);
            $db->db_delete('ptw_worker', [], "ptw_permit_id IN ($in)");
            $db->db_delete('ptw_document', [], "ptw_permit_id IN ($in)");
            $db->db_delete('ptw_status_history', [], "ptw_permit_id IN ($in)");
            $db->db_delete('ptw_approval_log', [], "ptw_permit_id IN ($in)");
        }
        $db->db_delete('ptw_number_sequence', [], 'site_id = ' . intval($siteId));
        $db->db_delete('ptw_permit', ['site_id' => strval($siteId)]);
    } else {
        // Full wipe of PTW data
        $db->db_delete('ptw_worker', [], '1=1');
        $db->db_delete('ptw_document', [], '1=1');
        $db->db_delete('ptw_status_history', [], '1=1');
        $db->db_delete('ptw_approval_log', [], '1=1');
        $db->db_delete('ptw_number_sequence', [], '1=1');
        $db->db_delete('ptw_permit', [], '1=1');
    }

    $db->db_commit();
    echo_line('Database purge: DONE');
} catch (Exception $e) {
    $db->db_rollback();
    http_response_code(500);
    echo_line('Database purge FAILED: ' . $e->getMessage());
    exit;
}

// Files purge (best-effort, out of transaction)
if ($deleteFiles) {
    $base = realpath(__DIR__ . '/../upload/ptw');
    if ($base && is_dir($base)) {
        $deletedFiles = 0;
        if ($scoped) {
            $target = $base . DIRECTORY_SEPARATOR . basename($siteId);
            if (is_dir($target)) {
                $deletedFiles = rrmdir($target);
                @rmdir($target); // remove now-empty site folder
                echo_line('Removed files (site ' . $siteId . '): ' . $deletedFiles);
            } else {
                echo_line('No upload folder found for site ' . $siteId);
            }
        } else {
            $entries = scandir($base);
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') continue;
                $path = $base . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($path)) {
                    $deletedFiles += rrmdir($path);
                    @rmdir($path);
                } else {
                    if (@unlink($path)) { $deletedFiles++; }
                }
            }
            echo_line('Removed files (all sites): ' . $deletedFiles);
        }
    } else {
        echo_line('Skip files: upload/ptw not found');
    }
} else {
    echo_line('Skip files: delete_files=0');
}

echo_line('PTW data cleanup: COMPLETE');
?>
