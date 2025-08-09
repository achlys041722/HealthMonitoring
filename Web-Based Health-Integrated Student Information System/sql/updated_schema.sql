-- Updated Database Schema for Hierarchical School Structure
-- Drop existing tables if they exist
DROP TABLE IF EXISTS student_health;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS schools;

-- Create schools table
CREATE TABLE schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    school_lrn VARCHAR(50) UNIQUE NOT NULL,
    address TEXT,
    contact_info VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create grade_levels table (replaces classes table)
CREATE TABLE grade_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    grade_name VARCHAR(20) NOT NULL, -- 'Kinder', 'Grade 1', 'Grade 2', etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_school_grade (school_id, grade_name)
);

-- Create students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lrn VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    sex ENUM('Male', 'Female') NOT NULL,
    birthdate DATE NOT NULL,
    height DECIMAL(5,2), -- in cm
    weight DECIMAL(5,2), -- in kg
    address TEXT,
    parent_name VARCHAR(255),
    grade_level_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grade_level_id) REFERENCES grade_levels(id) ON DELETE CASCADE
);

-- Create student_health table
CREATE TABLE student_health (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    nutritional_status VARCHAR(100),
    bmi DECIMAL(4,2),
    height_for_age ENUM('Severely Stunted', 'Stunted', 'Normal', 'Tall'),
    weight_for_age ENUM('Severely Underweight', 'Underweight', 'Normal', 'Overweight', 'Obese'),
    four_ps_beneficiary BOOLEAN DEFAULT FALSE,
    immunization_mr ENUM('None', '1st dose', '2nd dose') DEFAULT 'None',
    immunization_td ENUM('None', '1st dose', '2nd dose') DEFAULT 'None',
    immunization_hpv ENUM('None', 'Complete', 'Incomplete') DEFAULT 'None',
    deworming_1st BOOLEAN DEFAULT FALSE,
    deworming_2nd BOOLEAN DEFAULT FALSE,
    ailments TEXT,
    intervention ENUM('Treatment', 'Referral', 'None') DEFAULT 'None',
    allergies TEXT,
    date_of_exam DATE,
    status VARCHAR(100),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Insert sample data for testing
INSERT INTO schools (school_name, school_lrn, address, contact_info) VALUES
('Tahusan Elementary School', '123456789', 'Tahusan, Bohol', '09123456789'),
('Sample Elementary School', '987654321', 'Sample Address, Bohol', '09876543210');

-- Insert grade levels for each school
INSERT INTO grade_levels (school_id, grade_name) VALUES
-- Tahusan ES grades
(1, 'Kinder'),
(1, 'Grade 1'),
(1, 'Grade 2'),
(1, 'Grade 3'),
(1, 'Grade 4'),
(1, 'Grade 5'),
(1, 'Grade 6'),
-- Sample ES grades
(2, 'Kinder'),
(2, 'Grade 1'),
(2, 'Grade 2'),
(2, 'Grade 3'),
(2, 'Grade 4'),
(2, 'Grade 5'),
(2, 'Grade 6');

-- Create indexes for better performance
CREATE INDEX idx_students_grade_level ON students(grade_level_id);
CREATE INDEX idx_grade_levels_school ON grade_levels(school_id);
CREATE INDEX idx_students_lrn ON students(lrn);
CREATE INDEX idx_schools_lrn ON schools(school_lrn); 