<?php
session_start();
require_once '../db_connection.php';

if (!isLoggedIn()) {
    header("Location: php/login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: my_borrowings.php");
    exit();
}

$borrowing_id = (int)$_GET['id'];

$sql = "SELECT b.*, e.name as equipment_name, e.equipment_code, e.description as equipment_description,
        e.quantity as current_equipment_quantity, e.status as equipment_status,
        u.first_name, u.last_name, u.email,
        c.name as category_name,
        admin_issued.first_name as admin_issued_first_name, admin_issued.last_name as admin_issued_last_name,
        admin_approved.first_name as admin_approved_first_name, admin_approved.last_name as admin_approved_last_name,
        returned.first_name as returned_first_name, returned.last_name as returned_last_name
        FROM borrowings b
        JOIN equipment e ON b.equipment_id = e.equipment_id
        JOIN users u ON b.user_id = u.user_id
        JOIN categories c ON e.category_id = c.category_id
        LEFT JOIN users admin_issued ON b.admin_issued_id = admin_issued.user_id
        LEFT JOIN users admin_approved ON b.approved_by = admin_approved.user_id
        LEFT JOIN users returned ON b.returned_by = returned.user_id
        WHERE b.borrowing_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $borrowing_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Borrowing record not found.";
    header("Location: my_borrowings.php");
    exit();
}

$borrowing = $result->fetch_assoc();

if ($borrowing['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
    $_SESSION['error'] = "You don't have permission to view this borrowing record.";
    header("Location: my_borrowings.php");
    exit();
}

$is_overdue = ($borrowing['status'] == 'active' && strtotime($borrowing['due_date']) < time());
$status_class = $is_overdue ? 'overdue' : $borrowing['status'];

// Get borrowed quantity (default to 1 for older records)
$borrowed_quantity = isset($borrowing['quantity']) ? $borrowing['quantity'] : 1;

if (isset($_POST['return']) && ($borrowing['status'] == 'active' && $borrowing['approval_status'] == 'approved') && 
    (isAdmin() || $borrowing['user_id'] == $_SESSION['user_id'])) {
    
    // Get return quantity - default to borrowed quantity if not specified
    $return_quantity = isset($_POST['return_quantity']) && (int)$_POST['return_quantity'] > 0 
                      ? min((int)$_POST['return_quantity'], $borrowed_quantity) 
                      : $borrowed_quantity;
                      
    $conn->begin_transaction();
    try {
        // If returning all items
        if ($return_quantity >= $borrowed_quantity) {
            $update_borrowing_sql = "UPDATE borrowings SET 
                status = 'returned', 
                return_date = NOW(), 
                return_notes = ?, 
                returned_by = ?
                WHERE borrowing_id = ?";
            
            $return_notes = isset($_POST['return_notes']) ? sanitize($_POST['return_notes']) : 'Returned via system';
            
            $update_stmt = $conn->prepare($update_borrowing_sql);
            $update_stmt->bind_param("sii", $return_notes, $_SESSION['user_id'], $borrowing_id);
            $update_stmt->execute();
        } else {
            // If returning only part of the borrowed quantity
            $update_borrowing_sql = "UPDATE borrowings SET 
                quantity = quantity - ?,
                return_notes = CONCAT(return_notes, ' | Partially returned: ', ?, ' items on ', NOW()),
                updated_at = NOW()
                WHERE borrowing_id = ?";
                
            $return_notes = isset($_POST['return_notes']) ? sanitize($_POST['return_notes']) : 'Partial return';
            
            $update_stmt = $conn->prepare($update_borrowing_sql);
            $update_stmt->bind_param("isi", $return_quantity, $return_quantity, $borrowing_id);
            $update_stmt->execute();
        }
        

        $new_equipment_quantity = $borrowing['current_equipment_quantity'] + $return_quantity;
        $equipment_status = $new_equipment_quantity > 0 ? 'available' : $borrowing['equipment_status'];
        
        $update_equipment_sql = "UPDATE equipment 
                                SET quantity = ?, 
                                    status = ?,
                                    updated_at = NOW() 
                                WHERE equipment_id = ?";
        $update_equipment_stmt = $conn->prepare($update_equipment_sql);
        $update_equipment_stmt->bind_param("isi", $new_equipment_quantity, $equipment_status, $borrowing['equipment_id']);
        $update_equipment_stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = $return_quantity >= $borrowed_quantity 
                             ? "Equipment returned successfully." 
                             : "Partially returned {$return_quantity} item(s) successfully.";
        
        header("Location: view_borrowing.php?id=$borrowing_id");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error returning equipment: " . $e->getMessage();
    }
}

include '../../pages/borrowings/view_borrowing.html';
?>