<?php
require_once(__DIR__ . '/../../src/common/db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Health Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Debug Health Records Search Filter</h2>
    
    <?php
    $nurse_email = 'sample@gmail.com';
    
    // Check nurse requests
    echo "<h4>Nurse Requests for $nurse_email:</h4>";
    $requests = $conn->query("SELECT * FROM nurse_requests WHERE nurse_email = '$nurse_email' ORDER BY id");
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
    // Check assigned schools query
    echo "<h4>Assigned Schools Query Result:</h4>";
    $assigned_schools_stmt = $conn->prepare('
        SELECT DISTINCT s.school_name 
        FROM schools s 
        JOIN nurse_requests nr ON s.id = nr.school_id 
        WHERE nr.nurse_email = ? AND nr.status = "accepted"
        ORDER BY s.school_name
    ');
    $assigned_schools_stmt->bind_param('s', $nurse_email);
    $assigned_schools_stmt->execute();
    $assigned_schools_result = $assigned_schools_stmt->get_result();
    $assigned_schools = [];
    while ($row = $assigned_schools_result->fetch_assoc()) {
        $assigned_schools[] = $row['school_name'];
    }
    $assigned_schools_stmt->close();
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Assigned Schools</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($assigned_schools)): ?>
            <tr class="table-warning">
                <td>No assigned schools found!</td>
            </tr>
            <?php else: ?>
                <?php foreach ($assigned_schools as $school): ?>
                <tr>
                    <td><?php echo htmlspecialchars($school); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    // Test the main health records query
    echo "<h4>Test Health Records Query:</h4>";
    if (empty($assigned_schools)) {
        echo "<div class='alert alert-warning'>No assigned schools - query will return no data</div>";
    } else {
        $query = "
            SELECT s.id as student_id, s.lrn, s.first_name, s.middle_name, s.last_name, gl.grade_name, sch.school_name, sh.date_of_exam, sh.status
            FROM students s
            JOIN grade_levels gl ON s.grade_level_id = gl.id
            JOIN schools sch ON gl.school_id = sch.id
            LEFT JOIN student_health sh ON s.id = sh.student_id
            WHERE sch.school_name IN (" . str_repeat('?,', count($assigned_schools) - 1) . "?)
            ORDER BY sch.school_name, gl.grade_name, s.last_name, s.first_name
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(str_repeat('s', count($assigned_schools)), ...$assigned_schools);
        $stmt->execute();
        $records = $stmt->get_result();
        ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>LRN</th>
                    <th>Name</th>
                    <th>School</th>
                    <th>Grade</th>
                    <th>Health Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($records->num_rows == 0): ?>
                <tr class="table-warning">
                    <td colspan="6">No students found in assigned schools</td>
                </tr>
                <?php else: ?>
                    <?php while ($row = $records->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['student_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['lrn']); ?></td>
                        <td><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['school_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['grade_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['status'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        $stmt->close();
    }
    ?>
    
    <hr>
    
    <?php
    // Check if there are any students in the system
    echo "<h4>All Students in System:</h4>";
    $all_students = $conn->query("
        SELECT s.id, s.lrn, s.first_name, s.last_name, gl.grade_name, sch.school_name
        FROM students s
        JOIN grade_levels gl ON s.grade_level_id = gl.id
        JOIN schools sch ON gl.school_id = sch.id
        ORDER BY sch.school_name, gl.grade_name, s.last_name
    ");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Student ID</th>
                <th>LRN</th>
                <th>Name</th>
                <th>School</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($all_students->num_rows == 0): ?>
            <tr class="table-warning">
                <td colspan="5">No students found in the system</td>
            </tr>
            <?php else: ?>
                <?php while ($row = $all_students->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['lrn']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['school_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['grade_name']); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'create_nurse_requests') {
            // Create nurse requests for all schools
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
    
    <div class="card">
        <div class="card-header">
            Fix Nurse Requests
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
    
    <div class="mt-4">
        <a href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php" class="btn btn-primary">Back to Login</a>
    </div>
</div>
</body>
</html> 