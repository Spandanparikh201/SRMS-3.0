<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit();
}

$school_id = $_SESSION['school_id'];
$username = $_SESSION['fullname'];

// Get exams for this school
$examsQuery = "SELECT * FROM Exam WHERE school_id = ? ORDER BY start_date DESC";
$stmt = $conn->prepare($examsQuery);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$examsResult = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
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
            <h1>Manage Exams</h1>
            <p>Create and manage examinations for your school.</p>
        </div>

        <div class="action-buttons">
            <button onclick="showAddModal()" class="btn">Add New Exam</button>
        </div>

        <div class="card">
            <h2>Examinations</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Total Marks</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($exam = $examsResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                        <td><?php echo ucwords(str_replace('_', ' ', $exam['exam_type'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($exam['start_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($exam['end_date'])); ?></td>
                        <td><?php echo $exam['total_marks']; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $exam['status']; ?>">
                                <?php echo ucwords($exam['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="editExam(<?php echo $exam['exam_id']; ?>)" class="action-link">Edit</button>
                            <button onclick="enterMarks(<?php echo $exam['exam_id']; ?>)" class="action-link" style="color: #27ae60;">Enter Marks</button>
                            <button onclick="deleteExam(<?php echo $exam['exam_id']; ?>)" class="action-link" style="color: #ef4444;">Delete</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="examModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Exam</h2>
            <form id="examForm">
                <input type="hidden" id="examId" name="exam_id">
                <div class="form-group">
                    <label>Exam Name</label>
                    <input type="text" id="examName" name="exam_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Exam Type</label>
                    <select id="examType" name="exam_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="term1">Term 1</option>
                        <option value="term2">Term 2</option>
                        <option value="unit_test">Unit Test</option>
                        <option value="final">Final Exam</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" id="startDate" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" id="endDate" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Total Marks</label>
                        <input type="number" id="totalMarks" name="total_marks" class="form-control" value="100" min="1" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn" id="submitBtn">Add Exam</button>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Exam';
            document.getElementById('submitBtn').textContent = 'Add Exam';
            document.getElementById('examForm').reset();
            document.getElementById('examId').value = '';
            document.getElementById('examModal').style.display = 'block';
        }

        function editExam(id) {
            fetch(`exam_actions.php?action=get&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const exam = data.exam;
                    document.getElementById('modalTitle').textContent = 'Edit Exam';
                    document.getElementById('submitBtn').textContent = 'Update Exam';
                    document.getElementById('examId').value = exam.exam_id;
                    document.getElementById('examName').value = exam.exam_name;
                    document.getElementById('examType').value = exam.exam_type;
                    document.getElementById('startDate').value = exam.start_date;
                    document.getElementById('endDate').value = exam.end_date;
                    document.getElementById('totalMarks').value = exam.total_marks;
                    document.getElementById('status').value = exam.status;
                    document.getElementById('examModal').style.display = 'block';
                } else {
                    showNotification(data.error, 'error');
                }
            });
        }

        function deleteExam(id) {
            if (confirm('Are you sure you want to delete this exam?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('exam_id', id);
                
                fetch('exam_actions.php', {
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

        function closeModal() {
            document.getElementById('examModal').style.display = 'none';
        }

        document.getElementById('examForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = document.getElementById('examId').value ? 'update' : 'create';
            formData.append('action', action);
            
            fetch('exam_actions.php', {
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

        function enterMarks(examId) {
            window.location.href = `exam_marks_entry.php?exam_id=${examId}`;
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>