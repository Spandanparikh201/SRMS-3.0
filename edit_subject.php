<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit();
}

$subject_id = $_GET['subject_id'] ?? '';
if (!$subject_id) {
    echo "<script>alert('Invalid subject'); window.close();</script>";
    exit();
}

// Get subject data
$query = "SELECT * FROM Subject WHERE subject_id = ? AND school_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $subject_id, $_SESSION['school_id']);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result->fetch_assoc();

if (!$subject) {
    echo "<script>alert('Subject not found'); window.close();</script>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Subject</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <div class="container">
        <h2>Edit Subject</h2>
        <form id="editForm">
            <div class="form-group">
                <label>Subject Name</label>
                <input type="text" name="subject_name" class="form-control" value="<?= htmlspecialchars($subject['subject_name']) ?>" required>
            </div>
            <input type="hidden" name="subject_id" value="<?= $subject['subject_id'] ?>">
            <button type="submit" class="btn">Update Subject</button>
        </form>
    </div>
    <script>
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            fetch('subject_actions.php', {
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