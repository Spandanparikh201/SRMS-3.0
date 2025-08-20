<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$class_id = $_GET['class_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$exam_term = $_GET['exam_term'] ?? '';

if (empty($class_id) || empty($subject_id) || empty($exam_term)) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

// Get performance data
$performanceQuery = "SELECT s.roll_number, u.fullname, r.marks_obtained, r.total_subject_marks,
                     ROUND((r.marks_obtained / r.total_subject_marks * 100), 2) as percentage
                     FROM Result r
                     JOIN Student s ON r.student_id = s.student_id
                     JOIN User u ON s.user_id = u.user_id
                     WHERE r.class_id = ? AND r.subject_id = ? AND r.exam_term = ?
                     ORDER BY r.marks_obtained DESC";

$stmt = $conn->prepare($performanceQuery);
$stmt->bind_param("iis", $class_id, $subject_id, $exam_term);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
$total_percentage = 0;
$count = 0;
$highest = 0;
$lowest = 100;

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
    $total_percentage += $row['percentage'];
    $count++;
    
    if ($row['percentage'] > $highest) $highest = $row['percentage'];
    if ($row['percentage'] < $lowest) $lowest = $row['percentage'];
}

$average = $count > 0 ? round($total_percentage / $count, 2) : 0;

$response = [
    'stats' => [
        'total_students' => $count,
        'average_percentage' => $average,
        'highest_score' => $highest,
        'lowest_score' => $count > 0 ? $lowest : 0
    ],
    'students' => $students
];

header('Content-Type: application/json');
echo json_encode($response);
?>