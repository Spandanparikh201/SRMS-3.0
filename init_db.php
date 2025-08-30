<?php
require_once 'db_connect.php';

$sql = file_get_contents('srms_complete.sql');
$queries = array_filter(array_map('trim', explode(';', $sql)));

foreach ($queries as $query) {
    if (!empty($query)) {
        $conn->query($query);
    }
}

echo "Database initialized!";
?>