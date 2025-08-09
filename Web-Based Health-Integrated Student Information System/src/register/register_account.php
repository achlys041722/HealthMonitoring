<?php
session_start();
require_once(__DIR__ . '/../common/db.php');
require_once(__DIR__ . '/../common/notify.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';

    // Only allow registration if admin password is correct
    if ($admin_password !== 'sample') {
        header('Location: ../../templates/register/login.php?error=Invalid+admin+password&error_type=register');
        exit();
    }
    if ($password !== $confirm_password) {
        header('Location: ../../templates/register/login.php?error=Passwords+do+not+match&error_type=register');
        exit();
    }
    if (!$role || !$email || !$full_name || !$password) {
        header('Location: ../../templates/register/login.php?error=Please+fill+all+fields&error_type=register');
        exit();
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    if ($role === 'principal') {
        $stmt = $conn->prepare('INSERT INTO principals (email, password, full_name, elementary_school, contact_info) VALUES (?, ?, ?, "", "")');
        $stmt->bind_param('sss', $email, $hashed_password, $full_name);
    } elseif ($role === 'teacher') {
        // Check if email exists in teachers table
        $check = $conn->prepare('SELECT status FROM teachers WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();
        $result = $check->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'rejected') {
                // Update the rejected teacher record
                $stmt = $conn->prepare('UPDATE teachers SET password = ?, full_name = ?, status = "pending" WHERE email = ?');
                $stmt->bind_param('sss', $hashed_password, $full_name, $email);
                if ($stmt->execute()) {
                    // Notify principal of reactivation
                    $principal_email = $_POST['principal_email'] ?? '';
                    if ($principal_email) {
                        $principal_lookup = $conn->prepare('SELECT id FROM principals WHERE email = ?');
                        $principal_lookup->bind_param('s', $principal_email);
                        $principal_lookup->execute();
                        $principal_lookup->bind_result($principal_id);
                        if ($principal_lookup->fetch()) {
                            add_notification($principal_id, 'principal', 'teacher_request', 'A teacher registration request was reactivated: ' . $full_name);
                        }
                        $principal_lookup->close();
                    }
                    header('Location: ../../templates/register/login.php?success=Account+reactivated+successfully');
                    exit();
                } else {
                    header('Location: ../../templates/register/login.php?error=Account+reactivation+failed&error_type=register');
                    exit();
                }
            } else {
                header('Location: ../../templates/register/login.php?error=Email+already+exists&error_type=register');
                exit();
            }
        }
        // If not exists, insert new
        $stmt = $conn->prepare('INSERT INTO teachers (email, password, full_name, grade_level, address, contact_info, principal_email, status) VALUES (?, ?, ?, "", "", "", ?, "pending")');
        $principal_email = $_POST['principal_email'] ?? '';
        $stmt->bind_param('ssss', $email, $hashed_password, $full_name, $principal_email);
    } elseif ($role === 'nurse') {
        $stmt = $conn->prepare('INSERT INTO nurses (email, password, full_name, birthdate, address, contact_info, assigned_school) VALUES (?, ?, ?, NULL, NULL, NULL, NULL)');
        $stmt->bind_param('sss', $email, $hashed_password, $full_name);
    } else {
        header('Location: ../../templates/register/login.php?error=Invalid+role&error_type=register');
        exit();
    }
    if ($stmt->execute()) {
        // Notify principal of new teacher registration
        if ($role === 'teacher') {
            $principal_email = $_POST['principal_email'] ?? '';
            if ($principal_email) {
                $principal_lookup = $conn->prepare('SELECT id FROM principals WHERE email = ?');
                $principal_lookup->bind_param('s', $principal_email);
                $principal_lookup->execute();
                $principal_lookup->bind_result($principal_id);
                if ($principal_lookup->fetch()) {
                    add_notification($principal_id, 'principal', 'teacher_request', 'A new teacher registration request was submitted: ' . $full_name);
                }
                $principal_lookup->close();
            }
        }
        header('Location: ../../templates/register/login.php?success=Account+created+successfully');
        exit();
    } else {
        if ($conn->errno === 1062) { // Duplicate entry
            header('Location: ../../templates/register/login.php?error=Email+already+exists&error_type=register');
        } else {
            header('Location: ../../templates/register/login.php?error=Account+creation+failed&error_type=register');
        }
        exit();
    }
} else {
    header('Location: ../../templates/register/login.php');
    exit();
} 