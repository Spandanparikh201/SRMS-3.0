<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$type = $_GET['type'] ?? '';
$school_id = $_GET['school_id'] ?? $_SESSION['school_id'];
$class_id = $_GET['class_id'] ?? null;

switch ($type) {
    case 'subjects':
        if ($class_id) {
            $query = "SELECT s.subject_name, AVG(er.marks_obtained / er.total_marks * 100) as avg_percentage 
                      FROM examresult er 
                      JOIN subject s ON er.subject_id = s.subject_id 
                      JOIN student st ON er.student_id = st.student_id 
                      WHERE st.school_id = ? AND st.class_id = ? 
                      GROUP BY s.subject_id 
                      ORDER BY avg_percentage DESC";
        } else {
            $query = "SELECT s.subject_name, AVG(er.marks_obtained / er.total_marks * 100) as avg_percentage 
                      FROM examresult er 
                      JOIN subject s ON er.subject_id = s.subject_id 
                      JOIN student st ON er.student_id = st.student_id 
                      WHERE st.school_id = ? 
                      GROUP BY s.subject_id 
                      ORDER BY avg_percentage DESC";
        }
        break;
        
    case 'classes':
        $query = "SELECT CONCAT(c.class_name, ' ', c.division) as class_name, AVG(er.marks_obtained / er.total_marks * 100) as avg_percentage 
                  FROM examresult er 
                  JOIN student st ON er.student_id = st.student_id 
                  JOIN class c ON st.class_id = c.class_id 
                  WHERE c.school_id = ? 
                  GROUP BY c.class_id 
                  ORDER BY avg_percentage DESC";
        break;
        
    default:
        echo json_encode(['labels' => [], 'values' => []]);
        exit();
}

$stmt = $conn->prepare($query);
if ($type === 'subjects' && $class_id) {
    $stmt->bind_param("ii", $school_id, $class_id);
} else {
    $stmt->bind_param("i", $school_id);
}
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$values = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $type === 'subjects' ? $row['subject_name'] : $row['class_name'];
    $values[] = round($row['avg_percentage'], 1);
}

header('Content-Type: application/json');
echo json_encode(['labels' => $labels, 'values' => $values]);
?>