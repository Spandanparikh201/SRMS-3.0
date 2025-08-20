<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as principal
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit();
}

$school_id = $_SESSION['school_id'];

// Get teacher count
$teacherQuery = "SELECT COUNT(*) as teacher_count FROM Teacher WHERE school_id = ?";
$stmt = $conn->prepare($teacherQuery);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$teacherResult = $stmt->get_result();
$teacherCount = $teacherResult->fetch_assoc()['teacher_count'];

// Get student count
$studentQuery = "SELECT COUNT(*) as student_count FROM Student WHERE school_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$studentResult = $stmt->get_result();
$studentCount = $studentResult->fetch_assoc()['student_count'];

// Get class count
$classQuery = "SELECT COUNT(*) as class_count FROM Class WHERE school_id = ?";
$stmt = $conn->prepare($classQuery);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$classResult = $stmt->get_result();
$classCount = $classResult->fetch_assoc()['class_count'];

// Get subject count
$subjectQuery = "SELECT COUNT(*) as subject_count FROM Subject WHERE school_id = ?";
$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$subjectResult = $stmt->get_result();
$subjectCount = $subjectResult->fetch_assoc()['subject_count'];

$username = $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard - SRMS</title>    <link rel="stylesheet" href="assets/css/iris-design-system.css">
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
            <li><a href="student_pass_fail_report.php">Pass/Fail Report</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Manage your school in the Student Result Management System.</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?php echo $teacherCount; ?></div>
                <div class="stat-label">Teachers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $studentCount; ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $classCount; ?></div>
                <div class="stat-label">Classes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $subjectCount; ?></div>
                <div class="stat-label">Subjects</div>
            </div>
        </div>

        <div class="action-buttons">
            <button onclick="showAssignmentModal()" class="btn">Assign Teacher to Class & Subject</button>
            <button onclick="exportResults('csv')" class="btn btn-secondary">Export Results (CSV)</button>
            <button onclick="exportStudents('csv')" class="btn btn-secondary">Export Students (CSV)</button>
        </div>

        <div class="card">
            <h2>Student Performance Overview</h2>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="classFilter">Filter by Class:</label>
                <select id="classFilter" class="form-control" style="width: 200px; display: inline-block;" onchange="updatePerformanceChart()">
                    <option value="">All Classes</option>
                    <?php
                    $classQuery = "SELECT class_id, class_name, division FROM Class WHERE school_id = ? ORDER BY class_name, division";
                    $stmt = $conn->prepare($classQuery);
                    $stmt->bind_param("i", $school_id);
                    $stmt->execute();
                    $classResult = $stmt->get_result();
                    while ($class = $classResult->fetch_assoc()) {
                        echo "<option value='{$class['class_id']}'>{$class['class_name']} {$class['division']}</option>";
                    }
                    ?>
                </select>
            </div>
            <canvas id="performanceChart" width="400" height="200"></canvas>
        </div>

       
        <div class="card">
            <h2>Teacher Assignments</h2>
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
                    $stmt->bind_param("i", $school_id);
                    $stmt->execute();
                    $assignmentsResult = $stmt->get_result();
                    
                    while ($assignment = $assignmentsResult->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($assignment['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['class_name'] . ' ' . $assignment['division']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                        <td>
                            <button class="btn btn-danger" onclick="deleteAssignment(<?php echo $assignment['teacher_class_subject_id']; ?>)">Remove</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignmentModal()">&times;</span>
            <h2>Assign Teacher to Class & Subject</h2>
            <form id="assignmentForm">
                <div class="form-group">
                    <label>Teacher</label>
                    <select name="teacher_id" class="form-control" required>
                        <option value="">Select Teacher</option>
                        <?php
                        $teacherQuery = "SELECT t.teacher_id, u.fullname FROM Teacher t JOIN User u ON t.user_id = u.user_id WHERE t.school_id = ?";
                        $stmt = $conn->prepare($teacherQuery);
                        $stmt->bind_param("i", $school_id);
                        $stmt->execute();
                        $teacherResult = $stmt->get_result();
                        while ($teacher = $teacherResult->fetch_assoc()) {
                            echo "<option value='{$teacher['teacher_id']}'>{$teacher['fullname']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select name="class_id" class="form-control" required>
                        <option value="">Select Class</option>
                        <?php
                        $classQuery = "SELECT class_id, class_name, division FROM Class WHERE school_id = ?";
                        $stmt = $conn->prepare($classQuery);
                        $stmt->bind_param("i", $school_id);
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
                        $stmt->bind_param("i", $school_id);
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/app.js"></script>
    <script>
        let performanceChart;
        
        function updatePerformanceChart() {
            const classId = document.getElementById('classFilter').value;
            const url = classId ? 
                `get_performance_data.php?type=subjects&school_id=<?php echo $school_id; ?>&class_id=${classId}` :
                `get_performance_data.php?type=subjects&school_id=<?php echo $school_id; ?>`;
            
            fetch(url)
            .then(response => response.json())
            .then(data => {
                if (performanceChart) {
                    performanceChart.destroy();
                }
                
                const ctx = document.getElementById('performanceChart').getContext('2d');
                performanceChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Average Percentage',
                            data: data.values,
                            backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error loading chart data:', error));
        }
        
        // Load initial chart
        updatePerformanceChart();

        function showAssignmentModal() {
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
            if (confirm('Are you sure you want to remove this assignment? This action cannot be undone.')) {
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
                        // If error mentions results exist, show additional info
                        if (data.error.includes('result records exist')) {
                            showNotification('Tip: You can view and manage results from the Results section', 'info');
                        }
                    }
                });
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeAssignmentModal();
            }
        }
    </script>
    </div>
</body>
</html>