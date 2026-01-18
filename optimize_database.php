<?php
// Database Optimization Script
require_once 'db_connect.php';

// Add indexes for better performance
$indexes = [
    "CREATE INDEX idx_user_username ON user(username)",
    "CREATE INDEX idx_user_role ON user(role)",
    "CREATE INDEX idx_user_school_id ON user(school_id)",
    "CREATE INDEX idx_student_school_id ON student(school_id)",
    "CREATE INDEX idx_student_class_id ON student(class_id)",
    "CREATE INDEX idx_teacher_school_id ON teacher(school_id)",
    "CREATE INDEX idx_examresult_student_id ON examresult(student_id)",
    "CREATE INDEX idx_examresult_exam_id ON examresult(exam_id)",
    "CREATE INDEX idx_examresult_subject_id ON examresult(subject_id)",
    "CREATE INDEX idx_class_school_id ON class(school_id)",
    "CREATE INDEX idx_subject_school_id ON subject(school_id)",
    "CREATE INDEX idx_exam_school_id ON exam(school_id)",
    "CREATE INDEX idx_audit_log_user_id ON audit_log(user_id)",
    "CREATE INDEX idx_audit_log_created_at ON audit_log(created_at)"
];

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

foreach ($indexes as $index) {
    $result = @$conn->query($index);
    if ($result) {
        echo "<li>✅ Index created successfully</li>";
    } else {
        if (strpos($conn->error, 'Duplicate key name') !== false) {
            echo "<li>ℹ️ Index already exists</li>";
        } else {
            echo "<li>❌ Error: " . $conn->error . "</li>";
        }
    }
}

echo "</ul>";
echo "<p>✅ Database optimization completed!</p>";
echo "<p>📊 Performance indexes added for faster queries</p>";
echo "<p>🔍 Audit logging system ready</p>";
?>