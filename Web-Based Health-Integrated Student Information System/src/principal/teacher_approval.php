<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: ../templates/login.php?error=Access+denied');
    exit();
}
require_once(__DIR__ . '/../common/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['teacher_id'], $_POST['action'])) {
    $teacher_id = intval($_POST['teacher_id']);
    $action = $_POST['action'];
    if ($action === 'approve') {
        $status = 'approved';
    } elseif ($action === 'reject') {
        $status = 'rejected';
    } else {
        header('Location: ../templates/principal_dashboard.php?error=Invalid+action');
        exit();
    }
    $stmt = $conn->prepare('UPDATE teachers SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $teacher_id);
    $stmt->execute();
    header('Location: ../templates/principal_dashboard.php?msg=Teacher+status+updated');
    exit();
} else {
    header('Location: ../templates/principal_dashboard.php?error=Invalid+request');
    exit();
} 