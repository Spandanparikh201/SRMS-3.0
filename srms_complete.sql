CREATE DATABASE srms_db;
USE srms_db;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- 1. School Table
CREATE TABLE School (
    school_id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    school_address VARCHAR(255),
    principal_name VARCHAR(255) DEFAULT 'Not specified',
    principal_username VARCHAR(100) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    UNIQUE (school_name)
);

-- 2. User Table
CREATE TABLE User (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'principal', 'admin') NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (school_id) REFERENCES School(school_id)
);

-- 3. Class Table
CREATE TABLE Class (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(10) NOT NULL,
    division VARCHAR(10) NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (school_id) REFERENCES School(school_id),
    UNIQUE (class_name, division, school_id)
);

-- 4. Subject Table
CREATE TABLE Subject (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(255) NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (school_id) REFERENCES School(school_id),
    UNIQUE (subject_name, school_id)
);

-- 5. Student Table
CREATE TABLE Student (
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
CREATE TABLE Teacher (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    school_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES User(user_id),
    FOREIGN KEY (school_id) REFERENCES School(school_id)
);

-- 7. Teacher_Class_Subject Table
CREATE TABLE Teacher_Class_Subject (
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
CREATE TABLE Result (
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

-- Sample Data Insertion
INSERT INTO School (school_name, school_address, principal_name, principal_username, status) VALUES
('Delhi Public School, Hyderabad', 'Road No. 12, Banjara Hills, Hyderabad, Telangana', 'Dr. Priya Sharma', 'priya.principal', 'active'),
('Kendriya Vidyalaya, Bangalore', 'Old Airport Road, Vimanapura, Bangalore, Karnataka', 'Mr. Sanjay Kumar', 'sanjay.principal', 'active'),
('St. Xavier\'s Collegiate School, Kolkata', '30 Park Street, Kolkata, West Bengal', 'Fr. Thomas Varghese', 'thomas.principal', 'active');

-- Admin and Principal Users
INSERT INTO User (username, password, fullname, role, school_id) VALUES
('srms.admin', 'admin', 'SRMS Administrator', 'admin', 1),
('priya.principal', 'principalpass123', 'Dr. Priya Sharma', 'principal', 1),
('sanjay.principal', 'principalpass123', 'Mr. Sanjay Kumar', 'principal', 2),
('thomas.principal', 'principalpass123', 'Fr. Thomas Varghese', 'principal', 3);

-- Teachers
INSERT INTO User (username, password, fullname, role, school_id) VALUES
('anita.math', 'teacherpass123', 'Ms. Anita Reddy', 'teacher', 1),
('rajesh.sci', 'teacherpass123', 'Mr. Rajesh Singh', 'teacher', 1),
('pooja.eng', 'teacherpass123', 'Ms. Pooja Gupta', 'teacher', 2),
('amit.hist', 'teacherpass123', 'Mr. Amit Verma', 'teacher', 3);

-- Students
INSERT INTO User (username, password, fullname, role, school_id) VALUES
('arjun.dps', 'studentpass123', 'Arjun Singh', 'student', 1),
('riya.dps', 'studentpass123', 'Riya Sharma', 'student', 1),
('kiran.kv', 'studentpass123', 'Kiran Rao', 'student', 2),
('sara.kv', 'studentpass123', 'Sara Khan', 'student', 2),
('vivek.sx', 'studentpass123', 'Vivek Das', 'student', 3),
('isha.sx', 'studentpass123', 'Isha Bose', 'student', 3);

-- Classes
INSERT INTO Class (class_name, division, school_id) VALUES
('10', 'A', 1), ('10', 'B', 1),
('9', 'A', 2), ('9', 'B', 2),
('11', 'Science', 3), ('11', 'Commerce', 3);

-- Subjects
INSERT INTO Subject (subject_name, school_id) VALUES
('Mathematics', 1), ('Science', 1), ('English', 1), ('Social Science', 1),
('Hindi', 2), ('English', 2), ('Mathematics', 2),
('Physics', 3), ('Chemistry', 3), ('Biology', 3), ('History', 3);

-- Students
INSERT INTO Student (roll_number, user_id, class_id, school_id) VALUES
('23001', 9, 1, 1), ('23002', 10, 1, 1),
('24001', 11, 3, 2), ('24002', 12, 3, 2),
('22001', 13, 5, 3), ('22002', 14, 5, 3);

-- Teachers
INSERT INTO Teacher (user_id, school_id) VALUES
(5, 1), (6, 1), (7, 2), (8, 3);

-- Teacher Assignments
INSERT INTO Teacher_Class_Subject (teacher_id, class_id, subject_id) VALUES
(1, 1, 1), (1, 2, 1), (2, 1, 2), (2, 2, 2),
(3, 3, 5), (3, 4, 6), (4, 5, 11);

-- Sample Results
INSERT INTO Result (student_id, class_id, subject_id, exam_term, marks_obtained, total_subject_marks, recorded_by_teacher_id) VALUES
(1, 1, 1, 'term1', 85.00, 100.00, 1),
(1, 1, 2, 'term1', 78.50, 100.00, 2),
(1, 1, 1, 'term2', 90.00, 100.00, 1),
(2, 1, 1, 'term1', 90.50, 100.00, 1),
(2, 1, 2, 'term1', 82.00, 100.00, 2);