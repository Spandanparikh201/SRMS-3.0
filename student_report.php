<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$format = $_GET['format'] ?? 'pdf';
$exam_id = $_GET['exam_id'] ?? 'all';

// Get student information
$studentQuery = "SELECT s.*, u.fullname, c.class_name, c.division, sc.school_name 
                 FROM student s 
                 JOIN user u ON s.user_id = u.user_id
                 JOIN class c ON s.class_id = c.class_id 
                 JOIN school sc ON s.school_id = sc.school_id
                 WHERE s.user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get exam results
if ($exam_id === 'all') {
    $resultsQuery = "SELECT er.marks_obtained, er.total_marks, 
                            s.subject_name, e.exam_name,
                            ROUND((er.marks_obtained / er.total_marks * 100), 2) as percentage
                     FROM examresult er
                     JOIN subject s ON er.subject_id = s.subject_id
                     JOIN exam e ON er.exam_id = e.exam_id
                     WHERE er.student_id = ?
                     ORDER BY e.exam_name, s.subject_name";
    $stmt = $conn->prepare($resultsQuery);
    $stmt->bind_param("i", $student['student_id']);
} else {
    $resultsQuery = "SELECT er.marks_obtained, er.total_marks, 
                            s.subject_name, e.exam_name,
                            ROUND((er.marks_obtained / er.total_marks * 100), 2) as percentage
                     FROM examresult er
                     JOIN subject s ON er.subject_id = s.subject_id
                     JOIN exam e ON er.exam_id = e.exam_id
                     WHERE er.student_id = ? AND er.exam_id = ?
                     ORDER BY s.subject_name";
    $stmt = $conn->prepare($resultsQuery);
    $stmt->bind_param("ii", $student['student_id'], $exam_id);
}
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($format === 'csv') {
    header('Content-Type: text/csv');
    $filename = 'student_report_' . $student['roll_number'] . ($exam_id !== 'all' ? '_exam_' . $exam_id : '') . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Student info
    fputcsv($output, ['Student Report']);
    fputcsv($output, ['Name', $student['fullname']]);
    fputcsv($output, ['Roll Number', $student['roll_number']]);
    fputcsv($output, ['Class', $student['class_name'] . ' ' . $student['division']]);
    fputcsv($output, ['School', $student['school_name']]);
    fputcsv($output, []);
    
    // Results header
    fputcsv($output, ['Exam', 'Subject', 'Marks Obtained', 'Total Marks', 'Percentage']);
    
    // Results data
    foreach ($results as $result) {
        fputcsv($output, [
            $result['exam_name'],
            $result['subject_name'],
            $result['marks_obtained'],
            $result['total_marks'],
            $result['percentage'] . '%'
        ]);
    }
    
    fclose($output);
    exit();
}

// PDF format
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Report - <?php echo htmlspecialchars($student['fullname']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .student-info { margin-bottom: 20px; }
        .info-row { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Academic Report</h1>
        <h2><?php echo htmlspecialchars($student['school_name']); ?></h2>
    </div>
    
    <div class="student-info">
        <div class="info-row"><strong>Name:</strong> <?php echo htmlspecialchars($student['fullname']); ?></div>
        <div class="info-row"><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></div>
        <div class="info-row"><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['division']); ?></div>
        <div class="info-row"><strong>Report Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Exam</th>
                <th>Subject</th>
                <th>Marks Obtained</th>
                <th>Total Marks</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalObtained = 0;
            $totalMarks = 0;
            foreach ($results as $result): 
                $totalObtained += $result['marks_obtained'];
                $totalMarks += $result['total_marks'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                <td><?php echo $result['marks_obtained']; ?></td>
                <td><?php echo $result['total_marks']; ?></td>
                <td><?php echo $result['percentage']; ?>%</td>
            </tr>
            <?php endforeach; ?>
            <?php if ($totalMarks > 0): ?>
            <tr class="total-row">
                <td colspan="2">Overall Total</td>
                <td><?php echo $totalObtained; ?></td>
                <td><?php echo $totalMarks; ?></td>
                <td><?php echo round(($totalObtained / $totalMarks) * 100, 2); ?>%</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>