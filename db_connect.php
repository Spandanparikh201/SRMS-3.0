<?php
$servername = $_ENV['MYSQL_HOST'] ?? "localhost";
$username = $_ENV['MYSQL_USER'] ?? "root";
$password = $_ENV['MYSQL_PASSWORD'] ?? "";
$dbname = $_ENV['MYSQL_DATABASE'] ?? "srms_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>