<?php
session_start();
require_once(__DIR__ . '/../common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $elementary_school = trim($_POST['elementary_school']);
    $school_lrn = trim($_POST['school_lrn']);
    $contact_info = trim($_POST['contact_info']);
    $stmt = $conn->prepare('UPDATE principals SET full_name = ?, elementary_school = ?, school_lrn = ?, contact_info = ? WHERE id = ?');
    $stmt->bind_param('ssssi', $full_name, $elementary_school, $school_lrn, $contact_info, $_SESSION['user_id']);
    $stmt->execute();
    $_SESSION['full_name'] = $full_name;
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/principal_dashboard.php');
    exit();
} else {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/principal_registration.php');
    exit();
} 