<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/api/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

\Gfm\Security\MaintenanceGuard::requireKey();

echo json_encode(['success' => true, 'message' => 'API key accepted']);
