<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal')) {
    header("Location: login.php");
    exit();
}

$username = $_GET['username'] ?? '';
if (!$username) {
    echo "<script>alert('Invalid teacher'); window.close();</script>";
    exit();
}

// Get teacher data
$query = "SELECT t.teacher_id, u.fullname, u.username 
          FROM Teacher t 
          JOIN User u ON t.user_id = u.user_id 
          WHERE u.username = ? AND t.school_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $username, $_SESSION['school_id']);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    echo "<script>alert('Teacher not found'); window.close();</script>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Teacher</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <div class="container">
        <h2>Edit Teacher</h2>
        <form id="editForm">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($teacher['fullname']) ?>" required>
            </div>
            <input type="hidden" name="teacher_id" value="<?= $teacher['teacher_id'] ?>">
            <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
            <button type="submit" class="btn">Update Teacher</button>
        </form>
    </div>
    <script>
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            fetch('teacher_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.success);
                    window.opener.location.reload();
                    window.close();
                } else {
                    alert(data.error);
                }
            });
        });
    </script>
</body>
</html>