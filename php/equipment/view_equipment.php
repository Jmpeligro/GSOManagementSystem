<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Equipment.php';

if (!isLoggedIn()) {
    header("Location: php/login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No equipment specified.";
    header("Location: ../equipment/equipment.php");
    exit();
}

$equipment_id = (int)$_GET['id'];
$equipment = new Equipment($conn);

if (!$equipment->load($equipment_id)) {
    $_SESSION['error'] = "Equipment not found.";
    header("Location: ../equipment/equipment.php");
    exit();
}

// Get category name
$category_id = $equipment->getCategoryId();
$category_name = '';

if ($category_id) {
    $category_sql = "SELECT name FROM categories WHERE category_id = ?";
    $stmt = $conn->prepare($category_sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $category_result = $stmt->get_result();
        
        if ($category_result && $category_result->num_rows > 0) {
            $category_row = $category_result->fetch_assoc();
            $category_name = $category_row['name'];
        }
    }
}

// Get borrowing history - use a variable for the parameter
$history_limit = 10;
$borrowing_history = $equipment->getBorrowingHistory($history_limit);

// Get current borrower if equipment is borrowed
$current_borrower = $equipment->getStatus() == 'borrowed' ? $equipment->getCurrentBorrower() : null;

// Create an equipment data array for the view
$equipment_data = [
    'equipment_id' => $equipment->getId(),
    'name' => $equipment->getName(),
    'description' => $equipment->getDescription(),
    'equipment_code' => $equipment->getEquipmentCode(),
    'category_id' => $equipment->getCategoryId(),
    'category_name' => $category_name,
    'condition_status' => $equipment->getConditionStatus(),
    'status' => $equipment->getStatus(),
    'acquisition_date' => $equipment->getAcquisitionDate(),
    'notes' => $equipment->getNotes(),
    'quantity' => $equipment->getQuantity(),
    'available_quantity' => $equipment->getAvailableQuantity(),
    'created_at' => $equipment->getCreatedAt(),
    'updated_at' => $equipment->getUpdatedAt()
];

include '../../pages/equipment/view_equipment.html';
?>