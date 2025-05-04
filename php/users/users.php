<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/User.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: php/login.php");
    exit();
}

$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$show_archived = isset($_GET['show_archived']) ? sanitize($_GET['show_archived']) : '0';

$filters = [
    'role' => $role_filter,
    'search' => $search_query,
    'archived' => $show_archived === '1'
];

$users = User::getAll($conn, $filters);

// Handle archive action
if (isset($_GET['archive']) && isAdmin()) {
    $archive_id = (int)$_GET['archive'];
    
    if ($archive_id != $_SESSION['user_id']) {
        $user = new User($conn);
        if ($user->load($archive_id)) {
            $result = $user->archive();
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        } else {
            $_SESSION['error'] = "User not found.";
        }
    } else {
        $_SESSION['error'] = "You cannot archive your own account.";
    }

    header("Location: users.php");
    exit();
}

// Handle restore action
if (isset($_GET['restore']) && isAdmin()) {
    $restore_id = (int)$_GET['restore'];
    
    $user = new User($conn);
    if ($user->load($restore_id)) {
        $result = $user->restore();
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = "User not found.";
    }

    header("Location: users.php");
    exit();
}

// Handle delete action
if (isset($_GET['delete']) && isAdmin()) {
    $delete_id = (int)$_GET['delete'];
    
    if ($delete_id != $_SESSION['user_id']) {
        $user = new User($conn);
        if ($user->load($delete_id)) {
            $result = $user->delete();
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        } else {
            $_SESSION['error'] = "User not found.";
        }
    } else {
        $_SESSION['error'] = "You cannot delete your own account.";
    }

    header("Location: users.php");
    exit();
}

include '../../pages/users/users.html';
?>