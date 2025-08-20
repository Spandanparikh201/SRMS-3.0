<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal')) {
    header("Location: login.php");
    exit();
}

$username = $_GET['username'] ?? '';
if (!$username) {
    echo "<script>alert('Invalid student'); window.close();</script>";
    exit();
}

// Get student data
$query = "SELECT s.*, u.fullname, u.username, c.class_name, c.division 
          FROM Student s 
          JOIN User u ON s.user_id = u.user_id 
          JOIN Class c ON s.class_id = c.class_id 
          WHERE u.username = ? AND s.school_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $username, $_SESSION['school_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo "<script>alert('Student not found'); window.close();</script>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <div class="container">
        <h2>Edit Student</h2>
        <form id="editForm">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($student['fullname']) ?>" required>
            </div>
            <div class="form-group">
                <label>Roll Number</label>
                <input type="text" name="roll_number" class="form-control" value="<?= htmlspecialchars($student['roll_number']) ?>" required>
            </div>
            <div class="form-group">
                <label>Class</label>
                <select name="class_id" class="form-control" required>
                    <?php
                    $classQuery = "SELECT class_id, class_name, division FROM Class WHERE school_id = ?";
                    $stmt = $conn->prepare($classQuery);
                    $stmt->bind_param("i", $_SESSION['school_id']);
                    $stmt->execute();
                    $classResult = $stmt->get_result();
                    while ($class = $classResult->fetch_assoc()) {
                        $selected = ($class['class_id'] == $student['class_id']) ? 'selected' : '';
                        echo "<option value='{$class['class_id']}' {$selected}>{$class['class_name']} {$class['division']}</option>";
                    }
                    ?>
                </select>
            </div>
            <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
            <button type="submit" class="btn">Update Student</button>
        </form>
    </div>
    <script>
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            fetch('student_actions.php', {
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