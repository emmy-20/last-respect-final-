<?php
session_start();
require_once 'db_connect.php';

// Only members can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['user_id'];

// Get notifications with deceased photo (if any)
$sql = "
    SELECT n.message, n.created_at, d.deceased_photo 
    FROM notifications n
    LEFT JOIN deaths d ON n.death_id = d.id
    WHERE n.user_id = ? AND d.status = 'approved'
    ORDER BY n.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .notification {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .notification img {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }
        hr {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <h2>Welcome, <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Member'; ?>!</h2>

    <h3>Your Notifications</h3>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='notification'>";
            echo "<p><strong>Notification:</strong> " . htmlspecialchars($row['message']) . "</p>";
            echo "<p><em>Received on: " . htmlspecialchars($row['created_at']) . "</em></p>";

            // Display photo if available
            if (!empty($row['deceased_photo'])) {
                echo "<p><img src='" . htmlspecialchars($row['deceased_photo']) . "' alt='Deceased Photo'></p>";
            }

            echo "</div>";
        }
    } else {
        echo "<p>You have no notifications yet.</p>";
    }
    ?>
</body>
</html>
