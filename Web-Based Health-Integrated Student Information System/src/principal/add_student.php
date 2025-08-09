<?php
session_start();
require_once(__DIR__ . '/../common/db.php');

// Check if user is logged in and is a principal
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Insert student personal information
        $stmt = $conn->prepare("INSERT INTO students (lrn, first_name, middle_name, last_name, sex, birthdate, height, weight, address, parent_name, grade_level_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $lrn = trim($_POST['lrn']);
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name']);
        $sex = $_POST['sex'];
        $birthdate = $_POST['birthdate'];
        $height = !empty($_POST['height']) ? $_POST['height'] : null;
        $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
        $address = trim($_POST['address'] ?? '');
        $parent_name = trim($_POST['parent_name'] ?? '');
        $grade_level_id = $_POST['grade_level_id']; // Get from form
        
        $stmt->bind_param("ssssssddssi", $lrn, $first_name, $middle_name, $last_name, $sex, $birthdate, $height, $weight, $address, $parent_name, $grade_level_id);
        $stmt->execute();
        
        $student_id = $conn->insert_id;
        
        // Insert student health information
        $stmt = $conn->prepare("INSERT INTO student_health (student_id, nutritional_status, bmi, height_for_age, weight_for_age, four_ps_beneficiary, immunization_mr, immunization_td, immunization_hpv, deworming, ailments, intervention, allergies, date_of_exam, status, remarks, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $nutritional_status = trim($_POST['nutritional_status'] ?? '');
        $bmi = !empty($_POST['bmi']) ? $_POST['bmi'] : null;
        $height_for_age = $_POST['height_for_age'] ?? '';
        $weight_for_age = $_POST['weight_for_age'] ?? '';
        $four_ps_beneficiary = isset($_POST['four_ps_beneficiary']) ? $_POST['four_ps_beneficiary'] : null;
        $immunization_mr = $_POST['immunization_mr'] ?? 'None';
        $immunization_td = $_POST['immunization_td'] ?? 'None';
        $immunization_hpv = $_POST['immunization_hpv'] ?? 'None';
        $deworming = $_POST['deworming'] ?? 'None';
        $ailments = trim($_POST['ailments'] ?? '');
        $intervention = $_POST['intervention'] ?? '';
        $allergies = trim($_POST['allergies'] ?? '');
        $date_of_exam = !empty($_POST['date_of_exam']) ? $_POST['date_of_exam'] : null;
        $status = trim($_POST['status'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');
        
        $stmt->bind_param("isdsissssssssssss", $student_id, $nutritional_status, $bmi, $height_for_age, $weight_for_age, $four_ps_beneficiary, $immunization_mr, $immunization_td, $immunization_hpv, $deworming, $ailments, $intervention, $allergies, $date_of_exam, $status, $remarks);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php?success=student_added');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Principal add student error: " . $e->getMessage());
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php?error=add_failed');
        exit();
    }
} else {
    // If not POST request, redirect back to form
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php');
    exit();
}
?> 