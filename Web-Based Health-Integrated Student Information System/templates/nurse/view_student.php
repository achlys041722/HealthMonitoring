<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

$activePage = 'students';

// Get student ID from URL
$student_id = $_GET['id'] ?? null;
if (!$student_id) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_records.php?error=invalid_student');
    exit();
}

// Get nurse's assigned schools
$nurse_id = $_SESSION['user_id'];
$nurse_email_stmt = $conn->prepare('SELECT email FROM nurses WHERE id = ?');
$nurse_email_stmt->bind_param('i', $nurse_id);
$nurse_email_stmt->execute();
$nurse_email_stmt->bind_result($nurse_email);
$nurse_email_stmt->fetch();
$nurse_email_stmt->close();

// Get assigned schools for this nurse
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

if (empty($assigned_schools)) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_records.php?error=no_school_assigned');
    exit();
}

// Get student information with health record
$school_placeholders = str_repeat('?,', count($assigned_schools) - 1) . '?';
$stmt = $conn->prepare("
    SELECT s.*, gl.grade_name, sch.school_name, sh.*
    FROM students s 
    JOIN grade_levels gl ON s.grade_level_id = gl.id 
    JOIN schools sch ON gl.school_id = sch.id 
    LEFT JOIN student_health sh ON s.id = sh.student_id
    WHERE s.id = ? AND sch.school_name IN ($school_placeholders)
");
$params = array_merge([$student_id], $assigned_schools);
$types = 'i' . str_repeat('s', count($assigned_schools));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_records.php?error=student_not_found');
    exit();
}

// Calculate age
$birthdate = new DateTime($student['birthdate']);
$today = new DateTime();
$age = $today->diff($birthdate)->y;

// Get filter params for back link
$filter_params = '';
if (!empty($_GET['search']) || !empty($_GET['school']) || !empty($_GET['grade']) || !empty($_GET['status'])) {
    $filter_params = '?';
    $filter = [];
    if (!empty($_GET['search'])) $filter['search'] = $_GET['search'];
    if (!empty($_GET['school'])) $filter['school'] = $_GET['school'];
    if (!empty($_GET['grade'])) $filter['grade'] = $_GET['grade'];
    if (!empty($_GET['status'])) $filter['status'] = $_GET['status'];
    $filter_params .= http_build_query($filter);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Details - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            color: white;
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 0.75rem;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0,0,0,0.05);
            height: 100%;
        }
        
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .card-header {
            padding: 0.75rem;
            border-bottom: 1px solid #f8f9fa;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-header i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .info-row:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            transform: translateX(3px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .info-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 0.85rem;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        
        .info-content {
            flex: 1;
            min-width: 0;
        }
        
        .info-label {
            font-size: 0.75rem;
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.2rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .info-value {
            font-size: 0.9rem;
            color: #212529;
            font-weight: 700;
            word-wrap: break-word;
        }
        
        .health-status {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .status-good { 
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .status-fair { 
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        
        .status-needs-attention { 
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
        }
        
        .status-follow-up { 
            background: linear-gradient(135deg, #007bff, #6610f2);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .btn-modern {
            border-radius: 20px;
            padding: 0.6rem 1.25rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.4s ease;
            border: none;
            font-size: 0.8rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        
        .btn-success-modern {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-secondary-modern {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .alert-modern {
            border-radius: 12px;
            border: none;
            padding: 1.25rem;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1565c0;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        /* Enhanced card header colors */
        .card-header.bg-primary {
            background: linear-gradient(135deg, #007bff, #0056b3) !important;
        }
        
        .card-header.bg-success {
            background: linear-gradient(135deg, #28a745, #1e7e34) !important;
        }
        
        .card-header.bg-info {
            background: linear-gradient(135deg, #17a2b8, #117a8b) !important;
        }
        
        .card-header.bg-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800) !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-header {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .card-body {
                padding: 2rem;
            }
            
            .info-row {
                padding: 1rem;
                margin-bottom: 1.25rem;
            }
            
            .info-icon {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
                margin-right: 1.25rem;
            }
            
            .student-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            
            .action-buttons {
                gap: 1rem;
            }
            
            .btn-modern {
                padding: 0.875rem 1.75rem;
                font-size: 0.9rem;
            }
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
                    <div class="d-flex align-items-center">
                        <div class="student-avatar">
                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                        </div>
                        <div class="ms-3">
                            <h2 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                            <p class="text-muted mb-0">
                                <i class="fas fa-id-card me-1"></i>LRN: <?php echo htmlspecialchars($student['lrn']); ?> | 
                                <i class="fas fa-layer-group me-1"></i><?php echo htmlspecialchars($student['grade_name']); ?> | 
                                <i class="fas fa-school me-1"></i><?php echo htmlspecialchars($student['school_name']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_assessment.php?id=<?php echo $student_id; ?>" 
                           class="btn btn-modern btn-success-modern">
                            <i class="fas fa-stethoscope me-2"></i>Conduct Assessment
                        </a>
                        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_records.php<?php echo $filter_params; ?>" 
                           class="btn btn-modern btn-secondary-modern">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="row g-3">
                <!-- Personal Information -->
                <div class="col-xl-6 col-lg-6">
                    <div class="info-card">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-user"></i>Personal Information
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-icon bg-primary text-white">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Full Name</div>
                                    <div class="info-value">
                                        <?php 
                                        echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                                        if (!empty($student['middle_name'])) {
                                            echo ' ' . htmlspecialchars($student['middle_name']);
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-info text-white">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Birthdate & Age</div>
                                    <div class="info-value">
                                        <?php echo date('M d, Y', strtotime($student['birthdate'])); ?> (<?php echo $age; ?> years old)
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-warning text-white">
                                    <i class="fas fa-venus-mars"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Sex</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['sex']); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-success text-white">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Address</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['address'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-secondary text-white">
                                    <i class="fas fa-user-friends"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Parent/Guardian</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['parent_name'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Health Information -->
                <div class="col-xl-6 col-lg-6">
                    <div class="info-card">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-heartbeat"></i>Health Information
                        </div>
                        <div class="card-body">
                            <?php if ($student['height'] || $student['weight'] || $student['bmi']): ?>
                            <div class="info-row">
                                <div class="info-icon bg-success text-white">
                                    <i class="fas fa-ruler-vertical"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Height</div>
                                    <div class="info-value"><?php echo $student['height'] ? $student['height'] . ' cm' : 'N/A'; ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-warning text-white">
                                    <i class="fas fa-weight"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Weight</div>
                                    <div class="info-value"><?php echo $student['weight'] ? $student['weight'] . ' kg' : 'N/A'; ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-info text-white">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">BMI</div>
                                    <div class="info-value"><?php echo $student['bmi'] ?: 'N/A'; ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-row">
                                <div class="info-icon bg-primary text-white">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Height-for-age</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['height_for_age'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-info text-white">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Weight-for-age</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['weight_for_age'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-warning text-white">
                                    <i class="fas fa-apple-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Nutritional Status</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['nutritional_status'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <?php if ($student['status']): ?>
                            <div class="info-row">
                                <div class="info-icon bg-danger text-white">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Overall Health Status</div>
                                    <div class="info-value">
                                        <?php 
                                        $status = $student['status'];
                                        $statusClass = 'status-fair';
                                        if ($status === 'Good') {
                                            $statusClass = 'status-good';
                                        } elseif (strpos($status, 'Needs') !== false) {
                                            $statusClass = 'status-needs-attention';
                                        } elseif (strpos($status, 'Follow-up') !== false) {
                                            $statusClass = 'status-follow-up';
                                        }
                                        ?>
                                        <span class="health-status <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-row">
                                <div class="info-icon bg-secondary text-white">
                                    <i class="fas fa-hand-holding-heart"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">4Ps Beneficiary</div>
                                    <div class="info-value">
                                        <?php echo $student['four_ps_beneficiary'] ? 'Yes' : 'No'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-3">
                <!-- Immunization Status -->
                <div class="col-xl-6 col-lg-6">
                    <div class="info-card">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-syringe"></i>Immunization Status
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-icon bg-info text-white">
                                    <i class="fas fa-shield-virus"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">MR (Measles-Rubella)</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['immunization_mr'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-warning text-white">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">TD (Tetanus-Diphtheria)</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['immunization_td'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-success text-white">
                                    <i class="fas fa-female"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">HPV (Grade 4 Females Only)</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['immunization_hpv'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-danger text-white">
                                    <i class="fas fa-pills"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Deworming</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['deworming'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Health Issues & Interventions -->
                <div class="col-xl-6 col-lg-6">
                    <div class="info-card">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-exclamation-triangle"></i>Health Issues & Interventions
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-icon bg-danger text-white">
                                    <i class="fas fa-thermometer-half"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Ailments/Health Issues</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['ailments'] ?: 'None reported'); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-warning text-white">
                                    <i class="fas fa-allergies"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Allergies</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['allergies'] ?: 'None reported'); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon bg-info text-white">
                                    <i class="fas fa-stethoscope"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Intervention Required</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['intervention'] ?: 'None'); ?></div>
                                </div>
                            </div>
                            
                            <?php if ($student['remarks']): ?>
                            <div class="info-row">
                                <div class="info-icon bg-primary text-white">
                                    <i class="fas fa-comment-medical"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Nurse's Remarks</div>
                                    <div class="info-value"><?php echo nl2br(htmlspecialchars($student['remarks'])); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($student['date_of_exam']): ?>
                            <div class="info-row">
                                <div class="info-icon bg-success text-white">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Last Health Examination</div>
                                    <div class="info-value"><?php echo date('M d, Y', strtotime($student['date_of_exam'])); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!$student['height'] && !$student['weight'] && !$student['bmi']): ?>
            <div class="alert alert-modern" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3"></i>
                    <div>
                        <h5 class="mb-1">No Health Assessment Recorded</h5>
                        <p class="mb-0">This student hasn't had a health assessment yet. Click "Conduct Assessment" to perform the first health evaluation.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 