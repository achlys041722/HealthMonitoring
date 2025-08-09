<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'teachers';
// Fetch principal's email
$stmt = $conn->prepare('SELECT email FROM principals WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($principal_email);
$stmt->fetch();
$stmt->close();
// Fetch only approved teachers for this principal
$teachers_stmt = $conn->prepare('SELECT id, full_name, email, grade_level FROM teachers WHERE principal_email = ? AND status = "approved"');
$teachers_stmt->bind_param('s', $principal_email);
$teachers_stmt->execute();
$teachers = $teachers_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teachers Management - Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem 0.5rem 0 0 !important;
            border: none;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
        }
        .table tbody tr {
            transition: all 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table tbody td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-color: #e9ecef;
        }
        .teacher-name {
            font-weight: 600;
            color: #2c3e50;
        }
        .teacher-email {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .grade-badge {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .btn-remove {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        .btn-remove:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .stats-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-chalkboard-teacher me-2"></i>Teachers Management</h2>
            </div>
            
            <!-- Statistics Card -->
            <div class="row">
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $teachers ? $teachers->num_rows : 0; ?></div>
                        <div class="stats-label"><i class="fas fa-users me-2"></i>Total Teachers</div>
                    </div>
                </div>
            </div>
            
            <!-- Teachers Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Approved Teachers</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($teachers && $teachers->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>Name</th>
                                    <th><i class="fas fa-envelope me-2"></i>Email</th>
                                    <th><i class="fas fa-graduation-cap me-2"></i>Grade Level</th>
                                    <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $teachers->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="teacher-name">
                                                <i class="fas fa-user-circle me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($row['full_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="teacher-email">
                                                <i class="fas fa-envelope me-2 text-muted"></i>
                                                <?php echo htmlspecialchars($row['email']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="grade-badge">
                                                <i class="fas fa-graduation-cap me-1"></i>
                                                <?php echo htmlspecialchars($row['grade_level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form action="../../src/principal/manage_teacher.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently remove this teacher? This action cannot be undone.');">
                                                <input type="hidden" name="teacher_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="remove">
                                                <button type="submit" class="btn btn-remove">
                                                    <i class="fas fa-trash me-1"></i>Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h5>No Approved Teachers</h5>
                        <p>There are currently no approved teachers in your school.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 