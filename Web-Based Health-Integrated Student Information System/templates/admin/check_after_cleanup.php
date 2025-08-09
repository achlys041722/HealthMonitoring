<?php
require_once(__DIR__ . '/../../src/common/db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check After Cleanup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Check Database After Cleanup</h2>
    
    <?php
    // Check schools table
    echo "<h4>Current Schools:</h4>";
    $schools = $conn->query("SELECT * FROM schools ORDER BY id");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>School Name</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $schools->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['school_name']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    // Check principals table
    echo "<h4>Principals and Their Schools:</h4>";
    $principals = $conn->query("SELECT * FROM principals ORDER BY id");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Elementary School</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $principals->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['elementary_school']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    // Check nurse requests
    echo "<h4>Nurse Requests:</h4>";
    $requests = $conn->query("SELECT * FROM nurse_requests ORDER BY id");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>School ID</th>
                <th>Nurse Email</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $requests->fetch_assoc()): ?>
            <tr <?php echo ($row['status'] === 'accepted') ? 'class="table-success"' : ''; ?>>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['school_id']; ?></td>
                <td><?php echo htmlspecialchars($row['nurse_email']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    // Check if principals' schools exist in schools table
    echo "<h4>Principal Schools vs Schools Table:</h4>";
    $principal_schools = $conn->query("SELECT DISTINCT elementary_school FROM principals");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Principal's School</th>
                <th>Exists in Schools Table?</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $principal_schools->fetch_assoc()): ?>
            <?php
            $school_name = $row['elementary_school'];
            $school_check = $conn->prepare("SELECT id FROM schools WHERE school_name = ?");
            $school_check->bind_param('s', $school_name);
            $school_check->execute();
            $school_result = $school_check->get_result();
            $exists = $school_result->num_rows > 0;
            $school_check->close();
            ?>
            <tr <?php echo !$exists ? 'class="table-warning"' : ''; ?>>
                <td><?php echo htmlspecialchars($school_name); ?></td>
                <td>
                    <?php if ($exists): ?>
                        <span class="badge bg-success">YES</span>
                    <?php else: ?>
                        <span class="badge bg-danger">NO</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'fix_missing_schools') {
            // Add missing schools from principals
            $principal_schools = $conn->query("SELECT DISTINCT elementary_school FROM principals");
            $added_count = 0;
            
            while ($row = $principal_schools->fetch_assoc()) {
                $school_name = $row['elementary_school'];
                
                // Check if school already exists
                $check_stmt = $conn->prepare("SELECT id FROM schools WHERE school_name = ?");
                $check_stmt->bind_param('s', $school_name);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows == 0) {
                    // School doesn't exist, add it
                    $insert_stmt = $conn->prepare("INSERT INTO schools (school_name) VALUES (?)");
                    $insert_stmt->bind_param('s', $school_name);
                    if ($insert_stmt->execute()) {
                        $added_count++;
                        echo "<div class='alert alert-success'>Added missing school: $school_name</div>";
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
            
            if ($added_count > 0) {
                echo "<div class='alert alert-success'><strong>Added $added_count missing schools!</strong></div>";
            } else {
                echo "<div class='alert alert-info'>No missing schools found.</div>";
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'create_nurse_requests') {
            // Create nurse requests for all schools
            $nurse_email = 'sample@gmail.com';
            $schools_result = $conn->query("SELECT id FROM schools ORDER BY id");
            $created_count = 0;
            
            while ($school = $schools_result->fetch_assoc()) {
                $school_id = $school['id'];
                
                // Check if request already exists
                $check_stmt = $conn->prepare("SELECT id FROM nurse_requests WHERE school_id = ? AND nurse_email = ?");
                $check_stmt->bind_param('is', $school_id, $nurse_email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows == 0) {
                    // Request doesn't exist, create it
                    $insert_stmt = $conn->prepare("INSERT INTO nurse_requests (school_id, nurse_email, status) VALUES (?, ?, 'accepted')");
                    $insert_stmt->bind_param('is', $school_id, $nurse_email);
                    if ($insert_stmt->execute()) {
                        $created_count++;
                        echo "<div class='alert alert-success'>Created nurse request for school ID: $school_id</div>";
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
            
            if ($created_count > 0) {
                echo "<div class='alert alert-success'><strong>Created $created_count nurse requests!</strong></div>";
            } else {
                echo "<div class='alert alert-info'>All nurse requests already exist.</div>";
            }
        }
    }
    ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Fix Missing Schools
                </div>
                <div class="card-body">
                    <p>Add any missing schools from the principals table to the schools table.</p>
                    <form method="POST">
                        <button type="submit" name="action" value="fix_missing_schools" class="btn btn-warning">
                            Add Missing Schools
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Create Nurse Requests
                </div>
                <div class="card-body">
                    <p>Create accepted nurse requests for all schools for sample@gmail.com.</p>
                    <form method="POST">
                        <button type="submit" name="action" value="create_nurse_requests" class="btn btn-info">
                            Create Nurse Requests
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php" class="btn btn-primary">Back to Login</a>
    </div>
</div>
</body>
</html> 