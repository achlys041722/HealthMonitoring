<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'notifications';
$principal_id = $_SESSION['user_id'];

// Fetch principal's information including school
$stmt = $conn->prepare('SELECT email, elementary_school FROM principals WHERE id = ?');
$stmt->bind_param('i', $principal_id);
$stmt->execute();
$stmt->bind_result($principal_email, $elementary_school);
$stmt->fetch();
$stmt->close();

// Get school_id from schools table
$school_stmt = $conn->prepare('SELECT id FROM schools WHERE school_name = ?');
$school_stmt->bind_param('s', $elementary_school);
$school_stmt->execute();
$school_stmt->bind_result($school_id);
$school_stmt->fetch();
$school_stmt->close();

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_role = 'principal' ORDER BY created_at DESC");
$stmt->bind_param('i', $principal_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Fetch pending teacher requests
$teachers_stmt = $conn->prepare('SELECT id, full_name, email, grade_level FROM teachers WHERE principal_email = ? AND status = "pending"');
$teachers_stmt->bind_param('s', $principal_email);
$teachers_stmt->execute();
$teacher_requests = $teachers_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Principal Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            border-bottom: 2px solid transparent;
        }
        .nav-tabs .nav-link.active {
            color: #007bff;
            background-color: transparent;
            border-bottom: 2px solid #007bff;
        }
        .tab-content {
            padding: 20px 0;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .fas, .fa {
            display: inline-block !important;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 1;
        }
        .me-2 {
            margin-right: 0.5rem !important;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-bell me-2"></i>Notification & Management</h2>
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
            
            <!-- Management Tools -->
            <div class="row mb-4">
                <!-- Invite Nurse -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-user-nurse me-2"></i>Invite Nurse</h5>
                        </div>
                        <div class="card-body">
                            <form action="/Web-Based%20Health-Integrated%20Student%20Information%20System/src/principal/invite_nurse.php" method="POST">
                                <div class="mb-3">
                                    <label for="nurse_email" class="form-label">Nurse Email</label>
                                    <input type="email" class="form-control" id="nurse_email" name="nurse_email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="school_id" class="form-label">School</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($elementary_school); ?>" readonly>
                                    <input type="hidden" name="school_id" value="<?php echo $school_id; ?>">
                                </div>
                                <button type="submit" class="btn btn-success w-100">Send Invitation</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- School Profile -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-school me-2"></i>School Profile</h5>
                        </div>
                        <div class="card-body">
                            <form action="/Web-Based%20Health-Integrated%20Student%20Information%20System/src/register/register_principal.php" method="POST">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Principal Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="elementary_school" class="form-label">School Name</label>
                                    <select class="form-select" id="elementary_school" name="elementary_school" required>
                                        <option value="">Select School</option>
                                        <option value="Tahusan Elementary School" <?php echo $elementary_school === 'Tahusan Elementary School' ? 'selected' : ''; ?>>Tahusan Elementary School</option>
                                        <option value="Biasong Elementary School" <?php echo $elementary_school === 'Biasong Elementary School' ? 'selected' : ''; ?>>Biasong Elementary School</option>
                                        <option value="Otama Elementary School" <?php echo $elementary_school === 'Otama Elementary School' ? 'selected' : ''; ?>>Otama Elementary School</option>
                                        <option value="Hinunangan West Central School" <?php echo $elementary_school === 'Hinunangan West Central School' ? 'selected' : ''; ?>>Hinunangan West Central School</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-info w-100">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Teacher Requests -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Pending Teacher Requests</h5>
                </div>
                <div class="card-body">
                    <?php if ($teacher_requests && $teacher_requests->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Grade Level</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $teacher_requests->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($row['grade_level']); ?></span></td>
                                    <td>
                                        <form action="../../src/principal/manage_teacher.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="teacher_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form action="../../src/principal/manage_teacher.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="teacher_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center py-3">No pending teacher requests.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Notifications -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i>System Notifications</h5>
                </div>
                <div class="card-body">
                    <?php if ($notifications->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($notif = $notifications->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $notif['type']))); ?></span></td>
                                    <td><?php echo htmlspecialchars($notif['message']); ?></td>
                                    <td><span class="badge bg-<?php echo $notif['status'] === 'unread' ? 'warning' : 'success'; ?>"><?php echo htmlspecialchars(ucfirst($notif['status'])); ?></span></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center py-3">No notifications found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 