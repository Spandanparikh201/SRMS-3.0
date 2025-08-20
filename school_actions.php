<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch($action) {
    case 'add':
        $school_name = $_POST['school_name'] ?? '';
        $school_address = $_POST['school_address'] ?? '';
        $principal_name = $_POST['principal_name'] ?? '';
        
        if (empty($school_name) || empty($school_address)) {
            echo json_encode(['error' => 'School name and address are required']);
            exit;
        }
        
        // Auto-generate principal credentials if principal name is provided
        $principal_username = '';
        $principal_password = '';
        
        if (!empty($principal_name)) {
            $principal_username = strtolower(str_replace(' ', '', $principal_name)) . rand(100, 999);
            $principal_password = 'principal' . rand(1000, 9999);
            
            // Check if username already exists
            $checkUser = $conn->prepare("SELECT user_id FROM User WHERE username = ?");
            $checkUser->bind_param("s", $principal_username);
            $checkUser->execute();
            if ($checkUser->get_result()->num_rows > 0) {
                $principal_username = strtolower(str_replace(' ', '', $principal_name)) . rand(1000, 9999);
            }
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("INSERT INTO School (school_name, school_address, principal_name, principal_username, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->bind_param("ssss", $school_name, $school_address, $principal_name, $principal_username);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to add school');
            }
            
            $school_id = $conn->insert_id;
            
            if (!empty($principal_name)) {
                $userStmt = $conn->prepare("INSERT INTO User (username, password, fullname, role, school_id) VALUES (?, ?, ?, 'principal', ?)");
                $userStmt->bind_param("sssi", $principal_username, $principal_password, $principal_name, $school_id);
                
                if (!$userStmt->execute()) {
                    throw new Exception('Failed to create principal account');
                }
            }
            
            $conn->commit();
            
            $response = ['success' => 'School added successfully'];
            if (!empty($principal_name)) {
                $response['principal_credentials'] = [
                    'username' => $principal_username,
                    'password' => $principal_password
                ];
            }
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'update':
        $id = $_POST['school_id'] ?? '';
        $school_name = $_POST['school_name'] ?? '';
        $school_address = $_POST['school_address'] ?? '';
        
        if (empty($id) || empty($school_name) || empty($school_address)) {
            echo json_encode(['error' => 'ID, school name and address are required']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE School SET school_name = ?, school_address = ? WHERE school_id = ?");
        $stmt->bind_param("ssi", $school_name, $school_address, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'School updated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to update school']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
?>