<?php
session_start();
require_once(__DIR__ . '/../common/db.php');

// Check if user is logged in and is a principal
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get student ID
    $student_id = (int)$_POST['student_id'];
    
    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'sex', 'birthdate', 'lrn', 'address', 'parent_name', 'grade_level_id'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/edit_student.php?id=' . $student_id . '&error=invalid_data');
            exit();
        }
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Debug: Log the received data
        error_log("Update student data: " . print_r($_POST, true));
        
        // Set height and weight values
        $height = $_POST['height'] ?: null;
        $weight = $_POST['weight'] ?: null;
        
        // Update student information
        $stmt = $conn->prepare("
            UPDATE students SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                sex = ?, 
                birthdate = ?, 
                lrn = ?, 
                address = ?, 
                parent_name = ?, 
                height = ?,
                weight = ?,
                grade_level_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->bind_param("ssssssssddii", 
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['sex'],
            $_POST['birthdate'],
            $_POST['lrn'],
            $_POST['address'],
            $_POST['parent_name'],
            $height,
            $weight,
            $_POST['grade_level_id'],
            $student_id
        );
        
        $stmt->execute();
        
        // Check if health record exists
        $check_health = $conn->prepare("SELECT id FROM student_health WHERE student_id = ?");
        $check_health->bind_param("i", $student_id);
        $check_health->execute();
        $health_result = $check_health->get_result();
        
        if ($health_result->num_rows > 0) {
            // Set variables for bind_param
            $bmi = $_POST['bmi'] ?: null;
            $nutritional_status = $_POST['nutritional_status'];
            $height_for_age = $_POST['height_for_age'];
            $weight_for_age = $_POST['weight_for_age'];
            $four_ps_beneficiary = $_POST['four_ps_beneficiary'];
            $immunization_mr = $_POST['immunization_mr'];
            $immunization_td = $_POST['immunization_td'];
            $immunization_hpv = $_POST['immunization_hpv'];
            $deworming = $_POST['deworming'];
            $ailments = $_POST['ailments'];
            $intervention = $_POST['intervention'];
            $allergies = $_POST['allergies'];
            $date_of_exam = $_POST['date_of_exam'] ?: null;
            $status = $_POST['status'];
            $remarks = $_POST['remarks'];
            
            // Update existing health record
            $stmt = $conn->prepare("
                UPDATE student_health SET 
                    bmi = ?, 
                    nutritional_status = ?, 
                    height_for_age = ?, 
                    weight_for_age = ?, 
                    four_ps_beneficiary = ?, 
                    immunization_mr = ?, 
                    immunization_td = ?, 
                    immunization_hpv = ?, 
                    deworming = ?, 
                    ailments = ?, 
                    intervention = ?, 
                    allergies = ?, 
                    date_of_exam = ?, 
                    status = ?, 
                    remarks = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE student_id = ?
            ");
            
            $stmt->bind_param("dsissssssssssssi", 
                $bmi,
                $nutritional_status,
                $height_for_age,
                $weight_for_age,
                $four_ps_beneficiary,
                $immunization_mr,
                $immunization_td,
                $immunization_hpv,
                $deworming,
                $ailments,
                $intervention,
                $allergies,
                $date_of_exam,
                $status,
                $remarks,
                $student_id
            );
            
        } else {
            // Set variables for bind_param
            $bmi = $_POST['bmi'] ?: null;
            $nutritional_status = $_POST['nutritional_status'];
            $height_for_age = $_POST['height_for_age'];
            $weight_for_age = $_POST['weight_for_age'];
            $four_ps_beneficiary = $_POST['four_ps_beneficiary'];
            $immunization_mr = $_POST['immunization_mr'];
            $immunization_td = $_POST['immunization_td'];
            $immunization_hpv = $_POST['immunization_hpv'];
            $deworming = $_POST['deworming'];
            $ailments = $_POST['ailments'];
            $intervention = $_POST['intervention'];
            $allergies = $_POST['allergies'];
            $date_of_exam = $_POST['date_of_exam'] ?: null;
            $status = $_POST['status'];
            $remarks = $_POST['remarks'];
            
            // Insert new health record
            $stmt = $conn->prepare("
                INSERT INTO student_health (
                    student_id, bmi, nutritional_status, height_for_age, 
                    weight_for_age, four_ps_beneficiary, immunization_mr, immunization_td, 
                    immunization_hpv, deworming, ailments, intervention, 
                    allergies, date_of_exam, status, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("idsissssssssssss", 
                $student_id,
                $bmi,
                $nutritional_status,
                $height_for_age,
                $weight_for_age,
                $four_ps_beneficiary,
                $immunization_mr,
                $immunization_td,
                $immunization_hpv,
                $deworming,
                $ailments,
                $intervention,
                $allergies,
                $date_of_exam,
                $status,
                $remarks
            );
        }
        
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/view_student.php?id=' . $student_id . '&success=updated');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Student update error: " . $e->getMessage());
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/edit_student.php?id=' . $student_id . '&error=update_failed');
        exit();
    }
} else {
    // If not POST request, redirect back
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php');
    exit();
}
?> 