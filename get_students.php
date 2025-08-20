<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$class_id = $_GET['class_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$exam_term = $_GET['exam_term'] ?? '';

if (empty($class_id)) {
    echo json_encode([]);
    exit();
}

$query = "SELECT s.student_id, s.roll_number, u.fullname,
                 r.marks_obtained, r.total_subject_marks
          FROM Student s 
          JOIN User u ON s.user_id = u.user_id 
          LEFT JOIN Result r ON s.student_id = r.student_id 
                              AND r.class_id = ? 
                              AND r.subject_id = ? 
                              AND r.exam_term = ?
          WHERE s.class_id = ? 
          ORDER BY s.roll_number";

$stmt = $conn->prepare($query);
$stmt->bind_param("iisi", $class_id, $subject_id, $exam_term, $class_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

header('Content-Type: application/json');
echo json_encode($students);
?>