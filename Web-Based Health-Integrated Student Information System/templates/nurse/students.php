<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

$activePage = 'students';

// Get nurse's assigned school
$nurse_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT assigned_school FROM nurses WHERE id = ?");
$stmt->bind_param("i", $nurse_id);
$stmt->execute();
$result = $stmt->get_result();
$nurse = $result->fetch_assoc();

if (!$nurse || !$nurse['assigned_school']) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/nurse_dashboard.php?error=no_school_assigned');
    exit();
}

$school_name = $nurse['assigned_school'];

// Get the school ID
$stmt = $conn->prepare("SELECT id FROM schools WHERE school_name = ?");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$school_result = $stmt->get_result();
$school = $school_result->fetch_assoc();

if (!$school) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/nurse_dashboard.php?error=school_not_found');
    exit();
}

$school_id = $school['id'];

// Fetch all students from the nurse's assigned school
$stmt = $conn->prepare("
    SELECT s.*, gl.grade_name, sch.school_name,
           sh.date_of_exam as last_health_exam,
           sh.status as health_status
    FROM students s 
    JOIN grade_levels gl ON s.grade_level_id = gl.id 
    JOIN schools sch ON gl.school_id = sch.id 
    LEFT JOIN student_health sh ON s.id = sh.student_id
    WHERE sch.id = ?
    ORDER BY gl.grade_name, s.last_name, s.first_name
");
$stmt->bind_param("i", $school_id);
$stmt->execute();
$students_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students - Nurse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            margin-bottom: 1.5rem;
        }
        .form-label, .card-header {
            font-size: 0.9rem;
        }
        .card-header {
            font-size: 1.1rem;
        }
        .health-status {
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
        }
        .status-good { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-needs-attention { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="mb-0">Students - Health Assessment</h2>
                <div>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($school_name); ?></span>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> 
                    <?php 
                    switch($_GET['success']) {
                        case 'assessment_added':
                            echo 'Health assessment has been recorded successfully.';
                            break;
                        case 'assessment_updated':
                            echo 'Health assessment has been updated successfully.';
                            break;
                        default:
                            echo 'Operation completed successfully.';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> 
                    <?php 
                    switch($_GET['error']) {
                        case 'assessment_failed':
                            echo 'Failed to record health assessment. Please try again.';
                            break;
                        case 'student_not_found':
                            echo 'Student not found.';
                            break;
                        default:
                            echo 'An error occurred. Please try again.';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Students List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Students - <?php echo htmlspecialchars($school_name); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($students_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>Name</th>
                                    <th>Grade</th>
                                    <th>Sex</th>
                                    <th>Last Health Exam</th>
                                    <th>Health Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = $students_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                    <td>
                                        <?php 
                                        echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']);
                                        if (!empty($student['middle_name'])) {
                                            echo ' ' . htmlspecialchars($student['middle_name']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['grade_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['sex']); ?></td>
                                    <td>
                                        <?php 
                                        if ($student['last_health_exam']) {
                                            echo date('M d, Y', strtotime($student['last_health_exam']));
                                        } else {
                                            echo '<span class="text-muted">No exam recorded</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $student['health_status'] ?? 'No Assessment';
                                        $statusClass = 'status-pending';
                                        if ($status === 'Good' || $status === 'Normal') {
                                            $statusClass = 'status-good';
                                        } elseif (strpos($status, 'Needs') !== false || strpos($status, 'Attention') !== false) {
                                            $statusClass = 'status-needs-attention';
                                        }
                                        ?>
                                        <span class="health-status <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewStudent(<?php echo $student['id']; ?>)">View</button>
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="conductAssessment(<?php echo $student['id']; ?>)">Health Assessment</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No students found at <?php echo htmlspecialchars($school_name); ?>.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/nurse_dashboard.php" class="btn btn-outline-secondary mt-3">Back to Dashboard</a>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function viewStudent(studentId) {
        window.location.href = '/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/view_student.php?id=' + studentId;
    }

    function conductAssessment(studentId) {
        window.location.href = '/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_assessment.php?id=' + studentId;
    }
</script>
</body>
</html> 