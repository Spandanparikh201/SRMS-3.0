<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$school_id = $_SESSION['school_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'bulk_save') {
    $marks = $input['marks'] ?? [];
    
    if (empty($marks)) {
        echo json_encode(['error' => 'No marks data provided']);
        exit();
    }
    
    $conn->begin_transaction();
    
    try {
        $success_count = 0;
        
        foreach ($marks as $mark) {
            $student_id = $mark['student_id'];
            $class_id = $mark['class_id'];
            $subject_id = $mark['subject_id'];
            $exam_id = $mark['exam_id'];
            $marks_obtained = $mark['marks_obtained'];
            $total_marks = $mark['total_marks'];
            
            // Update exam result (entry should already exist)
            $updateQuery = "UPDATE ExamResult SET marks_obtained = ?, total_marks = ?, updated_at = NOW() WHERE student_id = ? AND subject_id = ? AND exam_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ddiii", $marks_obtained, $total_marks, $student_id, $subject_id, $exam_id);
            
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => "Successfully saved marks for $success_count students"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Failed to save marks: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>