<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

$activePage = 'students';

// Get student ID from URL parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php?error=invalid_student');
    exit();
}

$student_id = (int)$_GET['id'];

// Fetch student information with school and grade details
$stmt = $conn->prepare("
    SELECT s.*, gl.grade_name, gl.school_id, sch.school_name, sh.*
    FROM students s
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN schools sch ON gl.school_id = sch.id
    LEFT JOIN student_health sh ON s.id = sh.student_id
    WHERE s.id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php?error=student_not_found');
    exit();
}

$student = $result->fetch_assoc();

// Fetch grade levels for the current school
$grade_levels_stmt = $conn->prepare("SELECT id, grade_name FROM grade_levels WHERE school_id = ? ORDER BY 
    CASE grade_name 
        WHEN 'Kinder' THEN 1
        WHEN 'Grade 1' THEN 2
        WHEN 'Grade 2' THEN 3
        WHEN 'Grade 3' THEN 4
        WHEN 'Grade 4' THEN 5
        WHEN 'Grade 5' THEN 6
        WHEN 'Grade 6' THEN 7
        ELSE 8
    END");
$grade_levels_stmt->bind_param("i", $student['school_id']);
$grade_levels_stmt->execute();
$grade_levels_result = $grade_levels_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <h2 class="mb-0">Edit Student Information</h2>
                <div>
                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/view_student.php?id=<?php echo $student_id; ?>" class="btn btn-primary">View Student</a>
                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php" class="btn btn-secondary">Back to Students</a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> Student information has been updated successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> 
                    <?php 
                    switch($_GET['error']) {
                        case 'update_failed':
                            echo 'Failed to update student. Please try again.';
                            break;
                        case 'invalid_data':
                            echo 'Invalid data provided. Please check your input.';
                            break;
                        default:
                            echo 'An error occurred. Please try again.';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="/Web-Based%20Health-Integrated%20Student%20Information%20System/src/principal/update_student.php">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                
                <div class="form-section">
                    <!-- Personal Info Card -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">Personal Information</div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" name="middle_name" value="<?php echo htmlspecialchars($student['middle_name']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sex</label>
                                    <select class="form-select" name="sex" id="sex" required onchange="handleGenderChange()">
                                        <option value="">Select Sex</option>
                                        <option value="Male" <?php echo $student['sex'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $student['sex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Birthdate</label>
                                    <input type="date" class="form-control" name="birthdate" value="<?php echo $student['birthdate']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">LRN</label>
                                    <input type="text" class="form-control" name="lrn" value="<?php echo htmlspecialchars($student['lrn']); ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="2" required><?php echo htmlspecialchars($student['address']); ?></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Parent/Guardian Name</label>
                                    <input type="text" class="form-control" name="parent_name" value="<?php echo htmlspecialchars($student['parent_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Info Card -->
                    <div class="card">
                        <div class="card-header bg-success text-white">Academic Information</div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">School</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['school_name']); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Grade Level</label>
                                    <select class="form-select" name="grade_level_id" id="grade_level_id" required onchange="handleGradeChange()">
                                        <option value="">Select Grade Level</option>
                                        <?php while ($grade = $grade_levels_result->fetch_assoc()): ?>
                                            <option value="<?php echo $grade['id']; ?>" <?php echo $student['grade_level_id'] == $grade['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($grade['grade_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Health Information Card -->
                <div class="card">
                    <div class="card-header bg-info text-white">Health Information</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Height (cm)</label>
                                        <input type="number" step="0.1" class="form-control" name="height" value="<?php echo $student['height']; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Weight (kg)</label>
                                        <input type="number" step="0.1" class="form-control" name="weight" value="<?php echo $student['weight']; ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">BMI</label>
                                        <input type="number" step="0.1" class="form-control" name="bmi" value="<?php echo $student['bmi']; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nutritional Status</label>
                                        <input type="text" class="form-control" name="nutritional_status" value="<?php echo htmlspecialchars($student['nutritional_status']); ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Height for Age</label>
                                        <select class="form-select" name="height_for_age">
                                            <option value="">Select Status</option>
                                            <option value="Severely Stunted" <?php echo $student['height_for_age'] === 'Severely Stunted' ? 'selected' : ''; ?>>Severely Stunted</option>
                                            <option value="Stunted" <?php echo $student['height_for_age'] === 'Stunted' ? 'selected' : ''; ?>>Stunted</option>
                                            <option value="Normal" <?php echo $student['height_for_age'] === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                            <option value="Tall" <?php echo $student['height_for_age'] === 'Tall' ? 'selected' : ''; ?>>Tall</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Weight for Age</label>
                                        <select class="form-select" name="weight_for_age">
                                            <option value="">Select Status</option>
                                            <option value="Severely Stunted" <?php echo $student['weight_for_age'] === 'Severely Stunted' ? 'selected' : ''; ?>>Severely Stunted</option>
                                            <option value="Stunted" <?php echo $student['weight_for_age'] === 'Stunted' ? 'selected' : ''; ?>>Stunted</option>
                                            <option value="Normal" <?php echo $student['weight_for_age'] === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                            <option value="Overweight" <?php echo $student['weight_for_age'] === 'Overweight' ? 'selected' : ''; ?>>Overweight</option>
                                            <option value="Obese" <?php echo $student['weight_for_age'] === 'Obese' ? 'selected' : ''; ?>>Obese</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">4Ps Beneficiary</label>
                                        <select class="form-select" name="four_ps_beneficiary">
                                            <option value="0" <?php echo $student['four_ps_beneficiary'] == 0 ? 'selected' : ''; ?>>No</option>
                                            <option value="1" <?php echo $student['four_ps_beneficiary'] == 1 ? 'selected' : ''; ?>>Yes</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Exam</label>
                                        <input type="date" class="form-control" name="date_of_exam" value="<?php echo $student['date_of_exam']; ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Deworming</label>
                                        <select class="form-select" name="deworming">
                                            <option value="None" <?php echo $student['deworming'] === 'None' ? 'selected' : ''; ?>>None</option>
                                            <option value="1st Dose" <?php echo $student['deworming'] === '1st Dose' ? 'selected' : ''; ?>>1st Dose</option>
                                            <option value="2nd Dose (Complete)" <?php echo $student['deworming'] === '2nd Dose (Complete)' ? 'selected' : ''; ?>>2nd Dose (Complete)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="Good" <?php echo ($student['status'] ?? '') === 'Good' ? 'selected' : ''; ?>>Good</option>
                                            <option value="Fair" <?php echo ($student['status'] ?? '') === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                                            <option value="Needs Attention" <?php echo ($student['status'] ?? '') === 'Needs Attention' ? 'selected' : ''; ?>>Needs Attention</option>
                                            <option value="Requires Follow-up" <?php echo ($student['status'] ?? '') === 'Requires Follow-up' ? 'selected' : ''; ?>>Requires Follow-up</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Intervention</label>
                                        <select class="form-select" name="intervention">
                                            <option value="">Select Intervention</option>
                                            <option value="Treatment" <?php echo $student['intervention'] === 'Treatment' ? 'selected' : ''; ?>>Treatment</option>
                                            <option value="Referral" <?php echo $student['intervention'] === 'Referral' ? 'selected' : ''; ?>>Referral</option>
                                            <option value="None" <?php echo $student['intervention'] === 'None' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Immunization Information -->
                        <div class="section-header">Immunization Records</div>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">MR (Measles-Rubella)</label>
                                <select class="form-select" name="immunization_mr">
                                    <option value="">Select Status</option>
                                    <option value="None" <?php echo $student['immunization_mr'] === 'None' ? 'selected' : ''; ?>>None</option>
                                    <option value="1st dose" <?php echo $student['immunization_mr'] === '1st dose' ? 'selected' : ''; ?>>1st dose</option>
                                    <option value="2nd dose" <?php echo $student['immunization_mr'] === '2nd dose' ? 'selected' : ''; ?>>2nd dose</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">TD (Tetanus-Diphtheria)</label>
                                <select class="form-select" name="immunization_td">
                                    <option value="">Select Status</option>
                                    <option value="None" <?php echo $student['immunization_td'] === 'None' ? 'selected' : ''; ?>>None</option>
                                    <option value="1st dose" <?php echo $student['immunization_td'] === '1st dose' ? 'selected' : ''; ?>>1st dose</option>
                                    <option value="2nd dose" <?php echo $student['immunization_td'] === '2nd dose' ? 'selected' : ''; ?>>2nd dose</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">HPV (Human Papillomavirus)</label>
                                <select class="form-select" name="immunization_hpv" id="immunization_hpv">
                                    <option value="">Select Status</option>
                                    <option value="None" <?php echo $student['immunization_hpv'] === 'None' ? 'selected' : ''; ?>>None</option>
                                    <option value="1st dose" <?php echo $student['immunization_hpv'] === '1st dose' ? 'selected' : ''; ?>>1st dose</option>
                                    <option value="2nd dose" <?php echo $student['immunization_hpv'] === '2nd dose' ? 'selected' : ''; ?>>2nd dose</option>
                                </select>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Ailments</label>
                                <textarea class="form-control" name="ailments" rows="2"><?php echo htmlspecialchars($student['ailments']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Allergies</label>
                                <textarea class="form-control" name="allergies" rows="2"><?php echo htmlspecialchars($student['allergies']); ?></textarea>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" name="remarks" rows="2"><?php echo htmlspecialchars($student['remarks']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Update Student Information</button>
                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/view_student.php?id=<?php echo $student_id; ?>" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                </div>
            </form>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
            // Get the selected grade level name to check if it's Grade 4
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

    // Initialize HPV immunization state on page load
    document.addEventListener('DOMContentLoaded', function() {
        handleGenderChange();
        handleGradeChange();
    });
</script>
</body>
</html> 