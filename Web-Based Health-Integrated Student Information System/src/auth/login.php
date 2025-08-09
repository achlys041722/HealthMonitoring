<?php
session_start();
require_once(__DIR__ . '/../common/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Principal
    $stmt = $conn->prepare('SELECT id, email, password, full_name, elementary_school, contact_info FROM principals WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'principal';
            $_SESSION['full_name'] = $user['full_name'];
            if (!$user['full_name'] || !$user['elementary_school'] || !$user['contact_info']) {
                header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/principal_registration.php');
                exit();
            }
            header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/principal_dashboard.php');
            exit();
        }
    }
    // Teacher
    $stmt = $conn->prepare('SELECT id, email, password, full_name, grade_level, address, contact_info, principal_email, status FROM teachers WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'teacher';
            $_SESSION['full_name'] = $user['full_name'];
            if (!$user['full_name'] || !$user['grade_level'] || !$user['address'] || !$user['contact_info'] || !$user['principal_email']) {
                header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_registration.php');
                exit();
            }
            if ($user['status'] !== 'approved') {
                header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_registration.php?error=Your+account+is+pending+approval+by+the+principal.');
                exit();
            }
            header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_dashboard.php');
            exit();
        }
    }
    // Nurse
    $stmt = $conn->prepare('SELECT id, email, password, full_name FROM nurses WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'nurse';
            $_SESSION['full_name'] = $user['full_name'];
            header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/nurse_dashboard.php');
            exit();
        }
    }
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php?error=Invalid+email+or+password&error_type=login');
    exit();
} else {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
} 