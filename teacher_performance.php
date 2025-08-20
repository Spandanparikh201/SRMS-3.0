<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

// Get teacher information
$teacherQuery = "SELECT * FROM Teacher WHERE user_id = ?";
$stmt = $conn->prepare($teacherQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacherResult = $stmt->get_result();
$teacherInfo = $teacherResult->fetch_assoc();
$teacher_id = $teacherInfo['teacher_id'];

$username = $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Performance - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="tdashboard.php">Dashboard</a></li>
            <li><a href="save_marks.php">Enter Marks</a></li>
            <li><a href="upload_marks.php">Upload Marks</a></li>
            <li><a href="teacher_performance.php">Class Performance</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Class Performance Analysis</h1>
            <p>View detailed performance analytics for your assigned classes.</p>
        </div>

        <div class="card">
            <h2>Select Class & Subject</h2>
            <form id="performanceForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Class</label>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php
                            $assignmentsQuery = "SELECT DISTINCT c.class_id, c.class_name, c.division
                                               FROM Teacher_Class_Subject tcs
                                               JOIN Class c ON tcs.class_id = c.class_id
                                               WHERE tcs.teacher_id = ?
                                               ORDER BY c.class_name, c.division";
                            $stmt = $conn->prepare($assignmentsQuery);
                            $stmt->bind_param("i", $teacher_id);
                            $stmt->execute();
                            $assignmentsResult = $stmt->get_result();
                            while ($row = $assignmentsResult->fetch_assoc()) {
                                echo "<option value='{$row['class_id']}'>{$row['class_name']} {$row['division']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <select id="subject_id" name="subject_id" class="form-control" required>
                            <option value="">Select Subject</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Term</label>
                        <select id="exam_term" name="exam_term" class="form-control" required>
                            <option value="">Select Term</option>
                            <option value="term1">Term 1</option>
                            <option value="term2">Term 2</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn">View Performance</button>
            </form>
        </div>

        <div id="performanceResults" class="card hidden">
            <h2>Performance Results</h2>
            <div id="performanceContent"></div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        const assignments = <?php 
            $assignmentsResult->data_seek(0);
            $assignmentsArray = [];
            while ($row = $assignmentsResult->fetch_assoc()) {
                $assignmentsArray[] = $row;
            }
            echo json_encode($assignmentsArray);
        ?>;

        document.getElementById('class_id').addEventListener('change', function() {
            const classId = this.value;
            const subjectSelect = document.getElementById('subject_id');
            
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            
            if (classId) {
                fetch(`get_teacher_subjects.php?class_id=${classId}&teacher_id=<?= $teacher_id ?>`)
                .then(response => response.json())
                .then(subjects => {
                    subjects.forEach(subject => {
                        subjectSelect.innerHTML += `<option value="${subject.subject_id}">${subject.subject_name}</option>`;
                    });
                });
            }
        });

        document.getElementById('performanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const classId = document.getElementById('class_id').value;
            const subjectId = document.getElementById('subject_id').value;
            const examTerm = document.getElementById('exam_term').value;
            
            if (!classId || !subjectId || !examTerm) {
                showNotification('Please select all fields', 'error');
                return;
            }
            
            loadPerformance(classId, subjectId, examTerm);
        });

        function loadPerformance(classId, subjectId, examTerm) {
            fetch(`get_class_performance.php?class_id=${classId}&subject_id=${subjectId}&exam_term=${examTerm}`)
            .then(response => response.json())
            .then(data => {
                let html = `
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-value">${data.stats.total_students}</div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.stats.average_percentage}%</div>
                            <div class="stat-label">Class Average</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.stats.highest_score}%</div>
                            <div class="stat-label">Highest Score</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.stats.lowest_score}%</div>
                            <div class="stat-label">Lowest Score</div>
                        </div>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Roll Number</th>
                                <th>Student Name</th>
                                <th>Marks Obtained</th>
                                <th>Total Marks</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                data.students.forEach(student => {
                    let grade = 'F';
                    if (student.percentage >= 90) grade = 'A+';
                    else if (student.percentage >= 80) grade = 'A';
                    else if (student.percentage >= 70) grade = 'B+';
                    else if (student.percentage >= 60) grade = 'B';
                    else if (student.percentage >= 50) grade = 'C';
                    else if (student.percentage >= 40) grade = 'D';
                    
                    html += `
                        <tr>
                            <td>${student.roll_number}</td>
                            <td>${student.fullname}</td>
                            <td>${student.marks_obtained}</td>
                            <td>${student.total_subject_marks}</td>
                            <td>${student.percentage}%</td>
                            <td>${grade}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table>';
                
                document.getElementById('performanceContent').innerHTML = html;
                document.getElementById('performanceResults').classList.remove('hidden');
            })
            .catch(error => {
                showNotification('Error loading performance data', 'error');
            });
        }
    </script>
</body>
</html>