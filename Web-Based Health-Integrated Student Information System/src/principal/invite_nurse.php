<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: ../../templates/register/login.php?error=Access+denied');
    exit();
}
require_once(__DIR__ . '/../common/db.php');
require_once(__DIR__ . '/../common/notify.php');

$principal_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $request_id = intval($_POST['request_id']);
        $action = $_POST['action'];
        if ($action === 'resend') {
            // Optionally, update timestamp or send email
            header('Location: ../../templates/principal/notifications.php?msg=Request+resent');
            exit();
        } elseif ($action === 'cancel') {
            $del = $conn->prepare('DELETE FROM nurse_requests WHERE id = ?');
            $del->bind_param('i', $request_id);
            $del->execute();
            header('Location: ../../templates/principal/notifications.php?msg=Request+cancelled');
            exit();
        }
    } elseif (isset($_POST['nurse_email'], $_POST['school_id'])) {
        $nurse_email = trim($_POST['nurse_email']);
        $school_id = intval($_POST['school_id']);
        // Check for existing requests (pending, accepted, or rejected)
        $check = $conn->prepare('SELECT id, status FROM nurse_requests WHERE school_id = ? AND nurse_email = ?');
        $check->bind_param('is', $school_id, $nurse_email);
        $check->execute();
        $check_result = $check->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing_request = $check_result->fetch_assoc();
            if ($existing_request['status'] === 'pending') {
                header('Location: ../../templates/principal/notifications.php?error=Pending+request+already+exists+for+this+nurse');
                exit();
            } elseif ($existing_request['status'] === 'accepted') {
                header('Location: ../../templates/principal/notifications.php?error=Nurse+is+already+assigned+to+this+school');
                exit();
            } elseif ($existing_request['status'] === 'rejected') {
                // Update rejected request to pending
                $update = $conn->prepare('UPDATE nurse_requests SET status = "pending" WHERE id = ?');
                $update->bind_param('i', $existing_request['id']);
                if ($update->execute()) {
                    // Add notification for the principal
                    add_notification($principal_id, 'principal', 'nurse_invitation', 'Nurse invitation resent to: ' . $nurse_email);
                    
                    // Add notification for the nurse (if they exist in the system)
                    $nurse_check = $conn->prepare('SELECT id FROM nurses WHERE email = ?');
                    $nurse_check->bind_param('s', $nurse_email);
                    $nurse_check->execute();
                    $nurse_result = $nurse_check->get_result();
                    if ($nurse_row = $nurse_result->fetch_assoc()) {
                        add_notification($nurse_row['id'], 'nurse', 'invitation_received', 'You have received a school assignment request from a principal');
                    }
                    $nurse_check->close();
                    
                    header('Location: ../../templates/principal/notifications.php?msg=Invite+resent+to+previously+rejected+nurse');
                    exit();
                } else {
                    header('Location: ../../templates/principal/notifications.php?error=Failed+to+resend+invite');
                    exit();
                }
            }
        }
        // Insert new request
        $stmt = $conn->prepare('INSERT INTO nurse_requests (school_id, nurse_email, status) VALUES (?, ?, "pending")');
        $stmt->bind_param('is', $school_id, $nurse_email);
        if ($stmt->execute()) {
            // Add notification for the principal
            add_notification($principal_id, 'principal', 'nurse_invitation', 'Nurse invitation sent to: ' . $nurse_email);
            
            // Add notification for the nurse (if they exist in the system)
            $nurse_check = $conn->prepare('SELECT id FROM nurses WHERE email = ?');
            $nurse_check->bind_param('s', $nurse_email);
            $nurse_check->execute();
            $nurse_result = $nurse_check->get_result();
            if ($nurse_row = $nurse_result->fetch_assoc()) {
                add_notification($nurse_row['id'], 'nurse', 'invitation_received', 'You have received a school assignment request from a principal');
            }
            $nurse_check->close();
            
            // Optionally, send email here
            header('Location: ../../templates/principal/notifications.php?msg=Invite+sent');
            exit();
        } else {
            header('Location: ../../templates/principal/notifications.php?error=Failed+to+send+invite');
            exit();
        }
    }
}
header('Location: ../../templates/principal/notifications.php');
exit(); 