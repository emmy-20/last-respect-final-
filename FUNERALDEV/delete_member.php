<?php
session_start();
require_once 'db_connect.php';

// Only allow access for admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Validate member ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "No valid member ID specified.";
    exit;
}

$id = intval($_GET['id']);

// Prevent admin from deleting their own account
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
    echo "You cannot delete your own account.";
    exit;
}

// Delete user from database
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: manage_members.php?deleted=1");
    exit;
} else {
    echo "Error deleting member: " . $stmt->error;
    $stmt->close();
}
?>
