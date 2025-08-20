<?php
// Session validation and security check
function validateSession() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session timeout (2 hours)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

function requireRole($allowedRoles) {
    if (!validateSession()) {
        header("Location: login.php");
        exit();
    }
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: unauthorized.php");
        exit();
    }
}

function requireSchoolAccess($school_id = null) {
    if (!validateSession()) {
        header("Location: login.php");
        exit();
    }
    
    // Admin can access all schools
    if ($_SESSION['role'] === 'admin') {
        return true;
    }
    
    // Other roles must match school_id
    if ($school_id && $_SESSION['school_id'] != $school_id) {
        header("Location: unauthorized.php");
        exit();
    }
    
    return true;
}

function logActivity($action, $details = '') {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) return;
    
    $query = "INSERT INTO Activity_Log (user_id, action, details, timestamp, ip_address) VALUES (?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($query);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->bind_param("isss", $_SESSION['user_id'], $action, $details, $ip);
    $stmt->execute();
}
?>