<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Function to generate username from name and school
function generateUsername($fullname, $school_name, $conn) {
    // Split the full name
    $nameParts = explode(' ', trim($fullname));
    $firstName = $nameParts[0];
    
    // Get school initials (first two letters of each word)
    $schoolWords = explode(' ', $school_name);
    $schoolInitials = '';
    foreach ($schoolWords as $word) {
        if (strlen($word) >= 2) {
            $schoolInitials .= substr($word, 0, 2);
        }
    }
    // Limit to first 2 characters if too long
    $schoolInitials = substr($schoolInitials, 0, 2);
    
    // Create base username: FirstName.SchoolInitials
    $baseUsername = $firstName . '.' . $schoolInitials;
    $username = $baseUsername;
    $counter = 1;
    
    // Check if username exists and add number if needed
    while (true) {
        $checkQuery = "SELECT user_id FROM User WHERE username = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            break;
        }
        
        $username = $baseUsername . $counter;
        $counter++;
    }
    
    return $username;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $fullname = $_POST['fullname'] ?? '';
        $password = $_POST['password'] ?? '';
        $school_id = $_SESSION['school_id'];
        
        if (empty($fullname) || empty($password)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        // Get school name for username generation
        $schoolQuery = "SELECT school_name FROM School WHERE school_id = ?";
        $stmt = $conn->prepare($schoolQuery);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $schoolResult = $stmt->get_result();
        
        if ($schoolResult->num_rows == 0) {
            echo json_encode(['error' => 'School not found']);
            exit();
        }
        
        $school_name = $schoolResult->fetch_assoc()['school_name'];
        
        // Generate username automatically
        $username = generateUsername($fullname, $school_name, $conn);
        
        // Insert user
        $userQuery = "INSERT INTO User (username, password, fullname, role, school_id) VALUES (?, ?, ?, 'teacher', ?)";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("sssi", $username, $password, $fullname, $school_id);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Insert teacher
            $teacherQuery = "INSERT INTO Teacher (user_id, school_id) VALUES (?, ?)";
            $stmt = $conn->prepare($teacherQuery);
            $stmt->bind_param("ii", $user_id, $school_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => 'Teacher added successfully', 'username' => $username]);
            } else {
                echo json_encode(['error' => 'Failed to create teacher record']);
            }
        } else {
            echo json_encode(['error' => 'Failed to create user']);
        }
        break;
        
    case 'update':
        $teacher_id = $_POST['teacher_id'] ?? '';
        $fullname = $_POST['fullname'] ?? '';
        $username = $_POST['username'] ?? '';
        
        if (empty($teacher_id) || empty($fullname) || empty($username)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        // Get user_id from teacher_id
        $getUserQuery = "SELECT user_id FROM Teacher WHERE teacher_id = ?";
        $stmt = $conn->prepare($getUserQuery);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['error' => 'Teacher not found']);
            exit();
        }
        
        $user_id = $result->fetch_assoc()['user_id'];
        
        // Update user
        $updateQuery = "UPDATE User SET fullname = ?, username = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $fullname, $username, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Teacher updated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to update teacher']);
        }
        break;
        
    case 'delete':
        $teacher_id = $_POST['teacher_id'] ?? '';
        
        if (empty($teacher_id)) {
            echo json_encode(['error' => 'Teacher ID is required']);
            exit();
        }
        
        // Get user_id
        $getUserQuery = "SELECT user_id FROM Teacher WHERE teacher_id = ?";
        $stmt = $conn->prepare($getUserQuery);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['error' => 'Teacher not found']);
            exit();
        }
        
        $user_id = $result->fetch_assoc()['user_id'];
        
        // Check if teacher has any assignments
        $assignmentsQuery = "SELECT COUNT(*) as count FROM Teacher_Class_Subject WHERE teacher_id = ?";
        $stmt = $conn->prepare($assignmentsQuery);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $assignmentCount = $stmt->get_result()->fetch_assoc()['count'];
        
        if ($assignmentCount > 0) {
            echo json_encode(['error' => 'Cannot delete teacher: ' . $assignmentCount . ' class assignments exist. Please remove assignments first.']);
            exit();
        }
        
        $conn->begin_transaction();
        
        try {
            // Delete teacher first
            $deleteTeacherQuery = "DELETE FROM Teacher WHERE teacher_id = ?";
            $stmt = $conn->prepare($deleteTeacherQuery);
            $stmt->bind_param("i", $teacher_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete teacher record');
            }
            
            // Delete user
            $deleteUserQuery = "DELETE FROM User WHERE user_id = ?";
            $stmt = $conn->prepare($deleteUserQuery);
            $stmt->bind_param("i", $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete user record');
            }
            
            $conn->commit();
            echo json_encode(['success' => 'Teacher deleted successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>