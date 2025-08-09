<?php
session_start();
require_once(__DIR__ . '/../common/db.php');

// Check if user is logged in and is a nurse
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        $student_id = $_POST['student_id'];
        
        // Verify nurse has access to this student's school
        $nurse_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT assigned_school FROM nurses WHERE id = ?");
        $stmt->bind_param("i", $nurse_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $nurse = $result->fetch_assoc();
        
        if (!$nurse || !$nurse['assigned_school']) {
            throw new Exception("Nurse not assigned to any school");
        }
        
        // Verify student belongs to nurse's assigned school
        $stmt = $conn->prepare("
            SELECT s.id 
            FROM students s 
            JOIN grade_levels gl ON s.grade_level_id = gl.id 
            JOIN schools sch ON gl.school_id = sch.id 
            WHERE s.id = ? AND sch.school_name = ?
        ");
        $stmt->bind_param("is", $student_id, $nurse['assigned_school']);
        $stmt->execute();
        $student_result = $stmt->get_result();
        
        if (!$student_result->fetch_assoc()) {
            throw new Exception("Student not found in nurse's assigned school");
        }
        
        // Check if health record exists
        $stmt = $conn->prepare("SELECT id FROM student_health WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $health_result = $stmt->get_result();
        $existing_health = $health_result->fetch_assoc();
        
        // Prepare data
        $height = !empty($_POST['height']) ? $_POST['height'] : null;
        $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
        $bmi = !empty($_POST['bmi']) ? $_POST['bmi'] : null;
        $height_for_age = !empty($_POST['height_for_age']) ? $_POST['height_for_age'] : null;
        $weight_for_age = !empty($_POST['weight_for_age']) ? $_POST['weight_for_age'] : null;
        $nutritional_status = trim($_POST['nutritional_status'] ?? '');
        $four_ps_beneficiary = isset($_POST['four_ps_beneficiary']) ? $_POST['four_ps_beneficiary'] : null;
        $immunization_mr = $_POST['immunization_mr'] ?? 'None';
        $immunization_td = $_POST['immunization_td'] ?? 'None';
        $immunization_hpv = $_POST['immunization_hpv'] ?? 'None';
        $deworming = $_POST['deworming'] ?? 'None';
        $ailments = trim($_POST['ailments'] ?? '');
        $intervention = $_POST['intervention'] ?? '';
        $allergies = trim($_POST['allergies'] ?? '');
        $date_of_exam = !empty($_POST['date_of_exam']) ? $_POST['date_of_exam'] : date('Y-m-d');
        $status = trim($_POST['status'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');
        
        // Debug: Log the received nutritional_status
        error_log("DEBUG: Received nutritional_status from POST: '" . $nutritional_status . "'");
        error_log("DEBUG: POST data for nutritional_status: " . print_r($_POST['nutritional_status'] ?? 'NOT_SET', true));
        
        if ($existing_health) {
            // Update existing health record - Use individual updates to avoid parameter order issues
            $updates = [
                "height = ?" => $height,
                "weight = ?" => $weight,
                "bmi = ?" => $bmi,
                "height_for_age = ?" => $height_for_age,
                "weight_for_age = ?" => $weight_for_age,
                "nutritional_status = ?" => $nutritional_status,
                "four_ps_beneficiary = ?" => $four_ps_beneficiary,
                "immunization_mr = ?" => $immunization_mr,
                "immunization_td = ?" => $immunization_td,
                "immunization_hpv = ?" => $immunization_hpv,
                "deworming = ?" => $deworming,
                "ailments = ?" => $ailments,
                "intervention = ?" => $intervention,
                "allergies = ?" => $allergies,
                "date_of_exam = ?" => $date_of_exam,
                "status = ?" => $status,
                "remarks = ?" => $remarks,
                "updated_at = NOW()" => null
            ];
            
            $update_parts = [];
            $update_values = [];
            $update_types = "";
            
            foreach ($updates as $field => $value) {
                if ($value !== null) {
                    $update_parts[] = $field;
                    $update_values[] = $value;
                    if (is_int($value)) {
                        $update_types .= "i";
                    } elseif (is_float($value)) {
                        $update_types .= "d";
                    } else {
                        $update_types .= "s";
                    }
                }
            }
            
            $update_values[] = $student_id;
            $update_types .= "i";
            
            $sql = "UPDATE student_health SET " . implode(", ", $update_parts) . " WHERE student_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($update_types, ...$update_values);
            
            // Debug: Log the actual values being passed
            error_log("DEBUG: nutritional_status value being passed: '" . $nutritional_status . "'");
            error_log("DEBUG: four_ps_beneficiary value being passed: '" . $four_ps_beneficiary . "'");
            error_log("DEBUG: SQL: " . $sql);
        } else {
            // Insert new health record
            $stmt = $conn->prepare("
                INSERT INTO student_health (student_id, height, weight, bmi, height_for_age, 
                    weight_for_age, nutritional_status, four_ps_beneficiary, immunization_mr, 
                    immunization_td, immunization_hpv, deworming, ailments, intervention, 
                    allergies, date_of_exam, status, remarks, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->bind_param("idddsissssssssssss", $student_id, $height, $weight, $bmi, 
                             $height_for_age, $weight_for_age, $nutritional_status, 
                             $four_ps_beneficiary, $immunization_mr, $immunization_td, 
                             $immunization_hpv, $deworming, $ailments, $intervention, 
                             $allergies, $date_of_exam, $status, $remarks);
        }
        
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_assessment.php?id=' . $student_id . '&success=assessment_saved');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Nurse save assessment error: " . $e->getMessage());
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_assessment.php?id=' . $student_id . '&error=save_failed');
        exit();
    }
} else {
    // If not POST request, redirect back to students list
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/students.php');
    exit();
}
?> 