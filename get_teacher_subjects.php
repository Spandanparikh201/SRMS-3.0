<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$class_id = $_GET['class_id'] ?? '';
$teacher_id = $_GET['teacher_id'] ?? '';

if (empty($class_id) || empty($teacher_id)) {
    echo json_encode([]);
    exit();
}

$subjectsQuery = "SELECT DISTINCT s.subject_id, s.subject_name
                  FROM Teacher_Class_Subject tcs
                  JOIN Subject s ON tcs.subject_id = s.subject_id
                  WHERE tcs.teacher_id = ? AND tcs.class_id = ?
                  ORDER BY s.subject_name";

$stmt = $conn->prepare($subjectsQuery);
$stmt->bind_param("ii", $teacher_id, $class_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

header('Content-Type: application/json');
echo json_encode($subjects);
?>