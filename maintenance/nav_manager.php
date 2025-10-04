<?php
/**
 * Navigation Manager Backend
 * CRUD for sys_nav, sys_nav_second, and role assignment/order in sys_nav_role
 *
 * NOTE: Intended for maintenance use only. Consider protecting this endpoint in production.
 */

require_once('../api/class/Constant.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = Constant::$dbHost;
$username = Constant::$dbUserName;
$password = Constant::$dbUserPassword;
$database = Constant::$dbName;

function send_json($ok, $data = null, $error = null) {
    echo json_encode([
        'success' => $ok,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    send_json(false, null, 'DB connection failed: ' . $e->getMessage());
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list_all': {
            // Main menus
            $navs = $pdo->query('SELECT nav_id, nav_desc, IFNULL(nav_status,1) nav_status FROM sys_nav ORDER BY nav_id')->fetchAll();
            // Submenus
            $subs = $pdo->query('SELECT nav_second_id, nav_id, nav_second_desc, nav_second_page, IFNULL(nav_second_status,1) nav_second_status FROM sys_nav_second ORDER BY nav_id, nav_second_id')->fetchAll();
            // Roles
            $roles = $pdo->query('SELECT role_id, role_desc, role_status FROM ref_role ORDER BY role_id')->fetchAll();
            // Assignments
            $assign = $pdo->query('SELECT role_id, nav_id, nav_second_id, nav_role_turn FROM sys_nav_role ORDER BY role_id, nav_role_turn')->fetchAll();
            send_json(true, [ 'navs' => $navs, 'submenus' => $subs, 'roles' => $roles, 'assignments' => $assign ]);
        }
        case 'create_nav': {
            $desc = trim($_POST['nav_desc'] ?? '');
            $status = isset($_POST['nav_status']) ? intval($_POST['nav_status']) : 1;
            if ($desc === '') send_json(false, null, 'nav_desc required');
            // Idempotent create by desc
            $stmt = $pdo->prepare('SELECT nav_id FROM sys_nav WHERE nav_desc = ? LIMIT 1');
            $stmt->execute([$desc]);
            $row = $stmt->fetch();
            if ($row) {
                // Update status optionally
                $pdo->prepare('UPDATE sys_nav SET nav_status = ? WHERE nav_id = ?')->execute([$status, $row['nav_id']]);
                send_json(true, ['nav_id' => $row['nav_id'], 'updated' => true]);
            } else {
                $pdo->prepare('INSERT INTO sys_nav (nav_desc, nav_status) VALUES (?, ?)')->execute([$desc, $status]);
                $id = $pdo->lastInsertId();
                send_json(true, ['nav_id' => $id, 'created' => true]);
            }
        }
        case 'update_nav': {
            $id = intval($_POST['nav_id'] ?? 0);
            $desc = isset($_POST['nav_desc']) ? trim($_POST['nav_desc']) : null;
            $status = isset($_POST['nav_status']) ? intval($_POST['nav_status']) : null;
            if ($id <= 0) send_json(false, null, 'nav_id required');
            $fields = [];
            $params = [];
            if ($desc !== null && $desc !== '') { $fields[] = 'nav_desc = ?'; $params[] = $desc; }
            if ($status !== null) { $fields[] = 'nav_status = ?'; $params[] = $status; }
            if (empty($fields)) send_json(false, null, 'No fields to update');
            $params[] = $id;
            $pdo->prepare('UPDATE sys_nav SET ' . implode(',', $fields) . ' WHERE nav_id = ?')->execute($params);
            send_json(true, ['nav_id' => $id]);
        }
        case 'create_submenu': {
            $nav_id = intval($_POST['nav_id'] ?? 0);
            $desc = trim($_POST['nav_second_desc'] ?? '');
            $page = trim($_POST['nav_second_page'] ?? '');
            $status = isset($_POST['nav_second_status']) ? intval($_POST['nav_second_status']) : 1;
            if ($nav_id <= 0 || $desc === '' || $page === '') send_json(false, null, 'nav_id, nav_second_desc, nav_second_page required');
            $stmt = $pdo->prepare('SELECT nav_second_id FROM sys_nav_second WHERE nav_id = ? AND nav_second_page = ? LIMIT 1');
            $stmt->execute([$nav_id, $page]);
            $row = $stmt->fetch();
            if ($row) {
                $pdo->prepare('UPDATE sys_nav_second SET nav_second_desc = ?, nav_second_status = ? WHERE nav_second_id = ?')
                    ->execute([$desc, $status, $row['nav_second_id']]);
                send_json(true, ['nav_second_id' => $row['nav_second_id'], 'updated' => true]);
            } else {
                $pdo->prepare('INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status) VALUES (?, ?, ?, ?)')
                    ->execute([$nav_id, $desc, $page, $status]);
                $id = $pdo->lastInsertId();
                send_json(true, ['nav_second_id' => $id, 'created' => true]);
            }
        }
        case 'update_submenu': {
            $id = intval($_POST['nav_second_id'] ?? 0);
            if ($id <= 0) send_json(false, null, 'nav_second_id required');
            $desc = isset($_POST['nav_second_desc']) ? trim($_POST['nav_second_desc']) : null;
            $page = isset($_POST['nav_second_page']) ? trim($_POST['nav_second_page']) : null;
            $status = isset($_POST['nav_second_status']) ? intval($_POST['nav_second_status']) : null;
            $fields = [];
            $params = [];
            if ($desc !== null && $desc !== '') { $fields[] = 'nav_second_desc = ?'; $params[] = $desc; }
            if ($page !== null && $page !== '') { $fields[] = 'nav_second_page = ?'; $params[] = $page; }
            if ($status !== null) { $fields[] = 'nav_second_status = ?'; $params[] = $status; }
            if (empty($fields)) send_json(false, null, 'No fields to update');
            $params[] = $id;
            $pdo->prepare('UPDATE sys_nav_second SET ' . implode(',', $fields) . ' WHERE nav_second_id = ?')->execute($params);
            send_json(true, ['nav_second_id' => $id]);
        }
        case 'assign_role': {
            $role_id = intval($_POST['role_id'] ?? 0);
            $nav_id = intval($_POST['nav_id'] ?? 0);
            $nav_second_id = intval($_POST['nav_second_id'] ?? 0);
            if ($role_id <= 0 || $nav_id <= 0 || $nav_second_id <= 0) send_json(false, null, 'role_id, nav_id, nav_second_id required');
            // Check existing
            $stmt = $pdo->prepare('SELECT 1 FROM sys_nav_role WHERE role_id = ? AND nav_id = ? AND nav_second_id = ?');
            $stmt->execute([$role_id, $nav_id, $nav_second_id]);
            if ($stmt->fetch()) {
                send_json(true, ['assigned' => false, 'exists' => true]);
            }
            // Determine next turn
            $turn = $pdo->prepare('SELECT COALESCE(MAX(nav_role_turn)+1, 1) AS next_turn FROM sys_nav_role WHERE role_id = ?');
            $turn->execute([$role_id]);
            $next = intval($turn->fetch()['next_turn'] ?? 1);
            $providedTurn = isset($_POST['nav_role_turn']) ? intval($_POST['nav_role_turn']) : $next;
            $pdo->prepare('INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn) VALUES (?, ?, ?, ?)')
                ->execute([$role_id, $nav_id, $nav_second_id, $providedTurn]);
            send_json(true, ['assigned' => true, 'nav_role_turn' => $providedTurn]);
        }
        case 'unassign_role': {
            $role_id = intval($_POST['role_id'] ?? 0);
            $nav_second_id = intval($_POST['nav_second_id'] ?? 0);
            if ($role_id <= 0 || $nav_second_id <= 0) send_json(false, null, 'role_id, nav_second_id required');
            $stmt = $pdo->prepare('DELETE FROM sys_nav_role WHERE role_id = ? AND nav_second_id = ?');
            $stmt->execute([$role_id, $nav_second_id]);
            send_json(true, ['deleted' => $stmt->rowCount()]);
        }
        case 'update_order': {
            $role_id = intval($_POST['role_id'] ?? 0);
            $nav_id = intval($_POST['nav_id'] ?? 0);
            $nav_second_id = intval($_POST['nav_second_id'] ?? 0);
            $turn = intval($_POST['nav_role_turn'] ?? 0);
            if ($role_id <= 0 || $nav_id <= 0 || $nav_second_id <= 0 || $turn <= 0) send_json(false, null, 'role_id, nav_id, nav_second_id, nav_role_turn required');
            $stmt = $pdo->prepare('UPDATE sys_nav_role SET nav_role_turn = ? WHERE role_id = ? AND nav_id = ? AND nav_second_id = ?');
            $stmt->execute([$turn, $role_id, $nav_id, $nav_second_id]);
            send_json(true, ['updated' => $stmt->rowCount()]);
        }
        case 'list_roles': {
            $roles = $pdo->query('SELECT role_id, role_desc, role_status FROM ref_role ORDER BY role_id')->fetchAll();
            send_json(true, ['roles' => $roles]);
        }
        default:
            send_json(false, null, 'Invalid action');
    }
} catch (PDOException $e) {
    send_json(false, null, 'SQL Error: ' . $e->getMessage());
} catch (Exception $e) {
    send_json(false, null, 'Error: ' . $e->getMessage());
}
