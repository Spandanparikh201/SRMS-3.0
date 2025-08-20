<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a teacher
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

// Get assigned classes count
$classesQuery = "SELECT COUNT(DISTINCT class_id) as count FROM Teacher_Class_Subject WHERE teacher_id = ?";
$stmt = $conn->prepare($classesQuery);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classesResult = $stmt->get_result();
$classCount = $classesResult->fetch_assoc()['count'];

// Get subjects count
$subjectsQuery = "SELECT COUNT(DISTINCT subject_id) as count FROM Teacher_Class_Subject WHERE teacher_id = ?";
$stmt = $conn->prepare($subjectsQuery);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$subjectsResult = $stmt->get_result();
$subjectCount = $subjectsResult->fetch_assoc()['count'];

// Get results count
$resultsQuery = "SELECT COUNT(*) as count FROM ExamResult WHERE student_id IN (SELECT student_id FROM Student WHERE school_id = ?)";
$stmt = $conn->prepare($resultsQuery);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$resultsResult = $stmt->get_result();
$resultCount = $resultsResult->fetch_assoc()['count'];

$username = $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - SRMS</title>    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="tdashboard.php">Dashboard</a></li>
            <li><a href="save_marks.php">Enter Marks</a></li>
            <li><a href="upload_marks.php">Upload Marks</a></li>
            <li><a href="teacher_performance.php">Class Performance</a></li>
            <li><a href="student_pass_fail_report.php">Pass/Fail Report</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Manage student marks and track performance.</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?php echo $classCount; ?></div>
                <div class="stat-label">Classes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $subjectCount; ?></div>
                <div class="stat-label">Subjects</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $resultCount; ?></div>
                <div class="stat-label">Results Entered</div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="save_marks.php" class="btn">Enter Marks</a>
            <a href="upload_marks.php" class="btn btn-secondary">Upload Marks</a>
            <button onclick="exportResults('csv')" class="btn btn-secondary">Export Results (CSV)</button>
        </div>

        <div class="card">
            <h2>Class Performance</h2>
            <canvas id="classPerformanceChart" width="400" height="200"></canvas>
        </div>

        <div class="card">
            <h2>Recent Marks Entered</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Term</th>
                        <th>Marks</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $marksQuery = "SELECT s.roll_number, u.fullname, c.class_name, c.division, sub.subject_name, 
                                          e.exam_name, er.marks_obtained, er.total_marks, er.updated_at
                                   FROM ExamResult er
                                   JOIN Student s ON er.student_id = s.student_id
                                   JOIN User u ON s.user_id = u.user_id
                                   JOIN Class c ON s.class_id = c.class_id
                                   JOIN Subject sub ON er.subject_id = sub.subject_id
                                   JOIN Exam e ON er.exam_id = e.exam_id
                                   WHERE s.school_id = ?
                                   ORDER BY er.updated_at DESC
                                   LIMIT 10";
                    $stmt = $conn->prepare($marksQuery);
                    $stmt->bind_param("i", $school_id);
                    $stmt->execute();
                    $marksResult = $stmt->get_result();
                    
                    while ($mark = $marksResult->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($mark['fullname']); ?> (<?php echo $mark['roll_number']; ?>)</td>
                        <td><?php echo $mark['class_name'] . ' ' . $mark['division']; ?></td>
                        <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($mark['exam_name']); ?></td>
                        <td><?php echo $mark['marks_obtained'] . '/' . $mark['total_marks']; ?></td>
                        <td><?php echo date('d M Y', strtotime($mark['updated_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Get class performance data
        const classData = {
            labels: ['Class 10A', 'Class 10B', 'Class 9A'],
            datasets: [{
                label: 'Average Marks',
                data: [78, 85, 72],
                backgroundColor: ['#2563eb', '#10b981', '#f59e0b'],
                borderWidth: 1
            }]
        };

        const ctx = document.getElementById('classPerformanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: classData,
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
    </script>
    </div>
</body>
</html>