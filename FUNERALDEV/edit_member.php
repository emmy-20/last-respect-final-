<?php
session_start();
require_once 'db_connect.php';

// Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Validate ID
if (!isset($_GET['id'])) {
    echo "Invalid member ID.";
    exit;
}

$id = $_GET['id'];
$success = $error = '';

// Fetch member
$stmt = $conn->prepare("SELECT full_name, id_number, email, phone_number, role, gender, area, date_of_birth FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();
$stmt->close();

if (!$member) {
    echo "Member not found.";
    exit;
}

// Update logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name']);
    $id_number = trim($_POST['id_number']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $role = $_POST['role'];
    $gender = $_POST['gender'];
    $area = trim($_POST['area']);
    $dob = $_POST['date_of_birth'];

    $update = $conn->prepare("UPDATE users SET full_name = ?, id_number = ?, email = ?, phone_number = ?, role = ?, gender = ?, area = ?, date_of_birth = ? WHERE id = ?");
    $update->bind_param("ssssssssi", $full_name, $id_number, $email, $phone_number, $role, $gender, $area, $dob, $id);

    if ($update->execute()) {
        $success = "Member updated successfully.";
        // Refresh member data
        $member = [
            'full_name' => $full_name,
            'id_number' => $id_number,
            'email' => $email,
            'phone_number' => $phone_number,
            'role' => $role,
            'gender' => $gender,
            'area' => $area,
            'date_of_birth' => $dob
        ];
    } else {
        $error = "Error: " . $update->error;
    }
    $update->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        form { max-width: 500px; margin: auto; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<h2 class="text-center">Edit Member</h2>

<?php if ($success): ?>
    <div class="alert alert-success text-center"><?php echo $success; ?></div>
<?php elseif ($error): ?>
    <div class="alert alert-danger text-center"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" class="border p-4 shadow rounded">
    <div class="mb-3">
        <label class="form-label">Full Name:</label>
        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($member['full_name']); ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">ID Number:</label>
        <input type="text" name="id_number" class="form-control" value="<?php echo htmlspecialchars($member['id_number']); ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Email:</label>
        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($member['email']); ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Phone Number:</label>
        <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($member['phone_number']); ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Role:</label>
        <select name="role" class="form-select">
            <option value="member" <?php if ($member['role'] === 'member') echo 'selected'; ?>>Member</option>
            <option value="admin" <?php if ($member['role'] === 'admin') echo 'selected'; ?>>Admin</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Gender:</label>
        <select name="gender" class="form-select" required>
            <option value="male" <?php if ($member['gender'] === 'male') echo 'selected'; ?>>Male</option>
            <option value="female" <?php if ($member['gender'] === 'female') echo 'selected'; ?>>Female</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Area:</label>
        <input type="text" name="area" class="form-control" value="<?php echo htmlspecialchars($member['area']); ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Date of Birth:</label>
        <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($member['date_of_birth']); ?>" required>
    </div>

    <div class="text-center">
        <button type="submit" class="btn btn-primary">Update Member</button>
    </div>
</form>

</body>
</html>
