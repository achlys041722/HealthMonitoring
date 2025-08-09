<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
    exit();
}
$activePage = '';
$school_options = [
    'Tahusan Elementary School',
    'Biasong Elementary School',
    'Otama Elementary School',
    'Hinunangan West Central School'
];
$grade_levels = [
    'Kinder',
    'Grade 1',
    'Grade 2',
    'Grade 3',
    'Grade 4',
    'Grade 5',
    'Grade 6'
];
// Build a PHP array of assigned grades for each school
$assigned_grades_by_school = [];
foreach ($school_options as $school) {
    $stmt = $conn->prepare('SELECT grade_level FROM teachers WHERE elementary_school = ? AND status = "approved"');
    $stmt->bind_param('s', $school);
    $stmt->execute();
    $result = $stmt->get_result();
    $assigned_grades_by_school[$school] = [];
    while ($row = $result->fetch_assoc()) {
        $assigned_grades_by_school[$school][] = $row['grade_level'];
    }
    $stmt->close();
}
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
// Fetch teacher status from DB
$teacher_status = '';
if (isset($_SESSION['email'])) {
    $stmt = $conn->prepare('SELECT status FROM teachers WHERE email = ?');
    $stmt->bind_param('s', $_SESSION['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $teacher_status = $row['status'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2>Complete Your Profile (Teacher)</h2>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-info" role="alert">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <?php if ($teacher_status === 'rejected'): ?>
                <div class="alert alert-danger" role="alert">
                    Your previous request was <strong>rejected</strong> by the principal. Please update your profile and submit again, or contact your principal for more information.
                </div>
            <?php elseif ($teacher_status === 'pending' && isset($_GET['success']) && $_GET['success'] === 'profile_updated'): ?>
                <div class="alert alert-info" role="alert">
                    Your account is pending approval by the principal.
                </div>
            <?php endif; ?>
            <form action="../../src/register/register_teacher.php" method="POST" id="teacherRegForm">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" readonly required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Elementary School</label>
                    <select class="form-select" name="elementary_school" id="elementary_school" required onchange="loadGradeLevels()">
                        <option value="">Select Elementary School</option>
                        <?php
                        // Fetch all schools
                        $stmt = $conn->prepare("SELECT id, school_name FROM schools ORDER BY school_name");
                        $stmt->execute();
                        $schools_result = $stmt->get_result();
                        while ($school = $schools_result->fetch_assoc()):
                        ?>
                        <option value="<?php echo htmlspecialchars($school['school_name']); ?>"><?php echo htmlspecialchars($school['school_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Grade Level</label>
                    <select class="form-select" name="grade_level_id" id="grade_level_id" required>
                        <option value="">Select Grade Level</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" required>
                </div>
                <div class="mb-3">
                    <label for="contact_info" class="form-label">Contact Info</label>
                    <input type="text" class="form-control" id="contact_info" name="contact_info" required>
                </div>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>">
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </form>
        </div>
    </div>
</div>
<script>
const assignedGradesBySchool = <?php echo json_encode($assigned_grades_by_school); ?>;
</script>
<script>
function loadGradeLevels() {
    const schoolSelect = document.getElementById('elementary_school');
    const gradeSelect = document.getElementById('grade_level_id');
    const selectedSchool = schoolSelect.value;
    
    // Reset grade level dropdown
    gradeSelect.innerHTML = '<option value="">Select Grade Level</option>';
    
    if (selectedSchool) {
        fetch('/Web-Based%20Health-Integrated%20Student%20Information%20System/src/common/get_school_id.php?school_name=' + encodeURIComponent(selectedSchool))
            .then(response => response.json())
            .then(data => {
                if (data.school_id) {
                    return fetch('/Web-Based%20Health-Integrated%20Student%20Information%20System/src/common/get_grade_levels.php?school_id=' + data.school_id);
                } else {
                    throw new Error('School not found');
                }
            })
            .then(response => response.json())
            .then(gradeLevels => {
                // Get assigned grades for the selected school
                const assigned = assignedGradesBySchool[selectedSchool] || [];
                gradeLevels.forEach(grade => {
                    if (!assigned.includes(grade.grade_name)) {
                        const option = document.createElement('option');
                        option.value = grade.grade_name; // Use grade name for ENUM
                        option.textContent = grade.grade_name;
                        gradeSelect.appendChild(option);
                    }
                });
            })
            .catch(error => {
                console.error('Error loading grade levels:', error);
            });
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 