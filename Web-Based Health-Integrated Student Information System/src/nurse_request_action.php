<?php
session_start();
require_once(__DIR__ . '/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    if ($action === 'accept') {
        $stmt = $conn->prepare('UPDATE nurse_requests SET status = "accepted" WHERE id = ?');
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare('UPDATE nurse_requests SET status = "rejected" WHERE id = ?');
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
    }
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/requests.php');
    exit();
} else {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/requests.php');
    exit();
} 