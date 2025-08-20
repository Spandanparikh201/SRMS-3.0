<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$school_id = $_GET['id'] ?? '';
if (empty($school_id)) {
    echo "Invalid school ID";
    exit();
}

// Get school details
$schoolQuery = "SELECT * FROM School WHERE school_id = ?";
$stmt = $conn->prepare($schoolQuery);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$result = $stmt->get_result();
$school = $result->fetch_assoc();

if (!$school) {
    echo "School not found";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit School - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
    <style>
        body { padding: 2rem; }
        .form-container { max-width: 500px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="card">
            <h2>Edit School</h2>
            
            <div id="message"></div>
            
            <form id="editSchoolForm">
                <input type="hidden" name="school_id" value="<?php echo $school['school_id']; ?>">
                
                <div class="form-group">
                    <label>School Name</label>
                    <input type="text" name="school_name" class="form-control" value="<?php echo htmlspecialchars($school['school_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>School Address</label>
                    <textarea name="school_address" class="form-control" rows="3" required><?php echo htmlspecialchars($school['school_address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Principal Name</label>
                    <input type="text" name="principal_name" class="form-control" value="<?php echo htmlspecialchars($school['principal_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Principal Username</label>
                    <input type="text" name="principal_username" class="form-control" value="<?php echo htmlspecialchars($school['principal_username'] ?? ''); ?>" readonly>
                    <small class="form-text">Username is auto-generated when principal name is provided</small>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo ($school['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($school['status'] ?? 'active') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Update School</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('editSchoolForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            fetch('school_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('message');
                if (data.success) {
                    messageDiv.innerHTML = '<div class="success">' + data.success + '</div>';
                    setTimeout(() => {
                        if (window.opener) {
                            window.opener.location.reload();
                            window.close();
                        }
                    }, 1500);
                } else {
                    messageDiv.innerHTML = '<div class="error">' + data.error + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('message').innerHTML = '<div class="error">Error updating school</div>';
            });
        });
    </script>
</body>
</html>