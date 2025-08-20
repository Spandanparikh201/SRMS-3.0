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
        
        // Prevent infinite loop
        if ($counter > 9999) {
            throw new Exception('Unable to generate unique username');
        }
    }
    
    return $username;
}

$action = $_POST['action'] ?? '';
$school_id = $_SESSION['school_id'];

switch ($action) {
    case 'add':
        $fullname = $_POST['fullname'] ?? '';
        $password = $_POST['password'] ?? '';
        $roll_number = $_POST['roll_number'] ?? '';
        $class_id = $_POST['class_id'] ?? '';
        
        if (empty($fullname) || empty($password) || empty($roll_number) || empty($class_id)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        // Validate class belongs to the same school
        $classQuery = "SELECT school_id FROM Class WHERE class_id = ?";
        $stmt = $conn->prepare($classQuery);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $classResult = $stmt->get_result();
        
        if ($classResult->num_rows == 0) {
            echo json_encode(['error' => 'Class not found']);
            exit();
        }
        
        $classSchool = $classResult->fetch_assoc()['school_id'];
        if ($classSchool != $school_id) {
            echo json_encode(['error' => 'Class does not belong to your school']);
            exit();
        }
        
        // Check for duplicate roll number in the same class
        $duplicateQuery = "SELECT student_id FROM Student WHERE roll_number = ? AND class_id = ? AND school_id = ?";
        $stmt = $conn->prepare($duplicateQuery);
        $stmt->bind_param("sii", $roll_number, $class_id, $school_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['error' => 'Roll number already exists in this class']);
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
        
        $conn->begin_transaction();
        
        try {
            // Insert user
            $userQuery = "INSERT INTO User (username, password, fullname, role, school_id) VALUES (?, ?, ?, 'student', ?)";
            $stmt = $conn->prepare($userQuery);
            $stmt->bind_param("sssi", $username, $password, $fullname, $school_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create user');
            }
            
            $user_id = $conn->insert_id;
            
            // Insert student
            $studentQuery = "INSERT INTO Student (roll_number, user_id, class_id, school_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($studentQuery);
            $stmt->bind_param("siii", $roll_number, $user_id, $class_id, $school_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create student record');
            }
            
            $conn->commit();
            echo json_encode(['success' => 'Student added successfully', 'username' => $username]);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'bulk_upload':
        if (!isset($_FILES['csv_file'])) {
            echo json_encode(['error' => 'No file uploaded']);
            exit();
        }
        
        $file = $_FILES['csv_file'];
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($file['name']);
        
        if (!move_uploaded_file($file['tmp_name'], $uploadFile)) {
            echo json_encode(['error' => 'Failed to upload file']);
            exit();
        }
        
        // Get school name for username generation
        $schoolQuery = "SELECT school_name FROM School WHERE school_id = ?";
        $stmt = $conn->prepare($schoolQuery);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $schoolResult = $stmt->get_result();
        $school_name = $schoolResult->fetch_assoc()['school_name'];
        
        // Process CSV
        $handle = fopen($uploadFile, 'r');
        $header = fgetcsv($handle); // Skip header row
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        $generated_credentials = [];
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 4) {
                $fullname = $data[0];
                $roll_number = $data[1];
                $class_name = $data[2];
                $division = $data[3];
                $password = $data[4] ?? 'student' . rand(1000, 9999);
                
                // Get class_id
                $classQuery = "SELECT class_id FROM Class WHERE class_name = ? AND division = ? AND school_id = ?";
                $stmt = $conn->prepare($classQuery);
                $stmt->bind_param("ssi", $class_name, $division, $school_id);
                $stmt->execute();
                $classResult = $stmt->get_result();
                
                if ($classResult->num_rows == 0) {
                    $errors[] = "Class '$class_name $division' not found for '$fullname'";
                    $error_count++;
                    continue;
                }
                
                $class_id = $classResult->fetch_assoc()['class_id'];
                
                // Generate username automatically
                $username = generateUsername($fullname, $school_name, $conn);
                
                // Insert user
                $userQuery = "INSERT INTO User (username, password, fullname, role, school_id) VALUES (?, ?, ?, 'student', ?)";
                $stmt = $conn->prepare($userQuery);
                $stmt->bind_param("sssi", $username, $password, $fullname, $school_id);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Insert student
                    $studentQuery = "INSERT INTO Student (roll_number, user_id, class_id, school_id) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($studentQuery);
                    $stmt->bind_param("siii", $roll_number, $user_id, $class_id, $school_id);
                    
                    if ($stmt->execute()) {
                        $success_count++;
                        $generated_credentials[] = ['name' => $fullname, 'username' => $username, 'password' => $password];
                    } else {
                        $errors[] = "Failed to create student record for '$fullname'";
                        $error_count++;
                    }
                } else {
                    $errors[] = "Failed to create user for '$fullname'";
                    $error_count++;
                }
            } else {
                $error_count++;
                $errors[] = "Invalid data format in row";
            }
        }
        
        fclose($handle);
        unlink($uploadFile); // Delete uploaded file
        
        $message = "Uploaded $success_count students successfully.";
        if ($error_count > 0) {
            $message .= " $error_count errors occurred.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5));
            }
        }
        
        $response = ['success' => $message];
        if (!empty($generated_credentials)) {
            $response['credentials'] = $generated_credentials;
        }
        
        echo json_encode($response);
        break;
        
    case 'update':
        $username = $_POST['username'] ?? '';
        $fullname = $_POST['fullname'] ?? '';
        $roll_number = $_POST['roll_number'] ?? '';
        $class_id = $_POST['class_id'] ?? '';
        
        if (empty($username) || empty($fullname) || empty($roll_number) || empty($class_id)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        // Update user
        $userQuery = "UPDATE User SET fullname = ? WHERE username = ? AND school_id = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("ssi", $fullname, $username, $school_id);
        
        if ($stmt->execute()) {
            // Get user_id
            $getUserQuery = "SELECT user_id FROM User WHERE username = ? AND school_id = ?";
            $stmt = $conn->prepare($getUserQuery);
            $stmt->bind_param("si", $username, $school_id);
            $stmt->execute();
            $userResult = $stmt->get_result();
            $user = $userResult->fetch_assoc();
            
            if ($user) {
                // Update student
                $studentQuery = "UPDATE Student SET roll_number = ?, class_id = ? WHERE user_id = ? AND school_id = ?";
                $stmt = $conn->prepare($studentQuery);
                $stmt->bind_param("siii", $roll_number, $class_id, $user['user_id'], $school_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => 'Student updated successfully']);
                } else {
                    echo json_encode(['error' => 'Failed to update student record']);
                }
            } else {
                echo json_encode(['error' => 'User not found']);
            }
        } else {
            echo json_encode(['error' => 'Failed to update user']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>