<?php
session_start();
require_once(__DIR__ . '/../common/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $elementary_school = trim($_POST['elementary_school']);
    $grade_level = $_POST['grade_level_id']; // This is actually the grade name, not an ID
    $address = trim($_POST['address']);
    $contact_info = trim($_POST['contact_info']);
    
    // Validate required fields
    if (empty($email) || empty($full_name) || empty($elementary_school) || empty($grade_level) || empty($address) || empty($contact_info)) {
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_registration.php?error=missing_fields');
        exit();
    }
    
    try {
        // Get the principal's email for the selected school
        $stmt = $conn->prepare("SELECT p.email FROM principals p WHERE p.elementary_school = ?");
        $stmt->bind_param("s", $elementary_school);
        $stmt->execute();
        $result = $stmt->get_result();
        $principal = $result->fetch_assoc();
        
        if (!$principal) {
            header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_registration.php?error=no_principal');
            exit();
        }
        
        $principal_email = $principal['email'];
        
        // Update teacher record
        $stmt = $conn->prepare("UPDATE teachers SET full_name = ?, elementary_school = ?, grade_level = ?, address = ?, contact_info = ?, principal_email = ?, status = 'pending' WHERE email = ?");
        $stmt->bind_param("sssssss", $full_name, $elementary_school, $grade_level, $address, $contact_info, $principal_email, $email);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_registration.php?success=profile_updated');
        } else {
            header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_registration.php?error=update_failed');
        }
        
    } catch (Exception $e) {
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_registration.php?error=database_error');
    }
    
} else {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_registration.php');
}
exit();
?> 