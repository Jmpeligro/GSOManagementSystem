<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/User.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: php/login.php");
    exit();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = $user_id > 0 ? 'edit' : 'add';
$title = $mode === 'edit' ? 'Edit User' : 'Add New User';

$user = new User($conn);
$university_id = '';
$first_name = '';
$last_name = '';
$email = '';
$department = '';
$phone = '';
$role = '';
$errors = [];

if ($mode === 'edit') {
    if (!$user->load($user_id)) {
        $_SESSION['error'] = "User not found.";
        header("Location: users.php");
        exit();
    }
    
    $university_id = $user->getUniversityId();
    $first_name = $user->getFirstName();
    $last_name = $user->getLastName();
    $email = $user->getEmail();
    $department = $user->getDepartment();
    $phone = $user->getPhone();
    $role = $user->getRole();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'university_id' => sanitize($_POST['university_id']),
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'email' => sanitize($_POST['email']),
        'department' => sanitize($_POST['department']),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'role' => sanitize($_POST['role'])
    ];
    
    // Handle password fields
    if ($mode === 'add' || !empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
        $data['confirm_password'] = $_POST['confirm_password'];
    }
    
    if ($mode === 'edit') {
        $result = $user->update($data);
    } else {
        $result = $user->create($data);
    }
    
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
        $department = $data['department'];
        $phone = $data['phone'];
        $role = $data['role'];
    }
}

include '../../pages/users/user_management.html';
?>