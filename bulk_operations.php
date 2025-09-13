<?php
require_once 'db_connect.php';
require_once 'includes/csrf.php';
require_once 'includes/validation.php';
require_once 'includes/audit.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'principal'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle bulk operations
if ($_POST) {
    requireCSRF();
    
    $operation = $_POST['operation'] ?? '';
    
    switch ($operation) {
        case 'bulk_student_import':
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $result = importStudentsFromCSV($_FILES['csv_file']);
                if ($result['success']) {
                    $message = "Successfully imported {$result['count']} students";
                    logActivity('BULK_STUDENT_IMPORT', 'student', null, "Imported {$result['count']} students");
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        case 'bulk_export':
            $type = $_POST['export_type'] ?? '';
            exportData($type);
            break;
    }
}

function importStudentsFromCSV($file) {
    global $conn;
    
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        return ['success' => false, 'error' => 'Could not read file'];
    }
    
    $count = 0;
    $header = fgetcsv($handle); // Skip header row
    
    while (($data = fgetcsv($handle)) !== FALSE) {
        // Assuming CSV format: fullname, username, password, roll_number, class_id
        $fullname = sanitizeInput($data[0]);
        $username = sanitizeInput($data[1]);
        $password = $data[2];
        $roll_number = sanitizeInput($data[3]);
        $class_id = (int)$data[4];
        
        // Create user first
        $userStmt = $conn->prepare("INSERT INTO user (username, password, fullname, role, school_id) VALUES (?, ?, ?, 'student', ?)");
        $userStmt->bind_param("sssi", $username, $password, $fullname, $_SESSION['school_id']);
        
        if ($userStmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Create student record
            $studentStmt = $conn->prepare("INSERT INTO student (user_id, roll_number, class_id, school_id) VALUES (?, ?, ?, ?)");
            $studentStmt->bind_param("isii", $user_id, $roll_number, $class_id, $_SESSION['school_id']);
            $studentStmt->execute();
            
            $count++;
        }
    }
    
    fclose($handle);
    return ['success' => true, 'count' => $count];
}

function exportData($type) {
    global $conn;
    
    $filename = $type . '_export_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    switch ($type) {
        case 'students':
            fputcsv($output, ['Name', 'Username', 'Roll Number', 'Class', 'School']);
            $query = "SELECT u.fullname, u.username, s.roll_number, c.class_name, sc.school_name 
                     FROM student s 
                     JOIN user u ON s.user_id = u.user_id 
                     JOIN class c ON s.class_id = c.class_id 
                     JOIN school sc ON s.school_id = sc.school_id";
            break;
            
        case 'teachers':
            fputcsv($output, ['Name', 'Username', 'Subject', 'School']);
            $query = "SELECT u.fullname, u.username, sub.subject_name, sc.school_name 
                     FROM teacher t 
                     JOIN user u ON t.user_id = u.user_id 
                     LEFT JOIN teacher_class_subject tcs ON t.teacher_id = tcs.teacher_id
                     LEFT JOIN subject sub ON tcs.subject_id = sub.subject_id
                     JOIN school sc ON t.school_id = sc.school_id";
            break;
    }
    
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    logActivity('BULK_EXPORT', $type, null, "Exported $type data");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Operations - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="<?php echo $_SESSION['role']; ?>dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="card">
            <h2>Bulk Operations</h2>
            
            <?php if ($message): ?>
                <div class="alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <div class="tab active" onclick="showSection('import')">Import Data</div>
                <div class="tab" onclick="showSection('export')">Export Data</div>
            </div>
            
            <div id="importSection" class="section active">
                <h3>Import Students from CSV</h3>
                <form method="POST" enctype="multipart/form-data">
                    <?php echo getCSRFField(); ?>
                    <input type="hidden" name="operation" value="bulk_student_import">
                    
                    <div class="form-group">
                        <label>CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <small>Format: Name, Username, Password, Roll Number, Class ID</small>
                    </div>
                    
                    <button type="submit" class="btn">Import Students</button>
                </form>
            </div>
            
            <div id="exportSection" class="section">
                <h3>Export Data</h3>
                <form method="POST">
                    <?php echo getCSRFField(); ?>
                    <input type="hidden" name="operation" value="bulk_export">
                    
                    <div class="form-group">
                        <label>Export Type</label>
                        <select name="export_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="students">Students</option>
                            <option value="teachers">Teachers</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Export Data</button>
                </form>
            </div>
        </div>
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