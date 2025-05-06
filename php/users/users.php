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
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'active';

$filters = [
    'role' => $role_filter,
    'search' => $search_query,
    'status' => $status_filter === 'all' ? null : $status_filter
];

$users = User::getAll($conn, $filters);

// Handle change status actions
if (isset($_GET['action']) && isset($_GET['id']) && isAdmin()) {
    $user_id = (int)$_GET['id'];
    $action = sanitize($_GET['action']);
    
    if ($user_id != $_SESSION['user_id'] || $action === 'activate') {
        $user = new User($conn);
        if ($user->load($user_id)) {
            $result = null;
            
            switch ($action) {
                case 'activate':
                    $result = $user->activate();
                    break;
                case 'deactivate':
                    $result = $user->deactivate();
                    break;
                case 'archive':
                    $result = $user->archive();
                    break;
                default:
                    $_SESSION['error'] = "Invalid action.";
                    break;
            }
            
            if ($result && $result['success']) {
                $_SESSION['success'] = $result['message'];
            } else if ($result) {
                $_SESSION['error'] = $result['message'];
            }
        } else {
            $_SESSION['error'] = "User not found.";
        }
    } else {
        $_SESSION['error'] = "You cannot change the status of your own account.";
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