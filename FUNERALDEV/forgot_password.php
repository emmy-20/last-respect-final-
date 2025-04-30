<?php
session_start();

require_once 'db_connect.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = ""; // ADD THIS

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);

    $sql = "SELECT id, full_name FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            $reset_token = bin2hex(random_bytes(50));
            $reset_token_expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $update_sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $reset_token, $reset_token_expiry, $user['id']);

            if ($update_stmt->execute()) {
                $reset_link = "http://localhost/funeraldev/reset_password.php?token=$reset_token";

                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'emilywambugha20@students.uonbi.ac.ke';
                    $mail->Password   = 'rxxe odnm zioe tkis'; // Use App Password if 2FA
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('emilywambugha20@students.uonbi.ac.ke', 'Last Respect FundDrive Platform');
                    $mail->addAddress($email, $user['full_name']);

                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body    = "Hi " . htmlspecialchars($user['full_name']) . ",<br><br>Click the link below to reset your password:<br><br><a href='$reset_link'>$reset_link</a><br><br>This link expires in 1 hour.";

                    $mail->send();

                    // Instead of redirecting immediately, show success
                    $success = "Password reset link has been sent to your email.";
                } catch (Exception $e) {
                    $error = "Message could not be sent. Mailer Error: " . $mail->ErrorInfo;
                }
            } else {
                $error = "Failed to generate reset token. Please try again.";
            }
        } else {
            $error = "No account found with that email.";
        }
    } else {
        $error = "Database error. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Forgot Password</h2>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <button type="submit">Submit</button>
    </form>
</body>
</html>
