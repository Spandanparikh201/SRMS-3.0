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
        $class_name = $_POST['class_name'] ?? '';
        $division = $_POST['division'] ?? '';
        
        if (empty($class_name) || empty($division)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        $insertQuery = "INSERT INTO Class (class_name, division, school_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssi", $class_name, $division, $school_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Class added successfully']);
        } else {
            echo json_encode(['error' => 'Failed to add class']);
        }
        break;
        
    case 'update':
        $class_id = $_POST['class_id'] ?? '';
        $class_name = $_POST['class_name'] ?? '';
        $division = $_POST['division'] ?? '';
        
        if (empty($class_id) || empty($class_name) || empty($division)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        $updateQuery = "UPDATE Class SET class_name = ?, division = ? WHERE class_id = ? AND school_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssii", $class_name, $division, $class_id, $school_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Class updated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to update class']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>