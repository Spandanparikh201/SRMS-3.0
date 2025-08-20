<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a principal
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.html");
    exit();
}

$school_id = $_SESSION['school_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $subject_name = $_POST['subject_name'];
    $subject_code = $_POST['subject_code'];
    
    // Check if subject belongs to this school
    $checkQuery = "SELECT * FROM Subject WHERE subject_id = ? AND school_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $subject_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = "You don't have permission to update this subject!";
    } else {
        // Update subject
        $stmt = $conn->prepare("UPDATE Subject SET subject_name = ?, subject_code = ? WHERE subject_id = ? AND school_id = ?");
        $stmt->bind_param("ssii", $subject_name, $subject_code, $subject_id, $school_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Subject updated successfully!";
        } else {
            $response['message'] = "Error updating subject: " . $conn->error;
        }
    }
}

// Redirect back to manage subjects page
header("Location: manage_subjects.php");
exit();
?>