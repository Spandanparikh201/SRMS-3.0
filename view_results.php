<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit();
}

$exam_term = $_GET['exam_term'] ?? '';
if (!$exam_term) {
    echo "<script>alert('Invalid exam term'); window.close();</script>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Results - <?= ucwords(str_replace('_', ' ', $exam_term)) ?></title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <div class="container">
        <h2><?= ucwords(str_replace('_', ' ', $exam_term)) ?> Results</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Marks</th>
                    <th>Total</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $resultsQuery = "SELECT u.fullname, c.class_name, c.division, sub.subject_name, 
                                r.marks_obtained, r.total_subject_marks,
                                ROUND((r.marks_obtained/r.total_subject_marks)*100, 2) as percentage
                                FROM Result r 
                                JOIN Student s ON r.student_id = s.student_id 
                                JOIN User u ON s.user_id = u.user_id
                                JOIN Class c ON r.class_id = c.class_id
                                JOIN Subject sub ON r.subject_id = sub.subject_id
                                WHERE s.school_id = ? AND r.exam_term = ?
                                ORDER BY u.fullname, sub.subject_name";
                $stmt = $conn->prepare($resultsQuery);
                $stmt->bind_param("is", $_SESSION['school_id'], $exam_term);
                $stmt->execute();
                $resultsResult = $stmt->get_result();
                while ($result = $resultsResult->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($result['fullname']) ?></td>
                    <td><?= htmlspecialchars($result['class_name'] . ' ' . $result['division']) ?></td>
                    <td><?= htmlspecialchars($result['subject_name']) ?></td>
                    <td><?= $result['marks_obtained'] ?></td>
                    <td><?= $result['total_subject_marks'] ?></td>
                    <td><?= $result['percentage'] ?>%</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>