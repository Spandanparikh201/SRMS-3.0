<?php
session_start();
require_once 'db_connect.php';

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
    <title>Manage Students - SRMS</title>    <link rel="stylesheet" href="assets/css/iris-design-system.css">
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
            <h1>Manage Students</h1>
            <p>Add, edit, and manage student records.</p>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="showSection('add')">Add Student</div>
            <div class="tab" onclick="showSection('bulk')">Bulk Upload</div>
            <div class="tab" onclick="showSection('manage')">Manage Students</div>
        </div>

        <div id="addSection" class="section active">
            <div class="card">
                <h2>Add New Student</h2>
                <form id="studentForm">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" class="form-control" placeholder="Enter student's full name" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                    <div class="form-group">
                        <label>Roll Number</label>
                        <input type="text" name="roll_number" class="form-control" placeholder="Enter roll number" required>
                    </div>
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php
                            $classQuery = "SELECT class_id, class_name, division FROM Class WHERE school_id = ? ORDER BY class_name, division";
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
                    <button type="submit" class="btn">Add Student</button>
                </form>
            </div>
        </div>

        <div id="bulkSection" class="section">
            <div class="card">
                <h2>Bulk Upload Students</h2>
                <div class="card">
                <h2>Download Template</h2>
                <p>Download the CSV template to format your student data correctly.</p>
                <a href="templates/students_template.csv" class="btn btn-secondary" download>Download Template</a>
                 </div>
                <form id="bulkUploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Upload CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <small>CSV format: fullname,roll_number,class_name,division,password</small>
                    </div>
                    <button type="submit" class="btn">Upload Students</button>
                </form>
            </div>
        </div>

        <div id="manageSection" class="section">
            <div class="card">
                <h2>Existing Students</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Username</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $studentsQuery = "SELECT s.roll_number, u.fullname, u.username, c.class_name, c.division 
                                         FROM Student s 
                                         JOIN User u ON s.user_id = u.user_id 
                                         JOIN Class c ON s.class_id = c.class_id 
                                         WHERE s.school_id = ? 
                                         ORDER BY c.class_name, c.division, s.roll_number";
                        $stmt = $conn->prepare($studentsQuery);
                        $stmt->bind_param("i", $_SESSION['school_id']);
                        $stmt->execute();
                        $studentsResult = $stmt->get_result();
                        while ($student = $studentsResult->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($student['roll_number']) ?></td>
                            <td><?= htmlspecialchars($student['fullname']) ?></td>
                            <td><?= htmlspecialchars($student['class_name'] . ' ' . $student['division']) ?></td>
                            <td><?= htmlspecialchars($student['username']) ?></td>
                            <td><button class="btn" onclick="editStudent('<?= $student['username'] ?>', '<?= htmlspecialchars($student['fullname']) ?>', '<?= $student['roll_number'] ?>')">Edit</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Student</h2>
            <form id="editStudentForm">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="editUsername" name="username" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="editFullname" name="fullname" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Roll Number</label>
                    <input type="text" id="editRollNumber" name="roll_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select id="editClassId" name="class_id" class="form-control" required>
                        <?php
                        $classQuery = "SELECT class_id, class_name, division FROM Class WHERE school_id = ? ORDER BY class_name, division";
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
                <button type="submit" class="btn">Update Student</button>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Student</h2>
            <form id="editStudentForm">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="editUsername" name="username" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="editFullname" name="fullname" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Roll Number</label>
                    <input type="text" id="editRollNumber" name="roll_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select id="editClassId" name="class_id" class="form-control" required>
                        <?php
                        $classQuery = "SELECT class_id, class_name, division FROM Class WHERE school_id = ? ORDER BY class_name, division";
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
                <button type="submit" class="btn">Update Student</button>
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

        document.getElementById('studentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add');
            
            fetch('student_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.success + (data.username ? ' (Username: ' + data.username + ')' : ''), 'success');
                    this.reset();
                } else {
                    showNotification(data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error adding student', 'error');
            });
        });

        document.getElementById('bulkUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'bulk_upload');
            
            fetch('student_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.success, 'success');
                    this.reset();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification(data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error uploading file', 'error');
            });
        });

        function editStudent(username, fullname, rollNumber) {
            document.getElementById('editUsername').value = username;
            document.getElementById('editFullname').value = fullname;
            document.getElementById('editRollNumber').value = rollNumber;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('editStudentForm').addEventListener('submit', function(e) {
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
                    showNotification(data.success, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.error, 'error');
                }
            });
        });

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>