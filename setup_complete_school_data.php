<?php
require_once 'db_connect.php';

class SchoolDataSetup {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function setupCompleteData() {
        $this->conn->begin_transaction();
        
        try {
            // Clear existing data first
            $this->clearExistingData();
            
            $schools = $this->getSchools();
            
            foreach ($schools as $school) {
                $this->setupSchoolData($school['school_id']);
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Complete school data setup successfully'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function clearExistingData() {
        // Delete in correct order to respect foreign keys
        $this->conn->query("DELETE FROM examresult");
        $this->conn->query("DELETE FROM teacher_class_subject");
        $this->conn->query("DELETE FROM student");
        $this->conn->query("DELETE FROM teacher");
        $this->conn->query("DELETE FROM user WHERE role IN ('student', 'teacher')");
        $this->conn->query("DELETE FROM exam");
        $this->conn->query("DELETE FROM subject");
        $this->conn->query("DELETE FROM class");
    }
    
    private function setupSchoolData($school_id) {
        // Add classes
        $classes = $this->addClasses($school_id);
        
        // Add subjects
        $subjects = $this->addSubjects($school_id);
        
        // Add students
        $students = $this->addStudents($school_id, $classes);
        
        // Add exam results
        $this->addExamResults($school_id, $students, $subjects);
    }
    
    private function getSchools() {
        $result = $this->conn->query("SELECT school_id FROM school");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    private function addClasses($school_id) {
        $classes = [
            ['1', 'A'], ['2', 'A'], ['3', 'A'], ['4', 'A'], ['5', 'A'],
            ['6', 'A'], ['7', 'A'], ['8', 'A'], ['9', 'A'], ['10', 'A'],
            ['11', 'Science'], ['11', 'Commerce'], ['12', 'Science'], ['12', 'Commerce']
        ];
        
        $class_ids = [];
        
        foreach ($classes as $class) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO class (class_name, division, school_id) VALUES (?, ?, ?)");
            $stmt->execute([$class[0], $class[1], $school_id]);
            
            // Get class_id
            $stmt = $this->conn->prepare("SELECT class_id FROM class WHERE class_name = ? AND division = ? AND school_id = ?");
            $stmt->execute([$class[0], $class[1], $school_id]);
            $class_ids[] = $stmt->get_result()->fetch_assoc()['class_id'];
        }
        
        return $class_ids;
    }
    
    private function addSubjects($school_id) {
        $subjects = [
            // Primary (1-5)
            'English', 'Mathematics', 'Science', 'Social Studies', 'Hindi',
            // Secondary (6-10)
            'Physics', 'Chemistry', 'Biology', 'History', 'Geography', 'Computer Science',
            // Higher Secondary Science (11-12)
            'Advanced Physics', 'Advanced Chemistry', 'Advanced Biology', 'Advanced Mathematics',
            // Higher Secondary Commerce (11-12)
            'Accountancy', 'Business Studies', 'Economics'
        ];
        
        $subject_ids = [];
        
        foreach ($subjects as $subject) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO subject (subject_name, school_id) VALUES (?, ?)");
            $stmt->execute([$subject, $school_id]);
            
            // Get subject_id
            $stmt = $this->conn->prepare("SELECT subject_id FROM subject WHERE subject_name = ? AND school_id = ?");
            $stmt->execute([$subject, $school_id]);
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $subject_ids[$subject] = $row['subject_id'];
            }
        }
        
        return $subject_ids;
    }
    
    private function addStudents($school_id, $class_ids) {
        $students = [];
        $names = [
            'Aarav Kumar', 'Vivaan Singh', 'Aditya Sharma', 'Vihaan Gupta', 'Arjun Patel',
            'Sai Reddy', 'Reyansh Yadav', 'Ayaan Khan', 'Krishna Verma', 'Ishaan Jain',
            'Ananya Agarwal', 'Diya Mehta', 'Priya Nair', 'Kavya Iyer', 'Aadhya Mishra',
            'Saanvi Tiwari', 'Avni Pandey', 'Kiara Saxena', 'Myra Bansal', 'Anika Chopra',
            'Riya Malhotra', 'Sia Kapoor', 'Ira Sinha', 'Pihu Bhatt'
        ];
        
        // Get school name for roll number prefix
        $stmt = $this->conn->prepare("SELECT school_name FROM school WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $school_name = $stmt->get_result()->fetch_assoc()['school_name'];
        $school_prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $school_name), 0, 3));
        
        foreach ($class_ids as $class_id) {
            // Get class info for roll number
            $stmt = $this->conn->prepare("SELECT class_name, division FROM class WHERE class_id = ?");
            $stmt->execute([$class_id]);
            $class_info = $stmt->get_result()->fetch_assoc();
            $class_prefix = $class_info['class_name'] . $class_info['division'];
            
            for ($i = 1; $i <= 14; $i++) {
                $name = $names[($i - 1) % count($names)];
                $roll_number = $school_prefix . $class_prefix . str_pad($i, 2, '0', STR_PAD_LEFT);
                $username = strtolower(str_replace(' ', '.', $name)) . '.' . $school_id . '.' . $class_id;
                
                // Add user
                $password = 'student123'; // Default password for all students
                $stmt = $this->conn->prepare("INSERT IGNORE INTO user (username, password, fullname, role, school_id) VALUES (?, ?, ?, 'student', ?)");
                $stmt->execute([$username, $password, $name, $school_id]);
                
                // Get user_id
                $stmt = $this->conn->prepare("SELECT user_id FROM user WHERE username = ?");
                $stmt->execute([$username]);
                $user_id = $stmt->get_result()->fetch_assoc()['user_id'];
                
                // Add student
                $stmt = $this->conn->prepare("INSERT IGNORE INTO student (roll_number, user_id, class_id, school_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$roll_number, $user_id, $class_id, $school_id]);
                
                // Get student_id
                $stmt = $this->conn->prepare("SELECT student_id FROM student WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $student_id = $stmt->get_result()->fetch_assoc()['student_id'];
                
                $students[] = ['student_id' => $student_id, 'class_id' => $class_id];
            }
        }
        
        return $students;
    }
    
    private function addExamResults($school_id, $students, $subjects) {
        // Get exams for this school
        $stmt = $this->conn->prepare("SELECT exam_id, total_marks FROM exam WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($exams)) {
            // Create a default exam
            $stmt = $this->conn->prepare("INSERT INTO exam (exam_name, exam_type, start_date, end_date, total_marks, school_id, status) VALUES ('Mid Term Exam', 'term1', '2025-02-15', '2025-02-25', 100.00, ?, 'completed')");
            $stmt->execute([$school_id]);
            $exam_id = $this->conn->insert_id;
            $exams = [['exam_id' => $exam_id, 'total_marks' => 100.00]];
        }
        
        foreach ($students as $student) {
            $class_subjects = $this->getSubjectsForClass($student['class_id'], $subjects);
            
            foreach ($exams as $exam) {
                foreach ($class_subjects as $subject_id) {
                    $marks = $this->generateRandomMarks($exam['total_marks']);
                    
                    $stmt = $this->conn->prepare("INSERT IGNORE INTO examresult (student_id, exam_id, subject_id, marks_obtained, total_marks) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$student['student_id'], $exam['exam_id'], $subject_id, $marks, $exam['total_marks']]);
                }
            }
        }
    }
    
    private function getSubjectsForClass($class_id, $subjects) {
        // Get class info
        $stmt = $this->conn->prepare("SELECT class_name, division FROM class WHERE class_id = ?");
        $stmt->execute([$class_id]);
        $class_info = $stmt->get_result()->fetch_assoc();
        
        $class_name = $class_info['class_name'];
        $division = $class_info['division'];
        
        $class_subjects = [];
        
        if ($class_name >= 1 && $class_name <= 5) {
            // Primary classes
            $primary_subjects = ['English', 'Mathematics', 'Science', 'Social Studies', 'Hindi'];
            foreach ($primary_subjects as $subject) {
                if (isset($subjects[$subject])) {
                    $class_subjects[] = $subjects[$subject];
                }
            }
        } elseif ($class_name >= 6 && $class_name <= 10) {
            // Secondary classes
            $secondary_subjects = ['English', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'History', 'Geography', 'Computer Science'];
            foreach ($secondary_subjects as $subject) {
                if (isset($subjects[$subject])) {
                    $class_subjects[] = $subjects[$subject];
                }
            }
        } elseif ($class_name == 11 || $class_name == 12) {
            // Higher secondary
            $common_subjects = ['English', 'Mathematics'];
            foreach ($common_subjects as $subject) {
                if (isset($subjects[$subject])) {
                    $class_subjects[] = $subjects[$subject];
                }
            }
            
            if ($division == 'Science') {
                $science_subjects = ['Advanced Physics', 'Advanced Chemistry', 'Advanced Biology', 'Advanced Mathematics'];
                foreach ($science_subjects as $subject) {
                    if (isset($subjects[$subject])) {
                        $class_subjects[] = $subjects[$subject];
                    }
                }
            } elseif ($division == 'Commerce') {
                $commerce_subjects = ['Accountancy', 'Business Studies', 'Economics'];
                foreach ($commerce_subjects as $subject) {
                    if (isset($subjects[$subject])) {
                        $class_subjects[] = $subjects[$subject];
                    }
                }
            }
        }
        
        return $class_subjects;
    }
    
    private function generateRandomMarks($totalMarks) {
        $percentage = mt_rand(35, 95); // 35% to 95%
        return round(($percentage / 100) * $totalMarks, 2);
    }
}

// Handle web interface
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

$setup = new SchoolDataSetup($conn);
$action = $_GET['action'] ?? '';

if ($action === 'setup') {
    $result = $setup->setupCompleteData();
    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Complete School Data - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <div class="container">
        <div class="welcome-banner">
            <h1>🏫 Setup Complete School Data</h1>
            <p>Add classes 1-12, students, subjects, and exam results for all schools</p>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div class="card">
            <h2>📚 Complete Data Setup</h2>
            <p>This will add:</p>
            <ul>
                <li><strong>Classes:</strong> 1A to 10A, 11 Science/Commerce, 12 Science/Commerce</li>
                <li><strong>Students:</strong> 14 students per class with realistic names and unique roll numbers</li>
                <li><strong>Login Credentials:</strong> Username format: firstname.lastname.schoolid.classid | Password: student123</li>
                <li><strong>Subjects:</strong> Age-appropriate subjects for each class level</li>
                <li><strong>Exam Results:</strong> Random marks (35-95%) for all students</li>
            </ul>
            
            <div class="warning" style="background: #e74c3c; color: white; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                <strong>⚠️ Warning:</strong> This will REPLACE all existing classes, students, subjects, and results with new data using proper roll numbers.
            </div>
            
            <button onclick="setupData()" class="btn">🚀 Setup Complete Data</button>
        </div>
    </div>

    <script>
        function setupData() {
            if (!confirm('Setup complete school data? This will add classes, students, subjects, and results for all schools.')) return;
            
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '⏳ Setting up data...';
            
            fetch('setup_complete_school_data.php?action=setup')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Error: ' + data.error);
                }
                btn.disabled = false;
                btn.textContent = '🚀 Setup Complete Data';
            })
            .catch(error => {
                alert('❌ Network error: ' + error.message);
                btn.disabled = false;
                btn.textContent = '🚀 Setup Complete Data';
            });
        }
    </script>
</body>
</html>