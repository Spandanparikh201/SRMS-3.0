-- SRMS Database Creation Script
CREATE DATABASE IF NOT EXISTS srms_db;
USE srms_db;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- 1. School Table
CREATE TABLE IF NOT EXISTS School (
    school_id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    school_address VARCHAR(255),
    UNIQUE (school_name)
);

-- 2. User Table
CREATE TABLE IF NOT EXISTS User (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'principal', 'admin') NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (school_id) REFERENCES School(school_id)
);

-- 3. Class Table
CREATE TABLE IF NOT EXISTS Class (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(10) NOT NULL,
    division VARCHAR(10) NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (school_id) REFERENCES School(school_id),
    UNIQUE (class_name, division, school_id)
);

-- 4. Subject Table
CREATE TABLE IF NOT EXISTS Subject (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(255) NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (school_id) REFERENCES School(school_id),
    UNIQUE (subject_name, school_id)
);

-- 5. Student Table
CREATE TABLE IF NOT EXISTS Student (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    roll_number VARCHAR(50) NOT NULL,
    user_id INT NOT NULL UNIQUE,
    class_id INT NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES User(user_id),
    FOREIGN KEY (class_id) REFERENCES Class(class_id),
    FOREIGN KEY (school_id) REFERENCES School(school_id),
    UNIQUE (roll_number, class_id, school_id)
);

-- 6. Teacher Table
CREATE TABLE IF NOT EXISTS Teacher (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    school_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES User(user_id),
    FOREIGN KEY (school_id) REFERENCES School(school_id)
);

-- 7. Teacher_Class_Subject Table
CREATE TABLE IF NOT EXISTS Teacher_Class_Subject (
    teacher_class_subject_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES Teacher(teacher_id),
    FOREIGN KEY (class_id) REFERENCES Class(class_id),
    FOREIGN KEY (subject_id) REFERENCES Subject(subject_id),
    UNIQUE (teacher_id, class_id, subject_id)
);

-- 8. Result Table
CREATE TABLE IF NOT EXISTS Result (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_term ENUM('term1', 'term2') NOT NULL,
    marks_obtained DECIMAL(5, 2) NOT NULL CHECK (marks_obtained >= 0),
    total_subject_marks DECIMAL(5, 2) NOT NULL CHECK (total_subject_marks > 0),
    recorded_by_teacher_id INT,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES Student(student_id),
    FOREIGN KEY (class_id) REFERENCES Class(class_id),
    FOREIGN KEY (subject_id) REFERENCES Subject(subject_id),
    FOREIGN KEY (recorded_by_teacher_id) REFERENCES Teacher(teacher_id),
    UNIQUE (student_id, class_id, subject_id, exam_term)
);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- SRMS Database Complete Data Insertion Script
-- Clear existing data (with foreign key checks disabled)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE Result;
TRUNCATE TABLE Teacher_Class_Subject;
TRUNCATE TABLE Teacher;
TRUNCATE TABLE Student;
TRUNCATE TABLE Subject;
TRUNCATE TABLE Class;
TRUNCATE TABLE User;
TRUNCATE TABLE School;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Schools Data
INSERT INTO School (school_name, school_address) VALUES
('Delhi Public School, Hyderabad', 'Road No. 12, Banjara Hills, Hyderabad, Telangana 500034'),
('Kendriya Vidyalaya, Bangalore', 'Old Airport Road, Vimanapura, Bangalore, Karnataka 560017'),
('St. Xavier\'s Collegiate School, Kolkata', '30 Park Street, Kolkata, West Bengal 700016'),
('DAV Public School, Mumbai', 'Linking Road, Bandra West, Mumbai, Maharashtra 400050'),
('Ryan International School, Delhi', 'Sector 25, Rohini, Delhi 110085');

-- 2. Users Data (Admin, Principals, Teachers, Students)
INSERT INTO User (username, password, fullname, role, school_id) VALUES
-- Admin
('srms.admin', 'admin', 'SRMS Administrator', 'admin', 1),

-- Principals
('priya.principal', 'principalpass123', 'Dr. Priya Sharma', 'principal', 1),
('sanjay.principal', 'principalpass123', 'Mr. Sanjay Kumar', 'principal', 2),
('thomas.principal', 'principalpass123', 'Fr. Thomas Varghese', 'principal', 3),
('meera.principal', 'principalpass123', 'Mrs. Meera Patel', 'principal', 4),
('rajesh.principal', 'principalpass123', 'Dr. Rajesh Gupta', 'principal', 5),

-- Teachers - DPS Hyderabad
('anita.math', 'teacherpass123', 'Ms. Anita Reddy', 'teacher', 1),
('rajesh.sci', 'teacherpass123', 'Mr. Rajesh Singh', 'teacher', 1),
('kavya.eng', 'teacherpass123', 'Ms. Kavya Nair', 'teacher', 1),
('suresh.soc', 'teacherpass123', 'Mr. Suresh Rao', 'teacher', 1),
('deepa.hin', 'teacherpass123', 'Mrs. Deepa Sharma', 'teacher', 1),

-- Teachers - KV Bangalore
('pooja.eng', 'teacherpass123', 'Ms. Pooja Gupta', 'teacher', 2),
('ramesh.math', 'teacherpass123', 'Mr. Ramesh Kumar', 'teacher', 2),
('sunita.sci', 'teacherpass123', 'Mrs. Sunita Joshi', 'teacher', 2),
('vikram.hin', 'teacherpass123', 'Mr. Vikram Singh', 'teacher', 2),

-- Teachers - St. Xavier's Kolkata
('amit.hist', 'teacherpass123', 'Mr. Amit Verma', 'teacher', 3),
('rita.phy', 'teacherpass123', 'Mrs. Rita Das', 'teacher', 3),
('subhash.che', 'teacherpass123', 'Dr. Subhash Bose', 'teacher', 3),
('maya.bio', 'teacherpass123', 'Ms. Maya Chatterjee', 'teacher', 3),

-- Students - DPS Hyderabad (Class 10A & 10B)
('arjun.dps', 'studentpass123', 'Arjun Singh', 'student', 1),
('riya.dps', 'studentpass123', 'Riya Sharma', 'student', 1),
('karthik.dps', 'studentpass123', 'Karthik Reddy', 'student', 1),
('priya.dps', 'studentpass123', 'Priya Nair', 'student', 1),
('rohit.dps', 'studentpass123', 'Rohit Kumar', 'student', 1),
('sneha.dps', 'studentpass123', 'Sneha Patel', 'student', 1),
('arun.dps', 'studentpass123', 'Arun Rao', 'student', 1),
('divya.dps', 'studentpass123', 'Divya Singh', 'student', 1),

-- Students - KV Bangalore (Class 9A & 9B)
('kiran.kv', 'studentpass123', 'Kiran Rao', 'student', 2),
('sara.kv', 'studentpass123', 'Sara Khan', 'student', 2),
('manish.kv', 'studentpass123', 'Manish Gupta', 'student', 2),
('ananya.kv', 'studentpass123', 'Ananya Joshi', 'student', 2),
('rahul.kv', 'studentpass123', 'Rahul Singh', 'student', 2),
('kavitha.kv', 'studentpass123', 'Kavitha Kumar', 'student', 2),

-- Students - St. Xavier's Kolkata (Class 11 Science & Commerce)
('vivek.sx', 'studentpass123', 'Vivek Das', 'student', 3),
('isha.sx', 'studentpass123', 'Isha Bose', 'student', 3),
('aritra.sx', 'studentpass123', 'Aritra Ghosh', 'student', 3),
('shreya.sx', 'studentpass123', 'Shreya Mukherjee', 'student', 3),
('sourav.sx', 'studentpass123', 'Sourav Roy', 'student', 3),
('ritu.sx', 'studentpass123', 'Ritu Chatterjee', 'student', 3);

-- 3. Classes Data
INSERT INTO Class (class_name, division, school_id) VALUES
-- DPS Hyderabad
('10', 'A', 1),
('10', 'B', 1),
('9', 'A', 1),
('9', 'B', 1),

-- KV Bangalore
('9', 'A', 2),
('9', 'B', 2),
('8', 'A', 2),
('8', 'B', 2),

-- St. Xavier's Kolkata
('11', 'Science', 3),
('11', 'Commerce', 3),
('12', 'Science', 3),
('12', 'Commerce', 3),

-- DAV Mumbai
('10', 'A', 4),
('10', 'B', 4),

-- Ryan Delhi
('9', 'A', 5),
('9', 'B', 5);

-- 4. Subjects Data
INSERT INTO Subject (subject_name, school_id) VALUES
-- DPS Hyderabad Subjects
('Mathematics', 1),
('Science', 1),
('English', 1),
('Social Science', 1),
('Hindi', 1),
('Computer Science', 1),

-- KV Bangalore Subjects
('Mathematics', 2),
('Science', 2),
('English', 2),
('Hindi', 2),
('Sanskrit', 2),
('Social Studies', 2),

-- St. Xavier's Kolkata Subjects
('Physics', 3),
('Chemistry', 3),
('Biology', 3),
('Mathematics', 3),
('English', 3),
('History', 3),
('Economics', 3),
('Accountancy', 3),

-- DAV Mumbai Subjects
('Mathematics', 4),
('Science', 4),
('English', 4),
('Marathi', 4),
('Hindi', 4),

-- Ryan Delhi Subjects
('Mathematics', 5),
('Science', 5),
('English', 5),
('Hindi', 5),
('Social Science', 5);

-- 5. Students Data
INSERT INTO Student (roll_number, user_id, class_id, school_id) VALUES
-- DPS Hyderabad Students
('DPS001', 21, 1, 1),   -- Arjun Singh - Class 10A
('DPS002', 22, 1, 1),   -- Riya Sharma - Class 10A
('DPS003', 23, 1, 1),   -- Karthik Reddy - Class 10A
('DPS004', 24, 1, 1),   -- Priya Nair - Class 10A
('DPS005', 25, 2, 1),   -- Rohit Kumar - Class 10B
('DPS006', 26, 2, 1),   -- Sneha Patel - Class 10B
('DPS007', 27, 2, 1),   -- Arun Rao - Class 10B
('DPS008', 28, 2, 1),   -- Divya Singh - Class 10B

-- KV Bangalore Students
('KV001', 29, 5, 2),    -- Kiran Rao - Class 9A
('KV002', 30, 5, 2),    -- Sara Khan - Class 9A
('KV003', 31, 5, 2),    -- Manish Gupta - Class 9A
('KV004', 32, 6, 2),    -- Ananya Joshi - Class 9B
('KV005', 33, 6, 2),    -- Rahul Singh - Class 9B
('KV006', 34, 6, 2),    -- Kavitha Kumar - Class 9B

-- St. Xavier's Kolkata Students
('SX001', 35, 9, 3),    -- Vivek Das - Class 11 Science
('SX002', 36, 9, 3),    -- Isha Bose - Class 11 Science
('SX003', 37, 9, 3),    -- Aritra Ghosh - Class 11 Science
('SX004', 38, 10, 3),   -- Shreya Mukherjee - Class 11 Commerce
('SX005', 39, 10, 3),   -- Sourav Roy - Class 11 Commerce
('SX006', 40, 10, 3);   -- Ritu Chatterjee - Class 11 Commerce

-- 6. Teachers Data
INSERT INTO Teacher (user_id, school_id) VALUES
-- DPS Hyderabad Teachers
(7, 1),   -- Anita Reddy
(8, 1),   -- Rajesh Singh
(9, 1),   -- Kavya Nair
(10, 1),  -- Suresh Rao
(11, 1),  -- Deepa Sharma

-- KV Bangalore Teachers
(12, 2),  -- Pooja Gupta
(13, 2),  -- Ramesh Kumar
(14, 2),  -- Sunita Joshi
(15, 2),  -- Vikram Singh

-- St. Xavier's Kolkata Teachers
(16, 3),  -- Amit Verma
(17, 3),  -- Rita Das
(18, 3),  -- Subhash Bose
(19, 3);  -- Maya Chatterjee

-- 7. Teacher Class Subject Assignments
INSERT INTO Teacher_Class_Subject (teacher_id, class_id, subject_id) VALUES
-- DPS Hyderabad Assignments
(1, 1, 1), (1, 2, 1),  -- Anita teaches Math to 10A & 10B
(2, 1, 2), (2, 2, 2),  -- Rajesh teaches Science to 10A & 10B
(3, 1, 3), (3, 2, 3),  -- Kavya teaches English to 10A & 10B
(4, 1, 4), (4, 2, 4),  -- Suresh teaches Social Science to 10A & 10B
(5, 1, 5), (5, 2, 5),  -- Deepa teaches Hindi to 10A & 10B

-- KV Bangalore Assignments
(6, 5, 9), (6, 6, 9),   -- Pooja teaches English to 9A & 9B
(7, 5, 7), (7, 6, 7),   -- Ramesh teaches Math to 9A & 9B
(8, 5, 8), (8, 6, 8),   -- Sunita teaches Science to 9A & 9B
(9, 5, 10), (9, 6, 10), -- Vikram teaches Hindi to 9A & 9B

-- St. Xavier's Kolkata Assignments
(10, 9, 18),           -- Amit teaches History to 11 Science
(11, 9, 13),           -- Rita teaches Physics to 11 Science
(12, 9, 14),           -- Subhash teaches Chemistry to 11 Science
(13, 9, 15);           -- Maya teaches Biology to 11 Science

-- 8. Sample Results Data
INSERT INTO Result (student_id, class_id, subject_id, exam_term, marks_obtained, total_subject_marks, recorded_by_teacher_id) VALUES
-- DPS Hyderabad Results - Term 1
-- Arjun Singh (student_id: 1)
(1, 1, 1, 'term1', 85.00, 100.00, 1),  -- Math
(1, 1, 2, 'term1', 78.50, 100.00, 2),  -- Science
(1, 1, 3, 'term1', 92.00, 100.00, 3),  -- English
(1, 1, 4, 'term1', 88.00, 100.00, 4),  -- Social Science
(1, 1, 5, 'term1', 76.00, 100.00, 5),  -- Hindi

-- Riya Sharma (student_id: 2)
(2, 1, 1, 'term1', 90.50, 100.00, 1),  -- Math
(2, 1, 2, 'term1', 82.00, 100.00, 2),  -- Science
(2, 1, 3, 'term1', 95.00, 100.00, 3),  -- English
(2, 1, 4, 'term1', 91.00, 100.00, 4),  -- Social Science
(2, 1, 5, 'term1', 84.00, 100.00, 5),  -- Hindi

-- Karthik Reddy (student_id: 3)
(3, 1, 1, 'term1', 72.00, 100.00, 1),  -- Math
(3, 1, 2, 'term1', 69.50, 100.00, 2),  -- Science
(3, 1, 3, 'term1', 81.00, 100.00, 3),  -- English
(3, 1, 4, 'term1', 75.00, 100.00, 4),  -- Social Science
(3, 1, 5, 'term1', 78.00, 100.00, 5),  -- Hindi

-- DPS Hyderabad Results - Term 2
(1, 1, 1, 'term2', 90.00, 100.00, 1),  -- Arjun Math
(1, 1, 2, 'term2', 88.00, 100.00, 2),  -- Arjun Science
(2, 1, 1, 'term2', 95.00, 100.00, 1),  -- Riya Math
(2, 1, 2, 'term2', 89.00, 100.00, 2),  -- Riya Science

-- KV Bangalore Results - Term 1
-- Kiran Rao (student_id: 9)
(9, 5, 7, 'term1', 70.00, 80.00, 7),   -- Math
(9, 5, 8, 'term1', 65.00, 80.00, 8),   -- Science
(9, 5, 9, 'term1', 72.00, 80.00, 6),   -- English
(9, 5, 10, 'term1', 68.00, 80.00, 9),  -- Hindi

-- Sara Khan (student_id: 10)
(10, 5, 7, 'term1', 75.00, 80.00, 7),   -- Math
(10, 5, 8, 'term1', 71.00, 80.00, 8),   -- Science
(10, 5, 9, 'term1', 78.00, 80.00, 6),   -- English
(10, 5, 10, 'term1', 74.00, 80.00, 9),  -- Hindi

-- St. Xavier's Kolkata Results - Term 1
-- Vivek Das (student_id: 15)
(15, 9, 13, 'term1', 75.00, 100.00, 11), -- Physics
(15, 9, 14, 'term1', 68.00, 100.00, 12), -- Chemistry
(15, 9, 15, 'term1', 82.00, 100.00, 13), -- Biology
(15, 9, 16, 'term1', 79.00, 100.00, 1),  -- Math

-- Isha Bose (student_id: 16)
(16, 9, 13, 'term1', 88.00, 100.00, 11), -- Physics
(16, 9, 14, 'term1', 85.00, 100.00, 12), -- Chemistry
(16, 9, 15, 'term1', 91.00, 100.00, 13), -- Biology
(16, 9, 16, 'term1', 87.00, 100.00, 1);  -- Math

-- Additional sample results for better data visualization
INSERT INTO Result (student_id, class_id, subject_id, exam_term, marks_obtained, total_subject_marks, recorded_by_teacher_id) VALUES
-- More DPS students
(4, 1, 1, 'term1', 88.00, 100.00, 1),  -- Priya Math
(4, 1, 2, 'term1', 85.00, 100.00, 2),  -- Priya Science
(5, 2, 1, 'term1', 79.00, 100.00, 1),  -- Rohit Math
(5, 2, 2, 'term1', 73.00, 100.00, 2),  -- Rohit Science
(6, 2, 1, 'term1', 92.00, 100.00, 1),  -- Sneha Math
(6, 2, 2, 'term1', 89.00, 100.00, 2),  -- Sneha Science

-- More KV students
(14, 6, 7, 'term1', 82.00, 80.00, 7),   -- Kavitha Math
(14, 6, 8, 'term1', 78.00, 80.00, 8);   -- Kavitha Science