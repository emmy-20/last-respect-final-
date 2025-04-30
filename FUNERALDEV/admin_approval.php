<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'];
    $action = $_POST['action'];

    // Get report details
    $stmt = $conn->prepare("SELECT * FROM death_reports WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$report) {
        die("Report not found.");
    }

    if ($action === 'Reject') {
        $conn->query("UPDATE death_reports SET status = 'rejected' WHERE id = $report_id");
        header("Location: admin_dashboard.php");
        exit;
    }

    // Proceed with approval
    $conn->query("UPDATE death_reports SET status = 'approved' WHERE id = $report_id");

    $deceased_user_id = $report['deceased_user_id'];
    $deceased_name = $report['deceased_name'];
    $affected_user_id = $report['affected_user_id'];
    $relationship = $report['relationship'];
    $date_of_death = $report['date_of_death'];

    if ($report['is_registered'] === 'yes') {
        $conn->query("UPDATE users SET status = 'deceased' WHERE id = $deceased_user_id");
    } else {
        $deceased_user_id = null;
    }

    // Insert into deaths table
    $stmt = $conn->prepare("INSERT INTO deaths (deceased_user_id, deceased_name, affected_user_id, date_of_death, relationship)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $deceased_user_id, $deceased_name, $affected_user_id, $date_of_death, $relationship);
    $stmt->execute();
    $death_id = $stmt->insert_id;
    $stmt->close();

    // Create contributions for all active members
    $members = $conn->query("SELECT id, marital_status FROM users WHERE status = 'active'");
    $stmt_c = $conn->prepare("INSERT INTO contributions (user_id, death_id, status, amount) VALUES (?, ?, 'pending', ?)");

    while ($m = $members->fetch_assoc()) {
        $uid = $m['id'];
        $amount = ($m['marital_status'] === 'single') ? 100 : 200;
        $stmt_c->bind_param("iid", $uid, $death_id, $amount);
        $stmt_c->execute();
    }

    $stmt_c->close();
    // Send notifications to each member
    require_once 'send_email.php';
    $members = $conn->query("SELECT id, email FROM users WHERE status = 'active'");
    $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    
    $notify_msg = "A new death has been reported (" . htmlspecialchars($deceased_name) . "). You are required to contribute.";
    
    while ($mem = $members->fetch_assoc()) {
        $uid = $mem['id'];
        $email = $mem['email'];
    
        // Save notification to database
        $notify_stmt->bind_param("is", $uid, $notify_msg);
        $notify_stmt->execute();
    
        // Send the email
        $email_subject = "New Death Notification";
        $email_body = "
            <p>Dear Member,</p>
            <p>$notify_msg</p>
            <p>Thank you.</p>
        ";
        sendEmail($email, $email_subject, $email_body);
    }
    
    $notify_stmt->close();
    


    header("Location: admin_dashboard.php?approved=1");
    exit;
}
?>
