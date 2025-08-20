<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get school ID from request
if (!isset($_GET['school_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'School ID is required']);
    exit();
}

$school_id = $_GET['school_id'];

// Get school information
$schoolQuery = "SELECT * FROM School WHERE school_id = ?";
$stmt = $conn->prepare($schoolQuery);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'School not found']);
    exit();
}

$school = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($school);
?>