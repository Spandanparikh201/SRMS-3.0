<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if user account is active
$userStatusQuery = "SELECT status FROM user WHERE user_id = ?";
$stmt = $conn->prepare($userStatusQuery);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$userStatus = $stmt->get_result()->fetch_assoc();

if ($userStatus['status'] === 'inactive') {
    session_destroy();
    header("Location: login.php?error=account_deactivated");
    exit();
}

// Get schools with principals
$schoolsQuery = "SELECT s.*, u.user_id as principal_user_id, u.fullname as principal_name 
                 FROM School s 
                 LEFT JOIN User u ON s.school_id = u.school_id AND u.role = 'principal' 
                 ORDER BY s.school_name";
$schoolsResult = $conn->query($schoolsQuery);
$schools = [];
while ($row = $schoolsResult->fetch_assoc()) {
    $schools[$row['school_id']] = $row;
}

// Get students count
$studentsQuery = "SELECT COUNT(*) as count FROM Student";
$studentsResult = $conn->query($studentsQuery);
$studentCount = $studentsResult->fetch_assoc()['count'];

// Get teachers count
$teachersQuery = "SELECT COUNT(*) as count FROM Teacher";
$teachersResult = $conn->query($teachersQuery);
$teacherCount = $teachersResult->fetch_assoc()['count'];

// Get classes count
$classesQuery = "SELECT COUNT(*) as count FROM Class";
$classesResult = $conn->query($classesQuery);
$classCount = $classesResult->fetch_assoc()['count'];

$username = $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_school.php">Manage Schools</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Manage schools in the Student Result Management System.</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?php echo $studentCount; ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $teacherCount; ?></div>
                <div class="stat-label">Teachers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($schools); ?></div>
                <div class="stat-label">Schools</div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="add_school.php" class="btn btn-secondary">Add Schools</a>
            <a href="data_integrity_check.php" class="btn btn-info">🔍 Data Integrity Check</a>
            <a href="enhanced_backup_system.php" class="btn btn-warning">💾 Enhanced Backup System</a>
            <a href="auto_backup_scheduler.php" class="btn btn-success">⏰ Auto Backup Scheduler</a>
            <a href="emergency_restore.php" class="btn btn-danger">🚨 Emergency Restore</a>
            <a href="backup_settings.php" class="btn">⚙️ Backup Settings</a>
            <a href="student_pass_fail_report.php" class="btn">📊 Pass/Fail Report</a>
            <a href="setup_complete_school_data.php" class="btn btn-success">🏠 Setup School Data</a>
            <a href="functionality_test.php" class="btn btn-info">🧪 Functionality Test</a>
        </div>

        <div class="card">
            <h2>Schools</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>School Name</th>
                        <th>Address</th>
                        <th>Principal</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schools as $school): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                            <td><?php echo htmlspecialchars($school['school_address']); ?></td>
                            <td><?php 
                                if (isset($school['principal_name']) && $school['principal_name']) {
                                    echo "ID: {$school['principal_user_id']} - {$school['principal_name']}";
                                } else {
                                    echo 'Not assigned';
                                }
                            ?></td>
                            <td><span class="status-badge status-<?php echo strtolower($school['status'] ?? 'active'); ?>"><?php echo ucfirst($school['status'] ?? 'active'); ?></span></td>
                            <td>
                                <button onclick="editSchool(<?php echo $school['school_id']; ?>)" class="action-link" style="background:none;border:none;color:#2563eb;cursor:pointer;font-weight:500;">Edit</button>
                                <button onclick="toggleSchoolStatus(<?php echo $school['school_id']; ?>)" class="action-link" style="background:none;border:none;color:#e74c3c;cursor:pointer;font-weight:500;margin-left:10px;"><?php echo $school['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit School</h2>
            <form id="editForm">
                <input type="hidden" id="editId" name="school_id">
                <div class="form-group">
                    <label>School Name</label>
                    <input type="text" id="editName" name="school_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>School Address</label>
                    <textarea id="editAddress" name="school_address" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Principal Name</label>
                    <input type="text" id="editPrincipal" name="principal_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Principal Username</label>
                    <input type="text" id="editUsername" name="principal_username" class="form-control" readonly>
                </div>
                <button type="submit" class="btn">Update School</button>
            </form>
        </div>
    </div>

    <script>
        function editSchool(id) {
            fetch(`school_data.php?action=get&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const school = data.school;
                    document.getElementById('editId').value = school.school_id;
                    document.getElementById('editName').value = school.school_name;
                    document.getElementById('editAddress').value = school.school_address;
                    document.getElementById('editPrincipal').value = school.principal_name || '';
                    document.getElementById('editUsername').value = school.principal_username || '';
                    document.getElementById('editModal').style.display = 'block';
                } else {
                    alert('Failed to load school data');
                }
            });
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            fetch('school_data.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.success);
                    closeModal();
                    location.reload();
                } else {
                    alert(data.error);
                }
            });
        });

        function toggleSchoolStatus(schoolId) {
            if (!confirm('Toggle school status? This will activate/deactivate all users in this school.')) return;
            
            fetch('school_status_manager.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=toggle_status&school_id=${schoolId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ School ${data.new_status === 'active' ? 'activated' : 'deactivated'} successfully!`);
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.error);
                }
            });
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>