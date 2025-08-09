<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'dashboard';
$principal_id = $_SESSION['user_id'];

// Fetch principal's school information
$stmt = $conn->prepare('SELECT elementary_school FROM principals WHERE id = ?');
$stmt->bind_param('i', $principal_id);
$stmt->execute();
$stmt->bind_result($elementary_school);
$stmt->fetch();
$stmt->close();

// Get school_id
$school_stmt = $conn->prepare('SELECT id FROM schools WHERE school_name = ?');
$school_stmt->bind_param('s', $elementary_school);
$school_stmt->execute();
$school_stmt->bind_result($school_id);
$school_stmt->fetch();
$school_stmt->close();

// Get statistics
$student_stmt = $conn->prepare('SELECT COUNT(*) as total_students FROM students s JOIN grade_levels gl ON s.grade_level_id = gl.id WHERE gl.school_id = ?');
$student_stmt->bind_param('i', $school_id);
$student_stmt->execute();
$student_stmt->bind_result($total_students);
$student_stmt->fetch();
$student_stmt->close();

$teacher_stmt = $conn->prepare('SELECT COUNT(*) as total_teachers FROM teachers WHERE principal_email = (SELECT email FROM principals WHERE id = ?) AND status = "approved"');
$teacher_stmt->bind_param('i', $principal_id);
$teacher_stmt->execute();
$teacher_stmt->bind_result($total_teachers);
$teacher_stmt->fetch();
$teacher_stmt->close();

$pending_stmt = $conn->prepare('SELECT COUNT(*) as pending_requests FROM teachers WHERE principal_email = (SELECT email FROM principals WHERE id = ?) AND status = "pending"');
$pending_stmt->bind_param('i', $principal_id);
$pending_stmt->execute();
$pending_stmt->bind_result($pending_requests);
$pending_stmt->fetch();
$pending_stmt->close();

// Get data for charts
$gender_stmt = $conn->prepare('
    SELECT s.sex, COUNT(*) as count 
    FROM students s 
    JOIN grade_levels gl ON s.grade_level_id = gl.id 
    WHERE gl.school_id = ? 
    GROUP BY s.sex
');
$gender_stmt->bind_param('i', $school_id);
$gender_stmt->execute();
$gender_result = $gender_stmt->get_result();
$gender_data = [];
while ($row = $gender_result->fetch_assoc()) {
    $gender_data[$row['sex']] = $row['count'];
}
$gender_stmt->close();

$status_stmt = $conn->prepare('
    SELECT sh.status, COUNT(*) as count 
    FROM students s 
    JOIN grade_levels gl ON s.grade_level_id = gl.id 
    LEFT JOIN student_health sh ON s.id = sh.student_id 
    WHERE gl.school_id = ? 
    GROUP BY sh.status
');
$status_stmt->bind_param('i', $school_id);
$status_stmt->execute();
$status_result = $status_stmt->get_result();
$status_data = [];
while ($row = $status_result->fetch_assoc()) {
    $status_data[$row['status']] = $row['count'];
}
$status_stmt->close();

// Get recent activities (only for this principal's school)
$recent_activities_stmt = $conn->prepare('
    SELECT n.* 
    FROM notifications n 
    WHERE n.user_id = ? AND n.user_role = "principal"
    ORDER BY n.created_at DESC 
    LIMIT 5
');
$recent_activities_stmt->bind_param('i', $principal_id);
$recent_activities_stmt->execute();
$recent_activities_result = $recent_activities_stmt->get_result();
$recent_activities = [];
while ($row = $recent_activities_result->fetch_assoc()) {
    $recent_activities[] = $row;
}
$recent_activities_stmt->close();

// Get upcoming tasks
$upcoming_tasks_stmt = $conn->prepare('
    SELECT COUNT(*) as pending_teachers 
    FROM teachers 
    WHERE principal_email = (SELECT email FROM principals WHERE id = ?) AND status = "pending"
');
$upcoming_tasks_stmt->bind_param('i', $principal_id);
$upcoming_tasks_stmt->execute();
$upcoming_tasks_stmt->bind_result($pending_teachers);
$upcoming_tasks_stmt->fetch();
$upcoming_tasks_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Principal Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container { 
            max-width: 500px; 
            margin: 0 auto 2rem auto;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 15px;
        }
        @media (min-width: 992px) {
            .charts-row { 
                display: flex; 
                gap: 2rem;
                align-items: flex-start;
            }
        }
        .custom-legend {
            font-size: 12px;
            margin-top: 10px;
        }
        .legend-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            display: inline-block;
        }
        .legend-text {
            font-size: 11px;
        }
        .chart-title {
            font-weight: bold;
            font-size: 14px;
            color: #333;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            white-space: nowrap;
        }
        .chart-container canvas {
            height: 250px !important;
        }
        .activity-item {
            padding: 0.75rem;
            border-left: 3px solid #007bff;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 0 0.25rem 0.25rem 0;
        }
        .task-item {
            padding: 0.75rem;
            border-left: 3px solid #28a745;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 0 0.25rem 0.25rem 0;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Principal)</h2>
                <div class="text-muted"><?php echo htmlspecialchars($elementary_school); ?></div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_students; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Teachers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_teachers; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Requests</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_requests; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Grade Levels</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">7</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row mb-4">
                <div class="chart-container">
                    <canvas id="genderPie"></canvas>
                    <div>
                        <div class="chart-title">Male vs. Female</div>
                        <div class="custom-legend">
                            <div class="legend-row">
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #36A2EB;"></span>
                                    <span class="legend-text">Male</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #FF6384;"></span>
                                    <span class="legend-text">Female</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="chart-container" style="margin-left: -50px;">
                    <canvas id="statusPie"></canvas>
                    <div>
                        <div class="chart-title">Student Status</div>
                        <div class="custom-legend">
                            <div class="legend-row">
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #4CAF50;"></span>
                                    <span class="legend-text">Good</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #FFC107;"></span>
                                    <span class="legend-text">Fair</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #FF5722;"></span>
                                    <span class="legend-text">Needs Attention</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #9C27B0;"></span>
                                    <span class="legend-text">Requires Follow-up</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/notifications.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-bell me-2"></i>View Notifications
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php" class="btn btn-outline-success w-100">
                                        <i class="fas fa-user-plus me-2"></i>Add Student
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/health_records.php" class="btn btn-outline-info w-100">
                                        <i class="fas fa-heartbeat me-2"></i>Health Records
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/teachers.php" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-users-cog me-2"></i>Manage Teachers
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Dashboard Content -->
            <div class="row">
                <!-- Recent Activities -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_activities)): ?>
                                <p class="text-muted">No recent activities</p>
                            <?php else: ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo htmlspecialchars($activity['message']); ?></strong>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($activity['created_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Tasks -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Upcoming Tasks</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($pending_teachers > 0): ?>
                                <div class="task-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Teacher Approval Required</strong>
                                            <br><small class="text-muted"><?php echo $pending_teachers; ?> teacher(s) waiting for approval</small>
                                        </div>
                                        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/notifications.php" class="btn btn-sm btn-warning">
                                            Review
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="task-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Health Records Review</strong>
                                        <br><small class="text-muted">Review student health assessments</small>
                                    </div>
                                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/health_records.php" class="btn btn-sm btn-info">
                                        View
                                    </a>
                                </div>
                            </div>

                            <div class="task-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Student Management</strong>
                                        <br><small class="text-muted">Add or update student information</small>
                                    </div>
                                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php" class="btn btn-sm btn-success">
                                        Manage
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Chart.js setup
const genderPie = new Chart(document.getElementById('genderPie'), {
    type: 'pie',
    data: {
        labels: ['Male', 'Female'],
        datasets: [{
            data: [<?php echo $gender_data['Male'] ?? 0; ?>, <?php echo $gender_data['Female'] ?? 0; ?>],
            backgroundColor: ['#36A2EB', '#FF6384']
        }]
    },
    options: {
        responsive: true, 
        plugins: {
            legend: {
                display: false
            }
        },
        rotation: 0,
        cutout: '0%',
        radius: '90%'
    }
});

const statusPie = new Chart(document.getElementById('statusPie'), {
    type: 'pie',
    data: {
        labels: ['Good', 'Fair', 'Needs Attention', 'Requires Follow-up'],
        datasets: [{
            data: [
                <?php echo $status_data['Good'] ?? 0; ?>, 
                <?php echo $status_data['Fair'] ?? 0; ?>, 
                <?php echo $status_data['Needs Attention'] ?? 0; ?>, 
                <?php echo $status_data['Requires Follow-up'] ?? 0; ?>
            ],
            backgroundColor: ['#4CAF50', '#FFC107', '#FF5722', '#9C27B0']
        }]
    },
    options: {
        responsive: true, 
        plugins: {
            legend: {
                display: false
            }
        },
        rotation: 0,
        cutout: '0%',
        radius: '90%'
    }
});
</script>
</body>
</html> 