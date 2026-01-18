<?php
// Database security and optimization
class DatabaseSecurity {
    
    public static function addSecurityIndexes($conn) {
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_user_username ON User(username)",
            "CREATE INDEX IF NOT EXISTS idx_user_school ON User(school_id)",
            "CREATE INDEX IF NOT EXISTS idx_student_roll ON Student(roll_number, class_id)",
            "CREATE INDEX IF NOT EXISTS idx_result_student ON Result(student_id, exam_term)",
            "CREATE INDEX IF NOT EXISTS idx_result_class_subject ON Result(class_id, subject_id)",
            "CREATE INDEX IF NOT EXISTS idx_teacher_school ON Teacher(school_id)",
            "CREATE INDEX IF NOT EXISTS idx_audit_user_time ON audit_log(user_id, timestamp)"
        ];
        
        foreach ($indexes as $index) {
            $conn->query($index);
        }
    }
    
    public static function addSecurityConstraints($conn) {
        // Add status column to User table if not exists
        $conn->query("ALTER TABLE User ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active'");
        
        // Add created_at and updated_at columns
        $conn->query("ALTER TABLE User ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        $conn->query("ALTER TABLE User ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        
        // Add password reset functionality
        $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES User(user_id),
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        )");
    }
    
    public static function cleanupExpiredSessions($conn) {
        // Clean up old audit logs (keep 6 months)
        $conn->query("DELETE FROM audit_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL 6 MONTH)");
        
        // Clean up expired password reset tokens
        $conn->query("DELETE FROM password_resets WHERE expires_at < NOW()");
    }
}

// Prepared statement helper
class SecureQuery {
    
    public static function select($conn, $query, $params = [], $types = '') {
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public static function insert($conn, $query, $params = [], $types = '') {
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $result = $stmt->execute();
        return $result ? $conn->insert_id : false;
    }
    
    public static function update($conn, $query, $params = [], $types = '') {
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $result = $stmt->execute();
        return $result ? $stmt->affected_rows : false;
    }
}
?>