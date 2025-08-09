<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}

$activePage = 'my_students';

// Get student ID from URL parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/my_students.php?error=invalid_student');
    exit();
}

$student_id = (int)$_GET['id'];
$teacher_id = $_SESSION['user_id'];

// Fetch student information with school and grade details, ensuring teacher can only view their assigned students
$stmt = $conn->prepare("
    SELECT s.*, gl.grade_name, sch.school_name, sh.*
    FROM students s
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN schools sch ON gl.school_id = sch.id
    LEFT JOIN student_health sh ON s.id = sh.student_id
    JOIN teachers t ON gl.id = t.grade_level_id
    WHERE s.id = ? AND t.id = ?
");
$stmt->bind_param("ii", $student_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/my_students.php?error=student_not_found');
    exit();
}

$student = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Student - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        .form-control-plaintext {
            color: #212529;
            font-weight: 500;
        }
        .section-header {
            background-color: #f8f9fa;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="col-lg-10 ms-sm-auto col-md-9 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="mb-0">View Student Information</h2>
                <div>
                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/edit_student.php?id=<?php echo $student_id; ?>" class="btn btn-warning">Edit Student</a>
                    <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/my_students.php" class="btn btn-secondary">Back to My Students</a>
                </div>
            </div>

            <div class="row">
                <!-- Personal Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['first_name']); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['last_name']); ?></div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Middle Name</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['middle_name'] ?: 'N/A'); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sex</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['sex']); ?></div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Birthdate</label>
                                    <div class="form-control-plaintext"><?php echo date('F j, Y', strtotime($student['birthdate'])); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">LRN</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['lrn']); ?></div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['address']); ?></div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Parent/Guardian Name</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['parent_name']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Academic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">School</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['school_name']); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Grade Level</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['grade_name']); ?></div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date Added</label>
                                    <div class="form-control-plaintext"><?php echo date('F j, Y', strtotime($student['created_at'])); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Updated</label>
                                    <div class="form-control-plaintext"><?php echo date('F j, Y', strtotime($student['updated_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Health Information -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Health Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($student['student_id']): // Check if health record exists ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Height (cm)</label>
                                            <div class="form-control-plaintext"><?php echo $student['height'] ? htmlspecialchars($student['height']) . ' cm' : 'N/A'; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Weight (kg)</label>
                                            <div class="form-control-plaintext"><?php echo $student['weight'] ? htmlspecialchars($student['weight']) . ' kg' : 'N/A'; ?></div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">BMI</label>
                                            <div class="form-control-plaintext"><?php echo $student['bmi'] ? htmlspecialchars($student['bmi']) : 'N/A'; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Nutritional Status</label>
                                            <div class="form-control-plaintext"><?php echo htmlspecialchars($student['nutritional_status'] ?: 'N/A'); ?></div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Height for Age</label>
                                            <div class="form-control-plaintext"><?php echo htmlspecialchars($student['height_for_age'] ?: 'N/A'); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Weight for Age</label>
                                            <div class="form-control-plaintext"><?php echo htmlspecialchars($student['weight_for_age'] ?: 'N/A'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">4Ps Beneficiary</label>
                                            <div class="form-control-plaintext"><?php echo $student['four_ps_beneficiary'] ? 'Yes' : 'No'; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Date of Exam</label>
                                            <div class="form-control-plaintext"><?php echo $student['date_of_exam'] ? date('F j, Y', strtotime($student['date_of_exam'])) : 'N/A'; ?></div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Deworming</label>
                                            <div class="form-control-plaintext"><?php echo htmlspecialchars($student['deworming'] ?: 'N/A'); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Status</label>
                                            <div class="form-control-plaintext"><?php echo htmlspecialchars($student['status'] ?: 'N/A'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Immunization Information -->
                            <div class="section-header">Immunization Records</div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">MR (Measles-Rubella)</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['immunization_mr'] ?: 'N/A'); ?></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">TD (Tetanus-Diphtheria)</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['immunization_td'] ?: 'N/A'); ?></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">HPV (Human Papillomavirus)</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['immunization_hpv'] ?: 'N/A'); ?></div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Ailments</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['ailments'] ?: 'None'); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Allergies</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['allergies'] ?: 'None'); ?></div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <label class="form-label">Remarks</label>
                                    <div class="form-control-plaintext"><?php echo htmlspecialchars($student['remarks'] ?: 'None'); ?></div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <strong>No health record found.</strong> Health information has not been recorded for this student yet.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 