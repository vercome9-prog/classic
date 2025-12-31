<?php
require_once __DIR__ . '/api/devices.php';
require_once __DIR__ . '/api/logs.php';
require_once __DIR__ . '/api/commands.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'getDevices') {
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = max(1, min(100, intval($_GET['perPage'] ?? 20)));
        $search = substr(trim($_GET['search'] ?? ''), 0, 255);
        echo json_encode(getDevices($page, $perPage, $search));
        
    } elseif ($action === 'getLogs') {
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = max(1, min(200, intval($_GET['perPage'] ?? 50)));
        $search = substr(trim($_GET['search'] ?? ''), 0, 255);
        $typeFilter = substr(trim($_GET['typeFilter'] ?? ''), 0, 100);
        echo json_encode(getLogs($page, $perPage, $search, $typeFilter));
        
    } elseif ($action === 'getDeviceCount') {
        echo json_encode(getDeviceCount());
        
    } elseif ($action === 'sendCommand') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
            exit;
        }
        $androidIds = $input['androidIds'] ?? [];
        $command = $input['command'] ?? '';
        if (is_array($androidIds) && !empty($command)) {
            $androidIds = array_slice($androidIds, 0, 100);
            $command = substr($command, 0, 10000);
            echo json_encode(sendCommand($androidIds, $command));
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

