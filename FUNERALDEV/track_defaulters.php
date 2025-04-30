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

    // Get IDs of users who have contributed for this death
    $contrib_result = $conn->prepare("SELECT user_id FROM contributions WHERE death_id = ?");
    $contrib_result->bind_param("i", $death_id);
    $contrib_result->execute();
    $contrib_result->bind_result($contributed_user_id);

    $contributors = [];
    while ($contrib_result->fetch()) {
        $contributors[] = $contributed_user_id;
    }
    $contrib_result->close();

    // Get all members who are NOT in the contributors list
    if (!empty($contributors)) {
        $placeholders = implode(',', array_fill(0, count($contributors), '?'));
        $types = str_repeat('i', count($contributors));
        $query = "SELECT id, full_name, id_number, phone_number FROM users WHERE role = 'member' AND id NOT IN ($placeholders)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$contributors);
    } else {
        // No one has contributed yet
        $stmt = $conn->prepare("SELECT id, full_name, id_number, phone_number FROM users WHERE role = 'member'");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0): ?>
        <table border="1">
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
        <p style="color:green;">All members have contributed ✅</p>
    <?php endif;

    $stmt->close();
endwhile;
?>

</body>
</html>
