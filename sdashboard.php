<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get student information
$studentQuery = "SELECT s.*, c.class_name, c.division FROM Student s JOIN Class c ON s.class_id = c.class_id WHERE s.user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$studentResult = $stmt->get_result();
$studentInfo = $studentResult->fetch_assoc();
$student_id = $studentInfo['student_id'];

// Get subjects count
$subjectsQuery = "SELECT COUNT(DISTINCT subject_id) as count FROM Result WHERE student_id = ?";
$stmt = $conn->prepare($subjectsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$subjectsResult = $stmt->get_result();
$subjectCount = $subjectsResult->fetch_assoc()['count'];

// Get results count
$resultsQuery = "SELECT COUNT(*) as count FROM Result WHERE student_id = ?";
$stmt = $conn->prepare($resultsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$resultsResult = $stmt->get_result();
$resultCount = $resultsResult->fetch_assoc()['count'];

// Get average percentage
$avgQuery = "SELECT AVG(marks_obtained / total_subject_marks * 100) as avg_percentage FROM Result WHERE student_id = ?";
$stmt = $conn->prepare($avgQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$avgResult = $stmt->get_result();
$avgPercentage = $avgResult->fetch_assoc()['avg_percentage'];

$username = $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SRMS</title>    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="sdashboard.php">Dashboard</a></li>
            <li><a href="student_results.php">My Results</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>View your academic results and performance.</p>
        </div>

        <div class="student-info">
            <h2>Student Information</h2>
            <div class="info-row">
                <span class="info-label">Roll Number:</span>
                <span class="info-value"><?php echo htmlspecialchars($studentInfo['roll_number']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Class:</span>
                <span class="info-value"><?php echo htmlspecialchars($studentInfo['class_name'] . ' ' . $studentInfo['division']); ?></span>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?php echo $subjectCount; ?></div>
                <div class="stat-label">Subjects</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $resultCount; ?></div>
                <div class="stat-label">Results</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $avgPercentage ? round($avgPercentage, 1) . '%' : 'N/A'; ?></div>
                <div class="stat-label">Average</div>
            </div>
        </div>
    </div>
</body>
</html>