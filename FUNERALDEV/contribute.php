 <?php
session_start();
require_once 'db_connect.php';

// Only logged-in members can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
      }

$member_id = $_SESSION['user_id'];
$fixed_amount = 100;

// Check if death_id is provided
if (!isset($_GET['death_id']) || !is_numeric($_GET['death_id'])) {
    echo "<div class='container mt-5'>";
    echo "<h2 class='text-center mb-4'>Select a Deceased Member to Contribute</h2>";

    $fetch_stmt = $conn->prepare("
        SELECT d.id, 
               COALESCE(du.full_name, d.deceased_name) AS display_name,
               d.date_of_death
        FROM deaths d
        LEFT JOIN users du ON d.deceased_user_id = du.id
        ORDER BY d.date_of_death DESC
        LIMIT 10
    ");
    $fetch_stmt->execute();
    $result = $fetch_stmt->get_result();
    $fetch_stmt->close();

    if ($result->num_rows > 0) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-bordered text-center mx-auto' style='max-width: 800px;'>";
        echo "<thead class='table-dark'><tr><th>Name</th><th>Date of Death</th><th>Action</th></tr></thead><tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>" . htmlspecialchars($row['display_name']) . "</td>
                <td>" . htmlspecialchars($row['date_of_death']) . "</td>
                <td><a href='contribute.php?death_id=" . $row['id'] . "' class='btn btn-sm btn-primary'>Contribute</a></td>
            </tr>";
        }

        echo "</tbody></table></div>";
    } else {
        echo "<p class='text-center text-muted'>No deceased records available.</p>";
    }

    echo "</div>";

    exit;
}




$death_id = (int)$_GET['death_id'];

// Fetch deceased details, even if the notification is no longer active
$stmt = $conn->prepare("
    SELECT 
        d.deceased_name,
        COALESCE(du.full_name, d.deceased_name) AS display_name,
        au.full_name AS affected_member_name
    FROM deaths d
    LEFT JOIN users du ON d.deceased_user_id = du.id
    LEFT JOIN users au ON d.affected_user_id = au.id
    WHERE d.id = ?
");
$stmt->bind_param("i", $death_id);
$stmt->execute();
$death = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$death) {
    echo "<p style='color:red;'>Death record not found.</p>";
    exit;
}

// Check if user already has a contribution record
$contrib_stmt = $conn->prepare("
    SELECT id AS contrib_id, status, payment_method
    FROM contributions
    WHERE death_id = ? AND user_id = ?
");
$contrib_stmt->bind_param("ii", $death_id, $member_id);
$contrib_stmt->execute();
$contribution = $contrib_stmt->get_result()->fetch_assoc();
$contrib_stmt->close();

// If no contribution exists, insert one with fixed amount
if (!$contribution) {
    $insert_stmt = $conn->prepare("
        INSERT INTO contributions (death_id, user_id, amount, status)
        VALUES (?, ?, ?, 'pending')
    ");
    $insert_stmt->bind_param("iii", $death_id, $member_id, $fixed_amount);
    $insert_stmt->execute();
    $insert_stmt->close();

    // Re-fetch the new contribution
    $recheck_stmt = $conn->prepare("
        SELECT id AS contrib_id, status, payment_method
        FROM contributions
        WHERE death_id = ? AND user_id = ?
    ");
    $recheck_stmt->bind_param("ii", $death_id, $member_id);
    $recheck_stmt->execute();
    $contribution = $recheck_stmt->get_result()->fetch_assoc();
    $recheck_stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contribute to <?= htmlspecialchars($death['display_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2 class="text-center">Contribute for <?= htmlspecialchars($death['display_name']); ?></h2>

    <div class="card shadow p-4 mx-auto" style=" color :black; max-width: 680px; margin-top: 2rem;">
        <h5 class="mb-3 text-center"style="font-size :20px;">
            You are about to contribute to:  
            <span class="text-primary fw-semibold"><?= htmlspecialchars($death['display_name']); ?></span>
        </h5>

        <p class="mb-3 text-center">
            <strong>Contribution Amount:</strong> KES 
            <span class="fs-5"><?= number_format($fixed_amount); ?></span>
        </p>

        <?php if ($contribution['status'] === 'pending'): ?>
            <form action="stk_push.php" method="POST">
            <input type="hidden" name="death_id" value="<?php echo $death_id; ?>" />
                <input type="hidden" name="amount" value="<?= $fixed_amount; ?>">

                <div class="mb-3">
                    <label for="phone" class="form-label">M-PESA Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="e.g. 0712345678" pattern="07[0-9]{8}" required>
                </div>

                <button type="submit" class="btn btn-success w-100">Pay Now via MPESA</button>
            </form>
        <?php else: ?>
            <div class="alert alert-success text-center mt-3">
                <strong>Payment Completed</strong><br>
                Method: <?= htmlspecialchars($contribution['payment_method'] ?? 'MPESA'); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- If the death record is no longer active but user still wants to contribute -->
    <?php if (!$death): ?>
        <div class="alert alert-warning mt-3 text-center">
            The death notification is no longer active, but you can still contribute.
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
