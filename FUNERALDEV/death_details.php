<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['death_id'])) {
    echo "No death ID provided.";
    exit;
}

$death_id = $_GET['death_id'];

$stmt = $conn->prepare("
    SELECT 
        d.deceased_name,
        d.date_of_death,
        d.deadline,
        du.full_name AS deceased_member_name,
        au.full_name AS affected_member_name
    FROM deaths d
    LEFT JOIN users du ON d.deceased_user_id = du.id
    LEFT JOIN users au ON d.affected_user_id = au.id
    WHERE d.id = ?
");
$stmt->bind_param("i", $death_id);
$stmt->execute();
$result = $stmt->get_result();
$death = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Death Details</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .death-details-card {
            max-width: 600px;
            margin: 20px auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .death-info p,
        .affected-member p,
        .contribution-message p {
            margin: 10px 0;
        }

        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<h2 style="text-align: center;">Death Details</h2>

<?php if ($death): ?>
    <div class="death-details-card">

        <div class="death-info">
            <p><strong>Deceased Name:</strong>
                <?php 
                    if (!empty($death['deceased_member_name'])) {
                        echo htmlspecialchars($death['deceased_member_name']) . " (Registered Member)";
                    } else {
                        echo htmlspecialchars($death['deceased_name']) . " (Not Registered)";
                    }
                ?>
            </p>

            <p><strong>Date of Death:</strong> <?= htmlspecialchars($death['date_of_death']); ?></p>
            <p><strong>Contribution Deadline:</strong> <?= htmlspecialchars($death['deadline']); ?></p>
        </div>

        <div class="affected-member">
            <p><strong>Affected Member:</strong>
                <?= !empty($death['affected_member_name']) ? htmlspecialchars($death['affected_member_name']) : "Unknown"; ?>
            </p>
        </div>

        <div class="contribution-message">
            <p style="font-style: italic; color: #333;">
                Let us come together as a community to support the affected family during this difficult time.
                Your contribution can make a meaningful difference. Please contribute before the deadline:
                <strong><?= htmlspecialchars($death['deadline']); ?></strong>.
            </p>
        </div>

        <div class="contribute-action" style="margin-top: 1em;">
            <a href="contribute.php?death_id=<?= $death_id; ?>" class="btn">Contribute Now</a>
        </div>
    </div>
<?php else: ?>
    <p style="text-align: center;">No information found for this record.</p>
<?php endif; ?>
<?php include 'footer.php'; ?>
</body>
</html>
