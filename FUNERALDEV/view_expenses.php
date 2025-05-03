<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

$death_id = $_GET['death_id'] ?? null;

if (!$death_id) {
    echo "Invalid request. Death ID is required.";
    exit;
}

// Fetch deceased name
$stmt = $conn->prepare("SELECT deceased_name FROM deaths WHERE id = ?");
$stmt->bind_param("i", $death_id);
$stmt->execute();
$stmt->bind_result($deceased_name);
$stmt->fetch();
$stmt->close();

// Fetch expenses
$expenses = $conn->prepare("SELECT description, amount, created_at FROM funeral_expenses WHERE death_id = ?");
$expenses->bind_param("i", $death_id);
$expenses->execute();
$result = $expenses->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Expenses - <?php echo htmlspecialchars($deceased_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container my-5">
    <h2 class="mb-4">Expenses for <?php echo htmlspecialchars($deceased_name); ?></h2>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>Description</th>
                    <th>Amount (KES)</th>
                    <th>Date Logged</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td class="text-end"><?php echo number_format($row['amount'], 2); ?></td>
                            <td class="text-center"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center text-muted">No expenses recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
