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
                 er.marks_obtained, er.total_marks as total_subject_marks
          FROM student s 
          JOIN user u ON s.user_id = u.user_id 
          LEFT JOIN examresult er ON s.student_id = er.student_id 
                                   AND er.subject_id = ?
          WHERE s.class_id = ? 
          ORDER BY s.roll_number";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $subject_id, $class_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

header('Content-Type: application/json');
echo json_encode($students);
?>