<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

$action = $_POST['action'] ?? '';
$school_id = $_POST['school_id'] ?? '';

if ($action === 'toggle_status') {
    $conn->begin_transaction();
    
    try {
        // Get current school status
        $stmt = $conn->prepare("SELECT status FROM school WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $school = $stmt->get_result()->fetch_assoc();
        
        $newStatus = $school['status'] === 'active' ? 'inactive' : 'active';
        $userStatus = $newStatus === 'active' ? 'active' : 'inactive';
        
        // Update school status
        $stmt = $conn->prepare("UPDATE school SET status = ? WHERE school_id = ?");
        $stmt->execute([$newStatus, $school_id]);
        
        // Update all users in this school (except admin)
        $stmt = $conn->prepare("UPDATE user SET status = ? WHERE school_id = ? AND role != 'admin'");
        $stmt->execute([$userStatus, $school_id]);
        
        $conn->commit();
        echo json_encode(['success' => true, 'new_status' => $newStatus]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>