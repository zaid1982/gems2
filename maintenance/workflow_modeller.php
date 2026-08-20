<?php

declare(strict_types=1);

require_once __DIR__ . '/_require_auth.php';
require_once __DIR__ . '/../api/class/Constant.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');
header('Content-Type: application/json; charset=utf-8');

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

try {
    $db = workflowModellerDb();
    switch ($action) {
        case 'flows':
            workflowModellerRespond(workflowModellerFlows($db['pdo']) + ['connection' => $db['info']]);
            break;
        case 'graph':
            $flowId = (int) ($_GET['flow_id'] ?? $_POST['flow_id'] ?? 2);
            workflowModellerRespond(workflowModellerGraph($db['pdo'], $flowId) + ['connection' => $db['info']]);
            break;
        default:
            throw new InvalidArgumentException('Invalid action. Use: flows, graph');
    }
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
}

function workflowModellerDb(): array
{
    $host = trim((string) Constant::$dbHost);
    $name = trim((string) Constant::$dbName);
    $user = trim((string) Constant::$dbUserName);
    $pass = (string) Constant::$dbUserPassword;
    $dsn = sprintf('mysql:host=%s;port=3306;dbname=%s;charset=utf8mb4', $host, $name);

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException(
            'Cannot reach MySQL at ' . $host . ' / ' . $name
            . '. This page uses api/class/Constant.php ($dbHost / $dbName). '
            . $e->getMessage()
        );
    }

    return [
        'pdo' => $pdo,
        'info' => [
            'host' => $host,
            'database' => $name,
            'source' => 'api/class/Constant.php',
        ],
    ];
}

function workflowModellerRespond(array $payload): void
{
    echo json_encode(
        ['success' => true, 'timestamp' => date('Y-m-d H:i:s')] + $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}

function workflowModellerTypeLabel(int $type): string
{
    return match ($type) {
        1 => 'Start',
        2 => 'Normal',
        3 => 'End',
        default => 'Type ' . $type,
    };
}

function workflowModellerClaimLabel(int $claimType): string
{
    return match ($claimType) {
        1 => 'No claim',
        2 => 'Must claim',
        3 => 'Assigned user',
        4 => 'Assigned group',
        default => 'Claim ' . $claimType,
    };
}

function workflowModellerAssignTypeLabel(int $type): string
{
    return match ($type) {
        1 => 'Assign to himself',
        2 => 'Assign to user',
        3 => 'Assign to group',
        default => 'Assign type ' . $type,
    };
}

function workflowModellerFlows(PDO $pdo): array
{
    $rows = $pdo->query(
        'SELECT flow_id, flow_desc, flow_due_day, flow_status
         FROM wfl_flow
         ORDER BY flow_id'
    )->fetchAll();

    $flows = [];
    foreach ($rows as $row) {
        $flows[] = [
            'flowId' => (int) $row['flow_id'],
            'flowDesc' => (string) $row['flow_desc'],
            'flowDueDay' => (int) $row['flow_due_day'],
            'flowStatus' => (int) $row['flow_status'],
        ];
    }

    return ['flows' => $flows];
}

function workflowModellerGraph(PDO $pdo, int $flowId): array
{
    if ($flowId <= 0) {
        throw new InvalidArgumentException('flow_id is required');
    }

    $flowStmt = $pdo->prepare(
        'SELECT flow_id, flow_desc, flow_due_day, flow_status
         FROM wfl_flow
         WHERE flow_id = ?'
    );
    $flowStmt->execute([$flowId]);
    $flow = $flowStmt->fetch();
    if (!$flow) {
        throw new InvalidArgumentException('Flow not found: ' . $flowId);
    }

    $cpStmt = $pdo->prepare(
        'SELECT
            c.checkpoint_id,
            c.flow_id,
            c.checkpoint_desc,
            c.checkpoint_type,
            c.checkpoint_claim_type,
            c.checkpoint_due_day,
            c.checkpoint_next,
            c.checkpoint_case_1,
            c.checkpoint_case_2,
            c.checkpoint_case_3,
            c.checkpoint_icon,
            c.role_id,
            c.group_id,
            c.checkpoint_order,
            c.checkpoint_color,
            c.checkpoint_skip,
            r.role_desc,
            g.group_name
         FROM wfl_checkpoint c
         LEFT JOIN ref_role r ON r.role_id = c.role_id
         LEFT JOIN sys_group g ON g.group_id = c.group_id
         WHERE c.flow_id = ?
         ORDER BY c.checkpoint_order, c.checkpoint_id'
    );
    $cpStmt->execute([$flowId]);
    $checkpointRows = $cpStmt->fetchAll();

    $ids = array_map(static fn (array $row): int => (int) $row['checkpoint_id'], $checkpointRows);
    $idSet = array_fill_keys($ids, true);

    $userCounts = [];
    $openCounts = [];
    $assignRows = [];

    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $userStmt = $pdo->prepare(
            "SELECT checkpoint_id, COUNT(DISTINCT user_id) AS user_count
             FROM wfl_checkpoint_user
             WHERE checkpoint_id IN ($placeholders)
             GROUP BY checkpoint_id"
        );
        $userStmt->execute($ids);
        foreach ($userStmt->fetchAll() as $row) {
            $userCounts[(int) $row['checkpoint_id']] = (int) $row['user_count'];
        }

        $openStmt = $pdo->prepare(
            'SELECT t.checkpoint_id, COUNT(*) AS open_count
             FROM wfl_task t
             INNER JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id
             WHERE tr.flow_id = ? AND t.task_current = 1
             GROUP BY t.checkpoint_id'
        );
        $openStmt->execute([$flowId]);
        foreach ($openStmt->fetchAll() as $row) {
            $openCounts[(int) $row['checkpoint_id']] = (int) $row['open_count'];
        }

        $assignStmt = $pdo->prepare(
            "SELECT checkpoint_assign_id, checkpoint_assign_type, checkpoint_id, checkpoint_to
             FROM wfl_checkpoint_assign
             WHERE checkpoint_id IN ($placeholders)
             ORDER BY checkpoint_id, checkpoint_to"
        );
        $assignStmt->execute($ids);
        $assignRows = $assignStmt->fetchAll();
    }

    $checkpoints = [];
    foreach ($checkpointRows as $row) {
        $id = (int) $row['checkpoint_id'];
        $type = (int) $row['checkpoint_type'];
        $claim = (int) $row['checkpoint_claim_type'];
        $checkpoints[] = [
            'checkpointId' => $id,
            'flowId' => (int) $row['flow_id'],
            'name' => (string) $row['checkpoint_desc'],
            'type' => $type,
            'typeLabel' => workflowModellerTypeLabel($type),
            'claimType' => $claim,
            'claimLabel' => workflowModellerClaimLabel($claim),
            'dueDay' => $row['checkpoint_due_day'] === null ? null : (int) $row['checkpoint_due_day'],
            'next' => $row['checkpoint_next'] === null ? null : (int) $row['checkpoint_next'],
            'case1' => $row['checkpoint_case_1'] === null ? null : (int) $row['checkpoint_case_1'],
            'case2' => $row['checkpoint_case_2'] === null ? null : (int) $row['checkpoint_case_2'],
            'case3' => $row['checkpoint_case_3'] === null ? null : (int) $row['checkpoint_case_3'],
            'icon' => (string) ($row['checkpoint_icon'] ?? ''),
            'roleId' => $row['role_id'] === null ? null : (int) $row['role_id'],
            'roleDesc' => (string) ($row['role_desc'] ?? ''),
            'groupId' => $row['group_id'] === null ? null : (int) $row['group_id'],
            'groupName' => (string) ($row['group_name'] ?? ''),
            'order' => (int) $row['checkpoint_order'],
            'color' => (string) ($row['checkpoint_color'] ?? ''),
            'skip' => (int) $row['checkpoint_skip'] === 1,
            'userCount' => $userCounts[$id] ?? 0,
            'openTasks' => $openCounts[$id] ?? 0,
        ];
    }

    $edges = [];
    $addEdge = static function (array &$edges, int $from, ?int $to, string $kind, string $label) use ($idSet): void {
        if ($to === null || $to <= 0 || empty($idSet[$to])) {
            return;
        }
        $edges[] = [
            'id' => $from . '-' . $kind . '-' . $to,
            'from' => $from,
            'to' => $to,
            'kind' => $kind,
            'label' => $label,
        ];
    };

    foreach ($checkpoints as $cp) {
        $addEdge($edges, $cp['checkpointId'], $cp['next'], 'next', 'next');
        $addEdge($edges, $cp['checkpointId'], $cp['case1'], 'case1', 'case 1');
        $addEdge($edges, $cp['checkpointId'], $cp['case2'], 'case2', 'case 2');
        $addEdge($edges, $cp['checkpointId'], $cp['case3'], 'case3', 'case 3');
    }

    $assigns = [];
    foreach ($assignRows as $row) {
        $from = (int) $row['checkpoint_id'];
        $to = (int) $row['checkpoint_to'];
        $type = (int) $row['checkpoint_assign_type'];
        $assigns[] = [
            'assignId' => (int) $row['checkpoint_assign_id'],
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'typeLabel' => workflowModellerAssignTypeLabel($type),
        ];
        if (!empty($idSet[$to])) {
            $edges[] = [
                'id' => $from . '-assign-' . $to . '-' . $row['checkpoint_assign_id'],
                'from' => $from,
                'to' => $to,
                'kind' => 'assign',
                'label' => 'assign',
            ];
        }
    }

    return [
        'flow' => [
            'flowId' => (int) $flow['flow_id'],
            'flowDesc' => (string) $flow['flow_desc'],
            'flowDueDay' => (int) $flow['flow_due_day'],
            'flowStatus' => (int) $flow['flow_status'],
        ],
        'checkpoints' => $checkpoints,
        'edges' => $edges,
        'assigns' => $assigns,
        'readOnly' => true,
    ];
}
