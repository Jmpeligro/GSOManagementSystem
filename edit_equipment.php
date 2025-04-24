<?php
session_start();
require_once 'db_connection.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No equipment specified.";
    header("Location: equipment.php");
    exit();
}

$equipment_id = (int)$_GET['id'];
$errors = [];
$success_message = '';

$sql_categories = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($sql_categories);

$sql_borrowed = "SELECT COUNT(*) as is_borrowed FROM borrowings 
                WHERE equipment_id = ? AND status = 'active'";
$stmt_borrowed = $conn->prepare($sql_borrowed);
$stmt_borrowed->bind_param("i", $equipment_id);
$stmt_borrowed->execute();
$borrowed_result = $stmt_borrowed->get_result();
$is_borrowed = $borrowed_result->fetch_assoc()['is_borrowed'] > 0;

$sql = "SELECT * FROM equipment WHERE equipment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Equipment not found.";
    header("Location: equipment.php");
    exit();
}

$equipment = $result->fetch_assoc();

$name = $equipment['name'];
$description = $equipment['description'] ?? '';
$equipment_code = $equipment['equipment_code'];
$category_id = $equipment['category_id'];
$condition_status = $equipment['condition_status'];
$status = $equipment['status'];
if ($is_borrowed) {
    $status = 'borrowed'; 
}
$acquisition_date = $equipment['acquisition_date'];
$notes = $equipment['notes'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $equipment_code = sanitize($_POST['equipment_code']);
    $category_id = (int)$_POST['category_id'];
    $condition_status = sanitize($_POST['condition_status']);
    $status = sanitize($_POST['status']);
    $acquisition_date = sanitize($_POST['acquisition_date']);
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($name)) {
        $errors[] = "Equipment name is required";
    }

    if (empty($equipment_code)) {
        $errors[] = "Equipment code is required";
    } else {
        $check_code_sql = "SELECT equipment_id FROM equipment 
                          WHERE equipment_code = ? AND equipment_id != ?";
        $check_stmt = $conn->prepare($check_code_sql);
        $check_stmt->bind_param("si", $equipment_code, $equipment_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
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
    } elseif (!in_array($status, ['available', 'maintenance', 'retired', 'borrowed'])) {
        $errors[] = "Invalid equipment status";
    }

    if ($is_borrowed && $status != 'borrowed') {
        $errors[] = "Cannot change status of equipment that is currently borrowed.";
        $status = 'borrowed';
    }

    if (empty($acquisition_date)) {
        $errors[] = "Acquisition date is required";
    } else {
        $date_parts = explode('-', $acquisition_date);
        if (count($date_parts) !== 3 || !checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
            $errors[] = "Invalid date format. Please use YYYY-MM-DD format.";
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE equipment 
                SET name = ?, 
                    description = ?, 
                    equipment_code = ?, 
                    category_id = ?, 
                    condition_status = ?, 
                    status = ?, 
                    acquisition_date = ?,
                    notes = ?,
                    updated_at = NOW()
                WHERE equipment_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", 
            $name, 
            $description, 
            $equipment_code, 
            $category_id, 
            $condition_status, 
            $status, 
            $acquisition_date,
            $notes,
            $equipment_id
        );

        if ($stmt->execute()) {
            $success_message = "Equipment updated successfully!";

            header("refresh:2;url=view_equipment.php?id=$equipment_id");
        } else {
            $errors[] = "Error: " . $stmt->error;
        }
    }
}

include 'edit_equipment.html';
?>