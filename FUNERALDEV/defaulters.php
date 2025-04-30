<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

if (!isset($_GET['death_id'])) {
    echo "Death ID not specified.";
    exit;
}

$death_id = intval($_GET['death_id']);

$stmt = $conn->prepare("SELECT deceased_name FROM deaths WHERE id = ?");
$stmt->bind_param("i", $death_id);
$stmt->execute();
$stmt->bind_result($deceased_name);
$stmt->fetch();
$stmt->close();

$defaulters = $conn->prepare("
    SELECT u.full_name, u.phone_number, u.email 
    FROM users u
    JOIN contributions c ON u.id = c.user_id
    WHERE c.death_id = ? AND c.status = 'pending'
");
$defaulters->bind_param("i", $death_id);
$defaulters->execute();
$result = $defaulters->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Defaulters for <?php echo htmlspecialchars($deceased_name); ?></title>
</head>
<body>

<h2>Defaulters for <?php echo htmlspecialchars($deceased_name); ?></h2>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="table-responsive">
    <table class="table table-sm table-bordered table-striped table-hover align-middle">
        <thead class="table-dark text-center">
            <tr>
                <th>Full Name</th>
                <th>Phone Number</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td class="text-nowrap"><?php echo htmlspecialchars($row['phone_number']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between mt-3">
    <form method="POST" action="send_all_email.php" class="d-inline">
        <button type="submit" class="btn btn-sm btn-primary w-100">Notify All Members via Email</button>
    </form>
    <form method="POST" action="download_all_report.php" class="d-inline">
        <button type="submit" class="btn btn-sm btn-secondary w-100">Download All Reports</button>
    </form>
</div>


<a href="admin_contributions.php">Back to Contributions</a>

</body>
</html>
 