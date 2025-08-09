<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

// Get principal's assigned school
$principal_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT elementary_school FROM principals WHERE id = ?");
$stmt->bind_param("i", $principal_id);
$stmt->execute();
$result = $stmt->get_result();
$principal = $result->fetch_assoc();

if (!$principal || !$principal['elementary_school']) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/principal_dashboard.php?error=no_school_assigned');
    exit();
}

$school_name = $principal['elementary_school'];

// Get the school ID and grade levels for the principal's school
$stmt = $conn->prepare("SELECT id FROM schools WHERE school_name = ?");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$school_result = $stmt->get_result();
$school = $school_result->fetch_assoc();

if (!$school) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/principal_dashboard.php?error=school_not_found');
    exit();
}

$school_id = $school['id'];

// Get grade levels for the principal's school
$stmt = $conn->prepare("SELECT id, grade_name FROM grade_levels WHERE school_id = ? ORDER BY grade_name");
$stmt->bind_param("i", $school_id);
$stmt->execute();
$grade_levels_result = $stmt->get_result();

$activePage = 'students';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students - Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Sticky Sidebar */
        .sidebar {
            position: fixed !important;
            top: 0;
            left: 0;
            height: 100vh !important;
            overflow-y: auto;
            z-index: 1000;
        }
        
        /* Adjust main content for fixed sidebar */
        main {
            margin-left: 280px !important;
            min-height: 100vh;
        }
        
        @media (max-width: 768px) {
            main {
                margin-left: 0 !important;
            }
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1000;
                width: 100%;
                height: 100vh;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
        }
        
        /* Enhanced Form Design */
        .form-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .form-section-title {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-section-title i {
            font-size: 1.2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .btn-modern {
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .btn-secondary-modern {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            color: white;
        }
        
        .btn-success-modern {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        /* Enhanced Table Styling */
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
        .btn-group .btn {
            border-radius: 20px;
            margin: 0 0.1rem;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        .btn-group .btn:hover {
            transform: translateY(-1px);
        }
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border-color: #007bff;
        }
        .btn-outline-warning:hover {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            border-color: #ffc107;
        }
        .btn-outline-danger:hover {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-color: #dc3545;
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
        .search-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        /* Animation for form show/hide */
        .form-container {
            transition: all 0.3s ease;
        }
        
        .students-list-container {
            transition: all 0.3s ease;
        }
        
        /* Progress indicator */
        .progress-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .progress-step {
            display: flex;
            align-items: center;
            margin: 0 1rem;
        }
        
        .progress-step.active {
            color: #007bff;
            font-weight: 600;
        }
        
        .progress-step.completed {
            color: #28a745;
        }
        
        .progress-step-number {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .progress-step.active .progress-step-number {
            background: #007bff;
            color: white;
        }
        
        .progress-step.completed .progress-step-number {
            background: #28a745;
            color: white;
        }
        
        .progress-step:not(.active):not(.completed) .progress-step-number {
            background: #e9ecef;
            color: #6c757d;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Students Management</h2>
                <button type="button" class="btn btn-success-modern btn-modern" id="showStudentFormBtn">
                    <i class="fas fa-plus me-2"></i>Add Student
                </button>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> 
                    <?php 
                    switch($_GET['success']) {
                        case 'student_added':
                            echo 'Student information has been added successfully.';
                            break;
                        case 'student_deleted':
                            echo 'Student has been deleted successfully.';
                            break;
                        case 'updated':
                            echo 'Student information has been updated successfully.';
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
                        case 'add_failed':
                            echo 'Failed to add student. Please try again.';
                            break;
                        case 'delete_failed':
                            echo 'Failed to delete student. Please try again.';
                            break;
                        case 'invalid_student':
                            echo 'Invalid student ID provided.';
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
            
            <!-- Add Student Form -->
            <div class="form-container" id="studentForm" style="display:none;">
                <!-- Progress Indicator -->
                <div class="progress-indicator">
                    <div class="progress-step active">
                        <div class="progress-step-number">1</div>
                        <span>Personal Information</span>
                    </div>
                    <div class="progress-step">
                        <div class="progress-step-number">2</div>
                        <span>Health Assessment</span>
                    </div>
                </div>
                
                <form method="POST" action="/Web-Based%20Health-Integrated%20Student%20Information%20System/src/principal/add_student.php" id="addStudentForm">
                    <!-- Personal Information Section -->
                    <div class="form-section-title">
                        <i class="fas fa-user"></i>
                        Personal Information
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">LRN *</label>
                                <input type="text" class="form-control" name="lrn" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Birthdate *</label>
                                <input type="date" class="form-control" name="birthdate" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Sex *</label>
                                <select class="form-select" name="sex" id="sex" required onchange="handleGenderChange()">
                                    <option value="">Select Sex</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Height (cm)</label>
                                <input type="number" step="0.01" class="form-control" name="height">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" step="0.01" class="form-control" name="weight">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Parent Name</label>
                                <input type="text" class="form-control" name="parent_name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Grade Level *</label>
                        <select class="form-select" name="grade_level_id" id="grade_level_id" required onchange="handleGradeChange()">
                            <option value="">Select Grade Level</option>
                            <?php while ($grade = $grade_levels_result->fetch_assoc()): ?>
                            <option value="<?php echo $grade['id']; ?>"><?php echo htmlspecialchars($grade['grade_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <input type="hidden" name="school_id" value="<?php echo $school_id; ?>">
                    
                    <!-- Health Assessment Section -->
                    <div class="form-section-title mt-4">
                        <i class="fas fa-heartbeat"></i>
                        Health & Nutritional Assessment
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nutritional Status</label>
                                <input type="text" class="form-control" name="nutritional_status">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">BMI</label>
                                <input type="number" step="0.01" class="form-control" name="bmi">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Height-for-age</label>
                                <select class="form-select" name="height_for_age">
                                    <option value="">Select</option>
                                    <option value="Severely Stunted">Severely Stunted</option>
                                    <option value="Stunted">Stunted</option>
                                    <option value="Normal">Normal</option>
                                    <option value="Tall">Tall</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Weight-for-age</label>
                                <select class="form-select" name="weight_for_age">
                                    <option value="">Select</option>
                                    <option value="Severely Underweight">Severely Underweight</option>
                                    <option value="Underweight">Underweight</option>
                                    <option value="Normal">Normal</option>
                                    <option value="Overweight">Overweight</option>
                                    <option value="Obese">Obese</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">4Ps Beneficiary</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="four_ps_beneficiary" value="1" id="four_ps_yes">
                                <label class="form-check-label" for="four_ps_yes">Yes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="four_ps_beneficiary" value="0" id="four_ps_no">
                                <label class="form-check-label" for="four_ps_no">No</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date of Examination</label>
                        <input type="date" class="form-control" name="date_of_exam">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Immunization</label>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">MR</label>
                                <select class="form-select" name="immunization_mr">
                                    <option value="None">None</option>
                                    <option value="1st dose">1st dose</option>
                                    <option value="2nd dose">2nd dose</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">TD</label>
                                <select class="form-select" name="immunization_td">
                                    <option value="None">None</option>
                                    <option value="1st dose">1st dose</option>
                                    <option value="2nd dose">2nd dose</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">HPV (Grade 4 Females Only)</label>
                                <select class="form-select" name="immunization_hpv" id="immunization_hpv">
                                    <option value="None">None</option>
                                    <option value="Complete">Complete</option>
                                    <option value="Incomplete">Incomplete</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Deworming</label>
                        <select class="form-select" name="deworming">
                            <option value="None">None</option>
                            <option value="1st Dose">1st Dose</option>
                            <option value="2nd Dose (Complete)">2nd Dose (Complete)</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Ailments</label>
                                <input type="text" class="form-control" name="ailments">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Allergies</label>
                                <input type="text" class="form-control" name="allergies">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Intervention</label>
                                <select class="form-select" name="intervention">
                                    <option value="">Select</option>
                                    <option value="Treatment">Treatment</option>
                                    <option value="Referral">Referral</option>
                                    <option value="None">None</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Status *</label>
                                <select class="form-select" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="Good">Good</option>
                                    <option value="Fair">Fair</option>
                                    <option value="Needs Attention">Needs Attention</option>
                                    <option value="Requires Follow-up">Requires Follow-up</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="3" placeholder="Additional notes or observations..."></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <button type="button" class="btn btn-secondary-modern btn-modern" onclick="hideForm()">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary-modern btn-modern">
                            <i class="fas fa-save me-2"></i>Save Student
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Students List Section -->
            <div class="students-list-container" id="studentsListContainer">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Students</h5>
                    </div>
                    <div class="card-body">
                        <!-- Search and Filter Section -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Search Students</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search by name, LRN, or parent...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Filter by Grade</label>
                                <select class="form-select" id="gradeFilter">
                                    <option value="">All Grades</option>
                                    <option value="Kinder">Kinder</option>
                                    <option value="Grade 1">Grade 1</option>
                                    <option value="Grade 2">Grade 2</option>
                                    <option value="Grade 3">Grade 3</option>
                                    <option value="Grade 4">Grade 4</option>
                                    <option value="Grade 5">Grade 5</option>
                                    <option value="Grade 6">Grade 6</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Filter by Sex</label>
                                <select class="form-select" id="sexFilter">
                                    <option value="">All</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">Clear Filters</button>
                                <span class="ms-2 text-muted" id="resultCount"></span>
                            </div>
                        </div>
                        
                        <?php
                        // Fetch students from principal's assigned school only
                        $stmt = $conn->prepare("
                            SELECT s.*, gl.grade_name, sch.school_name 
                            FROM students s 
                            JOIN grade_levels gl ON s.grade_level_id = gl.id 
                            JOIN schools sch ON gl.school_id = sch.id 
                            WHERE sch.id = ?
                            ORDER BY gl.grade_name, s.last_name, s.first_name
                        ");
                        $stmt->bind_param("i", $school_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0):
                        ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="studentsTable">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-id-card me-2"></i>LRN</th>
                                        <th><i class="fas fa-user me-2"></i>Name</th>
                                        <th><i class="fas fa-venus-mars me-2"></i>Sex</th>
                                        <th><i class="fas fa-graduation-cap me-2"></i>Grade Level</th>
                                        <th><i class="fas fa-users me-2"></i>Parent Name</th>
                                        <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="student-lrn">
                                                <i class="fas fa-id-card me-1"></i>
                                                <?php echo htmlspecialchars($student['lrn']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="student-name">
                                                <i class="fas fa-user-circle me-2 text-primary"></i>
                                                <?php 
                                                echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']);
                                                if (!empty($student['middle_name'])) {
                                                    echo ' ' . htmlspecialchars($student['middle_name']);
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="gender-badge <?php echo strtolower($student['sex']) === 'male' ? 'gender-male' : 'gender-female'; ?>">
                                                <i class="fas fa-<?php echo strtolower($student['sex']) === 'male' ? 'mars' : 'venus'; ?> me-1"></i>
                                                <?php echo htmlspecialchars($student['sex']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="grade-badge">
                                                <i class="fas fa-graduation-cap me-1"></i>
                                                <?php echo htmlspecialchars($student['grade_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-users me-2 text-muted"></i>
                                            <?php echo htmlspecialchars($student['parent_name']); ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewStudent(<?php echo $student['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="editStudent(<?php echo $student['id']; ?>)" title="Edit Student">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')" title="Delete Student">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <h5>No students found</h5>
                            <p class="text-muted">Add your first student using the form above.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Form visibility management
    document.getElementById('showStudentFormBtn').addEventListener('click', function() {
        showForm();
    });

    function showForm() {
        document.getElementById('studentForm').style.display = 'block';
        document.getElementById('studentsListContainer').style.display = 'none';
        document.getElementById('showStudentFormBtn').innerHTML = '<i class="fas fa-eye me-2"></i>View Students';
        document.getElementById('showStudentFormBtn').classList.remove('btn-success-modern');
        document.getElementById('showStudentFormBtn').classList.add('btn-secondary-modern');
    }

    function hideForm() {
        document.getElementById('studentForm').style.display = 'none';
        document.getElementById('studentsListContainer').style.display = 'block';
        document.getElementById('showStudentFormBtn').innerHTML = '<i class="fas fa-plus me-2"></i>Add Student';
        document.getElementById('showStudentFormBtn').classList.remove('btn-secondary-modern');
        document.getElementById('showStudentFormBtn').classList.add('btn-success-modern');
        // Reset form
        document.getElementById('addStudentForm').reset();
    }

    // Handle form submission to hide form after successful save
    document.getElementById('addStudentForm').addEventListener('submit', function() {
        // Form will be hidden by the success redirect
    });

    // Check for success parameter and hide form if present
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success')) {
            hideForm();
        }
    });

    // Handle gender change for HPV immunization
    function handleGenderChange() {
        const genderSelect = document.getElementById('sex');
        const hpvSelect = document.getElementById('immunization_hpv');
        const gradeSelect = document.getElementById('grade_level_id');
        
        if (genderSelect.value === 'Male') {
            hpvSelect.disabled = true;
            hpvSelect.value = 'None';
        } else if (genderSelect.value === 'Female') {
            // Check if grade level is Grade 4 for females
            if (gradeSelect.value) {
                const selectedOption = gradeSelect.options[gradeSelect.selectedIndex];
                const gradeName = selectedOption.text;
                
                if (gradeName === 'Grade 4') {
                    hpvSelect.disabled = false;
                } else {
                    hpvSelect.disabled = true;
                    hpvSelect.value = 'None';
                }
            }
        }
    }

    // Handle grade level change for HPV immunization
    function handleGradeChange() {
        const genderSelect = document.getElementById('sex');
        const hpvSelect = document.getElementById('immunization_hpv');
        const gradeSelect = document.getElementById('grade_level_id');
        
        if (genderSelect.value === 'Female' && gradeSelect.value) {
            const selectedOption = gradeSelect.options[gradeSelect.selectedIndex];
            const gradeName = selectedOption.text;
            
            if (gradeName === 'Grade 4') {
                hpvSelect.disabled = false;
            } else {
                hpvSelect.disabled = true;
                hpvSelect.value = 'None';
            }
        } else {
            hpvSelect.disabled = true;
            hpvSelect.value = 'None';
        }
    }

    // Student management functions
    function viewStudent(studentId) {
        window.location.href = '/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/view_student.php?id=' + studentId;
    }

    function editStudent(studentId) {
        window.location.href = '/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/edit_student.php?id=' + studentId;
    }

    function deleteStudent(studentId, studentName) {
        if (confirm('Are you sure you want to delete ' + studentName + '? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/Web-Based%20Health-Integrated%20Student%20Information%20System/src/principal/delete_student.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'student_id';
            input.value = studentId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Search and Filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const gradeFilter = document.getElementById('gradeFilter');
        const sexFilter = document.getElementById('sexFilter');
        const resultCount = document.getElementById('resultCount');
        
        // Add event listeners for real-time filtering
        searchInput.addEventListener('input', filterStudents);
        gradeFilter.addEventListener('change', filterStudents);
        sexFilter.addEventListener('change', filterStudents);
        
        // Initial count
        updateResultCount();
    });
    
    function filterStudents() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const gradeFilter = document.getElementById('gradeFilter').value;
        const sexFilter = document.getElementById('sexFilter').value;
        
        const tableRows = document.querySelectorAll('#studentsTable tbody tr');
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            const name = row.cells[1].textContent.toLowerCase();
            const lrn = row.cells[0].textContent.toLowerCase();
            const parent = row.cells[4].textContent.toLowerCase();
            const grade = row.cells[3].textContent;
            const sex = row.cells[2].textContent;
            
            // Check search term
            const matchesSearch = searchTerm === '' || 
                                name.includes(searchTerm) || 
                                lrn.includes(searchTerm) || 
                                parent.includes(searchTerm);
            
            // Check filters
            const matchesGrade = gradeFilter === '' || grade.includes(gradeFilter);
            const matchesSex = sexFilter === '' || sex.includes(sexFilter);
            
            if (matchesSearch && matchesGrade && matchesSex) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        updateResultCount(visibleCount);
    }
    
    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('gradeFilter').value = '';
        document.getElementById('sexFilter').value = '';
        filterStudents();
    }
    
    function updateResultCount(count) {
        const resultCount = document.getElementById('resultCount');
        if (count !== undefined) {
            resultCount.textContent = `Showing ${count} students`;
        } else {
            const visibleRows = document.querySelectorAll('#studentsTable tbody tr:not([style*="display: none"])').length;
            resultCount.textContent = `Showing ${visibleRows} students`;
        }
    }
</script>
</body>
</html> 