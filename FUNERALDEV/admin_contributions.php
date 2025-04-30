<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// Handle marking as paid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid_id'])) {
    $id = $_POST['mark_paid_id'];
    $update = $conn->prepare("UPDATE contributions SET status = 'paid' WHERE id = ?");
    $update->bind_param("i", $id);
    $update->execute();
    $update->close();
}

// Search by ID number
$search_id = $_GET['search_id'] ?? '';

$deaths = $conn->query("SELECT id, deceased_name, created_at FROM deaths ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Contributions Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f8fd;
            color: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        h2, h4 {
            color: #3b5998;
        }

        .table thead th {
            background-color: rgba(94, 165, 245, 0.699);
            color: #000;
        }

        .table-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .btn-outline-danger,
        .btn-outline-secondary {
            margin-top: 10px;
        }

        .badge {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container-fluid my-4 px-4">
    <h2 class="mb-4">Admin Dashboard - Track Member Contributions</h2>

    <form method="GET" class="mb-4 d-flex" style="max-width: 500px;">
        <input type="text" name="search_id" class="form-control me-2" placeholder="Search by ID Number" value="<?php echo htmlspecialchars($search_id); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <a href="defaulters_all.php" class="btn btn-outline-danger mb-4">View All Defaulters</a>

    <?php while ($death = $deaths->fetch_assoc()):
        $death_id = $death['id'];
        $death_name = htmlspecialchars($death['deceased_name']);

        // Total paid contributions
        $stmt = $conn->prepare("SELECT SUM(amount) AS total FROM contributions WHERE death_id = ? AND status = 'paid'");
        $stmt->bind_param("i", $death_id);
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();

        // Fetch all users, and join with contributions for this death
        $query = "
            SELECT 
                u.id, u.full_name, u.id_number,
                c.id AS contrib_id, c.amount, c.status, c.payment_method
            FROM users u
            LEFT JOIN contributions c ON c.user_id = u.id AND c.death_id = ?
            WHERE u.role = 'member'
        ";
        if (!empty($search_id)) {
            $query .= " AND u.id_number = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $death_id, $search_id);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $death_id);
        }
 
        $stmt->execute();
        $result = $stmt->get_result();
    ?>
        <div class="table-container">
            <h4><?php echo $death_name; ?> (Collected: KES <?php echo number_format($total, 2); ?>)</h4>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover align-middle w-100">
                    <thead class="text-center">
                        <tr>
                            <th>Member Name</th>
                            <th>ID Number</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                     
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        $is_paid = $row['status'] === 'paid';
                    ?>
                        <tr class="<?php echo $is_paid ? '' : 'table-warning'; ?>">
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                            <td class="text-end"><?php echo $row['amount'] ? number_format($row['amount'], 2) : '0.00'; ?></td>
                            <td class="text-center">
    <span class="badge bg-<?php echo $is_paid ? 'success' : 'warning'; ?>">
        <?php echo $row['status'] ?? 'Not Paid'; ?>
    </span>
</td>
                            <td class="text-center"><?php echo $row['payment_method'] ?? '-'; ?></td>
                            
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <a href="defaulters.php?death_id=<?php echo $death_id; ?>" class="btn btn-outline-secondary">
                View Defaulters for <?php echo $death_name; ?>
            </a>
        </div>
    <?php endwhile; ?>
</div>
</body>
</html>
