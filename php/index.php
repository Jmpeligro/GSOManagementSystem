<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    echo "<p>Welcome, $user_name!</p>";

    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        // Admin users go to dashboard
        header("Location: dashboard.php");
    } else {
        header("Location: ../php/equipment/equipment.php");
    }
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>