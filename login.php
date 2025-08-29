<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT user_id, username, password, fullname, role, school_id, status FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Check if account is active
        if ($row['status'] === 'inactive') {
            $error = "Account has been deactivated";
        } elseif ($password == $row['password']) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['school_id'] = $row['school_id'];
            
            switch ($row['role']) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'principal':
                    header("Location: pdashboard.php");
                    break;
                case 'teacher':
                    header("Location: tdashboard.php");
                    break;
                case 'student':
                    header("Location: sdashboard.php");
                    break;
                default:
                    header("Location: index.php");
                    break;
            }
            exit();
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Invalid username";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; padding-top: 0; }
        .login-container { background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(15px); padding: 3rem; border-radius: 25px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2); max-width: 450px; width: 100%; border: 1px solid rgba(255, 255, 255, 0.2); }
        .logo { text-align: center; font-size: 2.5rem; font-weight: 700; color: white; margin-bottom: 2.5rem; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        .form-control { background: rgba(255, 255, 255, 0.2); color: white; border: 2px solid rgba(255, 255, 255, 0.3); }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.8); }
        .form-control:focus { background: rgba(255, 255, 255, 0.3); border-color: rgba(255, 255, 255, 0.5); }
        .btn { background: linear-gradient(135deg, #3498db, #2980b9); width: 100%; }
        .btn:hover { background: linear-gradient(135deg, #2980b9, #3498db); }
        .error { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .back-link { text-align: center; margin-top: 1.5rem; }
        .back-link a { color: rgba(255, 255, 255, 0.9); text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .back-link a:hover { color: white; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🎓 SRMS Login</div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>