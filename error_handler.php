<?php
// Global error handler for better error management
function handleError($errno, $errstr, $errfile, $errline) {
    $error_message = "Error [$errno]: $errstr in $errfile on line $errline";
    
    // Log error to file
    error_log($error_message, 3, "logs/error.log");
    
    // Don't show detailed errors in production
    if (ini_get('display_errors')) {
        echo "<div class='error-message'>$error_message</div>";
    }
    
    return true;
}

function handleException($exception) {
    $error_message = "Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    
    // Log error
    error_log($error_message, 3, "logs/error.log");
    
    // Show user-friendly message
    echo "<div class='error-message'>An error occurred. Please try again or contact support.</div>";
}

// Set error handlers
set_error_handler('handleError');
set_exception_handler('handleException');

// Create logs directory if it doesn't exist
if (!is_dir('logs')) {
    mkdir('logs', 0755, true);
}

// Database error handler
function handleDatabaseError($conn, $operation = 'database operation') {
    if ($conn->error) {
        $error = "Database error during $operation: " . $conn->error;
        error_log($error, 3, "logs/database.log");
        return ['error' => 'Database operation failed. Please try again.'];
    }
    return null;
}

// Validation functions
function validateInput($data, $type = 'string') {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL) ? $data : false;
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT) ? (int)$data : false;
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT) ? (float)$data : false;
        default:
            return $data;
    }
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
}
?>