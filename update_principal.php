<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Handle principal update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_principal') {
    if (isset($_POST['user_id']) && isset($_POST['fullname']) && isset($_POST['username']) && isset($_POST['school_id'])) {
        $user_id = $_POST['user_id'];
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $school_id = $_POST['school_id'];
        $password = $_POST['password'] ?? null;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update user information
            if ($password && !empty($password)) {
                $stmt = $conn->prepare("UPDATE User SET fullname = ?, username = ?, password = ?, school_id = ? WHERE user_id = ? AND role = 'principal'");
                $stmt->bind_param("sssii", $fullname, $username, $password, $school_id, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE User SET fullname = ?, username = ?, school_id = ? WHERE user_id = ? AND role = 'principal'");
                $stmt->bind_param("ssii", $fullname, $username, $school_id, $user_id);
            }
            
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Principal updated successfully']);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error updating principal: ' . $e->getMessage()]);
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