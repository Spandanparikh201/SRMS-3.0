<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'principal')) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

// Get teacher information
$teacher_id = null;
if ($_SESSION['role'] === 'teacher') {
    $teacherQuery = "SELECT * FROM Teacher WHERE user_id = ?";
    $stmt = $conn->prepare($teacherQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $teacherResult = $stmt->get_result();
    $teacherInfo = $teacherResult->fetch_assoc();
    $teacher_id = $teacherInfo['teacher_id'];
}

// Get assigned classes and subjects
if ($_SESSION['role'] === 'teacher') {
    $assignmentsQuery = "
        SELECT tcs.teacher_class_subject_id, c.class_id, c.class_name, c.division, s.subject_id, s.subject_name
        FROM Teacher_Class_Subject tcs
        JOIN Class c ON tcs.class_id = c.class_id
        JOIN Subject s ON tcs.subject_id = s.subject_id
        WHERE tcs.teacher_id = ?
        ORDER BY c.class_name, c.division, s.subject_name
    ";
    $stmt = $conn->prepare($assignmentsQuery);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $assignmentsResult = $stmt->get_result();
} else {
    // For principals, show all classes and subjects in their school
    $assignmentsQuery = "
        SELECT c.class_id, c.class_name, c.division, s.subject_id, s.subject_name
        FROM Class c
        CROSS JOIN Subject s
        WHERE c.school_id = ? AND s.school_id = ?
        ORDER BY c.class_name, c.division, s.subject_name
    ";
    $stmt = $conn->prepare($assignmentsQuery);
    $stmt->bind_param("ii", $school_id, $school_id);
    $stmt->execute();
    $assignmentsResult = $stmt->get_result();
}

$username = $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Marks - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="tdashboard.php">Dashboard</a></li>
            <li><a href="save_marks.php">Enter Marks</a></li>
            <li><a href="upload_marks.php">Upload Marks</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Enter Marks</h1>
            <p>Record student marks for your assigned classes and subjects.</p>
        </div>

        <div class="card">
            <h2>Select Class & Subject</h2>
            <form id="selectionForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Class</label>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php 
                            $classes = [];
                            while ($row = $assignmentsResult->fetch_assoc()) {
                                $class_key = $row['class_id'];
                                if (!isset($classes[$class_key])) {
                                    $classes[$class_key] = $row;
                                    echo "<option value='{$row['class_id']}'>{$row['class_name']} {$row['division']}</option>";
                                }
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
                <button type="submit" class="btn">Load Students</button>
            </form>
        </div>

        <div id="studentsCard" class="card hidden">
            <h2>Enter Marks</h2>
            <div id="studentsTable"></div>
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
                assignments.forEach(assignment => {
                    if (assignment.class_id == classId) {
                        subjectSelect.innerHTML += `<option value="${assignment.subject_id}">${assignment.subject_name}</option>`;
                    }
                });
            }
        });

        document.getElementById('selectionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const classId = document.getElementById('class_id').value;
            const subjectId = document.getElementById('subject_id').value;
            const examTerm = document.getElementById('exam_term').value;
            
            if (!classId || !subjectId || !examTerm) {
                showNotification('Please select all fields', 'error');
                return;
            }
            
            loadStudents(classId, subjectId, examTerm);
        });

        function loadStudents(classId, subjectId, examTerm) {
            fetch(`get_students.php?class_id=${classId}&subject_id=${subjectId}&exam_term=${examTerm}`)
            .then(response => response.json())
            .then(students => {
                let tableHTML = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Roll Number</th>
                                <th>Student Name</th>
                                <th>Marks Obtained</th>
                                <th>Total Marks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                students.forEach(student => {
                    tableHTML += `
                        <tr>
                            <td>${student.roll_number}</td>
                            <td>${student.fullname}</td>
                            <td><input type="number" id="marks_${student.student_id}" class="form-control" value="${student.marks_obtained || ''}" min="0" step="0.01"></td>
                            <td><input type="number" id="total_${student.student_id}" class="form-control" value="${student.total_subject_marks || '100'}" min="1" step="0.01"></td>
                            <td><button onclick="saveMarks(${student.student_id}, ${classId}, ${subjectId}, '${examTerm}')" class="btn">Save</button></td>
                        </tr>
                    `;
                });
                
                tableHTML += '</tbody></table>';
                
                document.getElementById('studentsTable').innerHTML = tableHTML;
                document.getElementById('studentsCard').classList.remove('hidden');
            })
            .catch(error => {
                showNotification('Error loading students', 'error');
            });
        }

        function saveMarks(studentId, classId, subjectId, examTerm) {
            const marksObtained = document.getElementById(`marks_${studentId}`).value;
            const totalMarks = document.getElementById(`total_${studentId}`).value;
            
            if (!marksObtained || !totalMarks) {
                showNotification('Please enter both marks obtained and total marks', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('student_id', studentId);
            formData.append('class_id', classId);
            formData.append('subject_id', subjectId);
            formData.append('exam_term', examTerm);
            formData.append('marks_obtained', marksObtained);
            formData.append('total_marks', totalMarks);
            
            fetch('update_mark.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.success, 'success');
                } else {
                    showNotification(data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving marks', 'error');
            });
        }
    </script>
</body>
</html>