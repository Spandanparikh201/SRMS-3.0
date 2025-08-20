<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit();
}

$class_name = $_GET['class_name'] ?? '';
$division = $_GET['division'] ?? '';
if (!$class_name || !$division) {
    echo "<script>alert('Invalid class'); window.close();</script>";
    exit();
}

// Get class data
$query = "SELECT * FROM Class WHERE class_name = ? AND division = ? AND school_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $class_name, $division, $_SESSION['school_id']);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();

if (!$class) {
    echo "<script>alert('Class not found'); window.close();</script>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Class</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <div class="container">
        <h2>Edit Class</h2>
        <form id="editForm">
            <div class="form-group">
                <label>Class Name</label>
                <input type="text" name="class_name" class="form-control" value="<?= htmlspecialchars($class['class_name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Division</label>
                <input type="text" name="division" class="form-control" value="<?= htmlspecialchars($class['division']) ?>" required>
            </div>
            <input type="hidden" name="class_id" value="<?= $class['class_id'] ?>">
            <button type="submit" class="btn">Update Class</button>
        </form>
    </div>
    <script>
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            fetch('class_actions.php', {
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