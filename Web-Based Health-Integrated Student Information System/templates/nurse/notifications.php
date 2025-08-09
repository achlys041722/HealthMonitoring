<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'notifications';
$nurse_id = $_SESSION['user_id'];

// Handle bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    if (isset($_POST['selected_notifications']) && is_array($_POST['selected_notifications'])) {
        $selected_ids = $_POST['selected_notifications'];
        $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
        $delete_stmt = $conn->prepare("DELETE FROM notifications WHERE id IN ($placeholders) AND user_id = ? AND user_role = 'nurse'");
        $params = array_merge($selected_ids, [$nurse_id]);
        $delete_stmt->bind_param(str_repeat('i', count($params)), ...$params);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/notifications.php?success=deleted');
        exit();
    }
}

// Handle single delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $delete_stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ? AND user_role = 'nurse'");
    $delete_stmt->bind_param('ii', $delete_id, $nurse_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/notifications.php?success=deleted');
    exit();
}

// Mark as read
if (isset($_GET['mark_read'])) {
    $read_id = (int)$_GET['mark_read'];
    $update_stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ? AND user_role = 'nurse'");
    $update_stmt->bind_param('ii', $read_id, $nurse_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/notifications.php?success=marked_read');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_role = 'nurse' ORDER BY created_at DESC");
$stmt->bind_param('i', $nurse_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Count notifications by status
$unread_count = 0;
$total_count = 0;
$notifications_array = [];
while ($notif = $notifications->fetch_assoc()) {
    $notifications_array[] = $notif;
    $total_count++;
    if ($notif['status'] === 'unread') {
        $unread_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - Nurse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card h4 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .notification-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 0.75rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .notification-card.unread {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #fff 0%, #fff8f8 100%);
        }
        
        .notification-card.read {
            border-left-color: #28a745;
            opacity: 0.8;
        }
        
        .notification-header {
            padding: 0.75rem;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .notification-body {
            padding: 0.75rem;
        }
        
        .notification-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .type-request {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .type-approval {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .type-rejection {
            background: #ffebee;
            color: #c62828;
        }
        
        .type-info {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .btn-action {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-mark-read {
            background: #28a745;
            color: white;
        }
        
        .btn-mark-read:hover {
            background: #218838;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
            color: white;
        }
        
        .bulk-actions {
            background: white;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            display: none;
        }
        
        .bulk-actions.show {
            display: block;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .custom-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #11998e;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <!-- Success Messages -->
            <?php if (isset($_GET['success'])): ?>
                <?php if ($_GET['success'] === 'deleted'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>Notification(s) deleted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php elseif ($_GET['success'] === 'marked_read'): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-eye me-2"></i>Notification marked as read!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-2"><i class="fas fa-bell me-2"></i>Notifications</h2>
                        <p class="text-muted mb-0">Stay updated with your latest notifications and requests</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Nurse: <?php echo htmlspecialchars($_SESSION['full_name']); ?></small>
                        <small class="text-muted">Manage your notifications</small>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <h4><?php echo $total_count; ?></h4>
                        <p>Total Notifications</p>
                    </div>
                    <div class="stat-card">
                        <h4><?php echo $unread_count; ?></h4>
                        <p>Unread Notifications</p>
                    </div>
                    <div class="stat-card">
                        <h4><?php echo $total_count - $unread_count; ?></h4>
                        <p>Read Notifications</p>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="bulk-actions" id="bulkActions">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span id="selectedCount">0</span> notification(s) selected
                    </div>
                    <div>
                        <button type="button" class="btn btn-danger" onclick="deleteSelected()">
                            <i class="fas fa-trash me-2"></i>Delete Selected
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <?php if (empty($notifications_array)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h4>No Notifications</h4>
                <p>You don't have any notifications yet. They will appear here when you receive requests or updates.</p>
            </div>
            <?php else: ?>
            <form id="notificationsForm" method="POST">
                <?php foreach ($notifications_array as $notif): ?>
                <div class="notification-card <?php echo $notif['status']; ?>">
                    <div class="notification-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" class="custom-checkbox notification-checkbox" 
                                           name="selected_notifications[]" value="<?php echo $notif['id']; ?>"
                                           onchange="updateBulkActions()">
                                    <span class="notification-type type-<?php echo strtolower($notif['type']); ?>">
                                        <?php echo htmlspecialchars($notif['type']); ?>
                                    </span>
                                    <span class="ms-2 text-muted">
                                        <?php echo date('M d, Y \a\t H:i', strtotime($notif['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="notification-actions">
                                <?php if ($notif['status'] === 'unread'): ?>
                                <a href="?mark_read=<?php echo $notif['id']; ?>" class="btn-action btn-mark-read" 
                                   title="Mark as read">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php endif; ?>
                                <a href="?delete_id=<?php echo $notif['id']; ?>" class="btn-action btn-delete" 
                                   onclick="return confirm('Are you sure you want to delete this notification?')"
                                   title="Delete notification">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="notification-body">
                        <p class="mb-0"><?php echo htmlspecialchars($notif['message']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </form>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.notification-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (checkboxes.length > 0) {
        bulkActions.classList.add('show');
        selectedCount.textContent = checkboxes.length;
    } else {
        bulkActions.classList.remove('show');
    }
}

function deleteSelected() {
    const checkboxes = document.querySelectorAll('.notification-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select notifications to delete.');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${checkboxes.length} notification(s)?`)) {
        document.getElementById('notificationsForm').submit();
    }
}

// Select all functionality
function selectAll() {
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActions();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateBulkActions();
    
    // Auto-hide success notifications after 3 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 3000);
    });
});
</script>
</body>
</html> 