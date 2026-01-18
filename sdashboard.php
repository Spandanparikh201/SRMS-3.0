<?php
session_start();
require_once 'db_connect.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    throw new Exception('Unauthorized access');
}

$user_id = $_SESSION['user_id'];

// Get student information
$studentQuery = "SELECT s.*, c.class_name, c.division FROM student s JOIN class c ON s.class_id = c.class_id WHERE s.user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$studentResult = $stmt->get_result();
$studentInfo = $studentResult->fetch_assoc();
$student_id = $studentInfo['student_id'];

// Get subjects count
$subjectsQuery = "SELECT COUNT(DISTINCT subject_id) as count FROM examresult WHERE student_id = ?";
$stmt = $conn->prepare($subjectsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$subjectsResult = $stmt->get_result();
$subjectCount = $subjectsResult->fetch_assoc()['count'];

// Get results count
$resultsQuery = "SELECT COUNT(*) as count FROM examresult WHERE student_id = ?";
$stmt = $conn->prepare($resultsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$resultsResult = $stmt->get_result();
$resultCount = $resultsResult->fetch_assoc()['count'];

// Get average percentage
$avgQuery = "SELECT AVG(marks_obtained / total_marks * 100) as avg_percentage FROM examresult WHERE student_id = ?";
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
            <li><a href="change_password_simple.php">Change Password</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>View your academic results and performance.</p>
        </div>

        <div class="welcome-banner" style="text-align: left;">
            <h1 style="text-align: left;">Student Information</h1>
            <p style="text-align: left;">Roll Number: <?php echo htmlspecialchars($studentInfo['roll_number']); ?></p>
            <p style="text-align: left;">Class: <?php echo htmlspecialchars($studentInfo['class_name'] . ' ' . $studentInfo['division']); ?></p>
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

        <div class="card">
            <h2>Download Report</h2>
            <form id="reportForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label>Select Exam</label>
                    <select id="exam_id" name="exam_id" class="form-control" required>
                        <option value="">Select Exam</option>
                        <option value="all">All Exams</option>
                        <?php
                        $examQuery = "SELECT DISTINCT e.exam_id, e.exam_name 
                                     FROM exam e 
                                     JOIN examresult er ON e.exam_id = er.exam_id 
                                     WHERE er.student_id = ? 
                                     ORDER BY e.exam_name";
                        $stmt = $conn->prepare($examQuery);
                        $stmt->bind_param("i", $student_id);
                        $stmt->execute();
                        $examResult = $stmt->get_result();
                        while ($exam = $examResult->fetch_assoc()) {
                            echo "<option value='{$exam['exam_id']}'>{$exam['exam_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-row">
                    <button type="button" onclick="downloadReport('pdf')" class="btn">📄 Download PDF Report</button>
                    <button type="button" onclick="downloadReport('csv')" class="btn">📊 Download CSV Report</button>
                </div>
            </form>
        </div>

        <script>
        function downloadReport(format) {
            const examId = document.getElementById('exam_id').value;
            if (!examId) {
                alert('Please select an exam first');
                return;
            }
            
            // Show loading state
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.classList.add('loading');
            btn.disabled = true;
            
            const url = `student_report.php?format=${format}&exam_id=${examId}`;
            
            setTimeout(() => {
                btn.classList.remove('loading');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }, 2000);
            
            if (format === 'pdf') {
                window.open(url, '_blank');
            } else {
                window.location.href = url;
            }
        }
        </script>
    </div>
</body>
</html>