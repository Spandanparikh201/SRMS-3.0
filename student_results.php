<?php
session_start();
require_once 'db_connect.php';

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

// Get results
$resultsQuery = "SELECT er.*, s.subject_name, e.exam_name,
                 ROUND((er.marks_obtained/er.total_marks)*100, 2) as percentage
                 FROM ExamResult er 
                 JOIN Subject s ON er.subject_id = s.subject_id 
                 JOIN Exam e ON er.exam_id = e.exam_id
                 WHERE er.student_id = ? 
                 ORDER BY e.exam_name, s.subject_name";
$stmt = $conn->prepare($resultsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$resultsResult = $stmt->get_result();

$examResults = [];
while ($result = $resultsResult->fetch_assoc()) {
    $examResults[$result['exam_name']][] = $result;
}

$username = $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
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
            <h1>My Academic Results</h1>
            <p>View your examination results and academic performance.</p>
        </div>

        <div class="card">
            <h2>Student Information</h2>
            <div class="info-item">
                <div class="info-label">Name</div>
                <div class="info-value"><?= htmlspecialchars($username) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Roll Number</div>
                <div class="info-value"><?= htmlspecialchars($studentInfo['roll_number']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Class</div>
                <div class="info-value"><?= htmlspecialchars($studentInfo['class_name'] . ' ' . $studentInfo['division']) ?></div>
            </div>
        </div>

        <?php if (!empty($examResults)): ?>
            <div class="tabs">
                <?php $first = true; foreach ($examResults as $examName => $results): ?>
                    <div class="tab <?= $first ? 'active' : '' ?>" onclick="showSection('<?= str_replace(' ', '', $examName) ?>')"><?= htmlspecialchars($examName) ?></div>
                    <?php $first = false; endforeach; ?>
            </div>

            <?php $first = true; foreach ($examResults as $examName => $results): ?>
                <div id="<?= str_replace(' ', '', $examName) ?>Section" class="section <?= $first ? 'active' : '' ?>">
                    <div class="card">
                        <h2><?= htmlspecialchars($examName) ?> Results</h2>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Marks Obtained</th>
                                    <th>Total Marks</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($result['subject_name']) ?></td>
                                        <td><?= $result['marks_obtained'] ?></td>
                                        <td><?= $result['total_marks'] ?></td>
                                        <td><?= $result['percentage'] ?>%</td>
                                        <td>
                                            <?php
                                            $percentage = $result['percentage'];
                                            if ($percentage >= 90) echo 'A+';
                                            elseif ($percentage >= 80) echo 'A';
                                            elseif ($percentage >= 70) echo 'B+';
                                            elseif ($percentage >= 60) echo 'B';
                                            elseif ($percentage >= 50) echo 'C';
                                            elseif ($percentage >= 40) echo 'D';
                                            else echo 'F';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php $first = false; endforeach; ?>
        <?php else: ?>
            <div class="card">
                <p>No exam results available.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showSection(section) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(section + 'Section').classList.add('active');
        }
    </script>
</body>
</html>