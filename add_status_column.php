<?php
include 'db_connect.php';

// Add status column to school table
$sql = "ALTER TABLE school ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active'";

echo "Adding status column to school table...<br>";

if ($conn->query($sql) === TRUE) {
    echo "✓ Status column added successfully<br>";
} else {
    echo "✗ Error adding status column: " . $conn->error . "<br>";
}

echo "<br>Database update completed!";
$conn->close();
?>