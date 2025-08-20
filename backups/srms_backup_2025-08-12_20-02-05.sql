-- SRMS Database Backup
-- Generated on: 2025-08-12 20:02:05

SET FOREIGN_KEY_CHECKS = 0;

-- Table: School
DROP TABLE IF EXISTS `School`;
CREATE TABLE `school` (
  `school_id` int(11) NOT NULL AUTO_INCREMENT,
  `school_name` varchar(255) NOT NULL,
  `school_address` varchar(255) DEFAULT NULL,
  `principal_name` varchar(255) DEFAULT 'Not specified',
  `principal_username` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`school_id`),
  UNIQUE KEY `school_name` (`school_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `School` VALUES ('1', 'Delhi Public School, Hyderabad', 'Road No. 12, Banjara Hills, Hyderabad, Telangana 50003\r\n', 'Dr. Paavan Parekh', 'Paavan.principal', 'active');
INSERT INTO `School` VALUES ('2', 'Kendriya Vidyalaya, Bangalore', 'Old Airport Road, Vimanapura, Bangalore, Karnataka 560017', 'Dr. Meet Parekh', 'Meet.Principal', 'active');
INSERT INTO `School` VALUES ('3', 'St. Xavier\'s Collegiate School, Kolkata', '30 Park Street, Kolkata, West Bengal 700016', 'Not specified', NULL, 'active');
INSERT INTO `School` VALUES ('4', 'DAV Public School, Mumbai', 'Linking Road, Bandra West, Mumbai, Maharashtra 400050', 'Dr. Param Parekh', 'Param.Principal', 'active');
INSERT INTO `School` VALUES ('5', 'Ryan International School, Delhi', 'Sector 25, Rohini, Delhi 110085', 'Not specified', NULL, 'active');
INSERT INTO `School` VALUES ('6', 'Mogar High School', 'Mogar', 'Not specified', NULL, 'active');
INSERT INTO `School` VALUES ('7', 'Knowledge', 'Bakrol', 'Dr. Krupa Parekh', 'Krupa.Principal', 'active');
INSERT INTO `School` VALUES ('8', 'Sardar Patel Vidyamandir', 'Near Mahadev Mandir, Vasad', 'Jatin P. Patel', 'Jatin.Principal', 'inactive');
INSERT INTO `School` VALUES ('9', 'DAS', 'Opp. Chakli circle, Mumbai-360005', 'Paavan Parekh', 'paavanparekh209', 'active');
INSERT INTO `School` VALUES ('10', 'Kendriya Vidyalaya,HYD', 'Hyderabad', 'Param Parekh', 'paramparekh393', 'active');

-- Table: User
DROP TABLE IF EXISTS `User`;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `role` enum('student','teacher','principal','admin') NOT NULL,
  `school_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `User` VALUES ('1', 'srms.admin', 'admin', 'SRMS Administrator', 'admin', '1');
INSERT INTO `User` VALUES ('2', 'priya.principal', 'principalpass123', 'Dr. Priya Sharma', 'principal', '1');
INSERT INTO `User` VALUES ('3', 'sanjay.principal', 'principalpass123', 'Mr. Sanjay Kumar', 'principal', '2');
INSERT INTO `User` VALUES ('4', 'thomas.principal', 'principalpass123', 'Fr. Thomas Varghese', 'principal', '3');
INSERT INTO `User` VALUES ('5', 'meera.principal', 'principalpass123', 'Mrs. Meera Patel', 'principal', '4');
INSERT INTO `User` VALUES ('6', 'rajesh.principal', 'principalpass123', 'Dr. Rajesh Gupta', 'principal', '5');
INSERT INTO `User` VALUES ('7', 'anita.math', 'teacherpass123', 'Ms. Anita Reddy', 'teacher', '1');
INSERT INTO `User` VALUES ('8', 'rajesh.sci', 'teacherpass123', 'Mr. Rajesh Singh', 'teacher', '1');
INSERT INTO `User` VALUES ('9', 'kavya.eng', 'teacherpass123', 'Ms. Kavya Nair', 'teacher', '1');
INSERT INTO `User` VALUES ('10', 'suresh.soc', 'teacherpass123', 'Mr. Suresh Rao', 'teacher', '1');
INSERT INTO `User` VALUES ('11', 'deepa.hin', 'teacherpass123', 'Mrs. Deepa Sharma', 'teacher', '1');
INSERT INTO `User` VALUES ('12', 'pooja.eng', 'teacherpass123', 'Ms. Pooja Gupta', 'teacher', '2');
INSERT INTO `User` VALUES ('13', 'ramesh.math', 'teacherpass123', 'Mr. Ramesh Kumar', 'teacher', '2');
INSERT INTO `User` VALUES ('14', 'sunita.sci', 'teacherpass123', 'Mrs. Sunita Joshi', 'teacher', '2');
INSERT INTO `User` VALUES ('15', 'vikram.hin', 'teacherpass123', 'Mr. Vikram Singh', 'teacher', '2');
INSERT INTO `User` VALUES ('16', 'amit.hist', 'teacherpass123', 'Mr. Amit Verma', 'teacher', '3');
INSERT INTO `User` VALUES ('17', 'rita.phy', 'teacherpass123', 'Mrs. Rita Das', 'teacher', '3');
INSERT INTO `User` VALUES ('18', 'subhash.che', 'teacherpass123', 'Dr. Subhash Bose', 'teacher', '3');
INSERT INTO `User` VALUES ('19', 'maya.bio', 'teacherpass123', 'Ms. Maya Chatterjee', 'teacher', '3');
INSERT INTO `User` VALUES ('21', 'riya.dps', 'studentpass123', 'Riya Sharma', 'student', '1');
INSERT INTO `User` VALUES ('22', 'karthik.dps', 'studentpass123', 'Karthik Reddy', 'student', '1');
INSERT INTO `User` VALUES ('23', 'priya.dps', 'studentpass123', 'Priya Nair', 'student', '1');
INSERT INTO `User` VALUES ('24', 'rohit.dps', 'studentpass123', 'Rohit Kumar', 'student', '1');
INSERT INTO `User` VALUES ('25', 'sneha.dps', 'studentpass123', 'Sneha Patel', 'student', '1');
INSERT INTO `User` VALUES ('26', 'arun.dps', 'studentpass123', 'Arun Rao', 'student', '1');
INSERT INTO `User` VALUES ('27', 'divya.dps', 'studentpass123', 'Divya Singh', 'student', '1');
INSERT INTO `User` VALUES ('28', 'kiran.kv', 'studentpass123', 'Kiran Rao', 'student', '2');
INSERT INTO `User` VALUES ('29', 'sara.kv', 'studentpass123', 'Sara Khan', 'student', '2');
INSERT INTO `User` VALUES ('30', 'manish.kv', 'studentpass123', 'Manish Gupta', 'student', '2');
INSERT INTO `User` VALUES ('31', 'ananya.kv', 'studentpass123', 'Ananya Joshi', 'student', '2');
INSERT INTO `User` VALUES ('32', 'rahul.kv', 'studentpass123', 'Rahul Singh', 'student', '2');
INSERT INTO `User` VALUES ('33', 'kavitha.kv', 'studentpass123', 'Kavitha Kumar', 'student', '2');
INSERT INTO `User` VALUES ('34', 'vivek.sx', 'studentpass123', 'Vivek Das', 'student', '3');
INSERT INTO `User` VALUES ('35', 'isha.sx', 'studentpass123', 'Isha Bose', 'student', '3');
INSERT INTO `User` VALUES ('36', 'aritra.sx', 'studentpass123', 'Aritra Ghosh', 'student', '3');
INSERT INTO `User` VALUES ('37', 'shreya.sx', 'studentpass123', 'Shreya Mukherjee', 'student', '3');
INSERT INTO `User` VALUES ('38', 'sourav.sx', 'studentpass123', 'Sourav Roy', 'student', '3');
INSERT INTO `User` VALUES ('39', 'ritu.sx', 'studentpass123', 'Ritu Chatterjee', 'student', '3');
INSERT INTO `User` VALUES ('40', 'Param.Principal', '710708', 'Param Parekh', 'principal', '7');
INSERT INTO `User` VALUES ('41', 'Meet.Kn', 'studentpass123', 'Meet Parekh', 'student', '7');
INSERT INTO `User` VALUES ('42', 'Nikhil.Kn', '110011', 'Nikhil Kulkarni', 'teacher', '7');
INSERT INTO `User` VALUES ('43', 'john.doe', 'password123', 'John Doe', 'student', '1');
INSERT INTO `User` VALUES ('44', 'jane.smith', 'password123', 'Jane Smith', 'student', '1');
INSERT INTO `User` VALUES ('46', 'spandan.dps', 'teacherpass123', 'Spandan Parikh', 'teacher', '1');
INSERT INTO `User` VALUES ('47', 'paavanparekh209', 'principal5726', 'Paavan Parekh', 'principal', '9');
INSERT INTO `User` VALUES ('48', 'Karthik.DA', 'teacherpass123', 'Karthik Ready', 'teacher', '9');
INSERT INTO `User` VALUES ('49', 'paramparekh393', 'principal3722', 'Param Parekh', 'principal', '10');
INSERT INTO `User` VALUES ('50', 'Jane.De', 'teacherpass123', 'Jane Smith', 'teacher', '1');

-- Table: Teacher
DROP TABLE IF EXISTS `Teacher`;
CREATE TABLE `teacher` (
  `teacher_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  PRIMARY KEY (`teacher_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `teacher_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  CONSTRAINT `teacher_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Teacher` VALUES ('1', '7', '1');
INSERT INTO `Teacher` VALUES ('2', '8', '1');
INSERT INTO `Teacher` VALUES ('3', '9', '1');
INSERT INTO `Teacher` VALUES ('4', '10', '1');
INSERT INTO `Teacher` VALUES ('5', '11', '1');
INSERT INTO `Teacher` VALUES ('6', '12', '2');
INSERT INTO `Teacher` VALUES ('7', '13', '2');
INSERT INTO `Teacher` VALUES ('8', '14', '2');
INSERT INTO `Teacher` VALUES ('9', '15', '2');
INSERT INTO `Teacher` VALUES ('10', '16', '3');
INSERT INTO `Teacher` VALUES ('11', '17', '3');
INSERT INTO `Teacher` VALUES ('12', '18', '3');
INSERT INTO `Teacher` VALUES ('13', '19', '3');
INSERT INTO `Teacher` VALUES ('14', '42', '7');
INSERT INTO `Teacher` VALUES ('16', '46', '1');
INSERT INTO `Teacher` VALUES ('17', '48', '9');
INSERT INTO `Teacher` VALUES ('18', '50', '1');

-- Table: Student
DROP TABLE IF EXISTS `Student`;
CREATE TABLE `student` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `roll_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `roll_number` (`roll_number`,`class_id`,`school_id`),
  KEY `class_id` (`class_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `student_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  CONSTRAINT `student_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`),
  CONSTRAINT `student_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Student` VALUES ('1', 'DPS001', '21', '1', '1');
INSERT INTO `Student` VALUES ('2', 'DPS002', '22', '1', '1');
INSERT INTO `Student` VALUES ('3', 'DPS003', '23', '1', '1');
INSERT INTO `Student` VALUES ('4', 'DPS004', '24', '1', '1');
INSERT INTO `Student` VALUES ('5', 'DPS005', '25', '2', '1');
INSERT INTO `Student` VALUES ('6', 'DPS006', '26', '2', '1');
INSERT INTO `Student` VALUES ('7', 'DPS007', '27', '2', '1');
INSERT INTO `Student` VALUES ('8', 'DPS008', '28', '2', '1');
INSERT INTO `Student` VALUES ('9', 'KV001', '29', '5', '2');
INSERT INTO `Student` VALUES ('10', 'KV002', '30', '5', '2');
INSERT INTO `Student` VALUES ('11', 'KV003', '31', '5', '2');
INSERT INTO `Student` VALUES ('12', 'KV004', '32', '6', '2');
INSERT INTO `Student` VALUES ('13', 'KV005', '33', '6', '2');
INSERT INTO `Student` VALUES ('14', 'KV006', '34', '6', '2');
INSERT INTO `Student` VALUES ('15', 'SX001', '35', '9', '3');
INSERT INTO `Student` VALUES ('16', 'SX002', '36', '9', '3');
INSERT INTO `Student` VALUES ('17', 'SX003', '37', '9', '3');
INSERT INTO `Student` VALUES ('18', 'SX004', '38', '10', '3');
INSERT INTO `Student` VALUES ('19', 'SX005', '39', '10', '3');
INSERT INTO `Student` VALUES ('20', 'SX006', '40', '10', '3');
INSERT INTO `Student` VALUES ('21', 'Kn001', '41', '20', '7');
INSERT INTO `Student` VALUES ('22', 'DPS009', '43', '1', '1');
INSERT INTO `Student` VALUES ('23', 'DPS010', '44', '1', '1');

-- Table: Class
DROP TABLE IF EXISTS `Class`;
CREATE TABLE `class` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_name` varchar(10) NOT NULL,
  `division` varchar(10) NOT NULL,
  `school_id` int(11) NOT NULL,
  PRIMARY KEY (`class_id`),
  UNIQUE KEY `class_name` (`class_name`,`division`,`school_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `class_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Class` VALUES ('18', '1', 'A', '7');
INSERT INTO `Class` VALUES ('1', '10', 'A', '1');
INSERT INTO `Class` VALUES ('13', '10', 'A', '4');
INSERT INTO `Class` VALUES ('2', '10', 'B', '1');
INSERT INTO `Class` VALUES ('14', '10', 'B', '4');
INSERT INTO `Class` VALUES ('19', '10', 'B', '7');
INSERT INTO `Class` VALUES ('17', '11', 'A', '1');
INSERT INTO `Class` VALUES ('10', '11', 'Commerce', '3');
INSERT INTO `Class` VALUES ('21', '11', 'Science', '1');
INSERT INTO `Class` VALUES ('9', '11', 'Science', '3');
INSERT INTO `Class` VALUES ('20', '11', 'Science', '7');
INSERT INTO `Class` VALUES ('12', '12', 'Commerce', '3');
INSERT INTO `Class` VALUES ('11', '12', 'Science', '3');
INSERT INTO `Class` VALUES ('7', '8', 'A', '2');
INSERT INTO `Class` VALUES ('8', '8', 'B', '2');
INSERT INTO `Class` VALUES ('3', '9', 'A', '1');
INSERT INTO `Class` VALUES ('5', '9', 'A', '2');
INSERT INTO `Class` VALUES ('15', '9', 'A', '5');
INSERT INTO `Class` VALUES ('4', '9', 'B', '1');
INSERT INTO `Class` VALUES ('6', '9', 'B', '2');
INSERT INTO `Class` VALUES ('16', '9', 'B', '5');

-- Table: Subject
DROP TABLE IF EXISTS `Subject`;
CREATE TABLE `subject` (
  `subject_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(255) NOT NULL,
  `school_id` int(11) NOT NULL,
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `subject_name` (`subject_name`,`school_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `subject_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Subject` VALUES ('20', 'Accountancy', '3');
INSERT INTO `Subject` VALUES ('15', 'Biology', '3');
INSERT INTO `Subject` VALUES ('14', 'Chemistry', '3');
INSERT INTO `Subject` VALUES ('6', 'Computer Science', '1');
INSERT INTO `Subject` VALUES ('19', 'Economics', '3');
INSERT INTO `Subject` VALUES ('3', 'English', '1');
INSERT INTO `Subject` VALUES ('9', 'English', '2');
INSERT INTO `Subject` VALUES ('17', 'English', '3');
INSERT INTO `Subject` VALUES ('23', 'English', '4');
INSERT INTO `Subject` VALUES ('28', 'English', '5');
INSERT INTO `Subject` VALUES ('5', 'Hindi', '1');
INSERT INTO `Subject` VALUES ('10', 'Hindi', '2');
INSERT INTO `Subject` VALUES ('25', 'Hindi', '4');
INSERT INTO `Subject` VALUES ('29', 'Hindi', '5');
INSERT INTO `Subject` VALUES ('18', 'History', '3');
INSERT INTO `Subject` VALUES ('24', 'Marathi', '4');
INSERT INTO `Subject` VALUES ('1', 'Mathematics', '1');
INSERT INTO `Subject` VALUES ('7', 'Mathematics', '2');
INSERT INTO `Subject` VALUES ('16', 'Mathematics', '3');
INSERT INTO `Subject` VALUES ('21', 'Mathematics', '4');
INSERT INTO `Subject` VALUES ('26', 'Mathematics', '5');
INSERT INTO `Subject` VALUES ('31', 'Mathematics-1', '1');
INSERT INTO `Subject` VALUES ('13', 'Physics', '3');
INSERT INTO `Subject` VALUES ('11', 'Sanskrit', '2');
INSERT INTO `Subject` VALUES ('2', 'Science', '1');
INSERT INTO `Subject` VALUES ('8', 'Science', '2');
INSERT INTO `Subject` VALUES ('22', 'Science', '4');
INSERT INTO `Subject` VALUES ('27', 'Science', '5');
INSERT INTO `Subject` VALUES ('4', 'Social Science', '1');
INSERT INTO `Subject` VALUES ('30', 'Social Science', '5');
INSERT INTO `Subject` VALUES ('12', 'Social Studies', '2');

-- Table: Teacher_Class_Subject
DROP TABLE IF EXISTS `Teacher_Class_Subject`;
CREATE TABLE `teacher_class_subject` (
  `teacher_class_subject_id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  PRIMARY KEY (`teacher_class_subject_id`),
  UNIQUE KEY `teacher_id` (`teacher_id`,`class_id`,`subject_id`),
  KEY `class_id` (`class_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `teacher_class_subject_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`teacher_id`),
  CONSTRAINT `teacher_class_subject_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`),
  CONSTRAINT `teacher_class_subject_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Teacher_Class_Subject` VALUES ('1', '1', '1', '1');
INSERT INTO `Teacher_Class_Subject` VALUES ('2', '1', '2', '1');
INSERT INTO `Teacher_Class_Subject` VALUES ('3', '2', '1', '2');
INSERT INTO `Teacher_Class_Subject` VALUES ('4', '2', '2', '2');
INSERT INTO `Teacher_Class_Subject` VALUES ('5', '3', '1', '3');
INSERT INTO `Teacher_Class_Subject` VALUES ('6', '3', '2', '3');
INSERT INTO `Teacher_Class_Subject` VALUES ('7', '4', '1', '4');
INSERT INTO `Teacher_Class_Subject` VALUES ('8', '4', '2', '4');
INSERT INTO `Teacher_Class_Subject` VALUES ('9', '5', '1', '5');
INSERT INTO `Teacher_Class_Subject` VALUES ('10', '5', '2', '5');
INSERT INTO `Teacher_Class_Subject` VALUES ('11', '6', '5', '9');
INSERT INTO `Teacher_Class_Subject` VALUES ('12', '6', '6', '9');
INSERT INTO `Teacher_Class_Subject` VALUES ('13', '7', '5', '7');
INSERT INTO `Teacher_Class_Subject` VALUES ('14', '7', '6', '7');
INSERT INTO `Teacher_Class_Subject` VALUES ('15', '8', '5', '8');
INSERT INTO `Teacher_Class_Subject` VALUES ('16', '8', '6', '8');
INSERT INTO `Teacher_Class_Subject` VALUES ('24', '8', '8', '11');
INSERT INTO `Teacher_Class_Subject` VALUES ('17', '9', '5', '10');
INSERT INTO `Teacher_Class_Subject` VALUES ('18', '9', '6', '10');
INSERT INTO `Teacher_Class_Subject` VALUES ('23', '9', '8', '7');
INSERT INTO `Teacher_Class_Subject` VALUES ('19', '10', '9', '18');
INSERT INTO `Teacher_Class_Subject` VALUES ('20', '11', '9', '13');
INSERT INTO `Teacher_Class_Subject` VALUES ('21', '12', '9', '14');
INSERT INTO `Teacher_Class_Subject` VALUES ('22', '13', '9', '15');
INSERT INTO `Teacher_Class_Subject` VALUES ('37', '16', '17', '4');
INSERT INTO `Teacher_Class_Subject` VALUES ('36', '18', '21', '1');

-- Table: Result
DROP TABLE IF EXISTS `Result`;
CREATE TABLE `result` (
  `result_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `exam_term` enum('term1','term2') NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL CHECK (`marks_obtained` >= 0),
  `total_subject_marks` decimal(5,2) NOT NULL CHECK (`total_subject_marks` > 0),
  `recorded_by_teacher_id` int(11) DEFAULT NULL,
  `recorded_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`result_id`),
  UNIQUE KEY `student_id` (`student_id`,`class_id`,`subject_id`,`exam_term`),
  KEY `class_id` (`class_id`),
  KEY `subject_id` (`subject_id`),
  KEY `recorded_by_teacher_id` (`recorded_by_teacher_id`),
  CONSTRAINT `result_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  CONSTRAINT `result_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`),
  CONSTRAINT `result_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`),
  CONSTRAINT `result_ibfk_4` FOREIGN KEY (`recorded_by_teacher_id`) REFERENCES `teacher` (`teacher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Result` VALUES ('1', '1', '1', '1', 'term1', '85.00', '100.00', '1', '2025-07-24 16:56:10');
INSERT INTO `Result` VALUES ('2', '1', '1', '2', 'term1', '78.50', '100.00', '1', '2025-07-24 16:56:10');
INSERT INTO `Result` VALUES ('3', '1', '1', '3', 'term1', '92.00', '100.00', '3', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('4', '1', '1', '4', 'term1', '88.00', '100.00', '4', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('5', '1', '1', '5', 'term1', '76.00', '100.00', '5', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('6', '2', '1', '1', 'term1', '90.50', '100.00', '1', '2025-07-24 16:56:10');
INSERT INTO `Result` VALUES ('7', '2', '1', '2', 'term1', '82.00', '100.00', '1', '2025-07-24 16:56:10');
INSERT INTO `Result` VALUES ('8', '2', '1', '3', 'term1', '95.00', '100.00', '3', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('9', '2', '1', '4', 'term1', '91.00', '100.00', '4', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('10', '2', '1', '5', 'term1', '84.00', '100.00', '5', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('11', '3', '1', '1', 'term1', '78.00', '100.00', '1', '2025-07-22 21:07:22');
INSERT INTO `Result` VALUES ('12', '3', '1', '2', 'term1', '69.50', '100.00', '2', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('13', '3', '1', '3', 'term1', '81.00', '100.00', '3', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('14', '3', '1', '4', 'term1', '75.00', '100.00', '4', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('15', '3', '1', '5', 'term1', '78.00', '100.00', '5', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('16', '1', '1', '1', 'term2', '90.00', '100.00', '1', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('17', '1', '1', '2', 'term2', '88.00', '100.00', '2', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('18', '2', '1', '1', 'term2', '95.00', '100.00', '1', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('19', '2', '1', '2', 'term2', '89.00', '100.00', '2', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('20', '9', '5', '7', 'term1', '70.00', '80.00', '7', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('21', '9', '5', '8', 'term1', '65.00', '80.00', '8', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('22', '9', '5', '9', 'term1', '72.00', '80.00', '6', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('23', '9', '5', '10', 'term1', '68.00', '80.00', '9', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('24', '10', '5', '7', 'term1', '75.00', '80.00', '7', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('25', '10', '5', '8', 'term1', '71.00', '80.00', '8', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('26', '10', '5', '9', 'term1', '78.00', '80.00', '6', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('27', '10', '5', '10', 'term1', '74.00', '80.00', '9', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('28', '15', '9', '13', 'term1', '75.00', '100.00', '11', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('29', '15', '9', '14', 'term1', '68.00', '100.00', '12', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('30', '15', '9', '15', 'term1', '82.00', '100.00', '13', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('31', '15', '9', '16', 'term1', '79.00', '100.00', '1', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('32', '16', '9', '13', 'term1', '88.00', '100.00', '11', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('33', '16', '9', '14', 'term1', '85.00', '100.00', '12', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('34', '16', '9', '15', 'term1', '91.00', '100.00', '13', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('35', '16', '9', '16', 'term1', '87.00', '100.00', '1', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('36', '4', '1', '1', 'term1', '65.00', '100.00', '1', '2025-07-22 21:07:22');
INSERT INTO `Result` VALUES ('37', '4', '1', '2', 'term1', '85.00', '100.00', '2', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('38', '5', '2', '1', 'term1', '79.00', '100.00', '1', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('39', '5', '2', '2', 'term1', '73.00', '100.00', '2', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('40', '6', '2', '1', 'term1', '92.00', '100.00', '1', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('41', '6', '2', '2', 'term1', '89.00', '100.00', '2', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('42', '14', '6', '7', 'term1', '82.00', '80.00', '7', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('43', '14', '6', '8', 'term1', '78.00', '80.00', '8', '2025-07-22 20:08:47');
INSERT INTO `Result` VALUES ('44', '11', '5', '9', 'term1', '89.00', '100.00', '6', '2025-07-26 23:07:25');
INSERT INTO `Result` VALUES ('45', '12', '6', '9', 'term2', '98.00', '100.00', '6', '2025-08-12 00:27:16');
INSERT INTO `Result` VALUES ('46', '13', '6', '9', 'term2', '71.00', '100.00', '6', '2025-08-12 00:27:17');
INSERT INTO `Result` VALUES ('47', '14', '6', '9', 'term2', '35.00', '100.00', '6', '2025-08-12 00:27:19');

SET FOREIGN_KEY_CHECKS = 1;
