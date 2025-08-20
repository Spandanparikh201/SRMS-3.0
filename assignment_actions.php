<?php
session_start();
require_once 'db_connect.php';
require_once 'error_handler.php';
require_once 'session_check.php';

requireRole(['principal']);
logActivity('assignment_management', 'Accessed assignment actions');

$action = $_POST['action'] ?? '';
$school_id = $_SESSION['school_id'];

switch ($action) {
    case 'create':
        $teacher_id = $_POST['teacher_id'] ?? '';
        $class_id = $_POST['class_id'] ?? '';
        $subject_id = $_POST['subject_id'] ?? '';
        
        if (empty($teacher_id) || empty($class_id) || empty($subject_id)) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }
        
        // Validate teacher belongs to the same school
        $teacherSchoolQuery = "SELECT school_id FROM Teacher WHERE teacher_id = ?";
        $stmt = $conn->prepare($teacherSchoolQuery);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $teacherResult = $stmt->get_result();
        
        if ($teacherResult->num_rows == 0) {
            echo json_encode(['error' => 'Teacher not found']);
            exit();
        }
        
        $teacherSchool = $teacherResult->fetch_assoc()['school_id'];
        if ($teacherSchool != $school_id) {
            echo json_encode(['error' => 'Teacher does not belong to your school']);
            exit();
        }
        
        // Validate class belongs to the same school
        $classSchoolQuery = "SELECT school_id FROM Class WHERE class_id = ?";
        $stmt = $conn->prepare($classSchoolQuery);
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
        
        // Validate subject belongs to the same school
        $subjectSchoolQuery = "SELECT school_id FROM Subject WHERE subject_id = ?";
        $stmt = $conn->prepare($subjectSchoolQuery);
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $subjectResult = $stmt->get_result();
        
        if ($subjectResult->num_rows == 0) {
            echo json_encode(['error' => 'Subject not found']);
            exit();
        }
        
        $subjectSchool = $subjectResult->fetch_assoc()['school_id'];
        if ($subjectSchool != $school_id) {
            echo json_encode(['error' => 'Subject does not belong to your school']);
            exit();
        }
        
        // Check if assignment already exists
        $checkQuery = "SELECT teacher_class_subject_id FROM Teacher_Class_Subject WHERE teacher_id = ? AND class_id = ? AND subject_id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("iii", $teacher_id, $class_id, $subject_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['error' => 'Assignment already exists']);
            exit();
        }
        
        // Create assignment
        $insertQuery = "INSERT INTO Teacher_Class_Subject (teacher_id, class_id, subject_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iii", $teacher_id, $class_id, $subject_id);
        
        if ($stmt->execute()) {
            logActivity('assignment_created', "Teacher ID: $teacher_id, Class ID: $class_id, Subject ID: $subject_id");
            echo json_encode(['success' => 'Assignment created successfully']);
        } else {
            $dbError = handleDatabaseError($conn, 'assignment creation');
            echo json_encode($dbError ?: ['error' => 'Failed to create assignment']);
        }
        break;
        
    case 'delete':
        $assignment_id = $_POST['assignment_id'] ?? '';
        
        if (empty($assignment_id)) {
            echo json_encode(['error' => 'Assignment ID is required']);
            exit();
        }
        
        // Verify assignment belongs to the principal's school
        $verifyQuery = "SELECT tcs.teacher_class_subject_id 
                        FROM Teacher_Class_Subject tcs
                        JOIN Teacher t ON tcs.teacher_id = t.teacher_id
                        WHERE tcs.teacher_class_subject_id = ? AND t.school_id = ?";
        $stmt = $conn->prepare($verifyQuery);
        $stmt->bind_param("ii", $assignment_id, $school_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            echo json_encode(['error' => 'Assignment not found or unauthorized']);
            exit();
        }
        
        // Check if there are any results associated with this assignment
        $resultsQuery = "SELECT COUNT(*) as count FROM Result r
                         JOIN Teacher_Class_Subject tcs ON r.class_id = tcs.class_id AND r.subject_id = tcs.subject_id
                         WHERE tcs.teacher_class_subject_id = ?";
        $stmt = $conn->prepare($resultsQuery);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $resultCount = $stmt->get_result()->fetch_assoc()['count'];
        
        if ($resultCount > 0) {
            echo json_encode(['error' => 'Cannot remove assignment: ' . $resultCount . ' student results exist for this teacher-class-subject combination']);
            exit();
        }
        
        $deleteQuery = "DELETE FROM Teacher_Class_Subject WHERE teacher_class_subject_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $assignment_id);
        
        if ($stmt->execute()) {
            logActivity('assignment_deleted', "Assignment ID: $assignment_id");
            echo json_encode(['success' => 'Assignment removed successfully']);
        } else {
            $dbError = handleDatabaseError($conn, 'assignment deletion');
            echo json_encode($dbError ?: ['error' => 'Failed to remove assignment']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>