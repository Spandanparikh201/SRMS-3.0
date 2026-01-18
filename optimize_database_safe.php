<?php
// Safe Database Optimization Script
require_once 'db_connect.php';

// Create audit_log table first
$auditTable = "CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_role VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$conn->query($auditTable);

echo "<h2>Database Optimization</h2>";
echo "<ul>";
echo "<li>✅ Audit log table created</li>";

// Check and create indexes safely
$indexes = [
    ['name' => 'idx_user_username', 'table' => 'user', 'column' => 'username'],
    ['name' => 'idx_user_role', 'table' => 'user', 'column' => 'role'],
    ['name' => 'idx_user_school_id', 'table' => 'user', 'column' => 'school_id'],
    ['name' => 'idx_student_school_id', 'table' => 'student', 'column' => 'school_id'],
    ['name' => 'idx_student_class_id', 'table' => 'student', 'column' => 'class_id'],
    ['name' => 'idx_teacher_school_id', 'table' => 'teacher', 'column' => 'school_id'],
    ['name' => 'idx_examresult_student_id', 'table' => 'examresult', 'column' => 'student_id'],
    ['name' => 'idx_examresult_exam_id', 'table' => 'examresult', 'column' => 'exam_id'],
    ['name' => 'idx_examresult_subject_id', 'table' => 'examresult', 'column' => 'subject_id'],
    ['name' => 'idx_class_school_id', 'table' => 'class', 'column' => 'school_id'],
    ['name' => 'idx_subject_school_id', 'table' => 'subject', 'column' => 'school_id'],
    ['name' => 'idx_exam_school_id', 'table' => 'exam', 'column' => 'school_id'],
    ['name' => 'idx_audit_log_user_id', 'table' => 'audit_log', 'column' => 'user_id'],
    ['name' => 'idx_audit_log_created_at', 'table' => 'audit_log', 'column' => 'created_at']
];

foreach ($indexes as $index) {
    // Check if index exists
    $checkQuery = "SHOW INDEX FROM {$index['table']} WHERE Key_name = '{$index['name']}'";
    $result = $conn->query($checkQuery);
    
    if ($result && $result->num_rows == 0) {
        // Index doesn't exist, create it
        $createIndex = "CREATE INDEX {$index['name']} ON {$index['table']}({$index['column']})";
        if ($conn->query($createIndex)) {
            echo "<li>✅ Index {$index['name']} created</li>";
        } else {
            echo "<li>❌ Error creating {$index['name']}: " . $conn->error . "</li>";
        }
    } else {
        echo "<li>ℹ️ Index {$index['name']} already exists</li>";
    }
}

echo "</ul>";
echo "<p>✅ Database optimization completed!</p>";
echo "<p>📊 Performance indexes added for faster queries</p>";
echo "<p>🔍 Audit logging system ready</p>";
?>