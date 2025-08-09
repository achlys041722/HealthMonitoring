<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'debug';
$nurse_id = $_SESSION['user_id'];

// Get nurse's email
$nurse_email_stmt = $conn->prepare('SELECT email FROM nurses WHERE id = ?');
$nurse_email_stmt->bind_param('i', $nurse_id);
$nurse_email_stmt->execute();
$nurse_email_stmt->bind_result($nurse_email);
$nurse_email_stmt->fetch();
$nurse_email_stmt->close();

// Get all nurse requests (for debugging)
$all_requests_stmt = $conn->prepare('
    SELECT nr.*, s.school_name, p.full_name as principal_name 
    FROM nurse_requests nr 
    JOIN schools s ON nr.school_id = s.id 
    JOIN principals p ON s.school_name = p.elementary_school 
    ORDER BY nr.id DESC
');
$all_requests_stmt->execute();
$all_requests = $all_requests_stmt->get_result();
$all_requests_stmt->close();

// Get requests for this specific nurse
$my_requests_stmt = $conn->prepare('
    SELECT nr.*, s.school_name, p.full_name as principal_name 
    FROM nurse_requests nr 
    JOIN schools s ON nr.school_id = s.id 
    JOIN principals p ON s.school_name = p.elementary_school 
    WHERE nr.nurse_email = ?
    ORDER BY nr.id DESC
');
$my_requests_stmt->bind_param('s', $nurse_email);
$my_requests_stmt->execute();
$my_requests = $my_requests_stmt->get_result();
$my_requests_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Requests - Nurse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-bug me-2"></i>Debug Requests</h2>
            </div>
            
            <!-- Nurse Info -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-user-nurse me-2"></i>Your Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nurse ID:</strong> <?php echo $nurse_id; ?></p>
                    <p><strong>Your Email:</strong> <?php echo htmlspecialchars($nurse_email); ?></p>
                    <p><strong>Your Name:</strong> <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                </div>
            </div>
            
            <!-- All Requests in System -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Nurse Requests in System</h5>
                </div>
                <div class="card-body">
                    <?php if ($all_requests->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>School</th>
                                        <th>Principal</th>
                                        <th>Nurse Email</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = $all_requests->fetch_assoc()): ?>
                                        <tr <?php if ($request['nurse_email'] === $nurse_email): ?>class="table-primary"<?php endif; ?>>
                                            <td><?php echo $request['id']; ?></td>
                                            <td><?php echo htmlspecialchars($request['school_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['principal_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['nurse_email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    if ($request['status'] === 'pending') echo 'warning';
                                                    elseif ($request['status'] === 'accepted') echo 'success';
                                                    else echo 'danger';
                                                ?>"><?php echo $request['status']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No requests found in the system.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Your Requests -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Your Requests (Matching Your Email)</h5>
                </div>
                <div class="card-body">
                    <?php if ($my_requests->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>School</th>
                                        <th>Principal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = $my_requests->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $request['id']; ?></td>
                                            <td><?php echo htmlspecialchars($request['school_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['principal_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    if ($request['status'] === 'pending') echo 'warning';
                                                    elseif ($request['status'] === 'accepted') echo 'success';
                                                    else echo 'danger';
                                                ?>"><?php echo $request['status']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No requests found for your email address.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/requests.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Requests
                </a>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 