<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$school_id = $_SESSION['school_id'];
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'create':
        $exam_name = $_POST['exam_name'] ?? '';
        $exam_type = $_POST['exam_type'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $total_marks = $_POST['total_marks'] ?? 100;
        $status = $_POST['status'] ?? 'upcoming';
        
        if (empty($exam_name) || empty($exam_type) || empty($start_date) || empty($end_date)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        // Check for duplicate exam name
        $checkQuery = "SELECT exam_id FROM Exam WHERE exam_name = ? AND school_id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("si", $exam_name, $school_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['error' => 'Exam name already exists']);
            exit();
        }
        
        $insertQuery = "INSERT INTO Exam (exam_name, exam_type, start_date, end_date, total_marks, school_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssssdis", $exam_name, $exam_type, $start_date, $end_date, $total_marks, $school_id, $status);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Exam created successfully']);
        } else {
            echo json_encode(['error' => 'Failed to create exam']);
        }
        break;
        
    case 'update':
        $exam_id = $_POST['exam_id'] ?? '';
        $exam_name = $_POST['exam_name'] ?? '';
        $exam_type = $_POST['exam_type'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $total_marks = $_POST['total_marks'] ?? 100;
        $status = $_POST['status'] ?? 'upcoming';
        
        if (empty($exam_id) || empty($exam_name) || empty($exam_type) || empty($start_date) || empty($end_date)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        // Check for duplicate exam name (excluding current exam)
        $checkQuery = "SELECT exam_id FROM Exam WHERE exam_name = ? AND school_id = ? AND exam_id != ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("sii", $exam_name, $school_id, $exam_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['error' => 'Exam name already exists']);
            exit();
        }
        
        $updateQuery = "UPDATE Exam SET exam_name = ?, exam_type = ?, start_date = ?, end_date = ?, total_marks = ?, status = ? WHERE exam_id = ? AND school_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssdsii", $exam_name, $exam_type, $start_date, $end_date, $total_marks, $status, $exam_id, $school_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Exam updated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to update exam']);
        }
        break;
        
    case 'delete':
        $exam_id = $_POST['exam_id'] ?? '';
        
        if (empty($exam_id)) {
            echo json_encode(['error' => 'Exam ID is required']);
            exit();
        }
        
        $deleteQuery = "DELETE FROM Exam WHERE exam_id = ? AND school_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("ii", $exam_id, $school_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Exam deleted successfully']);
        } else {
            echo json_encode(['error' => 'Failed to delete exam']);
        }
        break;
        
    case 'get':
        $exam_id = $_GET['id'] ?? '';
        
        if (empty($exam_id)) {
            echo json_encode(['error' => 'Exam ID is required']);
            exit();
        }
        
        $selectQuery = "SELECT * FROM Exam WHERE exam_id = ? AND school_id = ?";
        $stmt = $conn->prepare($selectQuery);
        $stmt->bind_param("ii", $exam_id, $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $exam = $result->fetch_assoc();
            echo json_encode(['success' => true, 'exam' => $exam]);
        } else {
            echo json_encode(['error' => 'Exam not found']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>