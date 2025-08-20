<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$school_id = $_SESSION['school_id'];
$role = $_SESSION['role'];

// Handle teacher update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_teacher') {
    if (isset($_POST['user_id']) && isset($_POST['fullname']) && isset($_POST['username'])) {
        $user_id = $_POST['user_id'];
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $password = $_POST['password'] ?? null;
        
        // If principal, check if teacher belongs to their school
        if ($role === 'principal') {
            $checkQuery = "SELECT t.teacher_id FROM Teacher t WHERE t.user_id = ? AND t.school_id = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("ii", $user_id, $school_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'You do not have permission to update this teacher']);
                exit();
            }
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update user information
            if ($password && !empty($password)) {
                $stmt = $conn->prepare("UPDATE User SET fullname = ?, username = ?, password = ? WHERE user_id = ? AND role = 'teacher'");
                $stmt->bind_param("sssi", $fullname, $username, $password, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE User SET fullname = ?, username = ? WHERE user_id = ? AND role = 'teacher'");
                $stmt->bind_param("ssi", $fullname, $username, $user_id);
            }
            
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Teacher updated successfully']);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error updating teacher: ' . $e->getMessage()]);
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