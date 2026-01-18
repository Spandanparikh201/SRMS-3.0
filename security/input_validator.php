<?php
class InputValidator {
    
    public static function sanitizeString($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9._-]{3,20}$/', $username);
    }
    
    public static function validatePassword($password) {
        return strlen($password) >= 8 && preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password);
    }
    
    public static function validateMarks($marks, $total = 100) {
        return is_numeric($marks) && $marks >= 0 && $marks <= $total;
    }
    
    public static function validateRollNumber($rollNumber) {
        return preg_match('/^[A-Z0-9]{3,15}$/', $rollNumber);
    }
    
    public static function sanitizeFileName($filename) {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }
    
    public static function validateFileUpload($file, $allowedTypes = ['csv', 'xlsx']) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error';
        }
        
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'Invalid file type';
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = 'File too large';
        }
        
        return $errors;
    }
}
?>