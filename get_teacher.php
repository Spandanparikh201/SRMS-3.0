<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal')) {
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
$school_id = $_SESSION['school_id'];

// If admin, they can access any school's data
$schoolCondition = ($_SESSION['role'] === 'admin') ? "" : "AND t.school_id = " . $school_id;

// Get teacher information
$teacherQuery = "
    SELECT u.user_id, u.username, u.fullname, t.teacher_id, t.school_id
    FROM User u
    JOIN Teacher t ON u.user_id = t.user_id
    WHERE u.user_id = ? $schoolCondition
";
$stmt = $conn->prepare($teacherQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Teacher not found']);
    exit();
}

$teacher = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($teacher);
?>