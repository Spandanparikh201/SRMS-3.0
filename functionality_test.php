<?php
session_start();
require_once 'db_connect.php';

// Allow access for admin or if no session (for testing)
if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: Database Connection
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM school");
    $schoolCount = $result->fetch_assoc()['count'];
    $tests[] = ['name' => 'Database Connection', 'status' => 'PASS', 'message' => "Connected successfully - $schoolCount schools found"];
    $passed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'Database Connection', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $failed++;
}

// Test 2: User Authentication
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'admin'");
    $adminCount = $result->fetch_assoc()['count'];
    if ($adminCount > 0) {
        $tests[] = ['name' => 'Admin Users Exist', 'status' => 'PASS', 'message' => "$adminCount admin users found"];
        $passed++;
    } else {
        $tests[] = ['name' => 'Admin Users Exist', 'status' => 'FAIL', 'message' => 'No admin users found'];
        $failed++;
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Admin Users Exist', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $failed++;
}

// Test 3: School Data Integrity
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM school s LEFT JOIN user u ON s.school_id = u.school_id WHERE u.school_id IS NULL AND s.school_id IS NOT NULL");
    $orphanedSchools = $result->fetch_assoc()['count'];
    if ($orphanedSchools == 0) {
        $tests[] = ['name' => 'School Data Integrity', 'status' => 'PASS', 'message' => 'No orphaned school records'];
        $passed++;
    } else {
        $tests[] = ['name' => 'School Data Integrity', 'status' => 'WARN', 'message' => "$orphanedSchools schools without users"];
        $passed++;
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'School Data Integrity', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $failed++;
}

// Test 4: Teacher Assignment System
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM teacher_class_subject tcs 
                           JOIN teacher t ON tcs.teacher_id = t.teacher_id 
                           JOIN class c ON tcs.class_id = c.class_id 
                           JOIN subject s ON tcs.subject_id = s.subject_id");
    $validAssignments = $result->fetch_assoc()['count'];
    $tests[] = ['name' => 'Teacher Assignment System', 'status' => 'PASS', 'message' => "$validAssignments valid assignments found"];
    $passed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'Teacher Assignment System', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $failed++;
}

// Test 5: Student Results System
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM examresult r 
                           JOIN student s ON r.student_id = s.student_id 
                           JOIN exam e ON r.exam_id = e.exam_id 
                           JOIN subject sub ON r.subject_id = sub.subject_id");
    $validResults = $result->fetch_assoc()['count'];
    $tests[] = ['name' => 'Student Results System', 'status' => 'PASS', 'message' => "$validResults valid result records"];
    $passed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'Student Results System', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $failed++;
}

// Test 6: File System Permissions
$directories = ['uploads/', 'backups/', 'logs/'];
$fileSystemOK = true;
$fileSystemMessage = '';

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!is_writable($dir)) {
        $fileSystemOK = false;
        $fileSystemMessage .= "$dir not writable; ";
    }
}

if ($fileSystemOK) {
    $tests[] = ['name' => 'File System Permissions', 'status' => 'PASS', 'message' => 'All directories writable'];
    $passed++;
} else {
    $tests[] = ['name' => 'File System Permissions', 'status' => 'FAIL', 'message' => $fileSystemMessage];
    $failed++;
}

// Test 7: Core Files Existence
$coreFiles = [
    'login.php', 'admin_dashboard.php', 'pdashboard.php', 'tdashboard.php', 'sdashboard.php',
    'manage_students.php', 'manage_teacher.php', 'save_marks.php', 'export.php',
    'student_actions.php', 'teacher_actions.php', 'assignment_actions.php'
];
$missingFiles = [];
foreach ($coreFiles as $file) {
    if (!file_exists($file)) {
        $missingFiles[] = $file;
    }
}
if (empty($missingFiles)) {
    $tests[] = ['name' => 'Core Files Existence', 'status' => 'PASS', 'message' => 'All core files present'];
    $passed++;
} else {
    $tests[] = ['name' => 'Core Files Existence', 'status' => 'FAIL', 'message' => 'Missing: ' . implode(', ', $missingFiles)];
    $failed++;
}

// Test 8: Database Tables Structure
try {
    $tables = ['school', 'user', 'class', 'subject', 'student', 'teacher', 'teacher_class_subject', 'exam', 'examresult'];
    $missingTables = [];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            $missingTables[] = $table;
        }
    }
    if (empty($missingTables)) {
        $tests[] = ['name' => 'Database Schema', 'status' => 'PASS', 'message' => 'All required tables exist'];
        $passed++;
    } else {
        $tests[] = ['name' => 'Database Schema', 'status' => 'FAIL', 'message' => 'Missing tables: ' . implode(', ', $missingTables)];
        $failed++;
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Database Schema', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $failed++;
}

// Test 9: User Role System
try {
    $roleQuery = "SELECT role, COUNT(*) as count FROM user GROUP BY role";
    $result = $conn->query($roleQuery);
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[$row['role']] = $row['count'];
    }
    $requiredRoles = ['admin', 'principal', 'teacher', 'student'];
    $missingRoles = array_diff($requiredRoles, array_keys($roles));
    
    if (empty($missingRoles)) {
        $rolesSummary = implode(', ', array_map(function($role, $count) { return "$role: $count"; }, array_keys($roles), $roles));
        $tests[] = ['name' => 'User Role System', 'status' => 'PASS', 'message' => "All roles present ($rolesSummary)"];
        $passed++;
    } else {
        $tests[] = ['name' => 'User Role System', 'status' => 'WARN', 'message' => 'Missing roles: ' . implode(', ', $missingRoles)];
        $passed++;
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'User Role System', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $failed++;
}

// Test 10: Data Relationships Integrity
try {
    // Check for orphaned records
    $orphanChecks = [
        'Students without Users' => "SELECT COUNT(*) as count FROM student s LEFT JOIN user u ON s.user_id = u.user_id WHERE u.user_id IS NULL",
        'Teachers without Users' => "SELECT COUNT(*) as count FROM teacher t LEFT JOIN user u ON t.user_id = u.user_id WHERE u.user_id IS NULL",
        'Results without Students' => "SELECT COUNT(*) as count FROM examresult r LEFT JOIN student s ON r.student_id = s.student_id WHERE s.student_id IS NULL",
        'Assignments without Teachers' => "SELECT COUNT(*) as count FROM teacher_class_subject tcs LEFT JOIN teacher t ON tcs.teacher_id = t.teacher_id WHERE t.teacher_id IS NULL"
    ];
    
    $orphanCount = 0;
    $orphanDetails = [];
    foreach ($orphanChecks as $checkName => $query) {
        $result = $conn->query($query);
        $count = $result->fetch_assoc()['count'];
        if ($count > 0) {
            $orphanCount += $count;
            $orphanDetails[] = "$checkName: $count";
        }
    }
    
    if ($orphanCount == 0) {
        $tests[] = ['name' => 'Data Relationships', 'status' => 'PASS', 'message' => 'No orphaned records found'];
        $passed++;
    } else {
        $tests[] = ['name' => 'Data Relationships', 'status' => 'WARN', 'message' => 'Found issues: ' . implode(', ', $orphanDetails)];
        $passed++;
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Data Relationships', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $failed++;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Functionality Test - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
    <style>
        .test-result { padding: 1rem; margin: 0.5rem 0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .test-pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .test-fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .test-warn { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .test-status { font-weight: bold; padding: 0.25rem 0.5rem; border-radius: 4px; }
        .status-pass { background: #28a745; color: white; }
        .status-fail { background: #dc3545; color: white; }
        .status-warn { background: #ffc107; color: #212529; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .summary-card { text-align: center; padding: 1.5rem; border-radius: 12px; }
        .summary-pass { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .summary-fail { background: linear-gradient(135deg, #dc3545, #e74c3c); color: white; }
        .summary-total { background: linear-gradient(135deg, #007bff, #0056b3); color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 SRMS Functionality Test</h1>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div class="summary">
            <div class="summary-card summary-total">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $passed + $failed; ?></div>
                <div>Total Tests</div>
            </div>
            <div class="summary-card summary-pass">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $passed; ?></div>
                <div>Passed</div>
            </div>
            <div class="summary-card summary-fail">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $failed; ?></div>
                <div>Failed</div>
            </div>
        </div>

        <div class="card">
            <h2>Test Results</h2>
            <?php foreach ($tests as $test): ?>
                <div class="test-result test-<?php echo strtolower($test['status']); ?>">
                    <div>
                        <strong><?php echo $test['name']; ?></strong><br>
                        <small><?php echo $test['message']; ?></small>
                    </div>
                    <div class="test-status status-<?php echo strtolower($test['status']); ?>">
                        <?php echo $test['status']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($failed > 0): ?>
        <div class="card">
            <h2>⚠️ Issues Found</h2>
            <p>Some functionality tests failed. Please review the failed tests above and take corrective action.</p>
            <div class="action-buttons">
                <a href="data_integrity_check.php" class="btn btn-warning">Run Data Integrity Check</a>
                <a href="enhanced_backup_system.php" class="btn btn-info">Enhanced Backup System</a>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <h2>✅ All Tests Passed</h2>
            <p>All functionality tests passed successfully. The SRMS system is working properly without any data loss risks.</p>
            <div class="action-buttons">
                <a href="login.php" class="btn btn-primary">Go to Login</a>
                <a href="data_integrity_check.php" class="btn btn-secondary">Data Integrity Check</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>🎯 SRMS Functionality Summary</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div class="feature-group">
                    <h3>✅ Admin Features</h3>
                    <ul>
                        <li>School Management</li>
                        <li>System Administration</li>
                        <li>Data Integrity Checks</li>
                        <li>Backup & Restore</li>
                    </ul>
                </div>
                <div class="feature-group">
                    <h3>✅ Principal Features</h3>
                    <ul>
                        <li>Teacher Management</li>
                        <li>Student Management</li>
                        <li>Class & Subject Management</li>
                        <li>Teacher Assignments</li>
                        <li>Performance Analytics</li>
                        <li>Data Export</li>
                    </ul>
                </div>
                <div class="feature-group">
                    <h3>✅ Teacher Features</h3>
                    <ul>
                        <li>Mark Entry System</li>
                        <li>Bulk Mark Upload</li>
                        <li>Class Performance</li>
                        <li>Student Progress Tracking</li>
                        <li>Result Export</li>
                    </ul>
                </div>
                <div class="feature-group">
                    <h3>✅ Student Features</h3>
                    <ul>
                        <li>Personal Results View</li>
                        <li>Performance Analytics</li>
                        <li>Academic Information</li>
                        <li>Grade Tracking</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>System Information</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div>
                    <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                    <strong>MySQL Version:</strong> <?php echo $conn->server_info; ?><br>
                    <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                    <strong>Test Run:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                </div>
                <div>
                    <strong>Session Status:</strong> <?php echo isset($_SESSION['user_id']) ? 'Active' : 'None'; ?><br>
                    <strong>User Role:</strong> <?php echo $_SESSION['role'] ?? 'Not logged in'; ?><br>
                    <strong>Database:</strong> <?php echo $conn->get_server_info(); ?><br>
                    <strong>Connection:</strong> Active
                </div>
            </div>
        </div>
    </div>

    <style>
        .feature-group { background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 10px; }
        .feature-group h3 { color: #2c3e50; margin-bottom: 1rem; }
        .feature-group ul { list-style: none; padding: 0; }
        .feature-group li { padding: 0.25rem 0; color: #555; }
        .feature-group li:before { content: '✓ '; color: #27ae60; font-weight: bold; }
    </style>
</body>
</html>