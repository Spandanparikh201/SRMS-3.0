<?php
session_start();
require_once 'db_connect.php';

// Data integrity check and repair script
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

$issues = [];
$fixes = [];

// Check 1: Orphaned records
$orphanedUsers = $conn->query("SELECT COUNT(*) as count FROM User u 
                               LEFT JOIN Teacher t ON u.user_id = t.user_id 
                               LEFT JOIN Student s ON u.user_id = s.user_id 
                               WHERE u.role IN ('teacher', 'student') 
                               AND t.user_id IS NULL AND s.user_id IS NULL")->fetch_assoc()['count'];

if ($orphanedUsers > 0) {
    $issues[] = "Found $orphanedUsers orphaned user records";
}

// Check 2: Missing school references
$missingSchools = $conn->query("SELECT COUNT(*) as count FROM User u 
                                LEFT JOIN School s ON u.school_id = s.school_id 
                                WHERE u.school_id IS NOT NULL AND s.school_id IS NULL")->fetch_assoc()['count'];

if ($missingSchools > 0) {
    $issues[] = "Found $missingSchools users with invalid school references";
}

// Check 3: Duplicate usernames
$duplicateUsernames = $conn->query("SELECT username, COUNT(*) as count FROM User 
                                    GROUP BY username HAVING COUNT(*) > 1")->num_rows;

if ($duplicateUsernames > 0) {
    $issues[] = "Found $duplicateUsernames duplicate usernames";
}

// Check 4: Results without proper references
$invalidResults = $conn->query("SELECT COUNT(*) as count FROM examresult r 
                                LEFT JOIN student s ON r.student_id = s.student_id 
                                LEFT JOIN exam e ON r.exam_id = e.exam_id 
                                LEFT JOIN subject sub ON r.subject_id = sub.subject_id 
                                WHERE s.student_id IS NULL OR e.exam_id IS NULL OR sub.subject_id IS NULL")->fetch_assoc()['count'];

if ($invalidResults > 0) {
    $issues[] = "Found $invalidResults result records with invalid references";
}

// Check 5: Teacher assignments without proper references
$invalidAssignments = $conn->query("SELECT COUNT(*) as count FROM Teacher_Class_Subject tcs 
                                    LEFT JOIN Teacher t ON tcs.teacher_id = t.teacher_id 
                                    LEFT JOIN Class c ON tcs.class_id = c.class_id 
                                    LEFT JOIN Subject s ON tcs.subject_id = s.subject_id 
                                    WHERE t.teacher_id IS NULL OR c.class_id IS NULL OR s.subject_id IS NULL")->fetch_assoc()['count'];

if ($invalidAssignments > 0) {
    $issues[] = "Found $invalidAssignments teacher assignments with invalid references";
}

// Auto-fix issues if requested
if (isset($_POST['fix_issues'])) {
    $conn->begin_transaction();
    
    try {
        // Fix 1: Remove orphaned user records
        $conn->query("DELETE u FROM User u 
                      LEFT JOIN Teacher t ON u.user_id = t.user_id 
                      LEFT JOIN Student s ON u.user_id = s.user_id 
                      WHERE u.role IN ('teacher', 'student') 
                      AND t.user_id IS NULL AND s.user_id IS NULL");
        $fixes[] = "Removed orphaned user records";
        
        // Fix 2: Remove invalid results
        $conn->query("DELETE r FROM examresult r 
                      LEFT JOIN student s ON r.student_id = s.student_id 
                      LEFT JOIN exam e ON r.exam_id = e.exam_id 
                      LEFT JOIN subject sub ON r.subject_id = sub.subject_id 
                      WHERE s.student_id IS NULL OR e.exam_id IS NULL OR sub.subject_id IS NULL");
        $fixes[] = "Removed invalid result records";
        
        // Fix 3: Remove invalid teacher assignments
        $conn->query("DELETE tcs FROM Teacher_Class_Subject tcs 
                      LEFT JOIN Teacher t ON tcs.teacher_id = t.teacher_id 
                      LEFT JOIN Class c ON tcs.class_id = c.class_id 
                      LEFT JOIN Subject s ON tcs.subject_id = s.subject_id 
                      WHERE t.teacher_id IS NULL OR c.class_id IS NULL OR s.subject_id IS NULL");
        $fixes[] = "Removed invalid teacher assignments";
        
        $conn->commit();
        $success = "Data integrity issues fixed successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error fixing issues: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Integrity Check - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Data Integrity Check</h1>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Database Health Status</h2>
            
            <?php if (empty($issues)): ?>
                <div class="alert alert-success">✅ No data integrity issues found!</div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h3>Issues Found:</h3>
                    <ul>
                        <?php foreach ($issues as $issue): ?>
                            <li><?php echo $issue; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <form method="post" style="margin-top: 1rem;">
                    <button type="submit" name="fix_issues" class="btn btn-primary" 
                            onclick="return confirm('Are you sure you want to fix these issues? This action cannot be undone.')">
                        🔧 Fix Issues Automatically
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!empty($fixes)): ?>
                <div class="alert alert-success">
                    <h3>Fixes Applied:</h3>
                    <ul>
                        <?php foreach ($fixes as $fix): ?>
                            <li><?php echo $fix; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Database Statistics</h2>
            <div class="stats-grid">
                <?php
                $stats = [
                    'Schools' => $conn->query("SELECT COUNT(*) as count FROM School")->fetch_assoc()['count'],
                    'Users' => $conn->query("SELECT COUNT(*) as count FROM User")->fetch_assoc()['count'],
                    'Teachers' => $conn->query("SELECT COUNT(*) as count FROM Teacher")->fetch_assoc()['count'],
                    'Students' => $conn->query("SELECT COUNT(*) as count FROM Student")->fetch_assoc()['count'],
                    'Classes' => $conn->query("SELECT COUNT(*) as count FROM Class")->fetch_assoc()['count'],
                    'Subjects' => $conn->query("SELECT COUNT(*) as count FROM Subject")->fetch_assoc()['count'],
                    'Results' => $conn->query("SELECT COUNT(*) as count FROM examresult")->fetch_assoc()['count'],
                    'Assignments' => $conn->query("SELECT COUNT(*) as count FROM Teacher_Class_Subject")->fetch_assoc()['count']
                ];
                
                foreach ($stats as $label => $count):
                ?>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $count; ?></div>
                    <div class="stat-label"><?php echo $label; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .stat-item { text-align: center; padding: 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; }
        .stat-value { font-size: 2rem; font-weight: bold; color: #3498db; }
        .stat-label { font-size: 0.9rem; color: #666; margin-top: 0.5rem; }
        .alert { padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</body>
</html>