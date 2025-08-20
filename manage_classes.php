<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
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
    <title>Manage Classes - SRMS</title>    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="pdashboard.php">Dashboard</a></li>
            <li><a href="manage_teacher.php">Teachers</a></li>
            <li><a href="manage_students.php">Students</a></li>
            <li><a href="manage_classes.php">Classes</a></li>
            <li><a href="manage_subjects.php">Subjects</a></li>
            <li><a href="manage_exams.php">Exams</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Manage Classes</h1>
            <p>Add, edit, and manage class divisions.</p>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="showSection('add')">Add Class</div>
            <div class="tab" onclick="showSection('manage')">Manage Classes</div>
        </div>

        <div id="addSection" class="section active">
            <div class="card">
                <h2>Add New Class</h2>
                <form id="classForm">
                    <div class="form-group">
                        <label>Class Name</label>
                        <input type="text" name="class_name" class="form-control" placeholder="e.g. 10, 11, 12" required>
                    </div>
                    <div class="form-group">
                        <label>Division</label>
                        <input type="text" name="division" class="form-control" placeholder="e.g. A, B, Science, Commerce" required>
                    </div>
                    <button type="submit" class="btn">Add Class</button>
                </form>
            </div>
        </div>

        <div id="manageSection" class="section">
            <div class="card">
                <h2>Existing Classes</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Division</th>
                            <th>Student Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $classesQuery = "SELECT c.class_name, c.division, COUNT(s.student_id) as student_count 
                                        FROM Class c 
                                        LEFT JOIN Student s ON c.class_id = s.class_id 
                                        WHERE c.school_id = ? 
                                        GROUP BY c.class_id 
                                        ORDER BY c.class_name, c.division";
                        $stmt = $conn->prepare($classesQuery);
                        $stmt->bind_param("i", $_SESSION['school_id']);
                        $stmt->execute();
                        $classesResult = $stmt->get_result();
                        while ($class = $classesResult->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($class['class_name']) ?></td>
                            <td><?= htmlspecialchars($class['division']) ?></td>
                            <td><?= $class['student_count'] ?></td>
                            <td><button class="btn" onclick="editClass('<?= $class['class_name'] ?>', '<?= $class['division'] ?>')">Edit</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Class</h2>
            <form id="editClassForm">
                <div class="form-group">
                    <label>Class Name</label>
                    <input type="text" id="editClassName" name="class_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Division</label>
                    <input type="text" id="editDivision" name="division" class="form-control" required>
                </div>
                <button type="submit" class="btn">Update Class</button>
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

        document.getElementById('classForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add');
            
            fetch('class_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.success, 'success');
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error adding class', 'error');
            });
        });

        function editClass(className, division) {
            document.getElementById('editClassName').value = className;
            document.getElementById('editDivision').value = division;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('editClassForm').addEventListener('submit', function(e) {
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