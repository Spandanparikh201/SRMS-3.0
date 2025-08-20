<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Handle school update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_school') {
    if (isset($_POST['school_id']) && isset($_POST['school_name']) && isset($_POST['school_address'])) {
        $school_id = $_POST['school_id'];
        $school_name = $_POST['school_name'];
        $school_address = $_POST['school_address'];
        $principal_name = $_POST['principal_name'] ?? 'Not specified';
        $principal_username = $_POST['principal_username'] ?? null;
        $status = $_POST['status'] ?? 'active';
        
        // Update school
        $stmt = $conn->prepare("UPDATE School SET school_name = ?, school_address = ?, principal_name = ?, principal_username = ?, status = ? WHERE school_id = ?");
        $stmt->bind_param("sssssi", $school_name, $school_address, $principal_name, $principal_username, $status, $school_id);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'School updated successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error updating school: ' . $conn->error]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>