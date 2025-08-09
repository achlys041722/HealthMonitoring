<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'requests';
$nurse_id = $_SESSION['user_id'];

// Get nurse's email
$nurse_email_stmt = $conn->prepare('SELECT email FROM nurses WHERE id = ?');
$nurse_email_stmt->bind_param('i', $nurse_id);
$nurse_email_stmt->execute();
$nurse_email_stmt->bind_result($nurse_email);
$nurse_email_stmt->fetch();
$nurse_email_stmt->close();

// Get pending nurse requests for this nurse
$pending_requests_stmt = $conn->prepare('
    SELECT nr.*, s.school_name, p.full_name as principal_name 
    FROM nurse_requests nr 
    LEFT JOIN schools s ON nr.school_id = s.id 
    LEFT JOIN principals p ON s.school_name = p.elementary_school 
    WHERE nr.nurse_email = ? AND nr.status = "pending"
    ORDER BY nr.id DESC
');
$pending_requests_stmt->bind_param('s', $nurse_email);
$pending_requests_stmt->execute();
$pending_requests = $pending_requests_stmt->get_result();
$pending_requests_stmt->close();

// Get completed nurse requests for this nurse
$completed_requests_stmt = $conn->prepare('
    SELECT nr.*, s.school_name, p.full_name as principal_name 
    FROM nurse_requests nr 
    LEFT JOIN schools s ON nr.school_id = s.id 
    LEFT JOIN principals p ON s.school_name = p.elementary_school 
    WHERE nr.nurse_email = ? AND nr.status IN ("accepted", "rejected")
    ORDER BY nr.id DESC
    LIMIT 5
');
$completed_requests_stmt->bind_param('s', $nurse_email);
$completed_requests_stmt->execute();
$completed_requests = $completed_requests_stmt->get_result();
$completed_requests_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Principal Requests - Nurse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .request-item {
            padding: 1rem;
            border-left: 4px solid #007bff;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 0.25rem 0.25rem 0;
            transition: all 0.3s ease;
        }
        .request-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .request-item.accepted {
            border-left-color: #28a745;
        }
        .request-item.rejected {
            border-left-color: #dc3545;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-clipboard-list me-2"></i>Principal Requests</h2>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> <?php echo htmlspecialchars($_GET['msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Information Card -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>How It Works</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <i class="fas fa-user-tie fa-2x text-primary mb-2"></i>
                            <h6>Principal Sends Request</h6>
                            <small class="text-muted">Principal invites you to their school</small>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="fas fa-bell fa-2x text-warning mb-2"></i>
                            <h6>You Receive Notification</h6>
                            <small class="text-muted">Request appears in this section</small>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <h6>Accept or Reject</h6>
                            <small class="text-muted">Choose to work at the school or decline</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Requests -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Requests</h5>
                </div>
                <div class="card-body">
                    <?php if ($pending_requests->num_rows > 0): ?>
                        <?php while ($request = $pending_requests->fetch_assoc()): ?>
                            <div class="request-item">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1">
                                                    <i class="fas fa-school me-2"></i>
                                                    <?php echo htmlspecialchars($request['school_name'] ?? 'Unknown School'); ?>
                                                </h6>
                                                <p class="mb-1 text-muted">
                                                    <i class="fas fa-user-tie me-1"></i>
                                                    Principal: <?php echo htmlspecialchars($request['principal_name'] ?? 'Unknown Principal'); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Request ID: <?php echo $request['id']; ?>
                                                </small>
                                            </div>
                                            <div>
                                                <span class="badge bg-warning status-badge">Pending</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <form action="/Web-Based%20Health-Integrated%20Student%20Information%20System/src/nurse/nurse_request_action.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn btn-success btn-sm me-2">
                                                <i class="fas fa-check me-1"></i>Accept
                                            </button>
                                        </form>
                                        <form action="/Web-Based%20Health-Integrated%20Student%20Information%20System/src/nurse/nurse_request_action.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times me-1"></i>Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <h6 class="text-muted">No Pending Requests</h6>
                            <p class="text-muted">All requests have been processed.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Completed Requests -->
            <?php if ($completed_requests->num_rows > 0): ?>
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Completed Requests</h5>
                </div>
                <div class="card-body">
                    <?php while ($request = $completed_requests->fetch_assoc()): ?>
                        <div class="request-item <?php echo $request['status']; ?>">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="fas fa-school me-2"></i>
                                                <?php echo htmlspecialchars($request['school_name'] ?? 'Unknown School'); ?>
                                            </h6>
                                            <p class="mb-1 text-muted">
                                                <i class="fas fa-user-tie me-1"></i>
                                                Principal: <?php echo htmlspecialchars($request['principal_name'] ?? 'Unknown Principal'); ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Request ID: <?php echo $request['id']; ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php if ($request['status'] === 'accepted'): ?>
                                                <span class="badge bg-success status-badge">Accepted</span>
                                            <?php elseif ($request['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger status-badge">Rejected</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <small class="text-muted">
                                        <?php if ($request['status'] === 'accepted'): ?>
                                            <i class="fas fa-check-circle text-success me-1"></i>Request accepted
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger me-1"></i>Request rejected
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            

        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 