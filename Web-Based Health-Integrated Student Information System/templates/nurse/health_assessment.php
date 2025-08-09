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
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/students.php?error=invalid_student');
    exit();
}

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

// Get student information
$stmt = $conn->prepare("
    SELECT s.*, gl.grade_name, sch.school_name
    FROM students s 
    JOIN grade_levels gl ON s.grade_level_id = gl.id 
    JOIN schools sch ON gl.school_id = sch.id 
    WHERE s.id = ? AND sch.school_name = ?
");
$stmt->bind_param("is", $student_id, $nurse['assigned_school']);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/students.php?error=student_not_found');
    exit();
}

// Get existing health record
$stmt = $conn->prepare("SELECT * FROM student_health WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$health_result = $stmt->get_result();
$health_record = $health_result->fetch_assoc();

// Debug: Log the loaded health record data
error_log("DEBUG: Loaded health record for student $student_id - nutritional_status: '" . ($health_record['nutritional_status'] ?? 'NULL') . "'");
error_log("DEBUG: Full health record: " . print_r($health_record, true));

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
    <title>Health Assessment - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            font-size: 0.9rem;
        }
        .card-header {
            font-size: 1.1rem;
        }
        .form-label.section-header {
            font-size: 1.1rem !important;
        }
        .form-control, .form-select {
            font-size: 1rem;
        }
        .student-info {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="mb-0">Health Assessment</h2>
                <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_records.php<?php echo $filter_params; ?>" class="btn btn-secondary">Back to Student's List</a>
            </div>
            
            <!-- Student Information -->
            <div class="student-info">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>LRN:</strong> <?php echo htmlspecialchars($student['lrn']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Grade:</strong> <?php echo htmlspecialchars($student['grade_name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Sex:</strong> <?php echo htmlspecialchars($student['sex']); ?>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <strong>Age:</strong> <?php 
                        $birthdate = new DateTime($student['birthdate']);
                        $today = new DateTime();
                        $age = $today->diff($birthdate)->y;
                        echo $age . ' years old';
                        ?>
                    </div>
                    <div class="col-md-3">
                        <strong>School:</strong> <?php echo htmlspecialchars($student['school_name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Parent:</strong> <?php echo htmlspecialchars($student['parent_name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Date:</strong> <?php echo date('M d, Y'); ?>
                    </div>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> Health assessment has been saved successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> Failed to save health assessment. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Health Assessment Form -->
            <form method="POST" action="/Web-Based%20Health-Integrated%20Student%20Information%20System/src/nurse/save_assessment.php">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                
                <div class="form-section">
                    <!-- Physical Measurements Card -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">Physical Measurements</div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Height (cm)</label>
                                    <input type="number" step="0.01" class="form-control" name="height" value="<?php echo $health_record['height'] ?? $student['height'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" step="0.01" class="form-control" name="weight" value="<?php echo $health_record['weight'] ?? $student['weight'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">BMI</label>
                                    <input type="number" step="0.01" class="form-control" name="bmi" value="<?php echo $health_record['bmi'] ?? ''; ?>" readonly id="bmi_calculated">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Height-for-age</label>
                                    <select class="form-select" name="height_for_age" required>
                                        <option value="">Select</option>
                                        <option value="Severely Stunted" <?php echo ($health_record['height_for_age'] ?? '') === 'Severely Stunted' ? 'selected' : ''; ?>>Severely Stunted</option>
                                        <option value="Stunted" <?php echo ($health_record['height_for_age'] ?? '') === 'Stunted' ? 'selected' : ''; ?>>Stunted</option>
                                        <option value="Normal" <?php echo ($health_record['height_for_age'] ?? '') === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="Tall" <?php echo ($health_record['height_for_age'] ?? '') === 'Tall' ? 'selected' : ''; ?>>Tall</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Weight-for-age</label>
                                    <select class="form-select" name="weight_for_age" required>
                                        <option value="">Select</option>
                                        <option value="Severely Underweight" <?php echo ($health_record['weight_for_age'] ?? '') === 'Severely Underweight' ? 'selected' : ''; ?>>Severely Underweight</option>
                                        <option value="Underweight" <?php echo ($health_record['weight_for_age'] ?? '') === 'Underweight' ? 'selected' : ''; ?>>Underweight</option>
                                        <option value="Normal" <?php echo ($health_record['weight_for_age'] ?? '') === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="Overweight" <?php echo ($health_record['weight_for_age'] ?? '') === 'Overweight' ? 'selected' : ''; ?>>Overweight</option>
                                        <option value="Obese" <?php echo ($health_record['weight_for_age'] ?? '') === 'Obese' ? 'selected' : ''; ?>>Obese</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nutritional Status</label>
                                <select class="form-select" name="nutritional_status">
                                    <option value="Normal" <?php echo ($health_record['nutritional_status'] ?? '') === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                    <option value="Underweight" <?php echo ($health_record['nutritional_status'] ?? '') === 'Underweight' ? 'selected' : ''; ?>>Underweight</option>
                                    <option value="Overweight" <?php echo ($health_record['nutritional_status'] ?? '') === 'Overweight' ? 'selected' : ''; ?>>Overweight</option>
                                    <option value="Obese" <?php echo ($health_record['nutritional_status'] ?? '') === 'Obese' ? 'selected' : ''; ?>>Obese</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Immunization & Health Status Card -->
                    <div class="card">
                        <div class="card-header bg-success text-white">Immunization & Health Status</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">4Ps Beneficiary</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="four_ps_beneficiary" value="1" <?php echo ($health_record['four_ps_beneficiary'] ?? '') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Yes</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="four_ps_beneficiary" value="0" <?php echo ($health_record['four_ps_beneficiary'] ?? '') == '0' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">No</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label section-header">Immunization Status</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">MR (Measles-Rubella)</label>
                                        <select class="form-select" name="immunization_mr">
                                            <option value="None" <?php echo ($health_record['immunization_mr'] ?? '') === 'None' ? 'selected' : ''; ?>>None</option>
                                            <option value="1st dose" <?php echo ($health_record['immunization_mr'] ?? '') === '1st dose' ? 'selected' : ''; ?>>1st dose</option>
                                            <option value="2nd dose" <?php echo ($health_record['immunization_mr'] ?? '') === '2nd dose' ? 'selected' : ''; ?>>2nd dose</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">TD (Tetanus-Diphtheria)</label>
                                        <select class="form-select" name="immunization_td">
                                            <option value="None" <?php echo ($health_record['immunization_td'] ?? '') === 'None' ? 'selected' : ''; ?>>None</option>
                                            <option value="1st dose" <?php echo ($health_record['immunization_td'] ?? '') === '1st dose' ? 'selected' : ''; ?>>1st dose</option>
                                            <option value="2nd dose" <?php echo ($health_record['immunization_td'] ?? '') === '2nd dose' ? 'selected' : ''; ?>>2nd dose</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">HPV (Grade 4 Females Only)</label>
                                        <select class="form-select" name="immunization_hpv" id="immunization_hpv" <?php echo ($student['sex'] === 'Male' || $student['grade_name'] !== 'Grade 4') ? 'disabled' : ''; ?>>
                                            <option value="None" <?php echo ($health_record['immunization_hpv'] ?? '') === 'None' ? 'selected' : ''; ?>>None</option>
                                            <option value="Incomplete" <?php echo ($health_record['immunization_hpv'] ?? '') === 'Incomplete' ? 'selected' : ''; ?>>Incomplete</option>
                                            <option value="Complete" <?php echo ($health_record['immunization_hpv'] ?? '') === 'Complete' ? 'selected' : ''; ?>>Complete</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Deworming</label>
                                <select class="form-select" name="deworming">
                                    <option value="None" <?php echo ($health_record['deworming'] ?? '') === 'None' ? 'selected' : ''; ?>>None</option>
                                    <option value="1st Dose" <?php echo ($health_record['deworming'] ?? '') === '1st Dose' ? 'selected' : ''; ?>>1st Dose</option>
                                    <option value="2nd Dose (Complete)" <?php echo ($health_record['deworming'] ?? '') === '2nd Dose (Complete)' ? 'selected' : ''; ?>>2nd Dose (Complete)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Health Findings & Recommendations -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">Health Findings & Recommendations</div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Ailments/Health Issues</label>
                                <textarea class="form-control" name="ailments" rows="3" placeholder="Describe any health issues found..."><?php echo htmlspecialchars($health_record['ailments'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Allergies</label>
                                <textarea class="form-control" name="allergies" rows="3" placeholder="List any allergies..."><?php echo htmlspecialchars($health_record['allergies'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Intervention Required</label>
                                <select class="form-select" name="intervention">
                                    <option value="None" <?php echo ($health_record['intervention'] ?? '') === 'None' ? 'selected' : ''; ?>>None</option>
                                    <option value="Treatment" <?php echo ($health_record['intervention'] ?? '') === 'Treatment' ? 'selected' : ''; ?>>Treatment</option>
                                    <option value="Referral" <?php echo ($health_record['intervention'] ?? '') === 'Referral' ? 'selected' : ''; ?>>Referral</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Overall Health Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="Good" <?php echo ($health_record['status'] ?? '') === 'Good' ? 'selected' : ''; ?>>Good</option>
                                    <option value="Fair" <?php echo ($health_record['status'] ?? '') === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                                    <option value="Needs Attention" <?php echo ($health_record['status'] ?? '') === 'Needs Attention' ? 'selected' : ''; ?>>Needs Attention</option>
                                    <option value="Requires Follow-up" <?php echo ($health_record['status'] ?? '') === 'Requires Follow-up' ? 'selected' : ''; ?>>Requires Follow-up</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nurse's Remarks & Recommendations</label>
                            <textarea class="form-control" name="remarks" rows="4" placeholder="Provide detailed remarks and recommendations..."><?php echo htmlspecialchars($health_record['remarks'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Examination</label>
                            <input type="date" class="form-control" name="date_of_exam" value="<?php echo $health_record['date_of_exam'] ?? date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <button type="submit" class="btn btn-primary">Save Assessment</button>
                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/health_records.php<?php echo $filter_params; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-calculate BMI
    function calculateBMI() {
        const height = parseFloat(document.querySelector('input[name="height"]').value);
        const weight = parseFloat(document.querySelector('input[name="weight"]').value);
        
        if (height > 0 && weight > 0) {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            document.getElementById('bmi_calculated').value = bmi.toFixed(2);
        }
    }
    
    // Add event listeners for height and weight inputs
    document.querySelector('input[name="height"]').addEventListener('input', calculateBMI);
    document.querySelector('input[name="weight"]').addEventListener('input', calculateBMI);
    
    // Calculate BMI on page load if values exist
    window.addEventListener('load', calculateBMI);
</script>
</body>
</html> 