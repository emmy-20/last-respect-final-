<?php
session_start();

// Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Include DB connection
include 'db_connect.php';

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $id_number = trim($_POST['id_number']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $role = $_POST['role'];
    $gender = $_POST['gender'];
    $area = trim($_POST['area']);
    $date_of_birth = $_POST['date_of_birth'];

    // Insert into users table
    $sql = "INSERT INTO users (full_name, id_number, email, phone_number, role, gender, area, date_of_birth, status, password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NULL)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssssss", $full_name, $id_number, $email, $phone_number, $role, $gender, $area, $date_of_birth);
        if ($stmt->execute()) {
            $success = "Member registered successfully! They will set their own password at first login.";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Error preparing statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css">
    <title>Register Member</title>
</head>
<body>
<?php include 'navbar.php'; ?>
    <h2>Register New Member</h2>
    
    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php elseif ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Full Name:</label><br>
        <input type="text" name="full_name" required><br><br>

        <label>ID Number:</label><br>
        <input type="text" name="id_number" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Phone Number:</label><br>
        <input type="text" name="phone_number"><br><br>

        <label>Gender:</label><br>
        <select name="gender" required>
            <option value="">-- Select Gender --</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
        </select><br><br>

        <label>Area:</label><br>
        <input type="text" name="area" required><br><br>

        <label>Date of Birth:</label><br>
        <input type="date" name="date_of_birth" required><br><br>

        <label>Role:</label><br>
        <select name="role" required>
            <option value="member">Member</option>
            <option value="admin">Admin</option>
        </select><br><br>

        <button type="submit">Register Member</button>
    </form>
</body>
</html>
