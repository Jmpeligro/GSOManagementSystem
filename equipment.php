<?php
session_start();
require_once 'db_connection.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['delete_equipment']) && isAdmin()) {
    $equipment_id = (int)$_POST['equipment_id'];
   
    $check_sql = "SELECT COUNT(*) as count FROM borrowings 
                  WHERE equipment_id = ? AND status = 'active'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $equipment_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $is_borrowed = $check_result->fetch_assoc()['count'] > 0;
    
    if ($is_borrowed) {
        $_SESSION['error'] = "Cannot delete equipment that is currently borrowed.";
    } else {
        $delete_sql = "DELETE FROM equipment WHERE equipment_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $equipment_id);
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Equipment deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting equipment: " . $delete_stmt->error;
        }
    }
    header("Location: equipment.php");
    exit();
}

if (isset($_POST['update_status']) && isAdmin()) {
    $equipment_id = (int)$_POST['equipment_id'];
    $new_status = sanitize($_POST['new_status']);
    $allowed_statuses = ['available', 'maintenance', 'retired'];
    
    if (empty($new_status)) {
        $_SESSION['error'] = "No status selected.";
    } elseif (!in_array($new_status, $allowed_statuses)) {
        $_SESSION['error'] = "Invalid status selected.";
    } else {
        $check_borrow_sql = "SELECT COUNT(*) as count FROM borrowings 
                            WHERE equipment_id = ? AND status = 'active'";
        $check_stmt = $conn->prepare($check_borrow_sql);
        $check_stmt->bind_param("i", $equipment_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $is_borrowed = $check_result->fetch_assoc()['count'] > 0;
        
        if ($is_borrowed) {
            $_SESSION['error'] = "Cannot update status of equipment that is currently borrowed.";
        } else {
            $update_sql = "UPDATE equipment 
                          SET status = ?, 
                              updated_at = NOW() 
                          WHERE equipment_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_status, $equipment_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Equipment status updated to " . ucfirst($new_status);
            } else {
                $_SESSION['error'] = "Error updating status: " . $update_stmt->error;
            }
        }
    }
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$sql = "SELECT 
    e.*, 
    c.name as category_name,
    CASE WHEN b.equipment_id IS NOT NULL AND b.status = 'active' THEN 'borrowed' ELSE e.status END as display_status
    FROM equipment e
    JOIN categories c ON e.category_id = c.category_id
    LEFT JOIN borrowings b ON e.equipment_id = b.equipment_id AND b.status = 'active'
    WHERE 1=1";

if (!empty($status_filter)) {
    if ($status_filter == 'borrowed') {
        $sql .= " AND b.equipment_id IS NOT NULL AND b.status = 'active'";
    } else {
        $sql .= " AND e.status = '$status_filter' AND (b.equipment_id IS NULL OR b.status != 'active')";
    }
}

if ($category_filter > 0) {
    $sql .= " AND e.category_id = $category_filter";
}

if (!empty($search_query)) {
    $search_term = '%' . $search_query . '%';
    $sql .= " AND (e.name LIKE ? OR e.equipment_code LIKE ? OR e.description LIKE ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql .= " ORDER BY e.name ASC";
    $result = $conn->query($sql);
}

$sql_categories = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($sql_categories);

include 'equipment.html';
?>