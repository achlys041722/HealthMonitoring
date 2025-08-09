<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'dashboard';
$nurse_id = $_SESSION['user_id'];

// Get nurse's email
$nurse_email_stmt = $conn->prepare('SELECT email FROM nurses WHERE id = ?');
$nurse_email_stmt->bind_param('i', $nurse_id);
$nurse_email_stmt->execute();
$nurse_email_stmt->bind_result($nurse_email);
$nurse_email_stmt->fetch();
$nurse_email_stmt->close();

// Get all schools assigned to this nurse
$assigned_schools_stmt = $conn->prepare('
    SELECT DISTINCT s.school_name 
    FROM schools s 
    JOIN nurse_requests nr ON s.id = nr.school_id 
    WHERE nr.nurse_email = ? AND nr.status = "accepted"
    ORDER BY s.school_name
');
$assigned_schools_stmt->bind_param('s', $nurse_email);
$assigned_schools_stmt->execute();
$assigned_schools_result = $assigned_schools_stmt->get_result();
$assigned_schools = [];
while ($row = $assigned_schools_result->fetch_assoc()) {
    $assigned_schools[] = $row['school_name'];
}
$assigned_schools_stmt->close();

// Get school IDs for assigned schools
$school_ids_stmt = $conn->prepare('
    SELECT id 
    FROM schools 
    WHERE school_name IN (' . str_repeat('?,', count($assigned_schools) - 1) . '?)
');
$school_ids_stmt->bind_param(str_repeat('s', count($assigned_schools)), ...$assigned_schools);
$school_ids_stmt->execute();
$school_ids_result = $school_ids_stmt->get_result();
$school_ids = [];
while ($row = $school_ids_result->fetch_assoc()) {
    $school_ids[] = $row['id'];
}
$school_ids_stmt->close();

// Get statistics for all assigned schools
$school_ids_placeholders = str_repeat('?,', count($school_ids) - 1) . '?';
$student_stmt = $conn->prepare("SELECT COUNT(*) as total_students FROM students s JOIN grade_levels gl ON s.grade_level_id = gl.id WHERE gl.school_id IN ($school_ids_placeholders)");
$student_stmt->bind_param(str_repeat('i', count($school_ids)), ...$school_ids);
$student_stmt->execute();
$student_stmt->bind_result($total_students);
$student_stmt->fetch();
$student_stmt->close();

$health_records_stmt = $conn->prepare("SELECT COUNT(*) as total_records FROM student_health sh JOIN students s ON sh.student_id = s.id JOIN grade_levels gl ON s.grade_level_id = gl.id WHERE gl.school_id IN ($school_ids_placeholders)");
$health_records_stmt->bind_param(str_repeat('i', count($school_ids)), ...$school_ids);
$health_records_stmt->execute();
$health_records_stmt->bind_result($total_records);
$health_records_stmt->fetch();
$health_records_stmt->close();

$needs_attention_stmt = $conn->prepare("SELECT COUNT(*) as needs_attention FROM student_health sh JOIN students s ON sh.student_id = s.id JOIN grade_levels gl ON s.grade_level_id = gl.id WHERE gl.school_id IN ($school_ids_placeholders) AND sh.status IN ('Needs Attention', 'Requires Follow-up')");
$needs_attention_stmt->bind_param(str_repeat('i', count($school_ids)), ...$school_ids);
$needs_attention_stmt->execute();
$needs_attention_stmt->bind_result($needs_attention);
$needs_attention_stmt->fetch();
$needs_attention_stmt->close();

// Get data for charts (all assigned schools)
$gender_stmt = $conn->prepare("
    SELECT s.sex, COUNT(*) as count 
    FROM students s 
    JOIN grade_levels gl ON s.grade_level_id = gl.id 
    WHERE gl.school_id IN ($school_ids_placeholders)
    GROUP BY s.sex
");
$gender_stmt->bind_param(str_repeat('i', count($school_ids)), ...$school_ids);
$gender_stmt->execute();
$gender_result = $gender_stmt->get_result();
$gender_data = [];
while ($row = $gender_result->fetch_assoc()) {
    $gender_data[$row['sex']] = $row['count'];
}
$gender_stmt->close();

$status_stmt = $conn->prepare("
    SELECT sh.status, COUNT(*) as count 
    FROM students s 
    JOIN grade_levels gl ON s.grade_level_id = gl.id 
    LEFT JOIN student_health sh ON s.id = sh.student_id 
    WHERE gl.school_id IN ($school_ids_placeholders)
    GROUP BY sh.status
");
$status_stmt->bind_param(str_repeat('i', count($school_ids)), ...$school_ids);
$status_stmt->execute();
$status_result = $status_stmt->get_result();
$status_data = [];
while ($row = $status_result->fetch_assoc()) {
    $status_data[$row['status']] = $row['count'];
}
$status_stmt->close();

// Get recent health assessments (all assigned schools)
$recent_assessments_stmt = $conn->prepare("
    SELECT sh.*, s.first_name, s.last_name, gl.grade_name, sch.school_name
    FROM student_health sh 
    JOIN students s ON sh.student_id = s.id 
    JOIN grade_levels gl ON s.grade_level_id = gl.id 
    JOIN schools sch ON gl.school_id = sch.id
    WHERE gl.school_id IN ($school_ids_placeholders)
    ORDER BY sh.date_of_exam DESC 
    LIMIT 5
");
$recent_assessments_stmt->bind_param(str_repeat('i', count($school_ids)), ...$school_ids);
$recent_assessments_stmt->execute();
$recent_assessments = $recent_assessments_stmt->get_result();
$recent_assessments_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nurse Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        .assessment-item {
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
                 <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Nurse)</h2>
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
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Health Records</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_records; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-heartbeat fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Needs Attention</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $needs_attention; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                        <div class="chart-title">Health Status</div>
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
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_records.php" class="btn btn-outline-success w-100">
                                        <i class="fas fa-heartbeat me-2"></i>Health Records
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/schools.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-school me-2"></i>View Schools
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/notifications.php" class="btn btn-outline-info w-100">
                                        <i class="fas fa-bell me-2"></i>Notifications
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/requests.php" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-clipboard-list me-2"></i>Principal Requests
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Dashboard Content -->
            <div class="row">
                <!-- Recent Health Assessments -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Recent Health Assessments</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_assessments->num_rows > 0): ?>
                                <?php while ($assessment = $recent_assessments->fetch_assoc()): ?>
                                    <div class="assessment-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars($assessment['last_name'] . ', ' . $assessment['first_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($assessment['grade_name']); ?> • <?php echo htmlspecialchars($assessment['school_name']); ?> • <?php echo htmlspecialchars($assessment['status']); ?></small>
                                            </div>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($assessment['date_of_exam'])); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted">No recent health assessments</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Health Tips -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Health Tips</h5>
                        </div>
                        <div class="card-body">
                            <div class="assessment-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Regular Health Check-ups</strong>
                                        <br><small class="text-muted">Schedule routine health assessments for all students</small>
                                    </div>
                                    <i class="fas fa-calendar-check text-success"></i>
                                </div>
                            </div>
                            
                            <div class="assessment-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Monitor Growth</strong>
                                        <br><small class="text-muted">Track height, weight, and BMI regularly</small>
                                    </div>
                                    <i class="fas fa-chart-line text-info"></i>
                                </div>
                            </div>

                            <div class="assessment-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Immunization Records</strong>
                                        <br><small class="text-muted">Keep vaccination records up to date</small>
                                    </div>
                                    <i class="fas fa-shield-alt text-warning"></i>
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