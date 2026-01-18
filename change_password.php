<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Debug: Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'Form submitted - processing...';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $error = 'Data received: ' . (!empty($current_password) ? 'current_ok ' : 'current_empty ') . (!empty($new_password) ? 'new_ok ' : 'new_empty ') . (!empty($confirm_password) ? 'confirm_ok' : 'confirm_empty');
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM user WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check current password (plain text comparison as used in login)
            if ($current_password === $user['password']) {
                // Update password
                $updateStmt = $conn->prepare("UPDATE user SET password = ? WHERE user_id = ?");
                $updateStmt->bind_param("si", $new_password, $_SESSION['user_id']);
                
                if ($updateStmt->execute()) {
                    $message = 'Password changed successfully!';
                } else {
                    $error = 'Failed to update password: ' . $conn->error;
                }
            } else {
                $error = 'Current password is incorrect';
            }
        } else {
            $error = 'User not found';
        }
    }
}

// Get dashboard URL based on role
switch($_SESSION['role']) {
    case 'principal':
        $dashboard_url = 'pdashboard.php';
        break;
    case 'teacher':
        $dashboard_url = 'tdashboard.php';
        break;
    case 'student':
        $dashboard_url = 'sdashboard.php';
        break;
    default:
        $dashboard_url = 'login.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="<?php echo $dashboard_url; ?>">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="card" style="max-width: 500px; margin: 50px auto;">
            <h2>Change Password</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="change_password.php">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
                
                <div class="form-row">
                    <button type="submit" class="btn">Change Password</button>
                    <a href="<?php echo $dashboard_url; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>