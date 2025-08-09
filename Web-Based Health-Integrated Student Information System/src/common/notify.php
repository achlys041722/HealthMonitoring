<?php
require_once(__DIR__ . '/db.php');

function add_notification($user_id, $user_role, $type, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $user_role, $type, $message);
    $stmt->execute();
}