<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Equipment.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// Handle equipment deletion
if (isset($_POST['delete_equipment']) && isAdmin()) {
    $equipment_id = (int)$_POST['equipment_id'];
    
    $equipment = new Equipment($conn);
    if ($equipment->load($equipment_id)) {
        $result = $equipment->delete();
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = "Equipment not found.";
    }
    
    header("Location: equipment.php");
    exit();
}

// Handle status update
if (isset($_POST['update_status']) && isAdmin()) {
    $equipment_id = (int)$_POST['equipment_id'];
    $new_status = sanitize($_POST['new_status']);
    
    $equipment = new Equipment($conn);
    if ($equipment->load($equipment_id)) {
        $result = $equipment->updateStatus($new_status);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = "Equipment not found.";
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Handle quantity update
if (isset($_POST['update_quantity']) && isAdmin()) {
    $equipment_id = (int)$_POST['equipment_id'];
    $new_quantity = (int)$_POST['new_quantity'];
    
    $equipment = new Equipment($conn);
    if ($equipment->load($equipment_id)) {
        $result = $equipment->updateQuantity($new_quantity);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = "Equipment not found.";
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Set up filters for equipment list
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$filters = [
    'status' => $status_filter,
    'category' => $category_filter,
    'search' => $search_query
];

// Get equipment list
$result = Equipment::getAll($conn, $filters);

// Get categories for filter dropdown
$sql_categories = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($sql_categories);

include '../../pages/equipment/equipment.html';
?>