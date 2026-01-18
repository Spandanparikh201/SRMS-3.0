<?php
// Password hashing utilities
class PasswordSecurity {
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}

// Migration script to hash existing passwords
function migratePasswords($conn) {
    $query = "SELECT user_id, password FROM User WHERE LENGTH(password) < 60";
    $result = $conn->query($query);
    
    $updated = 0;
    while ($row = $result->fetch_assoc()) {
        $hashedPassword = PasswordSecurity::hashPassword($row['password']);
        $updateQuery = "UPDATE User SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $hashedPassword, $row['user_id']);
        $stmt->execute();
        $updated++;
    }
    
    return $updated;
}
?>