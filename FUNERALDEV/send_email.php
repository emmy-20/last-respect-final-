<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files manually
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Use your mail server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'emilywambugha20@students.uonbi.ac.ke'; // Your email
        $mail->Password   = 'rxxe odnm zioe tkis'; // Your email password or app password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('emilywambugha20@students.uonbi.ac.ke', 'Last Respct FundDrive Platform');
        $mail->addAddress($to); // Send to member

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $deceased_name = $_POST['deceased_name'];

    $subject = "Funeral Contribution Reminder";
    $message = "
        Dear $name,<br><br>
        You have a pending funeral contribution for <strong>$deceased_name</strong>.<br>
        Kindly make your contribution before the deadline.<br><br>
        Thank you,<br>
        <em>Last Respect FundDrive Platform</em>
    ";

    if (sendEmail($email, $subject, $message)) {
        echo "Email sent successfully to $email.";
    } else {
        echo " Failed to send email. Check error logs.";
    }
}

?>
