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
        $update_borrowing = "UPDATE borrowings 
            SET approval_status = 'approved',
                status = 'active',
                approved_by = ?,
                approval_date = NOW(),
                admin_notes = 'Approved by administrator',
                updated_at = NOW()
            WHERE borrowing_id = ?";
        $stmt = $conn->prepare($update_borrowing);
        $stmt->bind_param("ii", $_SESSION['user_id'], $borrowing_id);
        $stmt->execute();

        $update_equipment = "UPDATE equipment 
            SET status = 'borrowed',
                updated_at = NOW() 
            WHERE equipment_id = ?";
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

    $update_sql = "UPDATE borrowings 
        SET approval_status = 'denied',
            admin_notes = ?,
            approved_by = ?,
            approval_date = NOW(),
            updated_at = NOW()
        WHERE borrowing_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sii", $denial_reason, $_SESSION['user_id'], $borrowing_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Borrowing request denied.";
    } else {
        $_SESSION['error'] = "Error denying request.";
    }
    
    header("Location: pending_request.php");
    exit();
}

$sql = "SELECT 
    b.borrowing_id,
    b.equipment_id,
    b.borrow_date,
    b.due_date,
    b.notes,
    b.admin_notes,
    b.approval_status,
    b.status,
    b.created_at,
    e.name AS equipment_name,
    e.equipment_code AS equipment_code,
    u.first_name,
    u.last_name,
    u.university_id
    FROM borrowings b
    INNER JOIN equipment e ON b.equipment_id = e.equipment_id
    INNER JOIN users u ON b.user_id = u.user_id
    WHERE b.approval_status = 'pending'
    ORDER BY b.created_at DESC";
$result = $conn->query($sql);

include 'pending_request.html';
?>
