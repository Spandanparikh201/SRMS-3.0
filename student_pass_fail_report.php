<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal' && $_SESSION['role'] !== 'teacher')) {
    header("Location: login.php");
    exit();
}

$school_id = $_SESSION['school_id'];
$action = $_GET['action'] ?? '';

if ($action === 'get_report') {
    $class_id = $_GET['class_id'] ?? '';
    $exam_id = $_GET['exam_id'] ?? '';
    
    $query = "
        SELECT 
            s.roll_number,
            u.fullname as student_name,
            c.class_name,
            c.division,
            sub.subject_name,
            e.exam_name,
            er.marks_obtained,
            er.total_marks,
            ROUND((er.marks_obtained / er.total_marks * 100), 2) as percentage,
            CASE 
                WHEN (er.marks_obtained / er.total_marks * 100) < 32 THEN 'FAIL'
                ELSE 'PASS'
            END as result_status
        FROM examresult er
        JOIN student s ON er.student_id = s.student_id
        JOIN user u ON s.user_id = u.user_id
        JOIN class c ON s.class_id = c.class_id
        JOIN subject sub ON er.subject_id = sub.subject_id
        JOIN exam e ON er.exam_id = e.exam_id
        WHERE s.school_id = ?
    ";
    
    $params = [$school_id];
    
    if ($class_id) {
        $query .= " AND s.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($exam_id) {
        $query .= " AND er.exam_id = ?";
        $params[] = $exam_id;
    }
    
    $query .= " ORDER BY s.roll_number, sub.subject_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($results);
    exit;
}

if ($action === 'get_summary') {
    $class_id = $_GET['class_id'] ?? '';
    $exam_id = $_GET['exam_id'] ?? '';
    
    $query = "
        SELECT 
            COUNT(*) as total_results,
            SUM(CASE WHEN (er.marks_obtained / er.total_marks * 100) < 32 THEN 1 ELSE 0 END) as failed_count,
            SUM(CASE WHEN (er.marks_obtained / er.total_marks * 100) >= 32 THEN 1 ELSE 0 END) as passed_count
        FROM examresult er
        JOIN student s ON er.student_id = s.student_id
        WHERE s.school_id = ?
    ";
    
    $params = [$school_id];
    
    if ($class_id) {
        $query .= " AND s.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($exam_id) {
        $query .= " AND er.exam_id = ?";
        $params[] = $exam_id;
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $summary = $stmt->get_result()->fetch_assoc();
    
    echo json_encode($summary);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pass/Fail Report - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
    <style>
        .pass { color: #2ecc71; font-weight: bold; }
        .fail { color: #e74c3c; font-weight: bold; }
        .summary-card { background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 1rem; border-radius: 8px; margin: 1rem 0; }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] === 'principal' ? 'pdashboard.php' : 'tdashboard.php'); ?>" class="btn btn-secondary" style="margin-bottom: 20px;">← Back to Dashboard</a>
        
        <div class="welcome-banner">
            <h1>📊 Student Pass/Fail Report</h1>
            <p>View pass/fail status based on 32% passing criteria</p>
        </div>

        <div class="card">
            <h2>🔍 Filter Options</h2>
            <div class="form-row">
                <div class="form-group">
                    <label>Class</label>
                    <select id="classFilter" class="form-control">
                        <option value="">All Classes</option>
                        <?php
                        $classQuery = "SELECT class_id, class_name, division FROM class WHERE school_id = ? ORDER BY class_name, division";
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
                    <label>Exam</label>
                    <select id="examFilter" class="form-control">
                        <option value="">All Exams</option>
                        <?php
                        $examQuery = "SELECT exam_id, exam_name FROM exam WHERE school_id = ? ORDER BY exam_name";
                        $stmt = $conn->prepare($examQuery);
                        $stmt->bind_param("i", $school_id);
                        $stmt->execute();
                        $examResult = $stmt->get_result();
                        while ($exam = $examResult->fetch_assoc()) {
                            echo "<option value='{$exam['exam_id']}'>{$exam['exam_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <button onclick="loadReport()" class="btn">📊 Generate Report</button>
        </div>

        <div class="card">
            <h2>📈 Summary</h2>
            <div id="summaryContainer" class="summary-card">
                <p>Select filters and click "Generate Report" to view summary</p>
            </div>
        </div>

        <div class="card">
            <h2>📋 Detailed Results</h2>
            <div id="reportContainer">
                <p>No data loaded. Use filters above to generate report.</p>
            </div>
        </div>
    </div>

    <script>
        function loadReport() {
            const classId = document.getElementById('classFilter').value;
            const examId = document.getElementById('examFilter').value;
            
            // Load summary
            fetch(`student_pass_fail_report.php?action=get_summary&class_id=${classId}&exam_id=${examId}`)
            .then(response => response.json())
            .then(summary => {
                const passRate = summary.total_results > 0 ? 
                    ((summary.passed_count / summary.total_results) * 100).toFixed(1) : 0;
                
                document.getElementById('summaryContainer').innerHTML = `
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-value">${summary.total_results}</div>
                            <div class="stat-label">Total Results</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value pass">${summary.passed_count}</div>
                            <div class="stat-label">Passed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value fail">${summary.failed_count}</div>
                            <div class="stat-label">Failed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${passRate}%</div>
                            <div class="stat-label">Pass Rate</div>
                        </div>
                    </div>
                `;
            });
            
            // Load detailed report
            fetch(`student_pass_fail_report.php?action=get_report&class_id=${classId}&exam_id=${examId}`)
            .then(response => response.json())
            .then(results => {
                if (results.length === 0) {
                    document.getElementById('reportContainer').innerHTML = '<p>No results found for selected criteria.</p>';
                    return;
                }
                
                document.getElementById('reportContainer').innerHTML = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Exam</th>
                                <th>Marks</th>
                                <th>Percentage</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${results.map(result => `
                                <tr>
                                    <td>${result.roll_number}</td>
                                    <td>${result.student_name}</td>
                                    <td>${result.class_name} ${result.division}</td>
                                    <td>${result.subject_name}</td>
                                    <td>${result.exam_name}</td>
                                    <td>${result.marks_obtained}/${result.total_marks}</td>
                                    <td>${result.percentage}%</td>
                                    <td><span class="${result.result_status.toLowerCase()}">${result.result_status}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            });
        }
        
        // Auto-load on filter change
        document.getElementById('classFilter').addEventListener('change', loadReport);
        document.getElementById('examFilter').addEventListener('change', loadReport);
        
        // Load initial report
        loadReport();
    </script>
</body>
</html>