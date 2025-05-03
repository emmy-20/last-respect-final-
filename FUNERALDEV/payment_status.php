<?php
session_start();
include 'db_connect.php';

$status = $_GET['status'] ?? 'failed';
$death_id = $_GET['death_id'] ?? null;

$message = $_SESSION['error_message'] ?? "Something went wrong. Please try again.";
if ($status === 'success') {
    $message = $_SESSION['thankyou_message'] ?? "Your payment was successful. Thank you for your contribution.";
}

// Clear the session message after it’s displayed to avoid it showing on reload
unset($_SESSION['error_message']);
unset($_SESSION['thankyou_message']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="card shadow p-4 mx-auto" style="max-width: 600px;">
        <?php if ($status === 'success'): ?>
            <h3 class="text-center text-success mb-4">Thank You!</h3>
            <p class="text-center fs-5"><?= htmlspecialchars($message); ?></p>
        <?php else: ?>
            <h3 class="text-center text-danger mb-4">Payment Not Completed</h3>
            <p class="text-center fs-5"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <div class="text-center mt-4">
            <?php if ($death_id): ?>
                <a href="contribute.php?death_id=<?= urlencode($death_id); ?>" class="btn btn-warning">Try Again</a>
            <?php endif; ?>
            <a href="contribute.php" class="btn btn-secondary ms-2">Back to Contributions</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
