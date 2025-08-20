<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a principal
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.html");
    exit();
}

$school_id = $_SESSION['school_id'];

// Handle class update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id']) && isset($_POST['class_name']) && isset($_POST['division'])) {
    $class_id = $_POST['class_id'];
    $class_name = $_POST['class_name'];
    $division = $_POST['division'];
    
    // Check if class belongs to this school
    $checkQuery = "SELECT * FROM Class WHERE class_id = ? AND school_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $class_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "You don't have permission to update this class!";
        header("Location: manage_classes.php");
        exit();
    }
    
    // Check if the new class name and division combination already exists
    $checkDuplicateQuery = "SELECT * FROM Class WHERE class_name = ? AND division = ? AND school_id = ? AND class_id != ?";
    $stmt = $conn->prepare($checkDuplicateQuery);
    $stmt->bind_param("ssii", $class_name, $division, $school_id, $class_id);
    $stmt->execute();
    $duplicateResult = $stmt->get_result();
    
    if ($duplicateResult->num_rows > 0) {
        $_SESSION['error_message'] = "A class with this name and division already exists!";
        header("Location: manage_classes.php");
        exit();
    }
    
    // Update class
    $updateQuery = "UPDATE Class SET class_name = ?, division = ? WHERE class_id = ? AND school_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssii", $class_name, $division, $class_id, $school_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Class updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating class: " . $conn->error;
    }
    
    header("Location: manage_classes.php");
    exit();
} else {
    header("Location: manage_classes.php");
    exit();
}
?>