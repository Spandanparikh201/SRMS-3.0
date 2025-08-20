<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit();
}

$school_id = $_SESSION['school_id'];
$exam_id = $_GET['exam_id'] ?? '';

if (empty($exam_id)) {
    header("Location: manage_exams.php");
    exit();
}

// Get exam details
$examQuery = "SELECT * FROM Exam WHERE exam_id = ? AND school_id = ?";
$stmt = $conn->prepare($examQuery);
$stmt->bind_param("ii", $exam_id, $school_id);
$stmt->execute();
$examResult = $stmt->get_result();
$exam = $examResult->fetch_assoc();

if (!$exam) {
    header("Location: manage_exams.php");
    exit();
}

// Get all students in the school with their classes
$studentsQuery = "
    SELECT s.student_id, s.roll_number, u.fullname, c.class_name, c.division, c.class_id
    FROM Student s
    JOIN User u ON s.user_id = u.user_id
    JOIN Class c ON s.class_id = c.class_id
    WHERE s.school_id = ?
    ORDER BY c.class_name, c.division, s.roll_number
";
$stmt = $conn->prepare($studentsQuery);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$studentsResult = $stmt->get_result();

// Get subjects for the school
$subjectsQuery = "SELECT * FROM Subject WHERE school_id = ? ORDER BY subject_name";
$stmt = $conn->prepare($subjectsQuery);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$subjectsResult = $stmt->get_result();
$subjects = [];
while ($subject = $subjectsResult->fetch_assoc()) {
    $subjects[] = $subject;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Exam Marks - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="pdashboard.php">Dashboard</a></li>
            <li><a href="manage_exams.php">Manage Exams</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Enter Marks: <?php echo htmlspecialchars($exam['exam_name']); ?></h1>
            <p>Enter marks for all students in this examination</p>
        </div>

        <div class="card">
            <h2>Select Subject</h2>
            <div class="form-group">
                <select id="subjectSelect" class="form-control">
                    <option value="">Select Subject</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button onclick="loadStudents()" class="btn">Load Students</button>
        </div>

        <div id="marksCard" class="card hidden">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2>Student Marks Entry</h2>
                <button onclick="saveAllMarks()" class="btn">Save All Marks</button>
            </div>
            <div id="studentsTable"></div>
        </div>
    </div>

    <script>
        const examId = <?php echo $exam_id; ?>;
        const totalMarks = <?php echo $exam['total_marks']; ?>;
        let currentSubjectId = null;
        let studentsData = [];

        function loadStudents() {
            const subjectId = document.getElementById('subjectSelect').value;
            if (!subjectId) {
                alert('Please select a subject');
                return;
            }

            currentSubjectId = subjectId;
            
            fetch(`get_exam_students.php?exam_id=${examId}&subject_id=${subjectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    studentsData = data.students;
                    displayStudents(data.students);
                } else {
                    alert(data.error || 'Error loading students');
                }
            });
        }

        function displayStudents(students) {
            let tableHTML = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            students.forEach(student => {
                tableHTML += `
                    <tr>
                        <td>${student.roll_number}</td>
                        <td>${student.fullname}</td>
                        <td>${student.class_name} ${student.division}</td>
                        <td>
                            <input type="number" 
                                   id="marks_${student.student_id}" 
                                   class="form-control" 
                                   value="${student.marks_obtained || '0'}" 
                                   min="0" 
                                   max="${totalMarks}"
                                   step="0.01"
                                   style="width: 100px;">
                        </td>
                        <td>${totalMarks}</td>
                    </tr>
                `;
            });
            
            tableHTML += '</tbody></table>';
            
            document.getElementById('studentsTable').innerHTML = tableHTML;
            document.getElementById('marksCard').classList.remove('hidden');
        }

        function saveAllMarks() {
            if (!currentSubjectId) {
                alert('Please select a subject first');
                return;
            }

            const marksData = [];
            studentsData.forEach(student => {
                const marksInput = document.getElementById(`marks_${student.student_id}`);
                const marks = marksInput.value;
                
                if (marks !== '') {
                    marksData.push({
                        student_id: student.student_id,
                        class_id: student.class_id,
                        subject_id: currentSubjectId,
                        exam_id: examId,
                        marks_obtained: parseFloat(marks),
                        total_marks: totalMarks
                    });
                }
            });

            if (marksData.length === 0) {
                alert('Please enter marks for at least one student');
                return;
            }

            fetch('save_exam_marks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'bulk_save',
                    marks: marksData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Marks saved successfully!');
                    loadStudents(); // Reload to show updated marks
                } else {
                    alert(data.error || 'Error saving marks');
                }
            });
        }
    </script>
</body>
</html>