<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit();
}

$exam_id = $_GET['exam_id'] ?? '';
if (!$exam_id) {
    echo "<script>alert('Invalid exam'); window.close();</script>";
    exit();
}

// Get exam data
$query = "SELECT * FROM Exam WHERE exam_id = ? AND school_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $exam_id, $_SESSION['school_id']);
$stmt->execute();
$result = $stmt->get_result();
$exam = $result->fetch_assoc();

if (!$exam) {
    echo "<script>alert('Exam not found'); window.close();</script>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Exam</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <div class="container">
        <h2>Edit Exam</h2>
        <form id="editForm">
            <div class="form-group">
                <label>Exam Name</label>
                <input type="text" name="exam_name" class="form-control" value="<?= htmlspecialchars($exam['exam_name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Exam Date</label>
                <input type="date" name="exam_date" class="form-control" value="<?= htmlspecialchars($exam['exam_date']) ?>" required>
            </div>
            <input type="hidden" name="exam_id" value="<?= $exam['exam_id'] ?>">
            <button type="submit" class="btn">Update Exam</button>
        </form>
    </div>
    <script>
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            fetch('exam_actions.php', {
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