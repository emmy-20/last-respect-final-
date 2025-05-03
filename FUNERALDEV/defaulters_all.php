<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// PHPMailer setup
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_members'])) {
    $death_id = intval($_POST['death_id']);
    $deceased_name = $_POST['deceased_name'] ?? 'the deceased';

    $sent = 0;
    $failed = 0;

    foreach ($_POST['selected_members'] as $entry) {
        list($email, $name) = explode('|', $entry);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'emilywambugha20@students.uonbi.ac.ke';
            $mail->Password = 'rxxe odnm zioe tkis'; // Use app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('emilywambugha20@students.uonbi.ac.ke', 'Last Respect Fundrive Platform');
            $mail->addAddress($email, $name);
            $mail->isHTML(false);
            $mail->Subject = "Reminder: Contribution Pending for {$deceased_name}";
            $mail->Body = "Dear {$name},\n\nThis is a reminder that your contribution for the late {$deceased_name} is still pending.\nKindly make your payment as soon as possible.\n\nThank you for your support.\n\nRegards,\nContributions Team";

            $mail->send();
            $sent++;
        } catch (Exception $e) {
            $failed++;
        }
    }

    $successMessage = "{$sent} emails sent successfully.";
    if ($failed > 0) {
        $errorMessage = "{$failed} emails failed to send.";
    }
}

$deaths = $conn->query("SELECT id, deceased_name FROM deaths ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Defaulters - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function toggleAll(source) {
            let checkboxes = document.querySelectorAll('.member-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        window.onload = function () {
            const alertBox = document.getElementById("alert-box");
            if (alertBox) {
                setTimeout(() => {
                    alertBox.style.display = "none";
                }, 5000);
            }
        }
    </script>
</head>
<body class="p-4">

<h2>All Defaulters (Grouped by Deceased)</h2>
<a href="admin_contributions.php">← Back to Contributions</a><br><br>

<?php if ($successMessage || $errorMessage): ?>
    <div id="alert-box">
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php while ($death = $deaths->fetch_assoc()):
    $death_id = $death['id'];
    $deceased_name = htmlspecialchars($death['deceased_name']);

    $defaulters = $conn->prepare("
        SELECT u.full_name, u.phone_number, u.email 
        FROM users u
        LEFT JOIN contributions c ON u.id = c.user_id AND c.death_id = ?
        WHERE u.role = 'member' AND (c.status = 'pending' OR c.id IS NULL) AND u.email IS NOT NULL
    ");
    $defaulters->bind_param("i", $death_id);
    $defaulters->execute();
    $result = $defaulters->get_result();

    if ($result->num_rows > 0):
?>
    <hr>
    <h4><?php echo $deceased_name; ?></h4>

    <form method="POST">
        <input type="hidden" name="death_id" value="<?php echo $death_id; ?>">
        <input type="hidden" name="deceased_name" value="<?php echo $deceased_name; ?>">

        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped table-hover align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="member-checkbox" name="selected_members[]" value="<?php echo htmlspecialchars($row['email']) . '|' . htmlspecialchars($row['full_name']); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="text-end mb-5">
            <button type="submit" class="btn btn-primary">Notify Selected Members</button>
        </div>
    </form>

<?php 
    endif;
endwhile;
?>

</body>
</html>
