<?php

require_once __DIR__ . '/_require_auth.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'message' => 'API key accepted']);
