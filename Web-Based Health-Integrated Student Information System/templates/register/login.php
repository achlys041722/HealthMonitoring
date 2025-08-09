<?php
session_start();
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard based on role
    $role = $_SESSION['role'];
    if ($role === 'principal') {
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/principal_dashboard.php');
        exit();
    } elseif ($role === 'nurse') {
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/nurse/nurse_dashboard.php');
        exit();
    } elseif ($role === 'teacher') {
        header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/teacher/teacher_dashboard.php');
        exit();
    }
}
// Feedback messages
$error = isset($_GET['error']) ? $_GET['error'] : '';
$error_type = isset($_GET['error_type']) ? $_GET['error_type'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
// Always show registration errors if error_type=register
$show_register_error = ($error && $error_type === 'register');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login / Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-section { min-height: 100vh; display: flex; align-items: center; }
        .form-section { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-light">
<div class="container login-section">
    <div class="row w-100">
        <div class="col-md-6 d-flex align-items-center justify-content-center">
            <div class="form-section w-100">
                <h3 class="mb-4">Create Account</h3>
                <?php if ($show_register_error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form action="../../src/register/register_account.php" method="POST">
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="principal">Principal</option>
                            <option value="teacher">Teacher</option>
                            <option value="nurse">Nurse</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Admin Password</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
            </div>
        </div>
        <div class="col-md-6 d-flex align-items-center justify-content-center">
            <div class="form-section w-100">
                <h3 class="mb-4">Login</h3>
                <?php if ($error && $error_type !== 'register'): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form action="../../src/auth/login.php" method="POST">
                    <div class="mb-3">
                        <label for="login_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="login_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="login_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="login_password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 