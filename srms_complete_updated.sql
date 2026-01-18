CREATE DATABASE srms_db;
USE srms_db;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- 1. school Table
CREATE TABLE school (
    school_id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    school_address VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    user_id INT DEFAULT NULL,
    UNIQUE (school_name)
);

-- 2. user Table
CREATE TABLE user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'principal', 'admin') NOT NULL,
    school_id INT NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    FOREIGN KEY (school_id) REFERENCES school(school_id)
);

-- 3. class Table
CREATE TABLE class (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(10) NOT NULL,
    division VARCHAR(10) NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (school_id) REFERENCES school(school_id),
    UNIQUE (class_name, division, school_id)
);

-- 4. subject Table
CREATE TABLE subject (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(255) NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (school_id) REFERENCES school(school_id),
    UNIQUE (subject_name, school_id)
);

-- 5. student Table
CREATE TABLE student (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    roll_number VARCHAR(50) NOT NULL,
    user_id INT NOT NULL UNIQUE,
    class_id INT NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id),
    FOREIGN KEY (class_id) REFERENCES class(class_id),
    FOREIGN KEY (school_id) REFERENCES school(school_id),
    UNIQUE (roll_number, class_id, school_id)
);

-- 6. teacher Table
CREATE TABLE teacher (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    school_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id),
    FOREIGN KEY (school_id) REFERENCES school(school_id)
);

-- 7. teacher_class_subject Table
CREATE TABLE teacher_class_subject (
    teacher_class_subject_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES teacher(teacher_id),
    FOREIGN KEY (class_id) REFERENCES class(class_id),
    FOREIGN KEY (subject_id) REFERENCES subject(subject_id),
    UNIQUE (teacher_id, class_id, subject_id)
);

-- 8. exam Table
CREATE TABLE exam (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(255) NOT NULL,
    exam_term ENUM('term1', 'term2') NOT NULL,
    exam_date DATE,
    end_date DATE,
    total_marks DECIMAL(5,2) DEFAULT 100.00,
    class_id INT NOT NULL,
    status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES class(class_id)
);

-- 9. examresult Table
CREATE TABLE examresult (
    exam_result_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    subject_id INT NOT NULL,
    marks_obtained DECIMAL(5,2) DEFAULT 0.00,
    total_marks DECIMAL(5,2) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_exam_result (student_id, exam_id, subject_id),
    FOREIGN KEY (student_id) REFERENCES student(student_id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exam(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subject(subject_id) ON DELETE CASCADE
);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Sample Data Insertion
INSERT INTO school (school_name, school_address, status) VALUES
('Delhi Public School, Hyderabad', 'Road No. 12, Banjara Hills, Hyderabad, Telangana', 'active'),
('Kendriya Vidyalaya, Bangalore', 'Old Airport Road, Vimanapura, Bangalore, Karnataka', 'active'),
('St. Xavier\'s Collegiate School, Kolkata', '30 Park Street, Kolkata, West Bengal', 'active');

-- Admin and Principal Users
INSERT INTO user (username, password, fullname, role, school_id) VALUES
('srms.admin', 'admin', 'SRMS Administrator', 'admin', 1),
('priya.principal', 'principalpass123', 'Dr. Priya Sharma', 'principal', 1),
('sanjay.principal', 'principalpass123', 'Mr. Sanjay Kumar', 'principal', 2),
('thomas.principal', 'principalpass123', 'Fr. Thomas Varghese', 'principal', 3);

-- Teachers
INSERT INTO user (username, password, fullname, role, school_id) VALUES
('anita.math', 'teacherpass123', 'Ms. Anita Reddy', 'teacher', 1),
('rajesh.sci', 'teacherpass123', 'Mr. Rajesh Singh', 'teacher', 1),
('pooja.eng', 'teacherpass123', 'Ms. Pooja Gupta', 'teacher', 2),
('amit.hist', 'teacherpass123', 'Mr. Amit Verma', 'teacher', 3);

-- Students
INSERT INTO user (username, password, fullname, role, school_id) VALUES
('arjun.dps', 'studentpass123', 'Arjun Singh', 'student', 1),
('riya.dps', 'studentpass123', 'Riya Sharma', 'student', 1),
('kiran.kv', 'studentpass123', 'Kiran Rao', 'student', 2),
('sara.kv', 'studentpass123', 'Sara Khan', 'student', 2),
('vivek.sx', 'studentpass123', 'Vivek Das', 'student', 3),
('isha.sx', 'studentpass123', 'Isha Bose', 'student', 3);

-- Classes
INSERT INTO class (class_name, division, school_id) VALUES
('10', 'A', 1), ('10', 'B', 1),
('9', 'A', 2), ('9', 'B', 2),
('11', 'Science', 3), ('11', 'Commerce', 3);

-- Subjects
INSERT INTO subject (subject_name, school_id) VALUES
('Mathematics', 1), ('Science', 1), ('English', 1), ('Social Science', 1),
('Hindi', 2), ('English', 2), ('Mathematics', 2),
('Physics', 3), ('Chemistry', 3), ('Biology', 3), ('History', 3);

-- Students
INSERT INTO student (roll_number, user_id, class_id, school_id) VALUES
('23001', 9, 1, 1), ('23002', 10, 1, 1),
('24001', 11, 3, 2), ('24002', 12, 3, 2),
('22001', 13, 5, 3), ('22002', 14, 5, 3);

-- Teachers
INSERT INTO teacher (user_id, school_id) VALUES
(5, 1), (6, 1), (7, 2), (8, 3);

-- Teacher Assignments
INSERT INTO teacher_class_subject (teacher_id, class_id, subject_id) VALUES
(1, 1, 1), (1, 2, 1), (2, 1, 2), (2, 2, 2),
(3, 3, 5), (3, 4, 6), (4, 5, 11);