<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

$death_id = $_GET['death_id'] ?? null;

if (!$death_id) {
    echo "Invalid request. Death ID is required.";
    exit;
}

$success = '';
$error = '';

// Fetch deceased name
$stmt = $conn->prepare("SELECT deceased_name FROM deaths WHERE id = ?");
$stmt->bind_param("i", $death_id);
$stmt->execute();
$stmt->bind_result($deceased_name);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);

    if ($description && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO funeral_expenses (death_id, description, amount, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("isd", $death_id, $description, $amount);
        if ($stmt->execute()) {
            $success = "Expense logged successfully.";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Please provide valid expense details.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Log Expense - <?php echo htmlspecialchars($deceased_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Log Expense for <?php echo htmlspecialchars($deceased_name); ?></h2>

    <?php if ($success): ?>
    <div id="alert-msg" class="alert alert-success"><?php echo $success; ?></div>
<?php elseif ($error): ?>
    <div id="alert-msg" class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>


    <form method="POST">
        <div class="mb-3">
            <label for="description" class="form-label">Expense Description</label>
            <input type="text" name="description" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Amount (KES)</label>
            <input type="number" step="0.01" name="amount" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Log Expense</button>
        
    </form>
</div>
<script>
    <?php if ($success): ?>
        // Hide success message after 3 seconds and redirect
        setTimeout(function() {
            window.location.href = "view_expenses.php?death_id=<?php echo $death_id; ?>";
        }, 3000);
    <?php elseif ($error): ?>
        // Hide error message after 5 seconds
        setTimeout(function() {
            const alert = document.getElementById("alert-msg");
            if (alert) alert.style.display = "none";
        }, 5000);
    <?php endif; ?>
</script>

</body>
</html>
