<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal' && $_SESSION['role'] !== 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get student ID from request
if (!isset($_GET['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Student ID is required']);
    exit();
}

$student_id = $_GET['student_id'];
$school_id = $_SESSION['school_id'];
$role = $_SESSION['role'];

// Get student information
$studentQuery = "
    SELECT s.student_id, s.roll_number, u.fullname, c.class_name, c.division, s.school_id
    FROM Student s
    JOIN User u ON s.user_id = u.user_id
    JOIN Class c ON s.class_id = c.class_id
    WHERE s.student_id = ?
";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$studentResult = $stmt->get_result();

if ($studentResult->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Student not found']);
    exit();
}

$studentInfo = $studentResult->fetch_assoc();

// Check if user has access to this student's data
if ($role !== 'admin' && $studentInfo['school_id'] != $school_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'You do not have access to this student\'s data']);
    exit();
}

// Get term 1 results
$term1Query = "
    SELECT r.result_id, s.subject_name, r.marks_obtained, r.total_subject_marks,
           (r.marks_obtained / r.total_subject_marks * 100) as percentage
    FROM Result r
    JOIN Subject s ON r.subject_id = s.subject_id
    WHERE r.student_id = ? AND r.exam_term = 'term1'
    ORDER BY s.subject_name
";
$stmt = $conn->prepare($term1Query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$term1Result = $stmt->get_result();

$term1Results = [];
while ($row = $term1Result->fetch_assoc()) {
    $term1Results[] = $row;
}

// Get term 2 results
$term2Query = "
    SELECT r.result_id, s.subject_name, r.marks_obtained, r.total_subject_marks,
           (r.marks_obtained / r.total_subject_marks * 100) as percentage
    FROM Result r
    JOIN Subject s ON r.subject_id = s.subject_id
    WHERE r.student_id = ? AND r.exam_term = 'term2'
    ORDER BY s.subject_name
";
$stmt = $conn->prepare($term2Query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$term2Result = $stmt->get_result();

$term2Results = [];
while ($row = $term2Result->fetch_assoc()) {
    $term2Results[] = $row;
}

// Prepare response
$response = [
    'student_info' => $studentInfo,
    'term1_results' => $term1Results,
    'term2_results' => $term2Results
];

header('Content-Type: application/json');
echo json_encode($response);
?>