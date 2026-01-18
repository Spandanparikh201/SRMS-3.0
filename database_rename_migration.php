<?php
// Database Table Rename Migration Script
require_once 'db_connect.php';

echo "<h2>Database Table Rename Migration</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

try {
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Rename tables to match SRS standards
    $renames = [
        'School' => 'schools',
        'User' => 'users',
        'Class' => 'classes',
        'Subject' => 'subjects',
        'Student' => 'students',
        'Teacher' => 'teachers',
        'Teacher_Class_Subject' => 'teacher_class_subjects',
        'Result' => 'results',
        'audit_log' => 'audit_logs'
    ];
    
    foreach ($renames as $oldName => $newName) {
        $checkQuery = "SHOW TABLES LIKE '$oldName'";
        $result = $conn->query($checkQuery);
        
        if ($result->num_rows > 0) {
            $renameQuery = "RENAME TABLE `$oldName` TO `$newName`";
            if ($conn->query($renameQuery)) {
                echo "<p>✅ Renamed table: $oldName → $newName</p>";
            } else {
                echo "<p>❌ Failed to rename: $oldName → $newName</p>";
            }
        } else {
            echo "<p>⚠️ Table $oldName not found, skipping...</p>";
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<h3>✅ Table Rename Migration Completed!</h3>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
echo "<p><strong>Please update all PHP files to use new table names and delete this migration file.</strong></p>";
?>