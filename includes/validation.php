<?php
// Input Validation Functions

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

function validateRequired($value) {
    return !empty(trim($value));
}

function validateNumeric($value) {
    return is_numeric($value);
}

function validateAlphaNumeric($value) {
    return ctype_alnum($value);
}

function sanitizeArray($array) {
    $sanitized = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitizeArray($value);
        } else {
            $sanitized[$key] = sanitizeInput($value);
        }
    }
    return $sanitized;
}
?>