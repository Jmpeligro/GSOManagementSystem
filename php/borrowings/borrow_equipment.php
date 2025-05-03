<?php
session_start();
require_once '../db_connection.php';

if (!isLoggedIn()) {
    redirectWithMessage("../login.php", "Please log in to continue.");
}

if ($_SESSION['role'] === 'admin') {
    redirectWithMessage("../dashboard.php", "Admins are not allowed to borrow equipment.");
}

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithMessage("../equipment/equipment.php", "Invalid equipment ID.");
}

$equipment_id = (int)$_GET['id'];

$equipment = fetchEquipmentDetails($conn, $equipment_id);
if (!$equipment) {
    redirectWithMessage("../equipment/equipment.php", "Equipment not found or not available for borrowing.");
}

// Check if equipment has available quantity
if ($equipment['quantity'] <= 0) {
    redirectWithMessage("../equipment/equipment.php", "This equipment is out of stock.");
}

if (!empty($_SESSION['university_id'])) {
    echo "<p>University ID: {$_SESSION['university_id']}</p>";
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $borrow_date = sanitize($_POST['borrow_date']);
    $due_date = sanitize($_POST['due_date']);
    $purpose = sanitize($_POST['purpose']);
    // Get requested quantity (default to 1 if not specified)
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Validate requested quantity
    if ($quantity <= 0) {
        $error = "Please enter a valid quantity.";
    } else if ($quantity > $equipment['quantity']) {
        $error = "Requested quantity exceeds available quantity ({$equipment['quantity']} available).";
    } else {
        $validation_error = validateDates($borrow_date, $due_date);
        if ($validation_error) {
            $error = $validation_error;
        } else {
            $user_id = $_SESSION['user_id'];
            $success = processBorrowRequest($conn, $equipment_id, $user_id, $borrow_date, $due_date, $purpose, $quantity);
            if ($success) {
                header("refresh:2;url=my_borrowings.php");
            }
        }
    }
}

include '../../pages/borrowings/borrow_equipment.html';

function redirectWithMessage($url, $message) {
    $_SESSION['error'] = $message;
    header("Location: $url");
    exit();
}

function fetchEquipmentDetails($conn, $equipment_id) {
    $sql = "SELECT e.*, c.name as category_name 
            FROM equipment e
            JOIN categories c ON e.category_id = c.category_id
            WHERE e.equipment_id = ? AND e.status = 'available'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateDates($borrow_date, $due_date) {
    $borrow_datetime = new DateTime($borrow_date);
    $due_datetime = new DateTime($due_date);
    if ($borrow_datetime > $due_datetime) {
        return "Due date must be after the borrow date.";
    }
    return null;
}

function processBorrowRequest($conn, $equipment_id, $user_id, $borrow_date, $due_date, $purpose, $quantity) {
    // Start a transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        // Insert the borrowing record
        $sql = "INSERT INTO borrowings (
                    equipment_id, 
                    user_id, 
                    borrow_date, 
                    due_date, 
                    status, 
                    notes, 
                    approval_status,
                    created_at,
                    updated_at,
                    quantity
                ) VALUES (
                    ?, ?, ?, ?, 
                    'pending', 
                    ?, 
                    'pending',
                    NOW(),
                    NOW(),
                    ?
                )";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        if (!$stmt->bind_param("iisssi", $equipment_id, $user_id, $borrow_date, $due_date, $purpose, $quantity)) {
            throw new Exception("Error binding parameters: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        // Commit the transaction
        $conn->commit();
        return "Equipment borrow request submitted successfully. An administrator will review your request shortly.";
    } catch (Exception $e) {
        // Roll back the transaction on error
        $conn->rollback();
        return "Error processing your request: " . $e->getMessage();
    }
}
?>