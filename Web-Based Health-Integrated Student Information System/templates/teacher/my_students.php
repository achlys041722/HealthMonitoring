<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

// Get teacher's grade level ID
$teacher_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT grade_level FROM teachers WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher || !$teacher['grade_level']) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_dashboard.php?error=no_grade_assigned');
    exit();
}

// Get the grade_level_id from grade_levels table
$grade_level = $teacher['grade_level'];
$stmt = $conn->prepare("SELECT id FROM grade_levels WHERE grade_name = ?");
$stmt->bind_param("s", $grade_level);
$stmt->execute();
$grade_result = $stmt->get_result();
$grade_row = $grade_result->fetch_assoc();

if (!$grade_row) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_dashboard.php?error=grade_not_found');
    exit();
}

$grade_level_id = $grade_row['id'];

$activePage = 'my_students';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Students - Teacher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card {
            margin-bottom: 1.5rem;
        }
        @media (min-width: 992px) {
            .form-section {
                display: flex;
                gap: 2rem;
            }
            .form-section > .card {
                flex: 1 1 0;
            }
        }
        .form-label, .form-check-label, .card-header {
            font-size: 0.75rem;
        }
        .card-header {
            font-size: 1.1rem;
        }
        .form-label.section-header {
            font-size: 1.1rem !important;
        }
        .form-control, .form-select {
            font-size: 1.05rem;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="mb-0">My Students</h2>
                <button type="button" class="btn btn-success" id="showStudentFormBtn">Add Student</button>
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
                        case 'not_approved':
                            echo 'Your account is not yet approved. Please wait for principal approval.';
                            break;
                        case 'invalid_student':
                            echo 'Invalid student ID provided.';
                            break;
                        case 'student_not_found':
                            echo 'Student not found.';
                            break;
                        case 'unauthorized':
                            echo 'You are not authorized to access this student.';
                            break;
                        default:
                            echo 'An error occurred. Please try again.';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/Web-Based%20Health-Integrated%20Student%20Information%20System/src/teacher/add_student.php" id="studentForm" style="display:none;">
                <div class="form-section">
                    <!-- Personal Info Card -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">Personal Information</div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" name="middle_name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">LRN</label>
                                    <input type="text" class="form-control" name="lrn" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Birthdate</label>
                                    <input type="date" class="form-control" name="birthdate" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sex</label>
                                    <select class="form-select" name="sex" id="sex" required onchange="handleGenderChange()">
                                        <option value="">Select Sex</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Height (cm)</label>
                                    <input type="number" step="0.01" class="form-control" name="height">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" step="0.01" class="form-control" name="weight">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Name of Parent</label>
                                <input type="text" class="form-control" name="parent_name">
                            </div>
                            <!-- Grade level is automatically assigned based on teacher's assignment -->
                            <input type="hidden" name="grade_level_id" value="<?php echo $grade_level_id; ?>">
                        </div>
                    </div>
                    <!-- Health & Nutritional Assessment Card -->
                    <div class="card">
                        <div class="card-header bg-success text-white">Health & Nutritional Assessment</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nutritional Status</label>
                                <input type="text" class="form-control" name="nutritional_status">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">BMI</label>
                                    <input type="number" step="0.01" class="form-control" name="bmi">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Examination</label>
                                    <input type="date" class="form-control" name="date_of_exam">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Height-for-age</label>
                                    <select class="form-select" name="height_for_age">
                                        <option value="">Select</option>
                                        <option value="Severely Stunted">Severely Stunted</option>
                                        <option value="Stunted">Stunted</option>
                                        <option value="Normal">Normal</option>
                                        <option value="Tall">Tall</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Weight-for-age</label>
                                    <select class="form-select" name="weight_for_age">
                                        <option value="">Select</option>
                                        <option value="Severely Stunted">Severely Stunted</option>
                                        <option value="Stunted">Stunted</option>
                                        <option value="Normal">Normal</option>
                                        <option value="Overweight">Overweight</option>
                                        <option value="Obese">Obese</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">4Ps Beneficiary</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="four_ps_beneficiary" value="1">
                                        <label class="form-check-label">Yes</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="four_ps_beneficiary" value="0">
                                        <label class="form-check-label">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label section-header">Immunization</label>
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
                            <div class="mb-3">
                                <label class="form-label section-header">Deworming</label>
                                <select class="form-select" name="deworming">
                                    <option value="None">None</option>
                                    <option value="1st Dose">1st Dose</option>
                                    <option value="2nd Dose (Complete)">2nd Dose (Complete)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ailments</label>
                                <input type="text" class="form-control" name="ailments">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Intervention</label>
                                <select class="form-select" name="intervention">
                                    <option value="">Select</option>
                                    <option value="Treatment">Treatment</option>
                                    <option value="Referral">Referral</option>
                                    <option value="None">None</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Allergies</label>
                                <input type="text" class="form-control" name="allergies">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <input type="text" class="form-control" name="status">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" name="remarks" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="reset" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
            
            <!-- Students List Section -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">My Students</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get teacher's grade level and school information
                    $teacher_id = $_SESSION['user_id'];
                    $stmt = $conn->prepare("
                        SELECT t.grade_level, gl.id AS grade_level_id, gl.grade_name, s.school_name 
                        FROM teachers t 
                        JOIN grade_levels gl ON t.grade_level = gl.grade_name 
                        JOIN schools s ON gl.school_id = s.id 
                        WHERE t.id = ?
                    ");
                    $stmt->bind_param("i", $teacher_id);
                    $stmt->execute();
                    $teacher_result = $stmt->get_result();
                    $teacher_data = $teacher_result->fetch_assoc();
                    $teacher_grade_id = $teacher_data['grade_level_id'];
                    $teacher_grade_name = $teacher_data['grade_name'];
                    $teacher_school = $teacher_data['school_name'];
                    
                    // Fetch students for teacher's grade level with school info
                    $stmt = $conn->prepare("
                        SELECT s.*, gl.grade_name, sch.school_name 
                        FROM students s 
                        JOIN grade_levels gl ON s.grade_level_id = gl.id 
                        JOIN schools sch ON gl.school_id = sch.id 
                        WHERE s.grade_level_id = ? 
                        ORDER BY s.last_name, s.first_name
                    ");
                    $stmt->bind_param("i", $teacher_grade_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>Name</th>
                                    <th>Sex</th>
                                    <th>Parent Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = $result->fetch_assoc()): ?>
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
                                    <td><?php echo htmlspecialchars($student['sex']); ?></td>
                                    <td><?php echo htmlspecialchars($student['parent_name']); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewStudent(<?php echo $student['id']; ?>)">View</button>
                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="editStudent(<?php echo $student['id']; ?>)">Edit</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No students found for <?php echo htmlspecialchars($teacher_grade_name); ?> at <?php echo htmlspecialchars($teacher_school); ?>. Add students using the form above.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_dashboard.php" class="btn btn-outline-secondary mt-3">Back to Dashboard</a>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('showStudentFormBtn').addEventListener('click', function() {
        var form = document.getElementById('studentForm');
        if (form.style.display === 'none') {
            form.style.display = 'block';
            this.textContent = 'Hide Form';
            this.classList.remove('btn-success');
            this.classList.add('btn-secondary');
        } else {
            form.style.display = 'none';
            this.textContent = 'Add Student';
            this.classList.remove('btn-secondary');
            this.classList.add('btn-success');
        }
    });

    // Handle gender change for HPV immunization
    function handleGenderChange() {
        const genderSelect = document.getElementById('sex');
        const hpvSelect = document.getElementById('immunization_hpv');
        const gradeSelect = document.getElementById('grade_level');
        
        if (genderSelect.value === 'Male') {
            hpvSelect.disabled = true;
            hpvSelect.value = 'None';
        } else if (genderSelect.value === 'Female') {
            // Check if grade level is Grade 4 for females
            if (gradeSelect.value === 'Grade 4') {
                hpvSelect.disabled = false;
            } else {
                hpvSelect.disabled = true;
                hpvSelect.value = 'None';
            }
        }
    }

    // Handle grade level change for HPV immunization
    function handleGradeChange() {
        const genderSelect = document.getElementById('sex');
        const hpvSelect = document.getElementById('immunization_hpv');
        const gradeSelect = document.getElementById('grade_level');
        
        if (genderSelect.value === 'Female' && gradeSelect.value === 'Grade 4') {
            hpvSelect.disabled = false;
        } else {
            hpvSelect.disabled = true;
            hpvSelect.value = 'None';
        }
    }

    // Student management functions
    function viewStudent(studentId) {
        window.location.href = '/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/view_student.php?id=' + studentId;
    }

    function editStudent(studentId) {
        window.location.href = '/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/edit_student.php?id=' + studentId;
    }
</script>
</body>
</html> 