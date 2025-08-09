<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = 'schools';

// Get nurse's email
$nurse_id = $_SESSION['user_id'];
$nurse_email_stmt = $conn->prepare('SELECT email FROM nurses WHERE id = ?');
$nurse_email_stmt->bind_param('i', $nurse_id);
$nurse_email_stmt->execute();
$nurse_email_stmt->bind_result($nurse_email);
$nurse_email_stmt->fetch();
$nurse_email_stmt->close();

// Get assigned schools for this nurse
$assigned_schools_stmt = $conn->prepare('
    SELECT DISTINCT s.id, s.school_name 
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
    $assigned_schools[] = $row;
}
$assigned_schools_stmt->close();

// Get all students grouped by assigned school
$students_by_school = [];
foreach ($assigned_schools as $school) {
    $stmt = $conn->prepare("SELECT s.*, gl.grade_name FROM students s JOIN grade_levels gl ON s.grade_level_id = gl.id WHERE gl.school_id = ? ORDER BY gl.grade_name, s.last_name, s.first_name");
    $stmt->bind_param("i", $school['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($student = $result->fetch_assoc()) {
        $students[] = $student;
    }
    $students_by_school[$school['id']] = $students;
}

// Grade level options
$grade_levels = ['All Students', 'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schools - Nurse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .school-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .school-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .school-header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .school-header:hover {
            background: linear-gradient(135deg, #0d7a6f 0%, #2dd66b 100%);
        }
        
        .school-header h5 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .school-header .toggle-icon {
            transition: transform 0.3s ease;
            font-size: 1.2rem;
        }
        
        .school-header.expanded .toggle-icon {
            transform: rotate(180deg);
        }
        
        .school-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .grade-filter {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 8px;
            padding: 0.5rem;
            font-size: 0.9rem;
            max-width: 200px;
        }
        
        .grade-filter option {
            background: white;
            color: #333;
        }
        
        .students-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease;
        }
        
        .students-content.expanded {
            max-height: 1000px;
        }
        
        .table-container {
            padding: 1.5rem;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }
        
        .no-students {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
            font-style: italic;
        }
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card h4 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-2"><i class="fas fa-school me-2"></i>My Assigned Schools</h2>
                        <p class="text-muted mb-0">Manage and view students from your assigned schools</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Nurse: <?php echo htmlspecialchars($_SESSION['full_name']); ?></small>
                        <small class="text-muted">Email: <?php echo htmlspecialchars($nurse_email); ?></small>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <h4><?php echo count($assigned_schools); ?></h4>
                        <p>Assigned Schools</p>
                    </div>
                    <div class="stat-card">
                        <h4><?php echo array_sum(array_map(function($students) { return count($students); }, $students_by_school)); ?></h4>
                        <p>Total Students</p>
                    </div>
                </div>
            </div>

            <!-- Schools List -->
            <?php if (empty($assigned_schools)): ?>
            <div class="text-center py-5">
                <i class="fas fa-school fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No Schools Assigned</h4>
                <p class="text-muted">You haven't been assigned to any schools yet. Wait for principal invitations.</p>
            </div>
            <?php else: ?>
                <?php foreach ($assigned_schools as $school): ?>
                <div class="school-card">
                    <div class="school-header" onclick="toggleSchool(<?php echo $school['id']; ?>)">
                        <h5>
                            <span><i class="fas fa-school me-2"></i><?php echo htmlspecialchars($school['school_name']); ?></span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h5>
                        <div class="school-stats">
                            <div class="stat-item">
                                <i class="fas fa-users"></i>
                                <span><?php echo count($students_by_school[$school['id']]); ?> Students</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-layer-group"></i>
                                <span><?php echo count(array_unique(array_column($students_by_school[$school['id']], 'grade_name'))); ?> Grade Levels</span>
                            </div>
                            <div class="stat-item">
                                <select class="grade-filter" data-school-id="<?php echo $school['id']; ?>" onclick="event.stopPropagation();">
                                    <?php foreach ($grade_levels as $grade): ?>
                                    <option value="<?php echo $grade; ?>"><?php echo $grade; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="students-content" id="students-content-<?php echo $school['id']; ?>">
                        <div class="table-container">
                            <?php if (empty($students_by_school[$school['id']])): ?>
                            <div class="no-students">
                                <i class="fas fa-user-graduate fa-2x mb-2"></i>
                                <p>No students found for this school.</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="students-table-<?php echo $school['id']; ?>">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-id-card me-1"></i>LRN</th>
                                            <th><i class="fas fa-user me-1"></i>Name</th>
                                            <th><i class="fas fa-layer-group me-1"></i>Grade</th>
                                            <th><i class="fas fa-venus-mars me-1"></i>Sex</th>
                                            <th><i class="fas fa-birthday-cake me-1"></i>Birthdate</th>
                                            <th><i class="fas fa-user-friends me-1"></i>Parent Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students_by_school[$school['id']] as $student): ?>
                                        <tr data-grade="<?php echo htmlspecialchars($student['grade_name']); ?>">
                                            <td><strong><?php echo htmlspecialchars($student['lrn']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ($student['middle_name'] ? ' ' . $student['middle_name'] : '')); ?></td>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($student['grade_name']); ?></span></td>
                                            <td><?php echo htmlspecialchars($student['sex']); ?></td>
                                            <td><?php echo htmlspecialchars($student['birthdate']); ?></td>
                                            <td><?php echo htmlspecialchars($student['parent_name']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle school expansion
function toggleSchool(schoolId) {
    const header = document.querySelector(`[onclick="toggleSchool(${schoolId})"]`);
    const content = document.getElementById(`students-content-${schoolId}`);
    
    // Close all other schools first
    document.querySelectorAll('.school-header').forEach(function(otherHeader) {
        if (otherHeader !== header) {
            otherHeader.classList.remove('expanded');
        }
    });
    
    document.querySelectorAll('.students-content').forEach(function(otherContent) {
        if (otherContent !== content) {
            otherContent.classList.remove('expanded');
        }
    });
    
    // Toggle current school
    if (content.classList.contains('expanded')) {
        content.classList.remove('expanded');
        header.classList.remove('expanded');
    } else {
        content.classList.add('expanded');
        header.classList.add('expanded');
    }
}

// Grade filter logic
const gradeLevels = <?php echo json_encode($grade_levels); ?>;
document.querySelectorAll('.grade-filter').forEach(function(select) {
    select.addEventListener('change', function() {
        const schoolId = this.getAttribute('data-school-id');
        const table = document.getElementById('students-table-' + schoolId);
        const selectedGrade = this.value;
        
        if (table) {
            Array.from(table.querySelectorAll('tbody tr')).forEach(function(row) {
                if (selectedGrade === 'All Students' || row.getAttribute('data-grade') === selectedGrade) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    });
});

// Initialize - all schools start collapsed
document.addEventListener('DOMContentLoaded', function() {
    // Ensure all schools start collapsed
    document.querySelectorAll('.school-header').forEach(function(header) {
        header.classList.remove('expanded');
    });
    
    document.querySelectorAll('.students-content').forEach(function(content) {
        content.classList.remove('expanded');
    });
});
</script>
</body>
</html> 