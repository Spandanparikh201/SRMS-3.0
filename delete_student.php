<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$student_id = $_POST['student_id'] ?? '';
$school_id = $_SESSION['school_id'];

if (empty($student_id)) {
    echo json_encode(['error' => 'Student ID is required']);
    exit();
}

// Verify student belongs to the same school
$verifyQuery = "SELECT s.student_id, u.user_id, u.fullname 
                FROM Student s 
                JOIN User u ON s.user_id = u.user_id 
                WHERE s.student_id = ? AND s.school_id = ?";
$stmt = $conn->prepare($verifyQuery);
$stmt->bind_param("ii", $student_id, $school_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['error' => 'Student not found or unauthorized']);
    exit();
}

$student = $result->fetch_assoc();

// Check if student has any results
$resultsQuery = "SELECT COUNT(*) as count FROM Result WHERE student_id = ?";
$stmt = $conn->prepare($resultsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$resultCount = $stmt->get_result()->fetch_assoc()['count'];

if ($resultCount > 0) {
    echo json_encode(['error' => 'Cannot delete student: ' . $resultCount . ' result records exist. Please delete results first or contact administrator.']);
    exit();
}

$conn->begin_transaction();

try {
    // Delete student record first
    $deleteStudentQuery = "DELETE FROM Student WHERE student_id = ?";
    $stmt = $conn->prepare($deleteStudentQuery);
    $stmt->bind_param("i", $student_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete student record');
    }
    
    // Delete user record
    $deleteUserQuery = "DELETE FROM User WHERE user_id = ?";
    $stmt = $conn->prepare($deleteUserQuery);
    $stmt->bind_param("i", $student['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete user record');
    }
    
    $conn->commit();
    echo json_encode(['success' => 'Student "' . $student['fullname'] . '" deleted successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?>