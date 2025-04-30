<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// Fetch all deaths
$deaths = $conn->query("SELECT id, deceased_name FROM deaths ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Member Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .table-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4 text-center">Funeral Contributions and Usage Report</h2>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Deceased Name</th>
                        <th>Total Collected (KES)</th>
                        <th>Number of Contributors</th>
                        <th>Expenses Details</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($death = $deaths->fetch_assoc()): 
                    $death_id = $death['id'];
                    $death_name = htmlspecialchars($death['deceased_name']);

                    // Fetch total collected and number of contributors
                    $stmt = $conn->prepare("SELECT SUM(amount), COUNT(*) FROM contributions WHERE death_id = ? AND status = 'paid'");
                    $stmt->bind_param("i", $death_id);
                    $stmt->execute();
                    $stmt->bind_result($total_collected, $contributors_count);
                    $stmt->fetch();
                    $stmt->close();

                    // Fetch expenses
                    $expenses = $conn->prepare("SELECT description, amount FROM funeral_expenses WHERE death_id = ?");
                    $expenses->bind_param("i", $death_id);
                    $expenses->execute();
                    $expenses_result = $expenses->get_result();
                ?>
                    <tr>
                        <td><?php echo $death_name; ?></td>
                        <td class="text-end"><?php echo number_format($total_collected ?? 0, 2); ?></td>
                        <td class="text-center"><?php echo $contributors_count ?? 0; ?></td>
                        <td>
                            <?php if ($expenses_result->num_rows > 0): ?>
                                <ul class="mb-0">
                                    <?php while ($expense = $expenses_result->fetch_assoc()): ?>
                                        <li><?php echo htmlspecialchars($expense['description']); ?>: KES <?php echo number_format($expense['amount'], 2); ?></li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">No expenses recorded</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
