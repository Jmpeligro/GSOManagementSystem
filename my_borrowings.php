<?php
session_start();
require_once 'db_connection.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$university_id = $_SESSION['university_id'] ?? null;

$sql = "SELECT 
    b.borrowing_id,
    b.equipment_id,
    b.user_id,
    b.borrow_date,
    b.due_date,
    b.return_date,
    b.admin_issued_id,
    b.admin_received_id,
    b.status,
    b.condition_on_return,
    b.notes,
    b.created_at,
    b.updated_at,
    b.approval_status,
    b.approved_by,
    b.approval_date,
    b.admin_notes,
    b.return_notes,
    b.returned_by,
    e.name as equipment_name, 
    e.equipment_code
    FROM borrowings b
    JOIN equipment e ON b.equipment_id = e.equipment_id
    WHERE b.user_id = ?
    ORDER BY b.borrow_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $borrowing_id = (int)$_GET['cancel'];

    $check_sql = "SELECT status, approval_status 
                 FROM borrowings 
                 WHERE borrowing_id = ? 
                 AND user_id = ? 
                 AND approval_status = 'pending'
                 AND status != 'active'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $borrowing_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $delete_sql = "DELETE FROM borrowings WHERE borrowing_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $borrowing_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Borrowing request cancelled successfully.";
        } else {
            $_SESSION['error'] = "Error cancelling request.";
        }
    } else {
        $_SESSION['error'] = "Invalid request or request already processed.";
    }
    
    header("Location: my_borrowings.php");
    exit();
}

include 'my_borrowings.html';


?>