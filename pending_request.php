<?php
session_start();
require_once 'db_connection.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}
if (isset($_POST['approve_request'])) {
    $borrowing_id = (int)$_POST['borrowing_id'];
    $equipment_id = (int)$_POST['equipment_id'];
    
    $conn->begin_transaction();
    
    try {
        $update_borrowing = "UPDATE borrowings SET status = 'approved' WHERE borrowing_id = ?";
        $stmt = $conn->prepare($update_borrowing);
        $stmt->bind_param("i", $borrowing_id);
        $stmt->execute();

        $update_equipment = "UPDATE equipment SET status = 'borrowed' WHERE equipment_id = ?";
        $stmt = $conn->prepare($update_equipment);
        $stmt->bind_param("i", $equipment_id);
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Borrowing request approved successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error approving request: " . $e->getMessage();
    }
    
    header("Location: pending_request.php");
    exit();
}

if (isset($_POST['deny_request'])) {
    $borrowing_id = (int)$_POST['borrowing_id'];
    $denial_reason = sanitize($_POST['denial_reason']);

    $update_sql = "UPDATE borrowings SET status = 'denied', denial_reason = ? WHERE borrowing_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $denial_reason, $borrowing_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Borrowing request denied.";
    } else {
        $_SESSION['error'] = "Error denying request.";
    }
    
    header("Location: pending_request.php");
    exit();
}

$sql = "SELECT 
            borrowings.borrowing_id,
            borrowings.equipment_id,
            borrowings.borrow_date,
            borrowings.due_date,
            borrowings.notes,
            equipment.name AS equipment_name,
            equipment.equipment_code AS equipment_code,
            users.first_name,
            users.last_name
        FROM borrowings
        INNER JOIN equipment ON borrowings.equipment_id = equipment.equipment_id
        INNER JOIN users ON borrowings.user_id = users.user_id
        WHERE borrowings.approval_status = 'pending'";
$result = $conn->query($sql);

include 'pending_request.html';
?>
