<?php
session_start();
require_once(__DIR__ . '/../../src/common/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Principal Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2>Complete Your Profile (Principal)</h2>
            <form action="../../src/register/register_principal.php" method="POST">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" readonly>
                    <small class="text-muted">Name was set during account creation</small>
                </div>
                <div class="mb-3">
                    <label for="elementary_school" class="form-label">Elementary School</label>
                    <select class="form-select" id="elementary_school" name="elementary_school" required>
                        <option value="">Select School</option>
                        <?php foreach ($school_options as $school): ?>
                            <option value="<?php echo htmlspecialchars($school); ?>"><?php echo htmlspecialchars($school); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="school_lrn" class="form-label">School ID / LRN</label>
                    <input type="text" class="form-control" id="school_lrn" name="school_lrn" required>
                </div>
                <div class="mb-3">
                    <label for="contact_info" class="form-label">Contact Info</label>
                    <input type="text" class="form-control" id="contact_info" name="contact_info" required>
                </div>
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 