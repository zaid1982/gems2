<?php
require_once __DIR__ . '/_require_auth.php';

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
$orderStep = 10; // Gap between nav_role_turn values for easier manual adjustments

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

$fetchRoleAssignmentsStmt = $pdo->prepare('SELECT nav_id, nav_second_id, nav_role_turn FROM sys_nav_role WHERE role_id = ? ORDER BY nav_role_turn, nav_id, nav_second_id');
$updateRoleAssignmentStmt = $pdo->prepare('UPDATE sys_nav_role SET nav_role_turn = ? WHERE role_id = ? AND nav_id = ? AND nav_second_id = ?');
$updateRoleAssignmentNullStmt = $pdo->prepare('UPDATE sys_nav_role SET nav_role_turn = ? WHERE role_id = ? AND nav_id = ? AND nav_second_id IS NULL');

function fetch_role_assignments(PDOStatement $stmt, int $roleId): array {
    $stmt->execute([$roleId]);
    return $stmt->fetchAll() ?: [];
}

function apply_role_order(PDOStatement $updateStmt, PDOStatement $updateNullStmt, int $roleId, array $rows, int $orderStep): void {
    $index = 0;
    foreach ($rows as $row) {
        $index++;
        $newTurn = $index * $orderStep;
        $navId = intval($row['nav_id']);
        $navSecondId = $row['nav_second_id'];
        if ($navSecondId === null) {
            $updateNullStmt->execute([$newTurn, $roleId, $navId]);
        } else {
            $updateStmt->execute([$newTurn, $roleId, $navId, intval($navSecondId)]);
        }
    }
}

function normalize_role_order(PDOStatement $fetchStmt, PDOStatement $updateStmt, PDOStatement $updateNullStmt, int $roleId, int $orderStep): int {
    $rows = fetch_role_assignments($fetchStmt, $roleId);
    if (empty($rows)) {
        return 0;
    }
    apply_role_order($updateStmt, $updateNullStmt, $roleId, $rows, $orderStep);
    return count($rows);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list_all': {
            // Main menus
            $navs = $pdo->query('SELECT nav_id, nav_desc, nav_icon, IFNULL(nav_status,1) nav_status FROM sys_nav ORDER BY nav_id')->fetchAll();
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
            $icon = isset($_POST['nav_icon']) ? strtolower(preg_replace('/[^a-z0-9-]/', '', trim($_POST['nav_icon']))) : '';
            $icon = $icon === '' ? null : $icon;
            if ($desc === '') send_json(false, null, 'nav_desc required');
            // Idempotent create by desc
            $stmt = $pdo->prepare('SELECT nav_id FROM sys_nav WHERE nav_desc = ? LIMIT 1');
            $stmt->execute([$desc]);
            $row = $stmt->fetch();
            if ($row) {
                // Update status optionally
                $pdo->prepare('UPDATE sys_nav SET nav_status = ?, nav_icon = ? WHERE nav_id = ?')->execute([$status, $icon, $row['nav_id']]);
                send_json(true, ['nav_id' => $row['nav_id'], 'updated' => true]);
            } else {
                $pdo->prepare('INSERT INTO sys_nav (nav_desc, nav_icon, nav_status) VALUES (?, ?, ?)')->execute([$desc, $icon, $status]);
                $id = $pdo->lastInsertId();
                send_json(true, ['nav_id' => $id, 'created' => true]);
            }
        }
        case 'update_nav': {
            $id = intval($_POST['nav_id'] ?? 0);
            $desc = isset($_POST['nav_desc']) ? trim($_POST['nav_desc']) : null;
            $status = isset($_POST['nav_status']) ? intval($_POST['nav_status']) : null;
            $iconRaw = $_POST['nav_icon'] ?? null;
            $icon = null;
            if ($iconRaw !== null) {
                $iconSanitized = strtolower(preg_replace('/[^a-z0-9-]/', '', trim($iconRaw)));
                $icon = $iconSanitized === '' ? null : $iconSanitized;
            }
            if ($id <= 0) send_json(false, null, 'nav_id required');
                $targetSlot = intval($_POST['nav_role_turn'] ?? 0);
                if ($role_id <= 0 || $nav_id <= 0 || $targetSlot <= 0) send_json(false, null, 'role_id, nav_id, nav_role_turn required');
                $assignments = fetch_role_assignments($fetchRoleAssignmentsStmt, $role_id);
                if (empty($assignments)) send_json(false, null, 'No assignments found for role');
                $currentIndex = null;
                foreach ($assignments as $idx => $row) {
                    $rowNavId = intval($row['nav_id']);
                    $rowNavSecond = $row['nav_second_id'];
                    $isMatch = $rowNavId === $nav_id;
                    if ($isMatch) {
                        if ($nav_second_id === null && $rowNavSecond === null) {
                            $currentIndex = $idx;
                            break;
                        }
                        if ($nav_second_id !== null && intval($rowNavSecond) === $nav_second_id) {
                            $currentIndex = $idx;
                            break;
                        }
                    }
                }
                if ($currentIndex === null) send_json(false, null, 'Assignment not found');
                $total = count($assignments);
                $targetSlot = max(1, min($targetSlot, $total));
                $targetIndex = $targetSlot - 1;
                $entry = $assignments[$currentIndex];
                array_splice($assignments, $currentIndex, 1);
                array_splice($assignments, $targetIndex, 0, [$entry]);
                $pdo->beginTransaction();
                try {
                    apply_role_order($updateRoleAssignmentStmt, $updateRoleAssignmentNullStmt, $role_id, $assignments, $orderStep);
                    $pdo->commit();
                } catch (Throwable $tx) {
                    $pdo->rollBack();
                    throw $tx;
                }
                send_json(true, ['role_id' => $role_id, 'position' => $targetSlot]);
            if ($status !== null) { $fields[] = 'nav_status = ?'; $params[] = $status; }
            if ($iconRaw !== null) { $fields[] = 'nav_icon = ?'; $params[] = $icon; }
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
            $navIdRaw = $_POST['nav_id'] ?? null;
            $navId = null;
            if ($navIdRaw !== null) {
                $navCandidate = intval($navIdRaw);
                if ($navCandidate > 0) {
                    $navId = $navCandidate;
                }
            }
            $fields = [];
            $params = [];
            if ($desc !== null && $desc !== '') { $fields[] = 'nav_second_desc = ?'; $params[] = $desc; }
            if ($page !== null && $page !== '') { $fields[] = 'nav_second_page = ?'; $params[] = $page; }
            if ($status !== null) { $fields[] = 'nav_second_status = ?'; $params[] = $status; }
            if ($navId !== null) { $fields[] = 'nav_id = ?'; $params[] = $navId; }
            if (empty($fields)) send_json(false, null, 'No fields to update');
            $params[] = $id;
            $pdo->prepare('UPDATE sys_nav_second SET ' . implode(',', $fields) . ' WHERE nav_second_id = ?')->execute($params);
            send_json(true, ['nav_second_id' => $id]);
        }
        case 'assign_role': {
            $role_id = intval($_POST['role_id'] ?? 0);
            $nav_id = intval($_POST['nav_id'] ?? 0);
            $nav_second_raw = $_POST['nav_second_id'] ?? null;
            $nav_second_id = null;
            if ($nav_second_raw !== null && $nav_second_raw !== '' && strtolower((string)$nav_second_raw) !== 'null') {
                $nav_second_id = intval($nav_second_raw);
                if ($nav_second_id <= 0) {
                    $nav_second_id = null;
                }
            }
            if ($role_id <= 0 || $nav_id <= 0) send_json(false, null, 'role_id, nav_id required');
            // Check existing
            if (is_null($nav_second_id)) {
                $stmt = $pdo->prepare('SELECT 1 FROM sys_nav_role WHERE role_id = ? AND nav_id = ? AND nav_second_id IS NULL');
                $stmt->execute([$role_id, $nav_id]);
            } else {
                $stmt = $pdo->prepare('SELECT 1 FROM sys_nav_role WHERE role_id = ? AND nav_id = ? AND nav_second_id = ?');
                $stmt->execute([$role_id, $nav_id, $nav_second_id]);
            }
            if ($stmt->fetch()) {
                send_json(true, ['assigned' => false, 'exists' => true]);
            }
            // Determine next turn using configured step
            $turn = $pdo->prepare('SELECT COALESCE(MAX(nav_role_turn), 0) AS max_turn FROM sys_nav_role WHERE role_id = ?');
            $turn->execute([$role_id]);
            $maxTurn = intval($turn->fetch()['max_turn'] ?? 0);
            $defaultNext = $maxTurn <= 0 ? $orderStep : (int)(floor($maxTurn / $orderStep) + 1) * $orderStep;
            $requestedTurn = isset($_POST['nav_role_turn']) ? intval($_POST['nav_role_turn']) : 0;
            $providedTurn = $requestedTurn > 0 ? $requestedTurn : $defaultNext;
            if ($providedTurn <= 0) {
                $providedTurn = $orderStep;
            }
            $providedTurn = (int)ceil($providedTurn / $orderStep) * $orderStep;
            $pdo->beginTransaction();
            try {
                if ($providedTurn <= $maxTurn && $maxTurn > 0) {
                    $shiftStmt = $pdo->prepare('UPDATE sys_nav_role SET nav_role_turn = nav_role_turn + ? WHERE role_id = ? AND nav_role_turn >= ?');
                    $shiftStmt->execute([$orderStep, $role_id, $providedTurn]);
                }
                $insertStmt = $pdo->prepare('INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn) VALUES (?, ?, ?, ?)');
                $insertStmt->execute([$role_id, $nav_id, $nav_second_id, $providedTurn]);
                $pdo->commit();
            } catch (Throwable $tx) {
                $pdo->rollBack();
                throw $tx;
            }
            send_json(true, ['assigned' => true, 'nav_role_turn' => $providedTurn]);
        }
        case 'assign_role_multi': {
            $role_ids = json_decode($_POST['role_ids'] ?? '[]', true);
            $nav_id = intval($_POST['nav_id'] ?? 0);
            $nav_second_ids = json_decode($_POST['nav_second_ids'] ?? '[]', true);
            if (!is_array($role_ids) || empty($role_ids) || $nav_id <= 0 || !is_array($nav_second_ids) || empty($nav_second_ids)) {
                send_json(false, null, 'role_ids, nav_id, nav_second_ids required');
            }
            $role_ids = array_values(array_unique(array_filter(array_map('intval', $role_ids))));
            $nav_second_ids = array_values(array_unique(array_filter(array_map('intval', $nav_second_ids))));
            if (empty($role_ids) || empty($nav_second_ids)) {
                send_json(false, null, 'role_ids, nav_id, nav_second_ids required');
            }
            $insertStmt = $pdo->prepare('INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn) VALUES (?, ?, ?, ?)');
            $checkStmt = $pdo->prepare('SELECT 1 FROM sys_nav_role WHERE role_id = ? AND nav_id = ? AND nav_second_id = ?');
            $turnStmt = $pdo->prepare('SELECT COALESCE(MAX(nav_role_turn), 0) AS max_turn FROM sys_nav_role WHERE role_id = ?');
            $created = 0;
            $skipped = 0;
            $pdo->beginTransaction();
            try {
                foreach ($role_ids as $roleId) {
                    $turnStmt->execute([$roleId]);
                    $currentTurn = intval($turnStmt->fetch()['max_turn'] ?? 0);
                    if ($currentTurn > 0) {
                        $currentTurn = (int)ceil($currentTurn / $orderStep) * $orderStep;
                    }
                    foreach ($nav_second_ids as $navSecondId) {
                        $checkStmt->execute([$roleId, $nav_id, $navSecondId]);
                        if ($checkStmt->fetch()) {
                            $skipped++;
                            continue;
                        }
                        $currentTurn += $orderStep;
                        $insertStmt->execute([$roleId, $nav_id, $navSecondId, $currentTurn]);
                        $created++;
                    }
                }
                $pdo->commit();
            } catch (Throwable $tx) {
                $pdo->rollBack();
                throw $tx;
            }
            send_json(true, ['created' => $created, 'skipped' => $skipped]);
        }
        case 'unassign_role': {
            $role_id = intval($_POST['role_id'] ?? 0);
            $nav_id = intval($_POST['nav_id'] ?? 0);
            $nav_second_raw = $_POST['nav_second_id'] ?? null;
            $nav_second_id = null;
            if ($nav_second_raw !== null && $nav_second_raw !== '' && strtolower((string)$nav_second_raw) !== 'null') {
                $nav_second_id = intval($nav_second_raw);
                if ($nav_second_id <= 0) {
                    $nav_second_id = null;
                }
            }
            if ($role_id <= 0) send_json(false, null, 'role_id required');
            $deleted = 0;
            if (is_null($nav_second_id)) {
                if ($nav_id <= 0) send_json(false, null, 'nav_id required for parent assignment removal');
                $stmt = $pdo->prepare('DELETE FROM sys_nav_role WHERE role_id = ? AND nav_id = ? AND nav_second_id IS NULL');
                $stmt->execute([$role_id, $nav_id]);
                $deleted = $stmt->rowCount();
            } else {
                if ($nav_id > 0) {
                    $stmt = $pdo->prepare('DELETE FROM sys_nav_role WHERE role_id = ? AND nav_id = ? AND nav_second_id = ?');
                    $stmt->execute([$role_id, $nav_id, $nav_second_id]);
                } else {
                    // Fallback for legacy callers that omit nav_id
                    $stmt = $pdo->prepare('DELETE FROM sys_nav_role WHERE role_id = ? AND nav_second_id = ?');
                    $stmt->execute([$role_id, $nav_second_id]);
                }
                $deleted = $stmt->rowCount();
            }
            send_json(true, ['deleted' => $deleted]);
        }
        case 'update_order': {
            $role_id = intval($_POST['role_id'] ?? 0);
            $nav_id = intval($_POST['nav_id'] ?? 0);
            $nav_second_raw = $_POST['nav_second_id'] ?? null;
            $nav_second_id = null;
            if ($nav_second_raw !== null && $nav_second_raw !== '' && strtolower((string)$nav_second_raw) !== 'null') {
                $nav_second_id = intval($nav_second_raw);
                if ($nav_second_id <= 0) {
                    $nav_second_id = null;
                }
            }
            $turn = intval($_POST['nav_role_turn'] ?? 0);
            if ($role_id <= 0 || $turn <= 0) send_json(false, null, 'role_id, nav_role_turn required');
            if (is_null($nav_second_id) && $nav_id <= 0) send_json(false, null, 'nav_id required when nav_second_id is null');

            if (is_null($nav_second_id)) {
                $stmt = $pdo->prepare('UPDATE sys_nav_role SET nav_role_turn = ? WHERE role_id = ? AND nav_id = ? AND nav_second_id IS NULL');
                $stmt->execute([$turn, $role_id, $nav_id]);
            } else {
                if ($nav_id > 0) {
                    $stmt = $pdo->prepare('UPDATE sys_nav_role SET nav_role_turn = ? WHERE role_id = ? AND nav_id = ? AND nav_second_id = ?');
                    $stmt->execute([$turn, $role_id, $nav_id, $nav_second_id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE sys_nav_role SET nav_role_turn = ? WHERE role_id = ? AND nav_second_id = ?');
                    $stmt->execute([$turn, $role_id, $nav_second_id]);
                }
            }
            send_json(true, ['updated' => $stmt->rowCount()]);
        }
        case 'list_roles': {
            $roles = $pdo->query('SELECT role_id, role_desc, role_status FROM ref_role ORDER BY role_id')->fetchAll();
            send_json(true, ['roles' => $roles]);
        }
        case 'copy_role_navs': {
            $source_role = intval($_POST['source_role_id'] ?? 0);
            $target_roles = json_decode($_POST['target_role_ids'] ?? '[]', true);
            $mode = strtolower(trim($_POST['mode'] ?? 'merge'));
            if ($source_role <= 0 || !is_array($target_roles) || empty($target_roles)) {
                send_json(false, null, 'source_role_id and target_role_ids required');
            }
            $target_roles = array_values(array_unique(array_filter(array_map('intval', $target_roles))));
            if (empty($target_roles)) {
                send_json(false, null, 'target_role_ids required');
            }
            $stmtSource = $pdo->prepare('SELECT nav_id, nav_second_id, nav_role_turn FROM sys_nav_role WHERE role_id = ? ORDER BY nav_role_turn, nav_id');
            $stmtSource->execute([$source_role]);
            $sourceNavs = $stmtSource->fetchAll();
            if (empty($sourceNavs)) {
                send_json(false, null, 'Source role has no navigation defined');
            }
            $deleteStmt = $pdo->prepare('DELETE FROM sys_nav_role WHERE role_id = ?');
            $checkChildStmt = $pdo->prepare('SELECT 1 FROM sys_nav_role WHERE role_id = ? AND nav_id = ? AND nav_second_id = ?');
            $checkParentStmt = $pdo->prepare('SELECT 1 FROM sys_nav_role WHERE role_id = ? AND nav_id = ? AND nav_second_id IS NULL');
            $turnStmt = $pdo->prepare('SELECT COALESCE(MAX(nav_role_turn), 0) AS max_turn FROM sys_nav_role WHERE role_id = ?');
            $insertStmt = $pdo->prepare('INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn) VALUES (?, ?, ?, ?)');
            $created = 0;
            $skipped = 0;
            $pdo->beginTransaction();
            try {
                foreach ($target_roles as $roleId) {
                    if ($roleId === $source_role) { continue; }
                    $nextTurn = 0;
                    if ($mode === 'replace') {
                        $deleteStmt->execute([$roleId]);
                    } else {
                        $turnStmt->execute([$roleId]);
                        $nextTurn = intval($turnStmt->fetch()['max_turn'] ?? 0);
                    }
                    foreach ($sourceNavs as $row) {
                        $navIdRow = intval($row['nav_id']);
                        $navSecondId = $row['nav_second_id'];
                        $navSecondVal = ($navSecondId === null || strtolower((string)$navSecondId) === 'null') ? null : intval($navSecondId);
                        if ($mode !== 'replace') {
                            if (is_null($navSecondVal)) {
                                $checkParentStmt->execute([$roleId, $navIdRow]);
                                $exists = $checkParentStmt->fetch();
                            } else {
                                $checkChildStmt->execute([$roleId, $navIdRow, $navSecondVal]);
                                $exists = $checkChildStmt->fetch();
                            }
                            if ($exists) {
                                $skipped++;
                                continue;
                            }
                            $nextTurn++;
                            $turnToUse = $nextTurn;
                        } else {
                            $turnToUse = intval($row['nav_role_turn']);
                            if ($turnToUse <= 0) {
                                $nextTurn++;
                                $turnToUse = $nextTurn;
                            }
                        }
                        $insertStmt->execute([$roleId, $navIdRow, $navSecondVal, $turnToUse]);
                        $created++;
                    }
                }
                $pdo->commit();
            } catch (Throwable $tx) {
                $pdo->rollBack();
                throw $tx;
            }
            send_json(true, ['created' => $created, 'skipped' => $skipped, 'mode' => $mode]);
        }
        default:
            send_json(false, null, 'Invalid action');
    }
} catch (PDOException $e) {
    send_json(false, null, 'SQL Error: ' . $e->getMessage());
} catch (Exception $e) {
    send_json(false, null, 'Error: ' . $e->getMessage());
}
