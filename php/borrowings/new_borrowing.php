<?php
session_start();
require_once '../db_connection.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get available equipment (quantity > 0)
$sql_equipment = "SELECT equipment_id, name, equipment_code, quantity FROM equipment 
                 WHERE status = 'available' AND quantity > 0
                 ORDER BY name";
$result_equipment = $conn->query($sql_equipment);

$sql_users = "SELECT user_id, first_name, last_name, email FROM users ORDER BY last_name, first_name";
$result_users = $conn->query($sql_users);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_id = (int)$_POST['equipment_id'];
    $user_id = (int)$_POST['user_id'];
    $borrow_date = sanitize($_POST['borrow_date']);
    $due_date = sanitize($_POST['due_date']);
    $notes = sanitize($_POST['notes']);
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    $errors = [];
    
    if ($equipment_id <= 0) {
        $errors[] = "Please select valid equipment.";
    }
    
    if ($user_id <= 0) {
        $errors[] = "Please select a valid user.";
    }
    
    if ($quantity <= 0) {
        $errors[] = "Please enter a valid quantity.";
    }
    
    $borrow_datetime = new DateTime($borrow_date);
    $due_datetime = new DateTime($due_date);
    
    if ($due_datetime <= $borrow_datetime) {
        $errors[] = "Due date must be after the borrow date.";
    }

    // Check equipment availability and quantity
    $check_equipment = "SELECT status, quantity FROM equipment WHERE equipment_id = ?";
    $stmt = $conn->prepare($check_equipment);
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $equipment_result = $stmt->get_result();
    
    if ($equipment_result->num_rows == 0) {
        $errors[] = "Equipment not found.";
    } else {
        $equipment_data = $equipment_result->fetch_assoc();
        if ($equipment_data['status'] !== 'available') {
            $errors[] = "Selected equipment is not available for borrowing.";
        }
        
        if ($equipment_data['quantity'] < $quantity) {
            $errors[] = "Requested quantity ({$quantity}) exceeds available quantity ({$equipment_data['quantity']}).";
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            $insert_borrowing = "INSERT INTO borrowings (
                equipment_id, 
                user_id, 
                borrow_date, 
                due_date, 
                status, 
                notes, 
                approval_status, 
                admin_issued_id,
                created_at,
                updated_at,
                approval_date,
                admin_notes,
                quantity
            ) VALUES (
                ?, ?, ?, ?, 
                'active', 
                ?, 
                'approved', 
                ?,
                NOW(),
                NOW(),
                NOW(),
                'Approved at creation',
                ?
            )";
            
            $stmt = $conn->prepare($insert_borrowing);
            $stmt->bind_param("iisssii", 
                $equipment_id, 
                $user_id, 
                $borrow_date, 
                $due_date, 
                $notes, 
                $_SESSION['user_id'],
                $quantity
            );
            
            $stmt->execute();
            
            $new_quantity = $equipment_data['quantity'] - $quantity;
            $equipment_status = $new_quantity > 0 ? 'available' : 'borrowed';
            
            $update_equipment = "UPDATE equipment 
                                SET quantity = ?, 
                                    status = ?,
                                    updated_at = NOW() 
                                WHERE equipment_id = ?";
            $stmt = $conn->prepare($update_equipment);
            $stmt->bind_param("isi", $new_quantity, $equipment_status, $equipment_id);
            $stmt->execute();
            
            $conn->commit();
            $_SESSION['success'] = "Equipment borrowed successfully.";
            header("Location: borrowings.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error processing borrowing: " . $e->getMessage();
        }
    }
}

include '../pages/borrowings/new_borrowing.html';
?>