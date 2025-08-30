<?php
require_once 'db_connect_cloud.php';

// Read and execute SQL file
$sql_file = 'srms_complete.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    
    // Split SQL into individual queries
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if ($conn->query($query) === TRUE) {
                echo "Query executed successfully\n";
            } else {
                echo "Error: " . $conn->error . "\n";
            }
        }
    }
    echo "Database setup completed!\n";
} else {
    echo "SQL file not found!\n";
}

$conn->close();
?>