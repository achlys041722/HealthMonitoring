<?php
require_once(__DIR__ . '/../../src/common/db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Nurse Schools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Debug Nurse Schools Issue</h2>
    
    <?php
    // Check all nurse requests
    echo "<h4>All Nurse Requests:</h4>";
    $requests = $conn->query("SELECT * FROM nurse_requests ORDER BY id DESC");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>School ID</th>
                <th>Nurse Email</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $requests->fetch_assoc()): ?>
            <tr <?php echo ($row['status'] === 'accepted') ? 'class="table-success"' : ''; ?>>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['school_id']; ?></td>
                <td><?php echo htmlspecialchars($row['nurse_email']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    // Check all schools
    echo "<h4>All Schools:</h4>";
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
    // Test the exact query that the schools page uses
    echo "<h4>Test Query for sample@gmail.com:</h4>";
    $test_email = 'sample@gmail.com';
    $test_query = "
        SELECT DISTINCT s.id, s.school_name 
        FROM schools s 
        JOIN nurse_requests nr ON s.id = nr.school_id 
        WHERE nr.nurse_email = ? AND nr.status = 'accepted'
        ORDER BY s.school_name
    ";
    $test_stmt = $conn->prepare($test_query);
    $test_stmt->bind_param('s', $test_email);
    $test_stmt->execute();
    $test_result = $test_stmt->get_result();
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>School ID</th>
                <th>School Name</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $test_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['school_name']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    // Check if there are any accepted requests with school_id = 0
    echo "<h4>Accepted Requests with school_id = 0:</h4>";
    $zero_school_requests = $conn->query("SELECT * FROM nurse_requests WHERE school_id = 0 AND status = 'accepted'");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>School ID</th>
                <th>Nurse Email</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $zero_school_requests->fetch_assoc()): ?>
            <tr class="table-warning">
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['school_id']; ?></td>
                <td><?php echo htmlspecialchars($row['nurse_email']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div class="mt-4">
        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/admin/fix_school_ids.php" class="btn btn-warning">Fix School IDs</a>
        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php" class="btn btn-primary">Back to Login</a>
    </div>
</div>
</body>
</html> 