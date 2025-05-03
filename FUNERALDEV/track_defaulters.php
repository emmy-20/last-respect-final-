<?php
session_start();
require_once 'db_connect.php';

// Only admin should access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Defaulters List</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 8px 12px;
            border: 1px solid #ccc;
        }
        th {
            background-color: #f8f8f8;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<h2>Defaulters Tracking</h2>

<?php
// Fetch all approved deaths with valid deadline
$deaths_result = $conn->query("
    SELECT id, deceased_name, deadline 
    FROM deaths 
    WHERE status = 'approved' AND deadline >= CURDATE()
    ORDER BY deadline ASC
");

while ($death = $deaths_result->fetch_assoc()):
    $death_id = $death['id'];
    $deceased_name = htmlspecialchars($death['deceased_name']);
    $deadline = htmlspecialchars($death['deadline']);

    echo "<h3>Defaulters for: <u>$deceased_name</u> (Deadline: $deadline)</h3>";

    // Get members who have NOT contributed to this death using LEFT JOIN
    $query = "
        SELECT u.full_name, u.id_number, u.phone_number
        FROM users u
        LEFT JOIN (
            SELECT user_id FROM contributions WHERE death_id = ?
        ) c ON u.id = c.user_id
        WHERE u.role = 'member' AND c.user_id IS NULL
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $death_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Member Name</th>
                    <th>ID Number</th>
                    <th>Phone Number</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']); ?></td>
                    <td><?= htmlspecialchars($row['id_number']); ?></td>
                    <td><?= htmlspecialchars($row['phone_number']); ?></td>
                    <td><span style="color:red;">Not Contributed</span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color:green;">All members have contributed </p>
    <?php endif;

    $stmt->close();
endwhile;

$conn->close();
?>

</body>
</html>
