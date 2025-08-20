<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';
$school_id = $_SESSION['school_id'];

switch ($action) {
    case 'add':
        $subject_name = $_POST['subject_name'] ?? '';
        
        if (empty($subject_name)) {
            echo json_encode(['error' => 'Subject name is required']);
            exit();
        }
        
        $insertQuery = "INSERT INTO Subject (subject_name, school_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("si", $subject_name, $school_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Subject added successfully']);
        } else {
            echo json_encode(['error' => 'Failed to add subject']);
        }
        break;
        
    case 'update':
        $subject_id = $_POST['subject_id'] ?? '';
        $subject_name = $_POST['subject_name'] ?? '';
        
        if (empty($subject_id) || empty($subject_name)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        $updateQuery = "UPDATE Subject SET subject_name = ? WHERE subject_id = ? AND school_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sii", $subject_name, $subject_id, $school_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Subject updated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to update subject']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>