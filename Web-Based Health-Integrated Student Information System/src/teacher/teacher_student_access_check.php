<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../templates/login.php?error=Access+denied');
    exit();
}
require_once(__DIR__ . '/../common/db.php');
$teacher_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT status FROM teachers WHERE id = ?');
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();
if ($status !== 'approved') {
    echo '<div class="alert alert-warning text-center mt-5">Your account is pending approval by the principal. You cannot manage students until approved.</div>';
    exit();
} 