<?php
require_once(__DIR__ . '/db.php');

header('Content-Type: application/json');

if (isset($_GET['school_name'])) {
    $school_name = trim($_GET['school_name']);
    
    try {
        $stmt = $conn->prepare("SELECT id FROM schools WHERE school_name = ?");
        $stmt->bind_param("s", $school_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $school = $result->fetch_assoc();
        
        if ($school) {
            echo json_encode(['school_id' => $school['id']]);
        } else {
            echo json_encode(['error' => 'School not found']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'School name is required']);
}
?> 