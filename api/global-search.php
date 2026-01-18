<?php
session_start();
require_once '../db_connect.php';
require_once '../session_check.php';
require_once '../security/input_validator.php';

header('Content-Type: application/json');

if (!validateSession()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$query = InputValidator::sanitizeString($input['query'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];
$searchTerm = "%{$query}%";

try {
    // Search Students
    $studentQuery = "SELECT s.student_id, u.fullname, s.roll_number, c.class_name, c.division, sc.school_name 
                     FROM Student s 
                     JOIN User u ON s.user_id = u.user_id 
                     JOIN Class c ON s.class_id = c.class_id 
                     JOIN School sc ON s.school_id = sc.school_id 
                     WHERE (u.fullname LIKE ? OR s.roll_number LIKE ?) 
                     AND s.school_id = ? 
                     LIMIT 10";
    
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("ssi", $searchTerm, $searchTerm, $_SESSION['school_id']);
    $stmt->execute();
    $studentResults = $stmt->get_result();
    
    while ($row = $studentResults->fetch_assoc()) {
        $results[] = [
            'type' => 'Student',
            'title' => $row['fullname'],
            'subtitle' => "Roll: {$row['roll_number']} | Class: {$row['class_name']}-{$row['division']}",
            'url' => "student_results.php?student_id={$row['student_id']}"
        ];
    }
    
    // Search Teachers (if user has permission)
    if (in_array($_SESSION['role'], ['admin', 'principal'])) {
        $teacherQuery = "SELECT t.teacher_id, u.fullname, sc.school_name 
                         FROM Teacher t 
                         JOIN User u ON t.user_id = u.user_id 
                         JOIN School sc ON t.school_id = sc.school_id 
                         WHERE u.fullname LIKE ? 
                         AND t.school_id = ? 
                         LIMIT 10";
        
        $stmt = $conn->prepare($teacherQuery);
        $stmt->bind_param("si", $searchTerm, $_SESSION['school_id']);
        $stmt->execute();
        $teacherResults = $stmt->get_result();
        
        while ($row = $teacherResults->fetch_assoc()) {
            $results[] = [
                'type' => 'Teacher',
                'title' => $row['fullname'],
                'subtitle' => "Teacher | {$row['school_name']}",
                'url' => "teacher_performance.php?teacher_id={$row['teacher_id']}"
            ];
        }
    }
    
    // Search Classes
    $classQuery = "SELECT c.class_id, c.class_name, c.division, sc.school_name,
                          COUNT(s.student_id) as student_count
                   FROM Class c 
                   JOIN School sc ON c.school_id = sc.school_id 
                   LEFT JOIN Student s ON c.class_id = s.class_id
                   WHERE (c.class_name LIKE ? OR c.division LIKE ?) 
                   AND c.school_id = ? 
                   GROUP BY c.class_id
                   LIMIT 10";
    
    $stmt = $conn->prepare($classQuery);
    $stmt->bind_param("ssi", $searchTerm, $searchTerm, $_SESSION['school_id']);
    $stmt->execute();
    $classResults = $stmt->get_result();
    
    while ($row = $classResults->fetch_assoc()) {
        $results[] = [
            'type' => 'Class',
            'title' => "{$row['class_name']}-{$row['division']}",
            'subtitle' => "{$row['student_count']} students | {$row['school_name']}",
            'url' => "manage_classes.php?class_id={$row['class_id']}"
        ];
    }
    
    // Search Subjects
    $subjectQuery = "SELECT sub.subject_id, sub.subject_name, sc.school_name 
                     FROM Subject sub 
                     JOIN School sc ON sub.school_id = sc.school_id 
                     WHERE sub.subject_name LIKE ? 
                     AND sub.school_id = ? 
                     LIMIT 10";
    
    $stmt = $conn->prepare($subjectQuery);
    $stmt->bind_param("si", $searchTerm, $_SESSION['school_id']);
    $stmt->execute();
    $subjectResults = $stmt->get_result();
    
    while ($row = $subjectResults->fetch_assoc()) {
        $results[] = [
            'type' => 'Subject',
            'title' => $row['subject_name'],
            'subtitle' => "Subject | {$row['school_name']}",
            'url' => "manage_subjects.php?subject_id={$row['subject_id']}"
        ];
    }
    
    logActivity('global_search', "Query: {$query}, Results: " . count($results));
    
} catch (Exception $e) {
    error_log("Global search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
    exit;
}

echo json_encode($results);
?>