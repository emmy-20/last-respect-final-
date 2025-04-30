<?php
session_start();
require_once 'db_connect.php';

// Ensure member is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Member Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">  <!-- Link to external CSS -->
</head>
<body>
<?php include 'navbar.php'; ?>
    <h2>Member Dashboard</h2>
<div class="dashboard-container">
        <div class="dashboard-box"><a href="notifications.php">View Notifications</a></div>
        <div class="dashboard-box"><a href="contribute.php">Contribute for Deceased</a></div>
        <div class="dashboard-box"><a href="my_contributions.php">View My Contributions</a></div>
        <div class="dashboard-box"><a href="member_report.php">Report Death to the Village Leader</a></div>
    </div>
    
</body>
</html>
