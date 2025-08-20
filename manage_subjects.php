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
    <title>Manage Subjects - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
    <style>
        .tabs { 
            display: flex; 
            gap: 1rem; 
            margin-bottom: 2rem; 
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0;
        }
        .tab { 
            padding: 1rem 2rem; 
            background: #f9fafb; 
            border: 2px solid #e5e7eb;
            border-bottom: none;
            border-radius: 8px 8px 0 0; 
            cursor: pointer; 
            transition: all 0.3s;
            font-weight: 600;
            color: #374151;
            position: relative;
            top: 2px;
        }
        .tab:hover {
            background: #f3f4f6;
            color: #2563eb;
        }
        .tab.active { 
            background: #2563eb; 
            color: white; 
            border-color: #2563eb;
            z-index: 1;
        }
        .section { display: none; }
        .section.active { display: block; }
        .action-buttons { margin-bottom: 1rem; }
        .action-buttons .btn { margin-right: 1rem; }
    </style>
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
            <h1>Manage Subjects</h1>
            <p>Add, edit, and manage subjects for your school.</p>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="showSection('add')">Add Subject</div>
            <div class="tab" onclick="showSection('manage')">Manage Subjects</div>
        </div>

        <div id="addSection" class="section active">
            <div class="card">
                <h2>Add New Subject</h2>
                <form id="subjectForm">
                    <div class="form-group">
                        <label>Subject Name</label>
                        <input type="text" name="subject_name" class="form-control" placeholder="e.g. Mathematics, Science, English" required>
                    </div>
                    <button type="submit" class="btn">Add Subject</button>
                </form>
            </div>
        </div>

        <div id="manageSection" class="section">
            <div class="card">
                <h2>Existing Subjects</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Subject ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subjectsQuery = "SELECT subject_id, subject_name 
                                         FROM Subject 
                                         WHERE school_id = ? 
                                         ORDER BY subject_name";
                        $stmt = $conn->prepare($subjectsQuery);
                        $stmt->bind_param("i", $_SESSION['school_id']);
                        $stmt->execute();
                        $subjectsResult = $stmt->get_result();
                        while ($subject = $subjectsResult->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                            <td><?= $subject['subject_id'] ?></td>
                            <td><button class="btn" onclick="editSubject(<?= $subject['subject_id'] ?>, '<?= htmlspecialchars($subject['subject_name']) ?>')">Edit</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

       

    <!-- Edit Subject Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Subject</h2>
            <form id="editSubjectForm">
                <input type="hidden" id="editSubjectId" name="subject_id">
                <div class="form-group">
                    <label>Subject Name</label>
                    <input type="text" id="editSubjectName" name="subject_name" class="form-control" required>
                </div>
                <button type="submit" class="btn">Update Subject</button>
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

        document.getElementById('subjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add');
            
            fetch('subject_actions.php', {
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
                showNotification('Error adding subject', 'error');
            });
        });



        function editSubject(subjectId, subjectName) {
            document.getElementById('editSubjectId').value = subjectId;
            document.getElementById('editSubjectName').value = subjectName;
            document.getElementById('editModal').style.display = 'block';
        }

        function editExam(examTerm) {
            window.location.href = 'view_results.php?exam_term=' + examTerm;
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('editSubjectForm').addEventListener('submit', function(e) {
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