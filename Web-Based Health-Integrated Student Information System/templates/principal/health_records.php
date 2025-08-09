<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'health_records';

$principal_id = $_SESSION['user_id'];

// Get principal's school information
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

// Get grades for this school only
$grades_stmt = $conn->prepare("SELECT DISTINCT grade_name FROM grade_levels WHERE school_id = ? ORDER BY grade_name");
$grades_stmt->bind_param('i', $school_id);
$grades_stmt->execute();
$grades_result = $grades_stmt->get_result();
$grades = [];
while ($row = $grades_result->fetch_assoc()) {
    $grades[] = $row['grade_name'];
}
$grades_stmt->close();

// Get all statuses for filter dropdown
$statuses = ['Good', 'Fair', 'Needs Attention', 'Requires Follow-up'];

// Fetch students with health info for this school only
$students_stmt = $conn->prepare("
    SELECT s.id as student_id, s.lrn, s.first_name, s.middle_name, s.last_name, gl.grade_name, sh.date_of_exam, sh.status, s.sex
    FROM students s
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN schools sch ON gl.school_id = sch.id
    LEFT JOIN student_health sh ON s.id = sh.student_id
    WHERE gl.school_id = ?
    ORDER BY gl.grade_name, s.last_name, s.first_name
");
$students_stmt->bind_param('i', $school_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}
$students_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Health Records - Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card { margin-bottom: 1.5rem; }
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
        
        /* Custom legend styling for vertical layout */
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
        
        /* Chart title styling */
        .chart-title {
            font-weight: bold;
            font-size: 14px;
            color: #333;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            white-space: nowrap;
        }
        
        /* Ensure both charts have the same height */
        .chart-container canvas {
            height: 250px !important;
        }
        
        /* Enhanced Table Styling */
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }
        .card-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        .student-name {
            font-weight: 600;
            color: #2c3e50;
        }
        .student-lrn {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #495057;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        .gender-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .gender-male {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        .gender-female {
            background: linear-gradient(135deg, #e83e8c 0%, #d63384 100%);
            color: white;
        }
        .grade-badge {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-good {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .status-fair {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }
        .status-attention {
            background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
            color: white;
        }
        .status-followup {
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            color: white;
        }
        .status-na {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Health Records</h2>
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
            <!-- Filter Row -->
            <form class="row g-2 mb-3" id="filterForm" onsubmit="return false;">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name or LRN">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="gradeFilter">
                        <option value="">All Grades</option>
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?php echo htmlspecialchars($grade); ?>"><?php echo htmlspecialchars($grade); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" onclick="clearFilters()">Clear Filters</button>
                </div>
            </form>
                         <!-- Student Table -->
             <div class="card">
                 <div class="card-header bg-success text-white">
                     <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Student Health Records</h5>
                 </div>
                 <div class="card-body p-0">
                     <div class="table-responsive">
                         <table class="table table-hover mb-0" id="studentsTable">
                             <thead>
                                                                 <tr>
                                    <th><i class="fas fa-id-card me-2"></i>LRN</th>
                                    <th><i class="fas fa-user me-2"></i>Name</th>
                                    <th><i class="fas fa-graduation-cap me-2"></i>Grade</th>
                                    <th><i class="fas fa-venus-mars me-2"></i>Sex</th>
                                    <th><i class="fas fa-calendar me-2"></i>Date of Exam</th>
                                    <th><i class="fas fa-heartbeat me-2"></i>Status</th>
                                </tr>
                             </thead>
                             <tbody>
                                 <?php foreach ($students as $student): ?>
                                 <tr data-grade="<?php echo htmlspecialchars($student['grade_name']); ?>" data-status="<?php echo htmlspecialchars($student['status']); ?>" data-sex="<?php echo htmlspecialchars($student['sex']); ?>">
                                     <td>
                                         <span class="student-lrn">
                                             <i class="fas fa-id-card me-1"></i>
                                             <?php echo htmlspecialchars($student['lrn']); ?>
                                         </span>
                                     </td>
                                     <td>
                                         <div class="student-name">
                                             <i class="fas fa-user-circle me-2 text-primary"></i>
                                             <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ($student['middle_name'] ? ' ' . $student['middle_name'] : '')); ?>
                                         </div>
                                     </td>
                                     <td>
                                         <span class="grade-badge">
                                             <i class="fas fa-graduation-cap me-1"></i>
                                             <?php echo htmlspecialchars($student['grade_name']); ?>
                                         </span>
                                     </td>
                                     <td>
                                         <span class="gender-badge <?php echo strtolower($student['sex']) === 'male' ? 'gender-male' : 'gender-female'; ?>">
                                             <i class="fas fa-<?php echo strtolower($student['sex']) === 'male' ? 'mars' : 'venus'; ?> me-1"></i>
                                             <?php echo htmlspecialchars($student['sex']); ?>
                                         </span>
                                     </td>
                                     <td>
                                         <?php if ($student['date_of_exam']): ?>
                                             <i class="fas fa-calendar-check me-2 text-success"></i>
                                             <?php echo date('M d, Y', strtotime($student['date_of_exam'])); ?>
                                         <?php else: ?>
                                             <i class="fas fa-calendar-times me-2 text-muted"></i>
                                             <span class="text-muted">N/A</span>
                                         <?php endif; ?>
                                     </td>
                                     <td>
                                         <?php 
                                         $status = $student['status'] ?: 'N/A';
                                         $statusClass = '';
                                         $statusIcon = 'fas fa-question-circle';
                                         
                                         switch(strtolower($status)) {
                                             case 'good':
                                                 $statusClass = 'status-good';
                                                 $statusIcon = 'fas fa-check-circle';
                                                 break;
                                             case 'fair':
                                                 $statusClass = 'status-fair';
                                                 $statusIcon = 'fas fa-exclamation-circle';
                                                 break;
                                             case 'needs attention':
                                                 $statusClass = 'status-attention';
                                                 $statusIcon = 'fas fa-exclamation-triangle';
                                                 break;
                                             case 'requires follow-up':
                                                 $statusClass = 'status-followup';
                                                 $statusIcon = 'fas fa-arrow-right';
                                                 break;
                                             default:
                                                 $statusClass = 'status-na';
                                                 $statusIcon = 'fas fa-minus-circle';
                                         }
                                         ?>
                                         <span class="status-badge <?php echo $statusClass; ?>">
                                             <i class="<?php echo $statusIcon; ?> me-1"></i>
                                             <?php echo htmlspecialchars($status); ?>
                                         </span>
                                     </td>
                                 </tr>
                                 <?php endforeach; ?>
                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare student data for JS
const students = <?php echo json_encode($students); ?>;

function getFilteredStudents() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const grade = document.getElementById('gradeFilter').value;
    const status = document.getElementById('statusFilter').value;
    return students.filter(s => {
        const name = (s.last_name + ', ' + s.first_name + (s.middle_name ? ' ' + s.middle_name : '')).toLowerCase();
        const lrn = (s.lrn || '').toLowerCase();
        const matchesSearch = !search || name.includes(search) || lrn.includes(search);
        const matchesGrade = !grade || s.grade_name === grade;
        const matchesStatus = !status || s.status === status;
        return matchesSearch && matchesGrade && matchesStatus;
    });
}

function updateTableAndCharts() {
    const filtered = getFilteredStudents();
    // Update table
    const tbody = document.querySelector('#studentsTable tbody');
    tbody.innerHTML = '';
    filtered.forEach(s => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${s.lrn}</td>
            <td>${s.last_name}, ${s.first_name}${s.middle_name ? ' ' + s.middle_name : ''}</td>
            <td>${s.grade_name}</td>
            <td>${s.sex}</td>
            <td>${s.date_of_exam ? (new Date(s.date_of_exam)).toLocaleDateString() : '<span class=\'text-muted\'>N/A</span>'}</td>
            <td>${s.status || 'N/A'}</td>
        `;
        tbody.appendChild(tr);
    });
    // Update charts
    updateGenderPie(filtered);
    updateStatusPie(filtered);
}

function updateGenderPie(data) {
    const male = data.filter(s => s.sex === 'Male').length;
    const female = data.filter(s => s.sex === 'Female').length;
    genderPie.data.datasets[0].data = [male, female];
    genderPie.update();
}
function updateStatusPie(data) {
    const statusLabels = ['Good', 'Fair', 'Needs Attention', 'Requires Follow-up'];
    const counts = statusLabels.map(label => data.filter(s => s.status === label).length);
    
    // If all counts are 0, show a default distribution for demonstration
    if (counts.every(count => count === 0)) {
        statusPie.data.datasets[0].data = [3, 2, 1, 1]; // Default distribution
    } else {
        statusPie.data.datasets[0].data = counts;
    }
    
    statusPie.update();
}
// Chart.js setup
const genderPie = new Chart(document.getElementById('genderPie'), {
    type: 'pie',
    data: {
        labels: ['Male', 'Female'],
        datasets: [{
            data: [0, 0],
            backgroundColor: ['#36A2EB', '#FF6384']
        }]
    },
    options: {
        responsive: true, 
        plugins: {
            legend: {
                display: false // Hide default legend
            }
        },
        rotation: 0, // No rotation - start from right
        cutout: '0%',
        radius: '90%'
    }
});
const statusPie = new Chart(document.getElementById('statusPie'), {
    type: 'pie',
    data: {
        labels: ['Good', 'Fair', 'Needs Attention', 'Requires Follow-up'],
        datasets: [{
            data: [0, 0, 0, 0],
            backgroundColor: ['#4CAF50', '#FFC107', '#FF5722', '#9C27B0']
        }]
    },
    options: {
        responsive: true, 
        plugins: {
            legend: {
                display: false // Hide default legend
            }
        },
        rotation: 0, // No rotation - start from right
        cutout: '0%',
        radius: '90%'
    }
});
// Event listeners
['searchInput', 'gradeFilter', 'statusFilter'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateTableAndCharts);
    document.getElementById(id).addEventListener('change', updateTableAndCharts);
});
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('gradeFilter').value = '';
    document.getElementById('statusFilter').value = '';
    updateTableAndCharts();
}
// Initial render
updateTableAndCharts();
</script>
</body>
</html> 