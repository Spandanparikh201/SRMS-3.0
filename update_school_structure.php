<?php
require_once 'db_connect.php';

// Add status column to user table
$result = $conn->query("SHOW COLUMNS FROM user LIKE 'status'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE user ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
    echo "✅ Added status column to user table\n";
} else {
    echo "ℹ️ Status column already exists in user table\n";
}

// Update school table structure - replace principal_name and principal_username with user_id
$result = $conn->query("SHOW COLUMNS FROM school LIKE 'user_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE school DROP COLUMN principal_name");
    $conn->query("ALTER TABLE school DROP COLUMN principal_username");
    $conn->query("ALTER TABLE school ADD COLUMN user_id INT DEFAULT NULL");
    $conn->query("ALTER TABLE school ADD FOREIGN KEY (user_id) REFERENCES user(user_id)");
    echo "✅ Updated school table structure\n";
} else {
    echo "ℹ️ School table already has user_id column\n";
}

// Update all existing users to active status
$conn->query("UPDATE user SET status = 'active' WHERE status IS NULL");

echo "✅ Database structure updated successfully!";
?>