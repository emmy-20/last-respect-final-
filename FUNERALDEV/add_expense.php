<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// Handle expense submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $death_id = $_POST['death_id'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare("INSERT INTO funeral_expenses (death_id, description, amount) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $death_id, $description, $amount); // i = int, s = string, d = double/decimal
    $stmt->execute();
    $stmt->close();

    $success = "Expense added successfully!";
}

// Fetch deaths for the dropdown
$deaths = $conn->query("SELECT id, deceased_name FROM deaths ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Funeral Expense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Add Funeral Expense</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm" style="max-width: 600px;">
        <div class="mb-3">
            <label for="death_id" class="form-label">Select Funeral</label>
            <select name="death_id" class="form-select" required>
    <option value="">Select Deceased</option>
    <?php 
    $deaths = $conn->query("SELECT id, deceased_name, created_at FROM deaths ORDER BY created_at DESC");
    while ($death = $deaths->fetch_assoc()): 
    ?>
        <option value="<?php echo $death['id']; ?>">
            <?php echo htmlspecialchars($death['deceased_name']) . " - (" . date('d M Y', strtotime($death['created_at'])) . ")"; ?>
        </option>
    <?php endwhile; ?>
</select>

        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Expense Description</label>
            <input type="text" name="description" id="description" class="form-control" placeholder="e.g., Coffin, Transport" required>
        </div>

        <div class="mb-3">
            <label for="amount" class="form-label">Amount (KES)</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" placeholder="e.g., 5000" required>
        </div>

        <button type="submit" class="btn btn-primary">Add Expense</button>
    </form>
</div>

</body>
</html>
