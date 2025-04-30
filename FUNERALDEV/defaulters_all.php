<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

$deaths = $conn->query("SELECT id, deceased_name FROM deaths ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Defaulters - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // JavaScript to toggle all checkboxes
        function toggleAll(source) {
            let checkboxes = document.querySelectorAll('.member-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }
    </script>
</head>
<body class="p-4">

<h2>All Defaulters (Grouped by Deceased)</h2>
<a href="admin_contributions.php">← Back to Contributions</a><br><br>

<?php while ($death = $deaths->fetch_assoc()):
    $death_id = $death['id'];
    $deceased_name = htmlspecialchars($death['deceased_name']);

    $defaulters = $conn->prepare("
        SELECT u.id, u.full_name, u.phone_number, u.email
        FROM users u
        JOIN contributions c ON u.id = c.user_id
        WHERE c.death_id = ? AND c.status = 'pending'
    ");
    $defaulters->bind_param("i", $death_id);
    $defaulters->execute();
    $result = $defaulters->get_result();

    if ($result->num_rows > 0):
?>
    <hr>
    <h4><?php echo $deceased_name; ?></h4>

    <form method="POST" action="send_bulk_email.php">
        <input type="hidden" name="deceased_name" value="<?php echo $deceased_name; ?>">

        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped table-hover align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="member-checkbox" name="selected_members[]" value="<?php echo htmlspecialchars($row['email']) . '|' . htmlspecialchars($row['full_name']); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="text-end mb-5">
            <button type="submit" class="btn btn-primary">Notify Selected Members</button>
        </div>
    </form>

<?php 
    endif;
endwhile;
?>

</body>
</html>
