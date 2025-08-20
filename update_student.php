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

// Handle student update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_student') {
    if (isset($_POST['user_id']) && isset($_POST['student_id']) && isset($_POST['fullname']) && isset($_POST['username']) && isset($_POST['roll_number']) && isset($_POST['class_id'])) {
        $user_id = $_POST['user_id'];
        $student_id = $_POST['student_id'];
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $roll_number = $_POST['roll_number'];
        $class_id = $_POST['class_id'];
        $password = $_POST['password'] ?? null;
        
        // If principal, check if student belongs to their school
        if ($role === 'principal') {
            $checkQuery = "SELECT s.student_id FROM Student s WHERE s.user_id = ? AND s.school_id = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("ii", $user_id, $school_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'You do not have permission to update this student']);
                exit();
            }
            
            // Also check if the class belongs to their school
            $checkClassQuery = "SELECT c.class_id FROM Class c WHERE c.class_id = ? AND c.school_id = ?";
            $stmt = $conn->prepare($checkClassQuery);
            $stmt->bind_param("ii", $class_id, $school_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid class selection']);
                exit();
            }
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update user information
            if ($password && !empty($password)) {
                $stmt = $conn->prepare("UPDATE User SET fullname = ?, username = ?, password = ? WHERE user_id = ? AND role = 'student'");
                $stmt->bind_param("sssi", $fullname, $username, $password, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE User SET fullname = ?, username = ? WHERE user_id = ? AND role = 'student'");
                $stmt->bind_param("ssi", $fullname, $username, $user_id);
            }
            
            $stmt->execute();
            
            // Update student information
            $stmt = $conn->prepare("UPDATE Student SET roll_number = ?, class_id = ? WHERE student_id = ?");
            $stmt->bind_param("sii", $roll_number, $class_id, $student_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error updating student: ' . $e->getMessage()]);
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