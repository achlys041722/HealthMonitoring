-- Migration Script: Fix Student Health Table Structure
-- This script completely rebuilds the student_health table with all required columns

-- Drop the existing table and recreate it
DROP TABLE IF EXISTS student_health;

CREATE TABLE student_health (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    height DECIMAL(5,2) DEFAULT NULL,
    weight DECIMAL(5,2) DEFAULT NULL,
    bmi DECIMAL(4,2) DEFAULT NULL,
    height_for_age ENUM('Severely Stunted', 'Stunted', 'Normal', 'Tall') DEFAULT NULL,
    weight_for_age ENUM('Severely Underweight', 'Underweight', 'Normal', 'Overweight', 'Obese') DEFAULT NULL,
    nutritional_status VARCHAR(100) DEFAULT NULL,
    four_ps_beneficiary BOOLEAN DEFAULT FALSE,
    immunization_mr ENUM('None', '1st dose', '2nd dose', 'Complete') DEFAULT 'None',
    immunization_td ENUM('None', '1st dose', '2nd dose', 'Complete') DEFAULT 'None',
    immunization_hpv ENUM('None', 'Complete', 'Incomplete') DEFAULT 'None',
    deworming ENUM('None', '1st Dose', '2nd Dose (Complete)') DEFAULT 'None',
    ailments TEXT DEFAULT NULL,
    intervention ENUM('Treatment', 'Referral', 'None') DEFAULT 'None',
    allergies TEXT DEFAULT NULL,
    date_of_exam DATE DEFAULT NULL,
    status VARCHAR(100) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Verify the changes
SELECT 
    'Student health table recreated successfully with all required columns' as message; 