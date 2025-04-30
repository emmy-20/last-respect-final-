<?php
session_start();
require_once 'db_connect.php';

// Only logged-in members can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['user_id'];

// Fetch all contributions by the logged-in member
$stmt = $conn->prepare("
    SELECT 
        c.amount, c.status, c.payment_method, c.created_at,
        COALESCE(d.deceased_name, 'Unknown') AS deceased_name
    FROM contributions c
    JOIN deaths d ON c.death_id = d.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Contributions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Blue-themed header but with black text for better contrast */
        .table-primary {
            background-color: rgba(94, 165, 245, 0.699) !important;
            color: #000 !important;
        }

        .table-primary th {
            color: #000 !important;
        }

        body {
            background-color: #f4f8fd;
            color: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        h2 {
            color: #3b5998;
        }

        .table-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .badge {
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">My Contribution History</h2>

    <div class="table-container">
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped align-middle w-100">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>Deceased Name</th>
                            <th>Amount (KES)</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="text-center">
                            <td><?= htmlspecialchars($row['deceased_name']); ?></td>
                            <td class="text-end"><?= number_format((float)$row['amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?= $row['status'] === 'paid' ? 'success' : 'warning'; ?>">
                                    <?= ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['payment_method'] ?? 'MPESA'); ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                You haven't made any contributions yet.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>


