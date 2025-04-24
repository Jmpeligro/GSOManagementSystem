<?php
session_start();
require_once 'db_connection.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = $user_id > 0 ? 'edit' : 'add';
$title = $mode === 'edit' ? 'Edit User' : 'Add New User';

$university_id = '';
$first_name = '';
$last_name = '';
$email = '';
$department = '';
$phone = '';
$role = '';
$errors = [];

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
    $university_id = $user['university_id'];
    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
    $email = $user['email'];
    $department = $user['department'];
    $phone = $user['phone'];
    $role = $user['role'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $university_id = sanitize($_POST['university_id']);
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $department = sanitize($_POST['department']);
    $phone = sanitize($_POST['phone']);
    $role = sanitize($_POST['role']);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($university_id)) {
        $errors[] = "University ID is required";
    }
    
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
    } elseif (!in_array($role, ['admin', 'faculty', 'staff', 'student'])) {
        $errors[] = "Invalid role selected";
    }
 
    if ($mode === 'add') {
        if (empty($password)) {
            $errors[] = "Password is required for new users";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
    } elseif (!empty($password) && strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    $email_check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $different_id = $mode === 'edit' ? $user_id : 0;
    $email_check_stmt->bind_param("si", $email, $different_id);
    $email_check_stmt->execute();
    $email_result = $email_check_stmt->get_result();
    
    if ($email_result->num_rows > 0) {
        $errors[] = "Email already exists";
    }

    if (empty($errors)) {
        if ($mode === 'edit') {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET university_id = ?, first_name = ?, last_name = ?, email = ?, department = ?, phone = ?, role = ?, password = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->bind_param("ssssssssi", $university_id, $first_name, $last_name, $email, $department, $phone, $role, $hashed_password, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET university_id = ?, first_name = ?, last_name = ?, email = ?, department = ?, phone = ?, role = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->bind_param("sssssssi", $university_id, $first_name, $last_name, $email, $department, $phone, $role, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "User updated successfully";
                header("Location: users.php");
                exit();
            } else {
                $errors[] = "Error updating user: " . $conn->error;
            }
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (university_id, first_name, last_name, email, department, phone, role, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("ssssssss", $university_id, $first_name, $last_name, $email, $department, $phone, $role, $hashed_password);
            
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