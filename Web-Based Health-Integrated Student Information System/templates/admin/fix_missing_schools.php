<?php
require_once(__DIR__ . '/../../src/common/db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fix Missing Schools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Fix Missing Schools in Nurse Requests</h2>
    
    <?php
    // Check nurse requests with non-existent schools
    echo "<h4>Nurse Requests with Non-existent Schools:</h4>";
    $invalid_requests = $conn->query("
        SELECT nr.*, s.school_name 
        FROM nurse_requests nr 
        LEFT JOIN schools s ON nr.school_id = s.id 
        WHERE s.id IS NULL
        ORDER BY nr.id
    ");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Request ID</th>
                <th>School ID</th>
                <th>Nurse Email</th>
                <th>Status</th>
                <th>School Name</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $invalid_requests->fetch_assoc()): ?>
            <tr class="table-warning">
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['school_id']; ?></td>
                <td><?php echo htmlspecialchars($row['nurse_email']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo $row['school_name'] ?? 'NULL (School not found)'; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    // Check all schools
    echo "<h4>Available Schools:</h4>";
    $schools = $conn->query("SELECT * FROM schools ORDER BY id");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>School Name</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $schools->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['school_name']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'fix_missing_schools') {
            // Get the first available school ID
            $school_result = $conn->query("SELECT id FROM schools ORDER BY id LIMIT 1");
            if ($school_row = $school_result->fetch_assoc()) {
                $default_school_id = $school_row['id'];
                
                // Update nurse requests with non-existent schools to use the first available school
                $update_stmt = $conn->prepare("
                    UPDATE nurse_requests nr 
                    LEFT JOIN schools s ON nr.school_id = s.id 
                    SET nr.school_id = ? 
                    WHERE s.id IS NULL
                ");
                $update_stmt->bind_param('i', $default_school_id);
                
                if ($update_stmt->execute()) {
                    $affected_rows = $update_stmt->affected_rows;
                    echo "<div class='alert alert-success'>Successfully updated $affected_rows nurse request(s) with non-existent schools to school_id = $default_school_id</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error updating nurse requests</div>";
                }
                $update_stmt->close();
            } else {
                echo "<div class='alert alert-warning'>No schools found in database</div>";
            }
        }
    }
    ?>
    
    <div class="card">
        <div class="card-header">
            Fix Missing Schools Issue
        </div>
        <div class="card-body">
            <p>This will update all nurse requests that reference non-existent schools to use the first available school ID.</p>
            <form method="POST" onsubmit="return confirm('Are you sure? This will update all nurse requests with non-existent schools')">
                <button type="submit" name="action" value="fix_missing_schools" class="btn btn-warning">
                    Fix Missing Schools
                </button>
            </form>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/admin/fix_school_ids.php" class="btn btn-info">Fix School IDs (0)</a>
        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php" class="btn btn-primary">Back to Login</a>
    </div>
</div>
</body>
</html> 