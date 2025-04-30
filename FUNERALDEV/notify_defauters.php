<?php
require_once 'db_connect.php';

// Fetch all active death notices
$death_stmt = $conn->prepare("SELECT id, deceased_name, deadline FROM deaths WHERE status = 'approved' AND deadline >= CURDATE()");
$death_stmt->execute();
$death_result = $death_stmt->get_result();

while ($death = $death_result->fetch_assoc()) {
    $death_id = $death['id'];
    $deceased_name = $death['deceased_name'];
    $deadline = $death['deadline'];

    // Fetch all registered members
    $members_stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE role = 'member'");
    $members_stmt->execute();
    $members_result = $members_stmt->get_result();

    while ($member = $members_result->fetch_assoc()) {
        $member_id = $member['id'];
        $member_name = $member['full_name'];
        $member_email = $member['email'];

        // Check if the member has contributed to this death notice
        $contrib_stmt = $conn->prepare("SELECT COUNT(*) FROM contributions WHERE user_id = ? AND death_id = ?");
        $contrib_stmt->bind_param("ii", $member_id, $death_id);
        $contrib_stmt->execute();
        $contrib_stmt->bind_result($contrib_count);
        $contrib_stmt->fetch();
        $contrib_stmt->close();

        if ($contrib_count == 0) {
            // Member has not contributed; send reminder email
            $subject = "Reminder: Contribution Needed for $deceased_name";
            $message = "Dear $member_name,\n\nWe kindly remind you to contribute towards the funeral expenses of $deceased_name. The contribution deadline is $deadline.\n\nPlease log in to the platform to make your contribution.\n\nThank you for your support.\n\nBest regards,\nLast Respect FundDrive Team";
            $headers = "From: no-reply@yourdomain.com";

            // Send the email
            mail($member_email, $subject, $message, $headers);
        }
    }
    $members_stmt->close();
}
$death_stmt->close();
?>
