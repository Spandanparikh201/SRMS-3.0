<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$school_id = $_SESSION['school_id'];
$exam_id = $_GET['exam_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';

if (empty($exam_id) || empty($subject_id)) {
    echo json_encode(['error' => 'Exam ID and Subject ID are required']);
    exit();
}

// Get all students with existing marks for this exam and subject
$studentsQuery = "
    SELECT s.student_id, s.roll_number, u.fullname, c.class_name, c.division, c.class_id,
           er.marks_obtained, er.total_marks
    FROM Student s
    JOIN User u ON s.user_id = u.user_id
    JOIN Class c ON s.class_id = c.class_id
    LEFT JOIN ExamResult er ON s.student_id = er.student_id 
                            AND er.subject_id = ? 
                            AND er.exam_id = ?
    WHERE s.school_id = ?
    ORDER BY c.class_name, c.division, s.roll_number
";

$stmt = $conn->prepare($studentsQuery);
$stmt->bind_param("iii", $subject_id, $exam_id, $school_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode(['success' => true, 'students' => $students]);
?>