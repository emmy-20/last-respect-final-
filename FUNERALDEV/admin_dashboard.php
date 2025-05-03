<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="dashboard.css"> 
</head>
<body>
<?php include 'navbar.php'; ?>
    <h2>Admin Dashboard</h2>

    <div class="dashboard-container">
        <div class="dashboard-box"><a href="register_member.php">Register Member</a></div>
        <div class="dashboard-box"><a href="admin_contributions.php">manage contributions</a></div>
        <div class="dashboard-box"><a href="manage_members.php">Manage Members</a></div>
        <div class="dashboard-box"><a href="admin.php">Report death</a></div>
    </div>
</body>
</html>
