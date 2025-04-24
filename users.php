<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build the SQL query with filters using prepared statements
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

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

$sql .= " ORDER BY last_name ASC, first_name ASC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Process delete user if requested
if (isset($_GET['delete']) && isAdmin()) {
    $delete_id = (int)$_GET['delete'];
    
    // Don't allow deleting yourself
    if ($delete_id != $_SESSION['user_id']) {
        // Check if user has active borrowings
        $check_sql = "SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'active'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $delete_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $active_count = $check_result->fetch_assoc()['count'];
        
        if ($active_count == 0) {
            // Safe to delete the user
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
    
    // Redirect to refresh the page
    header("Location: users.php");
    exit();
}

include 'users.html';
?>