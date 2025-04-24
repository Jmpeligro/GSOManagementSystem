<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = $user_id > 0 ? 'edit' : 'add';
$title = $mode === 'edit' ? 'Edit User' : 'Add New User';

$first_name = '';
$last_name = '';
$email = '';
$department = '';
$role = '';
$errors = [];

// If editing, fetch user data
if ($mode === 'edit') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "User not found.";
        header("Location: users.php");
        exit();
    }
    
    $user = $result->fetch_assoc();
    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
    $email = $user['email'];
    $department = $user['department'];
    $role = $user['role'];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $department = sanitize($_POST['department']);
    $role = sanitize($_POST['role']);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate inputs
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($department)) {
        $errors[] = "Department is required";
    }
    
    if (empty($role)) {
        $errors[] = "Role is required";
    }
    
    // Check email uniqueness (excluding current user if editing)
    $email_check_sql = "SELECT user_id FROM users WHERE email = ?";
    if ($mode === 'edit') {
        $email_check_sql .= " AND user_id != ?";
    }
    
    $email_check_stmt = $conn->prepare($email_check_sql);
    
    if ($mode === 'edit') {
        $email_check_stmt->bind_param("si", $email, $user_id);
    } else {
        $email_check_stmt->bind_param("s", $email);
    }
    
    $email_check_stmt->execute();
    $email_result = $email_check_stmt->get_result();
    
    if ($email_result->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    
    // If adding a new user, password is required
    if ($mode === 'add' && empty($password)) {
        $errors[] = "Password is required for new users";
    }
    
    // If no errors, proceed with database operation
    if (empty($errors)) {
        if ($mode === 'edit') {
            // Update existing user
            if (!empty($password)) {
                // If password is provided, update it as well
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, department = ?, role = ?, password = ? WHERE user_id = ?");
                $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $department, $role, $hashed_password, $user_id);
            } else {
                // Otherwise, update everything except password
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, department = ?, role = ? WHERE user_id = ?");
                $stmt->bind_param("sssssi", $first_name, $last_name, $email, $department, $role, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "User updated successfully";
                header("Location: users.php");
                exit();
            } else {
                $errors[] = "Error updating user: " . $conn->error;
            }
        } else {
            // Add new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, department, role, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $first_name, $last_name, $email, $department, $role, $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "User added successfully";
                header("Location: users.php");
                exit();
            } else {
                $errors[] = "Error adding user: " . $conn->error;
            }
        }
    }
}

include 'user_management.html';

?>