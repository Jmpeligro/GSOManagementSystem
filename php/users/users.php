<?php
session_start();
require_once '../db_connection.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: php/login.php");
    exit();
}

$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$show_archived = isset($_GET['show_archived']) ? sanitize($_GET['show_archived']) : '0';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if ($show_archived === '0') {
    $sql .= " AND archived = FALSE";
} elseif ($show_archived === '1') {
    $sql .= " AND archived = TRUE";
}

if (!empty($role_filter)) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR department LIKE ? OR university_id LIKE ?)";
    $search_param = "%" . $search_query . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sssss";
}

$sql .= " ORDER BY archived ASC, last_name ASC, first_name ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL prepare failed: " . $conn->error . " | Query: " . $sql);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    die("SQL execution failed: " . $stmt->error);
}

if (isset($_GET['archive']) && isAdmin()) {
    $archive_id = (int)$_GET['archive'];
    
    if ($archive_id != $_SESSION['user_id']) {
        $check_sql = "SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'active'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $archive_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $active_count = $check_result->fetch_assoc()['count'];
        
        if ($active_count == 0) {
            $archive_sql = "UPDATE users SET archived = TRUE, archived_at = NOW() WHERE user_id = ?";
            $archive_stmt = $conn->prepare($archive_sql);
            $archive_stmt->bind_param("i", $archive_id);
            
            if ($archive_stmt->execute()) {
                $_SESSION['success'] = "User archived successfully.";
            } else {
                $_SESSION['error'] = "Error archiving user.";
            }
        } else {
            $_SESSION['error'] = "Cannot archive user with active borrowings.";
        }
    } else {
        $_SESSION['error'] = "You cannot archive your own account.";
    }

    header("Location: users.php");
    exit();
}

if (isset($_GET['restore']) && isAdmin()) {
    $restore_id = (int)$_GET['restore'];
    
    $restore_sql = "UPDATE users SET archived = FALSE, archived_at = NULL WHERE user_id = ?";
    $restore_stmt = $conn->prepare($restore_sql);
    $restore_stmt->bind_param("i", $restore_id);
    
    if ($restore_stmt->execute()) {
        $_SESSION['success'] = "User restored successfully.";
    } else {
        $_SESSION['error'] = "Error restoring user.";
    }

    header("Location: users.php");
    exit();
}

if (isset($_GET['delete']) && isAdmin()) {
    $delete_id = (int)$_GET['delete'];
    
    if ($delete_id != $_SESSION['user_id']) {
        $check_sql = "SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'active'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $delete_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $active_count = $check_result->fetch_assoc()['count'];
        
        if ($active_count == 0) {
            $delete_sql = "DELETE FROM users WHERE user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $delete_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "User deleted successfully.";
            } else {
                $_SESSION['error'] = "Error deleting user.";
            }
        } else {
            $_SESSION['error'] = "Cannot delete user with active borrowings.";
        }
    } else {
        $_SESSION['error'] = "You cannot delete your own account.";
    }

    header("Location: users.php");
    exit();
}

include '../../pages/users/users.html';
?>