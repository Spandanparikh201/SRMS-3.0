<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['fullname'];
$school_id = $_SESSION['school_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Assignments - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="pdashboard.php">Dashboard</a></li>
            <li><a href="manage_teacher.php">Manage Teachers</a></li>
            <li><a href="teacher_class_assignments.php">Teacher Assignments</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Teacher Class & Subject Assignments</h1>
            <p>Assign teachers to classes and subjects.</p>
        </div>

        <div class="card">
            <h2>Create New Assignment</h2>
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

        <div class="card">
            <h2>Current Assignments</h2>
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
                                        WHERE t.school_id = ?";
                    $stmt = $conn->prepare($assignmentsQuery);
                    $stmt->bind_param("i", $school_id);
                    $stmt->execute();
                    $assignmentsResult = $stmt->get_result();
                    while ($assignment = $assignmentsResult->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($assignment['fullname']) ?></td>
                        <td><?= htmlspecialchars($assignment['class_name'] . ' ' . $assignment['division']) ?></td>
                        <td><?= htmlspecialchars($assignment['subject_name']) ?></td>
                        <td>
                            <button class="btn btn-danger" onclick="deleteAssignment(<?= $assignment['teacher_class_subject_id'] ?>)">Remove</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
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
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.error, 'error');
                }
            });
        });

        function deleteAssignment(id) {
            if (confirm('Are you sure you want to remove this assignment?')) {
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
                    }
                });
            }
        }
    </script>
</body>
</html>