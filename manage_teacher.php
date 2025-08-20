<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal')) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - SRMS</title>    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <?php if ($role === 'admin'): ?>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="manage_school.php">Manage Schools</a></li>
            <?php else: ?>
                <li><a href="pdashboard.php">Dashboard</a></li>
            <?php endif; ?>
            <li><a href="manage_teacher.php">Teachers</a></li>
            <li><a href="manage_students.php">Students</a></li>
            <?php if ($role === 'principal'): ?>
                <li><a href="manage_classes.php">Classes</a></li>
                <li><a href="manage_subjects.php">Subjects</a></li>
                <li><a href="manage_exams.php">Exams</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Manage Teachers</h1>
            <p>Add, edit, and assign teachers to classes and subjects.</p>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="showSection('add')">Add Teacher</div>
            <div class="tab" onclick="showSection('manage')">Manage Teachers</div>
        </div>

        <div id="addSection" class="section active">
            <div class="card">
                <h2>Add New Teacher</h2>
                <form id="teacherForm">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" class="form-control" placeholder="Enter teacher's full name" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                    <button type="submit" class="btn">Add Teacher</button>
                </form>
            </div>
        </div>

        <div id="manageSection" class="section">
            <div class="card">
                <h2>Existing Teachers</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Teacher ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $teachersQuery = "SELECT t.teacher_id, u.fullname, u.username 
                                         FROM Teacher t 
                                         JOIN User u ON t.user_id = u.user_id 
                                         WHERE t.school_id = ? 
                                         ORDER BY u.fullname";
                        $stmt = $conn->prepare($teachersQuery);
                        $stmt->bind_param("i", $_SESSION['school_id']);
                        $stmt->execute();
                        $teachersResult = $stmt->get_result();
                        while ($teacher = $teachersResult->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($teacher['fullname']) ?></td>
                            <td><?= htmlspecialchars($teacher['username']) ?></td>
                            <td><?= $teacher['teacher_id'] ?></td>
                            <td>
                                <button class="btn" onclick="editTeacher('<?= $teacher['username'] ?>', '<?= htmlspecialchars($teacher['fullname']) ?>')">Edit</button>
                                <button class="btn btn-secondary" onclick="assignTeacher(<?= $teacher['teacher_id'] ?>, '<?= htmlspecialchars($teacher['fullname']) ?>')">Assign</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Teacher Assignments Section -->
        <div class="card">
        <h2>Current Teacher Assignments</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $assignmentsQuery = "SELECT tcs.teacher_class_subject_id, u.fullname, c.class_name, c.division, s.subject_name
                                    FROM Teacher_Class_Subject tcs
                                    JOIN Teacher t ON tcs.teacher_id = t.teacher_id
                                    JOIN User u ON t.user_id = u.user_id
                                    JOIN Class c ON tcs.class_id = c.class_id
                                    JOIN Subject s ON tcs.subject_id = s.subject_id
                                    WHERE t.school_id = ?
                                    ORDER BY u.fullname";
                $stmt = $conn->prepare($assignmentsQuery);
                $stmt->bind_param("i", $_SESSION['school_id']);
                $stmt->execute();
                $assignmentsResult = $stmt->get_result();
                while ($assignment = $assignmentsResult->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($assignment['fullname']) ?></td>
                    <td><?= htmlspecialchars($assignment['class_name'] . ' ' . $assignment['division']) ?></td>
                    <td><?= htmlspecialchars($assignment['subject_name']) ?></td>
                    <td>
                        <button class="btn btn-danger" onclick="deleteAssignment(<?= $assignment['teacher_class_subject_id'] ?>)">Remove</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Teacher</h2>
            <form id="editTeacherForm">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="editUsername" name="username" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="editFullname" name="fullname" class="form-control" required>
                </div>
                <button type="submit" class="btn">Update Teacher</button>
            </form>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignmentModal()">&times;</span>
            <h2>Assign Teacher to Class & Subject</h2>
            <form id="assignmentForm">
                <input type="hidden" id="assignTeacherId" name="teacher_id">
                <div class="form-group">
                    <label>Teacher</label>
                    <input type="text" id="assignTeacherName" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select name="class_id" class="form-control" required>
                        <option value="">Select Class</option>
                        <?php
                        $classQuery = "SELECT class_id, class_name, division FROM Class WHERE school_id = ?";
                        $stmt = $conn->prepare($classQuery);
                        $stmt->bind_param("i", $_SESSION['school_id']);
                        $stmt->execute();
                        $classResult = $stmt->get_result();
                        while ($class = $classResult->fetch_assoc()) {
                            echo "<option value='{$class['class_id']}'>{$class['class_name']} {$class['division']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">Select Subject</option>
                        <?php
                        $subjectQuery = "SELECT subject_id, subject_name FROM Subject WHERE school_id = ?";
                        $stmt = $conn->prepare($subjectQuery);
                        $stmt->bind_param("i", $_SESSION['school_id']);
                        $stmt->execute();
                        $subjectResult = $stmt->get_result();
                        while ($subject = $subjectResult->fetch_assoc()) {
                            echo "<option value='{$subject['subject_id']}'>{$subject['subject_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn">Create Assignment</button>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function showSection(section) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(section + 'Section').classList.add('active');
        }

        document.getElementById('teacherForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add');
            
            fetch('teacher_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.success + (data.username ? ' (Username: ' + data.username + ')' : ''), 'success');
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error adding teacher', 'error');
            });
        });

        function editTeacher(username, fullname) {
            document.getElementById('editUsername').value = username;
            document.getElementById('editFullname').value = fullname;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('editTeacherForm').addEventListener('submit', function(e) {
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
                    showNotification(data.success, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.error, 'error');
                }
            });
        });

        function assignTeacher(teacherId, teacherName) {
            document.getElementById('assignTeacherId').value = teacherId;
            document.getElementById('assignTeacherName').value = teacherName;
            document.getElementById('assignmentModal').style.display = 'block';
        }

        function closeAssignmentModal() {
            document.getElementById('assignmentModal').style.display = 'none';
        }

        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create');
            
            fetch('assignment_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.success, 'success');
                    closeAssignmentModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.error, 'error');
                }
            });
        });

        function deleteAssignment(id) {
            if (confirm('Are you sure you want to remove this assignment?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('assignment_id', id);
                
                fetch('assignment_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.success, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.error, 'error');
                    }
                });
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                if (event.target.id === 'editModal') {
                    closeModal();
                } else if (event.target.id === 'assignmentModal') {
                    closeAssignmentModal();
                }
            }
        }
    </script>
</body>
</html>