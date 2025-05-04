<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Equipment.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: php/login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No equipment specified.";
    header("Location: equipment.php");
    exit();
}

$equipment_id = (int)$_GET['id'];
$equipment = new Equipment($conn);

if (!$equipment->load($equipment_id)) {
    $_SESSION['error'] = "Equipment not found.";
    header("Location: equipment.php");
    exit();
}

$errors = [];
$success_message = '';

// Check if equipment is currently borrowed
$is_borrowed = $equipment->isBorrowed();
$status = $equipment->getStatus();
if ($is_borrowed) {
    $status = 'borrowed';
}

// Get categories for dropdown
$sql_categories = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($sql_categories);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prepare equipment data from form submission
    $equipment_data = [
        'name' => sanitize($_POST['name']),
        'description' => sanitize($_POST['description']),
        'equipment_code' => sanitize($_POST['equipment_code']),
        'category_id' => (int)$_POST['category_id'],
        'condition_status' => sanitize($_POST['condition_status']),
        'status' => sanitize($_POST['status']),
        'acquisition_date' => sanitize($_POST['acquisition_date']),
        'quantity' => (int)$_POST['quantity'],
        'notes' => sanitize($_POST['notes'] ?? '')
    ];
    
    // Validate date format
    if (!empty($equipment_data['acquisition_date'])) {
        $date_parts = explode('-', $equipment_data['acquisition_date']);
        if (count($date_parts) !== 3 || !checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
            $errors[] = "Invalid date format. Please use YYYY-MM-DD format.";
        }
    }
    
    // Force borrowed status if equipment is currently borrowed
    if ($is_borrowed && $equipment_data['status'] != 'borrowed') {
        $errors[] = "Cannot change status of equipment that is currently borrowed.";
        $equipment_data['status'] = 'borrowed';
    }
    
    // If no errors from custom validation, update equipment
    if (empty($errors)) {
        $result = $equipment->update($equipment_data);
        
        if ($result['success']) {
            $success_message = $result['message'];
            header("refresh:2;url=view_equipment.php?id=$equipment_id");
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Extract variables for the view
$name = $equipment->getName();
$description = $equipment->getDescription();
$equipment_code = $equipment->getEquipmentCode();
$category_id = $equipment->getCategoryId();
$condition_status = $equipment->getConditionStatus();
$acquisition_date = $equipment->getAcquisitionDate();
$notes = $equipment->getNotes();
$quantity = $equipment->getQuantity();

include '../../pages/equipment/edit_equipment.html';
?>