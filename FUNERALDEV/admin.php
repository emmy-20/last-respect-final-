<?php
session_start();
include 'db_connect.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

$members = [];
$result = $conn->query("SELECT id, full_name, id_number FROM users WHERE status = 'active'");
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_registered = isset($_POST['is_registered']) ? 1 : 0;
    $deceased_user_id = $is_registered ? $_POST['deceased_user_id'] : null;
    $deceased_name = !$is_registered ? trim($_POST['deceased_name']) : '';
    $relationship = !$is_registered ? trim($_POST['relationship']) : '';
    $date_of_death = $_POST['date_of_death'];
    $deadline = $_POST['deadline'];
    $area = trim($_POST['area']);
    $description = trim($_POST['description']);
    $affected_user_id = $is_registered ? $deceased_user_id : $_POST['affected_user_id'];
    $reported_by = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO deaths 
        (deceased_user_id, deceased_name, affected_user_id, date_of_death, relationship, reported_by, deadline, area, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("isississs",
        $deceased_user_id, $deceased_name, $affected_user_id, $date_of_death,
        $relationship, $reported_by, $deadline, $area, $description
    );

    if ($stmt->execute()) {
        $success = "Death report submitted successfully.";

        // Send email notification
        $admin_email = 'emilywambugha20@students.uonbi.ac.ke';
        $subject = "New Death Logged";
        $body = "
            A new death has been logged.<br><br>
            <strong>Name:</strong> " . ($is_registered ? 'Registered Member' : $deceased_name) . "<br>
            <strong>Area:</strong> $area<br>
            <strong>Deadline:</strong> $deadline<br>
            <strong>Description:</strong><br>$description
        ";
        sendEmail($admin_email, $subject, $body);

        // TODO: Trigger dashboard notification here
        // TODO: Trigger contribution logic for the deceased here

    } else {
        $error = "Database error: " . $stmt->error;
    }
    $stmt->close();
}

function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'emilywambugha20@students.uonbi.ac.ke';
        $mail->Password = 'rxxe odnm zioe tkis';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('emilywambugha20@students.uonbi.ac.ke', 'Last Respect FundDrive Platform');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Log a Death</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        label { color: black; }
        span { font-size: 14px; font-weight: bold; color: black; }
    </style>
    <script>
        function toggleDeceasedSection() {
            const isChecked = document.getElementById('is_registered').checked;

            document.getElementById('registered_section').style.display = isChecked ? 'block' : 'none';
            document.getElementById('manual_deceased_section').style.display = isChecked ? 'none' : 'block';
            document.getElementById('affected_member_section').style.display = isChecked ? 'none' : 'block';
        }
    </script>
</head>
<body>
<?php include 'navbar.php'; ?>
<h2>Log a Death (Admin)</h2>

<?php if ($success): ?>
    <div class="alert-message success"><?php echo $success; ?></div>
<?php elseif ($error): ?>
    <div class="alert-message error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">
    <label>
        <input type="checkbox" id="is_registered" name="is_registered" onclick="toggleDeceasedSection()">
        Deceased is a registered member
    </label><br><br>

    <div id="registered_section" style="display: none;">
        <label>Select Deceased (Registered Member):</label><br>
        <select name="deceased_user_id">
            <?php foreach ($members as $m): ?>
                <option value="<?php echo $m['id']; ?>">
                    <?php echo htmlspecialchars($m['full_name']); ?> (ID: <?php echo htmlspecialchars($m['id_number']); ?>)
                </option>
            <?php endforeach; ?>
        </select><br><br>
    </div>

    <div id="manual_deceased_section">
        <label>Deceased Full Name:</label><br>
        <input type="text" name="deceased_name"><br><br>

        <label>Relationship to Affected Member:</label><br>
        <input type="text" name="relationship"><br><br>
    </div>

    <div id="affected_member_section">
        <label>Affected Member:</label><br>
        <select name="affected_user_id">
            <?php foreach ($members as $m): ?>
                <option value="<?php echo $m['id']; ?>">
                    <?php echo htmlspecialchars($m['full_name']); ?> (ID: <?php echo htmlspecialchars($m['id_number']); ?>)
                </option>
            <?php endforeach; ?>
        </select><br><br>
    </div>

    <label>Date of Death:</label><br>
    <input type="date" name="date_of_death" required><br><br>

    <label>Burial Date (Deadline for Contributions):</label><br>
    <input type="date" name="deadline" required><br><br>

    <label>Area:</label><br>
    <input type="text" name="area" required><br><br>

    <label>Description:</label><br>
    <textarea name="description" rows="4" cols="50" required></textarea><br><br>

    <button type="submit">Submit Death Record</button>
</form>


<script>
    // Set initial state on page load
    window.onload = toggleDeceasedSection;

    setTimeout(() => {
        const alert = document.querySelector('.alert-message');
        if (alert) {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }
    }, 5000);
</script>
</body>
</html>
