<?php
$servername = "db.fr-pari1.bengt.wasmernet.com";
$username = "7239bada7d21800036f6f3e48a3f";
$password = "068b7239-bada-7e94-8000-f5018332d94e";
$dbname = "srms_db1";
$port = 10272;

// Create a new MySQLi connection
try {
    $conn = new mysqli($servername, $username, $password, $dbname, $port);

    // Check connection
    if ($conn->connect_error) {
        throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
    }
} catch (mysqli_sql_exception $e) {
    // This will catch the connection error and provide a more informative message
    die("Connection failed: " . $e->getMessage());
}
?>
