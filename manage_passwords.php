<?php
require_once 'db_connect.php';
require_once 'includes/csrf.php';
require_once 'includes/validation.php';
require_once 'includes/audit.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'principal'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle password change
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    requireCSRF();
    $target_user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    // Authorization check
    $authorized = false;
    
    if ($_SESSION['role'] === 'admin') {
        $authorized = true;
    } elseif ($_SESSION['role'] === 'principal') {
        // Check if target user is a teacher or student in the same school
        $checkTeacher = $conn->prepare("SELECT t.teacher_id FROM teacher t WHERE t.user_id = ? AND t.school_id = ?");
        $checkTeacher->bind_param("ii", $target_user_id, $_SESSION['school_id']);
        $checkTeacher->execute();
        $isTeacher = $checkTeacher->get_result()->num_rows > 0;
        
        $checkStudent = $conn->prepare("SELECT s.student_id FROM student s WHERE s.user_id = ? AND s.school_id = ?");
        $checkStudent->bind_param("ii", $target_user_id, $_SESSION['school_id']);
        $checkStudent->execute();
        $isStudent = $checkStudent->get_result()->num_rows > 0;
        
        $authorized = $isTeacher || $isStudent;
    }
    
    if ($authorized) {
        $updateStmt = $conn->prepare("UPDATE user SET password = ? WHERE user_id = ?");
        $updateStmt->bind_param("si", $new_password, $target_user_id);
        
        if ($updateStmt->execute()) {
            $message = 'Password changed successfully!';
            logActivity('PASSWORD_CHANGE', 'user', $target_user_id, 'Password changed by ' . $_SESSION['role']);
        } else {
            $error = 'Failed to update password';
            logActivity('PASSWORD_CHANGE_FAIL', 'user', $target_user_id, 'Password change failed');
        }
    } else {
        $error = 'Unauthorized to change this user\'s password';
    }
}

// Get users based on role
if ($_SESSION['role'] === 'admin') {
    $usersQuery = "SELECT u.user_id, u.username, u.fullname, u.role, s.school_name 
                   FROM user u 
                   LEFT JOIN school s ON u.school_id = s.school_id 
                   WHERE u.role IN ('principal', 'teacher', 'student')
                   ORDER BY u.role, u.fullname";
    $usersResult = $conn->query($usersQuery);
} else {
    // Principal can see teachers and students in their school
    $usersQuery = "SELECT u.user_id, u.username, u.fullname, u.role 
                   FROM user u 
                   WHERE u.user_id IN (
                       SELECT t.user_id FROM teacher t WHERE t.school_id = ?
                       UNION
                       SELECT s.user_id FROM student s WHERE s.school_id = ?
                   ) AND u.role IN ('teacher', 'student')
                   ORDER BY u.role, u.fullname";
    $stmt = $conn->prepare($usersQuery);
    $stmt->bind_param("ii", $_SESSION['school_id'], $_SESSION['school_id']);
    $stmt->execute();
    $usersResult = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Passwords - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="<?php echo $_SESSION['role']; ?>dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="card">
            <h2>Manage User Passwords</h2>
            
            <?php if ($message): ?>
                <div class="alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Search and Filter Controls -->
            <div class="form-group">
                <label>Search Users</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name or username..." onkeyup="filterUsers()">
            </div>
            
            <div class="form-group">
                <label>Filter by Role</label>
                <div style="display: flex; gap: 15px; margin-top: 10px;">
                    <label><input type="radio" name="roleFilter" value="all" checked onchange="filterUsers()"> All</label>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <label><input type="radio" name="roleFilter" value="principal" onchange="filterUsers()"> Principal</label>
                    <?php endif; ?>
                    <label><input type="radio" name="roleFilter" value="teacher" onchange="filterUsers()"> Teacher</label>
                    <label><input type="radio" name="roleFilter" value="student" onchange="filterUsers()"> Student</label>
                </div>
            </div>
            
            <table class="table" id="usersTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <th>School</th>
                        <?php endif; ?>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $usersResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo ucfirst($user['role']); ?></td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <td><?php echo htmlspecialchars($user['school_name'] ?? 'N/A'); ?></td>
                        <?php endif; ?>
                        <td>
                            <button class="btn" onclick="showPasswordModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['fullname']); ?>')">
                                Change Password
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePasswordModal()">&times;</span>
            <h2>Change Password</h2>
            <p>Changing password for: <span id="targetUserName"></span></p>
            
            <form method="POST">
                <?php echo getCSRFField(); ?>
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="user_id" id="targetUserId">
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                
                <div class="form-row">
                    <button type="submit" class="btn">Change Password</button>
                    <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showPasswordModal(userId, userName) {
            document.getElementById('targetUserId').value = userId;
            document.getElementById('targetUserName').textContent = userName;
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }

        function filterUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.querySelector('input[name="roleFilter"]:checked')?.value || 'all';
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const name = row.cells[0].textContent.toLowerCase();
                const username = row.cells[1].textContent.toLowerCase();
                const role = row.cells[2].textContent.toLowerCase();
                
                const matchesSearch = name.includes(searchTerm) || username.includes(searchTerm);
                const matchesRole = roleFilter === 'all' || role === roleFilter;
                
                row.style.display = (matchesSearch && matchesRole) ? '' : 'none';
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closePasswordModal();
            }
        }
    </script>
</body>
</html>