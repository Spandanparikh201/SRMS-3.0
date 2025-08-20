<?php
/**
 * Database Migration Script for School Table
 * This script adds the new columns to existing School tables
 */

require_once 'db_connect.php';

echo "<h2>SRMS Database Migration - School Table Update</h2>\n";
echo "<p>This script will update your School table to include the new columns.</p>\n";

try {
    // Check if columns already exist
    $checkColumns = $conn->query("SHOW COLUMNS FROM School LIKE 'principal_name'");
    
    if ($checkColumns->num_rows == 0) {
        echo "<p>Adding new columns to School table...</p>\n";
        
        // Add principal_name column
        $conn->query("ALTER TABLE School ADD COLUMN principal_name VARCHAR(255) DEFAULT 'Not specified' AFTER school_address");
        echo "<p>✓ Added principal_name column</p>\n";
        
        // Add principal_username column
        $conn->query("ALTER TABLE School ADD COLUMN principal_username VARCHAR(100) DEFAULT NULL AFTER principal_name");
        echo "<p>✓ Added principal_username column</p>\n";
        
        // Add status column
        $conn->query("ALTER TABLE School ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER principal_username");
        echo "<p>✓ Added status column</p>\n";
        
        echo "<p><strong>Migration completed successfully!</strong></p>\n";
        echo "<p>All existing schools have been set to 'active' status with 'Not specified' as principal name.</p>\n";
        
    } else {
        echo "<p><strong>Migration already completed!</strong></p>\n";
        echo "<p>The School table already has the new columns.</p>\n";
    }
    
    // Display current school table structure
    echo "<h3>Current School Table Structure:</h3>\n";
    $structure = $conn->query("DESCRIBE School");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

$conn->close();
?>