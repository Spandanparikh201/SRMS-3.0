<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'principal')) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Marks - SRMS</title>    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="tdashboard.php">Dashboard</a></li>
            <li><a href="save_marks.php">Enter Marks</a></li>
            <li><a href="upload_marks.php">Upload Marks</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Upload Marks</h1>
            <p>Bulk upload student marks using CSV files.</p>
        </div>

        <div class="card">
            <h2>Download Template</h2>
            <p>Download the CSV template to format your marks data correctly.</p>
            <a href="templates/marks_template.csv" class="btn btn-secondary" download>Download Template</a>
        </div>

        <div class="card">
            <h2>Upload CSV File</h2>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select CSV File</label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                </div>
                <button type="submit" class="btn">Upload Marks</button>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'bulk_upload');
            
            fetch('update_mark.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.success, 'success');
                    this.reset();
                } else {
                    showNotification(data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Upload failed', 'error');
            });
        });
    </script>
    </div>
</body>
</html>