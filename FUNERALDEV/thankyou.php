<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['user_id'];

// Check if death_id was passed
if (!isset($_GET['death_id']) || !is_numeric($_GET['death_id'])) {
    echo "<p class='text-danger'>Invalid request. No deceased selected.</p>";
    exit;
}

$death_id = (int) $_GET['death_id'];

// Fetch contribution details
$stmt = $conn->prepare("
    SELECT 
        c.amount, 
        c.payment_method, 
        c.status,
        COALESCE(u.full_name, d.deceased_name) AS deceased_name
    FROM contributions c
    JOIN deaths d ON c.death_id = d.id
    LEFT JOIN users u ON d.deceased_user_id = u.id
    WHERE c.user_id = ? AND c.death_id = ?
    ORDER BY c.id DESC
    LIMIT 1
");
$stmt->bind_param("ii", $member_id, $death_id);
$stmt->execute();
$result = $stmt->get_result();
$contribution = $result->fetch_assoc();
$stmt->close();

if (!$contribution) {
    echo "<p class='text-danger'>No contribution record found.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Thank You for Your Contribution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="card shadow p-4 mx-auto" style="max-width: 600px;">
        <h3 class="text-center text-success mb-4">Thank You!</h3>
        <p class="text-center fs-5">
            Your contribution to <strong><?= htmlspecialchars($contribution['deceased_name']); ?></strong> has been received.
        </p>
        <ul class="list-group list-group-flush text-center">
            <li class="list-group-item"><strong>Status:</strong> <?= ucfirst($contribution['status']); ?></li>
            <li class="list-group-item"><strong>Amount:</strong> KES <?= number_format($contribution['amount'], 2); ?></li>
            <li class="list-group-item"><strong>Payment Method:</strong> <?= htmlspecialchars($contribution['payment_method'] ?? 'MPESA'); ?></li>
        </ul>

        <div class="text-center mt-4">
            <a href="contribute.php" class="btn btn-primary">Back to Contributions</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
