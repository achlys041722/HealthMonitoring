-- Student Profiles Table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lrn VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    sex ENUM('Male', 'Female') NOT NULL,
    birthdate DATE NOT NULL,
    height FLOAT,
    weight FLOAT,
    address TEXT,
    parent_name VARCHAR(100),
    grade_level VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Student Health and Nutritional Assessment Table
CREATE TABLE IF NOT EXISTS student_health (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    nutritional_status VARCHAR(50),
    bmi FLOAT,
    height_for_age ENUM('Severely Stunted', 'Stunted', 'Normal', 'Tall'),
    weight_for_age ENUM('Severely Stunted', 'Stunted', 'Normal', 'Overweight', 'Obese'),
    four_ps_beneficiary BOOLEAN,
    immunization_mr ENUM('None', '1st dose', '2nd dose', 'Complete'),
    immunization_td ENUM('None', '1st dose', '2nd dose', 'Complete'),
    immunization_hpv ENUM('None', '1st dose', '2nd dose'),
    deworming ENUM('None', '1st Dose', '2nd Dose (Complete)'),
    ailments TEXT,
    intervention ENUM('Treatment', 'Referral', 'None'),
    allergies TEXT,
    date_of_exam DATE,
    status VARCHAR(50),
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);
