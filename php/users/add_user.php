<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/User.php';

// Check for admin access
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error'] = "You do not have permission to access this page.";
    header("Location: ../login.php");
    exit();
}

$title = "Add New User";
$mode = 'add';
$user_id = 0;

// Initialize empty user data
$university_id = '';
$first_name = '';
$last_name = '';
$email = '';
$role = '';
$department = '';
$phone = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize data
    $data = [
        'university_id' => sanitize($_POST['university_id']),
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'email' => sanitize($_POST['email']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'role' => sanitize($_POST['role']),
        'department' => sanitize($_POST['department']),
        'phone' => isset($_POST['phone']) ? sanitize($_POST['phone']) : ''
    ];
    
    // Create new user
    $user = new User($conn);
    $result = $user->create($data);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header("Location: users.php");
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
        
        // Keep form data for re-display
        $university_id = $data['university_id'];
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];
        $email = $data['email'];
        $role = $data['role'];
        $department = $data['department'];
        $phone = $data['phone'];
    }
}

// Include the HTML template
include '../../pages/users/user_management.html';
?>