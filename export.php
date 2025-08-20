<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';
$class_id = $_GET['class_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$term = $_GET['term'] ?? '';

switch ($type) {
    case 'results':
        $query = "SELECT s.roll_number, u.fullname, c.class_name, c.division, sub.subject_name, 
                         e.exam_name, er.marks_obtained, er.total_marks,
                         ROUND((er.marks_obtained / er.total_marks * 100), 2) as percentage
                  FROM ExamResult er
                  JOIN Student s ON er.student_id = s.student_id
                  JOIN User u ON s.user_id = u.user_id
                  JOIN Class c ON s.class_id = c.class_id
                  JOIN Subject sub ON er.subject_id = sub.subject_id
                  JOIN Exam e ON er.exam_id = e.exam_id
                  WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Filter by school for principals
        if ($_SESSION['role'] === 'principal') {
            $query .= " AND s.school_id = ?";
            $params[] = $_SESSION['school_id'];
            $types .= "i";
        }
        
        if ($class_id) {
            $query .= " AND s.class_id = ?";
            $params[] = $class_id;
            $types .= "i";
        }
        if ($subject_id) {
            $query .= " AND er.subject_id = ?";
            $params[] = $subject_id;
            $types .= "i";
        }
        if ($term) {
            $query .= " AND e.exam_type = ?";
            $params[] = $term;
            $types .= "s";
        }
        
        $query .= " ORDER BY c.class_name, c.division, s.roll_number";
        
        $stmt = $conn->prepare($query);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        if ($format === 'csv') {
            exportCSV($data, 'results_export.csv');
        } else {
            exportPDF($data, 'Results Report');
        }
        break;
        
    case 'students':
        $query = "SELECT s.roll_number, u.fullname, c.class_name, c.division, sch.school_name
                  FROM Student s
                  JOIN User u ON s.user_id = u.user_id
                  JOIN Class c ON s.class_id = c.class_id
                  JOIN School sch ON s.school_id = sch.school_id
                  WHERE s.school_id = ?
                  ORDER BY c.class_name, c.division, s.roll_number";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['school_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        if ($format === 'csv') {
            exportCSV($data, 'students_export.csv');
        } else {
            exportPDF($data, 'Students Report');
        }
        break;
        
    default:
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
}

function exportCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        // Write header
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit();
}

function exportPDF($data, $title) {
    // Simple HTML to PDF conversion
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <title>' . $title . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #2563eb; text-align: center; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            tr:nth-child(even) { background-color: #f9f9f9; }
        </style>
    </head>
    <body>
        <h1>' . $title . '</h1>
        <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    
    if (!empty($data)) {
        $html .= '<table><thead><tr>';
        
        // Table headers
        foreach (array_keys($data[0]) as $header) {
            $html .= '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        
        // Table data
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    } else {
        $html .= '<p>No data available.</p>';
    }
    
    $html .= '</body></html>';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $title)) . '.pdf"');
    
    // For production, use a proper PDF library like TCPDF or DOMPDF
    // This is a simple HTML output for demonstration
    echo $html;
    exit();
}
?>