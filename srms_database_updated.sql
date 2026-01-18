-- SRMS Database Creation Script - Updated to match backup structure
CREATE DATABASE IF NOT EXISTS srms_db;
USE srms_db;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- 1. school Table
CREATE TABLE IF NOT EXISTS school (
    school_id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    school_address VARCHAR(255),
    status ENUM('active','inactive') DEFAULT 'active',
    user_id INT DEFAULT NULL,
    UNIQUE (school_name)
);

-- 2. user Table
CREATE TABLE IF NOT EXISTS user (
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
CREATE TABLE IF NOT EXISTS class (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(10) NOT NULL,
    division VARCHAR(10) NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (school_id) REFERENCES school(school_id),
    UNIQUE (class_name, division, school_id)
);

-- 4. subject Table
CREATE TABLE IF NOT EXISTS subject (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(255) NOT NULL,
    school_id INT NOT NULL,
    FOREIGN KEY (school_id) REFERENCES school(school_id),
    UNIQUE (subject_name, school_id)
);

-- 5. student Table
CREATE TABLE IF NOT EXISTS student (
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
CREATE TABLE IF NOT EXISTS teacher (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    school_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id),
    FOREIGN KEY (school_id) REFERENCES school(school_id)
);

-- 7. teacher_class_subject Table
CREATE TABLE IF NOT EXISTS teacher_class_subject (
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
CREATE TABLE IF NOT EXISTS exam (
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
CREATE TABLE IF NOT EXISTS examresult (
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