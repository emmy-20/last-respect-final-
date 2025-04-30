<?php
session_start();
include 'db_connect.php'; // Connect to the database
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Last Respect FundDrive Platform</title>
    <link rel="stylesheet" href="styles.css">
    <style>
    .dashboard-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 20px;
        justify-content: center;
    }

    .dashboard-box {
        background-color: #f2f2f2;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        width: 200px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }

    .dashboard-box:hover {
        transform: translateY(-5px);
        background-color: #e9f5ff;
    }

    .dashboard-box a {
        text-decoration: none;
        color: #333;
        font-weight: bold;
    }

    .notices {
        margin-top: 20px;
        /* background-color: #fff8f0; */
        padding: 15px;
        border-radius: 10px;
    }

    .notices h3 {
        text-align: center;
        margin-bottom: 10px;
    }

    .notices-grid {
        display: grid;
        /* grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); */
        gap: 10px;
    }

    .notice {
        background-color: #fff;
        border-left: 5px solid #ff6f61;
        padding: 10px 15px;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100px;
    }

    .notice strong {
        font-size: 1.05em;
        color: #333;
    }

    .notice em {
        color: #777;
    }

    .btn {
        margin: 10px auto;;
        align: flex-start;
        background-color: #007bff;
        color: white;
        padding: 3px 12px;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.9em;
        top: -5px; /* moves it slightly up */
    }

    .btn:hover {
        background-color: #0056b3;
    }

    .echo {
        color: red;
        font-weight: bold;
    }

    header, .intro {
        text-align: center;
    }
</style>

</head>
<body>

<?php include 'navbar.php'; ?>

<main>
<?php if (!isset($_SESSION['user_id'])): ?>
    <section class="intro">
        <h1>Welcome to the Last Respect FundDrive Platform</h1>
        <p class="lead">Join us in supporting each other during moments of loss. Log in to contribute, view notices, or report a bereavement.</p>
        <a href="login.php" class="btn">Login to Continue</a>
    </section>
<?php else: ?>
        <?php
    $full_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : "Member";
?>
<p>Welcome, <strong><?= $full_name; ?></strong></p>

        <!-- Death Notices -->
        <section class="notices">
            <h3>Recent Death Notices</h3>
            <div class="notices-grid">
            <?php
                $stmt = $conn->prepare("
                    SELECT 
                        d.id, 
                        d.deceased_name, 
                        d.date_of_death, 
                        du.full_name AS deceased_member_name
                    FROM deaths d
                    LEFT JOIN users du ON d.deceased_user_id = du.id
                    WHERE d.deadline >= CURDATE()
                    ORDER BY d.date_of_death DESC
                    LIMIT 5
                ");

                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $deceased_display = !empty($row['deceased_member_name']) 
                            ? htmlspecialchars($row['deceased_member_name']) . " (Registered)"
                            : htmlspecialchars($row['deceased_name']);
            ?>
                <div class="notice">
                    <p><strong><?= $deceased_display; ?></strong> <em>Date of Death:</em> <?= htmlspecialchars($row['date_of_death']); ?></p>
                    <p> Click the button to view more details</p>
                    <p></p>
                    <a href="death_details.php?death_id=<?= $row['id']; ?>" class="btn">Details & Contribute</a>
                </div>
            <?php
                    endwhile;
                else:
                    echo "<p>No recent notices.</p>";
                endif;
            ?>
            </div>
        </section>

        <!-- Member Dashboard Links -->
        <section class="dashboard">
            <h2>Member Dashboard</h2>
            <div class="dashboard-container">
                <div class="dashboard-box"><a href="contribute.php">Contribute for Deceased</a></div>
                <div class="dashboard-box"><a href="my_contributions.php">View My Contributions</a></div>
                <div class="dashboard-box"><a href="member_report.php">View total amount contributed</a></div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
