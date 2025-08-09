<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'notifications';
$teacher_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_role = 'teacher' ORDER BY created_at DESC");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Notifications</h2>
            </div>
            <div class="card">
                <div class="card-body">
                    <?php if ($notifications->num_rows > 0): ?>
                    <table class="table table-striped">
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
                                <td><?php echo htmlspecialchars($notif['type']); ?></td>
                                <td><?php echo htmlspecialchars($notif['message']); ?></td>
                                <td><?php echo htmlspecialchars($notif['status']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="text-muted">No notifications found.</p>
                    <?php endif; ?>
                </div>
            </div>
            <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 