<?php
session_start();
require_once 'db_connection.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No equipment specified.";
    header("Location: equipment.php");
    exit();
}

$equipment_id = (int)$_GET['id'];

$sql = "SELECT e.*, c.name as category_name 
        FROM equipment e
        JOIN categories c ON e.category_id = c.category_id
        WHERE e.equipment_id = ?";

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

$sql_history = "SELECT b.borrowing_id, u.first_name, u.last_name, 
                b.borrow_date, b.due_date, b.return_date, b.status
                FROM borrowings b
                JOIN users u ON b.user_id = u.user_id
                WHERE b.equipment_id = ?
                ORDER BY b.borrow_date DESC
                LIMIT 10";

$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $equipment_id);
$stmt_history->execute();
$history_result = $stmt_history->get_result();

$current_borrower = null;
if ($equipment['status'] == 'borrowed') {
    $sql_current = "SELECT b.borrowing_id, u.first_name, u.last_name, u.email,
                    b.borrow_date, b.due_date, b.purpose
                    FROM borrowings b
                    JOIN users u ON b.user_id = u.user_id
                    WHERE b.equipment_id = ? AND b.status = 'borrowed'
                    ORDER BY b.borrow_date DESC
                    LIMIT 1";
    
    $stmt_current = $conn->prepare($sql_current);
    $stmt_current->bind_param("i", $equipment_id);
    $stmt_current->execute();
    $current_result = $stmt_current->get_result();
    
    if ($current_result->num_rows > 0) {
        $current_borrower = $current_result->fetch_assoc();
    }
}

include 'view_equipment.html';
?>