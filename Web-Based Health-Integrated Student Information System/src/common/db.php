<?php
// src/common/db.php
$host = 'localhost';
$db   = 'health_student_info_system';
$user = 'root'; // Default XAMPP user
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
// Use $conn in your scripts to interact with the database 