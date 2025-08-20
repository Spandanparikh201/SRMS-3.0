<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get teacher information
$teacherQuery = "SELECT * FROM Teacher WHERE user_id = ?";
$stmt = $conn->prepare($teacherQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacherResult = $stmt->get_result();
$teacherInfo = $teacherResult->fetch_assoc();
$teacher_id = $teacherInfo['teacher_id'];

// Get type of data requested
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Return data based on type
if ($type === 'subjects' && isset($_GET['class_id'])) {
    $class_id = $_GET['class_id'];
    
    // Get subjects assigned to this teacher for the selected class
    $subjectsQuery = "
        SELECT s.subject_id, s.subject_name
        FROM Teacher_Class_Subject tcs
        JOIN Subject s ON tcs.subject_id = s.subject_id
        WHERE tcs.teacher_id = ? AND tcs.class_id = ?
        ORDER BY s.subject_name
    ";
    $stmt = $conn->prepare($subjectsQuery);
    $stmt->bind_param("ii", $teacher_id, $class_id);
    $stmt->execute();
    $subjectsResult = $stmt->get_result();
    
    $subjects = [];
    while ($row = $subjectsResult->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($subjects);
} elseif ($type === 'classes') {
    // Get classes assigned to this teacher
    $classesQuery = "
        SELECT DISTINCT c.class_id, c.class_name, c.division
        FROM Teacher_Class_Subject tcs
        JOIN Class c ON tcs.class_id = c.class_id
        WHERE tcs.teacher_id = ?
        ORDER BY c.class_name, c.division
    ";
    $stmt = $conn->prepare($classesQuery);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $classesResult = $stmt->get_result();
    
    $classes = [];
    while ($row = $classesResult->fetch_assoc()) {
        $classes[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($classes);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
}
?>