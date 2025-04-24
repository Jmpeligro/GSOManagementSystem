<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    echo "<p>Welcome, $user_name!</p>";
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>