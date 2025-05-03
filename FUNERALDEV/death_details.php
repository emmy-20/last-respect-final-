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
        d.relationship,
        d.area,
        d.description,
        du.full_name AS deceased_member_name,
        au.full_name AS affected_member_name,
        ru.full_name AS reporter_name
    FROM deaths d
    LEFT JOIN users du ON d.deceased_user_id = du.id
    LEFT JOIN users au ON d.affected_user_id = au.id
    LEFT JOIN users ru ON d.reported_by = ru.id
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
            max-width: 700px;
            margin: 20px auto;
            background: #f9f9f9;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            color: #333;
            line-height: 1.6;
        }

        .death-details-card h3 {
            text-align: center;
            margin-bottom: 20px;
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
        <h3>Deceased Information</h3>
        <div class="death-info">
            <p><strong>Deceased Name:</strong>
                <?= !empty($death['deceased_member_name']) 
                    ? htmlspecialchars($death['deceased_member_name']) . " (Registered Member)"
                    : htmlspecialchars($death['deceased_name']) . " (Not Registered)"; ?>
            </p>
            <p><strong>Date of Death:</strong> <?= htmlspecialchars($death['date_of_death']); ?></p>
            <p><strong>Contribution Deadline:</strong> <?= htmlspecialchars($death['deadline']); ?></p>
            <p><strong>Area:</strong> <?= htmlspecialchars($death['area']); ?></p>
            <?php if (!empty($death['relationship'])): ?>
                <p><strong>Relationship to Affected Member:</strong> <?= htmlspecialchars($death['relationship']); ?></p>
            <?php endif; ?>
            <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($death['description'])); ?></p>
        </div>
        <div class="affected-member">
            <p><strong>Affected Member:</strong>
                <?= !empty($death['affected_member_name']) ? htmlspecialchars($death['affected_member_name']) : "Unknown"; ?>
            </p>
        </div>
        <div class="reporter">
            <p><strong>Reported By:</strong>
                <?= !empty($death['reporter_name']) ? htmlspecialchars($death['reporter_name']) : "Unknown"; ?>
            </p>
        </div>

        <div class="contribution-message">
            <p style="font-style: italic;">
            <p style="font-style: italic; color: #333;">
    During times of grief, our strength as a community lies in standing together.
     We kindly appeal to your compassion and solidarity to support the affected family during this difficult moment.
      Every contribution, brings comfort and eases their burden. Let us extend our hand of support and uphold the spirit of unity that binds us all. 
    Please make your contribution before the burial date : 

                <strong><?= htmlspecialchars($death['deadline']); ?></strong>.
            </p>
        </div>

        <div class="contribute-action" style="margin-top: 1em; text-align: center;">
            <a href="contribute.php?death_id=<?= $death_id; ?>" class="btn">Contribute Now</a>
        </div>
    </div>
<?php else: ?>
    <p style="text-align: center;">No information found for this record.</p>
<?php endif; ?>

<?php include 'footer.php'; ?>
</body>
</html>
