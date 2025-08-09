<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'health_records';

$nurse_id = $_SESSION['user_id'];

// Get nurse's email
$nurse_email_stmt = $conn->prepare('SELECT email FROM nurses WHERE id = ?');
$nurse_email_stmt->bind_param('i', $nurse_id);
$nurse_email_stmt->execute();
$nurse_email_stmt->bind_result($nurse_email);
$nurse_email_stmt->fetch();
$nurse_email_stmt->close();

// Get schools assigned to this nurse
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

// Get assigned schools for dropdown (only schools nurse is assigned to)
$schools = $assigned_schools;

// Get all students data for client-side filtering
$all_students_query = "
    SELECT s.id as student_id, s.lrn, s.first_name, s.middle_name, s.last_name, s.sex, gl.grade_name, sch.school_name, sh.date_of_exam, sh.status
    FROM students s
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN schools sch ON gl.school_id = sch.id
    LEFT JOIN student_health sh ON s.id = sh.student_id
    WHERE sch.school_name IN (" . str_repeat('?,', count($assigned_schools) - 1) . "?)
    ORDER BY sch.school_name, gl.grade_name, s.last_name, s.first_name
";

$all_students_stmt = $conn->prepare($all_students_query);
if (!empty($assigned_schools)) {
    $all_students_stmt->bind_param(str_repeat('s', count($assigned_schools)), ...$assigned_schools);
}
$all_students_stmt->execute();
$all_students_result = $all_students_stmt->get_result();
$all_students = [];
while ($row = $all_students_result->fetch_assoc()) {
    $all_students[] = $row;
}
$all_students_stmt->close();

// Get grades for assigned schools only
if (empty($assigned_schools)) {
    $grades = [];
} else {
    $grades_placeholders = str_repeat('?,', count($assigned_schools) - 1) . '?';
    $grades_stmt = $conn->prepare("
        SELECT DISTINCT gl.grade_name 
        FROM grade_levels gl 
        JOIN schools s ON gl.school_id = s.id 
        WHERE s.school_name IN ($grades_placeholders) 
        ORDER BY gl.grade_name
    ");
    $grades_stmt->bind_param(str_repeat('s', count($assigned_schools)), ...$assigned_schools);
    $grades_stmt->execute();
    $grades_result = $grades_stmt->get_result();
    $grades = [];
    while ($row = $grades_result->fetch_assoc()) {
        $grades[] = $row['grade_name'];
    }
    $grades_stmt->close();
}

// Get all statuses for filter dropdown
$statuses = ['Good', 'Fair', 'Needs Attention', 'Requires Follow-up'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Health Records - Nurse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card { margin-bottom: 1.5rem; }
        .form-label { font-weight: 600; }
        .health-status { font-size: 0.9rem; padding: 0.3rem 0.6rem; border-radius: 0.25rem; font-weight: 500; }
        .status-good { background-color: #d4edda; color: #155724; }
        .status-fair { background-color: #fff3cd; color: #856404; }
        .status-needs-attention { background-color: #f8d7da; color: #721c24; }
        .status-follow-up { background-color: #cce5ff; color: #004085; }
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
        .student-lrn, .student-name, .school-badge, .grade-badge, .gender-badge {
            font-size: 0.9rem;
            font-weight: 500;
        }
        .school-badge, .grade-badge, .gender-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .gender-male {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        .gender-female {
            background: linear-gradient(135deg, #e83e8c 0%, #c73e6b 100%);
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
                <div class="text-end">
                    <small class="text-muted">
                        Assigned Schools: <?php echo count($assigned_schools); ?><br>
                        <span id="activeFiltersDisplay"></span>
                    </small>
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
            
            <form class="row g-2 mb-3" id="filterForm" onsubmit="return false;">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name or LRN">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="schoolFilter">
                        <option value="">All Schools</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?php echo htmlspecialchars($school); ?>"><?php echo htmlspecialchars($school); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="gradeFilter">
                        <option value="">All Grades</option>
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?php echo htmlspecialchars($grade); ?>"><?php echo htmlspecialchars($grade); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-primary w-100" onclick="clearFilters()">Clear</button>
                </div>
            </form>
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
                                    <th><i class="fas fa-school me-2"></i>School</th>
                                    <th><i class="fas fa-graduation-cap me-2"></i>Grade</th>
                                    <th><i class="fas fa-venus-mars me-2"></i>Sex</th>
                                    <th><i class="fas fa-calendar me-2"></i>Date of Exam</th>
                                    <th><i class="fas fa-heartbeat me-2"></i>Status</th>
                                    <th><i class="fas fa-eye me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_students as $student): ?>
                                <tr data-school="<?php echo htmlspecialchars($student['school_name']); ?>" data-grade="<?php echo htmlspecialchars($student['grade_name']); ?>" data-status="<?php echo htmlspecialchars($student['status']); ?>" data-sex="<?php echo htmlspecialchars($student['sex']); ?>">
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
                                        <span class="school-badge">
                                            <i class="fas fa-school me-1"></i>
                                            <?php echo htmlspecialchars($student['school_name']); ?>
                                        </span>
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
                                                $statusClass = 'status-needs-attention';
                                                $statusIcon = 'fas fa-exclamation-triangle';
                                                break;
                                            case 'requires follow-up':
                                                $statusClass = 'status-follow-up';
                                                $statusIcon = 'fas fa-arrow-right';
                                                break;
                                            default:
                                                $statusClass = 'status-na';
                                                $statusIcon = 'fas fa-minus-circle';
                                        }
                                        ?>
                                        <span class="health-status <?php echo $statusClass; ?>">
                                            <i class="<?php echo $statusIcon; ?> me-1"></i>
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/view_student.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
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
<script>
// Student data for client-side filtering
const allStudents = <?php echo json_encode($all_students); ?>;

function getFilteredStudents() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const schoolFilter = document.getElementById('schoolFilter').value;
    const gradeFilter = document.getElementById('gradeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    return allStudents.filter(student => {
        const matchesSearch = !searchTerm || 
            student.first_name.toLowerCase().includes(searchTerm) ||
            student.last_name.toLowerCase().includes(searchTerm) ||
            student.lrn.toLowerCase().includes(searchTerm);
        
        const matchesSchool = !schoolFilter || student.school_name === schoolFilter;
        const matchesGrade = !gradeFilter || student.grade_name === gradeFilter;
        const matchesStatus = !statusFilter || student.status === statusFilter;

        return matchesSearch && matchesSchool && matchesGrade && matchesStatus;
    });
}

function updateTableAndCharts() {
    const filtered = getFilteredStudents();
    
    // Update active filters display
    updateActiveFiltersDisplay();
    
    // Update table
    const tbody = document.querySelector('#studentsTable tbody');
    tbody.innerHTML = '';
    
    filtered.forEach(student => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-school', student.school_name);
        tr.setAttribute('data-grade', student.grade_name);
        tr.setAttribute('data-status', student.status);
        tr.setAttribute('data-sex', student.sex);
        
        // Status styling
        let statusClass = 'status-na';
        let statusIcon = 'fas fa-minus-circle';
        
        switch(student.status?.toLowerCase()) {
            case 'good':
                statusClass = 'status-good';
                statusIcon = 'fas fa-check-circle';
                break;
            case 'fair':
                statusClass = 'status-fair';
                statusIcon = 'fas fa-exclamation-circle';
                break;
            case 'needs attention':
                statusClass = 'status-needs-attention';
                statusIcon = 'fas fa-exclamation-triangle';
                break;
            case 'requires follow-up':
                statusClass = 'status-follow-up';
                statusIcon = 'fas fa-arrow-right';
                break;
        }
        
        const genderClass = student.sex.toLowerCase() === 'male' ? 'gender-male' : 'gender-female';
        const genderIcon = student.sex.toLowerCase() === 'male' ? 'mars' : 'venus';
        const dateDisplay = student.date_of_exam ? 
            `<i class="fas fa-calendar-check me-2 text-success"></i>${new Date(student.date_of_exam).toLocaleDateString()}` : 
            `<i class="fas fa-calendar-times me-2 text-muted"></i><span class="text-muted">N/A</span>`;
        
        tr.innerHTML = `
            <td>
                <span class="student-lrn">
                    <i class="fas fa-id-card me-1"></i>
                    ${student.lrn}
                </span>
            </td>
            <td>
                <div class="student-name">
                    <i class="fas fa-user-circle me-2 text-primary"></i>
                    ${student.last_name}, ${student.first_name}${student.middle_name ? ' ' + student.middle_name : ''}
                </div>
            </td>
            <td>
                <span class="school-badge">
                    <i class="fas fa-school me-1"></i>
                    ${student.school_name}
                </span>
            </td>
            <td>
                <span class="grade-badge">
                    <i class="fas fa-graduation-cap me-1"></i>
                    ${student.grade_name}
                </span>
            </td>
            <td>
                <span class="gender-badge ${genderClass}">
                    <i class="fas fa-${genderIcon} me-1"></i>
                    ${student.sex}
                </span>
            </td>
            <td>${dateDisplay}</td>
            <td>
                <span class="health-status ${statusClass}">
                    <i class="${statusIcon} me-1"></i>
                    ${student.status || 'N/A'}
                </span>
            </td>
            <td>
                <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/view_student.php?id=${student.student_id}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye me-1"></i>View
                </a>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    // Update charts
    updateGenderPie(filtered);
    updateStatusPie(filtered);
}

function updateActiveFiltersDisplay() {
    const searchTerm = document.getElementById('searchInput').value;
    const schoolFilter = document.getElementById('schoolFilter').value;
    const gradeFilter = document.getElementById('gradeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    const activeFilters = [];
    if (searchTerm) activeFilters.push(`Search: '${searchTerm}'`);
    if (schoolFilter) activeFilters.push(`School: '${schoolFilter}'`);
    if (gradeFilter) activeFilters.push(`Grade: '${gradeFilter}'`);
    if (statusFilter) activeFilters.push(`Status: '${statusFilter}'`);
    
    const displayElement = document.getElementById('activeFiltersDisplay');
    if (activeFilters.length > 0) {
        displayElement.textContent = `Active Filters: ${activeFilters.join(', ')}`;
    } else {
        displayElement.textContent = '';
    }
}

function updateGenderPie(data) {
    const male = data.filter(s => s.sex === 'Male').length;
    const female = data.filter(s => s.sex === 'Female').length;
    
    // If no data, show empty chart
    if (male === 0 && female === 0) {
        genderPie.data.datasets[0].data = [0, 0];
    } else {
        genderPie.data.datasets[0].data = [male, female];
    }
    genderPie.update();
}

function updateStatusPie(data) {
    const statusLabels = ['Good', 'Fair', 'Needs Attention', 'Requires Follow-up'];
    const counts = statusLabels.map(label => data.filter(s => s.status === label).length);
    
    // If all counts are 0, show empty chart
    if (counts.every(count => count === 0)) {
        statusPie.data.datasets[0].data = [0, 0, 0, 0];
    } else {
        statusPie.data.datasets[0].data = counts;
    }
    
    statusPie.update();
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('schoolFilter').value = '';
    document.getElementById('gradeFilter').value = '';
    document.getElementById('statusFilter').value = '';
    updateTableAndCharts();
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
            data: [0, 0, 0, 0],
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

// Event listeners for real-time filtering
['searchInput', 'schoolFilter', 'gradeFilter', 'statusFilter'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateTableAndCharts);
    document.getElementById(id).addEventListener('change', updateTableAndCharts);
});

// Initial render
updateTableAndCharts();
</script>