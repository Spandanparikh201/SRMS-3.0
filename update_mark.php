<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';
$teacher_id = $_SESSION['teacher_id'] ?? null;

// Get teacher_id if not in session
if (!$teacher_id) {
    $teacherQuery = "SELECT teacher_id FROM Teacher WHERE user_id = ?";
    $stmt = $conn->prepare($teacherQuery);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $teacher_id = $result->fetch_assoc()['teacher_id'];
        $_SESSION['teacher_id'] = $teacher_id;
    }
}

switch ($action) {
    case 'save':
        $student_id = $_POST['student_id'] ?? '';
        $class_id = $_POST['class_id'] ?? '';
        $subject_id = $_POST['subject_id'] ?? '';
        $exam_term = $_POST['exam_term'] ?? '';
        $exam_id = $_POST['exam_id'] ?? null;
        $marks_obtained = $_POST['marks_obtained'] ?? '';
        $total_marks = $_POST['total_marks'] ?? '';
        
        if (empty($student_id) || empty($class_id) || empty($subject_id) || empty($exam_term) || empty($marks_obtained) || empty($total_marks)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        // Check if result already exists
        if ($exam_id) {
            $checkQuery = "SELECT result_id FROM Result WHERE student_id = ? AND class_id = ? AND subject_id = ? AND exam_id = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("iiii", $student_id, $class_id, $subject_id, $exam_id);
        } else {
            $checkQuery = "SELECT result_id FROM Result WHERE student_id = ? AND class_id = ? AND subject_id = ? AND exam_term = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("iiis", $student_id, $class_id, $subject_id, $exam_term);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing result
            if ($exam_id) {
                $updateQuery = "UPDATE Result SET marks_obtained = ?, total_subject_marks = ?, recorded_by_teacher_id = ?, recorded_at = NOW() WHERE student_id = ? AND class_id = ? AND subject_id = ? AND exam_id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("ddiiiii", $marks_obtained, $total_marks, $teacher_id, $student_id, $class_id, $subject_id, $exam_id);
            } else {
                $updateQuery = "UPDATE Result SET marks_obtained = ?, total_subject_marks = ?, recorded_by_teacher_id = ?, recorded_at = NOW() WHERE student_id = ? AND class_id = ? AND subject_id = ? AND exam_term = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("ddiiiiis", $marks_obtained, $total_marks, $teacher_id, $student_id, $class_id, $subject_id, $exam_term);
            }
        } else {
            // Insert new result
            if ($exam_id) {
                $insertQuery = "INSERT INTO Result (student_id, class_id, subject_id, exam_id, exam_term, marks_obtained, total_subject_marks, recorded_by_teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("iiiisddi", $student_id, $class_id, $subject_id, $exam_id, $exam_term, $marks_obtained, $total_marks, $teacher_id);
            } else {
                $insertQuery = "INSERT INTO Result (student_id, class_id, subject_id, exam_term, marks_obtained, total_subject_marks, recorded_by_teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("iiisddi", $student_id, $class_id, $subject_id, $exam_term, $marks_obtained, $total_marks, $teacher_id);
            }
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Marks saved successfully']);
        } else {
            echo json_encode(['error' => 'Failed to save marks']);
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
        
        // Process CSV
        $handle = fopen($uploadFile, 'r');
        $header = fgetcsv($handle); // Skip header row
        $success_count = 0;
        $error_count = 0;
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 6) {
                $student_id = $data[0];
                $class_id = $data[1];
                $subject_id = $data[2];
                $exam_term = $data[3];
                $marks_obtained = $data[4];
                $total_marks = $data[5];
                
                // Insert or update result
                $insertQuery = "INSERT INTO Result (student_id, class_id, subject_id, exam_term, marks_obtained, total_subject_marks, recorded_by_teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), total_subject_marks = VALUES(total_subject_marks), recorded_by_teacher_id = VALUES(recorded_by_teacher_id), recorded_at = NOW()";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("iiisddi", $student_id, $class_id, $subject_id, $exam_term, $marks_obtained, $total_marks, $teacher_id);
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        fclose($handle);
        unlink($uploadFile); // Delete uploaded file
        
        echo json_encode(['success' => "Uploaded $success_count records successfully. $error_count errors."]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>