<?php
// Audit Trail System

function logActivity($action, $table_name = null, $record_id = null, $details = null) {
    global $conn;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $user_role = $_SESSION['role'] ?? 'guest';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, user_role, action, table_name, record_id, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssssss", $user_id, $user_role, $action, $table_name, $record_id, $details, $ip_address, $user_agent);
    $stmt->execute();
}

function createAuditTable() {
    global $conn;
    
    $sql = "CREATE TABLE IF NOT EXISTS audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_role VARCHAR(50),
        action VARCHAR(100) NOT NULL,
        table_name VARCHAR(100),
        record_id INT,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->query($sql);
}

// Initialize audit table
createAuditTable();
?>