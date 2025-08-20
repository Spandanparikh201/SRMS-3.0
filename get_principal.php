<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get user ID from request
if (!isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User ID is required']);
    exit();
}

$user_id = $_GET['user_id'];

// Get principal information
$principalQuery = "SELECT * FROM User WHERE user_id = ? AND role = 'principal'";
$stmt = $conn->prepare($principalQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Principal not found']);
    exit();
}

$principal = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($principal);
?>