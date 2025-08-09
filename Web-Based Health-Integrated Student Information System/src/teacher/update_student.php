<?php
session_start();
require_once(__DIR__ . '/../common/db.php');

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get student ID and teacher ID
    $student_id = (int)$_POST['student_id'];
    $teacher_id = $_SESSION['user_id'];
    
    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'sex', 'birthdate', 'lrn', 'address', 'parent_name'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/edit_student.php?id=' . $student_id . '&error=invalid_data');
            exit();
        }
    }
    
    try {
        // Verify that the teacher can edit this student (student belongs to teacher's assigned grade)
        $verify_stmt = $conn->prepare("
            SELECT s.id FROM students s
            JOIN grade_levels gl ON s.grade_level_id = gl.id
            JOIN teachers t ON gl.id = t.grade_level_id
            WHERE s.id = ? AND t.id = ?
        ");
        $verify_stmt->bind_param("ii", $student_id, $teacher_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0) {
            header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/my_students.php?error=unauthorized');
            exit();
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update student information (teachers cannot change school_id or grade_level_id)
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
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->bind_param("ssssssssi", 
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['sex'],
            $_POST['birthdate'],
            $_POST['lrn'],
            $_POST['address'],
            $_POST['parent_name'],
            $student_id
        );
        
        $stmt->execute();
        
        // Check if health record exists
        $check_health = $conn->prepare("SELECT id FROM student_health WHERE student_id = ?");
        $check_health->bind_param("i", $student_id);
        $check_health->execute();
        $health_result = $check_health->get_result();
        
        if ($health_result->num_rows > 0) {
            // Update existing health record
            $stmt = $conn->prepare("
                UPDATE student_health SET 
                    height = ?, 
                    weight = ?, 
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
            
            $stmt->bind_param("dddsisssssssssssssi", 
                $_POST['height'] ?: null,
                $_POST['weight'] ?: null,
                $_POST['bmi'] ?: null,
                $_POST['nutritional_status'],
                $_POST['height_for_age'],
                $_POST['weight_for_age'],
                $_POST['four_ps_beneficiary'],
                $_POST['immunization_mr'],
                $_POST['immunization_td'],
                $_POST['immunization_hpv'],
                $_POST['deworming'],
                $_POST['ailments'],
                $_POST['intervention'],
                $_POST['allergies'],
                $_POST['date_of_exam'] ?: null,
                $_POST['status'],
                $_POST['remarks'],
                $student_id
            );
            
        } else {
            // Insert new health record
            $stmt = $conn->prepare("
                INSERT INTO student_health (
                    student_id, height, weight, bmi, nutritional_status, height_for_age, 
                    weight_for_age, four_ps_beneficiary, immunization_mr, immunization_td, 
                    immunization_hpv, deworming, ailments, intervention, 
                    allergies, date_of_exam, status, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("idddsisssssssssssss", 
                $student_id,
                $_POST['height'] ?: null,
                $_POST['weight'] ?: null,
                $_POST['bmi'] ?: null,
                $_POST['nutritional_status'],
                $_POST['height_for_age'],
                $_POST['weight_for_age'],
                $_POST['four_ps_beneficiary'],
                $_POST['immunization_mr'],
                $_POST['immunization_td'],
                $_POST['immunization_hpv'],
                $_POST['deworming'],
                $_POST['ailments'],
                $_POST['intervention'],
                $_POST['allergies'],
                $_POST['date_of_exam'] ?: null,
                $_POST['status'],
                $_POST['remarks']
            );
        }
        
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/view_student.php?id=' . $student_id . '&success=updated');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/edit_student.php?id=' . $student_id . '&error=update_failed');
        exit();
    }
} else {
    // If not POST request, redirect back
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/my_students.php');
    exit();
}
?> 