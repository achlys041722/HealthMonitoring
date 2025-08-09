<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: ../templates/login.php?error=Access+denied');
    exit();
}
require_once(__DIR__ . '/../common/db.php');

$school_name = trim($_POST['school_name']);
$school_lrn = trim($_POST['school_lrn']);
$principal_id = $_SESSION['user_id'];

// Check if LRN already exists
$check = $conn->prepare('SELECT id FROM schools WHERE lrn = ?');
$check->bind_param('s', $school_lrn);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    header('Location: ../templates/school_registration.php?error=School+LRN+already+exists');
    exit();
}

// Insert school
$stmt = $conn->prepare('INSERT INTO schools (name, lrn, principal_id) VALUES (?, ?, ?)');
$stmt->bind_param('ssi', $school_name, $school_lrn, $principal_id);
if ($stmt->execute()) {
    header('Location: ../templates/principal_dashboard.php');
    exit();
} else {
    header('Location: ../templates/school_registration.php?error=Failed+to+register+school');
    exit();
} 