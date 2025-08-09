<?php
session_start();
require_once(__DIR__ . '/../common/db.php');

// Check if user is logged in and is a principal
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = (int)$_POST['student_id'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Delete student health records first (due to foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM student_health WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        
        // Delete student personal information
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php?success=student_deleted');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php?error=delete_failed');
        exit();
    }
} else {
    // If not POST request or no student_id, redirect back
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php');
    exit();
}
?> 