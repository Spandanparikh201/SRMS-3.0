<?php
require_once 'db_connect.php';

// Create Exam table
$createExamTable = "
CREATE TABLE IF NOT EXISTS Exam (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(100) NOT NULL,
    exam_type ENUM('term1', 'term2', 'unit_test', 'final') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_marks DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    school_id INT NOT NULL,
    status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES School(school_id),
    UNIQUE KEY unique_exam (exam_name, school_id)
)";

if ($conn->query($createExamTable) === TRUE) {
    echo "Exam table created successfully<br>";
} else {
    echo "Error creating Exam table: " . $conn->error . "<br>";
}

// Insert sample exams
$sampleExams = "
INSERT IGNORE INTO Exam (exam_name, exam_type, start_date, end_date, total_marks, school_id, status) VALUES
('Mid Term Exam - Term 1', 'term1', '2025-02-15', '2025-02-25', 100.00, 1, 'upcoming'),
('Final Exam - Term 1', 'term1', '2025-04-01', '2025-04-10', 100.00, 1, 'upcoming'),
('Unit Test 1', 'unit_test', '2025-01-20', '2025-01-22', 50.00, 1, 'completed'),
('Mid Term Exam - Term 1', 'term1', '2025-02-15', '2025-02-25', 100.00, 2, 'upcoming'),
('Final Exam - Term 1', 'term1', '2025-04-01', '2025-04-10', 100.00, 2, 'upcoming')
";

if ($conn->query($sampleExams) === TRUE) {
    echo "Sample exams inserted successfully<br>";
} else {
    echo "Error inserting sample exams: " . $conn->error . "<br>";
}

echo "Database setup completed!";
?>