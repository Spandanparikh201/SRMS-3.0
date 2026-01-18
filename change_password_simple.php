<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

if ($_POST) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if ($new !== $confirm) {
        $error = 'New passwords do not match';
    } else {
        // Get current password
        $stmt = $conn->prepare("SELECT password FROM user WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($current === $user['password']) {
            // Update password
            $stmt = $conn->prepare("UPDATE user SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $message = 'Password changed successfully!';
            } else {
                $error = 'Update failed';
            }
        } else {
            $error = 'Current password incorrect';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <div class="container">
        <div class="card" style="max-width: 400px; margin: 50px auto;">
            <h2>Change Password</h2>
            
            <?php if ($message): ?>
                <div class="alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn">Change Password</button>
                <a href="<?php echo $_SESSION['role']; ?>dashboard.php" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</body>
</html>