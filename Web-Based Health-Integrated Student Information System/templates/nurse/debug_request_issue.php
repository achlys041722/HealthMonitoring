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
    <title>Debug Request Issue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Debug Request Issue</h2>
    
    <?php
    $nurse_id = $_SESSION['user_id'];
    
    // Get nurse's email
    $nurse_email_stmt = $conn->prepare('SELECT email FROM nurses WHERE id = ?');
    $nurse_email_stmt->bind_param('i', $nurse_id);
    $nurse_email_stmt->execute();
    $nurse_email_stmt->bind_result($nurse_email);
    $nurse_email_stmt->fetch();
    $nurse_email_stmt->close();
    
    echo "<h4>Your Information:</h4>";
    echo "<p><strong>Nurse ID:</strong> $nurse_id</p>";
    echo "<p><strong>Your Email:</strong> $nurse_email</p>";
    
    echo "<hr>";
    
    // Get ALL nurse requests
    echo "<h4>All Nurse Requests in Database:</h4>";
    $all_requests = $conn->query("SELECT * FROM nurse_requests ORDER BY id DESC");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>School ID</th>
                <th>Nurse Email</th>
                <th>Status</th>
                <th>Matches You?</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $all_requests->fetch_assoc()): ?>
            <tr <?php echo ($row['nurse_email'] === $nurse_email) ? 'class="table-warning"' : ''; ?>>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['school_id']; ?></td>
                <td><?php echo htmlspecialchars($row['nurse_email']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td>
                    <?php if ($row['nurse_email'] === $nurse_email): ?>
                        <span class="badge bg-success">YES</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">NO</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    // Check for exact matches
    $exact_matches = $conn->prepare("SELECT COUNT(*) as count FROM nurse_requests WHERE nurse_email = ?");
    $exact_matches->bind_param('s', $nurse_email);
    $exact_matches->execute();
    $exact_matches->bind_result($count);
    $exact_matches->fetch();
    $exact_matches->close();
    
    // Check for pending matches
    $pending_matches = $conn->prepare("SELECT COUNT(*) as count FROM nurse_requests WHERE nurse_email = ? AND status = 'pending'");
    $pending_matches->bind_param('s', $nurse_email);
    $pending_matches->execute();
    $pending_matches->bind_result($pending_count);
    $pending_matches->fetch();
    $pending_matches->close();
    
    echo "<h4>Summary:</h4>";
    echo "<p><strong>Total requests for your email:</strong> $count</p>";
    echo "<p><strong>Pending requests for your email:</strong> $pending_count</p>";
    
    if ($count == 0) {
        echo "<div class='alert alert-warning'>No requests found for your email. The principal might have used a different email address.</div>";
    } elseif ($pending_count == 0) {
        echo "<div class='alert alert-info'>You have requests but none are pending. Check the 'Recent Completed Requests' section.</div>";
    } else {
        echo "<div class='alert alert-success'>You have $pending_count pending request(s). They should appear in the Principal Requests section.</div>";
    }
    ?>
    
    <hr>
    
    <h4>Possible Solutions:</h4>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    If No Requests Found
                </div>
                <div class="card-body">
                    <p>The principal might have used a different email address. Check:</p>
                    <ul>
                        <li>Email case sensitivity (sample5@gmail.com vs Sample5@gmail.com)</li>
                        <li>Extra spaces in the email</li>
                        <li>Different email domain (.com vs .org)</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    If Requests Exist But Not Showing
                </div>
                <div class="card-body">
                    <p>Check the request status:</p>
                    <ul>
                        <li>If status is 'accepted' or 'rejected', check "Recent Completed Requests"</li>
                        <li>If status is 'pending', refresh the page</li>
                        <li>Check if there are any SQL errors</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/requests.php" class="btn btn-primary">Back to Principal Requests</a>
        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/admin/cleanup_requests.php" class="btn btn-warning">Cleanup Requests</a>
    </div>
</div>
</body>
</html> 