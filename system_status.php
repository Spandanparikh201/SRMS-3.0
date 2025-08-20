<?php
session_start();
require_once 'db_connect.php';
require_once 'session_check.php';

requireRole(['admin']);

$status = [
    'database' => 'OK',
    'sessions' => 'OK',
    'files' => 'OK',
    'permissions' => 'OK',
    'errors' => []
];

// Check database connection
try {
    $conn->query("SELECT 1");
} catch (Exception $e) {
    $status['database'] = 'ERROR';
    $status['errors'][] = 'Database connection failed';
}

// Check critical files
$criticalFiles = [
    'db_connect.php',
    'login.php',
    'assets/css/iris-design-system.css',
    'js/app.js'
];

foreach ($criticalFiles as $file) {
    if (!file_exists($file)) {
        $status['files'] = 'ERROR';
        $status['errors'][] = "Missing file: $file";
    }
}

// Check directory permissions
$directories = ['uploads/', 'backups/', 'logs/'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!is_writable($dir)) {
        $status['permissions'] = 'ERROR';
        $status['errors'][] = "Directory not writable: $dir";
    }
}

// Check for recent errors
if (file_exists('logs/error.log')) {
    $errorLog = file_get_contents('logs/error.log');
    $recentErrors = array_slice(explode("\n", $errorLog), -10);
    if (count(array_filter($recentErrors)) > 0) {
        $status['errors'] = array_merge($status['errors'], array_filter($recentErrors));
    }
}

header('Content-Type: application/json');
echo json_encode($status);
?>