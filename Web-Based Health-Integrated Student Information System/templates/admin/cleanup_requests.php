<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');

// Simple admin check - you can access this directly
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cleanup Nurse Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Nurse Requests Cleanup</h2>
    
    <?php
    // Show current requests
    $requests = $conn->query("SELECT * FROM nurse_requests ORDER BY id DESC");
    ?>
    
    <h4>Current Nurse Requests:</h4>
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
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['school_id']; ?></td>
                <td><?php echo htmlspecialchars($row['nurse_email']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <h4>Cleanup Actions:</h4>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Remove Specific Email Requests
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Nurse Email:</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="sample5@gmail.com">
                        </div>
                        <button type="submit" name="action" value="remove_email" class="btn btn-warning">Remove Requests for This Email</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Remove All Pending Requests
                </div>
                <div class="card-body">
                    <p class="text-danger">This will remove ALL pending nurse requests!</p>
                    <form method="POST" onsubmit="return confirm('Are you sure? This will delete all pending requests!')">
                        <button type="submit" name="action" value="remove_pending" class="btn btn-danger">Remove All Pending Requests</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'remove_email' && !empty($_POST['email'])) {
                $email = trim($_POST['email']);
                $stmt = $conn->prepare("DELETE FROM nurse_requests WHERE nurse_email = ?");
                $stmt->bind_param('s', $email);
                if ($stmt->execute()) {
                    echo '<div class="alert alert-success mt-3">Removed all requests for: ' . htmlspecialchars($email) . '</div>';
                } else {
                    echo '<div class="alert alert-danger mt-3">Error removing requests</div>';
                }
            } elseif ($_POST['action'] === 'remove_pending') {
                $stmt = $conn->prepare("DELETE FROM nurse_requests WHERE status = 'pending'");
                if ($stmt->execute()) {
                    echo '<div class="alert alert-success mt-3">Removed all pending requests</div>';
                } else {
                    echo '<div class="alert alert-danger mt-3">Error removing pending requests</div>';
                }
            }
        }
    }
    ?>
    
    <div class="mt-4">
        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php" class="btn btn-primary">Back to Login</a>
    </div>
</div>
</body>
</html> 