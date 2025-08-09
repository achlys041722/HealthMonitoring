<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Debug - Nurse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Email Debug Information</h2>
    
    <?php
    $nurse_id = $_SESSION['user_id'];
    
    // Get nurse's email from database
    $stmt = $conn->prepare('SELECT email FROM nurses WHERE id = ?');
    $stmt->bind_param('i', $nurse_id);
    $stmt->execute();
    $stmt->bind_result($nurse_email);
    $stmt->fetch();
    $stmt->close();
    
    echo "<p><strong>Your Nurse ID:</strong> $nurse_id</p>";
    echo "<p><strong>Your Email in Database:</strong> $nurse_email</p>";
    
    // Get all nurse requests
    $all_requests = $conn->query("SELECT * FROM nurse_requests ORDER BY id DESC");
    
    echo "<h3>All Nurse Requests in Database:</h3>";
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>ID</th><th>School ID</th><th>Nurse Email</th><th>Status</th></tr></thead>";
    echo "<tbody>";
    
    while ($row = $all_requests->fetch_assoc()) {
        $highlight = ($row['nurse_email'] === $nurse_email) ? "style='background-color: yellow;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['school_id']}</td>";
        echo "<td>{$row['nurse_email']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    // Check for exact matches
    $exact_matches = $conn->prepare("SELECT COUNT(*) as count FROM nurse_requests WHERE nurse_email = ?");
    $exact_matches->bind_param('s', $nurse_email);
    $exact_matches->execute();
    $exact_matches->bind_result($count);
    $exact_matches->fetch();
    $exact_matches->close();
    
    echo "<p><strong>Exact email matches:</strong> $count</p>";
    
    // Check for case-insensitive matches
    $case_insensitive = $conn->prepare("SELECT COUNT(*) as count FROM nurse_requests WHERE LOWER(nurse_email) = LOWER(?)");
    $case_insensitive->bind_param('s', $nurse_email);
    $case_insensitive->execute();
    $case_insensitive->bind_result($count2);
    $case_insensitive->fetch();
    $case_insensitive->close();
    
    echo "<p><strong>Case-insensitive matches:</strong> $count2</p>";
    
    // Show similar emails
    $similar = $conn->prepare("SELECT DISTINCT nurse_email FROM nurse_requests WHERE nurse_email LIKE ?");
    $search_term = "%" . substr($nurse_email, 0, 5) . "%";
    $similar->bind_param('s', $search_term);
    $similar->execute();
    $similar_result = $similar->get_result();
    
    echo "<h3>Similar Emails (first 5 characters):</h3>";
    echo "<ul>";
    while ($row = $similar_result->fetch_assoc()) {
        echo "<li>{$row['nurse_email']}</li>";
    }
    echo "</ul>";
    $similar->close();
    ?>
    
    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/requests.php" class="btn btn-primary">Back to Requests</a>
</div>
</body>
</html> 