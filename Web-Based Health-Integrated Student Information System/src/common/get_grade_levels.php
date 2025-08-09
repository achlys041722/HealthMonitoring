<?php
require_once(__DIR__ . '/db.php');

header('Content-Type: application/json');

if (isset($_GET['school_id'])) {
    $school_id = (int)$_GET['school_id'];
    
    try {
        $stmt = $conn->prepare("SELECT id, grade_name FROM grade_levels WHERE school_id = ? ORDER BY 
            CASE grade_name 
                WHEN 'Kinder' THEN 1
                WHEN 'Grade 1' THEN 2
                WHEN 'Grade 2' THEN 3
                WHEN 'Grade 3' THEN 4
                WHEN 'Grade 4' THEN 5
                WHEN 'Grade 5' THEN 6
                WHEN 'Grade 6' THEN 7
                ELSE 8
            END");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grade_levels = [];
        while ($row = $result->fetch_assoc()) {
            $grade_levels[] = $row;
        }
        
        echo json_encode($grade_levels);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'School ID is required']);
}
?> 