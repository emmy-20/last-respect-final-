<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation</title>
    <link rel="stylesheet" href="styles.css">
    <style>
       
    </style>
</head>
<body>

<nav class="navbar">
    <ul class="nav-list">
        <img class="logo" src="images/logo.jpg" alt="Logo">

        <li><a href="index.php">Home</a></li>
        <li><a href="logout.php">Logout</a></li>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>

    <?php if (isset($_SESSION['full_name'])): ?>
        <?php
        $defaultPhoto = 'images/default_user.png'; // Make sure this file exists
        $profilePhoto = (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo']))
            ? $_SESSION['profile_photo']
            : $defaultPhoto;
        ?>
        <div class="user-info">
            <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Profile Photo" class="user-photo">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
        </div>
    <?php endif; ?>
</nav>

</body>
</html>
