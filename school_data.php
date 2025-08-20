<?php
include 'db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch($action) {
    case 'get':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['error' => 'ID is required']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT s.*, u.fullname as principal_name, u.username as principal_username 
                                FROM School s 
                                LEFT JOIN User u ON s.school_id = u.school_id AND u.role = 'principal' 
                                WHERE s.school_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($school = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'school' => $school]);
        } else {
            echo json_encode(['error' => 'School not found']);
        }
        break;
        
    case 'details':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['error' => 'ID is required']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT * FROM school WHERE school_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($school = $result->fetch_assoc()) {
            // Get student count for this school (if students table has school_id column)
            $studentCount = 0;
            $studentResult = $conn->query("SHOW COLUMNS FROM students LIKE 'school_id'");
            if ($studentResult->num_rows > 0) {
                $studentCount = $conn->query("SELECT COUNT(*) as count FROM students WHERE school_id = " . $id)->fetch_assoc()['count'] ?? 0;
            }
            
            $html = "
            <div class='school-info'>
                <div class='info-item'>
                    <div class='info-label'>School ID</div>
                    <div class='info-value'>{$school['school_id']}</div>
                </div>
                <div class='info-item'>
                    <div class='info-label'>School Name</div>
                    <div class='info-value'>" . htmlspecialchars($school['school_name']) . "</div>
                </div>
                <div class='info-item'>
                    <div class='info-label'>Address</div>
                    <div class='info-value'>" . htmlspecialchars($school['school_address']) . "</div>
                </div>
                <div class='info-item'>
                    <div class='info-label'>Principal Name</div>
                    <div class='info-value'>" . htmlspecialchars($school['principal_name'] ?? 'Not specified') . "</div>
                </div>
                <div class='info-item'>
                    <div class='info-label'>Principal Username</div>
                    <div class='info-value'>" . htmlspecialchars($school['principal_username'] ?? 'Not set') . "</div>
                </div>
                <div class='info-item'>
                    <div class='info-label'>Total Students</div>
                    <div class='info-value'>{$studentCount}</div>
                </div>
            </div>";
            
            echo json_encode(['success' => true, 'html' => $html]);
        } else {
            echo json_encode(['error' => 'School not found']);
        }
        break;
        
    case 'update':
        $id = $_POST['school_id'] ?? '';
        $school_name = $_POST['school_name'] ?? '';
        $school_address = $_POST['school_address'] ?? '';
        $principal_name = $_POST['principal_name'] ?? '';
        
        if (empty($id) || empty($school_name) || empty($school_address)) {
            echo json_encode(['error' => 'ID, school name and address are required']);
            exit;
        }
        
        $conn->begin_transaction();
        
        try {
            // Update school
            $stmt = $conn->prepare("UPDATE School SET school_name = ?, school_address = ? WHERE school_id = ?");
            $stmt->bind_param("ssi", $school_name, $school_address, $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update school data');
            }
            
            // Update principal if provided
            if (!empty($principal_name)) {
                // Check if principal exists for this school
                $checkPrincipal = $conn->prepare("SELECT user_id FROM User WHERE school_id = ? AND role = 'principal'");
                $checkPrincipal->bind_param("i", $id);
                $checkPrincipal->execute();
                $principalResult = $checkPrincipal->get_result();
                
                if ($principalResult->num_rows > 0) {
                    // Update existing principal
                    $principal = $principalResult->fetch_assoc();
                    $updatePrincipal = $conn->prepare("UPDATE User SET fullname = ? WHERE user_id = ?");
                    $updatePrincipal->bind_param("si", $principal_name, $principal['user_id']);
                    $updatePrincipal->execute();
                } else {
                    // Create new principal
                    $username = strtolower(str_replace(' ', '', $principal_name)) . '.principal';
                    $password = 'principalpass123';
                    
                    $createPrincipal = $conn->prepare("INSERT INTO User (username, password, fullname, role, school_id) VALUES (?, ?, ?, 'principal', ?)");
                    $createPrincipal->bind_param("sssi", $username, $password, $principal_name, $id);
                    $createPrincipal->execute();
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => 'School data updated successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
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
        $user_id = null;
        
        if (!empty($principal_name)) {
            // Generate username from principal name
            $principal_username = strtolower(str_replace(' ', '', $principal_name)) . rand(100, 999);
            
            // Generate random password
            $principal_password = 'principal' . rand(1000, 9999);
            
            // Check if username already exists
            $checkUser = $conn->prepare("SELECT user_id FROM User WHERE username = ?");
            $checkUser->bind_param("s", $principal_username);
            $checkUser->execute();
            if ($checkUser->get_result()->num_rows > 0) {
                $principal_username = strtolower(str_replace(' ', '', $principal_name)) . rand(1000, 9999);
            }
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert school first
            $stmt = $conn->prepare("INSERT INTO School (school_name, school_address, principal_name, principal_username, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->bind_param("ssss", $school_name, $school_address, $principal_name, $principal_username);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to add school');
            }
            
            $school_id = $conn->insert_id;
            
            // Create principal user account if principal name provided
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
        
    case 'deactivate':
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['error' => 'ID is required']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE school SET status = 'inactive' WHERE school_id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'School deactivated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to deactivate school']);
        }
        break;
        
    case 'activate':
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['error' => 'ID is required']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE school SET status = 'active' WHERE school_id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'School activated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to activate school']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
?>