<?php
require_once(__DIR__ . '/../../src/common/db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check Schools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Schools Table Check</h2>
    
    <?php
    // Check schools table
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
    // Check nurse requests with school details
    echo "<h4>Nurse Requests with School Details:</h4>";
    $requests = $conn->query("
        SELECT nr.*, s.school_name, s.id as school_table_id
        FROM nurse_requests nr 
        LEFT JOIN schools s ON nr.school_id = s.id 
        ORDER BY nr.id DESC
    ");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Request ID</th>
                <th>School ID (Request)</th>
                <th>School ID (Table)</th>
                <th>School Name</th>
                <th>Nurse Email</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $requests->fetch_assoc()): ?>
            <tr <?php echo ($row['school_name'] === null) ? 'class="table-warning"' : ''; ?>>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['school_id']; ?></td>
                <td><?php echo $row['school_table_id']; ?></td>
                <td><?php echo htmlspecialchars($row['school_name'] ?? 'NULL'); ?></td>
                <td><?php echo htmlspecialchars($row['nurse_email']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    // Check principals table
    echo "<h4>Principals:</h4>";
    $principals = $conn->query("SELECT * FROM principals ORDER BY id");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Elementary School</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $principals->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['elementary_school']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html> 