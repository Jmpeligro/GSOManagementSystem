<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Equipment.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$errors = [];
$success_message = '';

// Initialize form variables to prevent undefined variable warnings
$name = '';
$description = '';
$equipment_code = '';
$category_id = 0;
$condition_status = '';
$status = '';
$acquisition_date = '';
$quantity = 1;
$notes = '';

// Fetch categories for form dropdown
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
    
    // Preserve form values in case of errors
    $name = $equipment_data['name'];
    $description = $equipment_data['description'];
    $equipment_code = $equipment_data['equipment_code'];
    $category_id = $equipment_data['category_id'];
    $condition_status = $equipment_data['condition_status'];
    $status = $equipment_data['status'];
    $acquisition_date = $equipment_data['acquisition_date'];
    $quantity = $equipment_data['quantity'];
    $notes = $equipment_data['notes'];
    
    // Validate date format
    if (!empty($equipment_data['acquisition_date'])) {
        $date_parts = explode('-', $equipment_data['acquisition_date']);
        if (count($date_parts) !== 3 || !checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
            $errors[] = "Invalid date format. Please use YYYY-MM-DD format.";
        }
    }
    
    // If no errors from custom validation, create equipment
    if (empty($errors)) {
        $equipment = new Equipment($conn);
        $result = $equipment->create($equipment_data);
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Reset form
            $name = '';
            $description = '';
            $equipment_code = '';
            $category_id = 0;
            $condition_status = '';
            $status = '';
            $acquisition_date = '';
            $quantity = 1;
            $notes = '';
        } else {
            $errors[] = $result['message'];
        }
    }
}

include '../../pages/equipment/equipment_management.html';
?>