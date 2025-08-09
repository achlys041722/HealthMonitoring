<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: login.php?error=Access+denied');
    exit();
}
// If the principal already has a school, redirect to dashboard
require_once '../config/db.php';
$principal_id = $_SESSION['user_id'];
$school_check = $conn->prepare('SELECT id FROM schools WHERE principal_id = ?');
$school_check->bind_param('i', $principal_id);
$school_check->execute();
$school_check->store_result();
if ($school_check->num_rows > 0) {
    header('Location: principal_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h4>Register Your School</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </div>
                        <?php endif; ?>
                        <form action="../../src/register/register_school.php" method="POST">
                            <div class="mb-3">
                                <label for="school_name" class="form-label">School Name</label>
                                <input type="text" class="form-control" id="school_name" name="school_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="school_lrn" class="form-label">School LRN</label>
                                <input type="text" class="form-control" id="school_lrn" name="school_lrn" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Register School</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 