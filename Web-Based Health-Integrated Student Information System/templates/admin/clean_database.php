<?php
require_once(__DIR__ . '/../../src/common/db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clean Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Clean Database - Reset Schools</h2>
    
    <?php
    // Show current state
    echo "<h4>Current Schools:</h4>";
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
    // Show current nurse requests
    echo "<h4>Current Nurse Requests:</h4>";
    $requests = $conn->query("SELECT * FROM nurse_requests ORDER BY id");
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'clean_database') {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // 1. Clear all nurse requests
                $conn->query("DELETE FROM nurse_requests");
                echo "<div class='alert alert-info'>Cleared all nurse requests</div>";
                
                // 2. Clear all schools
                $conn->query("DELETE FROM schools");
                echo "<div class='alert alert-info'>Cleared all schools</div>";
                
                // 3. Reset auto-increment
                $conn->query("ALTER TABLE schools AUTO_INCREMENT = 1");
                $conn->query("ALTER TABLE nurse_requests AUTO_INCREMENT = 1");
                echo "<div class='alert alert-info'>Reset auto-increment counters</div>";
                
                // 4. Re-insert the 4 standard schools
                $standard_schools = [
                    'Tahusan Elementary School',
                    'Biasong Elementary School', 
                    'Otama Elementary School',
                    'Hinunangan West Central School'
                ];
                
                $insert_school = $conn->prepare("INSERT INTO schools (school_name) VALUES (?)");
                foreach ($standard_schools as $school_name) {
                    $insert_school->bind_param('s', $school_name);
                    $insert_school->execute();
                }
                $insert_school->close();
                echo "<div class='alert alert-success'>Re-inserted 4 standard schools</div>";
                
                // 5. Re-create nurse requests for sample@gmail.com (accepted)
                $nurse_email = 'sample@gmail.com';
                $schools_result = $conn->query("SELECT id FROM schools ORDER BY id");
                $school_ids = [];
                while ($row = $schools_result->fetch_assoc()) {
                    $school_ids[] = $row['id'];
                }
                
                $insert_request = $conn->prepare("INSERT INTO nurse_requests (school_id, nurse_email, status) VALUES (?, ?, 'accepted')");
                foreach ($school_ids as $school_id) {
                    $insert_request->bind_param('is', $school_id, $nurse_email);
                    $insert_request->execute();
                }
                $insert_request->close();
                echo "<div class='alert alert-success'>Re-created accepted nurse requests for sample@gmail.com</div>";
                
                // Commit transaction
                $conn->commit();
                echo "<div class='alert alert-success'><strong>Database cleaned successfully!</strong></div>";
                
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }
    ?>
    
    <div class="card">
        <div class="card-header bg-warning">
            <h5 class="mb-0">⚠️ Clean Database</h5>
        </div>
        <div class="card-body">
            <p><strong>This will:</strong></p>
            <ul>
                <li>Delete ALL schools and nurse requests</li>
                <li>Reset auto-increment counters</li>
                <li>Re-insert the 4 standard schools with clean IDs (1, 2, 3, 4)</li>
                <li>Re-create accepted nurse requests for sample@gmail.com</li>
            </ul>
            <p class="text-danger"><strong>Warning:</strong> This will permanently delete all existing data!</p>
            <form method="POST" onsubmit="return confirm('Are you absolutely sure? This will delete ALL schools and nurse requests!')">
                <button type="submit" name="action" value="clean_database" class="btn btn-danger">
                    Clean Database
                </button>
            </form>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php" class="btn btn-primary">Back to Login</a>
    </div>
</div>
</body>
</html> 