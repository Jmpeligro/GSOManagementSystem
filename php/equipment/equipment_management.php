<?php
session_start();
require_once '../db_connection.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$name = $description = $equipment_code = $acquisition_date = '';
$category_id = $condition_status = $status = '';
$quantity = 1; // Default quantity value
$errors = [];
$success_message = '';

$sql_categories = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($sql_categories);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $equipment_code = sanitize($_POST['equipment_code']);
    $category_id = (int)$_POST['category_id'];
    $condition_status = sanitize($_POST['condition_status']);
    $status = sanitize($_POST['status']);
    $acquisition_date = sanitize($_POST['acquisition_date']);
    $quantity = (int)$_POST['quantity']; // Get quantity from form

    if (empty($name)) {
        $errors[] = "Equipment name is required";
    }

    if (empty($equipment_code)) {
        $errors[] = "Equipment code is required";
    } else {
        $check_code_sql = "SELECT equipment_id FROM equipment WHERE equipment_code = '$equipment_code'";
        $check_result = $conn->query($check_code_sql);
        if ($check_result->num_rows > 0) {
            $errors[] = "Equipment code already exists. Please use a different code.";
        }
    }

    if ($category_id <= 0) {
        $errors[] = "Please select a category";
    }

    if (empty($condition_status)) {
        $errors[] = "Condition status is required";
    } elseif (!in_array($condition_status, ['new', 'good', 'fair', 'poor'])) {
        $errors[] = "Invalid condition status";
    }

    if (empty($status)) {
        $errors[] = "Equipment status is required";
    } elseif (!in_array($status, ['available', 'maintenance', 'retired'])) {
        $errors[] = "Invalid equipment status";
    }

    if (empty($acquisition_date)) {
        $errors[] = "Acquisition date is required";
    } else {
        $date_parts = explode('-', $acquisition_date);
        if (count($date_parts) !== 3 || !checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
            $errors[] = "Invalid date format. Please use YYYY-MM-DD format.";
        }
    }

    // Validate quantity
    if (!isset($quantity) || !is_numeric($quantity) || $quantity < 1) {
        $errors[] = "Quantity must be a positive number";
        $quantity = 1; // Reset to default if invalid
    }

    if (empty($errors)) {
        $sql = "INSERT INTO equipment (
            name, 
            description, 
            equipment_code, 
            category_id, 
            condition_status, 
            status, 
            acquisition_date,
            quantity,
            notes,
            created_at,
            updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, 
            ?,
            '', 
            NOW(), 
            NOW()
        )";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssis", 
            $name, 
            $description, 
            $equipment_code, 
            $category_id, 
            $condition_status, 
            $status, 
            $acquisition_date,
            $quantity
        );

        if ($stmt->execute()) {
            $success_message = "Equipment added successfully!";
            $name = $description = $equipment_code = $acquisition_date = '';
            $category_id = $condition_status = $status = '';
            $quantity = 1;
        } else {
            $errors[] = "Error: " . $stmt->error;
        }
    }
}

include '../../pages/equipment/equipment_management.html';
?>